<?php
require_once __DIR__ . '/../../bootstrap.php';
require_once BASE_PATH . '/includes/site_context.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'ADMIN') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => '권한 없음']);
    exit;
}

if (!$currentSiteId) {
    echo json_encode(['success' => false, 'message' => '현장 정보 없음']);
    exit;
}

$title       = trim($_POST['title'] ?? '');
$categoryId  = intval($_POST['category_id'] ?? 0);
$signers     = $_POST['signers'] ?? [];

if ($title === '' || !$categoryId) {
    echo json_encode(['success' => false, 'message' => '필수값 누락']);
    exit;
}

if (!isset($_FILES['pdf']) || $_FILES['pdf']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'PDF 업로드 실패']);
    exit;
}


$stmt = $pdo->prepare("
    SELECT id FROM template_categories
    WHERE id = :id AND site_id = :site_id AND is_deleted = 0
");
$stmt->execute([
    ':id' => $categoryId,
    ':site_id' => $currentSiteId
]);

if (!$stmt->fetch()) {
    echo json_encode(['success' => false, 'message' => '유효하지 않은 카테고리']);
    exit;
}

$uploadDir = BASE_PATH . '/storage/templates/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

$pdfName = uniqid('tpl_', true) . '.pdf';
$pdfPath = $uploadDir . $pdfName;

if (!move_uploaded_file($_FILES['pdf']['tmp_name'], $pdfPath)) {
    echo json_encode(['success' => false, 'message' => 'PDF 저장 실패']);
    exit;
}

$pdfDbPath = '/storage/templates/' . $pdfName;

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("
        INSERT INTO templates (
            site_id,
            category_id,
            title,
            file_path,
            created_at
        ) VALUES (
            :site_id,
            :category_id,
            :title,
            :file_path,
            NOW()
        )
    ");
    $stmt->execute([
        ':site_id' => $currentSiteId,
        ':category_id' => $categoryId,
        ':title' => $title,
        ':file_path' => $pdfDbPath
    ]);

    $templateId = $pdo->lastInsertId();

    $signerOrder = 1;

    foreach ($signers as $signer) {

        $role = $signer['role'] ?? null;
        $companyName = $signer['company_name'] ?? null;

        if (!in_array($role, ['SITE', 'GUEST'])) {
            throw new Exception('잘못된 서명자 역할');
        }

        $stmt = $pdo->prepare("
            INSERT INTO template_signers (
                template_id,
                signer_order,
                signer_role,
                company_name
            ) VALUES (
                :template_id,
                :signer_order,
                :signer_role,
                :company_name
            )
        ");
        $stmt->execute([
            ':template_id' => $templateId,
            ':signer_order' => $signerOrder,
            ':signer_role' => $role,
            ':company_name' => $companyName
        ]);

        if (!empty($signer['attachments'])) {
            foreach ($signer['attachments'] as $att) {

                $attTitle = trim($att['title'] ?? '');
                if ($attTitle === '') continue;

                $stmt = $pdo->prepare("
                    INSERT INTO template_attachments (
                        template_id,
                        signer_order,
                        title,
                        description,
                        required
                    ) VALUES (
                        :template_id,
                        :signer_order,
                        :title,
                        :description,
                        :required
                    )
                ");
                $stmt->execute([
                    ':template_id' => $templateId,
                    ':signer_order' => $signerOrder,
                    ':title' => $attTitle,
                    ':description' => $att['description'] ?? '',
                    ':required' => !empty($att['required']) ? 1 : 0
                ]);
            }
        }

        $signerOrder++;
    }

    $pdo->commit();

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
