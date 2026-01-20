<?php
require_once __DIR__ . '/../../../../bootstrap.php';

if ($_SESSION['role'] !== 'ADMIN') {
  die('권한 없음');
}

$siteId = intval($_POST['site_id']);
$serviceStart = $_POST['service_start'];
$serviceEnd   = $_POST['service_end'];
$isEnabled    = intval($_POST['is_enabled']);
$maxCount = intval($_POST['max_contract_count']);

if ($maxCount < $service['used_contract_count']) {
  die('이미 사용한 계약 수보다 작게 설정할 수 없습니다.');
}

$stmt = $pdo->prepare("
  UPDATE site_service
  SET
    service_start = :start,
    service_end   = :end,
    is_enabled    = :enabled,
    max_contract_count = :max_count
  WHERE site_id = :site_id
");

$stmt->execute([
  'start'     => $serviceStart,
  'end'       => $serviceEnd,
  'enabled'   => $isEnabled,
  'max_count' => $maxCount,
  'site_id'   => $siteId
]);

header('Location: index.php?tab=billing');
exit;
