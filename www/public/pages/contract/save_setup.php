<?php
require_once __DIR__ . '/../../../../bootstrap.php';
require_once BASE_PATH . '/includes/site_context.php';

if (!isset($_SESSION['user_id'])) {
    die('로그인이 필요합니다.');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die('잘못된 접근');
}

$loginUserId = (int)$_SESSION['user_id'];
$siteId      = (int)$_SESSION['site_id'];

$templateId = (int)($_POST['template_id'] ?? 0);
$title      = trim($_POST['contract_title'] ?? '');
$complex    = trim($_POST['contract_complex'] ?? '');
$building   = trim($_POST['contract_building'] ?? '');
$unit       = trim($_POST['contract_unit'] ?? '');
$signers    = $_POST['signers'] ?? [];

if (!$templateId || !$title || empty($signers)) {
    die('필수 정보 누락');
}

$stmt = $pdo->prepare("
    SELECT t.file_path, t.category_id, c.name AS category_name, c.sort_order
    FROM templates t
    JOIN template_categories c ON c.id = t.category_id
    WHERE t.id = :id
      AND t.site_id = :site_id
      AND c.is_deleted = 0
");
$stmt->execute([
    ':id'      => $templateId,
    ':site_id' => $siteId
]);

$template = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$template) {
    die('템플릿 정보 없음');
}

$stmt = $pdo->prepare("
    SELECT id
    FROM contract_categories
    WHERE site_id = :site_id
      AND name = :name
");
$stmt->execute([
    ':site_id' => $siteId,
    ':name'    => $template['category_name']
]);

$contractCategoryId = $stmt->fetchColumn();

if (!$contractCategoryId) {
    $stmt = $pdo->prepare("
        INSERT INTO contract_categories (site_id, name, sort_order)
        VALUES (:site_id, :name, :sort_order)
    ");
    $stmt->execute([
        ':site_id'    => $siteId, 
        ':name'       => $template['category_name'], 
        ':sort_order' => $template['sort_order']
    ]);
    $contractCategoryId = $pdo->lastInsertId();
}

ksort($signers);
$totalSigners = count($signers);

$companyName = '';
foreach ($signers as $s) {
    if (($s['role'] ?? '') === 'SITE') {
        $companyName = $s['company_name'] ?? '';
        break;
    }
}

$documentUid = bin2hex(random_bytes(16));

$src = BASE_PATH . $template['file_path'];
$dstDir = BASE_PATH . '/storage/contracts/original';

if (!is_dir($dstDir)) {
    mkdir($dstDir, 0777, true);
}

$dst = $dstDir . '/' . $documentUid . '.pdf';

if (!file_exists($src) || !copy($src, $dst)) {
    die('계약 원본 PDF 복사 실패');
}

$originalFilePath = '/storage/contracts/original/' . $documentUid . '.pdf';

$pdo->beginTransaction();

try {

    $stmt = $pdo->prepare("
        INSERT INTO contracts (
            site_id, template_id, category_id,
            document_uid, original_file_path,
            company_name, complex, building, unit,
            title, total_signers,
            status, current_signer_order, created_at
        ) VALUES (
            :site_id, :template_id, :category_id,
            :document_uid, :original_file_path,
            :company_name, :complex, :building, :unit,
            :title, :total_signers,
            'PROGRESS', 1, NOW()
        )
    ");
    $stmt->execute([
        ':site_id'           => $siteId,
        ':template_id'       => $templateId,
        ':category_id'       => $contractCategoryId,
        ':document_uid'      => $documentUid,
        ':original_file_path'=> $originalFilePath,
        ':company_name'      => $companyName,
        ':complex'           => $complex,
        ':building'          => $building,
        ':unit'              => $unit,
        ':title'             => $title,
        ':total_signers'     => $totalSigners
    ]);

    $contractId = $pdo->lastInsertId();

    $stmtSigner = $pdo->prepare("
        INSERT INTO contract_signers (
            contract_id, signer_order, signer_type,
            user_id, guest_identity_id,
            is_proxy, display_name, display_phone, proxy_identity_id
        ) VALUES (
            :contract_id, :signer_order, :signer_type,
            :user_id, :guest_identity_id,
            :is_proxy, :display_name, :display_phone, :proxy_identity_id
        )
    ");

    foreach ($signers as $order => $s) {
        $isProxy = !empty($s['is_proxy']);
        $isSite  = ($s['role'] === 'SITE');

        $stmtSigner->execute([
            ':contract_id'       => $contractId,
            ':signer_order'      => (int)$order,
            ':signer_type'       => $isSite ? 'SITE' : 'GUEST',
            ':user_id'           => $isSite ? $loginUserId : null,
            ':guest_identity_id' => !$isSite ? (int)$s['guest_identity_id'] : null,
            ':is_proxy'          => $isProxy ? 1 : 0,
            ':display_name'      => $isProxy ? ($s['proxy_name'] ?? '') : ($s['name'] ?? ''),
            ':display_phone'     => $isProxy ? ($s['proxy_phone'] ?? '') : ($s['phone'] ?? ''),
            ':proxy_identity_id' => $isProxy ? (int)($s['proxy_identity_id'] ?? 0) : null
        ]);
    }

    $pdo->prepare("
        INSERT INTO contract_fields (
            contract_id, page_no, signer_order,
            field_type, x, y, width, height,
            label, required,
            ch_plural, ch_min, ch_max,
            t_style, t_size, t_array, t_color
        )
        SELECT
            :contract_id, page_no, signer_order,
            field_type, x, y, width, height,
            label, required,
            ch_plural, ch_min, ch_max,
            t_style, t_size, t_array, t_color
        FROM template_fields
        WHERE template_id = :template_id
    ")->execute([
        ':contract_id' => $contractId,
        ':template_id' => $templateId
    ]);

    $pdo->prepare("
        INSERT INTO contract_attachments (
            contract_id, signer_order,
            title, description, required,
            file_path, uploaded_at
        )
        SELECT
            :contract_id, signer_order,
            title, description, required,
            NULL, NULL
        FROM template_attachments
        WHERE template_id = :template_id
    ")->execute([
        ':contract_id' => $contractId,
        ':template_id' => $templateId
    ]);

    $pdo->commit();

} catch (Exception $e) {
    $pdo->rollBack();
    die('저장 실패: ' . $e->getMessage());
}

header('Location: ../document/index.php');
exit;
