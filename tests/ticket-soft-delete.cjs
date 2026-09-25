// Run after browser, handover, and audit checks against the local QA instance.
// Deletes only the clearly labeled ticket recorded by tests/browser.cjs.
const { chromium } = require('playwright');
const fs = require('node:fs');
const path = require('node:path');
const root = path.resolve(__dirname, '..');
const base = 'http://127.0.0.1:8030';
const accounts = JSON.parse(fs.readFileSync(path.join(root, '.local/test-accounts.json'), 'utf8'));
const fixture = JSON.parse(fs.readFileSync(path.join(root, '.local/qa/results.json'), 'utf8'));
const checks = [];
function check(name, passed, details = '') {
  checks.push({ name, passed: Boolean(passed), details: String(details) });
  console.log(`${passed ? 'PASS' : 'FAIL'} ${name}${details ? ` :: ${details}` : ''}`);
}
async function login(browser, account) {
  const page = await browser.newPage();
  await page.goto(base + '/login.php');
  await page.locator('[name=username]').fill(account.username);
  await page.locator('[name=password]').fill(account.password);
  await Promise.all([page.waitForURL('**/dashboard.php'), page.getByRole('button', { name: 'Sign in', exact: true }).click()]);
  return page;
}

(async () => {
  const browser = await chromium.launch({ executablePath: 'C:/Program Files/Google/Chrome/Application/chrome.exe', headless: true });
  try {
    const teamLeader = await login(browser, accounts.tl);
    const ticketUrl = `${base}/ticket.php?id=${fixture.ticketId}`;
    await teamLeader.goto(ticketUrl);
    check('Team Leader cannot see the delete control', await teamLeader.getByRole('button', { name: 'Delete ticket', exact: true }).count() === 0);
    check('Team Leader cannot see the deleted tickets sidebar link', await teamLeader.getByRole('link', { name: 'Deleted tickets', exact: true }).count() === 0);
    check('Team Leader cannot open the deleted tickets page', (await teamLeader.request.get(`${base}/deleted-tickets.php`)).status() === 403);
    const token = await teamLeader.locator('[name=csrf_token]').first().inputValue();
    const denied = await teamLeader.request.post(ticketUrl, { form: { csrf_token: token, action: 'delete' }, maxRedirects: 0 });
    check('Team Leader delete POST is denied', denied.status() === 403, `status=${denied.status()}`);

    const admin = await login(browser, accounts.admin);
    await admin.goto(ticketUrl);
    check('Super Admin sees the delete control', await admin.getByRole('button', { name: 'Delete ticket', exact: true }).count() === 1);
    admin.once('dialog', dialog => dialog.accept());
    await Promise.all([admin.waitForURL('**/index.php'), admin.getByRole('button', { name: 'Delete ticket', exact: true }).click()]);
    check('Super Admin soft-deletes the QA ticket', new URL(admin.url()).pathname.endsWith('/index.php'));
    const hidden = await admin.request.get(ticketUrl);
    check('Soft-deleted ticket is no longer directly visible', hidden.status() === 404, `status=${hidden.status()}`);
    const tickets = await admin.request.get(`${base}/export.php?type=tickets`);
    check('Soft-deleted ticket is omitted from active ticket export', tickets.status() === 200 && !(await tickets.text()).includes(fixture.ticketSubject), `status=${tickets.status()}`);
    await admin.goto(`${base}/deleted-tickets.php`);
    check('Super Admin opens the deleted tickets list', await admin.getByRole('heading', { name: 'Recently deleted tickets', exact: true }).count() === 1);
    check('Deleted QA ticket appears in the list', await admin.getByRole('row').filter({ hasText: fixture.ticketSubject }).count() === 1);
  } finally {
    await browser.close();
  }
  const result = { date: new Date().toISOString(), ticketId: fixture.ticketId, passed: checks.filter(item => item.passed).length, failed: checks.filter(item => !item.passed).length, checks };
  fs.writeFileSync(path.join(root, '.local/qa/ticket-soft-delete-results.json'), JSON.stringify(result, null, 2));
  if (result.failed) process.exitCode = 1;
  console.log(`Completed ${result.passed} soft-delete checks; ${result.failed} failed.`);
})().catch(error => { console.error(error.stack || error); process.exitCode = 1; });
