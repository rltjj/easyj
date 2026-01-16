<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../../config/database.php';

if ($_SESSION['role'] !== 'ADMIN') {
    die('접근 불가');
}

$templateId = intval($_GET['id'] ?? 0);
if (!$templateId) die('템플릿 없음');

$stmt = $pdo->prepare("SELECT * FROM templates WHERE id = :id");
$stmt->execute([':id' => $templateId]);
$template = $stmt->fetch(PDO::FETCH_ASSOC);
$staffSignMode = $template['staff_sign_mode'];
if (!$template) die('존재하지 않음');

$stmt = $pdo->prepare("
  SELECT *
  FROM template_fields
  WHERE template_id = :id
");
$stmt->execute([':id' => $templateId]);
$fields = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="ko">
<head>
<meta charset="UTF-8">
<title>템플릿 에디터</title>

<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.8.162/pdf.min.js"></script>

<style>
body { margin:0; display:flex; height:100%; font-family:sans-serif; }
.sidebar {
  width:260px; height:100%; background:#f5f5f5; padding:10px; overflow:auto; position: fixed;
}
.sidebar h3 { cursor:pointer; margin:10px 0; }
.sidebar .group { display:none; padding-left:10px; }
.sidebar button { width:100%; margin:4px 0; }

.editor {
  flex:1; background:#ddd; display:flex; justify-content:center;
}
#pdfWrap {
  position:relative;
}
.field {
  position:absolute;
  border:1px dashed #333;
  background:#fff;
  cursor:move;
}

.field .resize {
  position:absolute;
  width:10px;
  height:10px;
  right:-5px;
  bottom:-5px;
  background:#4f46e5;
  cursor:se-resize;
}

.field.selected {
  border:2px solid red;
}
.save-btn {
  position:fixed;
  bottom:20px;
  left:20px;
  padding:10px 20px;
}
.pdf-page {
  position: relative;
  margin-bottom: 30px;
  background: #fff;
  box-shadow: 0 2px 6px rgba(0,0,0,.15);
}
.pdf-page.active {
  outline: 3px solid #4f46e5;
}
.guide-line {
  position:absolute;
  background:#4f46e5;
  pointer-events:none;
  display:none;
  z-index:1000;
}
.guide-line.x {
  height:1px;
  width:100%;
}

.guide-line.y {
  width:1px;
  height:100%;
}
</style>
</head>

<body>

<div class="sidebar">

  <h3 onclick="toggleGroup(this)">직원</h3>
  <div class="group">
    <button onclick="addField('TEXT','STAFF')">텍스트</button>
    <button onclick="addField('CHECKBOX','STAFF')">체크박스</button>

    <?php if ($staffSignMode === 'LIVE'): ?>
      <button onclick="addField('SIGN','STAFF', '날인')">서명/날인</button>
    <?php else: ?>
      <button disabled style="opacity:.5; cursor:not-allowed;">
        서명/날인 (사용 불가)
      </button>
    <?php endif; ?>
  </div>

  <h3 onclick="toggleGroup(this)">계약자</h3>
  <div class="group">
    <button onclick="addField('TEXT','CONTRACTOR')">텍스트</button>
    <button onclick="addField('CHECKBOX','CONTRACTOR')">체크박스</button>
    <button onclick="addField('SIGN','CONTRACTOR', '서명')">서명/날인</button>
    <button onclick="addField('TEXT','CONTRACTOR','주민등록번호')">주민등록번호</button>
    <button onclick="addField('TEXT','CONTRACTOR','주소')">주소</button>
    <button onclick="addField('TEXT','CONTRACTOR','동')">동</button>
    <button onclick="addField('TEXT','CONTRACTOR','호')">호</button>
  </div>

  <h3 onclick="toggleGroup(this)">날짜</h3>
  <div class="group">
    <button onclick="addField('DATE','CONTRACTOR','yyyy')">yyyy</button>
    <button onclick="addField('DATE','CONTRACTOR','mm')">mm</button>
    <button onclick="addField('DATE','CONTRACTOR','dd')">dd</button>
  </div>

  <hr>
  <h3>선택 필드 설정</h3>

  <div id="fieldPanel" style="display:none">
    <label>
      라벨
      <input type="text" id="fieldLabel">
    </label>
    <br><br>
    <button onclick="deleteField()">삭제</button>
  </div>

</div>

<div class="editor">
  <div id="pdfWrap">
    <div id="guide-x" class="guide-line x"></div>
    <div id="guide-y" class="guide-line y"></div>
  </div>
</div>

<button class="save-btn" onclick="saveFields()">저장하기</button>

