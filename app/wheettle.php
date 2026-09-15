<?php
declare(strict_types=1);

const STAFF_STATUSES = ['new' => 'New staff', 'active' => 'Active', 'exited' => 'Exited'];
const TICKET_STATUSES = ['open' => 'Open', 'in_progress' => 'In progress', 'pending' => 'Pending', 'closed' => 'Closed'];
const STAFF_FIELDS = ['employee_code','full_name','position','shift_schedule','department_id','team_label','tl_id','start_date','exit_date','employment_status','notes'];

function query(string $sql, array $params = []): PDOStatement {
    $statement = db()->prepare($sql);
    $statement->execute($params);
    return $statement;
}
function input(string $key, int $max = 2000): string {
    $value = trim(request_string($_POST, $key));
    if (mb_strlen($value) > $max) throw new InvalidArgumentException(ucfirst(str_replace('_',' ', $key)) . ' is too long.');
    return $value;
}
function date_value(string $value, bool $required = false): ?string {
    if ($value === '' && !$required) return null;
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
    if (!$date || $date->format('Y-m-d') !== $value) throw new InvalidArgumentException('Enter a valid date.');
    return $value;
}
function positive_id(array $source, string $key): int {
    $value = request_string($source, $key);
    return ctype_digit($value) ? (int)$value : 0;
}
function flash(string $message): void { $_SESSION['flash'] = $message; }
function csrf_field(): void { echo '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">'; }
function options(array $rows, string $selected = '', string $label = 'name'): void {
    foreach ($rows as $row) echo '<option value="'.e((string)$row['id']).'"'.((string)$row['id'] === $selected ? ' selected' : '').'>'.e($row[$label]).'</option>';
}
function enum_options(array $values, string $selected): void {
    foreach ($values as $key => $label) echo '<option value="'.e($key).'"'.($selected === $key ? ' selected' : '').'>'.e($label).'</option>';
}
function tls(): array { return query("SELECT u.id,u.full_name FROM users u JOIN roles r ON r.id=u.role_id WHERE u.is_active=1 AND r.slug='team-leader' ORDER BY u.full_name")->fetchAll(); }
function staff_record(int $id, bool $lock = false): array {
    $row = query('SELECT s.*,d.name AS department_name,u.full_name AS tl_name FROM staff_directory s LEFT JOIN departments d ON d.id=s.department_id LEFT JOIN users u ON u.id=s.tl_id WHERE s.id=?' . ($lock ? ' FOR UPDATE' : ''), [$id])->fetch();
    if (!$row) { http_response_code(404); exit('Employee not found.'); }
    return $row;
}
function staff_snapshot(array $row): array {
    return array_intersect_key($row, array_flip(array_merge(STAFF_FIELDS, ['department_name','tl_name'])));
}
function save_employee(?int $id, array $actor): int {
    $data = [];
    foreach (['employee_code'=>40,'full_name'=>150,'position'=>150,'shift_schedule'=>190,'team_label'=>120,'notes'=>10000] as $field=>$limit) $data[$field]=input($field,$limit);
    foreach (['employee_code','full_name','position','shift_schedule','team_label'] as $field) if ($data[$field]==='') throw new InvalidArgumentException('Complete all required employee details.');
    $data['department_id'] = positive_id($_POST,'department_id');
    $data['tl_id'] = positive_id($_POST,'tl_id');
    if (!query('SELECT 1 FROM departments WHERE id=?',[$data['department_id']])->fetchColumn()) throw new InvalidArgumentException('Select a department.');
    if (!in_array($data['tl_id'],array_map('intval',array_column(tls(),'id')),true)) throw new InvalidArgumentException('Select an active team leader.');
    $data['employment_status']=input('employment_status',20);
    if (!isset(STAFF_STATUSES[$data['employment_status']])) throw new InvalidArgumentException('Select a valid employee status.');
    $data['start_date']=date_value(input('start_date',10),true);
    $data['exit_date']=date_value(input('exit_date',10),$data['employment_status']==='exited');
    if ($data['employment_status'] !== 'exited' && $data['exit_date']) throw new InvalidArgumentException('Exit date is only used for exited employees.');
    if ($data['exit_date'] && ($data['exit_date'] < $data['start_date'] || $data['exit_date'] > date('Y-m-d'))) throw new InvalidArgumentException('Exit date must be between the start date and today.');
    $effective=date_value(input('effective_date',10),true);
    if ($effective>date('Y-m-d')) throw new InvalidArgumentException('History changes must take effect today or earlier.');
    $note=input('history_note',10000);
    if ($note==='') throw new InvalidArgumentException('Add a history note explaining this entry.');
    db()->beginTransaction();
    try {
        $before=$id ? staff_record($id,true) : null;
        if ($before && (int)$before['version']!==positive_id($_POST,'version')) throw new InvalidArgumentException('Another TL changed this profile. Reload the page before saving again.');
        if ($id) {
            $latest=query("SELECT MAX(effective_date) FROM staff_history WHERE staff_id=? AND event_type<>'note'",[$id])->fetchColumn();
            if ($latest && $effective<$latest) throw new InvalidArgumentException('Profile changes cannot predate the latest profile change. Use a history note for earlier events.');
        }
        $columns=array_keys($data); $values=array_values($data);
        if ($id) query('UPDATE staff_directory SET '.implode(',',array_map(fn($key)=>$key.'=?',$columns)).',is_active=?,version=version+1 WHERE id=?',array_merge($values,[$data['employment_status']==='exited'?0:1,$id]));
        else { query('INSERT INTO staff_directory('.implode(',',$columns).',is_active) VALUES('.implode(',',array_fill(0,count($columns)+1,'?')).')',array_merge($values,[$data['employment_status']==='exited'?0:1])); $id=(int)db()->lastInsertId(); }
        $after=staff_record($id);
        query('INSERT INTO staff_history(staff_id,actor_id,event_type,effective_date,notes,before_data,after_data) VALUES(?,?,?,?,?,?,?)',[$id,$actor['id'],$before?'updated':'created',$effective,$note,$before?json_encode(staff_snapshot($before),JSON_THROW_ON_ERROR):null,json_encode(staff_snapshot($after),JSON_THROW_ON_ERROR)]);
        db()->commit(); return $id;
    } catch(Throwable $error) { if(db()->inTransaction()) db()->rollBack(); if($error instanceof PDOException && $error->getCode()==='23000') throw new InvalidArgumentException('That employee code already exists, or a selected record is no longer available.'); throw $error; }
}
function staff_filter(array $source): array {
    $filters=[]; $params=[]; $values=[];
    foreach (['q','status','department_id','tl_id','team','from','to'] as $key) $values[$key]=trim(request_string($source,$key));
    if ($values['q']!=='') { $filters[]='(s.full_name LIKE ? OR s.employee_code LIKE ? OR s.position LIKE ?)'; for($i=0;$i<3;$i++)$params[]='%'.$values['q'].'%'; }
    if(isset(STAFF_STATUSES[$values['status']])) { $filters[]='s.employment_status=?';$params[]=$values['status']; }
    foreach(['department_id','tl_id'] as $key) if(ctype_digit($values[$key]) && (int)$values[$key]>0){$filters[]='s.'.$key.'=?';$params[]=(int)$values[$key];}
    if($values['team']!==''){$filters[]='s.team_label=?';$params[]=$values['team'];}
    foreach(['from'=>'>=','to'=>'<='] as $key=>$op) if($values[$key]!==''){date_value($values[$key],true);$filters[]='s.start_date'.$op.'?';$params[]=$values[$key];}
    if($values['from']!=='' && $values['to']!=='' && $values['from']>$values['to']) throw new InvalidArgumentException('Start-date range is reversed.');
    return [$filters?' WHERE '.implode(' AND ',$filters):'',$params,$values];
}
function ticket_filter(array $source, array $user): array {
    $where=['t.deleted_at IS NULL'];$params=[];$values=[];
    foreach(['q','status','priority','department_id','staff_id','assignee_id','from','to'] as $key)$values[$key]=trim(request_string($source,$key));
    if($values['q']!==''){$where[]='(t.subject LIKE ? OR t.ticket_number LIKE ? OR t.employee_name LIKE ?)';for($i=0;$i<3;$i++)$params[]='%'.$values['q'].'%';}
    foreach(['status'=>TICKET_STATUSES,'priority'=>TICKET_PRIORITIES] as $key=>$choices)if(isset($choices[$values[$key]])){$where[]='t.'.$key.'=?';$params[]=$values[$key];}
    foreach(['department_id','staff_id','assignee_id'] as $key)if(ctype_digit($values[$key]) && (int)$values[$key]>0){$where[]='t.'.$key.'=?';$params[]=(int)$values[$key];}
    if($values['from']!==''){date_value($values['from'],true);$where[]='t.created_at>=?';$params[]=$values['from'].' 00:00:00';}
    if($values['to']!==''){date_value($values['to'],true);$where[]='t.created_at<=?';$params[]=$values['to'].' 23:59:59';}
    if($values['from']!=='' && $values['to']!=='' && $values['from']>$values['to'])throw new InvalidArgumentException('Ticket date range is reversed.');
    if($user['role_slug']==='team-member'){$where[]='t.created_by=?';$params[]=$user['id'];}
    return [' WHERE '.implode(' AND ',$where),$params,$values];
}
function pagination(int $total, int $page, int $size=25): void {
    $pages=max(1,(int)ceil($total/$size));
    echo '<div class="pagination"><span>'.number_format($total).' records · Page '.$page.' of '.$pages.'</span><div class="actions">';
    foreach (['Previous'=>$page-1,'Next'=>$page+1] as $label=>$target) if($target>=1 && $target<=$pages) echo '<a class="button button-secondary" href="?'.e(http_build_query(array_merge($_GET,['page'=>$target]))).'">'.$label.'</a>';
    echo '</div></div>';
}
function page_number(int $total): int { return max(1,min(max(1,(int)ceil($total/25)),positive_id($_GET,'page') ?: 1)); }
function csv_download(string $name, array $headers, iterable $rows): never {
    header('Content-Type: text/csv; charset=utf-8');header('Content-Disposition: attachment; filename="'.$name.'.csv"');
    $out=fopen('php://output','w');fwrite($out,"\xEF\xBB\xBF");fputcsv($out,$headers);
    foreach($rows as $row) { $safe=array_map(function($value){$text=(string)($value??'');return preg_match('/^[\s\x00-\x1F]*[=+@-]/u',$text)?"'".$text:$text;},array_values($row));fputcsv($out,$safe); }
    fclose($out);exit;
}
function history_changes(array $entry): array {
    $before=json_decode($entry['before_data']??'{}',true)??[];$after=json_decode($entry['after_data']??'{}',true)??[];$changes=[];
    foreach($after as $key=>$value) {
        if(in_array($key,['department_id','tl_id'],true))continue;
        if((string)($before[$key]??'')!==(string)($value??''))$changes[]=ucfirst(str_replace('_',' ',$key)).': '.(($before[$key]??'')!==''?($before[$key]??'').' → ':'').($value?:'—');
    }
    return $changes;
}
