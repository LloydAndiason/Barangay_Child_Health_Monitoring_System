<?php
session_start();
header('Content-Type: application/json');
require_once '../database/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT id, fullname, assigned_area FROM users WHERE role = 'bns' ORDER BY fullname");
    $stmt->execute();
    $bns_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'bns_list' => $bns_list
    ]);
    
} catch (PDOException $e) {
    error_log("Get BNS list error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>