<?php
session_start();
header('Content-Type: application/json');
require_once '../database/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?? [];

$report_id = $input['report_id'] ?? '';
$action    = $input['action'] ?? '';      // accept, decline, delete

if ($report_id === '' || $action === '') {
    echo json_encode(['success' => false, 'message' => 'Missing parameters']);
    exit;
}

try {
    // ---- fetch report -------------------------------------------------
    $stmt = $pdo->prepare("SELECT * FROM reports WHERE id = ?");
    $stmt->execute([$report_id]);
    $report = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$report) {
        echo json_encode(['success' => false, 'message' => 'Report not found']);
        exit;
    }

    $report_data = json_decode($report['report_data'], true) ?? [];

    // --------------------------------------------------------------------
    switch ($action) {
        case 'accept':
            $saved = 0;
            foreach ($report_data['records'] ?? [] as $rec) {
                if (($rec['source'] ?? '') !== 'temporary') continue;

                $d = $rec['data'];
                $stmt = $pdo->prepare("
                    INSERT INTO child_records
                        (child_id, bns_id, height, weight, muac, record_date, health_status, notes)
                    VALUES
                        (?,?,?,?,?,?,?,?)
                ");
                $stmt->execute([
                    $d['child_id'],
                    $report['bns_id'],
                    $d['height'],
                    $d['weight'],
                    $d['muac'] ?? null,
                    $d['record_date'],
                    $d['health_status'],
                    $d['notes'] ?? ''
                ]);
                $saved++;
            }

            // **IMPORTANT** – update the *same* report row
            $stmt = $pdo->prepare("UPDATE reports SET status = 'accepted', reviewed_at = NOW() WHERE id = ?");
            $stmt->execute([$report_id]);

            // notify BNS
            $msg = "Your report for {$report['report_month']} has been accepted. $saved record(s) saved.";
            $stmt = $pdo->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?,?,?,?)");
            $stmt->execute([$report['bns_id'], 'Report Accepted', $msg, 'report_accepted']);

            echo json_encode(['success' => true, 'message' => "Report accepted – $saved record(s) saved."]);
            break;

        case 'decline':
            $stmt = $pdo->prepare("UPDATE reports SET status = 'declined', reviewed_at = NOW() WHERE id = ?");
            $stmt->execute([$report_id]);

            $msg = "Your report for {$report['report_month']} has been declined. Please review and resubmit.";
            $stmt = $pdo->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?,?,?,?)");
            $stmt->execute([$report['bns_id'], 'Report Declined', $msg, 'report_declined']);

            echo json_encode(['success' => true, 'message' => 'Report declined']);
            break;

        case 'delete':
            $stmt = $pdo->prepare("DELETE FROM reports WHERE id = ?");
            $stmt->execute([$report_id]);

            echo json_encode(['success' => true, 'message' => 'Report deleted']);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} catch (PDOException $e) {
    error_log("Report management error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?>