<?php
declare(strict_types=1);

// Run after importing the application schema. Never resets existing accounts.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$options = getopt('', ['admin-role:', 'output:']);
$adminRole = $options['admin-role'] ?? '';
$output = $options['output'] ?? '';
if (!in_array($adminRole, ['super-admin', 'team-leader', 'management'], true) || $output === '') {
    fwrite(STDERR, "Usage: php backend/tools/provision-accounts.php --admin-role=management --output=<private-credentials.json>\n");
    exit(1);
}
$root = dirname(__DIR__, 2);
$configPath = is_file($root.'/backend/config/config.local.php')
    ? $root.'/backend/config/config.local.php'
    : $root.'/backend/config/config.example.php';
$config = require $configPath;
$initialSuperAdminPassword = $config['accounts']['superadmin_initial_password'] ?? '';
if (!is_string($initialSuperAdminPassword) || ($initialSuperAdminPassword !== '' && (strlen($initialSuperAdminPassword) < 12 || strlen($initialSuperAdminPassword) > 72))) {
    fwrite(STDERR, "The configured initial Super Admin password must be 12 to 72 characters.\n");
    exit(1);
}
require $root.'/backend/app/database.php';
$pdo = db();
$accounts = [
    ['tl', 'Team Leader', 'team-leader'],
    ['superadmin', 'Super Administrator', 'super-admin'],
    ['admin1', 'Administrator 1', $adminRole],
    ['admin2', 'Administrator 2', $adminRole],
    ['admin3', 'Administrator 3', $adminRole],
];
$created = [];
$handle = null;
try {
    $pdo->beginTransaction();
    foreach ($accounts as [$username, $name, $role]) {
        $existing = $pdo->prepare('SELECT id FROM users WHERE username=?');
        $existing->execute([$username]);
        if ($existing->fetchColumn()) {
            echo "Preserved existing account: $username (password, role and status unchanged).\n";
            continue;
        }
        $roleQuery = $pdo->prepare('SELECT id FROM roles WHERE slug=?');
        $roleQuery->execute([$role]);
        $roleId = $roleQuery->fetchColumn();
        if (!$roleId) throw new RuntimeException("Missing role: $role. Import the application schema and migrations first.");
        $password = $username === 'superadmin' && $initialSuperAdminPassword !== ''
            ? $initialSuperAdminPassword
            : 'Wts!'.bin2hex(random_bytes(10));
        $insert = $pdo->prepare('INSERT INTO users(role_id,username,full_name,password_hash,must_change_password,is_active,approval_status) VALUES(?,?,?,?,1,1,\'approved\')');
        $insert->execute([$roleId, $username, $name, password_hash($password, PASSWORD_DEFAULT)]);
        $created[] = ['username'=>$username, 'password'=>$password, 'role'=>$role];
    }
    if ($created) {
        // Exclusive creation prevents overwriting an earlier credential handover.
        $handle = @fopen($output, 'x');
        if (!$handle) throw new RuntimeException('Cannot create credentials file; use a new writable private path. No accounts created.');
        if (DIRECTORY_SEPARATOR !== '\\' && !chmod($output, 0600)) throw new RuntimeException('Cannot restrict credentials file permissions.');
        $json = json_encode($created, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR)."\n";
        if (fwrite($handle, $json) !== strlen($json) || !fflush($handle)) throw new RuntimeException('Cannot save credentials. No accounts created.');
    }
    $pdo->commit();
    if (is_resource($handle)) fclose($handle);
    echo count($created)." account(s) created. First login requires a password change.\n";
    if ($created) echo "Temporary credentials saved to: $output\n";
} catch (Throwable $error) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    if (is_resource($handle)) { fclose($handle); unlink($output); }
    fwrite(STDERR, $error->getMessage()."\n");
    exit(1);
}
