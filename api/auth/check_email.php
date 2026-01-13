<?php
require '../../config/database.php';

$email = $_POST['email'] ?? '';

$stmt = $pdo->prepare("SELECT id FROM users WHERE email=?");
$stmt->execute([$email]);

echo json_encode(['available' => !$stmt->fetch()]);
