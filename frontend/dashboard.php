<?php
require dirname(__DIR__).'/backend/app/bootstrap.php';
$user=require_permission('view_all_tickets');
if(($_SERVER['REQUEST_METHOD']??'')!=='GET'){header('Allow: GET');http_response_code(405);exit('Use GET to view Dashboard.');}
require_once dirname(__DIR__).'/backend/app/mixed-tickets.php';
$loaded=mixed_ticket_load($user);$stats=mixed_ticket_summary($loaded['rows']);
$recent=array_slice(mixed_ticket_rows($loaded['rows'],mixed_ticket_filter([])),0,4);
$otherCount=array_sum(array_column(array_diff_key($stats['statuses'],TICKET_STATUSES),'count'));
$partial=$loaded['feed_error']!=='';$own=($user['role_slug']??'')==='team-member';
$colors=['open'=>'#327ea0','in_progress'=>'#d9982b','pending'=>'#8b6eb0','closed'=>'#37977d'];
$stops=[];$position=0;
foreach($stats['statuses'] as $key=>$group){$next=$position+100*$group['count']/max(1,$stats['total']);$stops[]=($colors[$key]??'#7c8a96').' '.round($position,4).'% '.round($next,4).'%';$position=$next;}
$ring=$stats['total']?'conic-gradient('.implode(',',$stops).')':'#edf3f5';
$maxMonth=max(1,...array_values(array_map(static fn($month)=>$month['active']+$month['closed'],$stats['months'])));
$maxSource=max(1,...array_values($stats['sources']));
$staffStats=user_can('view_staff')?query("SELECT COUNT(*) total,COALESCE(SUM(employment_status='active'),0) active,COALESCE(SUM(employment_status='new'),0) new,COALESCE(SUM(employment_status='exited'),0) exited FROM staff_directory")->fetch():null;
$trendPoints=[];foreach(array_values($stats['months']) as $i=>$month){$trendPoints[]=round(24+$i*952/5,2).','.round(190-166*($month['active']+$month['closed'])/$maxMonth,2);}
$trendLine=implode(' ',$trendPoints);$trendArea='M 24,190 L '.implode(' L ',$trendPoints).' L 976,190 Z';
page_start('Dashboard',$user);
?>
<div class="ticket-overview dashboard-canvas">
<?php if($partial):?><div class="error" role="alert">External tickets could not be loaded. Totals and graphs below include local tickets only. <?=e($loaded['feed_error'])?></div><?php endif;?>
<section class="panel dashboard-kpi-panel"><div class="dashboard-head"><h2>Ticket summary</h2><a class="button button-secondary" href="dashboard.php">Refresh</a></div>
<div class="overview-metrics metric-grid-reference">
<a class="overview-metric" data-metric="total" href="index.php"><span><?=$partial?'Local tickets':($own?'Your tickets':'Total tickets')?></span><strong><?=$stats['total']?></strong></a>
<?php foreach($stats['statuses'] as $key=>$group):if(!isset(TICKET_STATUSES[$key]))continue;?><a class="overview-metric" data-metric="<?=e($key)?>" href="index.php?<?=e(http_build_query(['status'=>$key]))?>"><span><?=e($group['label'])?></span><strong><?=$group['count']?></strong></a><?php endforeach;?>
<div class="overview-metric" data-metric="urgent"><span>Urgent active</span><strong><?=$stats['urgent']?></strong></div>
<div class="overview-metric" data-metric="other"><span>Other statuses</span><strong><?=$otherCount?></strong></div>
<div class="overview-metric" data-metric="unassigned"><span>Unassigned active</span><strong><?=$stats['unassigned']?></strong></div>
</div></section>
<div class="overview-charts dashboard-chart-grid">
<section class="panel dashboard-trend-panel"><div class="dashboard-head"><div><h2>Tickets created</h2><p class="overview-caption">Last six months</p></div></div>
<div class="trend-chart"><svg viewBox="0 0 1000 220" role="img" aria-label="Ticket creation counts over the last six months" preserveAspectRatio="none"><defs><linearGradient id="whittle-trend-fill" x1="0" y1="0" x2="0" y2="1"><stop offset="0%" stop-color="#327ea0" stop-opacity=".22"/><stop offset="100%" stop-color="#327ea0" stop-opacity=".02"/></linearGradient></defs><g class="trend-grid"><?php foreach([24,79,134,190] as $y):?><line x1="24" x2="976" y1="<?=$y?>" y2="<?=$y?>"/><?php endforeach;?></g><path d="<?=$trendArea?>" fill="url(#whittle-trend-fill)"/><polyline points="<?=$trendLine?>" fill="none" stroke="#327ea0" stroke-width="3"/><?php foreach(array_values($stats['months']) as $i=>$month):[$x,$y]=explode(',',$trendPoints[$i]);?><circle cx="<?=$x?>" cy="<?=$y?>" r="4" fill="#327ea0"><title><?=e($month['label'])?>: <?=$month['active']+$month['closed']?> tickets</title></circle><?php endforeach;?></svg><div class="trend-axis"><?php foreach($stats['months'] as $group):?><div class="month-column"><span><?=e($group['label'])?></span><small><?=$group['active']+$group['closed']?> tickets</small></div><?php endforeach;?></div></div>
<?php if(!$stats['total']):?><p class="muted">No tickets are available yet.</p><?php endif;?></section>
<section class="panel dashboard-status-panel"><h2>By status</h2><p class="overview-caption">Ticket distribution</p><div class="status-chart"><div class="status-ring" aria-hidden="true" style="background:<?=e($ring)?>"><span class="ring-value"><strong><?=$stats['total']?></strong><small>Total</small></span></div><ul class="chart-legend" aria-label="Ticket counts by status"><?php foreach($stats['statuses'] as $key=>$group):?><li><a href="index.php?<?=e(http_build_query(['status'=>$key]))?>"><span class="chart-dot" style="background:<?=$colors[$key]??'#7c8a96'?>"></span><?=e($group['label'])?><strong><?=$group['count']?></strong></a></li><?php endforeach;?></ul></div></section>
</div>
<div class="dashboard-lower-grid">
<section class="panel dashboard-signals-panel"><h2>Tickets by source</h2><p class="overview-caption"><?=$own?'Your local ticket workload.':($partial?'External source counts are unavailable.':'Whittle and the four external desks.')?></p><?php foreach($stats['sources'] as $origin=>$count):if(($own||$partial)&&$origin!=='Whittle')continue;?><a class="source-bar" data-origin="<?=e($origin)?>" href="index.php?<?=e(http_build_query(['origin'=>$origin]))?>"><span><?=e($origin)?><strong><?=$count?></strong></span><div class="bar-track" aria-hidden="true"><span style="width:<?=round(100*$count/$maxSource,2)?>%"></span></div></a><?php endforeach;?></section>
<section class="panel overview-recent dashboard-recent-panel"><h2>Recent tickets</h2><p class="overview-caption">Latest requests</p><div class="recent-ticket-list"><?php foreach($recent as $ticket):?><a class="recent-ticket-item" data-ticket-key="<?=e($ticket['key'])?>" href="<?=e($ticket['url'])?>"><span class="recent-ticket-copy"><strong><?=e($ticket['title'])?></strong><small><?=e($ticket['department'])?> &middot; <?=e($ticket['origin'])?> #<?=$ticket['id']?></small></span><span class="recent-ticket-status"><span class="badge <?=isset(TICKET_STATUSES[$ticket['status_key']])?e($ticket['status_key']):''?>"><?=e($ticket['status'])?></span><small><?=e(substr($ticket['created'],0,10))?></small></span></a><?php endforeach;if(!$recent):?><p class="muted">No tickets yet.</p><?php endif;?></div><a href="index.php">View all tickets &rarr;</a></section>
</div>
<?php if($staffStats):?><details class="panel"><summary>Staff overview</summary><div class="metrics"><?php foreach(['total'=>['All employees','staff.php'],'active'=>['Active staff','staff.php?status=active'],'new'=>['New staff','staff.php?status=new'],'exited'=>['Exited staff','staff.php?status=exited']] as $key=>[$label,$url]):?><a class="metric" href="<?=$url?>"><span class="metric-label"><?=$label?></span><strong><?=(int)$staffStats[$key]?></strong></a><?php endforeach;?></div><a href="history.php">View staff history &rarr;</a></details><?php endif;?>
<p class="overview-caption">Updated <?=e(date('M j, Y g:i a'))?>. Refresh to load new tickets and status changes.</p>
</div>
<?php page_end();?>
