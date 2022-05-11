<?php

$file_name = $_GET['pdf'];
$file_url = 'https://' . $_SERVER['HTTP_HOST'] . '/pdf/' . $file_name;

header('Content-Description: File Transfer');
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="'.basename($file_url).'"');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . filesize($file_url));

// Flush system output buffer
flush();
readfile($file_url);
die();

readfile($file_url);
exit;