<?php
$pdfUrl = '/easyjoin/uploads/templates/' . basename($template['pdf_path']);
?>

<script>
const pdfUrl = "<?= $pdfUrl ?>";
const wrap = document.getElementById('pdfWrap');
const STAFF_SIGN_MODE = "<?= $staffSignMode ?>";

let selected = null;
let activePage = null;

let fields = <?= json_encode($fields, JSON_UNESCAPED_UNICODE) ?>;

const SNAP = 5;

function snap(v) {
  return Math.round(v / SNAP) * SNAP;
}

let copiedField = null;


pdfjsLib.getDocument(pdfUrl).promise.then(async pdf => {
  for (let pageNum = 1; pageNum <= pdf.numPages; pageNum++) {
    const page = await pdf.getPage(pageNum);
    const viewport = page.getViewport({ scale: 1.3 });

    const pageWrap = document.createElement('div');
    pageWrap.className = 'pdf-page';
    pageWrap.style.position = 'relative';
    pageWrap.style.marginBottom = '20px';

    pageWrap.addEventListener('click', e => {
      e.stopPropagation();
      setActivePage(pageWrap);
    });

    const canvas = document.createElement('canvas');
    canvas.width = viewport.width;
    canvas.height = viewport.height;

    const guideX = document.createElement('div');
    guideX.className = 'guide-line x';

    const guideY = document.createElement('div');
    guideY.className = 'guide-line y';

    pageWrap.appendChild(guideX);
    pageWrap.appendChild(guideY);
    pageWrap.appendChild(canvas);

    wrap.appendChild(pageWrap);

    await page.render({
      canvasContext: canvas.getContext('2d'),
      viewport
    }).promise;
  }

  renderFields();
});

function renderFields() {
  fields.forEach(f => {
    const pageEl = document.querySelectorAll('.pdf-page')[f.page - 1];
    if (!pageEl) return;

    createFieldEl(f, pageEl);
  });
}

function createFieldEl(f, pageEl) {
  const el = document.createElement('div');
  el.className = 'field';
  el.style.left = f.pos_x + 'px';
  el.style.top = f.pos_y + 'px';
  el.style.width = (f.width || 100) + 'px';
  el.style.height = (f.height || 30) + 'px';
  el.textContent = f.label || f.field_type;

  el.dataset.type = f.field_type;
  el.dataset.role = f.role;

  makeDraggable(el, pageEl);

  if (f.field_type === 'TEXT' || f.field_type === 'SIGN') {
    const resize = document.createElement('div');
    resize.className = 'resize';
    el.appendChild(resize);

    makeResizable(el, resize, pageEl);
  }

  el.onclick = e => {
    e.stopPropagation();
    select(el);
  };

  pageEl.appendChild(el);
}

function addField(type, role, label='') {

  if (
    role === 'STAFF' &&
    type === 'SIGN' &&
    STAFF_SIGN_MODE !== 'LIVE'
  ) {
    alert(
      STAFF_SIGN_MODE === 'PRE_INCLUDED'
        ? '이 문서는 직원 서명/날인이 이미 포함된 템플릿입니다.'
        : '이 문서는 직원 서명/날인을 사용하지 않습니다.'
    );
    return;
  }
  
  if (!activePage) {
    alert('페이지를 먼저 클릭하세요');
    return;
  }

  const f = {
    field_type: type,
    role: role,
    label,
    pos_x: 50,
    pos_y: 50,
    width: type === 'CHECKBOX' ? 20 : 120,
    height: type === 'CHECKBOX' ? 20 : 30
  };

  createFieldEl(f, activePage);
}

function makeDraggable(el, container) {
  const guideX = container.querySelector('.guide-line.x');
  const guideY = container.querySelector('.guide-line.y');

  let offsetX, offsetY;

  el.onmousedown = e => {
    if (e.target.classList.contains('resize')) return;

    select(el);

    offsetX = e.offsetX;
    offsetY = e.offsetY;

    document.onmousemove = ev => {
      const rect = container.getBoundingClientRect();

      let x = ev.clientX - rect.left - offsetX;
      let y = ev.clientY - rect.top  - offsetY;


      // 경계 제한
      x = snap(x);
      y = snap(y);

      el.style.left = x + 'px';
      el.style.top  = y + 'px';

      // 가이드라인 표시
      guideX.style.top = (y + el.offsetHeight / 2) + 'px';
      guideY.style.left = (x + el.offsetWidth / 2) + 'px';

      guideX.style.display = 'block';
      guideY.style.display = 'block';
    };

    document.onmouseup = () => {
      document.onmousemove = null;
      guideX.style.display = 'none';
      guideY.style.display = 'none';
    };
  };
}

