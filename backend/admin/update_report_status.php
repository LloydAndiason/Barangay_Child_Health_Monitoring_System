<?php
require_once(__DIR__ . '/../database/config.php');
session_start();
header('Content-Type: application/json');

// ✅ Admin-only access
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

$input = json_decode(file_get_contents("php://input"), true);
$report_id = $input['report_id'] ?? null;
$action = strtolower(trim($input['status'] ?? ''));

if (!$report_id || !$action) {
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit;
}

try {
    // Fetch report info
    $stmt = $pdo->prepare("SELECT * FROM reports WHERE id = ?");
    $stmt->execute([$report_id]);
    $report = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$report) {
        echo json_encode(['success' => false, 'message' => 'Report not found']);
        exit;
    }

    // Identify report owner
    $bns_id = $report['bns_id'];
    $month = $report['report_month'];
    $year  = $report['report_year'];

    if ($action === 'delete') {
        // 🗑️ Delete report permanently
        $del = $pdo->prepare("DELETE FROM reports WHERE id = ?");
        $del->execute([$report_id]);

        echo json_encode(['success' => true, 'message' => 'Report deleted successfully.']);
    } 
    elseif ($action === 'declined') {
        // ❌ Decline: remove pending entries, mark declined, add notification
        $pdo->beginTransaction();

        // Delete pending entries
        $delPending = $pdo->prepare("DELETE FROM pending_entry WHERE bns_id = ? AND month = ? AND year = ?");
        $delPending->execute([$bns_id, $month, $year]);

        // Update report status
        $update = $pdo->prepare("UPDATE reports SET status = 'declined' WHERE id = ?");
        $update->execute([$report_id]);

        // 📨 Add notification for BNS
        $notifMsg = "Your monthly report for " . date("F", mktime(0, 0, 0, $month, 1)) . " $year has been declined by the Admin.";
        $notif = $pdo->prepare("INSERT INTO notifications (user_id, message, type) VALUES (?, ?, 'report_declined')");
        $notif->execute([$bns_id, $notifMsg]);

        $pdo->commit();

        echo json_encode(['success' => true, 'message' => 'Report declined, pending entries removed, and BNS notified.']);
    } 
    elseif ($action === 'accepted') {
        // ✅ Accept: mark accepted, add notification
        $pdo->beginTransaction();

        $update = $pdo->prepare("UPDATE reports SET status = 'accepted' WHERE id = ?");
        $update->execute([$report_id]);

        // 📨 Add notification for BNS
        $notifMsg = "Your monthly report for " . date("F", mktime(0, 0, 0, $month, 1)) . " $year has been accepted by the Admin.";
        $notif = $pdo->prepare("INSERT INTO notifications (user_id, message, type) VALUES (?, ?, 'report_accepted')");
        $notif->execute([$bns_id, $notifMsg]);

        $pdo->commit();

        echo json_encode(['success' => true, 'message' => 'Report accepted and BNS notified.']);
    } 
    else {
        // 🔄 Generic status update
        $update = $pdo->prepare("UPDATE reports SET status = ? WHERE id = ?");
        $update->execute([$action, $report_id]);
        echo json_encode(['success' => true, 'message' => 'Report status updated to ' . ucfirst($action) . '.']);
    }
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
