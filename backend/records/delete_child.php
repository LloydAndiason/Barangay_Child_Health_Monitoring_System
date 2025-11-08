<?php
session_start();
header('Content-Type: application/json');
require_once '../database/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $child_id = $input['child_id'] ?? '';
    
    if (empty($child_id)) {
        echo json_encode(['success' => false, 'message' => 'Child ID required']);
        exit;
    }
    
    try {
        // Check if child exists
        $stmt = $pdo->prepare("SELECT full_name FROM children WHERE id = ?");
        $stmt->execute([$child_id]);
        $child = $stmt->fetch();
        
        if (!$child) {
            echo json_encode(['success' => false, 'message' => 'Child not found']);
            exit;
        }
        
        // Start transaction
        $pdo->beginTransaction();
        
        // First, delete related records in child_records table
        $stmt = $pdo->prepare("DELETE FROM child_records WHERE child_id = ?");
        $stmt->execute([$child_id]);
        
        // Also delete any other related records (adjust based on your database structure)
        // Example if you have other related tables:
        // $stmt = $pdo->prepare("DELETE FROM another_related_table WHERE child_id = ?");
        // $stmt->execute([$child_id]);
        
        // Now delete the child
        $stmt = $pdo->prepare("DELETE FROM children WHERE id = ?");
        $stmt->execute([$child_id]);
        
        // Commit transaction
        $pdo->commit();
        
        echo json_encode([
            'success' => true, 
            'message' => 'Child "' . $child['full_name'] . '" deleted successfully'
        ]);
        
    } catch (PDOException $e) {
        // Rollback transaction on error
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Delete child error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}
?>