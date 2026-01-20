<?php
require_once __DIR__ . '/../../../../bootstrap.php';

if (!in_array($_SESSION['role'], ['ADMIN', 'OPERATOR'])) {
  die('권한 없음');
}

$siteId = intval($_POST['site_id'] ?? 0);
if (!$siteId) {
  die('현장 정보 없음');
}

$siteName          = trim($_POST['site_name'] ?? '');

$companyName       = trim($_POST['company_name'] ?? '');
$companyPhone      = trim($_POST['company_phone'] ?? '');
$officeAddress     = trim($_POST['office_address'] ?? '');
$modelhouseAddress = trim($_POST['modelhouse_address'] ?? '');

$agencyName        = trim($_POST['agency_name'] ?? '');
$managerPhone      = trim($_POST['manager_phone'] ?? '');

try {
  $pdo->beginTransaction();

  $stmt = $pdo->prepare("
    UPDATE sites
    SET name = :name
    WHERE id = :id
  ");
  $stmt->execute([
    'name' => $siteName,
    'id'   => $siteId
  ]);

  $stmt = $pdo->prepare("
    UPDATE operator_company
    SET
        company_name       = :company_name,
        company_phone      = :company_phone,
        office_address     = :office_address,
        modelhouse_address = :modelhouse_address
    WHERE site_id = :site_id
    ");
    $stmt->execute([
    'site_id'            => $siteId,
    'company_name'       => $companyName,
    'company_phone'      => $companyPhone,
    'office_address'     => $officeAddress,
    'modelhouse_address' => $modelhouseAddress
    ]);

  $stmt = $pdo->prepare("
    UPDATE operator_agency
    SET
        agency_name   = :agency_name,
        manager_phone = :manager_phone
    WHERE site_id = :site_id
    ");
    $stmt->execute([
    'site_id'       => $siteId,
    'agency_name'   => $agencyName,
    'manager_phone' => $managerPhone
    ]);

  $pdo->commit();

  header('Location: index.php?tab=site&saved=1');
  exit;

} catch (Exception $e) {
  $pdo->rollBack();
  die('현장 정보 저장 중 오류 발생');
}
