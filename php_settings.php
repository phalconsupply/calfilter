<?php
header('Content-Type: application/json; charset=utf-8');

$settings = [
    'upload_max_filesize' => ini_get('upload_max_filesize'),
    'post_max_size' => ini_get('post_max_size'),
    'memory_limit' => ini_get('memory_limit'),
    'max_execution_time' => ini_get('max_execution_time'),
    'file_uploads' => ini_get('file_uploads'),
    'temp_dir' => sys_get_temp_dir(),
    'temp_writable' => is_writable(sys_get_temp_dir()),
    'script_dir' => __DIR__,
    'script_dir_writable' => is_writable(__DIR__),
    'display_errors' => ini_get('display_errors'),
    'error_reporting' => error_reporting(),
    'loaded_extensions' => get_loaded_extensions()
];

echo json_encode($settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
