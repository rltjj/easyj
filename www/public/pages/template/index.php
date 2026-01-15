<?php
require_once __DIR__ . '/../../../../bootstrap.php';
require_once BASE_PATH . '/includes/site_context.php';
?>
<!DOCTYPE html>
<html lang="ko">
<head>
  <meta charset="UTF-8">
  <title>템플릿</title>
  <link rel="stylesheet" href="../../css/layout.css">
</head>
<body>
  <div class="sidebar">
    <?php require_once PUBLIC_PATH . '/layouts/sidebar.php'; ?>
  </div>

  <div class="main-area">
    <div class="header">
      <?php require_once PUBLIC_PATH . '/layouts/header.php'; ?>
    </div>

    <main class="content">
      템플릿
    </main>
  </div>

</body>
</html>
