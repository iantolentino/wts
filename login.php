<?php
require __DIR__.'/app/bootstrap.php';
if(current_user())redirect('dashboard.php');
$error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
    verify_csrf();$username=mb_strtolower(trim(request_string($_POST,'username')));$password=request_string($_POST,'password');
    $bucket=hash('sha256',($_SERVER['REMOTE_ADDR']??'local').'|'.$username);
    $attempt=query('SELECT failures,last_attempt FROM login_attempts WHERE bucket=?',[$bucket])->fetch();
    if($attempt && (int)$attempt['failures']>=10 && strtotime($attempt['last_attempt'])>time()-900)$error='Too many sign-in attempts. Please try again in 15 minutes.';
    else{
        $user=query('SELECT * FROM users WHERE username=?',[$username])->fetch();
        if($user && password_verify($password,$user['password_hash']) && $user['is_active'] && $user['approval_status']==='approved'){
            query('DELETE FROM login_attempts WHERE bucket=?',[$bucket]);session_regenerate_id(true);$_SESSION['user_id']=$user['id'];query('UPDATE users SET last_login_at=NOW() WHERE id=?',[$user['id']]);redirect($user['must_change_password']?'change-password.php':'dashboard.php');
        }
        query('INSERT INTO login_attempts(bucket,failures,last_attempt) VALUES(?,1,NOW()) ON DUPLICATE KEY UPDATE failures=IF(last_attempt<DATE_SUB(NOW(),INTERVAL 15 MINUTE),1,failures+1),last_attempt=NOW()',[$bucket]);
        $error='Invalid username or password, or the account is inactive.';
    }
}
?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Sign in · Wheettle</title><link rel="icon" href="assets/favicon.svg"><link rel="stylesheet" href="assets/css/app.css"><link rel="stylesheet" href="assets/css/wheettle.css"></head><body class="wts login-page"><main class="login-wrap"><section class="login-copy"><p class="eyebrow">Wheettle · People & service</p><h1>Every person.<br>Every chapter.<br>One shared record.</h1><p>A clearer place for service requests and the people behind them.</p></section><section class="login-card"><img class="strata-logo" src="assets/stratastaff-logo.png" alt="Strata Staff Global"><h2>Welcome back</h2><p class="muted">Sign in to your Wheettle workspace.</p><?php error_notice($error);?><form method="post"><?php csrf_field();?><label>Username<input name="username" autocomplete="username" required maxlength="80" value="<?=e(request_string($_POST,'username'))?>"></label><label>Password<input name="password" type="password" autocomplete="current-password" required></label><button class="button">Sign in</button></form><p class="auth-foot">Need access? Contact your administrator.</p></section></main></body></html>
