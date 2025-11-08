<?php
require_once(__DIR__ . '/../database/config.php');
session_start();
header('Content-Type: application/json');

// 🧠 Ensure BNS is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not authenticated']);
    exit;
}

$user_id = $_SESSION['user_id'];
$data = json_decode(file_get_contents("php://input"), true);

$child_id = $data['child_id'] ?? null;
$height   = $data['height'] ?? null;
$weight   = $data['weight'] ?? null;
$muac     = $data['muac'] ?? null;
$remarks  = trim($data['remarks'] ?? '');
$month    = date('n');
$year     = date('Y');

// ✅ Validate inputs
if (!$child_id || !$height || !$weight || !$muac) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

// ✅ Check if BNS is assigned to the child
$stmt = $pdo->prepare("SELECT id FROM children WHERE id = ? AND assigned_bns_id = ?");
$stmt->execute([$child_id, $user_id]);

if ($stmt->rowCount() === 0) {
    echo json_encode(['success' => false, 'message' => 'You are not assigned to this child']);
    exit;
}

// 🩺 Compute automatic health status (Simplified BNS system)
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

// ✅ Insert into pending_entry table
try {
    $insert = $pdo->prepare("
        INSERT INTO pending_entry 
        (child_id, bns_id, height, weight, muac, remarks, month, year, health_status, status, date_created)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())
    ");
    $insert->execute([$child_id, $user_id, $height, $weight, $muac, $remarks, $month, $year, $health_status]);

    echo json_encode([
        'success' => true,
        'message' => '✅ Record saved successfully.',
        'health_status' => $health_status
    ]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
