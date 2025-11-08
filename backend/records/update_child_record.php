<?php
require_once '../database/config.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
if (!$data || empty($data['id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$record_id = $data['id'];
$height = floatval($data['height']);
$weight = floatval($data['weight']);
$muac = floatval($data['muac']);
$remarks = trim($data['remarks'] ?? '');
$record_date = date('Y-m-d');

// 🧮 Compute child status (reuse your real logic if available)
$status = '';
if ($weight <= 0 || $height <= 0) {
    $status = 'Invalid';
} elseif ($weight < 10) {
    $status = 'Severely Underweight';
} elseif ($weight < 15) {
    $status = 'Underweight';
} elseif ($weight >= 15 && $weight <= 25) {
    $status = 'Normal';
} else {
    $status = 'Overweight';
}

try {
    $stmt = $pdo->prepare("
        UPDATE child_records 
        SET height = ?, weight = ?, muac = ?, remarks = ?, health_status = ?, record_date = ?
        WHERE id = ?
    ");
    $stmt->execute([$height, $weight, $muac, $remarks, $status, $record_date, $record_id]);

    echo json_encode(['success' => true, 'message' => 'Record updated successfully', 'new_status' => $status]);
} catch (PDOException $e) {
    error_log('update_child_record error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database update failed']);
}
?>
