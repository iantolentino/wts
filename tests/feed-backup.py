import hashlib
import io
import json
import pathlib
import re
import shutil
import socket
import subprocess
import time
import urllib.error
import urllib.request
import http.cookiejar
import zipfile

root = pathlib.Path(__file__).resolve().parent.parent
fixture = root / '.local/feed-backup-qa'
for folder in ['frontend', 'frontend/assets/css', 'backend/app', 'backend/config', 'backend/storage/private']:
    (fixture / folder).mkdir(parents=True, exist_ok=True)
bootstrap = '''<?php
session_start();
$role = $_SERVER['HTTP_X_TEST_ROLE'] ?? '';
if ($role !== '') $_SESSION['user_id'] = 1; else unset($_SESSION['user_id']);
function db() { return new BackupFixtureDb(); }
class BackupFixtureDb { function prepare($sql) { return new BackupFixtureStatement(); } }
class BackupFixtureStatement {
    function execute($params = []) { return true; }
    function fetch() {
        $role = $_SERVER['HTTP_X_TEST_ROLE'] ?? '';
        return ['id'=>1,'role_id'=>1,'role_slug'=>$role === 'admin' ? 'super-admin' : $role,'must_change_password'=>$role === 'first-login' ? 1 : 0];
    }
}
require '__APP__/security.php';
require '__APP__/auth.php';
'''.replace('__APP__', (root / 'backend/app').as_posix())
(fixture / 'backend/app/bootstrap.php').write_text(bootstrap, encoding='utf-8')
shutil.copy2(root / 'tools/whittles-feed-backup.php', fixture / 'frontend/whittles-feed-backup.php')
(fixture / 'router.php').write_text("<?php $path=parse_url($_SERVER['REQUEST_URI'],PHP_URL_PATH); if($path==='/whittles-feed-backup.php'){require __DIR__.'/frontend/whittles-feed-backup.php';exit;} echo 'Fixture authentication landing';", encoding='utf-8')
targets = ['frontend/index.php','frontend/external-tickets.php','backend/app/feed.php','backend/app/mixed-tickets.php','backend/app/layout.php','backend/config/feed.local.php','frontend/dashboard.php','frontend/ticket.php','frontend/assets/css/ticket-overview.css']
for target in targets:
    file = fixture / target
    if file.is_file():
        file.unlink()
(fixture / targets[4]).write_bytes(b'<?php // old layout fixture\r\n')
with socket.socket() as sock:
    sock.bind(('127.0.0.1', 0))
    port = sock.getsockname()[1]
log = (fixture / 'server.log').open('w')
server = subprocess.Popen([r'C:\xampp\php\php.exe','-S',f'127.0.0.1:{port}','-t',str(fixture),str(fixture / 'router.php')], stdout=log, stderr=log, creationflags=subprocess.CREATE_NO_WINDOW)
class NoRedirect(urllib.request.HTTPRedirectHandler):
    def redirect_request(self, *args, **kwargs):
        return None
opener = urllib.request.build_opener(NoRedirect(), urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))
checks = 0
def check(value, name):
    global checks
    assert value, name
    checks += 1
    print('PASS: ' + name)
def request(role='admin', data=None, method=None):
    headers = {'X-Test-Role':role} if role else {}
    req = urllib.request.Request(f'http://127.0.0.1:{port}/whittles-feed-backup.php', data=data, headers=headers, method=method)
    try:
        res = opener.open(req, timeout=5)
    except urllib.error.HTTPError as error:
        res = error
    return res.status, res.headers, res.read()
def download():
    status, headers, body = request()
    csrf = re.search(rb'name="csrf_token" value="([^"]+)"', body).group(1)
    return request(data=b'action=download&csrf_token=' + csrf)
try:
    for _ in range(50):
        try:
            request()
            break
        except urllib.error.URLError:
            time.sleep(.1)
    check(request(role='')[0] == 302, 'anonymous request requires sign-in')
    check(request(role='tl')[0] == 403, 'Team Leader denied')
    check(request(role='management')[0] == 403, 'Management denied')
    check(request(role='first-login')[0] == 302, 'password-change enforcement')
    check(request(data=b'action=download&csrf_token=wrong')[0] == 419, 'CSRF required')
    check(request(method='PUT')[0] == 405, 'unsupported method rejected')
    status, headers, body = download()
    check(status == 200 and headers.get_content_type() == 'application/zip', 'Super Admin gets ZIP')
    check('attachment;' in headers['Content-Disposition'] and headers['Cache-Control'] == 'no-store, private', 'download is private attachment')
    with zipfile.ZipFile(io.BytesIO(body)) as archive:
        manifest = json.loads(archive.read('MANIFEST.json'))
        check(len(manifest['files']) == 9, 'manifest records nine deployment targets')
        check(sum(f['status'] == 'not_present_before_upload' for f in manifest['files']) == 8, 'eight missing files recorded as absent')
        original = (fixture / targets[4]).read_bytes()
        check(archive.read(targets[4]) == original, 'existing layout backed up byte for byte')
        check(manifest['files'][4]['sha256'] == hashlib.sha256(original).hexdigest(), 'backup hash matches archived bytes')
        check('RESTORE.txt' in archive.namelist(), 'restore instructions included')
    check(not list((fixture / 'backend/storage/private').glob('feed-backup-*')), 'temporary ZIP removed after download')
    for target in targets:
        (fixture / target).write_text('original fixture content ' + target, encoding='utf-8')
    (fixture / targets[5]).write_text('<?php // private-token-fixture-ABC123', encoding='utf-8')
    check(b'private-token-fixture-ABC123' not in request()[2], 'private config content absent from page')
    status, headers, body = download()
    with zipfile.ZipFile(io.BytesIO(body)) as archive:
        check(all(archive.read(t) == (fixture / t).read_bytes() for t in targets), 'all nine existing files backed up unchanged')
        check(len(archive.namelist()) == 11, 'only nine targets plus manifest and restore notes')
    for target in targets:
        (fixture / target).unlink()
    check(download()[0] == 500, 'wrong installation with no targets fails')
    check(not list((fixture / 'backend/storage/private').glob('feed-backup-*')), 'failed backup also removes temporary ZIP')
    print(f'{checks} backup checks passed with isolated account and file fixtures.')
finally:
    server.terminate()
    server.wait(timeout=5)
    log.close()
