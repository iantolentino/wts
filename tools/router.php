<?php
// For the loopback-only PHP development server. Apache uses the root .htaccess.
$path=rawurldecode(parse_url($_SERVER['REQUEST_URI'],PHP_URL_PATH)??'/');
if(str_contains($path,'..') || str_contains($path,'\\') || preg_match('#/(?:\.[^/]*|_brain|backend|database|documentation|tests|tools)(?:/|$)#i',$path) || preg_match('/\.(?:md|json|sql|ps1|log|ini|zip)$/i',$path)){http_response_code(403);exit('Forbidden');}
if($path==='/'){require dirname(__DIR__).'/frontend/dashboard.php';return true;}
return false;
