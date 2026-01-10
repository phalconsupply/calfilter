<?php
header('Content-Type: application/json; charset=utf-8');

$checks = [
    'php_version' => PHP_VERSION,
    'php_version_ok' => version_compare(PHP_VERSION, '7.2.0', '>='),
    'config_exists' => file_exists(__DIR__ . '/config.php'),
    'vendor_exists' => file_exists(__DIR__ . '/PhpSpreadsheet/vendor/autoload.php'),
    'extensions' => []
];

// Check required extensions
$required_extensions = ['zip', 'xml', 'gd', 'mbstring'];
foreach ($required_extensions as $ext) {
    $checks['extensions'][$ext] = extension_loaded($ext);
}

// Try to load PhpSpreadsheet
try {
    if ($checks['vendor_exists']) {
        require_once __DIR__ . '/PhpSpreadsheet/vendor/autoload.php';
        $checks['phpspreadsheet_loaded'] = class_exists('\PhpOffice\PhpSpreadsheet\Spreadsheet');
    } else {
        $checks['phpspreadsheet_loaded'] = false;
        $checks['error'] = 'Vendor folder not found';
    }
} catch (Exception $e) {
    $checks['phpspreadsheet_loaded'] = false;
    $checks['error'] = $e->getMessage();
}

// Try to connect to database
try {
    if ($checks['config_exists']) {
        require_once __DIR__ . '/config.php';
        $conn = getDbConnection();
        $checks['database_connected'] = $conn->ping();
        $conn->close();
    } else {
        $checks['database_connected'] = false;
        $checks['db_error'] = 'Config file not found';
    }
} catch (Exception $e) {
    $checks['database_connected'] = false;
    $checks['db_error'] = $e->getMessage();
}

echo json_encode($checks, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
