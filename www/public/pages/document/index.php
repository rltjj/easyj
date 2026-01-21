<?php
require_once __DIR__ . '/../../../../bootstrap.php';
require_once BASE_PATH . '/includes/site_context.php';

$activeMenu = 'document';

if (!isset($_SESSION['user_id'])) {
    die('로그인이 필요합니다.');
}

$stmt = $pdo->prepare("
    SELECT
        c.id,
        c.title,
        c.status,
        c.updated_at,
        GROUP_CONCAT(
            cs.display_name
            ORDER BY cs.signer_order
            SEPARATOR ', '
        ) AS signer_names
    FROM contracts c
    LEFT JOIN contract_signers cs
        ON cs.contract_id = c.id
    WHERE c.site_id = :site_id
      AND c.is_deleted = 0
    GROUP BY c.id
    ORDER BY c.updated_at DESC
");
$stmt->execute([':site_id' => $currentSiteId]);
$documents = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="ko">
<head>
  <meta charset="UTF-8">
  <title>문서함</title>
  <link rel="stylesheet" href="../../css/layout.css">
  <link rel="stylesheet" href="../../css/document.css">
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
      <table class="document-table">
        <thead>
          <tr>
            <th style="width:120px;">상태</th>
            <th>문서 제목</th>
            <th>서명자</th>
            <th style="width:140px;">마지막 업데이트</th>
          </tr>
        </thead>
        <tbody>

        <?php if (empty($documents)): ?>
          <tr>
            <td colspan="4">등록된 문서가 없습니다.</td>
          </tr>
        <?php endif; ?>

        <?php foreach ($documents as $doc): ?>
          <tr onclick="location.href='/public/pages/contract/view.php?id=<?= $doc['id'] ?>'">
            <td>
              <span class="status <?= strtolower($doc['status']) ?>">
                <?= htmlspecialchars($doc['status']) ?>
              </span>
            </td>
            <td><?= htmlspecialchars($doc['title']) ?></td>
            <td><?= htmlspecialchars($doc['signer_names'] ?? '-') ?></td>
            <td><?= date('Y-m-d', strtotime($doc['updated_at'])) ?></td>
          </tr>
        <?php endforeach; ?>

        </tbody>
      </table>

    </main>
  </div>

</body>
</html>
