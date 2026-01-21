<!-- 문서 설정 -->
<?php
require_once __DIR__ . '/../../../../bootstrap.php';
require_once BASE_PATH . '/includes/site_context.php';
$activeMenu = 'contract';


if (!isset($_SESSION['user_id'])) {
    die('로그인이 필요합니다.');
}

$siteId     = $currentSiteId;
$templateId = intval($_GET['id'] ?? 0);
if (!$templateId) {
    die('template_id 없음');
}

$stmt = $pdo->prepare("
    SELECT title
    FROM templates
    WHERE id = :id
      AND site_id = :site_id
");
$stmt->execute([
    ':id' => $templateId,
    ':site_id' => $siteId
]);

$templateTitle = $stmt->fetchColumn();

if (!$templateTitle) {
    die('템플릿 정보를 찾을 수 없습니다.');
}

$stmt = $pdo->prepare("
    SELECT signer_order, signer_role, company_name
    FROM template_signers
    WHERE template_id = :tid
    ORDER BY signer_order ASC
");
$stmt->execute(['tid' => $templateId]);
$signers = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!$signers) {
    die('서명자 설정 없음');
}

$companyName = null;
$agencyName  = null;

$stmt = $pdo->prepare("SELECT company_name FROM operator_company WHERE site_id = :sid");
$stmt->execute(['sid' => $siteId]);
$companyName = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT agency_name FROM operator_agency WHERE site_id = :sid");
$stmt->execute(['sid' => $siteId]);
$agencyName = $stmt->fetchColumn();
?>
<!DOCTYPE html>
<html lang="ko">
<head>
  <meta charset="UTF-8">
  <title>문서 설정</title>
  <style>
  body {
      font-family: 'Pretendard', sans-serif;
      background: #f8fafc;
      margin: 0;
      padding: 0;
      color: #334155;
  }

  .container {
      max-width: 800px;
      margin: 60px auto;
      background: #fff;
      padding: 40px;
      border-radius: 16px;
      box-shadow: 0 4px 20px rgba(0,0,0,0.05);
  }

  h1 {
      font-size: 24px;
      color: #1e293b;
      margin-bottom: 30px;
      font-weight: 700;
  }

  label {
      display: block;
      margin-top: 20px;
      font-size: 14px;
      font-weight: 600;
      color: #64748b;
      margin-bottom: 8px;
  }

  input[type="text"], 
  input[type="tel"] {
      width: 100%;
      padding: 12px 15px;
      border: 1px solid #e2e8f0;
      border-radius: 8px;
      font-size: 15px;
      box-sizing: border-box;
      transition: all 0.2s;
      outline: none;
  }

  input[type="text"]:focus, 
  input[type="tel"]:focus {
      border-color: #4A90E2;
      box-shadow: 0 0 0 3px rgba(74, 144, 226, 0.1);
  }

  input[readonly] {
      background-color: #f1f5f9;
      color: #94a3b8;
      cursor: not-allowed;
      border-color: #e2e8f0;
  }

  hr {
      border: 0;
      border-top: 1px solid #f1f5f9;
      margin: 30px 0;
  }

  .block {
      background: #fff;
      border: 1px solid #e2e8f0;
      padding: 25px;
      border-radius: 12px;
      margin-bottom: 24px;
      transition: border-color 0.2s;
  }

  .block:hover {
      border-color: #cbd5e1;
  }

  .block h3 {
      margin-top: 0;
      font-size: 18px;
      color: #4A90E2;
      border-bottom: 1px solid #f1f5f9;
      padding-bottom: 15px;
      margin-bottom: 15px;
  }

  label input[type="checkbox"] {
      display: inline-block;
      width: auto;
      margin-top: 0;
      margin-right: 8px;
  }

  .proxy-fields {
      margin-top: 20px;
      display: none;
      background: #f1f7ff;
      padding: 20px;
      border-radius: 8px;
      border: 1px dashed #4A90E2;
  }

  button[type="submit"] {
      width: 100%;
      padding: 16px;
      background: #4A90E2;
      color: white;
      border: none;
      border-radius: 12px;
      font-size: 16px;
      font-weight: 700;
      cursor: pointer;
      margin-top: 20px;
      transition: background 0.2s;
  }

  button[type="submit"]:hover {
      background: #357ABD;
      box-shadow: 0 4px 12px rgba(74, 144, 226, 0.3);
  }

  @media (max-width: 600px) {
      .container {
          margin: 20px;
          padding: 25px;
      }
  }
  </style>

  <script>
    function toggleProxy(order) {
        const checked = document.getElementById('proxy_check_' + order).checked;
        document.getElementById('normal_' + order).style.display = checked ? 'none' : 'block';
        document.getElementById('proxy_' + order).style.display  = checked ? 'block' : 'none';
    }
  </script>
</head>
<body>

  <div class="container">
    <h1>계약 문서 설정</h1>

    <form method="post" action="confirm.php">

      <input type="hidden" name="template_id" value="<?= $templateId ?>">

      <label>계약 문서명</label>
      <input type="text"
       name="contract_title"
       value="<?= htmlspecialchars($templateTitle, ENT_QUOTES, 'UTF-8') ?>"
       required>

      <label>단지</label>
      <input type="text"
       name="contract_complex" required>

      <label>동</label>
      <input type="text"
       name="contract_building" required>

      <label>호수</label>
      <input type="text"
       name="contract_unit" required>

      <hr> 

      <?php foreach ($signers as $s): 
          $order = $s['signer_order'];
          $role  = $s['signer_role'];
          $companyType = $s['company_name'];
      ?>

      <div class="block">
        <h3>서명자 <?= $order ?></h3>

        <?php if ($role === 'SITE'): 

            $displayCompany = '';
            if ($companyType === 'COMPANY') $displayCompany = $companyName;
            if ($companyType === 'AGENCY')  $displayCompany = $agencyName;
        ?>

        <input type="hidden" name="signers[<?= $order ?>][role]" value="SITE">

        <label>소속</label>
        <input type="text"
          value="<?= htmlspecialchars($displayCompany) ?>"
          readonly>
        <input type="hidden"
          name="signers[<?= $order ?>][company_name]"
          value="<?= htmlspecialchars($displayCompany, ENT_QUOTES, 'UTF-8') ?>">

        <label>성명</label>
        <input type="text"
              name="signers[<?= $order ?>][name]"
              value="<?= htmlspecialchars($_SESSION['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
              readonly>

        <label>연락처</label>
        <input type="tel"
              name="signers[<?= $order ?>][phone]"
              value="<?= htmlspecialchars($_SESSION['phone'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
              readonly>

        <?php else: ?>

        <input type="hidden" name="signers[<?= $order ?>][role]" value="GUEST">

        <div id="normal_<?= $order ?>">
          <label>성명</label>
          <input type="text" name="signers[<?= $order ?>][name]">

          <label>연락처</label>
          <input type="tel" name="signers[<?= $order ?>][phone]">
        </div>

        <label>
        <input type="checkbox" id="proxy_check_<?= $order ?>"
              name="signers[<?= $order ?>][is_proxy]"
              value="1"
              onchange="toggleProxy(<?= $order ?>)">
        대리인입니다
        </label>

        <div class="proxy-fields" id="proxy_<?= $order ?>">
          <label>대리인 성명</label>
          <input type="text" name="signers[<?= $order ?>][proxy_name]">

          <label>대리인 연락처</label>
          <input type="tel" name="signers[<?= $order ?>][proxy_phone]">
        </div>

        <?php endif; ?>

      </div>

      <?php endforeach; ?>

      <button type="submit">다음</button>

    </form>
  </div>

</body>
</html>