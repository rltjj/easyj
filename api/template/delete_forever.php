<?php
require_once __DIR__ . '/../../bootstrap.php';
require_once BASE_PATH . '/includes/site_context.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SESSION['role'] !== 'ADMIN') {
  http_response_code(403);
  echo json_encode(['success' => false, 'message' => '권한 없음']);
  exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$ids = $data['ids'] ?? [];

if (!is_array($ids) || empty($ids)) {
  echo json_encode(['success' => false, 'message' => '삭제할 ID 없음']);
  exit;
}

$ids = array_values(array_filter($ids, fn($v) => is_numeric($v)));
if (empty($ids)) {
  echo json_encode(['success' => false, 'message' => '유효한 ID 없음']);
  exit;
}

$placeholders = implode(',', array_fill(0, count($ids), '?'));

try {
  $pdo->beginTransaction();

  $stmt = $pdo->prepare("
    DELETE FROM template_signers
    WHERE template_id IN ($placeholders)
  ");
  $stmt->execute($ids);

  $stmt = $pdo->prepare("
    DELETE FROM template_attachments
    WHERE template_id IN ($placeholders)
  ");
  $stmt->execute($ids);

  $stmt = $pdo->prepare("
    DELETE FROM template_fields
    WHERE template_id IN ($placeholders)
  ");
  $stmt->execute($ids);

  $stmt = $pdo->prepare("
    DELETE FROM templates
    WHERE id IN ($placeholders)
      AND site_id = ?
      AND is_deleted = 1
  ");
  $stmt->execute([...$ids, $currentSiteId]);

  $pdo->commit();

  echo json_encode(['success' => true]);

} catch (Exception $e) {
  $pdo->rollBack();
  http_response_code(500);
  echo json_encode([
    'success' => false,
    'error' => $e->getMessage()
  ]);
}
