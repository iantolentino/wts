<?php
// CLI only. Creates a NEW local database; never resets or reuses an existing database.
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
date_default_timezone_set('Asia/Manila');
$root=dirname(__DIR__);$config=require $root.'/config/config.example.php';
$pdo=new PDO('mysql:host=127.0.0.1;port=3306;charset=utf8mb4','root','',[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
$exists=$pdo->query("SELECT COUNT(*) FROM information_schema.schemata WHERE schema_name='wheettle_ticketing'")->fetchColumn();
if($exists){
    if(!in_array('--resume-empty-base',$argv,true)){fwrite(STDERR,"Database already exists. Setup refused; no data changed.\n");exit(1);}
    $pdo->exec('USE wheettle_ticketing');
    $hasMigration=$pdo->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='wheettle_ticketing' AND table_name='staff_history'")->fetchColumn();
    if($hasMigration || $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn() || $pdo->query('SELECT COUNT(*) FROM staff_directory')->fetchColumn() || $pdo->query('SELECT COUNT(*) FROM tickets')->fetchColumn()){
        fwrite(STDERR,"Resume refused: database is not an empty base schema.\n");exit(1);
    }
}else{
    $pdo->exec('CREATE DATABASE wheettle_ticketing CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
    $pdo->exec('USE wheettle_ticketing');
    $pdo->exec(file_get_contents($root.'/database/schema.sql'));
}
$pdo->exec(file_get_contents($root.'/database/001_wheettle.sql'));
$run=function($sql,$args=[])use($pdo){$q=$pdo->prepare($sql);$q->execute($args);return $q;};
$credentials=[];
$pdo->beginTransaction();
try{
    foreach(['admin'=>['Demo Administrator','super-admin'],'tl'=>['Alex Morgan (Demo TL)','team-leader'],'tl2'=>['Jordan Lee (Demo TL)','team-leader'],'management'=>['Demo Management','management'],'viewer'=>['Demo Client Viewer','client-viewer']] as $key=>[$name,$role]){
        $password='Wts!'.bin2hex(random_bytes(8));$username='demo.'.$key;
        $roleId=$run('SELECT id FROM roles WHERE slug=?',[$role])->fetchColumn();
        $run('INSERT INTO users(role_id,username,full_name,password_hash,must_change_password) VALUES(?,?,?,?,0)',[$roleId,$username,$name,password_hash($password,PASSWORD_DEFAULT)]);
        $credentials[$key]=['username'=>$username,'password'=>$password,'id'=>(int)$pdo->lastInsertId()];
    }
    $support=(int)$run("SELECT id FROM departments WHERE code='customer-support'")->fetchColumn();$operations=(int)$run("SELECT id FROM departments WHERE code='operations'")->fetchColumn();
    $today=date('Y-m-d');
    $fixtures=[['DEMO-001','Avery Collins','Customer Support Specialist','Mon–Fri, 8 AM–5 PM, Asia/Manila',$support,'Customer Experience',$credentials['tl']['id'],date('Y-m-d',strtotime('-5 months')),'','active'],['DEMO-002','Casey Rivera','Operations Assistant','Mon–Fri, 9 AM–6 PM, Asia/Manila',$operations,'Operations Support',$credentials['tl2']['id'],date('Y-m-d',strtotime('-2 days')),'','new'],['DEMO-003','Taylor Bennett','Support Associate','Mon–Fri, 10 PM–7 AM, Asia/Manila',$support,'Evening Support',$credentials['tl']['id'],date('Y-m-d',strtotime('-8 months')),date('Y-m-d',strtotime('-1 day')),'exited']];
    foreach($fixtures as $fixture){$fixture[8]=$fixture[8]?:null;$run('INSERT INTO staff_directory(employee_code,full_name,position,shift_schedule,department_id,team_label,tl_id,start_date,exit_date,employment_status,notes,is_active) VALUES(?,?,?,?,?,?,?,?,?,?,?,?)',array_merge($fixture,['Fictional local demonstration record.',$fixture[9]==='exited'?0:1]));$id=(int)$pdo->lastInsertId();$snapshot=$run('SELECT s.employee_code,s.full_name,s.position,s.shift_schedule,s.department_id,s.team_label,s.tl_id,s.start_date,s.exit_date,s.employment_status,s.notes,d.name department_name,u.full_name tl_name FROM staff_directory s JOIN departments d ON d.id=s.department_id JOIN users u ON u.id=s.tl_id WHERE s.id=?',[$id])->fetch(PDO::FETCH_ASSOC);$run("INSERT INTO staff_history(staff_id,actor_id,event_type,effective_date,notes,after_data) VALUES(?,?,'created',?,?,?)",[$id,$credentials['tl']['id'],$today,'Fictional demo profile created for local testing.',json_encode($snapshot,JSON_THROW_ON_ERROR)]);}
    foreach([['Confirm onboarding equipment','open','normal',2,$operations,'Onboarding'],['Review shift handover process','in_progress','high',1,$support,'Performance'],['Complete exit handover','closed','normal',3,$support,'Resignation']] as $i=>[$subject,$status,$priority,$staffId,$department,$category]){
        $employee=$run('SELECT full_name FROM staff_directory WHERE id=?',[$staffId])->fetchColumn();
        $run('INSERT INTO tickets(ticket_number,issue_escalator,subject,category,department_id,employee_name,description,issue,status,priority,assignee_id,created_by,staff_id,resolution,closed_at) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)',['WTS-DEMO-00'.($i+1),'Demo Team Leader',$subject,$category,$department,$employee,'Fictional request for local testing.','Fictional request for local testing.',$status,$priority,$credentials['tl']['id'],$credentials['tl']['id'],$staffId,$status==='closed'?'Demo handover completed.':null,$status==='closed'?date('Y-m-d H:i:s'):null]);
        $ticketId=(int)$pdo->lastInsertId();$run('INSERT INTO ticket_assignees(ticket_id,user_id) VALUES(?,?)',[$ticketId,$credentials['tl']['id']]);$run("INSERT INTO ticket_activity(ticket_id,actor_id,action,details) VALUES(?,?,'created',?)",[$ticketId,$credentials['tl']['id'],json_encode(['source'=>'fictional local demo'])]);
    }
    $run("INSERT INTO app_settings(setting_key,setting_value) VALUES('setup_complete','1')");$pdo->commit();
}catch(Throwable $e){$pdo->rollBack();throw $e;}
if(!is_dir($root.'/.local'))mkdir($root.'/.local',0700,true);
file_put_contents($root.'/.local/test-accounts.json',json_encode($credentials,JSON_PRETTY_PRINT|JSON_THROW_ON_ERROR));
echo "Wheettle database created with fictional fixtures. Local credentials saved in .local/test-accounts.json (not tracked).\n";
