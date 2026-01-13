<?php
function env($key) {
    static $env = null;
    if ($env === null) {
        $env = parse_ini_file(__DIR__ . '/../.env');
    }
    return $env[$key] ?? null;
}

try {
    $pdo = new PDO(
        "mysql:host=" . env('DB_HOST') . ";dbname=" . env('DB_NAME') . ";charset=utf8mb4",
        env('DB_USER'),
        env('DB_PASS'),
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'DB 연결 실패']);
    exit;
}
