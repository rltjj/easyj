<?php

$categories = [];
$siteId = $_SESSION['current_site_id'] ?? null;

if ($siteId) {
    $stmt = $pdo->prepare("
        SELECT id, name
        FROM template_categories
        WHERE site_id = :site_id
          AND is_deleted = 0
        ORDER BY name ASC
    ");
    $stmt->execute([':site_id' => $siteId]);
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<div class="modal" id="templateModal">
  <div class="modal-content">

    <h2>템플릿 등록</h2>

    <form id="templateForm" method="post" enctype="multipart/form-data"
          action="create_action.php">

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

          <button type="button" class="add-category-btn"
            onclick="showCategoryInput()">+ 새 카테고리 추가</button>

          <div id="newCategoryBox" style="display:none;">
            <input type="text" id="newCategoryName" placeholder="카테고리명">
            <button type="button" onclick="addCategory()">추가</button>
          </div>
      </div>

      <div class="form-group">
        <label>서명자 설정</label><br>
        <!-- 서명자1 : 서명자 역할 드롭다운(현장관계자 SITE, 계약자 GUEST) -->
        <!-- 서명자2 : 서명자 역할 드롭다운(현장관계자 SITE, 계약자 GUEST) -->
        <!-- 현장관계자일 경우 상호명 설정 시행사상호 COMPANY, 모집주체상호 AGENCY  -->
        <!-- 서명자마다 첨부파일 설정 (필수/선택, 제목, 설명) + [추가] 버튼 -->
        <button type="button" class="add-signer-btn"
            onclick="showSignerInput()">+ 새 서명자 추가</button>
      </div>

      <div class="modal-actions">
        <button type="submit">등록</button>
        <button type="button" onclick="closeModal()">취소</button>
      </div>

    </form>

  </div>
</div>

<script>
function showCategoryInput() {
  document.getElementById('newCategoryBox').style.display = 'block';
}

function addCategory() {
  const name = document.getElementById('newCategoryName').value.trim();
  if (!name) return;

  fetch('/easyj/api/templates/add_category', {
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
</script>