function makeResizable(el, handle, container) {
  handle.onmousedown = e => {
    e.stopPropagation();

    const startX = e.pageX;
    const startY = e.pageY;
    const startW = el.offsetWidth;
    const startH = el.offsetHeight;

    const rect = container.getBoundingClientRect();

    document.onmousemove = ev => {
      let newW = startW + (ev.pageX - startX);
      let newH = startH + (ev.pageY - startY);

      newW = Math.max(40, newW);
      newH = Math.max(20, newH);

      const left = el.offsetLeft;
      const top  = el.offsetTop;

      newW = Math.min(newW, rect.width - left);
      newH = Math.min(newH, rect.height - top);

      el.style.width  = newW + 'px';
      el.style.height = newH + 'px';
    };

    document.onmouseup = () => {
      document.onmousemove = null;
    };
  };
}

function select(el) {
  document.querySelectorAll('.field').forEach(f => f.classList.remove('selected'));
  el.classList.add('selected');
  selected = el;
}

document.addEventListener('keydown', e => {
  if (e.key === 'Backspace' && selected) {
    selected.remove();
    selected = null;
  }
});

function saveFields() {
  const data = [];

  document.querySelectorAll('.pdf-page').forEach((pageEl, pageIndex) => {
    pageEl.querySelectorAll('.field').forEach(el => {
      data.push({
        page: pageIndex + 1,
        type: el.dataset.type,
        role: el.dataset.role,
        label: el.textContent,
        pos_x: parseInt(el.style.left),
        pos_y: parseInt(el.style.top),
        width: parseInt(el.style.width),
        height: parseInt(el.style.height)
      });
    });
  });

  fetch('save_fields.php?id=<?= $templateId ?>', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(data)
  })
  .then(res => res.json())
  .then(result => {
    if (result.success) {
      alert('저장되었습니다.');
      location.href = 'index.php';
    } else {
      alert('저장에 실패했습니다.');
    }
  })
  .catch(err => {
    console.error(err);
    alert('저장 중 오류가 발생했습니다.');
  });
}

function toggleGroup(h3) {
  const g = h3.nextElementSibling;
  g.style.display = g.style.display === 'block' ? 'none' : 'block';
}

function setActivePage(page) {
  document.querySelectorAll('.pdf-page').forEach(p =>
    p.classList.remove('active')
  );
  page.classList.add('active');
  activePage = page;
}

function select(el) {
  document.querySelectorAll('.field').forEach(f =>
    f.classList.remove('selected')
  );

  el.classList.add('selected');
  selected = el;

  document.getElementById('fieldPanel').style.display = 'block';
  document.getElementById('fieldLabel').value = el.textContent;
}

document.getElementById('fieldLabel').addEventListener('input', e => {
  if (selected) {
    selected.textContent = e.target.value;
  }
});

function deleteField() {
  if (!selected) return;
  selected.remove();
  selected = null;
  document.getElementById('fieldPanel').style.display = 'none';
}

document.addEventListener('keydown', e => {
  if (!selected) return;

  const step = e.shiftKey ? 10 : 1;

  let x = parseInt(selected.style.left);
  let y = parseInt(selected.style.top);

  switch (e.key) {
    case 'ArrowLeft':  x -= step; break;
    case 'ArrowRight': x += step; break;
    case 'ArrowUp':    y -= step; break;
    case 'ArrowDown':  y += step; break;
    default: return;
  }

  e.preventDefault();

  x = Math.max(0, Math.min(x, selected.parentElement.offsetWidth - selected.offsetWidth));
  y = Math.max(0, Math.min(y, selected.parentElement.offsetHeight - selected.offsetHeight));

  selected.style.left = x + 'px';
  selected.style.top  = y + 'px';
});

document.addEventListener('keydown', e => {
  if (!selected) return;

  // 복사
  if (e.ctrlKey && e.key === 'c') {
    e.preventDefault();

    copiedField = {
      type: selected.dataset.type,
      label: selected.textContent,
      width: parseInt(selected.style.width),
      height: parseInt(selected.style.height)
    };

    console.log('필드 복사됨', copiedField);
  }

  // 붙여넣기
  if (e.ctrlKey && e.key === 'v') {
    e.preventDefault();

    if (!copiedField || !activePage) return;

    const x = snap(parseInt(selected.style.left) + 10);
    const y = snap(parseInt(selected.style.top) + 10);

    const f = {
      field_type: copiedField.type,
      label: copiedField.label,
      pos_x: x,
      pos_y: y,
      width: copiedField.width,
      height: copiedField.height
    };

    createFieldEl(f, activePage);
  }
});
    
</script>

</body>
</html>
