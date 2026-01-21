<?php
require_once __DIR__ . '/../../../../bootstrap.php';
require_once BASE_PATH . '/includes/site_context.php';

if (!isset($_SESSION['user_id'])) {
    die('로그인이 필요합니다.');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die('잘못된 접근');
}

$siteId     = $_SESSION['site_id'];
$templateId = intval($_POST['template_id']);
$title      = trim($_POST['contract_title']);
$complex    = trim($_POST['contract_complex']);
$building   = trim($_POST['contract_building']);
$unit       = trim($_POST['contract_unit']);
$signers    = $_POST['signers'] ?? [];

if (!$templateId || !$title || empty($signers)) {
    die('필수 정보 누락');
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
            :document_uid,
            :company_name,
            :complex,
            :building,
            :unit,
            :title,
            :total_signers,
            'IN_PROGRESS',
            1,
            NOW()
        )
    ");

    $stmt->execute([
        ':site_id'            => $siteId,
        ':template_id'        => $templateId,
        ':document_uid'       => $documentUid,
        ':company_name'       => $_SESSION['company_name'] ?? '',
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

        $displayName = $isProxy
            ? ($s['proxy_name'] ?? '')
            : ($s['name'] ?? '');

        $displayPhone = $isProxy
            ? ($s['proxy_phone'] ?? '')
            : ($s['phone'] ?? '');

        $stmtSigner->execute([
            ':contract_id'        => $contractId,
            ':signer_order'       => intval($order),
            ':signer_type'        => $s['role'],
            ':user_id'            => $s['role'] === 'STAFF' ? intval($s['user_id']) : null,
            ':guest_identity_id'  => $s['role'] === 'GUEST' ? intval($s['guest_identity_id']) : null,
            ':is_proxy'           => $isProxy ? 1 : 0,
            ':display_name'       => $displayName,
            ':display_phone'      => $displayPhone,
            ':proxy_identity_id'  => $isProxy ? intval($s['proxy_identity_id'] ?? 0) : null
        ]);
    }

    $pdo->commit();

} catch (Exception $e) {
    $pdo->rollBack();
    die('저장 실패: ' . $e->getMessage());
}

header("Location: /public/pages/contract/terms.php?contract_id={$contractId}");
exit;
