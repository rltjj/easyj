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
$fields = array_map(function ($f) {
  return [
    'id'          => $f['id'],
    'template_id' => $f['template_id'],
    'page_no'     => $f['page_no'],
    'signer_order'=> $f['signer_order'],
    'field_type'  => $f['field_type'],
    'pos_x'       => $f['x'],
    'pos_y'       => $f['y'],
    'width'       => $f['width'],
    'height'      => $f['height'],
    'label'       => $f['label'],
    'required'    => $f['required'],
    'group_label' => $f['ch_plural'],
    'min_select'  => $f['ch_min'],
    'max_select'  => $f['ch_max'],
    'font_size'   => $f['t_size'],
    'text_align'  => $f['t_array'],
  ];
}, $fields);
?>
<!DOCTYPE html>
<html lang="ko">
<head>
<meta charset="UTF-8">
<title>템플릿 편집</title>

<script src="/easyj/assets/pdfjs/pdf.min.js"></script>

<style>
body {
    margin: 0;
    display: flex;
    height: 100vh;
    font-family: 'Pretendard', sans-serif;
    background-color: #e2e8f0;
    color: #334155;
}

.sidebar {
    width: 240px;
    background: #ffffff;
    border-right: 1px solid #cbd5e1;
    padding: 20px 15px;
    overflow-y: auto;
    box-shadow: 2px 0 10px rgba(0,0,0,0.05);
}

.sidebar h2 {
    font-size: 18px;
    margin-bottom: 20px;
    color: #0f172a;
    border-bottom: 2px solid #0858F7;
    padding-bottom: 10px;
}

.sidebar h3 {
    font-size: 14px;
    margin: 15px 0 8px;
    color: #64748b;
}

.sidebar button {
    width: 100%;
    margin-bottom: 6px;
    padding: 10px;
    border: 1px solid #e2e8f0;
    background: #f8fafc;
    border-radius: 6px;
    text-align: left;
    font-size: 13px;
    cursor: pointer;
    transition: all 0.2s;
}

.sidebar button:hover {
    background: #4f46e5;
    color: #fff;
    border-color: #4f46e5;
    transform: translateX(4px);
}

.editor {
    flex: 1;
    overflow: auto;
    padding: 40px;
    display: flex;
    flex-direction: column;
    align-items: center;
}

.page {
    position: relative;
    background: #ffffff;
    box-shadow: 0 10px 25px rgba(0,0,0,0.1);
    margin-bottom: 40px;
    border-radius: 4px;
}

.page.active {
    outline: 3px solid #0858F7;
}

.field {
    position: absolute;
    border: 1px solid #4f46e5;
    background: rgba(79, 70, 229, 0.1);
    font-size: 11px;
    color: #000000;
    font-weight: 600;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: move;
    box-sizing: border-box;
    border-radius: 2px;
}

.field.selected {
    outline: 2px solid #ef4444;
    background: rgba(239, 68, 68, 0.1);
    z-index: 10;
}

.resize {
    position: absolute;
    width: 8px;
    height: 8px;
    right: -4px;
    bottom: -4px;
    background: #4f46e5;
    border-radius: 50%;
    cursor: se-resize;
}

.prop-panel {
    width: 280px;
    background: #ffffff;
    border-left: 1px solid #cbd5e1;
    padding: 20px;
    overflow-y: auto;
    box-shadow: -2px 0 10px rgba(0,0,0,0.05);
}

.prop-panel h3 {
    font-size: 16px;
    margin-bottom: 20px;
    color: #1e293b;
}

.prop-panel label {
    display: block;
    margin-bottom: 15px;
    font-size: 13px;
    font-weight: 500;
    color: #475569;
}

.prop-panel input,
.prop-panel select {
    width: 100%;
    padding: 10px;
    margin-top: 5px;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 14px;
    outline: none;
}

.prop-panel input:focus {
    border-color: #4f46e5;
    ring: 2px #4f46e5;
}

.save-btn {
    position: fixed;
    bottom: 30px;
    right: 30px;
    padding: 12px 24px;
    background: #0f172a;
    color: #fff;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    z-index: 1000;
}

.save-btn:hover {
    transform: translateY(-2px);
    background: #1e293b;
    box-shadow: 0 6px 15px rgba(0,0,0,0.3);
}

#noSelect {
    text-align: center;
    color: #94a3b8;
    margin-top: 50px;
    font-style: italic;
}

.select-box {
    position: absolute;
    border: 1px dashed #0858F7;
    background: rgba(8, 88, 247, 0.15);
    pointer-events: none;
    z-index: 999;
}

</style>
</head>

<body>

<div class="sidebar">
  <h2>서명자</h2>

  <?php foreach ($signers as $s): ?>
    <h3>서명자 <?= $s['signer_order'] ?></h3>
    <button onclick="addField('TEXT', <?= $s['signer_order'] ?>)">텍스트</button>
    <button onclick="addField('CHECKBOX', <?= $s['signer_order'] ?>)">체크박스</button>
    <button onclick="addField('SIGN', <?= $s['signer_order'] ?>)">서명</button>
    <button onclick="addField('STAMP', <?= $s['signer_order'] ?>)">날인</button>
    <button onclick="addField('NUM', <?= $s['signer_order'] ?>)">숫자</button>
    <button onclick="addField('DATE_Y', <?= $s['signer_order'] ?>)">날짜(연)</button>
    <button onclick="addField('DATE_M', <?= $s['signer_order'] ?>)">날짜(월)</button>
    <button onclick="addField('DATE_D', <?= $s['signer_order'] ?>)">날짜(일)</button>
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
          <option value="LEFT">왼쪽</option>
          <option value="CENTER">가운데</option>
          <option value="RIGHT">오른쪽</option>
          <option value="BOTH">양쪽 정렬</option>
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

