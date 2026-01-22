<?php
require_once __DIR__ . '/../../../../bootstrap.php';

if (!isset($_SESSION['user_id'])) {
    die('로그인이 필요합니다.');
}

$contractId = (int)($_POST['contract_id'] ?? 0);
if (!$contractId) {
    die('잘못된 접근');
}

$stmt = $pdo->prepare("
    SELECT current_signer_order
    FROM contracts
    WHERE id = :id
");
$stmt->execute([':id' => $contractId]);
$signerOrder = $stmt->fetchColumn();

if (!$signerOrder) {
    die('계약 정보 없음');
}

$stmt = $pdo->prepare("
    SELECT id, started_at
    FROM audit_logs
    WHERE contract_id = :contract_id
      AND signer_order = :signer_order
");
$stmt->execute([
    ':contract_id' => $contractId,
    ':signer_order' => $signerOrder
]);

$audit = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$audit) {
    $stmt = $pdo->prepare("
        INSERT INTO audit_logs (
            contract_id,
            signer_order,
            started_at
        ) VALUES (
            :contract_id,
            :signer_order,
            NOW()
        )
    ");
    $stmt->execute([
        ':contract_id' => $contractId,
        ':signer_order' => $signerOrder
    ]);
} elseif (!$audit['started_at']) {
    $stmt = $pdo->prepare("
        UPDATE audit_logs
        SET started_at = NOW()
        WHERE id = :id
    ");
    $stmt->execute([
        ':id' => $audit['id']
    ]);
}

header("Location: verify.php?id={$contractId}");
exit;
