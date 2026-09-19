// Local registration/approval workflow. Uses fictional, unique QA accounts.
const {chromium}=require('playwright');
const fs=require('node:fs'),path=require('node:path'),assert=require('node:assert/strict');
const {randomBytes}=require('node:crypto');
const root=path.resolve(__dirname,'..'),base='http://127.0.0.1:8030';
const accounts=JSON.parse(fs.readFileSync(path.join(root,'.local/test-accounts.json')));
const results=[],created=[];const run=Date.now();
const check=(name,ok)=>{assert.ok(ok,name);results.push(name);console.log('PASS '+name);};
(async()=>{const browser=await chromium.launch({executablePath:'C:/Program Files/Google/Chrome/Application/chrome.exe',headless:true});let admin;
const newPage=async()=>{const ctx=await browser.newContext();return ctx.newPage();};
async function login(p,a){await p.goto(base+'/login.php');await p.locator('[name=username]').fill(a.username);await p.locator('[name=password]').fill(a.password);await Promise.all([p.waitForNavigation(),p.getByRole('button',{name:'Sign in',exact:true}).click()]);}
const form=p=>p.locator('form').first().evaluate(f=>Object.fromEntries(new FormData(f)));
try{
 const p=await newPage();await p.goto(base+'/login.php');
 check('Login links to registration',await p.getByRole('link',{name:'Create an account',exact:true}).count()===1);
 for(const width of [1440,390]){
  await p.setViewportSize({width,height:1000});
  for(const route of ['login.php','register.php']){
   await p.goto(base+'/'+route);const logos=p.locator('.auth-logos img');
   check(route+' logos load at '+width,await logos.evaluateAll(imgs=>imgs.length===2&&imgs.every(i=>i.complete&&i.naturalWidth>0)));
   check(route+' Whittles left and Strata right at '+width,await logos.evaluateAll(imgs=>imgs[0].alt==='Whittles'&&imgs[1].alt==='Strata Staff Global'&&imgs[0].getBoundingClientRect().right<=imgs[1].getBoundingClientRect().left));
   check(route+' fits '+width,await p.evaluate(()=>document.documentElement.scrollWidth<=innerWidth));
   check(route+' ticketing wording at '+width,!/Whittles workspace|People & service|Every person|Every chapter|One shared record/i.test(await p.locator('body').innerText()));
   await p.screenshot({path:path.join(root,'.local/qa',route.replace('.php','')+'-registration-'+width+'.png'),fullPage:true});
  }
 }
 check('Registration never offers Super Admin',await p.locator('[name=role] option[value="super-admin"]').count()===0);
 await p.locator('[name=password]').fill('ExamplePassword!123');await p.locator('[name=confirm_password]').fill('DifferentPassword!123');
 check('Live password mismatch feedback',await p.locator('#password-feedback').innerText()==='Passwords do not match.' && await p.locator('[name=confirm_password]').evaluate(i=>!i.validity.valid));
 await p.locator('[name=confirm_password]').fill('ExamplePassword!123');check('Live password match feedback',await p.locator('#password-feedback').innerText()==='Passwords match.');
 await p.locator('#show-passwords').check();check('Show passwords reveals both fields',await p.locator('[name=password]').getAttribute('type')==='text'&&await p.locator('[name=confirm_password]').getAttribute('type')==='text');
 await p.locator('#show-passwords').uncheck();
 let values=await form(p);const good={...values,email:`qa.${run}@example.test`,username:`qa.reg.${run}`,role:'team-leader',password:randomBytes(20).toString('hex')};good.confirm_password=good.password;
 const post=(overrides)=>p.request.post(base+'/register.php',{form:{...good,...overrides}});
 check('Registration requires CSRF',(await post({csrf_token:''})).status()===419);
 for(const [name,overrides,text] of [['forged Super Admin',{role:'super-admin'},'supported registration role'],['password mismatch',{confirm_password:'different'},'Passwords do not match'],['invalid email',{email:'bad'},'valid email'],['short password',{password:'short',confirm_password:'short'},'12 and 72'],['null password',{password:'123456789012\0',confirm_password:'123456789012\0'},'null characters']]){
  const r=await post(overrides);check('Server rejects '+name,r.status()===200&&(await r.text()).includes(text));
 }
 admin=await newPage();await login(admin,accounts.admin);
 for(const [i,role] of ['team-leader','management','client-viewer'].entries()){
  const account={username:`qa.reg.${run}.${i}`,email:`qa.${run}.${i}@example.test`,password:good.password};
  const r=await post({...account,role,is_active:'1',approval_status:'approved'});
  check(role+' registration submitted pending',r.url().endsWith('/login.php')&&(await r.text()).includes('Registration submitted'));
  created.push(account);
  const user=await newPage();await login(user,account);
  check(role+' pending login denied',user.url().endsWith('/login.php')&&(await user.locator('body').innerText()).includes('pending Super Admin approval'));
  check(role+' pending session cannot access app',(await user.request.get(base+'/dashboard.php',{maxRedirects:0})).status()===302);
  await admin.goto(base+'/users.php');let row=admin.getByRole('row').filter({hasText:account.username});
  check(role+' appears with email and pending status',(await row.innerText()).includes(account.email)&&(await row.innerText()).includes('Pending approval'));
  const data=await row.locator('form').evaluate(f=>Object.fromEntries(new FormData(f)));
  const toggle=await admin.request.post(base+'/users.php',{form:{...data,action:'toggle'}});check('Activation cannot bypass '+role+' review',(await toggle.text()).includes('Only approved accounts'));
  if(i===0){
   const dup=await post({...account,username:account.username+'.other',role});check('Duplicate email rejected',(await dup.text()).includes('already registered'));
   const dupUser=await post({...account,email:'other.'+account.email,role});check('Duplicate username rejected',(await dupUser.text()).includes('already registered'));
   const mgmt=await newPage();await login(mgmt,accounts.management);await mgmt.goto(base+'/logout.php');const token=(await form(mgmt)).csrf_token;
   check('Management cannot approve registrations',(await mgmt.request.post(base+'/users.php',{form:{...data,csrf_token:token,action:'approve'}})).status()===403);
   await admin.goto(base+'/employee-form.php');check('Pending TL absent from staff assignment',await admin.locator('[name=tl_id] option').filter({hasText:account.username}).count()===0);
   await admin.goto(base+'/create-ticket.php');check('Pending TL absent from ticket assignment',await admin.locator('[name=assignee_id] option').filter({hasText:account.username}).count()===0);
  }
  await admin.goto(base+'/users.php');row=admin.getByRole('row').filter({hasText:account.username});const action=i===1?'Reject':'Approve';
  await Promise.all([admin.waitForNavigation(),row.getByRole('button',{name:action,exact:true}).click()]);
  await login(user,account);
  if(i===1){check('Rejected registration cannot sign in',user.url().endsWith('/login.php')&&(await user.locator('body').innerText()).includes('not approved'));}
  else{
   check(role+' approved account signs in directly',user.url().endsWith('/dashboard.php'));
   check(role+' approved permissions correct',(await user.request.get(base+'/employee-form.php')).status()===(i===0?200:403));
   check('Approved registration cannot be reviewed twice',(await (await admin.request.post(base+'/users.php',{form:{...data,action:'approve'}})).text()).includes('already been reviewed'));
  }
 }
 for(const role of ['admin','tl','management','viewer']){
  const u=await newPage();await login(u,accounts[role]);
  check(role+' has no removed workspace branding',await u.locator('.workspace-brand').count()===0&&!/Whittles workspace|People & service/i.test(await u.locator('body').innerText()));
  check(role+' sidebar has Strata above Whittles',await u.locator('.brand img').evaluateAll(imgs=>imgs.length===2&&imgs[0].alt==='Strata Staff Global'&&imgs[0].getBoundingClientRect().bottom<=imgs[1].getBoundingClientRect().top));
  if(role==='admin'){await u.screenshot({path:path.join(root,'.local/qa/dual-brand-dashboard.png'),fullPage:true});}
 }
}finally{
 if(admin)for(const account of created){await admin.goto(base+'/users.php');const row=admin.getByRole('row').filter({hasText:account.username});const deactivate=row.getByRole('button',{name:'Deactivate',exact:true});if(await deactivate.count())await Promise.all([admin.waitForNavigation(),deactivate.click()]);const reject=row.getByRole('button',{name:'Reject',exact:true});if(await reject.count())await Promise.all([admin.waitForNavigation(),reject.click()]);}
 await browser.close();
}
fs.writeFileSync(path.join(root,'.local/qa/registration-results.json'),JSON.stringify({passed:results.length,results,date:new Date().toISOString()},null,2));console.log('Completed '+results.length+' registration checks. QA accounts left inactive/rejected.');
})().catch(e=>{console.error(e);process.exitCode=1;});
