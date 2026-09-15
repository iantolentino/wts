<?php
require __DIR__.'/app/bootstrap.php';
$user=require_permission('view_all_tickets');$id=positive_id($_GET,'id');
$file=query('SELECT a.* FROM ticket_attachments a JOIN tickets t ON t.id=a.ticket_id WHERE a.id=? AND t.deleted_at IS NULL AND a.file_data IS NOT NULL',[$id])->fetch();
if (!$file) {http_response_code(404);exit('Attachment not found.');}
require_ticket_visible($user,(int)$file['ticket_id']);
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="attachment"; filename*=UTF-8\'\''.rawurlencode($file['original_name']));
header('Content-Length: '.strlen($file['file_data']));
header("Content-Security-Policy: sandbox");
echo $file['file_data'];
