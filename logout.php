<?php
require __DIR__.'/app/bootstrap.php';
if($_SERVER['REQUEST_METHOD']==='POST'){verify_csrf();$_SESSION=[];session_destroy();setcookie(session_name(),'',time()-3600,'/');redirect('login.php');}
$user=require_login();page_start('Sign out',$user);?><section class="panel"><h2>Sign out of Whittles?</h2><form method="post"><?php csrf_field();?><div class="actions"><button class="button">Sign out</button><a class="button button-secondary" href="dashboard.php">Stay signed in</a></div></form></section><?php page_end();
