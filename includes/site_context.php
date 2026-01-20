<?php
if (!isset($_SESSION['user_id'])) {
    return;
}

$userId = $_SESSION['user_id'];
$role   = $_SESSION['role'];

if ($role === 'ADMIN') {

    $stmt = $pdo->prepare("
        SELECT id, name
        FROM sites
        ORDER BY created_at ASC, id ASC
    ");
    $stmt->execute();
    $sites = $stmt->fetchAll();

} elseif ($role === 'OPERATOR') {

    $stmt = $pdo->prepare("
        SELECT id, name
        FROM sites
        WHERE operator_id = ?
        ORDER BY created_at ASC, id ASC
    ");
    $stmt->execute([$userId]);
    $sites = $stmt->fetchAll();

} elseif ($role === 'STAFF') {

    $stmt = $pdo->prepare("
        SELECT s.id, s.name
        FROM sites s
        JOIN site_staff ss ON ss.site_id = s.id
        WHERE ss.user_id = ?
        ORDER BY s.created_at ASC, s.id ASC
    ");
    $stmt->execute([$userId]);
    $sites = $stmt->fetchAll();

} else {
    $sites = [];
}

if (empty($_SESSION['site_id']) && !empty($sites)) {
    $_SESSION['site_id'] = $sites[0]['id'];
}

$currentSiteId = $_SESSION['site_id'] ?? null;

if (!in_array($_SESSION['role'], ['ADMIN', 'OPERATOR', 'STAFF'])) {
    http_response_code(403);
    exit('권한 없음');
}

$stmt = $pdo->prepare("
  SELECT 
    is_enabled,
    service_start,
    service_end,
    max_contract_count,
    used_contract_count
  FROM site_service
  WHERE site_id = :site_id
  LIMIT 1
");
$stmt->execute([':site_id' => $currentSiteId]);

$siteService = $stmt->fetch(PDO::FETCH_ASSOC);

$siteServiceEnabled = (int)($siteService['is_enabled'] ?? 0);