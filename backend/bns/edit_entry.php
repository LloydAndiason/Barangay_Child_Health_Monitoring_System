<?php
require_once(__DIR__ . '/../database/config.php');
session_start();
header('Content-Type: application/json');

// Ensure logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$bns_id = $_SESSION['user_id'];
$input = json_decode(file_get_contents("php://input"), true);

$id = $input['id'] ?? null;
$height = $input['height'] ?? null;
$weight = $input['weight'] ?? null;
$muac = $input['muac'] ?? null;
$remarks = trim($input['remarks'] ?? '');

if (!$id || !$height || !$weight) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

try {
    // ✅ Verify that this entry belongs to the logged-in BNS
    $check = $pdo->prepare("SELECT id FROM pending_entry WHERE id = :id AND bns_id = :bns_id");
    $check->execute(['id' => $id, 'bns_id' => $bns_id]);
    if ($check->rowCount() === 0) {
        echo json_encode(['success' => false, 'message' => 'You are not authorized to edit this entry']);
        exit;
    }

    // ✅ Update entry
    $stmt = $pdo->prepare("
        UPDATE pending_entry
        SET height = :height,
            weight = :weight,
            muac = :muac,
            remarks = :remarks
        WHERE id = :id AND bns_id = :bns_id
    ");
    $stmt->execute([
        'height' => $height,
        'weight' => $weight,
        'muac' => $muac,
        'remarks' => $remarks,
        'id' => $id,
        'bns_id' => $bns_id
    ]);

    echo json_encode(['success' => true, 'message' => '✅ Entry updated successfully']);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
