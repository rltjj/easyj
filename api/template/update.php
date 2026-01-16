<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../../config/database.php';

if ($_SESSION['role'] !== 'ADMIN') {
    http_response_code(403);
    exit;
}

$templateId = intval($_GET['id']);
$data = json_decode(file_get_contents('php://input'), true);

$pdo->prepare("DELETE FROM template_fields WHERE template_id = ?")
    ->execute([$templateId]);

$stmt = $pdo->prepare("
  INSERT INTO template_fields
  (template_id, page, field_type, role, label, pos_x, pos_y, width, height)
  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
");

foreach ($data as $f) {
  $stmt->execute([
    $templateId,
    $f['page'],
    $f['type'],
    $f['role'],
    $f['label'],
    $f['pos_x'],
    $f['pos_y'],
    $f['width'],
    $f['height']
  ]);
}

echo json_encode(['success'=>true]);

