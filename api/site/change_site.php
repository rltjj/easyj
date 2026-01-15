<?php
require_once __DIR__ . '/../../bootstrap.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: /easyj/public/auth/login.html');
    exit;
}

$userId = $_SESSION['user_id'];
$role   = $_SESSION['role'];
$siteId = (int)($_POST['site_id'] ?? 0);

if (!$siteId) {
    header('Location: /easyj/public/pages/contract/index.php');
    exit;
}

$allowed = false;

if ($role === 'ADMIN') {

    $allowed = true;

} elseif ($role === 'OPERATOR') {

    $stmt = $pdo->prepare("
        SELECT id FROM sites
        WHERE id = ? AND operator_id = ?
    ");
    $stmt->execute([$siteId, $userId]);
    $allowed = (bool)$stmt->fetch();

} elseif ($role === 'STAFF') {

    $stmt = $pdo->prepare("
        SELECT site_id FROM site_staff
        WHERE site_id = ? AND user_id = ?
    ");
    $stmt->execute([$siteId, $userId]);
    $allowed = (bool)$stmt->fetch();
}

if (!$allowed) {
    die('접근 권한이 없습니다.');
}

$_SESSION['site_id'] = $siteId;

header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '/easyj/public/pages/contract/index.php'));
exit;