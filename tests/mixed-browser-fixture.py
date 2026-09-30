import json
import os
from pathlib import Path
import shutil
import socket
import subprocess
import time
import urllib.request

root=Path(__file__).resolve().parent.parent
fixture=root/'.local/mixed-qa'
for folder in ['frontend','backend/app']:
    (fixture/folder).mkdir(parents=True,exist_ok=True)
for name in ['index.php','external-tickets.php','ticket.php','dashboard.php','staff.php','employee.php','employee-form.php']:
    shutil.copy2(root/'frontend'/name,fixture/'frontend'/name)
for name in ['layout.php','mixed-tickets.php']:
    shutil.copy2(root/'backend/app'/name,fixture/'backend/app'/name)
feed=(root/'backend/app/feed.php').read_text(encoding='utf-8').replace('function whittle_feed_fetch(', 'function whittle_feed_fetch_live(')
feed+='''
function whittle_feed_fetch(?string $source=null,?int $id=null): array {
    if(($_SERVER['HTTP_X_TEST_FEED']??'')==='failed') throw new RuntimeException('Fixture feed unavailable.');
    $data=json_decode(file_get_contents(__DIR__.'/feed-fixture.json'),true);
    if(($_SERVER['HTTP_X_TEST_FEED']??'')==='large'){
        $original=$data['tickets'];
        for($batch=1;$batch<=5;$batch++)foreach($original as $ticket){$ticket['id']+=1000*$batch;$ticket['external_key']=$ticket['source_key'].':'.$ticket['id'];$data['tickets'][]=$ticket;}
        $data['count']=count($data['tickets']);
    }
    if($id!==null) {
        $data['tickets']=array_values(array_filter($data['tickets'],static fn($row)=>$row['id']===$id && $row['source_key']===$source));
        $data['count']=count($data['tickets']);
        if($data['count']!==1)throw new RuntimeException('No permitted fixture ticket.');
    }
    return whittle_feed_decode(json_encode($data));
}
'''
(fixture/'backend/app/feed.php').write_text(feed,encoding='utf-8')
local={'id':7,'ticket_number':'WHT-LOCAL-7','subject':'Local editable ticket','employee_name':'Local Employee','issue_escalator':'Local Requestor','creator_name':'Fixture User','category':'General Request','subcategory':None,'status':'open','priority':'normal','created_at':'2026-09-29 10:00:00','updated_at':'2026-09-29 10:30:00','department_name':'Admin','department_id':1,'assignee_name':'Local Agent','assignee_id':1,'staff_id':1,'issue':'Original local issue','resolution':None,'version':1,'deleted_at':None,'created_by':1}
(fixture/'backend/app/local-fixture.json').write_text(json.dumps([local,dict(local,id=8,ticket_number='WHT-LOCAL-8',subject='Second local ticket',created_by=2)]),encoding='utf-8')
tickets=[]
for i in range(39):
    source='stratast_support' if i<38 else 'stratast_escalations'
    tid=7+i if i<38 else 7
    tickets.append({'id':tid,'source_key':source,'external_key':f'{source}:{tid}','subject':'External read only ticket' if i==0 else f'External fixture {i}','requester':'Source Requestor','email':'fixture@example.invalid','status':'Open','priority':'Medium','category':'Support','date':'2026-09-29 09:00:00','last_update':'2026-09-29 10:20:00','assignee':'Source Agent','assigned_department':'Support Department','thread':[{'id':1,'type':'report','author':'Source Requestor','date':'2026-09-29 09:00:00','body':'<p>Original source issue</p>'},{'id':2,'type':'reply','author':'Source Agent','date':'2026-09-29 10:00:00','body':'<p>Reply comment</p><script>window.REMOTE_SCRIPT_RAN=true</script>'}]})
