<!-- 약관 동의 -->
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
        c.title AS contract_title,
        cs.signer_type,
        cs.display_name,
        cs.display_phone
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
    die('계약 정보 없음');
}

$userId = $_SESSION['user_id'];

$stmt = $pdo->prepare("
    SELECT
        c.title AS contract_title,
        cs.signer_type,
        cs.display_name,
        cs.display_phone
    FROM contracts c
    JOIN contract_signers cs
      ON cs.contract_id = c.id
     AND cs.signer_order = c.current_signer_order
     AND cs.user_id = :user_id
    WHERE c.id = :contract_id
      AND c.is_deleted = 0
");
$stmt->execute([
    ':contract_id' => $contractId,
    ':user_id'     => $userId
]);

$signer = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$signer) {
    echo "<script>
        alert('이 문서에 대한 서명 권한이 없습니다.');
        history.back();
    </script>";
    exit;
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
<meta charset="UTF-8">
<title>약관 동의</title>
<link rel="stylesheet" href="../../css/layout.css">
<style>
.terms-box { max-width: 600px; margin: 40px auto; }
.contract-title { font-size: 20px; font-weight: bold; margin-bottom: 20px; }

.signer-info {
  padding: 12px;
  background: #f7f7f7;
  border-radius: 6px;
  margin-bottom: 20px;
}

.checkbox-group { margin-top: 20px; }
.checkbox-item {
  display: flex;
  align-items: center;
  margin-bottom: 12px;
}
.checkbox-item label { margin-left: 8px; }

.checkbox-item a {
  margin-left: auto;
  font-size: 13px;
  color: #007bff;
  text-decoration: underline;
  cursor: pointer;
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

.btn-area { margin-top: 30px; text-align: right; }
.btn-next {
  padding: 10px 20px;
  border: none;
  border-radius: 4px;
  background: #ccc;
  color: #fff;
  cursor: not-allowed;
}
.btn-next.active {
  background: #007bff;
  cursor: pointer;
}

.modal {
  position: fixed;
  inset: 0;
  background: rgba(0,0,0,0.5);
  display: none;
  align-items: center;
  justify-content: center;
  z-index: 9999;
}

.modal.show {
  display: flex;
}

.modal-content {
  width: 90%;
  max-width: 600px;
  max-height: 80vh;
  background: #fff;
  border-radius: 8px;
  display: flex;
  flex-direction: column;
}

.modal-header {
  padding: 16px;
  border-bottom: 1px solid #e5e5e5;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.modal-body {
  padding: 16px;
  overflow-y: auto;
  font-size: 14px;
  line-height: 1.6;
  color: #333;
}

.modal-footer {
  padding: 12px 16px;
  border-top: 1px solid #e5e5e5;
  text-align: right;
}

.modal-btn {
  padding: 8px 16px;
  background: #007bff;
  border: none;
  border-radius: 4px;
  color: #fff;
  cursor: pointer;
}

.modal-close {
  background: none;
  border: none;
  font-size: 22px;
  cursor: pointer;
}
</style>
</head>
<body>

<div class="terms-box">

    <div class="contract-title">
        <?= htmlspecialchars($signer['contract_title']) ?>
    </div>

    <div class="signer-info">
        <div>서명자: <strong><?= htmlspecialchars($signer['display_name']) ?></strong></div>
        <div>연락처: <?= htmlspecialchars($signer['display_phone']) ?></div>
    </div>

    <div class="checkbox-group">

        <div class="checkbox-item">
        <input type="checkbox" id="agreeAll">
        <label for="agreeAll"><strong>모두 동의</strong></label>
        </div>

        <hr>

        <div class="checkbox-item">
        <input type="checkbox" class="agree-item" id="agree1">
        <label for="agree1">전자서명이용약관 동의</label>
        <a onclick="openTerm('term1')">보기</a>
        </div>

        <div class="checkbox-item">
        <input type="checkbox" class="agree-item" id="agree2">
        <label for="agree2">개인정보처리방침 동의</label>
        <a onclick="openTerm('term2')">보기</a>
        </div>

        <div class="checkbox-item">
        <input type="checkbox" class="agree-item" id="agree3">
        <label for="agree3">대면서명이용약관 동의</label>
        <a onclick="openTerm('term3')">보기</a>
        </div>

    </div>

    <form method="post" action="terms_action.php">
        <input type="hidden" name="contract_id" value="<?= $contractId ?>">
        <button id="nextBtn" class="btn-next" disabled>다음</button>
    </form>

    <div id="termModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
            <h3 id="modalTitle">약관</h3>
            <button class="modal-close" onclick="closeModal()">×</button>
            </div>

            <div class="modal-body" id="modalBody">
            </div>

            <div class="modal-footer">
            <button onclick="closeModal()" class="modal-btn">확인</button>
            </div>
        </div>
    </div>

</div>

<script>
const agreeAll  = document.getElementById('agreeAll');
const items    = document.querySelectorAll('.agree-item');
const nextBtn  = document.getElementById('nextBtn');

function updateState() {
  const checked = [...items].every(cb => cb.checked);
  agreeAll.checked = checked;
  nextBtn.classList.toggle('active', checked);
  nextBtn.disabled = !checked;
}

agreeAll.addEventListener('change', () => {
  items.forEach(cb => cb.checked = agreeAll.checked);
  updateState();
});

items.forEach(cb => cb.addEventListener('change', updateState));

nextBtn.addEventListener('click', () => {
  if (!nextBtn.classList.contains('active')) return;
  location.href = 'verify.php?id=<?= $contractId ?>';
});

const modal      = document.getElementById('termModal');
const modalTitle = document.getElementById('modalTitle');
const modalBody  = document.getElementById('modalBody');

const TERMS = {
  term1: {
    title: '전자서명 이용약관',
    content: `
      <p><strong>제1조 (목적)</strong></p>
      <p>본 약관은 본 서비스에서 제공하는 전자서명 기능을 이용하여 계약문서를 생성·서명·관리함에 있어, 
      전자서명의 법적 효력과 이용 절차, 책임 범위를 명확히 하는 것을 목적으로 합니다.</p>

      <p><strong>제2조 (전자서명의 효력)</strong></p>
      <p>1. 본 서비스에서 생성된 전자서명은 「전자문서 및 전자거래 기본법」 및 「전자서명법」에 따라 서면 서명과 동일한 효력을 가질 수 있습니다.<br>
      2. 전자서명은 서명자의 명시적 동의와 본인확인 절차를 거쳐 이루어진 경우에 한해 유효합니다.</p>

      <p><strong>제3조 (직원의 역할과 책임)</strong></p>
      <p>1. 직원(담당자)은 계약 진행 과정에서 다음 각 호를 확인해야 합니다.<br>
         - 계약자 또는 대리인의 신원 정보 입력 정확성<br>
         - 본인확인 및 약관 동의 절차의 정상 진행 여부<br>
         - 서명 전 계약 내용에 대한 충분한 설명 제공<br>
        2. 직원의 부주의, 허위 입력 또는 절차 미이행으로 발생한 문제에 대해 서비스 제공자는 책임을 지지 않습니다.</p>

      <p><strong>제4조 (문서의 생성 및 보관)</strong></p>
      <p>1. 전자서명이 완료된 문서는 위·변조 방지를 위한 기술적 조치가 적용됩니다.<br>
      2. 전자계약 문서는 관계 법령에 따라 보관되며, 열람 및 출력이 가능합니다.</p>

      <p><strong>제5조 (이용 제한)</strong></p>
      <p>다음 각 호에 해당하는 경우 전자서명 이용이 제한될 수 있습니다.<br>
         - 타인의 명의를 도용한 경우<br>
         - 법령 또는 공서양속에 반하는 계약인 경우<br>
         - 시스템을 악의적으로 이용하는 경우</p>
    `
  },
  term2: {
    title: '전자서명 개인정보 처리방침',
    content: `
      <p><strong>제1조 (수집하는 개인정보 항목)</strong></p>
      <p>본 서비스는 전자서명 및 계약 체결을 위해 다음의 개인정보를 수집·처리합니다.<br>
         - 계약자 정보: 성명, 전화번호<br>
         - 본인확인 정보: 본인인증 결과값<br>
         - 서명 정보: 전자서명 이미지, 서명 시각, IP 정보<br>
         - 첨부 정보: 신분증 사본, 본인서명사실확인서, 위임장, 인감증명서 등</p>

      <p><strong>제2조 (개인정보의 이용 목적)</strong></p>
      <p>수집된 개인정보는 다음 목적에 한하여 사용됩니다.<br>
         - 계약 당사자 본인 확인<br>
         - 전자계약 체결 및 이행<br>
         - 법적 분쟁 발생 시 증빙 자료 확보</p>

      <p><strong>제3조 (보유 및 이용 기간)</strong></p>
      <p>개인정보는 계약 종료 후 관계 법령이 정한 기간 동안 보관되며, 보관 기간 경과 시 지체 없이 파기됩니다.</p>

      <p><strong>제4조 (개인정보의 제공 및 위탁)</strong></p>
      <p>1. 개인정보는 원칙적으로 제3자에게 제공되지 않습니다.<br>
        2. 단, 법령에 따라 요청이 있는 경우에 한하여 제공될 수 있습니다.</p>

      <p><strong>제5조 (개인정보 보호를 위한 조치)</strong></p>
      <p>본 서비스는 개인정보의 안전한 처리를 위하여 접근 통제, 암호화, 로그 기록 등의 기술적·관리적 보호 조치를 시행합니다.</p>
    `
  },
  term3: {
    title: '대면서명 이용약관',
    content: `
      <p><strong>제1조 (대면서명의 정의)</strong></p>
      <p>대면서명이란 직원의 안내 하에 계약자가 동일 공간에서 직접 전자서명 시스템을 이용하여 계약에 서명하는 방식을 말합니다.</p>

      <p><strong>제2조 (대면서명 절차)</strong></p>
      <p>1. 직원은 계약자에게 계약 내용을 충분히 설명해야 합니다.<br>
      2. 계약자는 본인의 의사에 따라 직접 서명해야 하며, 직원은 이를 대신할 수 없습니다.<br>
      3. 직원은 서명 과정에 대한 안내만 제공하며, 서명 행위 자체에는 관여하지 않습니다.</p>

      <p><strong>제3조 (본인 확인 및 신분증 첨부)</strong></p>
      <p>1. 대면서명 시 계약자의 본인 확인을 위해 신분증 등의 증빙 자료를 첨부할 수 있습니다.<br>
      2. 대리인이 서명하는 경우, 위임장 및 관련 증빙 서류가 반드시 첨부되어야 합니다.</p>

      <p><strong>제4조 (책임의 귀속)</strong></p>
      <p>1. 계약자의 자발적 의사에 의해 이루어진 대면서명은 법적 효력을 가집니다.<br>
      2. 직원의 단순 안내를 이유로 계약 효력이 부정되지 않습니다.</p>

      <p><strong>제5조 (부정 사용 방지)</strong></p>
      <p>본 서비스는 대면서명 과정에서 발생할 수 있는 위·변조 및 부정 사용을 방지하기 위하여 서명 로그, 기기 정보, 시간 정보 등을 기록할 수 있습니다.</p>
    `
  }
};

function openTerm(type) {
  const term = TERMS[type];
  if (!term) return;

  modalTitle.textContent = term.title;
  modalBody.innerHTML = term.content;
  modal.classList.add('show');
}

function closeModal() {
  modal.classList.remove('show');
}

modal.addEventListener('click', e => {
  if (e.target === modal) closeModal();
});

document.addEventListener('keydown', e => {
  if (e.key === 'Escape') closeModal();
});
</script>

</body>
</html>
