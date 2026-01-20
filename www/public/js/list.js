let activeTemplateId = null;
let activeIsTrash = false;

window.openModal = function () {
  const modal = document.getElementById('templateModal');
  if (!modal) return;
  modal.classList.add('show');
};

window.closeModal = function () {
  const modal = document.getElementById('templateModal');
  if (!modal) return;
  modal.classList.remove('show');
};

document.addEventListener('DOMContentLoaded', () => {

  const checks = document.querySelectorAll('.row-check');
  const count = document.getElementById('selectedCount');
  const checkAll = document.getElementById('checkAll');

  const limit = parseInt(document.documentElement.dataset.limit, 10) || 10;

  function updateCount() {
    const checked = document.querySelectorAll('.row-check:checked').length;
    count.textContent = checked;
    checkAll.checked = checked === checks.length && checks.length > 0;
  }

  checks.forEach(c => {
    c.addEventListener('change', e => {
      e.stopPropagation();
      updateCount();
    });
  });

  if (checkAll) {
    checkAll.addEventListener('change', e => {
      checks.forEach(c => c.checked = e.target.checked);
      updateCount();
    });
  }

  window.search = function() {
    const k = document.getElementById('keyword').value;
    const categoryId = document.querySelector('.toolbar select').value;

    location.href =
      `?category_id=${encodeURIComponent(categoryId)}` +
      `&keyword=${encodeURIComponent(k)}` +
      `&limit=${limit}`;
  };

  window.setLimit = function(n) {
    const categoryId = document.querySelector('.toolbar select').value;
    const keyword = document.getElementById('keyword').value;

    location.href =
      `?category_id=${encodeURIComponent(categoryId)}` +
      `&keyword=${encodeURIComponent(keyword)}` +
      `&limit=${n}`;
  };

  const rows = document.querySelectorAll('#templateList tr');
  const actionBar = document.getElementById('templateActionBar');

  const editBtn = document.getElementById('editTemplateBtn');
  const signBtn = document.getElementById('signBtn');
  const favoriteBtn = document.getElementById('favoriteActionBtn');
  const trashBtn = document.getElementById('trashActionBtn');
  const deleteForeverBtn = document.getElementById('deleteForeverBtn');

  rows.forEach(row => {
    row.addEventListener('click', () => {
      const id = row.dataset.id;
      const isFavorite = row.dataset.favorite === '1';
      const isTrash = row.dataset.trash === '1';

      if (activeTemplateId === id) {
        activeTemplateId = null;
        actionBar.style.display = 'none';
        return;
      }

      activeTemplateId = id;
      activeIsTrash = isTrash;

      row.insertAdjacentElement('afterend', actionBar);
      actionBar.style.display = 'table-row';

      if (isTrash) {
        if (editBtn) editBtn.style.display = 'none';
        if (favoriteBtn) favoriteBtn.style.display = 'none';
        if (trashBtn) trashBtn.textContent = '복원하기';
        if (deleteForeverBtn) deleteForeverBtn.style.display = 'inline-block';
      } else {
        if (editBtn) editBtn.style.display = 'inline-block';
        if (favoriteBtn) {
          favoriteBtn.style.display = 'inline-block';
          favoriteBtn.textContent = isFavorite ? '즐겨찾기 해제' : '즐겨찾기';
        }
        if (trashBtn) trashBtn.textContent = '휴지통';
        if (deleteForeverBtn) deleteForeverBtn.style.display = 'none';
      }
    });
  });

  if (editBtn) {
    editBtn.addEventListener('click', () => {
      if (!activeTemplateId) return;
      location.href = `edit.php?id=${activeTemplateId}`;
    });
  }

  if (signBtn) {
    signBtn.addEventListener('click', (e) => {
      e.stopPropagation();
      if (!activeTemplateId) return;
      location.href = `setup.php?id=${activeTemplateId}`;
    });
  }

  if (favoriteBtn) {
    favoriteBtn.addEventListener('click', () => {
      if (!activeTemplateId) return;
      toggleFavorite([activeTemplateId]);
    });
  }

  if (trashBtn) {
    trashBtn.addEventListener('click', () => {
      if (!activeTemplateId) return;

      const message = activeIsTrash
        ? '이 템플릿을 복원하시겠습니까?'
        : '해당 템플릿을 휴지통으로 보내시겠습니까?';

      if (!confirm(message)) return;
      moveToTrash([activeTemplateId]);
    });
  }

  if (deleteForeverBtn) {
    deleteForeverBtn.addEventListener('click', () => {
      if (!activeTemplateId) return;

      if (!confirm(
        '⚠️ 이 템플릿을 완전히 삭제합니다.\n삭제 후에는 복구할 수 없습니다.'
      )) return;

      deleteForever([activeTemplateId]);
    });
  }
});

function toggleFavorite(ids) {
  fetch('/easyj/api/template/toggle_favorite', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ ids })
  })
  .then(res => res.json())
  .then(r => r.success && location.reload());
}

function moveToTrash(ids) {
  fetch('/easyj/api/template/toggle_trash', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ ids })
  })
  .then(() => location.reload());
}

function deleteForever(ids) {
  fetch('/easyj/api/template/delete_forever', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ ids })
  })
  .then(res => res.json())
  .then(r => r.success && location.reload());
}
