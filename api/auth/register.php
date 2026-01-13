<?php
header('Content-Type: application/json; charset=utf-8');
require '../../config/database.php';

$pdo->beginTransaction();

try {
    $stmt = $pdo->prepare("
      INSERT INTO users (email, password, name, phone)
      VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([
        $_POST['email'],
        password_hash($_POST['password'], PASSWORD_DEFAULT),
        $_POST['name'],
        $_POST['phone']
    ]);

    $userId = $pdo->lastInsertId();

    $stmt = $pdo->prepare("
      INSERT INTO sites (site_name, operator_id)
      VALUES (?, ?)
    ");
    $stmt->execute([
        $_POST['site_name'],
        $userId
    ]);

    $siteId = $pdo->lastInsertId();

    $stmt = $pdo->prepare("
      INSERT INTO companies
      (site_id, company_type, name, ceo_name, address, phone)
      VALUES (?, 'DEVELOPER', ?, ?, ?, ?)
    ");
    $stmt->execute([
        $siteId,
        $_POST['company_name'],
        $_POST['ceo_name'],
        $_POST['address'],
        $_POST['company_phone']
    ]);

    $stmt = $pdo->prepare("
      INSERT INTO companies
      (site_id, company_type, manager_name, manager_phone)
      VALUES (?, 'AGENCY', ?, ?)
    ");
    $stmt->execute([
        $siteId,
        $_POST['agency_manager'],
        $_POST['agency_phone']
    ]);

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => '회원가입이 완료되었습니다.'
    ]);
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode([
        'success' => false,
        'message' => '회원가입 실패',
        'error' => $e->getMessage()
    ]);
}
