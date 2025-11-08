<?php
require_once(__DIR__ . '/../database/config.php');
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$bns_id = $_SESSION['user_id'];
$month = $_GET['month'] ?? date('n');
$year  = $_GET['year'] ?? date('Y');

try {
    // ✅ Fetch entries made by this BNS or for children assigned to this BNS
    $stmt = $pdo->prepare("
        SELECT 
            pe.id,
            c.full_name AS child_name,
            pe.height,
            pe.weight,
            pe.muac,
            pe.remarks,
            pe.status,
            pe.date_created
        FROM pending_entry pe
        INNER JOIN children c ON c.id = pe.child_id
        WHERE pe.bns_id = :bns_id AND pe.month = :month AND pe.year = :year
        ORDER BY pe.date_created DESC
    ");
    $stmt->execute(['bns_id' => $bns_id, 'month' => $month, 'year' => $year]);
    $entries = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'entries' => $entries]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
