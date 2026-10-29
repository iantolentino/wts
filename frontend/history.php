<?php
require dirname(__DIR__).'/backend/app/bootstrap.php';
$user=require_permission('view_staff');
if(!in_array($user['role_slug'],['super-admin','team-leader'],true)){http_response_code(403);exit('Staff history is restricted.');}
$params=[];foreach(['q','department_id','tl_id','team'] as $key){$value=request_string($_GET,$key);if($value!=='')$params[$key]=$value;}
redirect('staff.php'.($params?'?'.http_build_query($params):''));
