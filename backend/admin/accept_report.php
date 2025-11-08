<?php
require_once(__DIR__ . '/../database/config.php');
session_start();
header('Content-Type: application/json');

// ✅ Security check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$report_id = intval($_POST['id'] ?? 0);
if (!$report_id) {
    echo json_encode(['success' => false, 'message' => 'Missing report ID']);
    exit;
}

try {
    $pdo->beginTransaction();

    // 1️⃣ Get the report details
    $stmt = $pdo->prepare("SELECT * FROM reports WHERE id = ?");
    $stmt->execute([$report_id]);
    $report = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$report) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Report not found']);
        exit;
    }

    // 2️⃣ Get pending entries for that BNS/month/year
    $pendingStmt = $pdo->prepare("
        SELECT * FROM pending_entry
        WHERE bns_id = ? AND month = ? AND year = ?
    ");
    $pendingStmt->execute([$report['bns_id'], $report['report_month'], $report['report_year']]);
    $entries = $pendingStmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$entries) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'No pending entries found for this report']);
        exit;
    }

    // 3️⃣ Prepare insert to child_records
    $insertStmt = $pdo->prepare("
        INSERT INTO child_records (child_id, bns_id, height, weight, muac, record_date, health_status, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");

    // 🧮 Helper function to classify health status
    function determineHealthStatus($weight, $height, $muac) {
        if ($height <= 0) return 'Unknown';

        $bmi = $weight / pow($height / 100, 2); // height is in cm → convert to m

        // Basic BMI-based classification (approximation for children)
        if ($bmi < 13.5 || $muac < 11.5) {
            return 'Critical';
        } elseif ($bmi >= 13.5 && $bmi < 15) {
            return 'Underweight';
        } elseif ($bmi >= 15 && $bmi < 18.5) {
            return 'Normal';
        } elseif ($bmi >= 18.5) {
            return 'Overweight';
        } else {
            return 'Normal';
        }
    }

    // 4️⃣ Insert entries into child_records
    foreach ($entries as $entry) {
        $health_status = determineHealthStatus($entry['weight'], $entry['height'], $entry['muac']);

        // 🔍 Log to PHP error log for debugging
        error_log("DEBUG: Transferring child_id={$entry['child_id']} | W={$entry['weight']} | H={$entry['height']} | MUAC={$entry['muac']} | Status=$health_status");

        $insertStmt->execute([
            $entry['child_id'],
            $entry['bns_id'],
            $entry['height'],
            $entry['weight'],
            $entry['muac'],
            date('Y-m-d', strtotime($entry['date_created'])),
            $health_status,
            $entry['date_created']
        ]);
    }

    // 5️⃣ Delete the transferred entries
    $deleteStmt = $pdo->prepare("DELETE FROM pending_entry WHERE bns_id = ? AND month = ? AND year = ?");
    $deleteStmt->execute([$report['bns_id'], $report['report_month'], $report['report_year']]);

    // 6️⃣ Mark report as accepted
    $updateReport = $pdo->prepare("UPDATE reports SET status = 'accepted' WHERE id = ?");
    $updateReport->execute([$report_id]);

    // 📨 Add notification for the BNS
try {
    $notifMsg = "Your monthly report for " . date("F", mktime(0, 0, 0, $report['report_month'], 1)) . " " . $report['report_year'] . " has been accepted by the Admin.";
    $notif = $pdo->prepare("INSERT INTO notifications (user_id, message, type) VALUES (?, ?, 'report_accepted')");
    $notif->execute([$report['bns_id'], $notifMsg]);
} catch (Exception $e) {
    error_log("Notification insert failed: " . $e->getMessage());
}


    $pdo->commit();

    echo json_encode(['success' => true, 'message' => '✅ Report accepted and data moved to main database.']);
} catch (Exception $e) {
    $pdo->rollBack();
    error_log("ERROR in accept_report.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
