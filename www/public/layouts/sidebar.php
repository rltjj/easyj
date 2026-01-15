<?php
$role = $_SESSION['role'];
?>
<div class="wrapper">
  <aside class="sidebar">
    <ul class="menu">
      <div class="log"><a href="../contract/index.php"><img id="log" src="../../img/log.png" alt="이지조인 로고"></a></div>
      <li><a href="../contract/index.php">전자서명</a></li>
      <li><a href="../template/index.php">템플릿</a></li>
      <li><a href="../file/index.php">문서함</a></li>
      <li><a href="../mypage/index.php">마이페이지</a></li>
      <?php if ($role === 'ADMIN'): ?>
        <li class="admin">
          <a href="../admin/index.php">관리자</a>
        </li>
      <?php endif; ?>
    </ul>
  </aside>
</div>