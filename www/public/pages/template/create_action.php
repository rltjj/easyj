<?php
require_once __DIR__ . '/../../../../bootstrap.php';
require_once BASE_PATH . '/includes/site_context.php';

if ($_SESSION['role'] !== 'ADMIN') {
    die('권한 없음');
}

if (!$siteId) {
    echo json_encode([
        'success' => false,
        'message' => '현장 정보가 없습니다.'
    ]);
    exit;
}

$title = trim($_POST['title'] ?? '');
$categoryId = $_POST['category_id'] ?? null;

if ($title === '' || !$categoryId) {
    die('필수값 누락');
}

$stmt = $pdo->prepare("
    SELECT id
    FROM template_categories
    WHERE id = :id
      AND site_id = :site_id
      AND is_deleted = 0
");
$stmt->execute([
    ':id'      => $categoryId,
    ':site_id' => $siteId
]);

if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
    die('유효하지 않은 카테고리');
}

if (!isset($_FILES['pdf']) || $_FILES['pdf']['error'] !== UPLOAD_ERR_OK) {
    die('파일 업로드 실패');
}

$uploadDir = __DIR__ . '/easyj/storage/templates/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

$filename = uniqid('tpl_', true) . '.pdf';
$filepath = $uploadDir . $filename;

move_uploaded_file($_FILES['pdf']['tmp_name'], $filepath);



$stmt = $pdo->prepare("
    INSERT INTO templates (
        site_id,
        title,
        category_id,
        pdf_path,
        created_at,
        staff_sign_mode
    ) VALUES (
        :site_id,
        :title,
        :category_id,
        :pdf_path,
        NOW(),
        :staff_sign_enabled
    )
");

$stmt->execute([
    ':site_id'     => $siteId,
    ':title'       => $title,
    ':category_id' => $categoryId,
    ':pdf_path'    => '/uploads/templates/' . $filename,
    ':staff_sign_enabled' => $staffSignEnabled
]);

header('Location: index.php');
exit;
