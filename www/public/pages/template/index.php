<?php
require_once __DIR__ . '/../../../../bootstrap.php';
require_once BASE_PATH . '/includes/site_context.php';
$activeMenu = 'templates';

$categoryId = intval($_GET['category_id'] ?? 0);
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
  t.is_favorite
FROM templates t
LEFT JOIN template_categories c ON t.category_id = c.id
WHERE t.site_id = :site_id
  AND t.is_deleted = 0
  AND (:category_id = 0 OR t.category_id = :category_id)
  AND t.title LIKE :keyword
ORDER BY t.sort_order ASC, t.id DESC
LIMIT :offset, :limit
";

$stmt = $pdo->prepare($sql);
$stmt->bindValue(':site_id', $currentSiteId, PDO::PARAM_INT);
$stmt->bindValue(':category_id', $categoryId, PDO::PARAM_INT);
$stmt->bindValue(':keyword', "%$keyword%");
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->execute();
$templates = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
          <option value="0">전체</option>
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

      <?php if ($role === 'ADMIN'): ?>
        <div class="top-action">
          <button onclick="openModal()">템플릿 등록</button>
        </div>
      <?php endif; ?>

      <?php include 'create.php'; ?>

      <div class="bulk-bar">
        선택 <span id="selectedCount">0</span>개
        <div class="right">
          <button id="toggleFavoriteBtn">즐겨찾기</button>
          <button id="trashTemplateBtn">휴지통</button>
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
          <tr data-id="<?= $t['id'] ?>" data-favorite="<?= $t['is_favorite'] ?>">
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
              <button id="editTemplateBtn">내용 확인 및 수정</button>
              <button id="favoriteActionBtn">즐겨찾기</button>
              <button id="trashActionBtn">휴지통</button>
            </td>
          </tr>
        </tbody>
      </table>
    </main>
  </div>

  <script src="../../js/list.js"></script>

</body>
</html>
