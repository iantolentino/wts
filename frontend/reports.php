<?php
require dirname(__DIR__).'/backend/app/bootstrap.php';
require_permission('view_reports');
$params=[];
foreach(['from','to'] as $key){$value=request_string($_GET,$key);if($value!=='')$params[$key]=$value;}
redirect('index.php'.($params?'?'.http_build_query($params):''));
