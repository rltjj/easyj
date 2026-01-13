<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../config/database.php';

$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if (!$email || !$password) {
    echo json_encode([
        'success' => false,
        'message' => '이메일과 비밀번호를 입력해주세요.'
    ]);
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND status = 'ACTIVE'");
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user || !password_verify($password, $user['password'])) {
    echo json_encode([
        'success' => false,
        'message' => '이메일 또는 비밀번호가 올바르지 않습니다.'
    ]);
    exit;
}

$_SESSION['user_id'] = $user['id'];
$_SESSION['role'] = $user['role'];
$_SESSION['name'] = $user['name'];
$_SESSION['email']   = $user['email'];

echo json_encode([
    'success' => true,
    'message' => '로그인 성공'
]);
exit;
