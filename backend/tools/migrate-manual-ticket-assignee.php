<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require dirname(__DIR__).'/app/bootstrap.php';
if (!query("SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='tickets' AND column_name='manual_assignee_name'")->fetchColumn()) {
    db()->exec(file_get_contents(dirname(__DIR__, 2).'/database/004_manual_ticket_assignee.sql'));
}
echo "Manual ticket assignee migration ready. Existing records preserved.\n";
