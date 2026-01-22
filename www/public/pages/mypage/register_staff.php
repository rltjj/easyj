<?php
require_once __DIR__ . '/../../../../bootstrap.php';
header('Content-Type: application/json');

if (!in_array($_SESSION['role'], ['ADMIN','OPERATOR'])) {
  echo json_encode(['success' => false, 'message' => '권한이 없습니다.']);
  exit;
}

$siteId = intval($_POST['site_id']);
$email  = trim($_POST['email']);
$name   = trim($_POST['name']);
$phone  = trim($_POST['phone']);

$stmt = $pdo->prepare("
  SELECT id, role
  FROM users
  WHERE email = :email
    AND name = :name
    AND phone = :phone
  LIMIT 1
");
$stmt->execute(compact('email','name','phone'));
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
  echo json_encode(['success' => false, 'message' => '일치하는 직원이 없습니다.']);
  exit;
}

if ($user['role'] !== 'STAFF') {
  echo json_encode(['success' => false, 'message' => '직원(STAFF)만 등록할 수 있습니다.']);
  exit;
}

$stmt = $pdo->prepare("
  SELECT COUNT(*) FROM site_staff
  WHERE site_id = :site_id AND user_id = :user_id
");
$stmt->execute([
  'site_id' => $siteId,
  'user_id' => $user['id']
]);

if ($stmt->fetchColumn() > 0) {
  echo json_encode(['success' => false, 'message' => '이미 등록된 직원입니다.']);
  exit;
}

$stmt = $pdo->prepare("
  SELECT site_id
  FROM site_staff
  WHERE user_id = :user_id
  LIMIT 1
");
$stmt->execute(['user_id' => $user['id']]);
$existingSiteId = $stmt->fetchColumn();

if ($existingSiteId && $existingSiteId != $siteId) {
  echo json_encode([
    'success' => false,
    'message' => '해당 직원은 이미 다른 현장에 소속되어 있습니다.'
  ]);
  exit;
}

$stmt = $pdo->prepare("
  INSERT INTO site_staff (site_id, user_id, joined_at)
  VALUES (:site_id, :user_id, NOW())
");
$stmt->execute([
  'site_id' => $siteId,
  'user_id' => $user['id']
]);

echo json_encode([
  'success' => true,
  'message' => '직원이 성공적으로 등록되었습니다.'
]);
exit;
