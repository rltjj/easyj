<?php
$siteId = $_SESSION['site_id'] ?? null;
$categories = [];

if ($siteId) {
    $stmt = $pdo->prepare("
        SELECT id, name
        FROM template_categories
        WHERE site_id = :site_id
          AND is_deleted = 0
        ORDER BY sort_order ASC, id ASC
    ");
    $stmt->execute([':site_id' => $siteId]);
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<div class="modal" id="templateModal">
  <div class="modal-content">

    <h2>템플릿 등록</h2>

    <form id="templateForm" enctype="multipart/form-data">

      <div class="form-group">
        <label>PDF 파일</label>
        <input type="file" name="pdf" accept="application/pdf" required>
      </div>

      <div class="form-group">
        <label>문서명</label>
        <input type="text" name="title" required>
      </div>

      <div class="form-group">
        <label>카테고리</label>
        <select name="category_id" required>
          <option value="">선택</option>
          <?php foreach ($categories as $cat): ?>
            <option value="<?= $cat['id'] ?>">
              <?= htmlspecialchars($cat['name']) ?>
            </option>
          <?php endforeach; ?>
        </select>

        <button type="button" onclick="showCategoryInput()">+ 새 카테고리</button>

        <div id="newCategoryBox" style="display:none;">
          <input type="text" id="newCategoryName" placeholder="카테고리명">
          <button type="button" onclick="addCategory()">추가</button>
        </div>
      </div>

      <hr>

      <h3>서명자 설정</h3>

      <div id="signerList"></div>

      <button type="button" onclick="addSigner()">+ 서명자 추가</button>

      <hr>

      <div class="modal-actions">
        <button type="submit">등록</button>
        <button type="button" onclick="closeModal()">취소</button>
      </div>

    </form>
  </div>
</div>

<template id="signerTemplate">
  <div class="signer-box">
    <h4>서명자 <span class="signer-index"></span></h4>

    <input type="hidden" name="signers[][order]" class="signer-order">

    <div class="form-group">
      <label>서명자 역할</label>
      <select name="signers[__INDEX__][role]" class="signer-role">
        <option value="SITE">현장관계자</option>
        <option value="GUEST">계약자</option>
      </select>
    </div>

    <div class="form-group company-select">
      <label>상호명</label>
      <select name="signers[__INDEX__][company_name]">
        <option value="COMPANY">시행사상호</option>
        <option value="AGENCY">모집주체상호</option>
      </select>
    </div>

    <div class="attachments"></div>

    <button type="button" onclick="addAttachment(this)">+ 첨부파일</button>
    <button type="button" onclick="removeSigner(this)">서명자 삭제</button>

    <hr>
  </div>
</template>

<template id="attachmentTemplate">
  <div class="attachment-box">
    <input type="hidden" name="attachments[][signer_order]" class="attachment-signer-order">

    <div class="form-group">
      <label>필수 여부</label>
      <select name="attachments[][required]">
        <option value="1">필수</option>
        <option value="0">선택</option>
      </select>
    </div>

    <div class="form-group">
      <label>제목</label>
      <input type="text" name="signers[__INDEX__][attachments][][title]">
    </div>

    <div class="form-group">
      <label>설명</label>
      <input type="text" name="signers[__INDEX__][attachments][][description]">
    </div>

    <button type="button" onclick="removeAttachment(this)">삭제</button>
  </div>
</template>

<script>

function addSigner() {
  const signerList = document.getElementById('signerList');

  const index = signerList.querySelectorAll('.signer-box').length;

  const tpl = document.getElementById('signerTemplate')
    .innerHTML.replaceAll('__INDEX__', index);

  const wrapper = document.createElement('div');
  wrapper.innerHTML = tpl;

  const box = wrapper.querySelector('.signer-box');
  const roleSelect = box.querySelector('.signer-role');
  const companyBox = box.querySelector('.company-select');
  const companySelect = companyBox.querySelector('select');

  companyBox.style.display = roleSelect.value === 'SITE' ? 'block' : 'none';

  roleSelect.addEventListener('change', () => {
    if (roleSelect.value === 'SITE') {
      companyBox.style.display = 'block';
    } else {
      companyBox.style.display = 'none';
      companySelect.value = '';
    }
  });

  box.querySelector('.signer-index').textContent = index + 1;
  box.querySelector('.signer-order').value = index + 1;

  signerList.appendChild(wrapper);
}

function removeSigner(btn) {
  btn.closest('.signer-box').remove();
  reorderSigners();
}

function reorderSigners() {
  const signers = document.querySelectorAll('.signer-box');

  signers.forEach((box, index) => {
    const order = index + 1;
    box.querySelector('.signer-index').textContent = order;
    box.querySelector('.signer-order').value = order;

    box.querySelectorAll('.attachment-signer-order').forEach(input => {
      input.value = order;
    });
  });
}

function addAttachment(btn) {
  const signerBox = btn.closest('.signer-box');
  const signerIndex = [...document.querySelectorAll('.signer-box')].indexOf(signerBox);

  const tpl = document.getElementById('attachmentTemplate')
    .innerHTML.replaceAll('__INDEX__', signerIndex);

  const wrapper = document.createElement('div');
  wrapper.innerHTML = tpl;

  signerBox.querySelector('.attachments').appendChild(wrapper);
}

function removeAttachment(btn) {
  btn.closest('.attachment-box').remove();
}

function showCategoryInput() {
  document.getElementById('newCategoryBox').style.display = 'block';
}

function addCategory() {
  const name = document.getElementById('newCategoryName').value.trim();
  if (!name) return;

  fetch('/easyj/api/template/add_category', {
    method: 'POST',
    headers: {'Content-Type':'application/json'},
    body: JSON.stringify({ name })
  })
  .then(r => r.json())
  .then(res => {
    if (res.success) {
      const select = document.querySelector('select[name="category_id"]');
      const opt = document.createElement('option');
      opt.value = res.id;
      opt.textContent = res.name;
      opt.selected = true;
      select.appendChild(opt);
      document.getElementById('newCategoryBox').style.display = 'none';
    } else {
      alert(res.message);
    }
  });
}

document.getElementById('templateForm').addEventListener('submit', e => {
  e.preventDefault();
  const formData = new FormData(e.target);

  fetch('/easyj/api/template/create_template', {
    method: 'POST',
    body: formData
  })
  .then(r => r.json())
  .then(res => {
    if (res.success) {
      location.reload();
    } else {
      alert(res.message);
    }
  });
});

</script>
