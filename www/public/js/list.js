let activeTemplateId = null;

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

  checkAll.addEventListener('change', e => {
    checks.forEach(c => c.checked = e.target.checked);
    updateCount();
  });

  window.search = function() {
    const k = document.getElementById('keyword').value;
    const category = document.querySelector('.toolbar select').value;
    location.href = `?category=${encodeURIComponent(category)}&keyword=${encodeURIComponent(k)}&limit=${limit}`;
  };

  window.setLimit = function(n) {
    const category = document.querySelector('.toolbar select').value;
    const keyword = document.getElementById('keyword').value;
    location.href = `?category=${encodeURIComponent(category)}&keyword=${encodeURIComponent(keyword)}&limit=${n}`;
  };

  window.openModal = () =>
    document.getElementById('templateModal').classList.add('show');

  window.closeModal = () =>
    document.getElementById('templateModal').classList.remove('show');

  const rows = document.querySelectorAll('#templateList tr');
  const actionBar = document.getElementById('templateActionBar');
  const editBtn = document.getElementById('editTemplateBtn');
  const favoriteBtn = document.getElementById('favoriteActionBtn');
  const trashBtn = document.getElementById('trashActionBtn');

  rows.forEach(row => {
    row.addEventListener('click', () => {
      const id = row.dataset.id;
      const isFavorite = row.dataset.favorite === '1';

      if (activeTemplateId === id) {
        activeTemplateId = null;
        actionBar.style.display = 'none';
        return;
      }

      activeTemplateId = id;
      row.insertAdjacentElement('afterend', actionBar);
      actionBar.style.display = 'table-row';

      favoriteBtn.textContent = isFavorite ? '즐겨찾기 해제' : '즐겨찾기';
    });
  });

  editBtn.addEventListener('click', () => {
    if (!activeTemplateId) return;
    location.href = `editor.php?id=${activeTemplateId}`;
  });

  favoriteBtn.addEventListener('click', () => {
    if (!activeTemplateId) return;

    toggleFavorite([activeTemplateId]);
  });


  trashBtn.addEventListener('click', () => {
    if (!activeTemplateId) return;
    if (!confirm('해당 템플릿을 휴지통으로 보내시겠습니까?')) return;

    moveToTrash([activeTemplateId]);
  });
});


function getCheckedIds() {
  return [...document.querySelectorAll('.row-check:checked')]
    .map(c => c.closest('tr').dataset.id);
}

document.getElementById('toggleFavoriteBtn').addEventListener('click', () => {
  const ids = getCheckedIds();
  if (!ids.length) return alert('선택된 템플릿이 없습니다.');

  toggleFavorite(ids);
});

document.getElementById('trashTemplateBtn').addEventListener('click', () => {
  const ids = getCheckedIds();
  if (!ids.length) return alert('선택된 템플릿이 없습니다.');
  if (!confirm('선택한 템플릿을 휴지통으로 이동하시겠습니까?')) return;

  moveToTrash(ids);
});


function toggleFavorite(ids) {
  fetch('/easyj/api/templates/toggle_favorite', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ ids })
  })
  .then(res => res.json())
  .then(r => r.success && location.reload());
}

function moveToTrash(ids) {
  fetch('/easyj/api/templates/toggle_trash', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ ids })
  })
  .then(() => location.reload());
}
