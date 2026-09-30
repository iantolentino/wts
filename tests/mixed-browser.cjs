const {chromium}=require('C:/Users/Admin/Downloads/wts-build-tools/node_modules/playwright');
const base=process.env.MIXED_QA_BASE;
let checks=0;function check(ok,name){if(!ok)throw new Error('FAIL: '+name);checks++;console.log('PASS: '+name);}
(async()=>{
 const browser=await chromium.launch({executablePath:'C:/Program Files/Google/Chrome/Application/chrome.exe',headless:true});
 try{
  const page=await browser.newPage();await page.goto(base+'/index.php');check(page.url().endsWith('/login.php'),'mixed list requires login');
  await page.setExtraHTTPHeaders({'X-Test-Role':'admin'});let response=await page.goto(base+'/index.php');check(response.status()===200,'mixed list loads');
  check(JSON.stringify(await page.locator('.mixed-table th').allTextContents())===JSON.stringify(['ID','Title','Requestor','Status','Priority','Assignee','Assigned Department','Category','Created','Last update']),'ten requested columns in order');
  check((await page.locator('.pagination').innerText()).includes('41 records'),'39 external and 2 local share one pagination');
  check(await page.locator('.mixed-table tbody tr').count()===25,'first combined page has 25 rows');
  const pager=page.getByRole('navigation',{name:'Ticket pagination'});
  check(await pager.locator('[aria-current="page"]').innerText()==='1'&&await pager.locator('[aria-disabled="true"]').count()===2,'first page selected and backward navigation disabled');
  await pager.getByRole('link',{name:'Page 2',exact:true}).click();
  check(await page.locator('.mixed-table tbody tr').count()===16&&await pager.locator('[aria-current="page"]').innerText()==='2','numbered link opens second page');
  check(await pager.locator('[aria-disabled="true"]').count()===2,'last page disables forward navigation');
  await pager.getByRole('link',{name:'First',exact:true}).click();await pager.getByRole('link',{name:'Last',exact:true}).click();
  check((await page.locator('.pagination').innerText()).includes('Page 2 of 2'),'first and last page links work');
  await pager.getByRole('link',{name:'Previous',exact:true}).click();await pager.getByRole('link',{name:'Next',exact:true}).click();
  check((await page.locator('.pagination').innerText()).includes('Page 2 of 2'),'previous and next page links work');
  await page.goto(base+'/index.php?page=999');check((await page.locator('.pagination').innerText()).includes('Page 2 of 2'),'oversized page clamps to last page');
  await page.goto(base+'/index.php?q=no-ticket-matches');check((await page.locator('.pagination').innerText()).includes('0 records')&&await pager.locator('[aria-disabled="true"]').count()===4,'empty results retain valid disabled pagination');
  await page.goto(base+'/index.php?department=IT+Department&assignee=Source+Agent');await pager.getByRole('link',{name:'Next',exact:true}).click();
  check(new URL(page.url()).searchParams.get('department')==='IT Department'&&new URL(page.url()).searchParams.get('assignee')==='Source Agent'&&await page.locator('.mixed-table tbody tr').count()===13,'pagination retains active filters');
  const filteredCsv=await page.request.get(base+'/index.php?department=IT+Department&page=2&download=csv',{headers:{'X-Test-Role':'admin'}});
  check((await filteredCsv.text()).trim().split('\n').length===39,'export includes all filtered pages');
  await page.setExtraHTTPHeaders({'X-Test-Role':'admin','X-Test-Feed':'large'});await page.goto(base+'/index.php?page=5');
  check(await pager.getByRole('link',{name:'Page 1',exact:true}).count()===1&&await pager.getByRole('link',{name:'Page 10',exact:true}).count()===1&&await pager.locator('.ticket-pagination-gap').count()===2,'large lists keep first/last and compact numbered page window');
  await pager.getByRole('link',{name:'Last',exact:true}).click();check((await page.locator('.pagination').innerText()).includes('Page 10 of 10')&&await page.locator('.mixed-table tbody tr').count()===11,'large list last page shows remaining rows');
  await page.setExtraHTTPHeaders({'X-Test-Role':'admin'});
  await page.goto(base+'/index.php');
  check(await page.getByRole('heading',{name:'All tickets',exact:true}).count()===0&&!(await page.locator('body').innerText()).includes('Whittle tickets and matching source tickets'),'ticket list introductory text removed');
  check(await page.getByRole('link',{name:'Dashboard',exact:true}).count()===1&&await page.getByRole('link',{name:'Overview',exact:true}).count()===0,'navigation renamed Dashboard');
  check(await page.getByRole('button',{name:/Notifications/}).count()===0,'notification button omitted');
  await page.goto(base+'/index.php?q=7');check(await page.locator('[data-ticket-key="whittle:7"]').count()===1&&await page.locator('[data-ticket-key="stratast_support:7"]').count()===1,'colliding local/source IDs appear separately');
  check(await page.getByRole('link',{name:'External tickets',exact:true}).count()===0,'no separate external list navigation');
  await page.locator('[data-ticket-key="stratast_support:7"] a').first().click();check(await page.locator('.source-comment').count()===4,'external report, reply, note and activity loaded');
  const detailText=await page.locator('body').innerText();check(detailText.includes('Detailed field value')&&detailText.includes('Internal handover note')&&detailText.includes('source-report.pdf')&&detailText.includes('agent@example.invalid')&&detailText.includes('2026-09-29 10:15:00'),'custom fields, attachments and comment metadata visible');
  check(detailText.includes('IT Department')&&!detailText.includes('Support Department'),'external detail uses source department mapping');
  check(await page.evaluate(()=>{const header=document.querySelector('.detail-header').getBoundingClientRect();const grid=document.querySelector('.detail-grid').getBoundingClientRect();return Math.abs(header.width-grid.width)<2&&header.bottom<grid.top;}),'detail header spans full content above conversation');
  check((await page.locator('.source-comment').allTextContents()).join(' ').includes('Reply comment'),'reply body visible');
  check(await page.getByRole('button',{name:/Save changes|Post comment|Delete ticket/}).count()===0,'external has no write controls');
  check(await page.evaluate(()=>!window.REMOTE_SCRIPT_RAN),'remote comment script cannot execute');
  check(await page.locator('.rich-text strong').count()===1&&await page.locator('.rich-text li').count()===2&&await page.locator('.rich-text table').count()===1,'safe paragraph/list/table formatting preserved');
  check(await page.locator('.rich-text img,.rich-text iframe,.rich-text [onclick],.rich-text a[href^="javascript:"]').count()===0,'unsafe rich content removed');
  for(const width of [390,768,1440]){await page.setViewportSize({width,height:900});check(await page.evaluate(()=>document.documentElement.scrollWidth<=innerWidth),'external detail fits '+width+'px');}
  const detailUrl=page.url();check((await page.request.post(detailUrl,{headers:{'X-Test-Role':'admin'},form:{action:'comment',body:'MUST NOT WRITE'}})).status()===405,'external comment POST rejected');
  check((await page.request.post(detailUrl,{headers:{'X-Test-Role':'admin'},form:{action:'update',subject:'MUST NOT WRITE'}})).status()===405,'external edit POST rejected');
  await page.goto(base+'/index.php?q=Local+editable');await page.locator('.mixed-table tbody a').first().click();check(page.url().includes('/ticket.php?id=7'),'local row opens original editable route');
  check(await page.getByRole('button',{name:'Save changes',exact:true}).count()===1,'local edit control retained');
  check(await page.getByRole('button',{name:'Post comment',exact:true}).count()===1,'local comment control retained');
  for(const width of [390,768,1440]){await page.setViewportSize({width,height:900});check(await page.evaluate(()=>document.documentElement.scrollWidth<=innerWidth),'local detail fits '+width+'px');}
  await page.goto(base+'/index.php?origin=Whittle');check(await page.locator('.mixed-table tbody tr').count()===2,'local source filter');
  await page.goto(base+'/index.php?assignee=Source+Agent');check((await page.locator('.pagination').innerText()).includes('39 records'),'external assignment filter');
  await page.goto(base+'/index.php?department=IT+Department');check((await page.locator('.pagination').innerText()).includes('38 records'),'mapped department filter matches support tickets');
  await page.goto(base+'/index.php?status=open&priority=normal');check((await page.locator('.pagination').innerText()).includes('37 records'),'status/priority filters span both sources');
  const csv=await page.request.get(base+'/index.php?download=csv',{headers:{'X-Test-Role':'admin'}});const csvText=await csv.text();check(csv.status()===200&&csvText.includes('Local editable ticket')&&csvText.includes('External read only ticket'),'combined CSV export');
  await page.goto(base+'/index.php?page=2');check(await page.locator('.mixed-table tbody tr').count()===16,'combined second page');
  await page.goto(base+'/index.php');for(const width of [390,768,1440]){await page.setViewportSize({width,height:900});check(await page.evaluate(()=>document.documentElement.scrollWidth<=innerWidth),'no page overflow '+width+'px');}
  await page.goto(base+'/dashboard.php');check((await page.locator('[data-metric="total"] strong').innerText())==='41','overview totals all mixed tickets');
  check(await page.getByRole('heading',{name:'Dashboard',exact:true}).count()===1&&await page.locator('.hero-strip').count()===0,'Dashboard title and welcome banner removal');
  check(await page.locator('.dashboard-chart-grid .dashboard-trend-panel svg').count()===1&&await page.locator('.dashboard-lower-grid .dashboard-recent-panel').count()===1,'reference dashboard panel composition rendered');
  check(await page.locator('.app-header-actions .button').first().evaluate(el=>getComputedStyle(el).borderColor)==='rgb(217, 225, 228)','existing Whittle button palette retained');
  check((await page.locator('[data-metric="open"] strong').innerText())==='37'&&(await page.locator('[data-metric="closed"] strong').innerText())==='1','overview normalizes source status counts');
  check((await page.locator('[data-metric="other"] strong').innerText())==='1'&&(await page.locator('.chart-legend').innerText()).includes('Awaiting Customer'),'dashboard retains unknown source statuses');
  check(await page.locator('.metric-grid-reference .overview-metric').count()===8,'reference metric layout has eight cards');
  check(await page.evaluate(()=>document.querySelector('.trend-axis').getBoundingClientRect().top>=document.querySelector('.trend-chart svg').getBoundingClientRect().bottom),'trend labels appear below full-width chart');
  check(await page.locator('.status-ring').count()===1&&await page.locator('.source-bar').count()===5&&await page.locator('.month-column').count()===6,'status, source and six-month charts rendered');
  check(await page.locator('.overview-recent [data-ticket-key="whittle:7"]').count()===1&&await page.locator('.overview-recent a[href^="external-tickets.php"]').count()>0,'recent overview rows include both ticket origins');
  await page.locator('[data-metric="pending"]').click();check(page.url().endsWith('/index.php?status=pending')&&await page.locator('.mixed-table tbody tr').count()===1,'overview status drilldown matches list');
  await page.goto(base+'/dashboard.php');for(const width of [390,768,1440]){await page.setViewportSize({width,height:900});check(await page.evaluate(()=>document.documentElement.scrollWidth<=innerWidth),'overview fits '+width+'px');}
  if(process.env.MIXED_QA_SCREENSHOTS){await page.screenshot({path:process.env.MIXED_QA_SCREENSHOTS+'/overview-desktop.png',fullPage:true});await page.setViewportSize({width:390,height:844});await page.screenshot({path:process.env.MIXED_QA_SCREENSHOTS+'/dashboard-mobile.png',fullPage:true});await page.goto(base+'/index.php');await page.screenshot({path:process.env.MIXED_QA_SCREENSHOTS+'/tickets-mobile.png',fullPage:true});await page.setViewportSize({width:1440,height:1000});await page.screenshot({path:process.env.MIXED_QA_SCREENSHOTS+'/tickets-desktop.png',fullPage:true});await page.goto(detailUrl);await page.screenshot({path:process.env.MIXED_QA_SCREENSHOTS+'/ticket-desktop.png',fullPage:true});await page.setViewportSize({width:390,height:844});await page.screenshot({path:process.env.MIXED_QA_SCREENSHOTS+'/ticket-mobile.png',fullPage:true});}
  await page.setExtraHTTPHeaders({'X-Test-Role':'admin','X-Test-Feed':'failed'});await page.goto(base+'/index.php');check((await page.locator('.error').innerText()).includes('local tickets only'),'feed failure explicitly labels partial local list');check(await page.locator('.mixed-table tbody tr').count()===2,'local list survives feed failure');check((await page.request.get(base+'/index.php?download=csv',{headers:{'X-Test-Role':'admin','X-Test-Feed':'failed'}})).status()===502,'partial export blocked');
  await page.goto(base+'/dashboard.php');check((await page.locator('.error').innerText()).includes('local tickets only')&&(await page.locator('[data-metric="total"] span').textContent())==='Local tickets'&&(await page.locator('[data-metric="total"] strong').innerText())==='2','overview failure cannot appear as complete totals');
  await page.setExtraHTTPHeaders({'X-Test-Role':'team-member'});await page.goto(base+'/index.php');check(await page.locator('.mixed-table tbody tr').count()===1,'Team Member own local ticket scope preserved');check((await page.request.get(detailUrl,{headers:{'X-Test-Role':'team-member'}})).status()===403,'Team Member external detail denied');
  check(await page.getByRole('link',{name:'Export CSV',exact:true}).count()===1,'Team Member export button available');
  const ownExport=await page.request.get(base+'/index.php?download=csv',{headers:{'X-Test-Role':'team-member'}});const ownCsv=await ownExport.text();
  check(ownExport.status()===200&&ownCsv.includes('Local editable ticket')&&!ownCsv.includes('Second local ticket')&&!ownCsv.includes('External fixture'),'Team Member export contains only own permitted ticket');
  await page.goto(base+'/dashboard.php');check((await page.locator('[data-metric="total"] strong').innerText())==='1'&&await page.locator('.source-bar').count()===1,'overview respects Team Member own-ticket scope');
  check((await page.request.post(base+'/dashboard.php',{headers:{'X-Test-Role':'admin'}})).status()===405,'overview rejects write requests');
  for(const role of ['management','department-head','team-leader','pod-leader','sme','client-viewer']){
   await page.setExtraHTTPHeaders({'X-Test-Role':role});await page.goto(base+'/index.php');
   check(await page.getByRole('link',{name:'Export CSV',exact:true}).count()===1&&(await page.request.get(base+'/index.php?download=csv',{headers:{'X-Test-Role':role}})).status()===200,'ticket export available for '+role+' without separate export grant');
  }
  await page.setExtraHTTPHeaders({'X-Test-Role':'client-viewer'});response=await page.goto(base+'/staff.php');
  check(response.status()===200&&(await page.locator('.directory-table').innerText()).includes('Fixture Employee'),'client can view staff list without database view_staff grant');
  check(await page.getByRole('link',{name:'Staff directory',exact:true}).count()===1&&await page.getByRole('link',{name:/Add employee/}).count()===0,'client staff navigation visible and add control hidden');
  await page.getByRole('link',{name:'View profile for Fixture Employee'}).click();
  check((await page.locator('body').innerText()).includes('Fixture Employee')&&await page.getByRole('link',{name:'Edit profile',exact:true}).count()===0&&await page.getByRole('button',{name:'Add note',exact:true}).count()===0,'client staff profile is read only even with database manage_staff grant');
  check((await page.request.get(base+'/employee-form.php?id=1',{headers:{'X-Test-Role':'client-viewer'}})).status()===403,'client direct staff editor denied');
  check((await page.request.post(base+'/employee-form.php?id=1',{headers:{'X-Test-Role':'client-viewer'},form:{full_name:'MUST NOT WRITE'}})).status()===403,'client staff update POST denied');
  check((await page.request.post(base+'/employee.php?id=1',{headers:{'X-Test-Role':'client-viewer'},form:{history_note:'MUST NOT WRITE'}})).status()===403,'client history note POST denied');
  check((await page.request.get(base+'/index.php?download=csv',{headers:{'X-Test-Role':'denied'}})).status()===403,'export denies users without ticket viewing access');
  await page.setExtraHTTPHeaders({'X-Test-Role':'denied'});check((await page.request.get(base+'/index.php',{headers:{'X-Test-Role':'denied'}})).status()===403,'ticket permission required');
  await page.setExtraHTTPHeaders({'X-Test-Role':'first-login'});await page.goto(base+'/index.php');check(page.url().endsWith('/change-password.php'),'first-login change enforced');
  await page.setExtraHTTPHeaders({'X-Test-Role':'admin'});await page.goto(base+'/external-tickets.php');check(page.url().endsWith('/index.php'),'old external list URL returns mixed list');
  console.log(checks+' isolated mixed browser checks passed.');
 }finally{await browser.close();}
})().catch(error=>{console.error(error.stack);process.exitCode=1;});
