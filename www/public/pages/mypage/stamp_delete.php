<?php
require_once __DIR__ . '/../../../../bootstrap.php';

if ($_SESSION['role'] !== 'OPERATOR') {
  die('권한 없음');
}

$id = intval($_POST['id'] ?? 0);
if (!$id) {
  die('잘못된 요청');
}

$stmt = $pdo->prepare("
  SELECT file_path
  FROM stamp
  WHERE id = :id
");
$stmt->execute(['id' => $id]);
$stamp = $stmt->fetch(PDO::FETCH_ASSOC);

if ($stamp) {
  $file = BASE_PATH.'/'.$stamp['file_path'];
  if (is_file($file)) {
    unlink($file);
  }

  $del = $pdo->prepare("DELETE FROM stamp WHERE id = :id");
  $del->execute(['id' => $id]);
}

header('Location: index.php?tab=stamp');
exit;
