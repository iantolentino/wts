<?php
declare(strict_types=1);

// Upload this temporary tool into Whittle's frontend/ folder, then use the
// normal top-level /whittles-feed-backup.php URL through the existing rewrite.
$backupRoot = realpath(dirname(__DIR__));
if ($backupRoot === false || !is_file($backupRoot . '/backend/app/bootstrap.php')) {
    http_response_code(500);
    exit('Upload this file into the existing Whittle frontend folder.');
}
require $backupRoot . '/backend/app/bootstrap.php';
$user = require_login();
if (($user['role_slug'] ?? '') !== 'super-admin') {
    http_response_code(403);
    exit('Only a Super Admin can download this backup.');
}

$backupFiles = [
    'frontend/index.php',
    'frontend/external-tickets.php',
    'backend/app/feed.php',
    'backend/app/mixed-tickets.php',
    'backend/app/layout.php',
    'backend/config/feed.local.php',
    'frontend/dashboard.php',
    'frontend/ticket.php',
    'frontend/assets/css/ticket-overview.css',
];
$error = '';
$method = $_SERVER['REQUEST_METHOD'] ?? '';
if (!in_array($method, ['GET', 'POST'], true)) {
    header('Allow: GET, POST');
    http_response_code(405);
    exit('Use the backup page to download these files.');
}
if ($method === 'POST') {
    verify_csrf();
    if (request_string($_POST, 'action') !== 'download') {
        http_response_code(400);
        exit('Unknown backup action.');
    }
    $tempPath = false;
    try {
        if (!class_exists('ZipArchive')) throw new RuntimeException('Enable the PHP ZIP extension in cPanel before downloading the backup.');
        $privateDirectory = $backupRoot . '/backend/storage/private';
        $privateResolved = realpath($privateDirectory);
        $rootPrefix = str_replace('\\', '/', $backupRoot) . '/';
        if ($privateResolved === false || strncmp(str_replace('\\', '/', $privateResolved), $rootPrefix, strlen($rootPrefix)) !== 0 || !is_dir($privateDirectory) || !is_writable($privateDirectory) || is_link($privateDirectory)) {
            throw new RuntimeException('The existing backend/storage/private folder must be writable to prepare the backup.');
        }
        $tempPath = tempnam($privateDirectory, 'feed-backup-');
        if ($tempPath === false || realpath(dirname($tempPath)) !== realpath($privateDirectory)) throw new RuntimeException('Unable to prepare the private backup file.');
        register_shutdown_function(static function () use ($tempPath): void {
            if (is_file($tempPath)) unlink($tempPath);
        });
        $zip = new ZipArchive();
        if ($zip->open($tempPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) throw new RuntimeException('Unable to create the backup archive.');
        $manifest = ['created_at' => gmdate('Y-m-d\TH:i:s\Z'), 'scope' => 'Whittle external feed deployment files only', 'files' => []];
        $added = 0;
        try {
            foreach ($backupFiles as $relative) {
                $path = $backupRoot . '/' . $relative;
                if (is_link($path)) throw new RuntimeException('A backup target is a symbolic link. Resolve it before using this tool.');
                if (!file_exists($path)) {
                    $manifest['files'][] = ['path' => $relative, 'status' => 'not_present_before_upload'];
                    continue;
                }
                $resolved = realpath($path);
                $rootPrefix = str_replace('\\', '/', $backupRoot) . '/';
                $resolvedPath = $resolved === false ? '' : str_replace('\\', '/', $resolved);
                if ($resolved === false || strncmp($resolvedPath, $rootPrefix, strlen($rootPrefix)) !== 0 || !is_file($path) || !is_readable($path)) {
                    throw new RuntimeException('A backup target could not be read safely. No backup was downloaded.');
                }
                $bytes = file_get_contents($path);
                if ($bytes === false || !$zip->addFromString($relative, $bytes)) throw new RuntimeException('Unable to include every existing target file in the backup.');
                $manifest['files'][] = ['path' => $relative, 'status' => 'backed_up', 'bytes' => strlen($bytes), 'sha256' => hash('sha256', $bytes)];
                ++$added;
            }
            if ($added === 0) throw new RuntimeException('None of the expected deployment files exists. Check that this tool is in the correct Whittle installation.');
            $instructions = "Whittle external feed backup\n\nRestore only the files marked backed_up in MANIFEST.json to their original relative paths.\nFor files marked not_present_before_upload, remove the new version if rolling back.\nThis ZIP contains no database backup. It may contain a private API token; keep it private.\nThe backup tool does not restore files automatically or change application files.\n";
            if (!$zip->addFromString('MANIFEST.json', json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)) || !$zip->addFromString('RESTORE.txt', $instructions)) throw new RuntimeException('Unable to add backup verification information.');
        } catch (Throwable $failure) {
            $zip->close();
            throw $failure;
        }
        if (!$zip->close()) throw new RuntimeException('Unable to finish the backup archive.');
        $size = filesize($tempPath);
        if ($size === false || $size < 1) throw new RuntimeException('The backup archive could not be verified.');
        session_write_close();
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="whittles-feed-before-upload-' . gmdate('Ymd-His') . '.zip"');
        header('Content-Length: ' . $size);
        header('Cache-Control: no-store, private');
        header('Pragma: no-cache');
        readfile($tempPath);
        unlink($tempPath);
        exit;
    } catch (Throwable $failure) {
        if ($tempPath !== false && is_file($tempPath)) unlink($tempPath);
        http_response_code(500);
        $error = $failure instanceof RuntimeException ? $failure->getMessage() : 'The backup could not be created. Check the PHP ZIP extension and private folder permissions.';
    }
}
?>
<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Whittle feed backup</title>
<style>body{font:16px/1.6 system-ui,sans-serif;background:#f4f6fa;color:#172b4d;margin:0;padding:24px}main{max-width:720px;margin:32px auto;background:#fff;padding:28px;border-radius:12px}li{overflow-wrap:anywhere}button{padding:12px 20px;border:0;border-radius:7px;background:#1959a6;color:#fff;font:inherit;cursor:pointer}.error{color:#a51c30}</style></head><body><main>
<h1>Back up the ticket and overview upload files</h1>
<p>Download the existing versions before uploading the ticket layout and overview update.</p>
<?php if ($error !== ''): ?><p class="error" role="alert"><?= e($error) ?></p><?php endif; ?>
<ul><?php foreach ($backupFiles as $file): ?><li><code><?= e($file) ?></code> — <?= is_file($backupRoot . '/' . $file) ? 'existing file will be backed up' : 'not present yet; recorded in the manifest' ?></li><?php endforeach; ?></ul>
<p>The ZIP includes a manifest and restore instructions. New files that do not exist yet are recorded so you can remove them when rolling back.</p>
<p>Keep the downloaded ZIP private: it may include your API token. Open it to confirm the backup, then remove this PHP tool from hosting before uploading the reader.</p>
<form method="post"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="download"><button type="submit">Download backup ZIP</button></form>
</main></body></html>
