<?php
require_once __DIR__ . '/../../bootstrap.php';
require_once BASE_PATH . '/includes/site_context.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => '로그인이 필요합니다.'
    ]);
    exit;
}

if (!in_array($_SESSION['role'], ['ADMIN', 'OPERATOR'])) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => '즐겨찾기 권한이 없습니다.'
    ]);
    exit;
}

if (!$currentSiteId) {
    echo json_encode([
        'success' => false,
        'message' => '현장 정보가 없습니다.'
    ]);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$ids  = $data['ids'] ?? [];

if (!is_array($ids) || empty($ids)) {
  echo json_encode(['success' => false, 'message' => 'ids가 없습니다.']);
  exit;
}

$placeholders = implode(',', array_fill(0, count($ids), '?'));

$sql = "
  UPDATE templates
  SET is_favorite = IF(is_favorite = 1, 0, 1)
  WHERE id IN ($placeholders)
";

$stmt = $pdo->prepare($sql);
$stmt->execute($ids);

echo json_encode(['success' => true]);
