<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$values = [
    'lab' => 'php2shell-private-loopback-lab',
    'variant' => getenv('LAB_VARIANT') ?: 'unknown',
    'php_version' => PHP_VERSION,
    'php_version_id' => PHP_VERSION_ID,
    'sapi' => PHP_SAPI,
    'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'unknown',
    'memory_limit' => ini_get('memory_limit'),
    'post_max_size' => ini_get('post_max_size'),
    'upload_max_filesize' => ini_get('upload_max_filesize'),
    'max_input_vars' => ini_get('max_input_vars'),
    'session_save_handler' => ini_get('session.save_handler'),
    'session_save_path' => ini_get('session.save_path'),
    'session_upload_progress_enabled' => ini_get('session.upload_progress.enabled'),
    'session_upload_progress_cleanup' => ini_get('session.upload_progress.cleanup'),
];

echo json_encode($values, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), "\n";
