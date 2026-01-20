<?php
require_once __DIR__ . '/../../../../bootstrap.php';
require_once BASE_PATH . '/includes/site_context.php';
$activeMenu = 'contract';

$categoryId = intval($_GET['category_id'] ?? 0);
$isTrash = ($categoryId === -1);

$keyword  = $_GET['keyword'] ?? '';
$limit    = intval($_GET['limit'] ?? 10);
$page     = max(1, intval($_GET['page'] ?? 1));
$offset   = ($page - 1) * $limit;

$catStmt = $pdo->prepare("
    SELECT id, name
    FROM template_categories
    WHERE site_id = :site_id
      AND is_deleted = 0
    ORDER BY name ASC
");
$catStmt->execute([':site_id' => $currentSiteId]);
$categories = $catStmt->fetchAll(PDO::FETCH_ASSOC);

$sql = "
SELECT 
  t.id,
  t.title,
  c.name AS category,
  t.created_at,
  t.is_favorite,
  t.is_deleted
FROM templates t
LEFT JOIN template_categories c ON t.category_id = c.id
WHERE t.site_id = :site_id
  AND (
    (:is_trash = 1 AND t.is_deleted = 1)
    OR
    (:is_trash = 0 AND t.is_deleted = 0)
  )
  AND (
    :category_id = 0
    OR :is_trash = 1
    OR t.category_id = :category_id
  )
    AND (
    :keyword = ''
    OR t.title LIKE :keyword
  )
      AND t.is_favorite = 1
ORDER BY t.sort_order ASC, t.id DESC
LIMIT :offset, :limit
";

$stmt = $pdo->prepare($sql);
$stmt->bindValue(':site_id', $currentSiteId, PDO::PARAM_INT);
$stmt->bindValue(':category_id', $categoryId, PDO::PARAM_INT);
$stmt->bindValue(':is_trash', $isTrash ? 1 : 0, PDO::PARAM_INT);
$stmt->bindValue(':keyword', $keyword !== '' ? "%$keyword%" : '');
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->execute();

$templates = $stmt->fetchAll(PDO::FETCH_ASSOC);

$currentServiceStatus = $currentSite['service_status'] ?? 'INACTIVE';

?>
<!DOCTYPE html>
<html lang="ko">
<head>
  <meta charset="UTF-8">
  <title>템플릿</title>
  <link rel="stylesheet" href="../../css/layout.css">
  <link rel="stylesheet" href="../../css/template.css">
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
      <div class="toolbar">
        <select onchange="search()">
          <option value="0" <?= $categoryId === 0 ? 'selected' : '' ?>>전체</option>

          <?php foreach ($categories as $cat): ?>
            <option value="<?= (int)$cat['id'] ?>"
              <?= $categoryId === (int)$cat['id'] ? 'selected' : '' ?>>
              <?= htmlspecialchars($cat['name']) ?>
            </option>
          <?php endforeach; ?>
        </select>

        <input type="text" id="keyword" placeholder="제목 검색" value="<?= htmlspecialchars($keyword) ?>">
        <button onclick="search()">검색</button>

        <div class="right">
          <button onclick="setLimit(10)">10개씩</button>
          <button onclick="setLimit(20)">20개씩</button>
        </div>
      </div>

      <table class="template-table">
        <thead>
          <tr>
            <th><input type="checkbox" id="checkAll"></th>
            <th>템플릿 제목</th>
            <th>카테고리</th>
            <th>등록일</th>
          </tr>
        </thead>

        <tbody id="templateList">
          <?php foreach ($templates as $t): ?>
          <tr data-id="<?= $t['id'] ?>" data-favorite="<?= $t['is_favorite'] ?>" data-trash="<?= $t['is_deleted'] ?>" >
            <td><input type="checkbox" class="row-check"></td>
            <td class="title"><?= htmlspecialchars($t['title']) ?></td>
            <td><?= htmlspecialchars($t['category']) ?></td>
            <td><?= date('Y-m-d', strtotime($t['created_at'])) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>

        <tbody>
          <tr id="templateActionBar" style="display:none;">
            <td colspan="4" class="action-bar">
              <?php if ($siteServiceEnabled === 1): ?>
              <button id="signBtn">서명하기</button>
              <?php else: ?>
              <p>활성화되지 않은 현장입니다. 관리자에게 문의하십시오.</p>
              <?php endif; ?>
            </td>
          </tr>
        </tbody>
      </table>
    </main>
  </div>

  <script src="../../js/list.js"></script>

</body>
</html>
