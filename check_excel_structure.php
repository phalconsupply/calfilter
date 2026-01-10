<?php
header('Content-Type: application/json; charset=utf-8');

if (!isset($_FILES['file'])) {
    echo json_encode(['success' => false, 'message' => 'Không có file được upload']);
    exit;
}

$file = $_FILES['file'];

if ($file['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'Lỗi upload file']);
    exit;
}

// Check if PhpSpreadsheet is available
if (!file_exists(__DIR__ . '/PhpSpreadsheet/vendor/autoload.php')) {
    echo json_encode(['success' => false, 'message' => 'PhpSpreadsheet chưa được cài đặt']);
    exit;
}

require_once __DIR__ . '/PhpSpreadsheet/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

try {
    $spreadsheet = IOFactory::load($file['tmp_name']);
    $worksheet = $spreadsheet->getActiveSheet();
    $rows = $worksheet->toArray();
    
    if (count($rows) < 1) {
        echo json_encode(['success' => false, 'message' => 'File không có dữ liệu']);
        exit;
    }
    
    $header = $rows[0];
    $dataRows = array_slice($rows, 1, 5); // Get first 5 data rows
    $columns = count($header);
    
    echo json_encode([
        'success' => true,
        'total_rows' => count($rows),
        'columns' => $columns,
        'header' => $header,
        'sample_rows' => $dataRows
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi đọc file: ' . $e->getMessage()
    ]);
}
