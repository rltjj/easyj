<?php
define('BASE_PATH', dirname(__DIR__, 3));
require_once BASE_PATH . '/bootstrap.php';

$path = $_GET['path'] ?? '';
$file = BASE_PATH . '/api/' . $path . '.php';

if (!file_exists($file)) {
    http_response_code(404);
    echo json_encode([
        'success' => false,
        'message' => 'API not found'
    ]);
    exit;
}

require $file;
