<?php
// Absolutely no output before this
ob_start();
header('Content-Type: application/json; charset=utf-8');

try {
    // Simple response
    $response = [
        'success' => true,
        'message' => 'Handler is working',
        'method' => $_SERVER['REQUEST_METHOD'],
        'has_file' => isset($_FILES['file']),
        'php_version' => PHP_VERSION,
        'working_dir' => getcwd(),
        'script_path' => __DIR__
    ];
    
    ob_end_clean();
    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    ob_end_clean();
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
