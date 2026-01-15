<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../bootstrap.php';

$email = trim($_POST['email'] ?? '');

if (!$email) {
    echo json_encode(['available' => false]);
    exit;
}

$stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
$stmt->execute([$email]);

echo json_encode([
    'available' => !$stmt->fetch()
]);