const SIGNER_COLORS = {
  1: { bg: '#FEF3C7', border: '#f5d20b' }, 
  2: { bg: '#DBEAFE', border: '#5774f5' },
  3: { bg: '#ebffdd', border: '#9af65c' },
  4: { bg: '#ffe0e0', border: '#ff4d4d' },
  5: { bg: '#ebd6ff', border: '#a94dff' },
  6: { bg: '#cfccf8', border: '#2617ff' },
  7: { bg: '#fcdaf1', border: '#f02aae' },
  8: { bg: '#ffffe5', border: '#f6fa21' },
};

const SIGNERS = <?= json_encode($signers, JSON_UNESCAPED_UNICODE) ?>;

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

function addField(type, signerOrder) {
  if (!activePage) {
    alert('페이지를 먼저 선택하세요');
    return;
  }

  let width = 60;
  let height = 30;
  let label = type;
  let field_type = type;

  if (type === 'CHECKBOX') {
    width = 22;
    height = 22;
    label = 'CHECKBOX';
  }

  if (type === 'NUM') {
    width = 80;
    height = 30;
    label = 'NUM';
    field_type = 'NUM';
  }

  if (type === 'DATE_Y') {
    width = 70;
    label = 'YYYY';
    field_type = 'DATE';
  }

  if (type === 'DATE_M') {
    width = 45;
    label = 'MM';
    field_type = 'DATE';
  }

  if (type === 'DATE_D') {
    width = 45;
    label = 'DD';
    field_type = 'DATE';
  }

  const field = {
    id: 'new_' + Date.now(),
    field_type: type,
    signer_order: signerOrder,
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
  el.dataset.signerOrder = f.signer_order;
  el.dataset.page = f.page_no;
  el.dataset.label = f.label || '';
  el.dataset.fontSize = f.font_size || 12;

  const order = parseInt(f.signer_order) || 1;
  const color = SIGNER_COLORS[order] || { bg: '#ffffff', border: '#888' };
  el.style.background = color.bg;
  el.style.borderColor = color.border;

  el.style.left = f.pos_x + 'px';
  el.style.top  = f.pos_y + 'px';
  el.style.width = f.width + 'px';
  el.style.height = f.height + 'px';
  el.style.textAlign = 'center';
  el.style.fontSize = (f.font_size || 12) + 'px';
  el.dataset.align = f.text_align || 'left';
  el.dataset.required = f.required ?? '0';
  el.dataset.group = f.group_label ?? '';
  el.dataset.min = f.min_select ?? '';
  el.dataset.max = f.max_select ?? '';

  if (f.field_type === 'CHECKBOX') {
    el.classList.add('checkbox');
    el.innerHTML = '□';
    el.dataset.type = 'CHECKBOX';
  } else if (f.field_type === 'NUM') {
  el.textContent = f.label || '0';
  el.style.fontSize = '12px';

  const resize = document.createElement('div');
  resize.className = 'resize';
  el.appendChild(resize);
  makeResizable(el, resize, page);
} else if (
    f.field_type === 'DATE_Y' ||
    f.field_type === 'DATE_M' ||
    f.field_type === 'DATE_D'
  ) {
    el.textContent = f.label;
    el.style.fontSize = '12px';

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
  const tag = document.activeElement.tagName;
  if (tag === 'INPUT' || tag === 'TEXTAREA' || tag === 'SELECT') {
    return;
  }

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
      field_type: base.dataset.type,
      signer_order: base.dataset.signerOrder,
      label: base.dataset.label,
      required: base.dataset.required,
      group_label: base.dataset.group,
      min_select: base.dataset.min,
      max_select: base.dataset.max,
      font_size: base.dataset.fontSize,
      text_align: base.dataset.align,
      width: base.offsetWidth,
      height: base.offsetHeight
    };
  }

  if (e.ctrlKey && e.key === 'v') {
    const base = selectedFields[0];

    if (!copiedField || !activePage) return;

    const f = {
      id: 'new_' + Date.now(),
      field_type: copiedField.field_type,
      signer_order: copiedField.signer_order,
      label: copiedField.label,
      required: copiedField.required,
      group_label: copiedField.group_label,
      min_select: copiedField.min_select,
      max_select: copiedField.max_select,
      font_size: copiedField.font_size,
      text_align: copiedField.text_align,
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
        signer_order: el.dataset.signerOrder,
        field_type: el.dataset.type, 
        label: el.dataset.label,
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

  fetch('/easyj/api/template/save_fields', {
    method:'POST',
    headers:{'Content-Type':'application/json'},
    body: JSON.stringify({ template_id:TEMPLATE_ID, fields:data })
  })
  .then(r=>r.json())
  .then(r => {
    alert(r.message || '저장 완료');
    location.href = 'index.php';
  });
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
