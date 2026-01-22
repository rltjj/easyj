<?php
require_once __DIR__ . '/../../../../bootstrap.php';
require_once BASE_PATH . '/includes/site_context.php';

$activeMenu = 'document';

if (!isset($_SESSION['user_id'])) {
    die('로그인이 필요합니다.');
}

$categories = $pdo->prepare("
  SELECT id, name
  FROM contract_categories
  WHERE site_id = :site_id
  ORDER BY sort_order ASC
");
$categories->execute([':site_id' => $currentSiteId]);
$categories = $categories->fetchAll();

$templates = $pdo->prepare("
  SELECT id, title
  FROM contract_template
  WHERE site_id = :site_id
  ORDER BY sort_order ASC, id DESC
");
$templates->execute([':site_id' => $currentSiteId]);
$templates = $templates->fetchAll();


$where = [];
$params = [':site_id' => $currentSiteId];

if (!empty($_GET['category_id'])) {
    $where[] = 'c.category_id = :category_id';
    $params[':category_id'] = (int)$_GET['category_id'];
}

if (!empty($_GET['contract_template_id'])) {
    $where[] = 'c.contract_template_id = :contract_template_id';
    $params[':contract_template_id'] = (int)$_GET['contract_template_id'];
}

if (!empty($_GET['title'])) {
    $where[] = 'c.title LIKE :title';
    $params[':title'] = '%' . $_GET['title'] . '%';
}

if (!empty($_GET['complex'])) {
    $where[] = 'c.complex LIKE :complex';
    $params[':complex'] = '%' . $_GET['complex'] . '%';
}

if (!empty($_GET['building'])) {
    $where[] = 'c.building LIKE :building';
    $params[':building'] = '%' . $_GET['building'] . '%';
}

if (!empty($_GET['unit'])) {
    $where[] = 'c.unit LIKE :unit';
    $params[':unit'] = '%' . $_GET['unit'] . '%';
}

if (!empty($_GET['status'])) {
    $where[] = 'c.status = :status';
    $params[':status'] = $_GET['status'];
}

if (!empty($_GET['date_from'])) {
    $where[] = 'DATE(c.created_at) >= :date_from';
    $params[':date_from'] = $_GET['date_from'];
}

if (!empty($_GET['date_to'])) {
    $where[] = 'DATE(c.created_at) <= :date_to';
    $params[':date_to'] = $_GET['date_to'];
}

$whereSql = $where ? ' AND ' . implode(' AND ', $where) : '';

$stmt = $pdo->prepare("
  SELECT
    c.id,
    c.title,
    c.status,
    c.current_signer_order,
    c.total_signers,
    c.updated_at,
    c.completed_at,
    GROUP_CONCAT(
      cs.display_name
      ORDER BY cs.signer_order
      SEPARATOR ', '
    ) AS signer_names
  FROM contracts c
  LEFT JOIN contract_signers cs ON cs.contract_id = c.id
  WHERE c.site_id = :site_id
    AND c.is_deleted = 0
    {$whereSql}
  GROUP BY c.id
  ORDER BY c.updated_at DESC
");

$stmt->execute($params);
$documents = $stmt->fetchAll(PDO::FETCH_ASSOC);

$statusMap = [
  'PROGRESS' => '진행',
  'DONE'     => '완료'
];

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
      <form method="get" class="document-filter">

        <div class="filter-row">
          <select name="category_id">
            <option value="">전체 카테고리</option>
            <?php foreach ($categories as $cat): ?>
              <option value="<?= $cat['id'] ?>"
                <?= ($_GET['category_id'] ?? '') == $cat['id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($cat['name']) ?>
              </option>
            <?php endforeach; ?>
          </select>

          <select name="contract_template_id">
            <option value="">전체 템플릿</option>
            <?php foreach ($templates as $tpl): ?>
              <option value="<?= $tpl['id'] ?>"
                <?= ($_GET['contract_template_id'] ?? '') == $tpl['id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($tpl['title']) ?>
              </option>
            <?php endforeach; ?>
          </select>

          <input type="text" name="title" placeholder="문서 제목"
                value="<?= htmlspecialchars($_GET['title'] ?? '') ?>">
        </div>

        <div class="filter-row">
          <input type="text" name="complex" placeholder="단지"
                value="<?= htmlspecialchars($_GET['complex'] ?? '') ?>">

          <input type="text" name="building" placeholder="동"
                value="<?= htmlspecialchars($_GET['building'] ?? '') ?>">

          <input type="text" name="unit" placeholder="호"
                value="<?= htmlspecialchars($_GET['unit'] ?? '') ?>">
        </div>

        <div class="filter-row">
          <select name="status">
            <option value="">전체 상태</option>
            <option value="PROGRESS" <?= ($_GET['status'] ?? '') === 'PROGRESS' ? 'selected' : '' ?>>진행</option>
            <option value="DONE" <?= ($_GET['status'] ?? '') === 'DONE' ? 'selected' : '' ?>>완료</option>
          </select>

          <input type="date" name="date_from" value="<?= $_GET['date_from'] ?? '' ?>">
          <input type="date" name="date_to" value="<?= $_GET['date_to'] ?? '' ?>">
        </div>

        <div class="filter-actions">
          <button type="submit">검색</button>
          <a href="index.php" class="btn-reset">초기화</a>
        </div>
      </form>

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

          <?php
            $statusText  = $statusMap[$doc['status']] ?? $doc['status'];
            $statusClass = strtolower($doc['status']);

            if ($doc['status'] === 'PROGRESS') {
                $current = (int)$doc['current_signer_order'];
                $total   = (int)$doc['total_signers'];
                $statusText = "진행_{$current}/{$total}";
            } elseif ($doc['status'] === 'DONE') {
                $statusText = '완료';
            } else {
                $statusText = $doc['status'];
            }

            if ($doc['status'] === 'DONE') {
                $date = $doc['completed_at'] ?? $doc['updated_at'];
            } else {
                $date = $doc['updated_at'];
            }

            $signers = $doc['signer_names']
              ? implode(',<br>', array_map('trim', explode(',', $doc['signer_names'])))
              : '-';
          ?>

          <tr onclick="location.href='../contract/terms.php?id=<?= $doc['id'] ?>'">
            <td>
              <span class="status <?= $statusClass ?>">
                <?= $statusText ?>
              </span>
            </td>
            <td><?= htmlspecialchars($doc['title']) ?></td>
            <td class="signers">
              <?= $signers ?>
            </td>
            <td>
              <?php
                $date = null;

                if ($doc['status'] === 'DONE') {
                  $date = $doc['completed_at'];
                } else {
                  $date = $doc['updated_at'];
                }
              ?>

              <?= $date ? date('Y-m-d', strtotime($date)) : '-' ?>
            </td>
          </tr>
        <?php endforeach; ?>

        </tbody>
      </table>

    </main>
  </div>

</body>
</html>
