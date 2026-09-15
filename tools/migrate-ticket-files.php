<?php
// CLI only, safe to rerun against the isolated Wheettle database.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require dirname(__DIR__).'/app/bootstrap.php';
if (query('SELECT DATABASE()')->fetchColumn() !== 'wheettle_ticketing') throw new RuntimeException('Expected wheettle_ticketing database.');
if (!query("SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='ticket_attachments' AND column_name='file_data'")->fetchColumn()) {
    db()->exec(file_get_contents(dirname(__DIR__).'/database/002_ticket_files.sql'));
}
echo "Ticket attachment migration ready. Existing records preserved.\n";
