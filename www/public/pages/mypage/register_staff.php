<?php
require_once __DIR__ . '/../../../../bootstrap.php';

if (!in_array($_SESSION['role'], ['ADMIN','OPERATOR'])) {
  die('권한 없음');
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
  die('일치하는 직원이 없습니다.');
}

if ($user['role'] !== 'STAFF') {
  die('직원 권한을 가진 사용자만 등록할 수 있습니다.');
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
  die('이미 등록된 직원입니다.');
}

$stmt = $pdo->prepare("
  SELECT site_id
  FROM site_staff
  WHERE user_id = :user_id
  LIMIT 1
");
$stmt->execute([
  'user_id' => $user['id']
]);
$existingSiteId = $stmt->fetchColumn();

if ($existingSiteId) {
  if ($existingSiteId == $siteId) {
    die('이미 해당 현장에 등록된 직원입니다.');
  } else {
    die('해당 직원은 이미 다른 현장에 소속되어 있습니다.');
  }
}

$stmt = $pdo->prepare("
  INSERT INTO site_staff (site_id, user_id, joined_at)
  VALUES (:site_id, :user_id, NOW())
");
$stmt->execute([
  'site_id' => $siteId,
  'user_id' => $user['id']
]);

header('Location: index.php?tab-team');
exit;
