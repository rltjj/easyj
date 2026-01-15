<?php

function getUserSites(PDO $pdo, int $userId, string $role): array
{
    if ($role === 'ADMIN') {
        // 관리자 → 모든 활성 현장
        $stmt = $pdo->query("
            SELECT id, site_name
            FROM sites
            WHERE service_enabled = 1
            ORDER BY created_at ASC
        ");
        return $stmt->fetchAll();
    }

    if ($role === 'OPERATOR') {
        $stmt = $pdo->prepare("
            SELECT id, site_name
            FROM sites
            WHERE operator_id = ?
              AND service_enabled = 1
            ORDER BY created_at ASC
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    // STAFF
    $stmt = $pdo->prepare("
        SELECT s.id, s.site_name
        FROM sites s
        JOIN site_staff ss ON ss.site_id = s.id
        WHERE ss.staff_id = ?
          AND s.service_enabled = 1
        ORDER BY s.created_at ASC
    ");
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}
