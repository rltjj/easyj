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
  font-size:22px;
  font-weight:bold;
  line-height:1;
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
.select-box {
  position:absolute;
  border:1px dashed #4f46e5;
  background:rgba(79,70,229,.15);
  pointer-events:none;
  z-index:999;
}
.save-btn {
  position:fixed;
  bottom:20px;
  left:20px;
  padding:10px 20px;
}

.prop-panel {
  width:260px;
  background:#fafafa;
  border-left:1px solid #ddd;
  padding:10px;
  overflow:auto;
}

.prop-panel h3 {
  margin-top:0;
}

.prop-panel label {
  display:block;
  margin-bottom:10px;
  font-size:13px;
}

.prop-panel input,
.prop-panel select {
  width:100%;
  padding:4px;
  box-sizing:border-box;
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
    <button onclick="addField('DATE_Y', <?= $s['id'] ?>)">날짜(연)</button>
    <button onclick="addField('DATE_M', <?= $s['id'] ?>)">날짜(월)</button>
    <button onclick="addField('DATE_D', <?= $s['id'] ?>)">날짜(일)</button>
    <hr>
  <?php endforeach; ?>
</div>

<div class="editor" id="pdfWrap"></div>

<div class="prop-panel" id="propPanel">
  <h3>필드 설정</h3>

  <div id="noSelect">필드를 선택하세요</div>

  <div id="fieldProps" style="display:none;">
    <label>
      라벨
      <input type="text" id="propLabel">
    </label>

    <label>
      필수 여부
      <select id="propRequired">
        <option value="0">선택</option>
        <option value="1">필수</option>
      </select>
    </label>

    <div id="checkboxProps" style="display:none;">
      <label>
        그룹 라벨
        <input type="text" id="propGroup">
      </label>

      <label>
        최소 선택
        <input type="number" id="propMin" min="0">
      </label>

      <label>
        최대 선택
        <input type="number" id="propMax" min="1">
      </label>
    </div>

    <div id="textProps" style="display:none;">
      <label>
        폰트 크기
        <input type="number" id="propFontSize" min="8" max="48">
      </label>

      <label>
        정렬
        <select id="propAlign">
          <option value="left">왼쪽</option>
          <option value="center">가운데</option>
          <option value="right">오른쪽</option>
        </select>
      </label>
    </div>
  </div>
</div>

<button class="save-btn" onclick="saveFields()">💾 저장</button>

<script>
const PDF_URL = "<?= htmlspecialchars($pdfUrl) ?>";
const TEMPLATE_ID = <?= $templateId ?>;
const EXIST_FIELDS = <?= json_encode($fields, JSON_UNESCAPED_UNICODE) ?>;
const GRID = 5;

const propLabel    = document.getElementById('propLabel');
const propRequired = document.getElementById('propRequired');
const propGroup    = document.getElementById('propGroup');
const propMin      = document.getElementById('propMin');
const propMax      = document.getElementById('propMax');
const propFontSize = document.getElementById('propFontSize');
const propAlign    = document.getElementById('propAlign');

propLabel.oninput = () => {
  selectedFields.forEach(el => {
    el.dataset.label = propLabel.value;
    if (el.dataset.type !== 'CHECKBOX') {
      el.textContent = propLabel.value;
    }
  });
};

propRequired.onchange = () => {
  selectedFields.forEach(el => {
    el.dataset.required = propRequired.value;
  });
};

propGroup.oninput = () => {
  selectedFields.forEach(el => {
    el.dataset.group = propGroup.value;
  });
};

propMin.oninput = () => {
  selectedFields.forEach(el => {
    el.dataset.min = propMin.value;
  });
};

propMax.oninput = () => {
  selectedFields.forEach(el => {
    el.dataset.max = propMax.value;
  });
};

propFontSize.oninput = () => {
  selectedFields.forEach(el => {
    el.dataset.fontSize = propFontSize.value;
    el.style.fontSize = propFontSize.value + 'px';
  });
};

propAlign.onchange = () => {
  selectedFields.forEach(el => {
    el.dataset.align = propAlign.value;
    el.style.textAlign = propAlign.value;
  });
};

pdfjsLib.GlobalWorkerOptions.workerSrc =
  "/easyj/assets/pdfjs/pdf.worker.min.js";

let pages = [];
let activePage = null;
let selectedFields = [];
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

  let width = 60;
  let height = 30;
  let label = type;

  if (type === 'CHECKBOX') {
    width = 22;
    height = 22;
    label = '';
  }

  if (type === 'DATE_Y') {
    width = 70;
    label = 'YYYY';
  }

  if (type === 'DATE_M') {
    width = 45;
    label = 'MM';
  }

  if (type === 'DATE_D') {
    width = 45;
    label = 'DD';
  }

  const field = {
    id: 'new_' + Date.now(),
    field_type: type,
    signer_id: signerId,
    label,
    page_no: activePage.dataset.page,
    pos_x: 50,
    pos_y: 50,
    width,
    height,
    font_size: 12,
    text_align: 'left'
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
  el.dataset.label = f.label || '';

  el.style.left = f.pos_x + 'px';
  el.style.top  = f.pos_y + 'px';
  el.style.width = f.width + 'px';
  el.style.height = f.height + 'px';
  el.style.textAlign = 'center';

  el.dataset.required = f.required ?? '0';
  el.dataset.group = f.group_label ?? '';
  el.dataset.min = f.min_select ?? '';
  el.dataset.max = f.max_select ?? '';
  el.dataset.fontSize = f.font_size ?? '12';
  el.style.fontSize = el.dataset.fontSize + 'px';
  el.dataset.align = f.text_align ?? '';

  if (f.field_type === 'CHECKBOX') {
    el.classList.add('checkbox');
    el.innerHTML = '□';

  } else if (
    f.field_type === 'DATE_Y' ||
    f.field_type === 'DATE_M' ||
    f.field_type === 'DATE_D'
  ) {
    el.textContent = f.label;
    el.style.fontSize = '12px';
    el.style.background = '#fdfdfd';

    const resize = document.createElement('div');
    resize.className = 'resize';
    el.appendChild(resize);
    makeResizable(el, resize, page);

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
    select(el, e.ctrlKey);
  };

  page.appendChild(el);
  return el;
}

function makeDraggable(el, container) {
  let ox, oy, down = false;
  let startPositions = [];

  function move(e) {
    if (!down) return;

    const r = container.getBoundingClientRect();
    const dx = Math.round((e.clientX - r.left - ox) / GRID) * GRID;
    const dy = Math.round((e.clientY - r.top  - oy) / GRID) * GRID;

    startPositions.forEach(p => {
      p.el.style.left = (p.x + dx) + 'px';
      p.el.style.top  = (p.y + dy) + 'px';
    });
  }

  function up() {
    down = false;
    document.removeEventListener('mousemove', move);
    document.removeEventListener('mouseup', up);
  }

  el.addEventListener('mousedown', e => {
    if (e.target.classList.contains('resize')) return;

    down = true;
    select(el, e.ctrlKey);

    const r = container.getBoundingClientRect();
    ox = e.clientX - r.left;
    oy = e.clientY - r.top;

    startPositions = selectedFields.map(f => ({
      el: f,
      x: parseInt(f.style.left),
      y: parseInt(f.style.top)
    }));

    document.addEventListener('mousemove', move);
    document.addEventListener('mouseup', up);
  });
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

function select(el, multi = false) {
  if (!multi) {
    document.querySelectorAll('.field').forEach(f =>
      f.classList.remove('selected')
    );
    selectedFields = [];
  }

  if (!selectedFields.includes(el)) {
    el.classList.add('selected');
    selectedFields.push(el);
  }

  showProps(el);
}

function showProps(el) {
  document.getElementById('noSelect').style.display = 'none';
  document.getElementById('fieldProps').style.display = 'block';

  document.getElementById('propLabel').value = el.dataset.label || '';
  document.getElementById('propRequired').value = el.dataset.required || '0';

  const type = el.dataset.type;

  document.getElementById('checkboxProps').style.display =
    type === 'CHECKBOX' ? 'block' : 'none';

  if (type === 'CHECKBOX') {
    propGroup.value = el.dataset.group || '';
    propMin.value = el.dataset.min || '';
    propMax.value = el.dataset.max || '';
  }

  document.getElementById('textProps').style.display =
    type === 'TEXT' ? 'block' : 'none';

  if (type === 'TEXT') {
    propFontSize.value = el.dataset.fontSize || '12';
    propAlign.value = el.dataset.align || 'left';
  }
}


function setActivePage(page) {
  document.querySelectorAll('.page').forEach(p =>
    p.classList.remove('active')
  );
  page.classList.add('active');
  activePage = page;
}

document.addEventListener('keydown', e => {

  if (!selectedFields.length) return;

  const step = e.shiftKey ? 10 : 1;
  let moved = false;

  selectedFields.forEach(el => {
    let x = parseInt(el.style.left);
    let y = parseInt(el.style.top);

    switch (e.key) {
      case 'ArrowLeft':  x -= step; break;
      case 'ArrowRight': x += step; break;
      case 'ArrowUp':    y -= step; break;
      case 'ArrowDown':  y += step; break;
    }

    el.style.left = x + 'px';
    el.style.top  = y + 'px';
  });

  if (e.key === 'Backspace' || e.key === 'Delete') {
    e.preventDefault();
    selectedFields.forEach(el => el.remove());
    selectedFields = [];
  }

  if (e.ctrlKey && e.key === 'c') {
    if (!selectedFields.length) return;
    const base = selectedFields[0];

    copiedField = {
      type: base.dataset.type,
      signer: base.dataset.signer,
      width: base.offsetWidth,
      height: base.offsetHeight
    };
  }

  if (e.ctrlKey && e.key === 'v') {
    const base = selectedFields[0];

    if (!copiedField || !activePage) return;

    const f = {
      id: 'new_' + Date.now(),
      field_type: copiedField.type,
      signer_id: copiedField.signer,
      label: '',
      page_no: activePage.dataset.page,
      pos_x: parseInt(base.style.left) + 10,
      pos_y: parseInt(base.style.top) + 10,
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
        height: parseInt(el.style.height),
        required: el.dataset.required ?? 0,
        group_label: el.dataset.group ?? '',
        min_select: el.dataset.min ?? null,
        max_select: el.dataset.max ?? null,
        font_size: el.dataset.fontSize ?? null,
        text_align: el.dataset.align ?? 'left'
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

let selectBox = null;
let startX = 0;
let startY = 0;

document.getElementById('pdfWrap').addEventListener('mousedown', e => {
  if (!activePage) return;
  if (e.target.closest('.field')) return;

  const pageRect = activePage.getBoundingClientRect();

  startX = e.clientX - pageRect.left;
  startY = e.clientY - pageRect.top;

  selectBox = document.createElement('div');
  selectBox.className = 'select-box';
  selectBox.style.left = startX + 'px';
  selectBox.style.top  = startY + 'px';
  selectBox.style.width = '0px';
  selectBox.style.height = '0px';

  activePage.appendChild(selectBox);

  function onMove(ev) {
    const x = ev.clientX - pageRect.left;
    const y = ev.clientY - pageRect.top;

    const left   = Math.min(x, startX);
    const top    = Math.min(y, startY);
    const width  = Math.abs(x - startX);
    const height = Math.abs(y - startY);

    selectBox.style.left   = left + 'px';
    selectBox.style.top    = top + 'px';
    selectBox.style.width  = width + 'px';
    selectBox.style.height = height + 'px';
  }

  function onUp() {
    document.removeEventListener('mousemove', onMove);
    document.removeEventListener('mouseup', onUp);

    const boxRect = selectBox.getBoundingClientRect();
    selectBox.remove();

    document.querySelectorAll('.field').forEach(f => {
      if (f.parentElement !== activePage) return;

      const fr = f.getBoundingClientRect();
      const hit =
        fr.left   < boxRect.right &&
        fr.right  > boxRect.left &&
        fr.top    < boxRect.bottom &&
        fr.bottom > boxRect.top;

      if (hit) {
        f.classList.add('selected');
        if (!selectedFields.includes(f)) {
          selectedFields.push(f);
        }
      }
    });
  }

  document.addEventListener('mousemove', onMove);
  document.addEventListener('mouseup', onUp);
});
</script>

</body>
</html>
