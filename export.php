<?php
require __DIR__.'/app/bootstrap.php';$user=require_login();$type=request_string($_GET,'type');
try{
    if($type==='staff'){
        require_permission('export_staff');[$where,$params]=staff_filter($_GET);
        $rows=query('SELECT s.employee_code,s.full_name,s.position,s.shift_schedule,d.name department,s.team_label,u.full_name team_leader,s.start_date,s.exit_date,s.employment_status,s.notes FROM staff_directory s LEFT JOIN departments d ON d.id=s.department_id LEFT JOIN users u ON u.id=s.tl_id'.$where.' ORDER BY s.full_name',$params);
        csv_download('wheettle-staff-'.date('Y-m-d'),['Employee code','Name','Position','Shift schedule','Department','Team','Team leader','Start date','Exit date','Status','Notes'],$rows);
    }elseif($type==='tickets'){
        require_permission('export_tickets');[$where,$params]=ticket_filter($_GET,$user);
        $rows=query('SELECT t.ticket_number,t.subject,t.employee_name,d.name department,t.category,t.priority,t.status,u.full_name assignee,t.issue,t.resolution,t.created_at,t.closed_at FROM tickets t JOIN departments d ON d.id=t.department_id LEFT JOIN users u ON u.id=t.assignee_id'.$where.' ORDER BY t.id DESC',$params);
        csv_download('wheettle-tickets-'.date('Y-m-d'),['Ticket','Subject','Employee','Department','Category','Priority','Status','Assigned to','Issue','Resolution','Created','Closed'],$rows);
    }elseif($type==='history'){
        require_permission('export_staff');$source=$_GET;unset($source['from'],$source['to']);[$where,$params]=staff_filter($source);
        $from=request_string($_GET,'from');$to=request_string($_GET,'to');
        if($from!=='' && $to!=='' && $from>$to)throw new InvalidArgumentException('Invalid history date range.');
        foreach(['from'=>'>=','to'=>'<='] as $key=>$op){$date=request_string($_GET,$key);if($date!==''){date_value($date,true);$where.=($where?' AND ':' WHERE ').'h.effective_date'.$op.'?';$params[]=$date;}}
        if($id=positive_id($_GET,'staff_id')){$where.=($where?' AND ':' WHERE ').'h.staff_id=?';$params[]=$id;}
        $rows=query('SELECT s.employee_code,s.full_name,h.event_type,h.effective_date,h.notes,u.full_name actor,h.created_at,h.before_data,h.after_data FROM staff_history h JOIN staff_directory s ON s.id=h.staff_id JOIN users u ON u.id=h.actor_id'.$where.' ORDER BY h.effective_date DESC,h.id DESC',$params);
        csv_download('wheettle-history-'.date('Y-m-d'),['Employee code','Name','Event','Effective date','Notes','Recorded by','Recorded at','Before profile','After profile'],$rows);
    }elseif($type==='report'){
        require_permission('view_reports');require_permission('export_staff');$from=date_value(request_string($_GET,'from'),true);$to=date_value(request_string($_GET,'to'),true);if($from>$to)throw new InvalidArgumentException('Invalid report date range.');
        $department=positive_id($_GET,'department_id');$scope=$department?' WHERE department_id=?':' WHERE 1=1';$params=$department?[$department]:[];
        $rows=[];foreach(STAFF_STATUSES as $status=>$label)$rows[]=['Current '.$label,query('SELECT COUNT(*) FROM staff_directory'.$scope.' AND employment_status=?',array_merge($params,[$status]))->fetchColumn()];
        foreach(['start_date'=>'Started in period','exit_date'=>'Exited in period'] as $field=>$label)$rows[]=[$label,query('SELECT COUNT(*) FROM staff_directory'.$scope.' AND '.$field.' BETWEEN ? AND ?',array_merge($params,[$from,$to]))->fetchColumn()];
        [$where,$tp]=ticket_filter(['from'=>$from,'to'=>$to,'department_id'=>(string)$department],$user);
        foreach(TICKET_STATUSES as $status=>$label)$rows[]=['Tickets created in period / '.$label,query('SELECT COUNT(*) FROM tickets t'.$where.' AND status=?',array_merge($tp,[$status]))->fetchColumn()];
        $rows[]=['Period from',$from];$rows[]=['Period to',$to];$rows[]=['Department',$department?query('SELECT name FROM departments WHERE id=?',[$department])->fetchColumn():'All departments'];
        csv_download('wheettle-report-'.date('Y-m-d'),['Metric','Value'],$rows);
    }else{http_response_code(400);exit('Unknown export type.');}
}catch(InvalidArgumentException $e){http_response_code(400);echo e($e->getMessage());}
