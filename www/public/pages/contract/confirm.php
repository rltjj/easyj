<!-- 문서 설정 확인 -->
<?php
require_once __DIR__ . '/../../../../bootstrap.php';
require_once BASE_PATH . '/includes/site_context.php';

if (!isset($_SESSION['user_id'])) {
    die('로그인이 필요합니다.');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die('잘못된 접근');
}

$templateId = intval($_POST['template_id']);
$title      = $_POST['contract_title'];
$complex    = $_POST['contract_complex'];
$building   = $_POST['contract_building'];
$unit       = $_POST['contract_unit'];
$signers    = $_POST['signers'];

ksort($signers);
?>
<!DOCTYPE html>
<html lang="ko">
<head>
<meta charset="UTF-8">
<title>문서 설정 확인</title>
</head>
<body>

<h1>계약 정보 확인</h1>

<form method="post" action="save_setup.php">

  <input type="hidden" name="template_id" value="<?= $templateId ?>">
  <input type="hidden" name="contract_title" value="<?= htmlspecialchars($title) ?>">
  <input type="hidden" name="contract_complex" value="<?= htmlspecialchars($complex) ?>">
  <input type="hidden" name="contract_building" value="<?= htmlspecialchars($building) ?>">
  <input type="hidden" name="contract_unit" value="<?= htmlspecialchars($unit) ?>">

  <h3>문서 정보</h3>
  <p>문서명: <?= htmlspecialchars($title) ?></p>
  <p>단지: <?= htmlspecialchars($complex) ?></p>
  <p>동/호: <?= htmlspecialchars($building) ?> / <?= htmlspecialchars($unit) ?></p>

  <hr>

  <h3>서명자</h3>

  <?php foreach ($signers as $order => $s): ?>

    <?php
      $isProxy = isset($s['is_proxy']) && $s['is_proxy'] == '1';

      $displayName  = $isProxy
        ? ($s['proxy_name'] ?? '')
        : ($s['name'] ?? '');

      $displayPhone = $isProxy
        ? ($s['proxy_phone'] ?? '')
        : ($s['phone'] ?? '');
    ?>

    <div>
      <strong>서명자 <?= $order ?></strong><br>
      역할: <?= htmlspecialchars($s['role']) ?><br>
      <?= $isProxy ? '대리인 성명' : '성명' ?>: <?= htmlspecialchars($displayName) ?><br>
      연락처: <?= htmlspecialchars($displayPhone) ?>
    </div>

    <?php foreach ($s as $k => $v): ?>
      <input type="hidden"
             name="signers[<?= $order ?>][<?= $k ?>]"
             value="<?= htmlspecialchars($v) ?>">
    <?php endforeach; ?>

    <hr>

  <?php endforeach; ?>

  <button type="submit">확정하고 생성</button>
  <button type="button" onclick="history.back()">수정</button>

</form>

</body>
</html>
