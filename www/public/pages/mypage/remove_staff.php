<?php
require_once __DIR__ . '/../../../../bootstrap.php';

if (!in_array($_SESSION['role'], ['ADMIN','OPERATOR'])) {
  die('권한 없음');
}

$siteId = intval($_POST['site_id']);
$userId = intval($_POST['user_id']);

if ($userId === $_SESSION['user_id']) {
  die('자기 자신은 제거할 수 없습니다.');
}

$stmt = $pdo->prepare("
  SELECT role FROM users WHERE id = :id
");
$stmt->execute(['id' => $userId]);
$role = $stmt->fetchColumn();

if ($role !== 'STAFF') {
  die('직원만 제거할 수 있습니다.');
}

$stmt = $pdo->prepare("
  SELECT COUNT(*)
  FROM contracts c
  JOIN contract_signers cs ON cs.contract_id = c.id
  WHERE c.site_id = :site_id
    AND c.status = 'PROGRESS'
    AND cs.user_id = :user_id
    AND cs.signer_type = 'SITE'
");
$stmt->execute([
  'site_id' => $siteId,
  'user_id' => $userId
]);

if ($stmt->fetchColumn() > 0) {
  die('진행 중인 계약에 참여 중인 직원은 제거할 수 없습니다.');
}

$stmt = $pdo->prepare("
  DELETE FROM site_staff
  WHERE site_id = :site_id
    AND user_id = :user_id
");
$stmt->execute([
  'site_id' => $siteId,
  'user_id' => $userId
]);

header('Location: index.php?tab-team');
exit;