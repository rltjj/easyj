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
        'message' => '카테고리 추가 권한이 없습니다.'
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

$input = json_decode(file_get_contents('php://input'), true);
$name = trim($input['name'] ?? '');

if ($name === '') {
    echo json_encode([
        'success' => false,
        'message' => '카테고리명을 입력해주세요.'
    ]);
    exit;
}

$stmt = $pdo->prepare("
    SELECT id
    FROM template_categories
    WHERE site_id = :site_id
      AND name = :name
      AND is_deleted = 0
");
$stmt->execute([
    ':site_id' => $currentSiteId,
    ':name'    => $name
]);

if ($stmt->fetch()) {
    echo json_encode([
        'success' => false,
        'message' => '이미 존재하는 카테고리입니다.'
    ]);
    exit;
}

$stmt = $pdo->prepare("
    SELECT COALESCE(MAX(sort_order), 0) + 1
    FROM template_categories
    WHERE site_id = :site_id
");
$stmt->execute([':site_id' => $currentSiteId]);
$sortOrder = (int)$stmt->fetchColumn();

$stmt = $pdo->prepare("
    INSERT INTO template_categories (
        site_id,
        name,
        sort_order,
        is_deleted
    ) VALUES (
        :site_id,
        :name,
        :sort_order,
        0
    )
");

$stmt->execute([
    ':site_id'    => $currentSiteId,
    ':name'       => $name,
    ':sort_order' => $sortOrder
]);

$categoryId = $pdo->lastInsertId();

echo json_encode([
    'success' => true,
    'id'      => $categoryId,
    'name'    => $name
]);