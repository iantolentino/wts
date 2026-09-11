const {chromium}=require('playwright');
const fs=require('node:fs');const path=require('node:path');const assert=require('node:assert/strict');
const root=path.resolve(__dirname,'..');const account=JSON.parse(fs.readFileSync(path.join(root,'.local/test-accounts.json'))).tl;
(async()=>{const browser=await chromium.launch({executablePath:'C:/Program Files/Google/Chrome/Application/chrome.exe',headless:true});try{
const page=await browser.newPage({viewport:{width:1440,height:1000}});await page.goto('http://127.0.0.1:8030/login.php');await page.screenshot({path:path.join(root,'.local/qa/login.png'),fullPage:true});
await page.locator('input[name=username]').fill(account.username);await page.locator('input[name=password]').fill(account.password);await Promise.all([page.waitForURL('**/dashboard.php'),page.getByRole('button',{name:'Sign in',exact:true}).click()]);
for(const [route,name] of [['dashboard.php','dashboard'],['staff.php','staff'],['employee.php?id=4','employee'],['reports.php','reports']]){const response=await page.goto('http://127.0.0.1:8030/'+route);assert.equal(response.status(),200);await page.screenshot({path:path.join(root,'.local/qa/'+name+'.png'),fullPage:true});assert.ok(await page.evaluate(()=>document.documentElement.scrollWidth<=innerWidth+1));}
await page.goto('http://127.0.0.1:8030/dashboard.php');assert.equal(await page.locator('.timeline-item').first().evaluate(e=>getComputedStyle(e).display),'block');
await page.setViewportSize({width:390,height:844});await page.screenshot({path:path.join(root,'.local/qa/mobile.png'),fullPage:true});assert.ok(await page.evaluate(()=>document.documentElement.scrollWidth<=innerWidth+1));console.log('PASS final desktop/mobile layout checks and screenshots');
}finally{await browser.close();}})().catch(e=>{console.error(e);process.exitCode=1;});
