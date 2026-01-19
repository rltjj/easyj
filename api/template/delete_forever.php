<?php
require_once __DIR__ . '/../../bootstrap.php';
require_once BASE_PATH . '/includes/site_context.php';

if (!in_array($_SESSION['role'], ['ADMIN'])) {
  http_response_code(403);
  exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$ids = $data['ids'] ?? [];

if (empty($ids)) {
  echo json_encode(['success' => false]);
  exit;
}

$placeholders = implode(',', array_fill(0, count($ids), '?'));

$sql = "
  DELETE FROM templates
  WHERE id IN ($placeholders)
    AND site_id = ?
    AND is_deleted = 1
";

$stmt = $pdo->prepare($sql);
$result = $stmt->execute([...$ids, $currentSiteId]);

echo json_encode(['success' => $result]);