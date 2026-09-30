<?php
declare(strict_types=1);
require_once __DIR__ . '/feed.php';
/** Shared by Tickets and Overview so totals use the same visibility rules. */
function mixed_ticket_load(array $user, bool $includeExternal = true): array {
    [$where,$params]=ticket_filter([],$user);
    $local=query("SELECT t.*,d.name department_name,u.full_name assignee_name,c.full_name creator_name,
        GREATEST(t.updated_at,COALESCE((SELECT MAX(tc.created_at) FROM ticket_comments tc WHERE tc.ticket_id=t.id),t.updated_at)) updated_at,
        (SELECT GROUP_CONCAT(au.full_name ORDER BY au.full_name SEPARATOR ', ') FROM ticket_assignees ta JOIN users au ON au.id=ta.user_id WHERE ta.ticket_id=t.id) assignee_names,
        (SELECT GROUP_CONCAT(ad.name ORDER BY ad.name SEPARATOR ', ') FROM ticket_departments td JOIN departments ad ON ad.id=td.department_id WHERE td.ticket_id=t.id) department_names
        FROM tickets t JOIN departments d ON d.id=t.department_id LEFT JOIN users u ON u.id=t.assignee_id LEFT JOIN users c ON c.id=t.created_by".$where,$params)->fetchAll();
    $rows=array_map('mixed_local_ticket',$local); $feed=null; $feedError='';
    if ($includeExternal && ($user['role_slug']??'')!=='team-member') {
        try { $feed=whittle_feed_fetch(); $rows=array_merge($rows,array_map('mixed_external_ticket',$feed['tickets'])); }
        catch (RuntimeException $exception) { $feedError=$exception->getMessage(); }
    }
    return ['rows'=>$rows,'feed'=>$feed,'feed_error'=>$feedError];
}

function mixed_ticket_summary(array $rows, ?DateTimeImmutable $today = null): array {
    $today??=new DateTimeImmutable('today');
    $statuses=[];
    foreach(TICKET_STATUSES as $key=>$label)$statuses[$key]=['label'=>$label,'count'=>0];
    $sources=array_fill_keys(array_merge(['Whittle'],array_values(WHITTLE_FEED_SOURCES)),0);
    $months=[]; $start=$today->modify('first day of this month')->setTime(0,0)->modify('-5 months');
    for($i=0;$i<6;++$i){$month=$start->modify('+'.$i.' months');$months[$month->format('Y-m')]=['label'=>$month->format('M Y'),'active'=>0,'closed'=>0];}
    $urgent=0; $unassigned=0;
    foreach($rows as $row){
        $key=$row['status_key'];
        if(!isset($statuses[$key]))$statuses[$key]=['label'=>$row['status'],'count'=>0];
        ++$statuses[$key]['count'];
        $sources[$row['origin']]=($sources[$row['origin']]??0)+1;
        if($row['priority_key']==='urgent' && $key!=='closed')++$urgent;
        if($key!=='closed' && in_array(trim($row['assignee']),['','Unassigned'],true))++$unassigned;
        $month=substr($row['created'],0,7);
        if(isset($months[$month]))++$months[$month][$key==='closed'?'closed':'active'];
    }
    return ['total'=>count($rows),'statuses'=>$statuses,'sources'=>$sources,'months'=>$months,'urgent'=>$urgent,'active'=>count($rows)-($statuses['closed']['count']??0),'unassigned'=>$unassigned];
}
function mixed_ticket_status(string $status): string {
    $value = strtolower(trim((string) preg_replace('/[^a-zA-Z0-9]+/', ' ', $status)));
    return ['open'=>'open','new'=>'open','in progress'=>'in_progress','pending'=>'pending','closed'=>'closed','resolved'=>'closed'][$value] ?? 'source:' . $value;
}
function mixed_ticket_priority(string $priority): string {
    $value = strtolower(trim($priority));
    return ['low'=>'low','normal'=>'normal','medium'=>'normal','high'=>'high','urgent'=>'urgent','critical'=>'urgent'][$value] ?? 'source:' . $value;
}
function mixed_local_ticket(array $ticket): array {
    return [
        'key'=>'whittle:'.$ticket['id'], 'id'=>(int)$ticket['id'], 'origin'=>'Whittle', 'external'=>false,
        'url'=>'ticket.php?id='.(int)$ticket['id'], 'title'=>$ticket['subject'],
        'requestor'=>$ticket['issue_escalator'] ?: ($ticket['creator_name'] ?? $ticket['employee_name']),
        'status'=>TICKET_STATUSES[$ticket['status']] ?? $ticket['status'], 'status_key'=>$ticket['status'],
        'priority'=>TICKET_PRIORITIES[$ticket['priority']] ?? $ticket['priority'], 'priority_key'=>$ticket['priority'],
        'assignee'=>$ticket['assignee_names'] ?? $ticket['assignee_name'] ?? 'Unassigned',
        'department'=>$ticket['department_names'] ?? $ticket['department_name'] ?? 'Not provided',
        'category'=>$ticket['category'], 'created'=>$ticket['created_at'], 'updated'=>$ticket['updated_at'], 'staff_id'=>(int)($ticket['staff_id'] ?? 0),
    ];
}
function mixed_external_ticket(array $ticket): array {
    return [
        'key'=>$ticket['external_key'], 'id'=>$ticket['id'], 'origin'=>WHITTLE_FEED_SOURCES[$ticket['source_key']], 'external'=>true,
        'url'=>'external-tickets.php?'.http_build_query(['source'=>$ticket['source_key'],'id'=>$ticket['id']]),
        'title'=>$ticket['subject'], 'requestor'=>$ticket['requester'],
        'status'=>$ticket['status'], 'status_key'=>mixed_ticket_status($ticket['status']),
        'priority'=>$ticket['priority'], 'priority_key'=>mixed_ticket_priority($ticket['priority']),
        'assignee'=>$ticket['assignee'] ?? (!empty($ticket['agent']) ? 'Agent #'.$ticket['agent'] : 'Unassigned'),
        'department'=>WHITTLE_FEED_DEPARTMENTS[$ticket['source_key']], 'category'=>$ticket['category'],
        'created'=>$ticket['date'], 'updated'=>$ticket['last_update'] ?? '', 'staff_id'=>0,
    ];
}
function mixed_ticket_filter(array $source): array {
    $filters=[];
    foreach (['q','status','priority','department','assignee','category','origin','from','to','staff_id'] as $key) {
        if (isset($source[$key]) && !is_string($source[$key])) throw new InvalidArgumentException('Choose valid ticket filters.');
        $filters[$key]=trim(request_string($source,$key));
        if (strlen($filters[$key])>250) throw new InvalidArgumentException('Use a shorter ticket filter.');
    }
    foreach (['from','to'] as $key) if ($filters[$key]!=='') date_value($filters[$key],true);
    if ($filters['from']!=='' && $filters['to']!=='' && $filters['from']>$filters['to']) throw new InvalidArgumentException('Ticket date range is reversed.');
    return $filters;
}
function mixed_ticket_rows(array $rows,array $filters): array {
    $rows=array_values(array_filter($rows,static function(array $row) use($filters): bool {
        if ($filters['q']!=='' && mb_stripos(implode(' ',[$row['id'],$row['key'],$row['title'],$row['requestor'],$row['assignee'],$row['department'],$row['category']]),$filters['q'])===false) return false;
        foreach (['status'=>'status_key','priority'=>'priority_key','department'=>'department','assignee'=>'assignee','category'=>'category','origin'=>'origin'] as $filter=>$field) if ($filters[$filter]!=='' && $filters[$filter]!==$row[$field]) return false;
        if ($filters['staff_id']!=='' && (int)$filters['staff_id']>0 && (int)$filters['staff_id']!==$row['staff_id']) return false;
        $created=substr($row['created'],0,10);
        return ($filters['from']==='' || $created>=$filters['from']) && ($filters['to']==='' || $created<=$filters['to']);
    }));
    usort($rows,static function(array $a,array $b): int { return strcmp($b['created'],$a['created']) ?: strcmp($a['key'],$b['key']); });
    return $rows;
}
