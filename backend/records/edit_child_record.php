<?php
require_once '../database/config.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
if (!$data || empty($data['id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid data']);
    exit;
}

try {
    $height = floatval($data['height']);
    $weight = floatval($data['weight']);
    $muac   = floatval($data['muac']);
    $notes  = trim($data['notes'] ?? '');
    $record_date = $data['record_date'];

    // 🩺 Compute automatic health status (same as generate_report.php)
    $health_status = "Normal"; // Default

    if (!empty($muac) && $muac < 11.5) {
        $health_status = "Critical";
    } elseif (!empty($muac) && $muac < 12.5) {
        $health_status = "Underweight";
    } else {
        // Compute BMI if height and weight are available
        if ($height > 0 && $weight > 0) {
            $bmi = $weight / pow($height / 100, 2);

            if ($bmi < 16) {
                $health_status = "Critical";
            } elseif ($bmi < 18.5) {
                $health_status = "Underweight";
            } elseif ($bmi <= 25) {
                $health_status = "Normal";
            } else {
                $health_status = "Overweight";
            }
        } else {
            $health_status = "Normal";
        }
    }

    // ✅ Update the child record
    $stmt = $pdo->prepare("
        UPDATE child_records 
        SET record_date = ?, height = ?, weight = ?, muac = ?, health_status = ?, notes = ?
        WHERE id = ?
    ");
    $stmt->execute([
        $record_date,
        $height,
        $weight,
        $muac,
        $health_status,
        $notes,
        $data['id']
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'Record updated successfully',
        'computed_status' => $health_status
    ]);
} catch (PDOException $e) {
    error_log('edit_child_record error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database update failed']);
}
?>
