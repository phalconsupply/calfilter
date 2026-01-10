<?php
// Kiểm tra phiên bản file hospital_import.php trên host
header('Content-Type: application/json');

$file = 'hospital_import.php';
$content = file_get_contents($file);

// Tìm dòng fetch trong file
preg_match("/fetch\('([^']+)',/", $content, $matches);

echo json_encode([
    'file_exists' => file_exists($file),
    'file_modified' => date('Y-m-d H:i:s', filemtime($file)),
    'fetch_url' => $matches[1] ?? 'NOT FOUND',
    'file_size' => filesize($file),
    'contains_handler' => strpos($content, 'hospital_import_handler.php') !== false
]);
