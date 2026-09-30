"""Run the real reader/layout/auth with isolated account rows and the real hosted feed."""
import os
from pathlib import Path
import socket
import subprocess
import time
import urllib.request

root = Path(__file__).resolve().parent.parent
fixture = root / '.local/feed-qa'
fixture.mkdir(parents=True, exist_ok=True)
app = (root / 'backend/app').as_posix()
bootstrap = '''<?php
session_start();
$role = $_SERVER['HTTP_X_TEST_ROLE'] ?? '';
if ($role !== '') $_SESSION['user_id'] = 1; else unset($_SESSION['user_id']);
function db() { return new FixtureDb(); }
class FixtureDb { function prepare($sql) { return new FixtureStatement($sql); } }
class FixtureStatement {
    private $sql; private $params = [];
    function __construct($sql) { $this->sql = $sql; }
    function execute($params = []) { $this->params = $params; return true; }
    function fetch() {
        $role = $_SERVER['HTTP_X_TEST_ROLE'] ?? '';
        return ['id'=>1,'role_id'=>1,'role_slug'=>$role === 'admin' ? 'super-admin' : ($role === 'tl' ? 'team-leader' : $role),'must_change_password'=>$role === 'first-login' ? 1 : 0,'full_name'=>'Fixture User','role_name'=>'Fixture Role'];
    }
    function fetchColumn() {
        return ($_SERVER['HTTP_X_TEST_ROLE'] ?? '') !== 'denied';
    }
}
require '__APP__/security.php';
require '__APP__/auth.php';
require '__APP__/layout.php';
'''.replace('__APP__', app)
(fixture / 'bootstrap.php').write_text(bootstrap, encoding='utf-8')
page = (root / 'frontend/external-tickets.php').read_text(encoding='utf-8')
page = page.replace("require dirname(__DIR__) . '/backend/app/bootstrap.php';", "require __DIR__ . '/bootstrap.php';")
page = page.replace("require_once dirname(__DIR__) . '/backend/app/feed.php';", "require_once '" + app + "/feed.php';")
(fixture / 'external-tickets.php').write_text(page, encoding='utf-8')
router = '''<?php
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if (strpos($path, '/backend/') === 0) { http_response_code(403); exit('Forbidden'); }
if (strpos($path, '/assets/') === 0 && strpos($path, '..') === false) {
    $file = '__FRONTEND__' . $path;
    if (!is_file($file)) { http_response_code(404); exit; }
    $ext = pathinfo($file, PATHINFO_EXTENSION);
    header('Content-Type: ' . (['css'=>'text/css','png'=>'image/png','webp'=>'image/webp'][$ext] ?? 'application/octet-stream'));
    readfile($file); exit;
}
if ($path === '/login.php' || $path === '/change-password.php') { echo 'Fixture authentication landing'; exit; }
if ($path !== '/external-tickets.php') { http_response_code(404); exit; }
require __DIR__ . '/external-tickets.php';
'''.replace('__FRONTEND__', (root / 'frontend').as_posix())
(fixture / 'router.php').write_text(router, encoding='utf-8')
with socket.socket() as sock:
    sock.bind(('127.0.0.1', 0))
    port = sock.getsockname()[1]
log = (fixture / 'server.log').open('w')
server = subprocess.Popen([r'C:\xampp\php\php.exe','-S',f'127.0.0.1:{port}','-t',str(fixture),str(fixture / 'router.php')], stdout=log, stderr=log, creationflags=subprocess.CREATE_NO_WINDOW)
try:
    for _ in range(50):
        try:
            urllib.request.urlopen(f'http://127.0.0.1:{port}/login.php', timeout=1).close()
            break
        except OSError:
            time.sleep(.1)
    env = dict(os.environ, FEED_QA_BASE=f'http://127.0.0.1:{port}', FEED_QA_FIXTURE='1')
    subprocess.run(['node',str(root / 'tests/feed-browser.cjs')], cwd=root, env=env, check=True)
finally:
    server.terminate()
    server.wait(timeout=5)
    log.close()
