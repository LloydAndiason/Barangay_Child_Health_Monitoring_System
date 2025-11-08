<?php
require_once '../database/config.php';

// TEMPORARY DEBUGGING — to reveal the actual error
//error_reporting(E_ALL);
//ini_set('display_errors', 1);
ini_set('log_errors', 1);

header('Content-Type: application/json');

$child_id = $_GET['child_id'] ?? '';

if (empty($child_id)) {
    echo json_encode(['success' => false, 'message' => 'Child ID required']);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT id, record_date, height, weight, muac, health_status, notes
        FROM child_records
        WHERE child_id = ?
        ORDER BY record_date DESC
    ");
    $stmt->execute([$child_id]);
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt2 = $pdo->prepare("SELECT full_name FROM children WHERE id = ?");
    $stmt2->execute([$child_id]);
    $child = $stmt2->fetch();

    echo json_encode([
        'success' => true,
        'records' => $records,
        'child_name' => $child['full_name'] ?? 'Unknown'
    ]);
} catch (PDOException $e) {
    // Show the real SQL error for debugging
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
