<?php
declare(strict_types=1);
require dirname(__DIR__).'/backend/app/security.php';
require dirname(__DIR__).'/backend/app/tickets.php';
require dirname(__DIR__).'/backend/app/wheettle.php';
require dirname(__DIR__).'/backend/app/mixed-tickets.php';
require 'C:/xampp/htdocs/wts-fetch-test/adapters.php';
$checks=0;
function mixed_check(bool $ok,string $name): void { global $checks;if(!$ok)throw new RuntimeException('FAIL: '.$name);++$checks;echo 'PASS: ',$name,PHP_EOL; }
$local=mixed_local_ticket(['id'=>7,'subject'=>'Local editable ticket','issue_escalator'=>'Local Requestor','status'=>'open','priority'=>'normal','category'=>'General Request','created_at'=>'2026-09-29 08:00:00','updated_at'=>'2026-09-29 09:00:00','department_name'=>'Admin','assignee_name'=>'Local Agent']);
$external=mixed_external_ticket(['id'=>7,'external_key'=>'stratast_support:7','source_key'=>'stratast_support','subject'=>'External read only ticket','requester'=>'Source Requestor','status'=>'Open','priority'=>'Medium','category'=>'Support','date'=>'2026-09-29 07:00:00','last_update'=>'2026-09-29 09:30:00','assignee'=>'Source Agent','assigned_department'=>'Support Department']);
mixed_check($local['key']!==$external['key'] && $local['url']==='ticket.php?id=7' && str_starts_with($external['url'],'external-tickets.php?'),'colliding IDs retain distinct routes');
mixed_check(count(mixed_ticket_rows([$external,$local],mixed_ticket_filter([])))===2,'same numeric IDs both retained');
mixed_check(mixed_ticket_rows([$external,$local],mixed_ticket_filter([]))[0]['external']===false,'combined creation sorting');
mixed_check(count(mixed_ticket_rows([$external,$local],mixed_ticket_filter(['status'=>'open','priority'=>'normal'])))===2,'shared status and priority mapping');
mixed_check(count(mixed_ticket_rows([$external,$local],mixed_ticket_filter(['q'=>'Source Requestor'])))===1,'shared requestor search');
mixed_check(count(mixed_ticket_rows([$external,$local],mixed_ticket_filter(['department'=>'IT Department'])))===1,'shared department filter');
foreach(WHITTLE_FEED_DEPARTMENTS as $source=>$department){
    $row=mixed_external_ticket(['id'=>7,'external_key'=>$source.':7','source_key'=>$source,'subject'=>'Department fixture','requester'=>'Requestor','status'=>'Open','priority'=>'Medium','category'=>'Support','date'=>'2026-09-29','assigned_department'=>'Old label']);
    mixed_check($row['department']===$department,'authoritative source department '.$source);
}
foreach(['Strata Support Desk'=>'IT Department','HR Escalation Desk'=>'HR Department','Training Desk'=>'LND Department','Requisition Desk'=>'Requisition'] as $source=>$department) mixed_check(whittles_source_department($source)===$department,'fetcher department '.$source);
mixed_check(count(mixed_ticket_rows([$external,$local],mixed_ticket_filter(['from'=>'2026-09-30'])))===0,'shared creation date filter');
$pdo=new PDO('sqlite::memory:');$pdo->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE,PDO::FETCH_ASSOC);
$pdo->sqliteCreateFunction('regexp',static fn($pattern,$value)=>preg_match('/'.$pattern.'/i',(string)$value),2);
$prefix='test_';
foreach([
    'tickets'=>'id INTEGER,subject TEXT,customer INTEGER,status INTEGER,priority INTEGER,category INTEGER,assigned_agent TEXT,date_created TEXT,date_updated TEXT,date_closed TEXT,cf_department TEXT',
    'customers'=>'id INTEGER,name TEXT,email TEXT',
    'statuses'=>'id INTEGER,name TEXT','priorities'=>'id INTEGER,name TEXT','categories'=>'id INTEGER,name TEXT',
    'agents'=>'id INTEGER,name TEXT,is_agentgroup INTEGER',
    'custom_fields'=>'id INTEGER,name TEXT,slug TEXT,type TEXT','options'=>'id INTEGER,name TEXT,custom_field INTEGER',
    'threads'=>'id INTEGER,ticket INTEGER,customer INTEGER,type TEXT,body TEXT,date_created TEXT,is_active INTEGER',
] as $name=>$columns)$pdo->exec('CREATE TABLE test_psmsc_'.$name.' ('.$columns.')');
$pdo->exec("INSERT INTO test_psmsc_customers VALUES (1,'Mae Ann Argete','fixture@example.invalid'),(2,'Carly Tayag','fixture-carly@example.invalid'),(3,'Unrelated Person','unrelated@example.invalid'),(4,'Source Agent','agent@example.invalid')");
foreach(['statuses'=>'Open','priorities'=>'Normal','categories'=>'Support'] as $name=>$label)$pdo->exec("INSERT INTO test_psmsc_{$name} VALUES (1,'{$label}')");
$pdo->exec("INSERT INTO test_psmsc_agents VALUES (4,'Source Agent',0),(5,'Support Group',1)");
$pdo->exec("INSERT INTO test_psmsc_custom_fields VALUES (9,'Assigned Department','cf_department','cf_dropdown')");
$pdo->exec("INSERT INTO test_psmsc_options VALUES (12,'Real Department',9)");
foreach([1=>1,2=>2,3=>3,4=>2] as $id=>$customer)$pdo->exec("INSERT INTO test_psmsc_tickets VALUES ({$id},'Fixture ticket',{$customer},1,1,1,'4,5','2026-09-29 08:00:00','2026-09-29 09:00:00',NULL,'12')");
$pdo->exec("INSERT INTO test_psmsc_threads VALUES (1,1,1,'report','Original issue','2026-09-29 08:00:00',1),(2,1,4,'reply','Agent reply','2026-09-29 09:00:00',1),(3,1,4,'note','Private note','2026-09-29 09:01:00',1),(4,1,4,'reply','Deleted reply','2026-09-29 09:02:00',0),(5,2,2,'report','Unrelated original','2026-09-29 08:00:00',1),(6,2,4,'reply','This is about Mae Ann Argete','2026-09-29 09:00:00',1),(7,4,2,'report','Other team ticket','2026-09-29 08:00:00',1)");
$tickets=fetch_whittles_tickets($pdo,$prefix,'Fixture Source');
$ids=array_map('intval',array_column($tickets,'id'));sort($ids);
mixed_check($ids===[1,2],'five-member filter plus Carly reply mention');
mixed_check($tickets[0]['assignee']==='Source Agent','agent IDs resolve to names');
mixed_check($tickets[0]['assigned_department']==='Real Department','department custom option resolves');
mixed_check($tickets[0]['last_update']==='2026-09-29 09:00:00','source last update retained');
mixed_check(array_unique(array_column(fetch_whittles_tickets($pdo,$prefix,'Strata Support Desk'),'assigned_department'))===['IT Department'],'fetcher source department overrides stored field labels');
$messages=fetch_whittles_thread($pdo,$prefix,1);
mixed_check(array_column($messages,'type')===['report','reply'],'comments include report and reply only');
mixed_check($messages[1]['author']==='Source Agent','comment author resolved');
mixed_check(count($messages)===2,'private notes and deleted replies excluded');
$metadata=whittles_assignment_metadata($pdo,$prefix);
mixed_check(whittles_assignment_labels(['assigned_agent'=>'5','cf_department'=>''],$metadata)===[null,'Support Group'],'assigned group fallback');
mixed_check(whittles_assignment_labels(['assigned_agent'=>'999'],['agents'=>[],'fields'=>[],'options'=>[]])===['Agent #999',null],'missing labels are explicit without invented department');
$pdo->exec('ALTER TABLE test_psmsc_threads ADD COLUMN attachments TEXT');
$pdo->exec('ALTER TABLE test_psmsc_threads ADD COLUMN date_updated TEXT');
$pdo->exec('ALTER TABLE test_psmsc_tickets ADD COLUMN auth_code TEXT');
$pdo->exec("UPDATE test_psmsc_tickets SET auth_code='SECRET-NOT-FOR-FEED'");
$pdo->exec("UPDATE test_psmsc_threads SET attachments='21,22,23',date_updated='2026-09-29 09:30:00' WHERE id=2");
$pdo->exec('CREATE TABLE test_psmsc_attachments (id INTEGER,name TEXT,file_path TEXT,is_image INTEGER,is_active INTEGER,date_created TEXT,ticket_id INTEGER)');
$pdo->exec("INSERT INTO test_psmsc_attachments VALUES (21,'source-report.pdf','SECRET-SERVER-PATH',0,1,'2026-09-29',1),(22,'other-ticket.pdf','SECRET-SERVER-PATH',0,1,'2026-09-29',99),(23,'deleted.pdf','SECRET-SERVER-PATH',0,0,'2026-09-29',1),(24,'unlinked.png','SECRET-SERVER-PATH',1,1,'2026-09-29',0)");
$pdo->exec("INSERT INTO test_psmsc_custom_fields VALUES (10,'Authentication','auth_code','cf_textfield'),(11,'Status','status','df_status')");
$pdo->exec("INSERT INTO test_psmsc_threads (id,ticket,customer,type,body,date_created,is_active) VALUES (8,1,4,'log','{\"slug\":\"status\",\"prev\":\"2\",\"new\":\"1\"}','2026-09-29 10:00:00',1),(9,99,4,'log','OTHER-TICKET-LOG','2026-09-29 10:00:00',1),(10,1,4,'log','{\"slug\":\"auth_code\",\"prev\":\"OLD-SECRET\",\"new\":\"SECRET-NOT-FOR-FEED\"}','2026-09-29 10:00:00',1)");
$detail=fetch_whittles_details($pdo,$prefix,1);
mixed_check(array_column($detail['thread'],'type')===['report','reply','note'],'full details include internal notes and exclude deleted messages');
mixed_check($detail['thread'][1]['author_email']==='agent@example.invalid' && $detail['thread'][1]['updated_at']==='2026-09-29 09:30:00','comment email and update timestamp retained');
mixed_check($detail['custom_fields'][0]['value']==='Real Department' && $detail['custom_fields'][0]['raw_value']==='12','custom field raw values and display labels retained');
mixed_check(array_column($detail['attachments'],'id')===[21],'attachments exclude other tickets, deleted and unreferenced uploads');
mixed_check(count($detail['activity'])===1 && $detail['activity'][0]['body']==='Status: 2 -> Open','activity resolves field labels and stays ticket-scoped');
$encoded=json_encode($detail);
mixed_check(strpos($encoded,'SECRET')===false && strpos($encoded,'OTHER-TICKET-LOG')===false,'auth codes and filesystem paths never exported');
mixed_check($detail['unavailable_sections']===[],'complete schema reports no missing sections');
$pdo->exec('DROP TABLE test_psmsc_attachments');
mixed_check(in_array('attachments',fetch_whittles_details($pdo,$prefix,1)['unavailable_sections'],true),'missing optional source table explicitly reported');
$summaryRows=[$local,$external,array_replace($external,['key'=>'stratast_escalations:7','origin'=>'HR','status_key'=>'closed','status'=>'Resolved','priority_key'=>'urgent','created'=>'2026-08-05']),array_replace($external,['key'=>'stratast_support:8','status_key'=>'source:waiting customer','status'=>'Waiting Customer','created'=>'2026-03-01','priority_key'=>'urgent'])];
$summary=mixed_ticket_summary($summaryRows,new DateTimeImmutable('2026-09-29'));
mixed_check($summary['total']===4 && array_sum(array_column($summary['statuses'],'count'))===4,'overview status counts reconcile with combined total');
mixed_check($summary['statuses']['open']['count']===2 && $summary['statuses']['closed']['count']===1 && $summary['statuses']['source:waiting customer']['count']===1,'overview preserves unfamiliar source status');
mixed_check($summary['sources']['Whittle']===1 && $summary['sources']['HR']===1 && $summary['sources']['Support']===2,'overview source counts preserve overlapping IDs');
mixed_check($summary['months']['2026-09']['active']===2 && $summary['months']['2026-08']['closed']===1 && count($summary['months'])===6,'overview months use creation date and current status');
mixed_check($summary['urgent']===1,'urgent workload excludes closed tickets');
mixed_check(mixed_ticket_summary([],new DateTimeImmutable('2026-09-29'))['total']===0,'overview empty state is valid');
echo $checks,' mixed ticket/adapter checks passed.',PHP_EOL;
