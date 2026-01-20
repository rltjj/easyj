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
  UPDATE stamp
  SET is_hided = 1 - is_hided
  WHERE id = :id
");
$stmt->execute(['id' => $id]);

header('Location: index.php?tab=stamp');
exit;
