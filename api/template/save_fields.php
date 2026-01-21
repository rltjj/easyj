<?php
require_once __DIR__ . '/../../bootstrap.php';
header('Content-Type: application/json');

if ($_SESSION['role'] !== 'ADMIN') {
    http_response_code(403);
    echo json_encode(['message' => '권한 없음']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

$templateId = intval($input['template_id'] ?? 0);
$fields     = $input['fields'] ?? [];

if (!$templateId) {
    echo json_encode(['message' => 'template_id 없음']);
    exit;
}

try {
    $pdo->beginTransaction();

    $del = $pdo->prepare("DELETE FROM template_fields WHERE template_id = :tid");
    $del->execute([':tid' => $templateId]);

    $ins = $pdo->prepare("
        INSERT INTO template_fields (
            template_id,
            page_no,
            signer_order,
            field_type,
            x,
            y,
            width,
            height,
            label,
            required,
            ch_plural,
            ch_min,
            ch_max,
            t_style,
            t_size,
            t_array,
            t_color
        ) VALUES (
            :template_id,
            :page_no,
            :signer_order,
            :field_type,
            :x,
            :y,
            :width,
            :height,
            :label,
            :required,
            :ch_plural,
            :ch_min,
            :ch_max,
            :t_style,
            :t_size,
            :t_array,
            :t_color
        )
    ");

    foreach ($fields as $f) {

        $fieldType = $f['field_type'];
        if ($fieldType === 'CHECK') $fieldType = 'CHECKBOX';
        if (in_array($fieldType, ['DATE_Y', 'DATE_M', 'DATE_D'])) $fieldType = 'DATE';

        $label = $f['label'] ?? '';
        if ($f['field_type'] === 'CHECKBOX') $fieldType = 'CHECKBOX';
        if ($f['field_type'] === 'DATE_Y') $label = 'YYYY';
        if ($f['field_type'] === 'DATE_M') $label = 'MM';
        if ($f['field_type'] === 'DATE_D') $label = 'DD';
        if (!$label && !str_starts_with($f['field_type'], 'DATE') && $fieldType !== 'CHECKBOX') {
            $label = $fieldType;
        }

        $chPlural = null;
        $chMin    = null;
        $chMax    = null;
        if ($fieldType === 'CHECKBOX') {
            $chPlural = $f['group_label'] ?? null;
            $chMin    = $f['min_select'] !== '' ? $f['min_select'] : null;
            $chMax    = $f['max_select'] !== '' ? $f['max_select'] : null;
        }

        $signerOrder = intval($f['signer_order']);

        if ($signerOrder <= 0) {
            throw new Exception('잘못된 signer_order');
        }

        $tArray = $fieldType === 'TEXT' ? ($f['text_align'] ?? 'LEFT') : null;

        $ins->execute([
            ':template_id' => $templateId,
            ':page_no'     => intval($f['page_no']),
            ':signer_order'=> $signerOrder,
            ':field_type'  => $fieldType,
            ':x'           => intval($f['pos_x']),
            ':y'           => intval($f['pos_y']),
            ':width'       => intval($f['width']),
            ':height'      => intval($f['height']),
            ':label'       => $label,
            ':required'    => intval($f['required'] ?? 0),
            ':ch_plural'   => $chPlural,
            ':ch_min'      => $chMin,
            ':ch_max'      => $chMax,
            ':t_style'     => null,
            ':t_size'      => $f['font_size'] ?? null,
            ':t_array'     => $tArray,
            ':t_color'     => null
        ]);
    }

    $pdo->commit();

    echo json_encode(['message' => '저장 완료']);

} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode([
        'message' => '저장 실패',
        'error'   => $e->getMessage()
    ]);
}
