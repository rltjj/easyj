<?php

function getContractStats(PDO $pdo, $siteId) {
    $stmt = $pdo->prepare("
        SELECT t.title, c.name AS category,
               SUM(CASE WHEN co.status='IN_PROGRESS' THEN 1 ELSE 0 END) AS in_progress,
               SUM(CASE WHEN co.status='COMPLETED' THEN 1 ELSE 0 END) AS completed
        FROM templates t
        LEFT JOIN templates_categories c ON t.category_id = c.id
        LEFT JOIN contracts co 
            ON co.template_id = t.id 
           AND co.site_id = :site_id  -- 계약도 현장 기준
        WHERE t.site_id = :site_id
        GROUP BY t.id, t.title, c.name
        ORDER BY t.created_at DESC
    ");
    $stmt->execute([':site_id' => $siteId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}


function getInquiries(PDO $pdo, $siteId) {
    $stmt = $pdo->prepare("
        SELECT i.id, i.content, i.status, u.name
        FROM inquiries i
        LEFT JOIN users u ON i.requester_id = u.id
        WHERE i.site_id = :site_id
        ORDER BY i.created_at DESC
    ");
    $stmt->execute([':site_id' => $siteId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
