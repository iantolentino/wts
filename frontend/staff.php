<?php
require dirname(__DIR__).'/backend/app/bootstrap.php';$user=require_permission('view_staff');$error='';
try{[$where,$params,$filters]=staff_filter($_GET);}catch(InvalidArgumentException $e){$error=$e->getMessage();[$where,$params,$filters]=staff_filter([]);}
if(!isset(STAFF_STATUSES[$filters['status']]))$filters['status']='';
$total=(int)query('SELECT COUNT(*) FROM staff_directory s'.$where,$params)->fetchColumn();$page=page_number($total);$offset=($page-1)*25;
$rows=query('SELECT s.*,d.name department_name,u.full_name tl_name FROM staff_directory s LEFT JOIN departments d ON d.id=s.department_id LEFT JOIN users u ON u.id=s.tl_id'.$where.' ORDER BY s.full_name,s.id LIMIT 25 OFFSET '.$offset,$params)->fetchAll();
[$countWhere,$countParams]=staff_filter(array_merge($filters,['status'=>'']));
$counts=array_fill_keys(array_keys(STAFF_STATUSES),0);
foreach(query('SELECT s.employment_status,COUNT(*) total FROM staff_directory s'.$countWhere.' GROUP BY s.employment_status',$countParams) as $count)$counts[$count['employment_status']]=(int)$count['total'];
$departments=active_departments();
$leaders=query("SELECT u.id,u.full_name FROM users u JOIN roles r ON r.id=u.role_id WHERE r.slug='team-leader' AND (u.approval_status='approved' OR EXISTS(SELECT 1 FROM staff_directory s WHERE s.tl_id=u.id)) ORDER BY u.full_name")->fetchAll();
$teams=query("SELECT DISTINCT team_label FROM staff_directory WHERE team_label<>'' ORDER BY team_label")->fetchAll();
$extraFilters=$filters['team']!=='' || $filters['from']!=='' || $filters['to']!=='';
$hasFilters=count(array_filter($filters,fn($v)=>$v!==''))>0;
$labels=['q'=>'Search','status'=>'Status','department_id'=>'Department','tl_id'=>'Team leader','team'=>'Team','from'=>'Started from','to'=>'Started to'];
$departmentNames=array_column($departments,'name','id');$leaderNames=array_column($leaders,'full_name','id');
$filterUrl=fn(array $values)=>'staff.php?'.http_build_query(array_filter($values,fn($v)=>$v!==''));
page_start('Staff directory',$user);
?><link rel="stylesheet" href="assets/css/staff-directory.css">
<div class="staff-directory">
<div class="section-head"><div><h2>Find an employee</h2><p class="muted">Search your staff, check their team and status, or open a profile for full details.</p></div><div class="actions"><?php if(user_can('manage_staff')):?><a class="button" href="employee-form.php">+ Add employee</a><?php endif;?></div></div>
<?php error_notice($error);?>
<section class="panel directory-search" aria-label="Find and filter employees">
<form method="get" action="staff.php">
<input type="hidden" name="status" value="<?=e($filters['status'])?>">
<div class="directory-primary-filters">
<label>Search employees<input type="search" name="q" placeholder="Enter a name, employee code or job title" value="<?=e($filters['q'])?>"></label>
<label>Department<select name="department_id"><option value="">All departments</option><?php options($departments,$filters['department_id']);?></select></label>
<label>Team leader<select name="tl_id"><option value="">All team leaders</option><?php options($leaders,$filters['tl_id'],'full_name');?></select></label>
<button class="button" type="submit">Search</button>
</div>
<details class="directory-more-filters" <?=$extraFilters?'open':''?>><summary>More filters <span class="muted">Team &amp; start date<?=$extraFilters?' · Applied':''?></span></summary>
<div class="directory-extra-filters"><label>Team<select name="team"><option value="">All teams</option><?php foreach($teams as $team):?><option value="<?=e($team['team_label'])?>" <?=$filters['team']===$team['team_label']?'selected':''?>><?=e($team['team_label'])?></option><?php endforeach;?></select></label>
<label>Started from<input type="date" name="from" value="<?=e($filters['from'])?>"></label><label>Started to<input type="date" name="to" value="<?=e($filters['to'])?>"></label><button class="button button-secondary" type="submit">Apply filters</button></div></details>
</form>
<?php if($hasFilters):?><div class="directory-applied" aria-label="Applied filters"><span>Filtered by</span><?php foreach($filters as $key=>$value):if($value==='')continue;$display=match($key){'status'=>STAFF_STATUSES[$value]??$value,'department_id'=>$departmentNames[$value]??$value,'tl_id'=>$leaderNames[$value]??$value,default=>$value};?><a class="filter-chip" href="<?=e($filterUrl(array_merge($filters,[$key=>''])))?>" aria-label="<?=e('Remove '.$labels[$key].' filter: '.$display)?>"><?=e($labels[$key].': '.$display)?><span aria-hidden="true">&times;</span></a><?php endforeach;?><a class="directory-clear" href="staff.php">Clear all</a></div><?php endif;?>
</section>
<section class="panel flush directory-results" aria-label="Employee results">
<nav class="directory-status" aria-label="Staff status"><?php foreach([''=>'All staff','active'=>'Active','new'=>'New staff','exited'=>'Exited'] as $key=>$label):?><a class="<?=$filters['status']===$key?'selected':''?>" <?=$filters['status']===$key?'aria-current="page"':''?> href="<?=e($filterUrl(array_merge($filters,['status'=>$key])))?>"><?=e($label)?><span><?=number_format($key===''?array_sum($counts):$counts[$key])?></span></a><?php endforeach;?></nav>
<div class="directory-result-heading"><div><h2><?=number_format($total)?> employee<?=$total===1?'':'s'?><?=$hasFilters?' found':''?></h2><p class="muted"><?php if($total):?>Showing <?=number_format($offset+1)?>–<?=number_format(min($offset+25,$total))?> · Sorted by name<?php else:?><?=$hasFilters?'Try a different search or remove a filter.':'Your employee directory is ready to get started.'?><?php endif;?></p></div><?php if(user_can('export_staff') && $total):?><a class="button button-secondary" href="export.php?<?=e(http_build_query(array_merge($filters,['type'=>'staff'])))?>">Export results</a><?php endif;?></div>
<?php if($rows):?><div class="directory-table-wrap"><table class="directory-table"><caption class="directory-sr-only">Employees matching your filters. Open a profile to view shift schedules, employment dates and history.</caption><thead><tr><th scope="col">Employee</th><th scope="col">Department &amp; team</th><th scope="col">Team leader</th><th scope="col">Status</th><th scope="col">Profile</th></tr></thead><tbody>
<?php foreach($rows as $row):?><tr>
<td class="directory-person"><a class="directory-name" href="employee.php?id=<?=(int)$row['id']?>"><?=e($row['full_name'])?></a><span class="directory-position"><?=e($row['position']?:'Position not recorded')?></span><span class="directory-code">Employee code: <?=e($row['employee_code']?:'Not recorded')?></span></td>
<td data-label="Department &amp; team"><div><?=e($row['department_name']?:'No department')?><span class="directory-secondary"><?=e($row['team_label']?:'No team assigned')?></span></div></td>
<td data-label="Team leader"><span><?=e($row['tl_name']?:'Not assigned')?></span></td>
<td data-label="Status"><span class="badge <?=e($row['employment_status'])?>"><?=e(STAFF_STATUSES[$row['employment_status']])?></span></td>
<td class="directory-profile"><a class="button button-secondary" href="employee.php?id=<?=(int)$row['id']?>" aria-label="<?=e('View profile for '.$row['full_name'])?>">View profile <span aria-hidden="true">&rarr;</span></a></td>
</tr><?php endforeach;?></tbody></table></div>
<div class="directory-list-note">Open a profile for shift schedules, start and exit dates, and staff history.</div><?php if($total>25)pagination($total,$page);?>
<?php else:?><div class="directory-empty"><h3><?=$hasFilters?'No employees match your filters':'No employees yet'?></h3><p><?=$hasFilters?'Check the spelling or clear your filters to see all staff.':'Add your first employee to keep their details, team and history in one place.'?></p><?php if($hasFilters):?><a class="button button-secondary" href="staff.php">Clear filters</a><?php elseif(user_can('manage_staff')):?><a class="button" href="employee-form.php">Add your first employee</a><?php endif;?></div><?php endif;?></section></div><?php page_end();
