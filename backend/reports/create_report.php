<?php
require 'config.php';
session_start();
header('Content-Type: application/json');

// Check login
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$user_id = $_SESSION['user_id'];
$month = isset($_GET['month']) ? intval($_GET['month']) : date('n');
$year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');

// Fetch all pending entries for this BNS within month + year
$query = $conn->prepare("
    SELECT 
        p.id AS entry_id,
        p.child_id,
        c.name AS child_name,
        c.birth_date,
        p.weight,
        p.height,
        p.remarks,
        p.status,
        p.month,
        p.year,
        p.date_created
    FROM pending_entry p
    JOIN children c ON p.child_id = c.id
    WHERE p.bns_id = ? AND p.month = ? AND p.year = ?
    ORDER BY p.date_created DESC
");
$query->bind_param("iii", $user_id, $month, $year);
$query->execute();
$result = $query->get_result();

$entries = [];
while ($row = $result->fetch_assoc()) {
    $entries[] = $row;
}

echo json_encode([
    'success' => true,
    'month' => $month,
    'year' => $year,
    'total_entries' => count($entries),
    'entries' => $entries
]);
?>
