<?php
require_once(__DIR__ . '/../database/config.php');
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$bns_id = $_SESSION['user_id'];
$data = json_decode(file_get_contents("php://input"), true);
$month = intval($data['month'] ?? date('n'));
$year  = intval($data['year'] ?? date('Y'));

try {
    $check = $pdo->prepare("SELECT id FROM reports WHERE bns_id = ? AND report_month = ? AND report_year = ?");
    $check->execute([$bns_id, $month, $year]);

    if ($check->rowCount() > 0) {
        echo json_encode(['success' => false, 'message' => '⚠️ Report for this month is already submitted.']);
        exit;
    }

    $insert = $pdo->prepare("INSERT INTO reports (bns_id, report_month, report_year, status, submitted_at) VALUES (?, ?, ?, 'pending', NOW())");
    $insert->execute([$bns_id, $month, $year]);

    echo json_encode(['success' => true, 'message' => '✅ Report submitted successfully!']);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
