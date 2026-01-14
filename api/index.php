<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../bootstrap.php';

$uri  = $_SERVER['REQUEST_URI']; 
$path = parse_url($uri, PHP_URL_PATH);

$path = str_replace('/easyj/api', '', $path);
$path = trim($path, '/'); 

if ($path === '') {
    http_response_code(404);
    echo json_encode(['message' => 'API Not Found']);
    exit;
}

$segments = explode('/', $path);

$folder = $segments[0] ?? null;
$file   = $segments[1] ?? null;

$target = __DIR__ . "/{$folder}/{$file}.php";

if (!file_exists($target)) {
    http_response_code(404);
    echo json_encode(['message' => 'Invalid API']);
    exit;
}

require $target;
