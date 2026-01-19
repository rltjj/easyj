<?php
require_once __DIR__ . '/../../../../bootstrap.php';

if ($_SESSION['role'] !== 'ADMIN') {
  die('접근 불가');
}

$templateId = intval($_GET['id'] ?? 0);
if (!$templateId) die('템플릿 ID 없음');

$stmt = $pdo->prepare("
  SELECT id, file_path
  FROM templates
  WHERE id = :id
");
$stmt->execute([':id' => $templateId]);
$template = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$template) die('템플릿 없음');

$pdfUrl = '/easyj' . $template['file_path'];

$signers = $pdo->prepare("
  SELECT id, signer_order, signer_role
  FROM template_signers
  WHERE template_id = :tid
  ORDER BY signer_order
");
$signers->execute([':tid' => $templateId]);
$signers = $signers->fetchAll(PDO::FETCH_ASSOC);

$fields = $pdo->prepare("
  SELECT *
  FROM template_fields
  WHERE template_id = :tid
");
$fields->execute([':tid' => $templateId]);
$fields = $fields->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="ko">
<head>
<meta charset="UTF-8">
<title>템플릿 편집</title>

<script src="/easyj/assets/pdfjs/pdf.min.js"></script>

<style>
body {
  margin:0;
  display:flex;
  height:100vh;
  font-family:sans-serif;
}
.sidebar {
  width:260px;
  background:#f5f5f5;
  padding:10px;
  overflow:auto;
}
.sidebar h3 {
  margin:10px 0 5px;
}
.sidebar button {
  width:100%;
  margin:3px 0;
}
.editor {
  flex:1;
  overflow:auto;
  background:#ccc;
}
.page {
  position:relative;
  margin:20px auto;
  background:#fff;
  box-shadow:0 2px 6px rgba(0,0,0,.2);
}
.page.active {
  outline:3px solid #4f46e5;
}
.field {
  position:absolute;
  border:1px dashed #333;
  background:#fff;
  font-size:12px;
  padding:3px;
  cursor:move;
  user-select:none;
  box-sizing: border-box;
}
.field.selected {
  border:2px solid red;
}
.field.checkbox {
  display:flex;
  align-items:center;
  justify-content:center;
  font-size:16px;
  padding:0;
  box-sizing:border-box;
}
.resize {
  position:absolute;
  width:10px;
  height:10px;
  right:-5px;
  bottom:-5px;
  background:#4f46e5;
  cursor:se-resize;
}
.save-btn {
  position:fixed;
  bottom:20px;
  left:20px;
  padding:10px 20px;
}
</style>
</head>

<body>

<div class="sidebar">
  <h2>서명자</h2>

  <?php foreach ($signers as $s): ?>
    <h3>서명자 <?= $s['signer_order'] ?></h3>
    <button onclick="addField('TEXT', <?= $s['id'] ?>)">텍스트</button>
    <button onclick="addField('CHECKBOX', <?= $s['id'] ?>)">체크박스</button>
    <button onclick="addField('SIGN', <?= $s['id'] ?>)">서명</button>
    <button onclick="addField('STAMP', <?= $s['id'] ?>)">날인</button>
    <hr>
  <?php endforeach; ?>
</div>

<div class="editor" id="pdfWrap"></div>

<button class="save-btn" onclick="saveFields()">💾 저장</button>

<script>
const PDF_URL = "<?= htmlspecialchars($pdfUrl) ?>";
const TEMPLATE_ID = <?= $templateId ?>;
const EXIST_FIELDS = <?= json_encode($fields, JSON_UNESCAPED_UNICODE) ?>;

pdfjsLib.GlobalWorkerOptions.workerSrc =
  "/easyj/assets/pdfjs/pdf.worker.min.js";

let pages = [];
let activePage = null;
let selected = null;
let copiedField = null;

pdfjsLib.getDocument(PDF_URL).promise.then(async pdf => {
  for (let p = 1; p <= pdf.numPages; p++) {
    const page = await pdf.getPage(p);
    const viewport = page.getViewport({ scale: 1.3 });

    const pageDiv = document.createElement('div');
    pageDiv.className = 'page';
    pageDiv.dataset.page = p;
    pageDiv.style.width = viewport.width + 'px';
    pageDiv.style.height = viewport.height + 'px';

    pageDiv.onclick = e => {
      e.stopPropagation();
      setActivePage(pageDiv);
    };

    const canvas = document.createElement('canvas');
    canvas.width = viewport.width;
    canvas.height = viewport.height;
    pageDiv.appendChild(canvas);

    document.getElementById('pdfWrap').appendChild(pageDiv);

    await page.render({
      canvasContext: canvas.getContext('2d'),
      viewport
    }).promise;

    pages[p] = pageDiv;
  }

  renderFields();
});

function renderFields() {
  EXIST_FIELDS.forEach(f => {
    const page = pages[f.page_no];
    if (!page) return;
    createFieldEl(f, page);
  });
}

function addField(type, signerId) {
  if (!activePage) {
    alert('페이지를 먼저 선택하세요');
    return;
  }

  const field = {
    id: 'new_' + Date.now(),
    field_type: type,
    signer_id: signerId,
    label: type === 'CHECKBOX' ? '' : type,
    page_no: activePage.dataset.page,
    pos_x: 50,
    pos_y: 50,
    width: type === 'CHECKBOX' ? 22 : 120,
    height: type === 'CHECKBOX' ? 22 : 30
  };

  createFieldEl(field, activePage);
}

function createFieldEl(f, page) {
  const el = document.createElement('div');
  el.className = 'field';
  el.dataset.id = f.id;
  el.dataset.type = f.field_type;
  el.dataset.signer = f.signer_id;
  el.dataset.page = f.page_no;

  el.style.left = f.pos_x + 'px';
  el.style.top  = f.pos_y + 'px';
  el.style.width = f.width + 'px';
  el.style.height = f.height + 'px';

  if (f.field_type === 'CHECKBOX') {
    el.classList.add('checkbox');
    el.innerHTML = '☐';
  } else {
    el.textContent = f.label;
    const resize = document.createElement('div');
    resize.className = 'resize';
    el.appendChild(resize);
    makeResizable(el, resize, page);
  }

  makeDraggable(el, page);

  el.onclick = e => {
    e.stopPropagation();
    select(el);
  };

  page.appendChild(el);
  return el;
}

function makeDraggable(el, container) {
  let ox, oy, down = false;

  el.onmousedown = e => {
    if (e.target.classList.contains('resize')) return;
    down = true;
    select(el);
    ox = e.offsetX;
    oy = e.offsetY;
  };

  document.onmousemove = e => {
    if (!down) return;
    const r = container.getBoundingClientRect();
    el.style.left = e.clientX - r.left - ox + 'px';
    el.style.top  = e.clientY - r.top  - oy + 'px';
  };

  document.onmouseup = () => down = false;
}

function makeResizable(el, handle, container) {
  handle.onmousedown = e => {
    e.stopPropagation();
    const sw = el.offsetWidth;
    const sh = el.offsetHeight;
    const sx = e.pageX;
    const sy = e.pageY;

    document.onmousemove = ev => {
      el.style.width  = Math.max(30, sw + ev.pageX - sx) + 'px';
      el.style.height = Math.max(20, sh + ev.pageY - sy) + 'px';
    };

    document.onmouseup = () => document.onmousemove = null;
  };
}

function select(el) {
  document.querySelectorAll('.field').forEach(f =>
    f.classList.remove('selected')
  );
  el.classList.add('selected');
  selected = el;
}

function setActivePage(page) {
  document.querySelectorAll('.page').forEach(p =>
    p.classList.remove('active')
  );
  page.classList.add('active');
  activePage = page;
}

document.addEventListener('keydown', e => {

  if (!selected) return;

  if (e.key === 'Backspace' || e.key === 'Delete') {
    e.preventDefault();
    selected.remove();
    selected = null;
  }

  if (e.ctrlKey && e.key === 'c') {
    copiedField = {
      type: selected.dataset.type,
      signer: selected.dataset.signer,
      width: selected.offsetWidth,
      height: selected.offsetHeight,
      html: selected.innerHTML
    };
  }

  if (e.ctrlKey && e.key === 'v') {
    if (!copiedField || !activePage) return;

    const f = {
      id: 'new_' + Date.now(),
      field_type: copiedField.type,
      signer_id: copiedField.signer,
      label: '',
      page_no: activePage.dataset.page,
      pos_x: parseInt(selected.style.left) + 10,
      pos_y: parseInt(selected.style.top) + 10,
      width: copiedField.width,
      height: copiedField.height
    };

    const el = createFieldEl(f, activePage);

    el.style.width  = copiedField.width  + 'px';
    el.style.height = copiedField.height + 'px';
  }
});

function saveFields() {
  const data = [];

  document.querySelectorAll('.page').forEach(page => {
    const pageNo = page.dataset.page;

    page.querySelectorAll('.field').forEach(el => {
      data.push({
        id: el.dataset.id,
        template_id: TEMPLATE_ID,
        signer_id: el.dataset.signer,
        field_type: el.dataset.type,
        label: el.dataset.type === 'CHECKBOX' ? '' : el.textContent,
        page_no: pageNo,
        pos_x: parseInt(el.style.left),
        pos_y: parseInt(el.style.top),
        width: parseInt(el.style.width),
        height: parseInt(el.style.height)
      });
    });
  });

  fetch('/easyj/api/templates/save_fields.php', {
    method:'POST',
    headers:{'Content-Type':'application/json'},
    body: JSON.stringify({ template_id:TEMPLATE_ID, fields:data })
  })
  .then(r=>r.json())
  .then(r=>alert(r.message || '저장 완료'));
}
</script>

</body>
</html>
