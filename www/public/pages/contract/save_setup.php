<?php
require_once __DIR__ . '/../../../../bootstrap.php';
require_once BASE_PATH . '/includes/site_context.php';

if (!isset($_SESSION['user_id'])) {
    die('로그인이 필요합니다.');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die('잘못된 접근');
}

$loginUserId = intval($_SESSION['user_id']);
$siteId     = $_SESSION['site_id'];
$templateId = intval($_POST['template_id']);
$title      = trim($_POST['contract_title']);
$complex    = trim($_POST['contract_complex']);
$building   = trim($_POST['contract_building']);
$unit       = trim($_POST['contract_unit']);
$signers    = $_POST['signers'] ?? [];
$companyName = '';
foreach ($signers as $s) {
    if (($s['role'] ?? '') === 'SITE') {
        $companyName = $s['company_name'] ?? '';
        break;
    }
}

if (!$templateId || !$title || empty($signers)) {
    die('필수 정보 누락');
}

$stmt = $pdo->prepare("
    SELECT category_id
    FROM templates
    WHERE id = :id
      AND site_id = :site_id
");
$stmt->execute([
    ':id' => $templateId,
    ':site_id' => $siteId
]);
$templateCategoryId = $stmt->fetchColumn();

if (!$templateCategoryId) {
    throw new Exception('템플릿 카테고리 없음');
}

$stmt = $pdo->prepare("
    SELECT name, sort_order
    FROM template_categories
    WHERE id = :id
      AND site_id = :site_id
      AND is_deleted = 0
");
$stmt->execute([
    ':id' => $templateCategoryId,
    ':site_id' => $siteId
]);
$templateCategory = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$templateCategory) {
    throw new Exception('템플릿 카테고리 정보 없음');
}

$stmt = $pdo->prepare("
    SELECT id
    FROM contract_categories
    WHERE site_id = :site_id
      AND name = :name
");
$stmt->execute([
    ':site_id' => $siteId,
    ':name'    => $templateCategory['name']
]);
$contractCategoryId = $stmt->fetchColumn();

if (!$contractCategoryId) {

    $stmt = $pdo->prepare("
        INSERT INTO contract_categories (
            site_id,
            name,
            sort_order
        ) VALUES (
            :site_id,
            :name,
            :sort_order
        )
    ");
    $stmt->execute([
        ':site_id'    => $siteId,
        ':name'       => $templateCategory['name'],
        ':sort_order' => $templateCategory['sort_order']
    ]);

    $contractCategoryId = $pdo->lastInsertId();
}

ksort($signers);
$totalSigners = count($signers);

$documentUid = bin2hex(random_bytes(12));

$pdo->beginTransaction();

try {

    $stmt = $pdo->prepare("
        INSERT INTO contracts (
            site_id,
            template_id,
            category_id,
            document_uid,
            company_name,
            complex,
            building,
            unit,
            title,
            total_signers,
            status,
            current_signer_order,
            created_at
        ) VALUES (
            :site_id,
            :template_id,
            :category_id,
            :document_uid,
            :company_name,
            :complex,
            :building,
            :unit,
            :title,
            :total_signers,
            'PROGRESS',
            1,
            NOW()
        )
    ");

    $stmt->execute([
        ':site_id'            => $siteId,
        ':template_id'        => $templateId,
        ':category_id'        => $contractCategoryId,
        ':document_uid'       => $documentUid,
        ':company_name'       => $companyName,
        ':complex'            => $complex,
        ':building'           => $building,
        ':unit'               => $unit,
        ':title'              => $title,
        ':total_signers'      => $totalSigners
    ]);

    $contractId = $pdo->lastInsertId();

    $stmtSigner = $pdo->prepare("
        INSERT INTO contract_signers (
            contract_id,
            signer_order,
            signer_type,
            user_id,
            guest_identity_id,
            is_proxy,
            display_name,
            display_phone,
            proxy_identity_id
        ) VALUES (
            :contract_id,
            :signer_order,
            :signer_type,
            :user_id,
            :guest_identity_id,
            :is_proxy,
            :display_name,
            :display_phone,
            :proxy_identity_id
        )
    ");

    foreach ($signers as $order => $s) {

        $isProxy = isset($s['is_proxy']) && $s['is_proxy'] == '1';
        $isSite = ($s['role'] === 'SITE');

        $displayName = $isProxy
            ? ($s['proxy_name'] ?? '')
            : ($s['name'] ?? '');

        $displayPhone = $isProxy
            ? ($s['proxy_phone'] ?? '')
            : ($s['phone'] ?? '');

        $stmtSigner->execute([
            ':contract_id'        => $contractId,
            ':signer_order'       => intval($order),
            ':signer_type'        => $isSite ? 'SITE' : 'GUEST',
            ':user_id'            => $isSite ? $loginUserId : null,
            ':guest_identity_id'  => $s['role'] === 'GUEST' ? intval($s['guest_identity_id']) : null,
            ':is_proxy'           => $isProxy ? 1 : 0,
            ':display_name'       => $displayName,
            ':display_phone'      => $displayPhone,
            ':proxy_identity_id'  => $isProxy ? intval($s['proxy_identity_id'] ?? 0) : null
        ]);
    }

    $stmt = $pdo->prepare("
        INSERT INTO contract_fields (
            contract_id,
            page_no,
            signer_order,
            field_type,
            x,
            y,
            width,
            height,
            label,
            required,
            ch_plural,
            ch_min,
            ch_max,
            t_style,
            t_size,
            t_array,
            t_color
        )
        SELECT
            :contract_id,
            page_no,
            signer_order,
            field_type,
            x,
            y,
            width,
            height,
            label,
            required,
            ch_plural,
            ch_min,
            ch_max,
            t_style,
            t_size,
            t_array,
            t_color
        FROM template_fields
        WHERE template_id = :template_id
    ");

    $stmt->execute([
        ':contract_id' => $contractId,
        ':template_id' => $templateId
    ]);

    $stmt = $pdo->prepare("
        INSERT INTO contract_attachments (
            contract_id,
            signer_order,
            title,
            description,
            required,
            file_path,
            uploaded_at
        )
        SELECT
            :contract_id,
            signer_order,
            title,
            description,
            required,
            NULL,
            NULL
        FROM template_attachments
        WHERE template_id = :template_id
    ");

    $stmt->execute([
        ':contract_id' => $contractId,
        ':template_id' => $templateId
    ]);


    $pdo->commit();

} catch (Exception $e) {
    $pdo->rollBack();
    die('저장 실패: ' . $e->getMessage());
}

header("Location: ../document/index.php");
exit;
