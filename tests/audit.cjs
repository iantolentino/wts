// Local audit regressions. Run after browser.cjs. Uses a disposable QA account.
const {chromium}=require('playwright');
const fs=require('node:fs');
const path=require('node:path');
const assert=require('node:assert/strict');
const {randomBytes}=require('node:crypto');
const root=path.resolve(__dirname,'..'),base='http://127.0.0.1:8030';
const accounts=JSON.parse(fs.readFileSync(path.join(root,'.local/test-accounts.json')));
const fixture=JSON.parse(fs.readFileSync(path.join(root,'.local/qa/results.json')));
const results=[];
function check(name,ok){assert.ok(ok,name);results.push(name);console.log('PASS '+name);}
(async()=>{
 const browser=await chromium.launch({executablePath:'C:/Program Files/Google/Chrome/Application/chrome.exe',headless:true});
 let admin,created=false;
 const username='qa.audit.'+Date.now(),password=randomBytes(20).toString('hex');
 async function login(account){const context=await browser.newContext();const page=await context.newPage();await page.goto(base+'/login.php');await page.locator('[name=username]').fill(account.username);await page.locator('[name=password]').fill(account.password);await Promise.all([page.waitForNavigation(),page.getByRole('button',{name:'Sign in',exact:true}).click()]);return page;}
 async function form(page,selector='form'){return page.locator(selector).evaluate(f=>Object.fromEntries(new FormData(f)));}
 try{
  const tl=await login(accounts.tl);
  await tl.goto(base+'/employee-form.php');
  check('New employee defaults to signed-in TL',await tl.locator('[name=tl_id]').inputValue()===String(accounts.tl.id));
  for(const route of ['staff.php','history.php','index.php','reports.php']){
   const r=await tl.request.get(base+'/'+route+'?from=2026-01%00-01');
   check(route+' rejects malformed date without server error',r.status()===200 && /Enter a valid date/.test(await r.text()));
  }
  for(const type of ['staff','history','tickets','report']){
   const r=await tl.request.get(base+'/export.php?type='+type+'&from=2026-01%00-01&to=2026-12-31');
   check(type+' export rejects malformed date',r.status()===400);
  }
  const invalidYear=await tl.request.get(base+'/reports.php?from=0001-01-01');
  check('Out-of-range database dates rejected',invalidYear.status()===200 && /Enter a valid date/.test(await invalidYear.text()));
  await tl.goto(base+'/history.php?department_id=999999&from=bad');
  check('Invalid history filters reset displayed and applied filters together',await tl.locator('[name=department_id]').inputValue()==='' && await tl.locator('.timeline-item').count()>0);
  await tl.goto(base+'/create-ticket.php');
  const fields=await form(tl);
  const department=await tl.locator('[name=department_id] option').nth(1).getAttribute('value');
  const oversized=await tl.request.post(base+'/create-ticket.php',{form:{...fields,subject:'QA oversized input must not save',staff_id:fixture.staffId,department_id:department,category:'General Request',priority:'normal',issue:'😀'.repeat(17000)}});
  check('Multibyte text overflow returns validation instead of database error',oversized.status()===200 && /Issue is too large/.test(await oversized.text()));
  const ticketUrl=base+'/ticket.php?id='+fixture.ticketId;
  await tl.goto(ticketUrl);
  const original=await form(tl,'form:has([name=version])');
  try{
   const zero=await tl.request.post(ticketUrl,{form:{...original,status:'closed',resolution:'0'}});
   check('Zero resolution is preserved',zero.status()===200 && /<h3>Resolution<\/h3><p class="preserve-lines">0<\/p>/.test(await zero.text()));
  }finally{
   await tl.goto(ticketUrl);const current=await form(tl,'form:has([name=version])');
   await tl.request.post(ticketUrl,{form:{...original,version:current.version,csrf_token:current.csrf_token}});
  }
  admin=await login(accounts.admin);await admin.goto(base+'/users.php');
  let values=await form(admin,'form:has([name=full_name])');
  const badPassword=await admin.request.post(base+'/users.php',{form:{...values,username,full_name:'QA Audit Account',role:'management',password:password+'\0x'}});
  check('Account creation rejects null-byte password gracefully',badPassword.status()===200 && /Passwords cannot contain null/.test(await badPassword.text()));
  await admin.request.post(base+'/users.php',{form:{...values,username,full_name:'QA Audit Account',role:'management',password}});
  await admin.reload();check('Disposable audit account created',await admin.getByRole('row').filter({hasText:username}).count()===1);created=true;
  const user=await login({username,password});
  check('New account must change password',user.url().endsWith('/change-password.php'));
  values=await form(user);
  const same=await user.request.post(base+'/change-password.php',{form:{...values,current_password:password,new_password:password,confirm_password:password}});
  check('Initial password cannot be reused to bypass required change',same.status()===200 && /Choose a different password/.test(await same.text()));
  const nul=await user.request.post(base+'/change-password.php',{form:{...values,current_password:password,new_password:password+'\0x',confirm_password:password+'\0x'}});
  check('Password change rejects null bytes gracefully',nul.status()===200 && /Passwords cannot contain null/.test(await nul.text()));
  await user.goto(base+'/logout.php');check('First-login account can reach sign-out confirmation',user.url().endsWith('/logout.php'));
  await Promise.all([user.waitForNavigation(),user.getByRole('button',{name:'Sign out',exact:true}).click()]);
  await user.goto(base+'/dashboard.php');check('First-login sign-out clears authentication',user.url().endsWith('/login.php'));
  const management=await login(accounts.management),viewer=await login(accounts.viewer);
  for(const [role,p] of [['Management',management],['Viewer',viewer]]){
   await p.goto(base+'/logout.php');const token=await p.locator('[name=csrf_token]').inputValue();
   for(const route of ['employee-form.php','users.php','create-ticket.php'])check(role+' POST denied '+route,(await p.request.post(base+'/'+route,{form:{csrf_token:token}})).status()===403);
   for(const action of ['comment','update'])check(role+' cannot '+action+' ticket',(await p.request.post(ticketUrl,{form:{csrf_token:token,action,body:'Must not save'}})).status()===403);
   for(const route of ['employee.php?id='+fixture.staffId,'departments.php'])check(role+' mutation denied '+route,(await p.request.post(base+'/'+route,{form:{csrf_token:token,history_note:'Must not save'}})).status()===403);
  }
  const anon=await browser.newContext();
  for(const route of ['dashboard.php','staff.php','history.php','reports.php','users.php','export.php?type=staff','attachment.php?id=1'])check('Anonymous denied '+route,(await anon.request.get(base+'/'+route,{maxRedirects:0})).status()===302);
  for(const route of ['/.local/owner-accounts.json','/.LOCAL/owner-accounts.json','/config/config.example.php','/CONFIG/config.example.php','/database/schema.sql','/tools/provision-accounts.php','/.git/config'])check('Private path denied '+route,(await anon.request.get(base+route)).status()===403);
 }finally{
  if(created&&admin){await admin.goto(base+'/users.php');const button=admin.getByRole('row').filter({hasText:username}).getByRole('button',{name:'Deactivate',exact:true});if(await button.count())await Promise.all([admin.waitForNavigation(),button.click()]);check('Audit account left deactivated',await admin.getByRole('row').filter({hasText:username}).getByRole('button',{name:'Activate',exact:true}).count()===1);}
  await browser.close();
 }
 fs.writeFileSync(path.join(root,'.local/qa/audit-results.json'),JSON.stringify({date:new Date().toISOString(),passed:results.length,results},null,2));
 console.log('Completed '+results.length+' audit checks.');
})().catch(e=>{console.error(e);process.exitCode=1;});
