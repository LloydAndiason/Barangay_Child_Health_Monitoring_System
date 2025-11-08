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
    
    $fullname = $input['fullname'] ?? '';
    $username = $input['username'] ?? '';
    $password = $input['password'] ?? '';
    $assigned_area = $input['assigned_area'] ?? '';
    
    // Validate required fields
    if (empty($fullname) || empty($username) || empty($password) || empty($assigned_area)) {
        echo json_encode(['success' => false, 'message' => 'All fields are required']);
        exit;
    }
    
    if (strlen($password) < 6) {
        echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters long']);
        exit;
    }
    
    try {
        // Check if username already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Username already exists']);
            exit;
        }
        
        // Store plain text password (NO HASHING)
        $stmt = $pdo->prepare("
            INSERT INTO users (fullname, username, password, role, assigned_area) 
            VALUES (?, ?, ?, 'bns', ?)
        ");
        
        $stmt->execute([$fullname, $username, $password, $assigned_area]);
        
        echo json_encode([
            'success' => true, 
            'message' => 'BNS account created successfully'
        ]);
        
    } catch (PDOException $e) {
        error_log("Add BNS error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}
?>