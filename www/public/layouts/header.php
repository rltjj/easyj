<?php
$userId = $_SESSION['user_id'];
$role = $_SESSION['role'];

$roleTextMap = [
  'ADMIN'    => '관리자',
  'OPERATOR' => '운영자',
  'STAFF'    => '직원'
];

$roleText = $roleTextMap[$role] ?? '';
?>

<header class="header">
  <?php if ($role === 'ADMIN' && !empty($sites)): ?>
    <form method="post" action="/easyj/api/site/change_site" class="site-select">
      <select name="site_id" onchange="this.form.submit()">
        <?php foreach ($sites as $site): ?>
          <option value="<?= $site['id'] ?>"
            <?= $site['id'] == $currentSiteId ? 'selected' : '' ?>>
            <?= htmlspecialchars($site['name']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </form>

  <?php elseif (!empty($sites)): ?>
    <div class="site-name">
      <?= htmlspecialchars($sites[0]['name']) ?>
    </div>
  <?php endif; ?>

  <div class="profile" id="profileBtn">
    <div class="circle"><?= mb_substr($roleText, 0, 1) ?></div>

    <div class="profile-popup" id="profilePopup">
      <p><strong><?= htmlspecialchars($_SESSION['name']) ?></strong></p>
      <p><?= $roleText ?></p>
      <?php if (!empty($_SESSION['email'])): ?>
        <p><?= htmlspecialchars($_SESSION['email']) ?></p>
      <?php endif; ?>
      <a href="/easyj/api/auth/logout">로그아웃</a>
    </div>
  </div>
</header>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const btn = document.getElementById('profileBtn');
  const popup = document.getElementById('profilePopup');

  btn.addEventListener('click', (e) => {
    e.stopPropagation();
    popup.classList.toggle('active');
  });

  document.addEventListener('click', () => {
    popup.classList.remove('active');
  });
});
</script>
