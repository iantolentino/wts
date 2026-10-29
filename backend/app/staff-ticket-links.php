<?php
declare(strict_types=1);

/** Owner-confirmed name variants; staff IDs always come from the directory. */
function staff_ticket_alias_groups(): array {
    return [
        ['Carmela Calilung', 'Calilung, Carmela', 'Carmela Perez', 'Perez, Carmela'],
        ['Frankie Mangulabnan', 'Mangulabnan, Frankie', 'Frankie Mae Mangulabnan', 'Mangulabnan, Frankie Mae'],
        ['Natasha Dizon', 'Dizon, Natasha', 'Natasha Louise Dizon', 'Dizon, Natasha Louise'],
    ];
}
function staff_ticket_name(string $name): string {
    return trim((string)preg_replace('/[^\p{L}\p{N}]+/u', ' ', mb_strtolower($name, 'UTF-8')));
}
function staff_ticket_name_key(string $name): string {
    $words=explode(' ',staff_ticket_name($name)); sort($words,SORT_STRING); return implode(' ',$words);
}
function staff_ticket_index(array $staff): array {
    $index=['staff'=>[], 'names'=>[], 'phrases'=>[]];
    foreach($staff as $person){
        $id=(int)$person['id']; $index['staff'][$id]=$person;
        $aliases=[$person['full_name']]; $key=staff_ticket_name_key($person['full_name']);
        foreach(staff_ticket_alias_groups() as $group){
            if(in_array($key,array_map('staff_ticket_name_key',$group),true))$aliases=array_merge($aliases,$group);
        }
        foreach(array_unique($aliases) as $alias){
            $index['names'][staff_ticket_name_key($alias)][$id]=true;
            $index['phrases'][staff_ticket_name($alias)][$id]=true;
        }
    }
    return $index;
}
function staff_ticket_directory_index(): array {
    static $index=null;
    return $index??=staff_ticket_index(query('SELECT id,full_name,employee_code FROM staff_directory ORDER BY full_name')->fetchAll());
}
/** Match explicit employee/requestor names and full names in external titles only. */
function staff_ticket_match(array $ticket, bool $external, array $index): array {
    $links=[];
    $explicit=(int)($ticket['staff_id']??0);
    if(!$external && isset($index['staff'][$explicit]))$links[$explicit]='Selected employee';
    $name=$external?($ticket['requester']??''):($ticket['employee_name']??'');
    $matches=$index['names'][staff_ticket_name_key((string)$name)]??[];
    // An explicitly selected native employee takes precedence over name inference.
    if(($external || !$explicit) && count($matches)===1)$links[(int)array_key_first($matches)]=$external?'Requestor':'Employee';
    if($external){
        $title=' '.staff_ticket_name((string)($ticket['subject']??'')).' ';
        foreach($index['phrases'] as $phrase=>$ids){
            if($phrase!=='' && count($ids)===1 && str_contains($title,' '.$phrase.' ')){
                $id=(int)array_key_first($ids); $links[$id]??='Named in title';
            }
        }
    }
    $people=[];
    foreach($links as $id=>$reason)$people[]=['id'=>$id,'full_name'=>$index['staff'][$id]['full_name'],'employee_code'=>$index['staff'][$id]['employee_code']??'','reason'=>$reason];
    return $people;
}
function staff_ticket_attach(array $row,array $ticket,bool $external,array $index): array {
    $row['linked_staff']=staff_ticket_match($ticket,$external,$index);
    $row['staff_ids']=array_column($row['linked_staff'],'id');
    if(!$row['staff_id'] && $row['staff_ids'])$row['staff_id']=$row['staff_ids'][0];
    return $row;
}
