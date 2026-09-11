<?php
require __DIR__.'/app/bootstrap.php';require_permission('view_staff');
$photo=query('SELECT image_data FROM staff_photos WHERE staff_id=?',[positive_id($_GET,'id')])->fetchColumn();
if($photo===false){http_response_code(404);exit('Photo not found.');}
header('Content-Type: image/jpeg');header('Content-Length: '.strlen($photo));echo $photo;
