<?php
declare(strict_types=1);

function app_is_setup(): bool {
    try { $q = db()->prepare("SELECT setting_value FROM app_settings WHERE setting_key = 'setup_complete'"); $q->execute(); return $q->fetchColumn() === '1'; } catch (PDOException) { return false; }
}
function current_user(bool $refresh = false): ?array {
    static $user = false;
    if ($refresh) $user = false;
    if ($user !== false) return $user;
    if (empty($_SESSION['user_id'])) return $user = null;
    $q = db()->prepare("SELECT u.*, r.name AS role_name, r.slug AS role_slug FROM users u JOIN roles r ON r.id = u.role_id WHERE u.id = ? AND u.is_active = 1 AND u.approval_status='approved'");
    $q->execute([$_SESSION['user_id']]);
    $row=$q->fetch();
    if (!$row || !isset($_SESSION['session_version']) || (int)$_SESSION['session_version'] !== (int)$row['session_version']) { unset($_SESSION['user_id'],$_SESSION['session_version']); return $user=null; }
    return $user=$row;
}
function refresh_current_user(): ?array {
    return current_user(true);
}
function user_can(string $permissionKey): bool {
    $user = current_user(); if ($user === null) return false;
    $role = $user['role_slug'] ?? '';
    if ($role === 'super-admin') return (bool)query('SELECT 1 FROM permissions WHERE permission_key=?',[$permissionKey])->fetchColumn();
    if (in_array($role, ['management','client-viewer'], true)) {
        return in_array($permissionKey, ['view_all_tickets','view_staff','view_reports','export_tickets','export_staff','comment_tickets','view_attachments'], true);
    }
    if (in_array($permissionKey, ['manage_users','manage_roles','delete_tickets'], true)) return false;
    if ($role === 'team-leader' && in_array($permissionKey, ['create_tickets','edit_tickets','close_tickets','assign_tickets','comment_tickets'], true)) return true;
    $q = db()->prepare('SELECT COALESCE(uo.granted, rp.granted, 0) FROM permissions p LEFT JOIN role_permissions rp ON rp.permission_id = p.id AND rp.role_id = :role_id LEFT JOIN user_permission_overrides uo ON uo.permission_id = p.id AND uo.user_id = :user_id WHERE p.permission_key = :permission_key');
    $q->execute(['role_id' => $user['role_id'], 'user_id' => $user['id'], 'permission_key' => $permissionKey]);
    return (bool) $q->fetchColumn();
}
function default_landing_page(): string {
    if (user_can('view_all_tickets')) return 'dashboard.php';
    if (user_can('access_leave_request_module')) return 'leave-requests.php';
    return 'change-password.php';
}
function require_login(): array {
    $user = current_user(); if ($user === null) redirect('login.php');
    if ($user['must_change_password'] && !in_array(basename($_SERVER['PHP_SELF']), ['change-password.php', 'logout.php'], true)) redirect('change-password.php');
    return $user;
}
function require_permission(string $permissionKey): array {
    $user = require_login(); if (!user_can($permissionKey)) { http_response_code(403); exit('You do not have permission to access this action.'); } return $user;
}
function require_non_team_member(array $user): array {
    if (($user['role_slug'] ?? '') === 'team-member') { http_response_code(403); exit('This workspace is not available to Team Members.'); }
    return $user;
}