tickets[0]['thread'].append({'id':3,'type':'note','author':'Source Agent','author_email':'agent@example.invalid','date':'2026-09-29 10:10:00','updated_at':'2026-09-29 10:15:00','body':'Internal handover note','attachment_ids':[21]})
tickets[0].update(details=[{'name':'Ticket channel','value':'Email'}],custom_fields=[{'name':'Custom request detail','value':'Detailed field value','raw_value':'Detailed field value','slug':'cf_fixture','type':'cf_textfield'}],attachments=[{'id':21,'name':'source-report.pdf','date':'2026-09-29'}],activity=[{'body':'Status: New -> Open','author':'Source Agent','date':'2026-09-29'}],unavailable_sections=[])
tickets[0]['thread'][0]['body']='<p>Original source issue</p><p><strong>Important detail</strong></p><ul><li>First request</li><li>Second request</li></ul><table><tr><th>Item</th><th>Value</th></tr><tr><td>Sample</td><td>Readable table</td></tr></table><a href="javascript:window.REMOTE_SCRIPT_RAN=true" onclick="window.REMOTE_SCRIPT_RAN=true">Unsafe link</a><img src=x onerror="window.REMOTE_SCRIPT_RAN=true"><iframe srcdoc="bad"></iframe>'
for i,status in [(1,'In Progress'),(2,'Pending'),(3,'Resolved'),(4,'Awaiting Customer')]:tickets[i]['status']=status
(fixture/'backend/app/feed-fixture.json').write_text(json.dumps({'success':True,'schema_version':1,'capabilities':['reply_comments','assignment_labels','last_update','full_details'],'fetched_at':'2026-09-29T01:00:00Z','count':len(tickets),'tickets':tickets}),encoding='utf-8')
app=(root/'backend/app').as_posix()
bootstrap='''<?php
session_start();$role=$_SERVER['HTTP_X_TEST_ROLE']??'';
if($role!=='')$_SESSION['user_id']=1;else unset($_SESSION['user_id']);
function db(){static $db;return $db??=new MixedFixtureDb();}
class MixedFixtureDb {
    private $staffDb;
    function __construct(){
        $this->staffDb=new PDO('sqlite::memory:');$this->staffDb->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE,PDO::FETCH_ASSOC);
        $this->staffDb->exec("CREATE TABLE departments(id INTEGER,name TEXT,code TEXT,is_active INTEGER);INSERT INTO departments VALUES(1,'Admin','ADMIN',1);
            CREATE TABLE roles(id INTEGER,slug TEXT);INSERT INTO roles VALUES(1,'team-leader');
            CREATE TABLE users(id INTEGER,full_name TEXT,role_id INTEGER,is_active INTEGER,approval_status TEXT);INSERT INTO users VALUES(1,'Fixture Leader',1,1,'approved');
            CREATE TABLE staff_directory(id INTEGER,employee_code TEXT,full_name TEXT,position TEXT,shift_schedule TEXT,department_id INTEGER,team_label TEXT,tl_id INTEGER,start_date TEXT,exit_date TEXT,employment_status TEXT,notes TEXT);INSERT INTO staff_directory VALUES(1,'EMP-001','Fixture Employee','Agent','Weekdays',1,'Whittle',1,'2026-01-01',NULL,'active','Staff notes');
            CREATE TABLE staff_history(id INTEGER,staff_id INTEGER,actor_id INTEGER,event_type TEXT,effective_date TEXT,notes TEXT,created_at TEXT,before_data TEXT,after_data TEXT);");
    }
    function prepare($sql){if(strpos($sql,'SELECT t.*')===false && (strpos($sql,'staff_directory')!==false || strpos($sql,'staff_history')!==false || strpos($sql,'FROM departments')!==false))return $this->staffDb->prepare($sql);return new MixedFixtureStatement($sql);}
    function query($sql){$statement=$this->prepare($sql);$statement->execute();return $statement;}
}
class MixedFixtureStatement extends PDOStatement {
    private $sql;private $params=[];
    public function __construct($sql){$this->sql=$sql;}
    public function execute(?array $params=null):bool{$this->params=$params??[];return true;}
    public function fetch(int $mode=PDO::FETCH_DEFAULT,int $orientation=PDO::FETCH_ORI_NEXT,int $offset=0):mixed {
        if(strpos($this->sql,'r.slug AS role_slug')!==false){$role=$_SERVER['HTTP_X_TEST_ROLE']??'';return ['id'=>1,'role_id'=>1,'role_slug'=>$role==='admin'?'super-admin':$role,'must_change_password'=>$role==='first-login'?1:0,'full_name'=>'Fixture User','role_name'=>'Fixture Role'];}
        if(strpos($this->sql,'SELECT t.*')!==false)return json_decode(file_get_contents(__DIR__.'/local-fixture.json'),true)[0];
        return false;
    }
    public function fetchColumn(int $column=0):mixed {
        if(strpos($this->sql,'permission_key')!==false){$permission=$this->params['permission_key']??'';$role=$_SERVER['HTTP_X_TEST_ROLE']??'';if($role==='denied')return false;if($role!=='admin'&&$permission==='export_tickets')return false;if($role==='client-viewer'&&$permission==='view_staff')return false;if($role==='management'&&in_array($permission,['edit_tickets','comment_tickets','create_tickets','delete_tickets']))return false;return true;}
        return 0;
    }
    public function fetchAll(int $mode=PDO::FETCH_DEFAULT,...$args):array {
        if(strpos($this->sql,'SELECT t.*')!==false){$rows=json_decode(file_get_contents(__DIR__.'/local-fixture.json'),true);return strpos($this->sql,'t.created_by=')!==false?[$rows[0]]:$rows;}
        if(strpos($this->sql,'SELECT u.id,u.full_name')!==false)return [['id'=>1,'full_name'=>'Local Agent']];
        return [];
    }
}
require '__APP__/security.php';require '__APP__/auth.php';require '__APP__/tickets.php';require '__APP__/wheettle.php';require __DIR__.'/layout.php';
'''.replace('__APP__',app)
(fixture/'backend/app/bootstrap.php').write_text(bootstrap,encoding='utf-8')
router='''<?php
$path=parse_url($_SERVER['REQUEST_URI'],PHP_URL_PATH);
if(strpos($path,'/backend/')===0){http_response_code(403);exit('Forbidden');}
if(strpos($path,'/assets/')===0&&strpos($path,'..')===false){$file='__FRONTEND__'.rawurldecode($path);if(!is_file($file)){http_response_code(404);exit;}$ext=pathinfo($file,PATHINFO_EXTENSION);header('Content-Type: '.(['css'=>'text/css','png'=>'image/png','webp'=>'image/webp'][$ext]??'application/octet-stream'));readfile($file);exit;}
if(in_array($path,['/login.php','/change-password.php'],true)){echo 'Fixture authentication landing';exit;}
if(!in_array($path,['/index.php','/ticket.php','/external-tickets.php','/dashboard.php','/staff.php','/employee.php','/employee-form.php'],true)){http_response_code(404);exit;}
require __DIR__.'/frontend'.$path;
'''.replace('__FRONTEND__',(root/'frontend').as_posix())
(fixture/'router.php').write_text(router,encoding='utf-8')
with socket.socket() as sock:
    sock.bind(('127.0.0.1',0));port=sock.getsockname()[1]
log=(fixture/'server.log').open('w')
server=subprocess.Popen([r'C:\xampp\php\php.exe','-S',f'127.0.0.1:{port}','-t',str(fixture),str(fixture/'router.php')],stdout=log,stderr=log,creationflags=subprocess.CREATE_NO_WINDOW)
try:
    for _ in range(50):
        try:urllib.request.urlopen(f'http://127.0.0.1:{port}/login.php',timeout=1).close();break
        except OSError:time.sleep(.1)
    subprocess.run(['node',str(root/'tests/mixed-browser.cjs')],cwd=root,env=dict(os.environ,MIXED_QA_BASE=f'http://127.0.0.1:{port}'),check=True)
finally:
    server.terminate();server.wait(timeout=5);log.close()
