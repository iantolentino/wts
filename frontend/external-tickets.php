<?php
require dirname(__DIR__) . '/backend/app/bootstrap.php';
$user=require_non_team_member(require_permission('view_all_tickets'));
require_once dirname(__DIR__) . '/backend/app/feed.php';
if (($_SERVER['REQUEST_METHOD']??'')!=='GET') {
    header('Allow: GET'); http_response_code(405); exit('Source tickets and comments are read-only.');
}
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
    session_write_close(); $data=whittle_feed_fetch($source,(int)$id); $ticket=$data['tickets'][0];
    $ticket['assigned_department']=WHITTLE_FEED_DEPARTMENTS[$source];
} catch (InvalidArgumentException $exception) { http_response_code(400); $error=$exception->getMessage(); }
catch (RuntimeException $exception) { http_response_code(502); $error=$exception->getMessage(); }
page_start('Source ticket',$user);
?>
<div class="ticket-detail">
<a class="back-link" href="index.php">&larr; Tickets</a><?php error_notice($error);?>
<?php if($ticket!==null):?>
<header class="detail-header"><div><p class="detail-kicker"><?=e(WHITTLE_FEED_SOURCES[$source])?> &middot; #<?=$ticket['id']?></p><h2><?=e($ticket['subject'])?></h2><p class="muted">Created <?=e($ticket['date'])?></p></div><div class="detail-badges"><span class="badge"><?=e($ticket['status'])?></span><span class="badge"><?=e($ticket['priority'])?></span><span class="badge">Read-only</span></div></header>
<?php if(!in_array('full_details',$data['capabilities']??[],true)):?><p class="notice">Some details are unavailable until the fetcher update is uploaded.</p><?php endif;?>
<?php if(!empty($ticket['unavailable_sections'])):?><p class="notice">Unavailable source sections: <?=e(implode(', ', $ticket['unavailable_sections']))?>.</p><?php endif;?>
<div class="detail-grid"><div>
<section class="panel"><div class="section-head"><h2>Conversation</h2><span class="muted"><?=count($ticket['thread'])?> messages</span></div>
<?php foreach($ticket['thread'] as $message):$type=$message['type']??'report';?><article class="source-comment <?=$type==='note'?'is-note':''?>"><div class="comment-head"><div><h3><?=e(['report'=>'Original ticket','reply'=>'Comment','note'=>'Internal note'][$type]??'Comment')?></h3><small><?=e((string)($message['author']??'Source user'))?><?php if(!empty($message['author_email'])):?> &middot; <?=e($message['author_email'])?><?php endif;?></small></div><small><?=e($message['date'])?><?php if(!empty($message['updated_at']) && $message['updated_at']!==$message['date']):?><br>Updated <?=e($message['updated_at'])?><?php endif;?></small></div><div class="rich-text"><?=whittle_feed_html($message['body'])?></div><?php if(!empty($message['attachment_ids'])):?><ul class="detail-attachments"><?php foreach($message['attachment_ids'] as $attachmentId):$matches=array_values(array_filter($ticket['attachments']??[],static fn($file)=>$file['id']===$attachmentId));?><li><?=e($matches[0]['name']??('Attachment #'.$attachmentId))?> <small>Download from the original source system.</small></li><?php endforeach;?></ul><?php endif;?></article><?php endforeach;if(!$ticket['thread']):?><p class="muted">No conversation is available from this source.</p><?php endif;?></section>
<?php if(!empty($ticket['custom_fields'])):?><section class="panel"><h2>Custom fields</h2><dl class="detail-facts"><?php foreach($ticket['custom_fields'] as $field):?><div><dt><?=e($field['name'])?></dt><dd><?=e(whittle_feed_text($field['value']===''?'Not provided':$field['value']))?></dd></div><?php endforeach;?></dl></section><?php endif;?>
<?php if(!empty($ticket['activity'])):?><details class="panel" open><summary>Ticket activity</summary><?php foreach($ticket['activity'] as $entry):?><article class="source-comment"><div class="comment-head"><h3><?=e($entry['author'])?></h3><small><?=e($entry['date'])?></small></div><div class="rich-text"><?=whittle_feed_html($entry['body'])?></div></article><?php endforeach;?></details><?php endif;?>
</div><aside>
<section class="panel"><h2>Ticket details</h2><dl class="detail-facts"><?php foreach(['requester'=>'Requestor','email'=>'Requestor email','assignee'=>'Assignee','assigned_department'=>'Assigned Department','category'=>'Category','date'=>'Created','last_update'=>'Last update','date_closed'=>'Closed'] as $field=>$label):?><div><dt><?=e($label)?></dt><dd><?=e((string)($ticket[$field]??($field==='assignee'?'Unassigned':'Not provided')))?></dd></div><?php endforeach;?></dl><p class="muted">This ticket and its comments can only be edited in the source system.</p></section>
<?php if(!empty($ticket['attachments'])):?><section class="panel"><h2>Attachments</h2><ul class="detail-attachments"><?php foreach($ticket['attachments'] as $attachment):?><li><?=e($attachment['name'])?> <small>#<?=$attachment['id']?> &middot; <?=e($attachment['date'])?></small></li><?php endforeach;?></ul><p class="muted">Download files from the original source system.</p></section><?php endif;?>
<?php if(!empty($ticket['details'])):?><details class="panel" open><summary>Additional details</summary><dl class="detail-facts"><?php foreach($ticket['details'] as $field):?><div><dt><?=e($field['name'])?></dt><dd><?=e(whittle_feed_text($field['value']===''?'Not provided':$field['value']))?></dd></div><?php endforeach;?></dl></details><?php endif;?>
</aside></div>
<?php endif;?></div><?php page_end();?>
