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
    $full_name = $input['full_name'] ?? '';
    $sex = $input['sex'] ?? '';
    $birthdate = $input['birthdate'] ?? '';
    $birthplace = $input['birthplace'] ?? '';
    $blood_type = $input['blood_type'] ?? '';
    $parent_guardian = $input['parent_guardian'] ?? '';
    $current_address = $input['current_address'] ?? '';
    $assigned_bns_id = $input['assigned_bns_id'] ?? '';
    
    // Validate required fields
    $required = ['child_id', 'full_name', 'sex', 'birthdate', 'parent_guardian', 'current_address', 'assigned_bns_id'];
    foreach ($required as $field) {
        if (empty($$field)) {
            echo json_encode(['success' => false, 'message' => "Field '$field' is required"]);
            exit;
        }
    }
    
    try {
        $stmt = $pdo->prepare("
            UPDATE children 
            SET full_name = ?, sex = ?, birthdate = ?, birthplace = ?, 
                blood_type = ?, parent_guardian = ?, current_address = ?, assigned_bns_id = ?
            WHERE id = ?
        ");
        
        $success = $stmt->execute([
            $full_name, $sex, $birthdate, $birthplace, $blood_type, 
            $parent_guardian, $current_address, $assigned_bns_id, $child_id
        ]);
        
        if ($success) {
            echo json_encode([
                'success' => true, 
                'message' => 'Child updated successfully!'
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update child record']);
        }
        
    } catch (PDOException $e) {
        error_log("Edit child error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>