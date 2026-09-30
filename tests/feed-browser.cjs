const { chromium } = require('C:/Users/Admin/Downloads/wts-build-tools/node_modules/playwright');
const fs = require('node:fs');
const path = require('node:path');
const root = path.resolve(__dirname, '..');
const accounts = JSON.parse(fs.readFileSync(path.join(root, '.local/test-accounts.json'), 'utf8'));
const token = fs.readFileSync('C:/Users/Admin/.local/wts-fetch-api/API-TOKEN.txt', 'utf8').trim();
const base = process.env.FEED_QA_BASE || 'http://127.0.0.1:8030';
const fixture = process.env.FEED_QA_FIXTURE === '1';
let checks = 0;
function check(value, name) { if (!value) throw new Error('FAIL: ' + name); checks++; console.log('PASS: ' + name); }
async function login(browser, account, role = 'admin') {
  const page = await browser.newPage();
  if (fixture) { await page.setExtraHTTPHeaders({'X-Test-Role':role}); return page; }
  await page.goto(base + '/login.php');
  await page.locator('[name=username]').fill(account.username);
  await page.locator('[name=password]').fill(account.password);
  await Promise.all([page.waitForURL('**/dashboard.php'), page.getByRole('button', {name:'Sign in', exact:true}).click()]);
  return page;
}
(async () => {
  const browser = await chromium.launch({ executablePath:'C:/Program Files/Google/Chrome/Application/chrome.exe', headless:true });
  try {
    const anonymous = await browser.newPage();
    await anonymous.goto(base + '/external-tickets.php');
    check(anonymous.url().endsWith('/login.php'), 'anonymous reader redirects to login');
    const page = await login(browser, accounts.admin);
    const errors = [];
    page.on('pageerror', error => errors.push(error.message));
    const response = await page.goto(base + '/external-tickets.php');
    check(response.status() === 200, 'authenticated live feed page');
    check(await page.locator('.feed-table tbody tr').count() === 25, 'first page has 25 tickets');
    check((await page.locator('.feed-pages').innerText()).includes('39 tickets'), 'page reports 39 source tickets');
    check(!(await page.content()).includes(token), 'API token absent from HTML');
    await page.getByRole('link', {name:'Next', exact:true}).click();
    check(await page.locator('.feed-table tbody tr').count() === 14, 'second page has remaining 14 tickets');
    await page.locator('.feed-table tbody a').first().click();
    check(await page.getByRole('heading', {name:'Conversation',exact:true}).count() === 1, 'ticket opens conversation');
    check(await page.getByRole('button', {name:/save|delete/i}).count() === 0, 'source ticket is read-only');
    await page.goto(base + '/external-tickets.php?source=stratast_escalations');
    check((await page.locator('.feed-table').innerText()).includes('No source tickets match'), 'empty HR source shown correctly');
    await page.goto(base + '/external-tickets.php?q=NO-MATCH-EXTERNAL-20260929');
    check((await page.locator('.feed-table').innerText()).includes('No source tickets match'), 'search empty state');
    check((await page.goto(base + '/external-tickets.php?source=invalid')).status() === 400, 'invalid source rejected');
    check((await page.goto(base + '/external-tickets.php?id=1')).status() === 400, 'detail needs source');
    check((await page.goto(base + '/external-tickets.php?source[]=stratast_support')).status() === 400, 'array query rejected');
    await page.goto(base + '/external-tickets.php');
    for (const width of [390, 768, 1440]) {
      await page.setViewportSize({width,height:900});
      check(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth), 'no page overflow at ' + width + 'px');
    }
    check((await page.request.get(base + '/backend/config/feed.local.php')).status() === 403, 'private feed config denied');
    check(errors.length === 0, 'no browser script errors');
    for (const role of ['tl','management','viewer']) {
      const rolePage = await login(browser, accounts[role], role);
      check((await rolePage.goto(base + '/external-tickets.php')).status() === 200, role + ' existing ticket permission allows reader');
      await rolePage.close();
    }
    if (fixture) {
      for (const [role, status] of [['team-member',403],['denied',403]]) {
        const rolePage = await login(browser, accounts.admin, role);
        check((await rolePage.goto(base + '/external-tickets.php')).status() === status, role + ' denied by actual permission helpers');
        await rolePage.close();
      }
      const firstLogin = await login(browser, accounts.admin, 'first-login');
      await firstLogin.goto(base + '/external-tickets.php');
      check(firstLogin.url().endsWith('/change-password.php'), 'first-login password change enforced');
      await firstLogin.close();
    }
    console.log(checks + ' browser checks passed' + (fixture ? ' (isolated account database; live hosted feed).' : '.'));
  } finally { await browser.close(); }
})().catch(error => { console.error(error.message); process.exitCode = 1; });
