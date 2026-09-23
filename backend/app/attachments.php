<?php
declare(strict_types=1);

function ticket_uploads(): array {
    $files = $_FILES['attachments'] ?? null;
    if (!$files) return [];
    if (!isset($files['error']) || !is_array($files['error']) || count($files['error']) > 3) throw new InvalidArgumentException('Attach up to 3 files.');
    $types = ['pdf'=>['application/pdf'],'txt'=>['text/plain'],'csv'=>['text/plain','text/csv','application/csv'],
        'jpg'=>['image/jpeg'],'jpeg'=>['image/jpeg'],'png'=>['image/png'],'webp'=>['image/webp'],
        'docx'=>['application/zip','application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
        'xlsx'=>['application/zip','application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']];
    $uploads=[];
    foreach ($files['error'] as $i=>$error) {
        if ($error === UPLOAD_ERR_NO_FILE) continue;
        if ($error !== UPLOAD_ERR_OK) throw new InvalidArgumentException('Upload failed. Each attachment must be at most 2 MB. Select the files again.');
        $tmp=$files['tmp_name'][$i]??null;$name=$files['name'][$i]??null;
        if (!is_string($tmp) || !is_string($name) || !is_uploaded_file($tmp)) throw new InvalidArgumentException('Invalid attachment upload.');
        $name=basename(str_replace('\\','/',$name));
        if ($name==='' || strlen($name)>200 || preg_match('/[\x00-\x1f\x7f]/',$name)) throw new InvalidArgumentException('Use an attachment filename of 1 to 200 bytes without control characters.');
        $size=filesize($tmp);if (!$size || $size>2*1024*1024) throw new InvalidArgumentException('Each attachment must be non-empty and at most 2 MB.');
        $ext=strtolower(pathinfo($name,PATHINFO_EXTENSION));$mime=(new finfo(FILEINFO_MIME_TYPE))->file($tmp);
        if (!isset($types[$ext]) || !in_array($mime,$types[$ext],true)) throw new InvalidArgumentException('Supported attachments: PDF, TXT, CSV, JPG, PNG, WebP, DOCX and XLSX. File contents must match the extension.');
        if (in_array($ext,['docx','xlsx'],true)) {
            $zip=new ZipArchive();if ($zip->open($tmp)!==true) throw new InvalidArgumentException('Invalid Office document.');
            try {
                $main=$ext==='docx'?'word/document.xml':'xl/workbook.xml';
                if ($zip->locateName('[Content_Types].xml')===false || $zip->locateName($main)===false) throw new InvalidArgumentException('Invalid Office document.');
                for($n=0;$n<$zip->numFiles;$n++) if(stripos($zip->getNameIndex($n),'vbaProject')!==false) throw new InvalidArgumentException('Macro-enabled documents are not supported.');
            } finally {$zip->close();}
        }
        $uploads[]=['name'=>$name,'mime'=>$mime,'size'=>$size,'data'=>file_get_contents($tmp)];
    }
    return $uploads;
}

function save_ticket_uploads(int $ticketId, int $actorId, array $uploads): void {
    foreach ($uploads as $file) {
        $stored=bin2hex(random_bytes(16));
        query('INSERT INTO ticket_attachments(ticket_id,uploaded_by,file_name,file_path,stored_name,original_name,mime_type,file_size,access_permission_key,file_data) VALUES(?,?,?,?,?,?,?,?,?,?)',
            [$ticketId,$actorId,$file['name'],'database:'.$stored,$stored,$file['name'],$file['mime'],$file['size'],'view_all_tickets',$file['data']]);
        activity($ticketId,$actorId,'attachment_added',['filename'=>$file['name'],'bytes'=>$file['size']]);
    }
}
