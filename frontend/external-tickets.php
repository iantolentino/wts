<?php
require dirname(__DIR__) . '/backend/app/bootstrap.php';
$user=require_non_team_member(require_permission('view_all_tickets'));
require_once dirname(__DIR__) . '/backend/app/feed.php';
if (!in_array($_SERVER['REQUEST_METHOD']??'', ['GET','POST'], true)) { header('Allow: GET, POST'); http_response_code(405); exit; }
$source=request_string($_GET,'source'); $id=request_string($_GET,'id');
if ($id==='' && (!isset($_GET['id']) || is_string($_GET['id']))) {
    $params=[];
    if(isset(WHITTLE_FEED_SOURCES[$source]))$params['origin']=WHITTLE_FEED_SOURCES[$source];
    $q=request_string($_GET,'q');if($q!=='')$params['q']=$q;
    redirect('index.php'.($params?'?'.http_build_query($params):''));
}
$error=''; $ticket=null; $data=null;
try {
    if (!is_string($_GET['source']??null) || !is_string($_GET['id']??null) || !isset(WHITTLE_FEED_SOURCES[$source]) || !ctype_digit($id) || filter_var($id,FILTER_VALIDATE_INT)===false || (int)$id<1) throw new InvalidArgumentException('Choose a valid source ticket.');
    $visibility=query('SELECT confidential,deleted_at FROM ticket_visibility WHERE ticket_key=?',[$source.':'.$id])->fetch();
    if ($visibility && ($visibility['deleted_at']!==null || (!empty($visibility['confidential']) && in_array($user['role_slug'],['management','client-viewer'],true)))) { http_response_code(404); exit('Ticket not found.'); }
    $cutoffs=source_ticket_cutoffs();
    if ($cutoffs) {
        $list=whittle_feed_fetch($source); $matches=array_values(array_filter($list['tickets'],static fn($candidate)=>(int)$candidate['id']===(int)$id));
        if (!$matches || !source_ticket_within_cutoff($matches[0],$cutoffs)) {http_response_code(404);exit('Ticket not found.');}
    }
    $data=whittle_feed_fetch($source,(int)$id); $ticket=$data['tickets'][0];
    if(!source_ticket_within_cutoff($ticket,$cutoffs)) {http_response_code(404);exit('Ticket not found.');}
    $ticket['assigned_department']=WHITTLE_FEED_DEPARTMENTS[$source];
} catch (InvalidArgumentException $exception) { http_response_code(400); $error=$exception->getMessage(); }
catch (RuntimeException $exception) { http_response_code(502); $error=$exception->getMessage(); }
if ($ticket !== null && ($_SERVER['REQUEST_METHOD']??'')==='POST') {
    verify_csrf();
    if (request_string($_POST,'action')==='delete') {
        if (($user['role_slug']??'')!=='super-admin') { http_response_code(403); exit('Only a Super Admin can delete tickets.'); }
        query('INSERT INTO ticket_visibility(ticket_key,deleted_at,updated_by) VALUES(?,NOW(),?) ON DUPLICATE KEY UPDATE deleted_at=NOW(),updated_by=VALUES(updated_by)',[$source.':'.$id,$user['id']]);
        flash('Ticket moved to deleted records.'); redirect('index.php');
    }
    if (request_string($_POST,'action')==='visibility') {
        if (($user['role_slug']??'')!=='super-admin') { http_response_code(403); exit('Only a Super Admin can change visibility.'); }
        $hidden=request_string($_POST,'hidden')==='1'?1:0;
        query('INSERT INTO ticket_visibility(ticket_key,confidential,updated_by) VALUES(?,?,?) ON DUPLICATE KEY UPDATE confidential=VALUES(confidential),updated_by=VALUES(updated_by)',[$source.':'.$id,$hidden,$user['id']]);
        flash($hidden?'Ticket hidden from clients and Management.':'Ticket visible to clients and Management.'); redirect('external-tickets.php?'.http_build_query(['source'=>$source,'id'=>$id]));
    }
    require_permission('comment_tickets');
    try {
        $body=input('body',10000);
        if ($body==='') throw new InvalidArgumentException('Write a note first.');
        db()->beginTransaction();
        query('INSERT INTO ticket_visibility(ticket_key,updated_by) VALUES(?,?) ON DUPLICATE KEY UPDATE ticket_key=VALUES(ticket_key)',[$source.':'.$id,$user['id']]);
        $current=query('SELECT confidential,deleted_at FROM ticket_visibility WHERE ticket_key=? FOR UPDATE',[$source.':'.$id])->fetch();
        if($current['deleted_at']!==null || (!empty($current['confidential']) && in_array($user['role_slug'],['management','client-viewer'],true))) {db()->rollBack();http_response_code(404);exit('Ticket not found.');}
        query('INSERT INTO external_ticket_notes(source_key,source_ticket_id,user_id,body) VALUES(?,?,?,?)',[$source,(int)$id,$user['id'],$body]);db()->commit();
        flash('Note added.'); redirect('external-tickets.php?'.http_build_query(['source'=>$source,'id'=>$id]));
    } catch (InvalidArgumentException $exception) { if(db()->inTransaction())db()->rollBack();$error=$exception->getMessage(); }
      catch (Throwable $exception) { if(db()->inTransaction())db()->rollBack();throw $exception; }
}
$localNotes=$ticket!==null ? query('SELECT n.*,u.full_name FROM external_ticket_notes n JOIN users u ON u.id=n.user_id WHERE n.source_key=? AND n.source_ticket_id=? ORDER BY n.id DESC',[$source,(int)$id])->fetchAll() : [];
if ($ticket!==null && $localNotes) $ticket['last_update']=whittle_latest_ticket_update($ticket['last_update']??'',$localNotes[0]['created_at']);
page_start('Source ticket',$user);
?>
<div class="ticket-detail">
<a class="back-link" href="index.php">&larr; Tickets</a><?php error_notice($error);?>
<?php if($ticket!==null):?>
<header class="detail-header"><div><p class="detail-kicker"><?=e(WHITTLE_FEED_SOURCES[$source])?> &middot; #<?=$ticket['id']?></p><h2><?=e($ticket['subject'])?></h2><p class="muted">Created <?=e($ticket['date'])?></p></div><div class="detail-badges"><span class="badge"><?=e($ticket['status'])?></span><span class="badge"><?=e($ticket['priority'])?></span><span class="badge">Source ticket</span></div></header>
<?php if(!in_array('full_details',$data['capabilities']??[],true)):?><p class="notice">Some details are unavailable until the fetcher update is uploaded.</p><?php endif;?>
<?php if(!empty($ticket['unavailable_sections'])):?><p class="notice">Unavailable source sections: <?=e(implode(', ', $ticket['unavailable_sections']))?>.</p><?php endif;?>
<div class="detail-grid"><div>
<section class="panel"><div class="section-head"><h2>Conversation</h2><span class="muted"><?=count($ticket['thread'])?> messages</span></div>
<?php foreach($ticket['thread'] as $message):$type=$message['type']??'report';?><article class="source-comment <?=$type==='note'?'is-note':''?>"><div class="comment-head"><div><h3><?=e(['report'=>'Original ticket','reply'=>'Comment','note'=>'Internal note'][$type]??'Comment')?></h3><small><?=e((string)($message['author']??'Source user'))?><?php if(!empty($message['author_email'])):?> &middot; <?=e($message['author_email'])?><?php endif;?></small></div><small><?=e($message['date'])?><?php if(!empty($message['updated_at']) && $message['updated_at']!==$message['date']):?><br>Updated <?=e($message['updated_at'])?><?php endif;?></small></div><div class="rich-text"><?=whittle_feed_html($message['body'])?></div><?php if(!empty($message['attachment_ids'])):?><ul class="detail-attachments"><?php foreach($message['attachment_ids'] as $attachmentId):$matches=array_values(array_filter($ticket['attachments']??[],static fn($file)=>$file['id']===$attachmentId));?><li><?=e($matches[0]['name']??('Attachment #'.$attachmentId))?> <small>Download from the original source system.</small></li><?php endforeach;?></ul><?php endif;?></article><?php endforeach;if(!$ticket['thread']):?><p class="muted">No conversation is available from this source.</p><?php endif;?></section>
<section class="panel"><h2>Notes</h2><p class="muted">Notes added here are visible to everyone who can view this ticket.</p><?php foreach($localNotes as $note):?><article class="detail-comment"><div class="comment-head"><h3><?=e($note['full_name'])?></h3><small><?=e($note['created_at'])?></small></div><div class="plain-text"><?=e($note['body'])?></div></article><?php endforeach;if(!$localNotes):?><p class="muted">No notes yet.</p><?php endif;if(user_can('comment_tickets')):?><form method="post"><?php csrf_field();?><label>Add a note<textarea name="body" rows="4" maxlength="10000" required></textarea></label><div class="form-footer"><button class="button">Add note</button></div></form><?php endif;?></section>
<?php if(!empty($ticket['custom_fields'])):?><section class="panel"><h2>Custom fields</h2><dl class="detail-facts"><?php foreach($ticket['custom_fields'] as $field):?><div><dt><?=e($field['name'])?></dt><dd><?=e(whittle_feed_text($field['value']===''?'Not provided':$field['value']))?></dd></div><?php endforeach;?></dl></section><?php endif;?>
<?php if(in_array($user['role_slug']??'', ['super-admin','team-leader'],true) && !empty($ticket['activity'])):?><details class="panel" open><summary>Ticket activity</summary><?php foreach($ticket['activity'] as $entry):?><article class="source-comment"><div class="comment-head"><h3><?=e($entry['author'])?></h3><small><?=e($entry['date'])?></small></div><div class="rich-text"><?=whittle_feed_html($entry['body'])?></div></article><?php endforeach;?></details><?php endif;?>
</div><aside>
<?php if(($user['role_slug']??'')==='super-admin'):$hidden=!empty($visibility['confidential']);?><section class="panel"><h2>Client visibility</h2><p><?=$hidden?'Hidden from clients and Management.':'Visible to clients and Management.'?></p><form method="post"><?php csrf_field();?><input type="hidden" name="action" value="visibility"><input type="hidden" name="hidden" value="<?=$hidden?'0':'1'?>"><button class="button button-secondary"><?=$hidden?'Make visible':'Hide confidential ticket'?></button></form></section><?php endif;?>
<?php if(($user['role_slug']??'')==='super-admin'):?><section class="panel"><h2>Delete ticket</h2><form method="post" onsubmit="return confirm('Move this ticket to deleted records?');"><?php csrf_field();?><input type="hidden" name="action" value="delete"><button class="button button-secondary">Delete ticket</button></form></section><?php endif;?>
<section class="panel"><h2>Ticket details</h2><dl class="detail-facts"><?php foreach(['requester'=>'Requestor','email'=>'Requestor email','assignee'=>'Assignee','assigned_department'=>'Assigned Department','category'=>'Category','date'=>'Created','last_update'=>'Last update','date_closed'=>'Closed'] as $field=>$label):?><div><dt><?=e($label)?></dt><dd><?=e((string)($ticket[$field]??($field==='assignee'?'Unassigned':'Not provided')))?></dd></div><?php endforeach;?></dl><p class="muted">Source ticket details can only be edited in the source system. Notes added here stay in this portal.</p></section>
<?php if(!empty($ticket['attachments'])):?><section class="panel"><h2>Attachments</h2><ul class="detail-attachments"><?php foreach($ticket['attachments'] as $attachment):?><li><?=e($attachment['name'])?> <small>#<?=$attachment['id']?> &middot; <?=e($attachment['date'])?></small></li><?php endforeach;?></ul><p class="muted">Download files from the original source system.</p></section><?php endif;?>
<?php if(!empty($ticket['details'])):?><details class="panel" open><summary>Additional details</summary><dl class="detail-facts"><?php foreach($ticket['details'] as $field):?><div><dt><?=e($field['name'])?></dt><dd><?=e(whittle_feed_text($field['value']===''?'Not provided':$field['value']))?></dd></div><?php endforeach;?></dl></details><?php endif;?>
</aside></div>
<?php endif;?></div><?php page_end();?>
