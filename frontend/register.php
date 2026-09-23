<?php
require dirname(__DIR__).'/backend/app/bootstrap.php';
require dirname(__DIR__).'/backend/app/auth-layout.php';
if(current_user())redirect('dashboard.php');
$roles=['team-leader'=>'Team Leader','management'=>'Admin (Management)','client-viewer'=>'Client Viewer'];
$error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
    verify_csrf();
    try{
        $email=mb_strtolower(input('email',190));$username=mb_strtolower(input('username',80));$role=input('role',80);
        $password=request_string($_POST,'password');$confirm=request_string($_POST,'confirm_password');
        if(!filter_var($email,FILTER_VALIDATE_EMAIL))throw new InvalidArgumentException('Enter a valid email address.');
        if(!preg_match('/^[a-z0-9._-]{3,80}$/D',$username))throw new InvalidArgumentException('Use 3 to 80 letters, numbers, dots, hyphens or underscores for your username.');
        if(!isset($roles[$role]))throw new InvalidArgumentException('Select a supported registration role.');
        if(strlen($password)<12 || strlen($password)>72)throw new InvalidArgumentException('Use a password between 12 and 72 bytes.');
        if(str_contains($password,"\0"))throw new InvalidArgumentException('Passwords cannot contain null characters.');
        if($password!==$confirm)throw new InvalidArgumentException('Passwords do not match.');
        $bucket=hash('sha256','registration|'.($_SERVER['REMOTE_ADDR']??'local'));
        db()->beginTransaction();
        query('INSERT IGNORE INTO login_attempts(bucket,failures,last_attempt) VALUES(?,0,NOW())',[$bucket]);
        $attempt=query('SELECT failures,last_attempt FROM login_attempts WHERE bucket=? FOR UPDATE',[$bucket])->fetch();
        if((int)$attempt['failures']>=10 && strtotime($attempt['last_attempt'])>time()-900)throw new InvalidArgumentException('Too many registrations. Please try again in 15 minutes.');
        $roleId=query('SELECT id FROM roles WHERE slug=?',[$role])->fetchColumn();
        if(!$roleId)throw new InvalidArgumentException('This role is not available. Contact your administrator.');
        query("INSERT INTO users(role_id,username,full_name,email,password_hash,must_change_password,is_active,approval_status) VALUES(?,?,?,?,?,0,0,'pending')",[$roleId,$username,$username,$email,password_hash($password,PASSWORD_DEFAULT)]);
        query('UPDATE login_attempts SET failures=IF(last_attempt<DATE_SUB(NOW(),INTERVAL 15 MINUTE),1,failures+1),last_attempt=NOW() WHERE bucket=?',[$bucket]);
        db()->commit();flash('Registration submitted. A Super Admin must approve your account before you can sign in.');redirect('login.php');
    }catch(InvalidArgumentException $e){if(db()->inTransaction())db()->rollBack();$error=$e->getMessage();}
    catch(PDOException $e){if(db()->inTransaction())db()->rollBack();if($e->getCode()!=='23000')throw $e;$error='That email address or username is already registered.';}
}
auth_page_start('Create account');
?><h2>Create an account</h2><p class="registration-hint">Request access to submit or review tickets. Your account needs Super Admin approval before you can sign in.</p><?php error_notice($error);?><form method="post" data-registration><?php csrf_field();?><label>Email<input type="email" name="email" required maxlength="190" autocomplete="email" value="<?=e(request_string($_POST,'email'))?>"></label><label>Username<input name="username" required minlength="3" maxlength="80" pattern="[a-zA-Z0-9._-]{3,80}" autocomplete="username" value="<?=e(request_string($_POST,'username'))?>"></label><label>Requested role<select name="role" required><option value="">Select a role</option><?php enum_options($roles,request_string($_POST,'role'));?></select></label><label>Password<input type="password" name="password" required minlength="12" maxlength="72" autocomplete="new-password" aria-describedby="password-rules"></label><small id="password-rules">Use at least 12 characters.</small><label>Confirm password<input type="password" name="confirm_password" required minlength="12" maxlength="72" autocomplete="new-password" aria-describedby="password-feedback"></label><div class="password-controls"><label><input type="checkbox" id="show-passwords">Show passwords</label></div><p id="password-feedback" class="password-feedback" role="status" aria-live="polite"></p><button class="button">Submit registration</button></form><p class="auth-foot">Already registered? <a href="login.php">Sign in</a></p><?php auth_page_end();
