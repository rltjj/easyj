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
          AND is_deleted = 0
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
          AND s.is_deleted = 0
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
