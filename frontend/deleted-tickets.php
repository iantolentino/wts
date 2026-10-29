<?php
require dirname(__DIR__).'/backend/app/bootstrap.php';
$user=require_login();
if(($user['role_slug']??'')!=='super-admin'){http_response_code(403);exit('Only a Super Admin can view deleted tickets.');}
require_permission('view_all_tickets');
if (($_SERVER['REQUEST_METHOD']??'')==='POST') {
    verify_csrf(); $key=request_string($_POST,'key');
    if (preg_match('/^whittle:([1-9][0-9]*)$/D',$key,$m)) {
        db()->beginTransaction();
        try { $id=(int)$m[1]; $changed=query('UPDATE tickets SET deleted_at=NULL,deleted_by=NULL,version=version+1 WHERE id=? AND deleted_at IS NOT NULL',[$id])->rowCount(); if($changed) activity($id,(int)$user['id'],'restored'); db()->commit(); } catch(Throwable $e) { db()->rollBack(); throw $e; }
    } elseif (preg_match('/^(stratast_support|stratast_escalations|stratast_wp346|stratast_requisition):[1-9][0-9]*$/D',$key)) {
        $changed=query('UPDATE ticket_visibility SET deleted_at=NULL,updated_by=? WHERE ticket_key=? AND deleted_at IS NOT NULL',[$user['id'],$key])->rowCount();
    } else { http_response_code(400); exit('Invalid ticket.'); }
    flash($changed?'Ticket restored.':'Ticket was already restored or not found.'); redirect('deleted-tickets.php');
}
$externalDeleted=query("SELECT ticket_key,deleted_at FROM ticket_visibility WHERE deleted_at IS NOT NULL ORDER BY deleted_at DESC")->fetchAll();
$total=(int)query('SELECT COUNT(*) FROM tickets WHERE deleted_at IS NOT NULL')->fetchColumn();
$page=page_number($total);
$rows=query('SELECT t.id,t.ticket_number,t.subject,t.employee_name,t.status,t.priority,t.created_at,t.deleted_at,u.full_name deleted_by_name,d.name department_name FROM tickets t LEFT JOIN departments d ON d.id=t.department_id LEFT JOIN users u ON u.id=t.deleted_by WHERE t.deleted_at IS NOT NULL ORDER BY t.deleted_at DESC,t.id DESC LIMIT 25 OFFSET '.(($page-1)*25))->fetchAll();
page_start('Deleted tickets',$user);
?><div class="section-head"><div><h2>Recently deleted tickets</h2><p class="muted">Soft-deleted tickets are kept here for Super Admin review.</p></div></div><section class="panel flush"><div class="table-wrap"><table><thead><tr><th>Ticket / subject</th><th>Employee</th><th>Department</th><th>Status</th><th>Priority</th><th>Deleted by</th><th>Deleted at</th><th>Action</th></tr></thead><tbody><?php foreach($rows as $ticket):?><tr><td><?=e($ticket['ticket_number'])?><span class="cell-secondary"><?=e($ticket['subject'])?></span></td><td><?=e($ticket['employee_name'])?></td><td><?=e($ticket['department_name']??'Unknown')?></td><td><span class="badge <?=e($ticket['status'])?>"><?=e(TICKET_STATUSES[$ticket['status']]??$ticket['status'])?></span></td><td><span class="badge <?=e($ticket['priority'])?>"><?=e(TICKET_PRIORITIES[$ticket['priority']]??$ticket['priority'])?></span></td><td><?=e($ticket['deleted_by_name']??'Unknown')?></td><td><?=e($ticket['deleted_at'])?></td><td><form method="post"><?php csrf_field();?><input type="hidden" name="key" value="whittle:<?=(int)$ticket['id']?>"><button class="button button-secondary">Restore</button></form></td></tr><?php endforeach;if(!$rows):?><tr><td colspan="8" class="empty">No deleted tickets.</td></tr><?php endif;?></tbody></table></div><?php pagination($total,$page);?></section><section class="panel"><h2>Deleted source tickets</h2><?php foreach($externalDeleted as $item):?><div class="section-head"><span><?=e($item['ticket_key'])?> · <?=e($item['deleted_at'])?></span><form method="post"><?php csrf_field();?><input type="hidden" name="key" value="<?=e($item['ticket_key'])?>"><button class="button button-secondary">Restore</button></form></div><?php endforeach;if(!$externalDeleted):?><p class="muted">No deleted source tickets.</p><?php endif;?></section><?php page_end();
