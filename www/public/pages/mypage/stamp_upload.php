<?php
require_once __DIR__ . '/../../../../bootstrap.php';

if ($_SESSION['role'] !== 'OPERATOR') {
  die('권한 없음');
}

$siteId = intval($_POST['site_id'] ?? 0);
if (!$siteId || empty($_FILES['stamp'])) {
  die('잘못된 요청');
}

$file = $_FILES['stamp'];

$allowedTypes = ['image/png', 'image/jpeg', 'image/webp'];
if (!in_array($file['type'], $allowedTypes)) {
  die('이미지 파일만 업로드 가능합니다');
}

if ($file['size'] > 2 * 1024 * 1024) {
  die('파일 용량 초과 (2MB)');
}

$ext = pathinfo($file['name'], PATHINFO_EXTENSION);
$filename = 'stamp_'.$siteId.'_'.uniqid().'.'.$ext;
$relativePath = 'storage/stamp/'.$filename;
$fullPath = BASE_PATH.'/'.$relativePath;

if (!move_uploaded_file($file['tmp_name'], $fullPath)) {
  die('파일 저장 실패');
}

$stmt = $pdo->prepare("
  INSERT INTO stamp (site_id, file_path, is_hided)
  VALUES (:site_id, :file_path, 0)
");
$stmt->execute([
  'site_id'   => $siteId,
  'file_path'=> $relativePath
]);

header('Location: index.php?tab=stamp');
exit;
