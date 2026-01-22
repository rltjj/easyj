<!-- 본인 확인 -->
 <?php
require_once __DIR__ . '/../../../../bootstrap.php';

if (!isset($_SESSION['user_id'])) {
    die('로그인이 필요합니다.');
}

$contractId = (int)($_GET['id'] ?? 0);
if (!$contractId) {
    die('잘못된 접근');
}

$stmt = $pdo->prepare("
    SELECT
        c.id AS contract_id,
        cs.signer_order,
        cs.signer_type,
        cs.user_id
    FROM contracts c
    JOIN contract_signers cs
      ON cs.contract_id = c.id
     AND cs.signer_order = c.current_signer_order
    WHERE c.id = :id
      AND c.is_deleted = 0
");
$stmt->execute([':id' => $contractId]);
$signer = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$signer) {
    die('서명자 정보 없음');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'site_verify') {

    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("SELECT password FROM users WHERE id = :id");
    $stmt->execute([':id' => $signer['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || !password_verify($password, $user['password'])) {
        $error = '비밀번호가 틀렸습니다. 다시 입력해주세요.';
    } else {
        $pdo->prepare("
            UPDATE audit_logs
            SET verified_at = NOW()
            WHERE contract_id = ?
              AND signer_order = ?
              AND verified_at IS NULL
        ")->execute([$contractId, $signer['signer_order']]);

        header("Location: sign.php?id={$contractId}");
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'guest_upload') {

    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        $error = '파일 업로드에 실패했습니다.';
    } else {
        $uploadDir = BASE_PATH . '/storage/attachments/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $filename = uniqid() . '_' . basename($_FILES['file']['name']);
        $filePath = $uploadDir . $filename;

        move_uploaded_file($_FILES['file']['tmp_name'], $filePath);

        $stmt = $pdo->prepare("
            INSERT INTO contract_attachments
            (contract_id, signer_order, title, required, file_path, uploaded_at)
            VALUES (?, ?, '본인서명사실확인서', 1, ?, NOW())
        ");
        $stmt->execute([
            $contractId,
            $signer['signer_order'],
            '/storage/attachments/' . $filename
        ]);

        $pdo->prepare("
            UPDATE audit_logs
            SET verified_at = NOW()
            WHERE contract_id = ?
              AND signer_order = ?
              AND verified_at IS NULL
        ")->execute([$contractId, $signer['signer_order']]);

        header("Location: sign.php?id={$contractId}");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
<meta charset="UTF-8">
<title>본인 확인</title>
<link rel="stylesheet" href="../../css/layout.css">
<style>
.box {
    max-width: 400px;
    margin: 60px auto;
    padding: 30px;
    border-radius: 8px;
}

h2 {
    font-size: 18px;
    font-weight: 700;
    margin-bottom: 20px;
    text-align: center;
}

p {
    font-size: 14px;
    color: #64748b;
    text-align: center;
    margin-bottom: 20px;
}

.error {
    font-size: 13px;
    color: #ef4444;
    background: #fef2f2;
    padding: 10px;
    border-radius: 4px;
    margin-bottom: 15px;
    text-align: center;
}

label {
    font-size: 13px;
    font-weight: 600;
    color: #475569;
}

input[type="password"],
input[type="file"] {
    width: 100%;
    height: 40px;
    padding: 0 10px;
    margin-top: 8px;
    border: 1px solid #d1d5db;
    border-radius: 4px;
    box-sizing: border-box;
    font-size: 14px;
}

button {
    width: 100%;
    height: 44px;
    margin-top: 15px;
    border: none;
    border-radius: 4px;
    background: #4A90E2;
    color: #fff;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
}

button:hover {
    background: #357ABD;
}

button[onclick] {
    background: #fff;
    color: #4A90E2;
    border: 1px solid #4A90E2;
}

hr {
    border: 0;
    border-top: 1px solid #f1f5f9;
    margin: 25px 0;
}
</style>
</head>
<body>

<div class="box">

<h2>본인 확인</h2>

<?php if ($error): ?>
<div class="error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<?php if ($signer['signer_type'] === 'SITE'): ?>

<form method="post">
    <input type="hidden" name="action" value="site_verify">
    <div>
        <label>비밀번호</label><br>
        <input type="password" name="password" required>
    </div>
    <button type="submit">확인</button>
</form>

<?php else: ?>

<p>본인 확인 방법을 선택해주세요.</p>

<button onclick="alert('본인인증 API 연동 예정')">본인인증</button>

<hr>

<form method="post" enctype="multipart/form-data">
    <input type="hidden" name="action" value="guest_upload">
    <div>
        <label>본인서명사실확인서 업로드</label><br>
        <input type="file" name="file" required>
    </div>
    <button type="submit">첨부 후 진행</button>
</form>

<?php endif; ?>

</div>

</body>
</html>
