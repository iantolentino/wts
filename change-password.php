<?php
require __DIR__.'/app/bootstrap.php';$user=require_login();$error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
    verify_csrf();$current=request_string($_POST,'current_password');$new=request_string($_POST,'new_password');
    if(!password_verify($current,$user['password_hash']))$error='Current password is incorrect.';
    elseif(strlen($new)<12 || strlen($new)>72)$error='Use a password between 12 and 72 bytes.';
    elseif(str_contains($new, "\0"))$error='Passwords cannot contain null characters.';
    elseif(password_verify($new,$user['password_hash']))$error='Choose a different password from your current password.';
    elseif($new!==request_string($_POST,'confirm_password'))$error='New passwords do not match.';
    else{query('UPDATE users SET password_hash=?,must_change_password=0 WHERE id=?',[password_hash($new,PASSWORD_DEFAULT),$user['id']]);session_regenerate_id(true);flash('Password updated.');redirect('dashboard.php');}
}
page_start('Account settings',$user);?><section class="panel"><h2>Change password</h2><?php error_notice($error);if($user['must_change_password']):?><p class="muted">Set your own password before continuing.</p><?php endif;?><form method="post" class="form-grid"><?php csrf_field();?><label class="wide">Current password<input name="current_password" type="password" autocomplete="current-password" required></label><label>New password<input name="new_password" type="password" autocomplete="new-password" minlength="12" maxlength="72" required></label><label>Confirm new password<input name="confirm_password" type="password" autocomplete="new-password" required></label><div class="wide"><button class="button">Update password</button></div></form></section><?php page_end();
