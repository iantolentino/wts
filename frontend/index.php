<?php
require dirname(__DIR__) . '/backend/app/bootstrap.php';
$user=require_permission('view_all_tickets');
if (($_SERVER['REQUEST_METHOD']??'')!=='GET') { header('Allow: GET'); http_response_code(405); exit('Open a local ticket to edit it.'); }
require_once dirname(__DIR__) . '/backend/app/mixed-tickets.php';
$error=''; $feedError=''; $feed=null;
try { $filters=mixed_ticket_filter($_GET); }
catch (InvalidArgumentException $exception) { http_response_code(400); $error=$exception->getMessage(); $filters=mixed_ticket_filter([]); }
$loaded=mixed_ticket_load($user,$error==='');
$allRows=$loaded['rows']; $feed=$loaded['feed']; $feedError=$loaded['feed_error'];
$choices=['status'=>TICKET_STATUSES,'priority'=>TICKET_PRIORITIES,'department'=>[],'assignee'=>[],'category'=>[],'origin'=>[]];
foreach ($allRows as $row) {
    foreach (['status'=>'status_key','priority'=>'priority_key'] as $name=>$key) $choices[$name][$row[$key]]=$row[$name];
    foreach (['department','assignee','category','origin'] as $name) $choices[$name][$row[$name]]=$row[$name];
}
foreach ($choices as &$values) asort($values,SORT_NATURAL|SORT_FLAG_CASE);
unset($values);
$rows=mixed_ticket_rows($allRows,$filters); $total=count($rows); $page=page_number($total);
if (request_string($_GET,'download')==='csv') {
    // Everyone allowed to view this list may export the same scoped, filtered rows.
    if($error!==''){http_response_code(400);exit('Correct the ticket filters before exporting.');}
    if ($feedError!=='') { http_response_code(502); exit('The source feed is unavailable. Refresh successfully before exporting a complete ticket list.'); }
    // Obtain source details before sending headers: a partial report must never look complete.
    $exportRows=[];
    try {
        $sourceKeys=[];foreach($rows as $row)if($row['external'])$sourceKeys[]=['source'=>$row['source_key'],'id'=>(int)$row['id']];
        $sourceDetails=whittle_feed_fetch_many($sourceKeys);
        foreach($rows as $row){
            $issue=$row['issue']??''; $resolution=$row['resolution']??''; $conversation=''; $details=''; $activity=''; $attachments=''; $email=''; $closed=$row['closed']??''; $unavailable='';
            if($row['external']){
                $ticket=$sourceDetails[$row['key']];
                $email=$ticket['email']??''; $closed=$ticket['date_closed']??''; $unavailable=implode(', ',$ticket['unavailable_sections']??[]);
                if(in_array($user['role_slug']??'', ['super-admin','team-leader'],true))$activity=implode("\n",array_map(static fn($item)=>$item['date'].' '.($item['author']??'').': '.whittle_feed_text($item['body']),$ticket['activity']??[]));
                $attachments=implode("\n",array_map(static fn($item)=>$item['name'].' (#'.$item['id'].')',$ticket['attachments']??[]));
                $conversation=implode("\n",array_map(static fn($m)=>($m['date']??'').' '.($m['author']??'').': '.whittle_feed_text($m['body']),$ticket['thread']??[]));
                $details=implode("\n",array_map(static fn($item)=>$item['name'].': '.whittle_feed_text($item['value']),array_merge($ticket['details']??[],$ticket['custom_fields']??[])));
                $notes=query('SELECT n.created_at,u.full_name,n.body FROM external_ticket_notes n JOIN users u ON u.id=n.user_id WHERE n.source_key=? AND n.source_ticket_id=? ORDER BY n.id',[$row['source_key'],$row['id']])->fetchAll();
            }else{
                $attachments=implode("\n",array_column(query('SELECT original_name FROM ticket_attachments WHERE ticket_id=? AND file_data IS NOT NULL ORDER BY id',[$row['id']])->fetchAll(),'original_name'));
                $notes=query('SELECT c.created_at,u.full_name,c.body FROM ticket_comments c JOIN users u ON u.id=c.user_id WHERE c.ticket_id=? ORDER BY c.id',[$row['id']])->fetchAll();
            }
            $noteText=implode("\n",array_map(static fn($note)=>$note['created_at'].' '.$note['full_name'].': '.$note['body'],$notes));
            $staffNames=implode(', ',array_column($row['linked_staff']??[],'full_name')); if($staffNames==='')$staffNames=$row['employee']??'';
            $exportRows[]=[$row['id'].' ('.$row['origin'].')',$row['ticket_number']??'', $staffNames, $row['description']??'', $row['title'],$row['requestor'],$row['status'],$row['priority'],$row['assignee'],$row['department'],$row['category'],$row['created'],$row['updated'],$email,$closed,$issue,$resolution,$conversation,$details,$activity,$attachments,$noteText,$unavailable];
        }
    } catch (RuntimeException $exception) { http_response_code(502); exit('Ticket details could not be loaded. Please try the export again.'); }
    // Recheck access after fetching source details, before sending any report bytes.
    $currentUser=refresh_current_user();
    if (!$currentUser || !user_can('view_all_tickets')) {http_response_code(403);exit('Your access changed. Please sign in again.');}
    [$visibleWhere,$visibleParams]=ticket_filter([],$currentUser);
    $visibleLocal=array_fill_keys(array_map('intval',query('SELECT t.id FROM tickets t'.$visibleWhere,$visibleParams)->fetchAll(PDO::FETCH_COLUMN)),true);
    $visibility=query('SELECT ticket_key,confidential,deleted_at FROM ticket_visibility')->fetchAll(PDO::FETCH_UNIQUE);
    $cutoffs=source_ticket_cutoffs();
    foreach($rows as $row){
        if (!$row['external']) $allowed=isset($visibleLocal[$row['id']]);
        else {
            $v=$visibility[$row['key']]??null;
            $allowed=(!$v || ($v['deleted_at']===null && (!in_array($currentUser['role_slug']??'', ['management','client-viewer'],true) || !$v['confidential']))) && source_ticket_within_cutoff($sourceDetails[$row['key']],$cutoffs);
        }
        if(!$allowed){http_response_code(409);exit('Ticket visibility changed while preparing the report. Please refresh and export again.');}
    }
    csv_download('whittles-tickets',['ID','Ticket number','Staff','Description','Title','Requestor','Status','Priority','Assignee','Assigned Department','Category','Created','Last update','Requestor email','Closed','Issue','Resolution','Source conversation','Source details','Source activity','Attachments','Portal notes','Unavailable source sections'],$exportRows);
}
$pages=max(1,(int)ceil($total/25));
$pageLinks=array_values(array_unique(array_merge([1,$pages],range(max(1,$page-2),min($pages,$page+2)))));
sort($pageLinks);
$pageUrl=static fn(int $target): string=>'index.php?'.http_build_query(array_merge($filters,['page'=>$target]));
$rows=array_slice($rows,($page-1)*25,25); page_start('Tickets',$user);
?>
<style>
.tickets-screen .ticket-pagination{display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:12px;padding:16px}
.tickets-screen .ticket-pagination-links{display:flex;flex-wrap:wrap;align-items:center;gap:6px}
.tickets-screen .ticket-pagination-links .button{min-width:36px;justify-content:center}
.tickets-screen .ticket-pagination-links [aria-disabled="true"]{opacity:.45;cursor:default}
.tickets-screen .ticket-pagination-links [aria-current="page"]{font-weight:700}
.tickets-screen .ticket-pagination-gap{padding:0 4px}
</style>
<div class="tickets-screen">
<?php error_notice($error); if($feedError!=='')error_notice('Source tickets could not be loaded. The list below contains local tickets only. '.$feedError); ?>
<?php if($feed!==null && !in_array('reply_comments',$feed['capabilities']??[],true)):?><p class="notice">Some comment details are unavailable until the fetcher update is uploaded.</p><?php endif;?>
<section class="panel ticket-filter-panel"><form method="get"><?php if($filters['origin']!==''):?><input type="hidden" name="origin" value="<?=e($filters['origin'])?>"><p class="muted">Source: <?=e($filters['origin'])?> · <a href="index.php?<?=e(http_build_query(array_merge($filters,['origin'=>''])))?>">Show all sources</a></p><?php endif;?>
<div class="ticket-search-toolbar"><label class="ticket-search-field"><span class="visually-hidden">Search tickets</span><input name="q" aria-label="Search tickets" maxlength="250" placeholder="Search tickets..." value="<?=e($filters['q'])?>"></label><div class="actions"><button class="button">Apply</button><a class="button button-secondary" href="index.php?<?=e(http_build_query($_GET))?>">Refresh</a><button class="button button-secondary" type="submit" name="download" value="csv">Export CSV</button></div></div>
<div class="ticket-filter-row"><span class="ticket-filter-row-title">Filter</span><label class="ticket-filter-field"><span class="visually-hidden">Staff member</span><select name="staff_id" aria-label="Staff member"><option value="">All staff</option><?php options(query("SELECT id,full_name FROM staff_directory ORDER BY full_name")->fetchAll(),$filters['staff_id'],'full_name');?></select></label>
<?php foreach(['status'=>['Status','All statuses'],'priority'=>['Priority','All priorities'],'department'=>['Assigned Department','All departments'],'category'=>['Category','All categories'],'assignee'=>['Assignee','All assignees']] as $key=>[$label,$all]):?><label class="ticket-filter-field"><span class="visually-hidden"><?=e($label)?></span><select name="<?=e($key)?>" aria-label="<?=e($label)?>"><option value=""><?=e($all)?></option><?php foreach($choices[$key] as $value=>$text):?><option value="<?=e((string)$value)?>" <?=$filters[$key]===(string)$value?'selected':''?>><?=e((string)$text)?></option><?php endforeach;?></select></label><?php endforeach;?>
<details class="ticket-date-filter" <?=$filters['from']!==''||$filters['to']!==''?'open':''?>><summary>Date range</summary><div><label>From<input type="date" name="from" value="<?=e($filters['from'])?>"></label><label>To<input type="date" name="to" value="<?=e($filters['to'])?>"></label><button class="button">Apply dates</button></div></details><a class="button button-secondary filter-reset" href="index.php">Reset</a></div>
</form></section>
<section class="panel flush ticket-table-panel"><div class="table-wrap"><table class="mixed-table"><thead><tr><?php foreach(['ID','Title','Requestor','Status','Priority','Assignee','Assigned Department','Category','Created','Last update'] as $heading):?><th><?=e($heading)?></th><?php endforeach;?></tr></thead><tbody>
<?php foreach($rows as $ticket):?><tr data-ticket-key="<?=e($ticket['key'])?>"><td data-label="ID"><a href="<?=e($ticket['url'])?>">#<?=$ticket['id']?></a><span class="cell-secondary"><?=e($ticket['origin'])?></span></td><td data-label="Title" class="mixed-title"><a href="<?=e($ticket['url'])?>" title="<?=e($ticket['title'])?>"><?=e($ticket['title'])?></a><?php if($ticket['external']):?><span class="cell-secondary">Source ticket</span><?php endif;?></td><td data-label="Requestor"><?=e($ticket['requestor'])?></td><td data-label="Status"><span class="badge <?=isset(TICKET_STATUSES[$ticket['status_key']])?e($ticket['status_key']):''?>"><?=e($ticket['status'])?></span></td><td data-label="Priority"><span class="badge"><?=e($ticket['priority'])?></span></td><td data-label="Assignee"><?=e($ticket['assignee'])?></td><td data-label="Department"><?=e($ticket['department'])?></td><td data-label="Category"><?=e($ticket['category'])?></td><td data-label="Created" class="mixed-date"><?=e($ticket['created'])?></td><td data-label="Last update" class="mixed-date"><?=e($ticket['updated']!==''?$ticket['updated']:'Not provided')?></td></tr><?php endforeach;?>
<?php if(!$rows):?><tr><td colspan="10" class="empty">No tickets match these filters.</td></tr><?php endif;?></tbody></table></div>
<div class="pagination ticket-pagination"><span><?=number_format($total)?> records &middot; Page <?=$page?> of <?=$pages?></span>
<nav class="ticket-pagination-links" aria-label="Ticket pagination">
<?php foreach(['First'=>1,'Previous'=>$page-1] as $label=>$target):?>
<?php if($page>1):?><a class="button button-secondary" href="<?=e($pageUrl($target))?>"><?=e($label)?></a><?php else:?><span class="button button-secondary" aria-disabled="true"><?=e($label)?></span><?php endif;?>
<?php endforeach;?>
<?php $previous=0; foreach($pageLinks as $target):?>
<?php if($previous>0 && $target>$previous+1):?><span class="ticket-pagination-gap" aria-hidden="true">&hellip;</span><?php endif;?>
<?php if($target===$page):?><span class="button" aria-current="page" aria-label="Page <?=$target?>"><?=$target?></span><?php else:?><a class="button button-secondary" href="<?=e($pageUrl($target))?>" aria-label="Page <?=$target?>"><?=$target?></a><?php endif;?>
<?php $previous=$target; endforeach;?>
<?php foreach(['Next'=>$page+1,'Last'=>$pages] as $label=>$target):?>
<?php if($page<$pages):?><a class="button button-secondary" href="<?=e($pageUrl($target))?>"><?=e($label)?></a><?php else:?><span class="button button-secondary" aria-disabled="true"><?=e($label)?></span><?php endif;?>
<?php endforeach;?>
</nav></div></section></div><script src="assets/js/quiet-refresh.js?v=20261029" defer></script><?php page_end();?>
