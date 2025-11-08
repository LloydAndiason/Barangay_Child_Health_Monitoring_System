<?php
session_start();
header('Content-Type: application/json');
require_once '../database/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'bns') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $report_month = $input['report_month'] ?? '';
    
    if (empty($report_month)) {
        echo json_encode(['success' => false, 'message' => 'Report month required']);
        exit;
    }
    
    try {
        // Check if report exists
        $stmt = $pdo->prepare("SELECT * FROM reports WHERE bns_id = ? AND report_month = ?");
        $stmt->execute([$_SESSION['user_id'], $report_month]);
        $report = $stmt->fetch();
        
        if (!$report) {
            echo json_encode(['success' => false, 'message' => 'Report not found. Please generate the report first.']);
            exit;
        }
        
        // Update report status to pending
        $stmt = $pdo->prepare("UPDATE reports SET status = 'pending', submitted_at = NOW() WHERE bns_id = ? AND report_month = ?");
        $stmt->execute([$_SESSION['user_id'], $report_month]);
        
        // Get all admin users for notification
        $stmt = $pdo->prepare("SELECT id FROM users WHERE role = 'admin'");
        $stmt->execute();
        $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Create notification for all admins
        $message = "New report submitted for " . $report_month . " by " . $_SESSION['fullname'];
        $stmt = $pdo->prepare("INSERT INTO notifications (user_id, message, type) VALUES (?, ?, 'new_report')");
        
        foreach ($admins as $admin) {
            $stmt->execute([$admin['id'], $message]);
        }
        
        echo json_encode([
            'success' => true, 
            'message' => 'Report submitted successfully for admin review'
        ]);
        
    } catch (PDOException $e) {
        error_log("Report submission error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}
?>