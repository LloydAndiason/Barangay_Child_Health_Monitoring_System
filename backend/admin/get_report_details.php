<?php
require_once(__DIR__ . '/../database/config.php');
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$report_id = intval($_GET['id'] ?? 0);
if (!$report_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid report ID']);
    exit;
}

try {
    // 1️⃣ Fetch report info
    $stmt = $pdo->prepare("
        SELECT r.*, u.fullname AS bns_name, u.assigned_area
        FROM reports r
        JOIN users u ON r.bns_id = u.id
        WHERE r.id = ?
    ");
    $stmt->execute([$report_id]);
    $report = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$report) {
        echo json_encode(['success' => false, 'message' => 'Report not found']);
        exit;
    }

    // 2️⃣ Fetch pending entries of that BNS for the same month/year
    $stmt2 = $pdo->prepare("
        SELECT p.*, c.full_name AS child_name
        FROM pending_entry p
        JOIN children c ON c.id = p.child_id
        WHERE p.bns_id = ? AND p.month = ? AND p.year = ?
        ORDER BY p.date_created DESC
    ");
    $stmt2->execute([$report['bns_id'], $report['report_month'], $report['report_year']]);
    $entries = $stmt2->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'report' => [
            'bns_name' => $report['bns_name'],
            'assigned_area' => $report['assigned_area'],
            'month' => $report['report_month'],
            'year' => $report['report_year'],
            'month_name' => date('F', mktime(0, 0, 0, $report['report_month'], 1)),
            'status' => $report['status'],
        ],
        'entries' => $entries
    ]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
