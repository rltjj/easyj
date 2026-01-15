<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../bootstrap.php';

$pdo->beginTransaction();

try {
    $role = $_POST['role'] ?? '';

    if (!in_array($role, ['OPERATOR', 'STAFF'])) {
        throw new Exception('잘못된 회원 유형입니다.');
    }

    $stmt = $pdo->prepare("
        INSERT INTO users (email, password, name, phone, role)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $_POST['email'],
        password_hash($_POST['password'], PASSWORD_DEFAULT),
        $_POST['name'],
        $_POST['phone'],
        $role
    ]);

    $userId = $pdo->lastInsertId();

    if ($role === 'OPERATOR') {

        $required = [
            'site_name','company_name','position','ceo_name',
            'company_phone','office_address','modelhouse_address',
            'agent_name','agent_phone'
        ];

        foreach ($required as $field) {
            if (empty($_POST[$field])) {
                throw new Exception('운영자 정보가 누락되었습니다.');
            }
        }

        $stmt = $pdo->prepare("
            INSERT INTO sites (name, operator_id)
            VALUES (?, ?)
        ");
        $stmt->execute([
            $_POST['site_name'],
            $userId
        ]);
        $siteId = $pdo->lastInsertId();

        $stmt = $pdo->prepare("
            INSERT INTO operator_company
            (site_id, company_name, position, ceo_name, company_phone, office_address, modelhouse_address)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $siteId,
            $_POST['company_name'],
            $_POST['position'],
            $_POST['ceo_name'],
            $_POST['company_phone'],
            $_POST['office_address'],
            $_POST['modelhouse_address']
        ]);

        $stmt = $pdo->prepare("
            INSERT INTO operator_agency
            (site_id, manager_name, manager_phone)
            VALUES (?, ?, ?)
        ");
        $stmt->execute([
            $siteId,
            $_POST['agent_name'],
            $_POST['agent_phone']
        ]);
    }

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => '회원가입이 완료되었습니다.'
    ]);

} catch (Exception $e) {
    $pdo->rollBack();

    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
