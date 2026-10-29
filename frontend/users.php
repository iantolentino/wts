<?php
require dirname(__DIR__).'/backend/app/bootstrap.php';$user=require_permission('manage_users');if(($user['role_slug']??'')!=='super-admin'){http_response_code(403);exit('Only a Super Admin can manage accounts.');}$error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
    verify_csrf();try{
        $action=input('action',20);
        if($action==='create'){
            $name=input('full_name',150);$username=input('username',80);$email=trim(input('email',190));$password=request_string($_POST,'password');$role=input('role',80);
            if($email!=='' && !filter_var($email,FILTER_VALIDATE_EMAIL))throw new InvalidArgumentException('Enter a valid email address.');
            if($name==='' || !preg_match('/^[a-z0-9._-]{3,80}$/',$username))throw new InvalidArgumentException('Enter a name and a lowercase username (letters, numbers, dots, hyphens or underscores).');
            if(strlen($password)<12 || strlen($password)>72)throw new InvalidArgumentException('Use an initial password between 12 and 72 bytes.');
            if(str_contains($password,"\0"))throw new InvalidArgumentException('Passwords cannot contain null characters.');
            if(!in_array($role,['super-admin','team-leader','management','client-viewer'],true))throw new InvalidArgumentException('Select a supported role.');
            $roleId=query('SELECT id FROM roles WHERE slug=?',[$role])->fetchColumn();query('INSERT INTO users(full_name,username,email,password_hash,role_id,must_change_password) VALUES(?,?,?,?,?,1)',[$name,$username,$email!==''?$email:null,password_hash($password,PASSWORD_DEFAULT),$roleId]);flash('Account created. The user must change their initial password at first sign-in.');
        }elseif(in_array($action,['approve','reject'],true)){
            if($user['role_slug']!=='super-admin'){http_response_code(403);exit('Only a Super Admin can review registrations.');}
            $id=positive_id($_POST,'id');
            db()->beginTransaction();
            $target=query('SELECT u.*,r.slug FROM users u JOIN roles r ON r.id=u.role_id WHERE u.id=? FOR UPDATE',[$id])->fetch();
            if(!$target || $target['approval_status']!=='pending')throw new InvalidArgumentException('This registration has already been reviewed or is no longer available.');
            if(!in_array($target['slug'],['team-leader','management','client-viewer'],true))throw new InvalidArgumentException('This registration requests an unsupported role.');
            query('UPDATE users SET approval_status=?,is_active=? WHERE id=?',[$action==='approve'?'approved':'rejected',$action==='approve'?1:0,$id]);
            db()->commit();flash($action==='approve'?'Registration approved. The user can now sign in.':'Registration rejected. The account cannot sign in.');
        }elseif($action==='clear_cutoff'){
            $id=positive_id($_POST,'id');query('DELETE FROM source_account_cutoffs WHERE user_id=?',[$id]);flash('Ticket cutoff removed.');
        }elseif($action==='cutoff'){
            $id=positive_id($_POST,'id');if($id===(int)$user['id'])throw new InvalidArgumentException('You cannot deactivate your own account.');
            $email=mb_strtolower(trim(input('source_email',190)),'UTF-8');$date=date_value(input('last_ticket_date',10),true);$deactivate=request_string($_POST,'deactivate')==='1';
            if(!filter_var($email,FILTER_VALIDATE_EMAIL))throw new InvalidArgumentException('Enter the exact source requester email.');
            db()->beginTransaction();$target=query('SELECT u.*,r.slug FROM users u JOIN roles r ON r.id=u.role_id WHERE u.id=? FOR UPDATE',[$id])->fetch();
            if(!$target || $target['slug']!=='team-leader' || $target['approval_status']!=='approved')throw new InvalidArgumentException('Select an approved Team Leader.');
            $existing=query('SELECT user_id FROM source_account_cutoffs WHERE user_id=? FOR UPDATE',[$id])->fetchColumn();
            if($existing)query('UPDATE source_account_cutoffs SET source_email=?,last_ticket_date=? WHERE user_id=?',[$email,$date,$id]);
            else query('INSERT INTO source_account_cutoffs(user_id,source_email,last_ticket_date) VALUES(?,?,?)',[$id,$email,$date]);
            if($deactivate && $target['is_active'])query('UPDATE users SET is_active=0,session_version=session_version+1 WHERE id=?',[$id]);
            db()->commit();flash($deactivate?'Account deactivated and ticket cutoff saved.':'Ticket cutoff saved.');
        }elseif($action==='toggle'){
            $id=positive_id($_POST,'id');if($id===(int)$user['id'])throw new InvalidArgumentException('You cannot deactivate your own account.');
            db()->beginTransaction();$target=query('SELECT u.*,r.slug FROM users u JOIN roles r ON r.id=u.role_id WHERE u.id=? FOR UPDATE',[$id])->fetch();if(!$target)throw new InvalidArgumentException('Account not found.');
            if($target['approval_status']!=='approved')throw new InvalidArgumentException('Only approved accounts can be activated or deactivated. Review pending registrations first.');
            if($target['is_active'] && $target['slug']==='team-leader')throw new InvalidArgumentException('Use the Team Leader cutoff form to deactivate this account.');
            query('UPDATE users SET is_active=?,session_version=session_version+1 WHERE id=?',[$target['is_active']?0:1,$id]);db()->commit();flash('Account status updated.');
        }else throw new InvalidArgumentException('Unknown account action.');
        redirect('users.php');
    }catch(InvalidArgumentException $e){if(db()->inTransaction())db()->rollBack();$error=$e->getMessage();}catch(PDOException $e){if(db()->inTransaction())db()->rollBack();if($e->getCode()!=='23000')throw $e;$error='That username or source email is already in use.';}
}
$rows=query("SELECT u.id,u.full_name,u.username,u.email,u.is_active,u.approval_status,u.created_at,r.name role_name,r.slug role_slug,sc.source_email,sc.last_ticket_date FROM users u JOIN roles r ON r.id=u.role_id LEFT JOIN source_account_cutoffs sc ON sc.user_id=u.id ORDER BY (u.approval_status='pending') DESC,u.created_at DESC,u.id DESC")->fetchAll();
$pending=count(array_filter($rows,fn($row)=>$row['approval_status']==='pending'));
page_start('Account Access',$user);
?><div class="section-head"><div><h2>Accounts & registrations</h2><p class="muted"><?= $pending ?> pending registration<?= $pending===1?'':'s' ?>. Review the requested role and email before approving access.</p></div></div><?php error_notice($error);?><section class="panel flush"><div class="table-wrap"><table><thead><tr><th>Name / username</th><th>Email</th><th>Role</th><th>Status</th><th>Action</th></tr></thead><tbody>
<?php foreach($rows as $row):$pendingRow=$row['approval_status']==='pending';$approved=$row['approval_status']==='approved';?>
<tr><td><?=e($row['full_name'])?><span class="cell-secondary"><?=e($row['username'])?></span></td><td><?=e($row['email']??'Not provided')?></td><td><?=e($row['role_name'])?></td><td><span class="badge <?=$pendingRow?'pending':($approved&&$row['is_active']?'active':'exited')?>"><?=$pendingRow?'Pending approval':(!$approved?'Rejected':($row['is_active']?'Active':'Inactive'))?></span></td><td>
<?php if($pendingRow && $user['role_slug']==='super-admin'):?><form method="post" class="registration-actions"><?php csrf_field();?><input type="hidden" name="id" value="<?=(int)$row['id']?>"><button class="button" name="action" value="approve">Approve</button><button class="button button-secondary" name="action" value="reject">Reject</button></form>
<?php elseif($approved && (int)$row['id']!==(int)$user['id']):?>
<?php if($row['role_slug']==='team-leader'):?><button type="button" class="button button-secondary" onclick="document.getElementById('cutoff-<?=(int)$row['id']?>').showModal()"><?=$row['is_active']?'Deactivate':'Edit ticket cutoff'?></button><dialog class="cutoff-dialog" id="cutoff-<?=(int)$row['id']?>" aria-labelledby="cutoff-title-<?=(int)$row['id']?>"><form method="post"><?php csrf_field();?><input type="hidden" name="action" value="cutoff"><input type="hidden" name="id" value="<?=(int)$row['id']?>"><h2 id="cutoff-title-<?=(int)$row['id']?>"><?=$row['is_active']?'Deactivate team leader':'Edit ticket cutoff'?></h2><p>Past tickets remain visible. Tickets created after the last day for this source requester email will not appear.</p><label>Source requester email<input type="email" name="source_email" maxlength="190" required value="<?=e($row['source_email']??$row['email']??'')?>"></label><label>Last ticket date<input type="date" name="last_ticket_date" required value="<?=e($row['last_ticket_date']??date('Y-m-d'))?>"></label><?php if($row['is_active']):?><input type="hidden" name="deactivate" value="1"><?php endif;?><div class="actions"><button class="button">Save</button><button class="button button-secondary" type="button" onclick="this.closest('dialog').close()">Cancel</button></div></form></dialog><?php endif;?>
<?php if($row['role_slug']==='team-leader' && $row['last_ticket_date']):?><p class="muted">Cutoff: <?=e($row['last_ticket_date'])?> · <?=e($row['source_email'])?></p><form method="post" onsubmit="return confirm('Remove this cutoff and show later matching tickets?');"><?php csrf_field();?><input type="hidden" name="action" value="clear_cutoff"><input type="hidden" name="id" value="<?=(int)$row['id']?>"><button class="button button-secondary">Remove cutoff</button></form><?php endif;?>
<?php if(!$row['is_active'] || $row['role_slug']!=='team-leader'):?><form method="post"><?php csrf_field();?><input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?=(int)$row['id']?>"><button class="button button-secondary"><?=$row['is_active']?'Deactivate':'Activate'?></button></form><?php endif;?><?php endif;?></td></tr><?php endforeach;?></tbody></table></div></section>
<section class="panel"><h2>Create account</h2><form method="post"><?php csrf_field();?><input type="hidden" name="action" value="create"><div class="form-grid"><label>Full name<input name="full_name" maxlength="150" required></label><label>Username<input name="username" maxlength="80" required pattern="[a-z0-9._-]{3,80}"></label><label>Email (optional)<input type="email" name="email" maxlength="190"><small>For a team leader, use their source ticket requester email.</small></label><label>Role<select name="role"><option value="team-leader">Team Leader — manage staff and tickets</option><option value="management">Admin (Management) — view and report</option><option value="client-viewer">Client Viewer — view tickets only</option><option value="super-admin">Super Admin — full access</option></select></label><label>Initial password<input name="password" type="password" minlength="12" maxlength="72" autocomplete="new-password" required></label></div><div class="form-footer"><button class="button">Create account</button></div></form></section><?php page_end();