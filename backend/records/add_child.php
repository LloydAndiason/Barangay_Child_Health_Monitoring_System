<?php
session_start();
header('Content-Type: application/json');
require_once '../database/config.php';

// Only admins may add child
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Read JSON input
$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    echo json_encode(['success' => false, 'message' => 'Invalid JSON input']);
    exit;
}

// Extract fields (provide defaults)
$full_name       = trim($input['full_name'] ?? '');
$sex             = trim($input['sex'] ?? '');
$birthdate       = trim($input['birthdate'] ?? '');
$birthplace      = trim($input['birthplace'] ?? '');
$blood_type      = trim($input['blood_type'] ?? '');
$parent_guardian = trim($input['parent_guardian'] ?? '');
$current_address = trim($input['current_address'] ?? '');
$assigned_bns_id = trim($input['assigned_bns_id'] ?? '');

// Simple validation for required fields
$required = [
    'full_name' => $full_name,
    'sex' => $sex,
    'birthdate' => $birthdate,
    'parent_guardian' => $parent_guardian,
    'current_address' => $current_address,
    'assigned_bns_id' => $assigned_bns_id
];

foreach ($required as $field => $value) {
    if ($value === '') {
        echo json_encode(['success' => false, 'message' => "Field '{$field}' is required"]);
        exit;
    }
}

try {
    // Example insert. Adjust table/column names if your DB differs.
    $sql = "INSERT INTO children
            (full_name, sex, birthdate, birthplace, blood_type, parent_guardian, current_address, assigned_bns_id, created_at)
            VALUES
            (:full_name, :sex, :birthdate, :birthplace, :blood_type, :parent_guardian, :current_address, :assigned_bns_id, NOW())";

    $stmt = $pdo->prepare($sql);
    $ok = $stmt->execute([
        ':full_name' => $full_name,
        ':sex' => $sex,
        ':birthdate' => $birthdate,
        ':birthplace' => $birthplace,
        ':blood_type' => $blood_type,
        ':parent_guardian' => $parent_guardian,
        ':current_address' => $current_address,
        ':assigned_bns_id' => $assigned_bns_id
    ]);

    if ($ok) {
        $child_id = $pdo->lastInsertId();
        echo json_encode(['success' => true, 'message' => 'Child added successfully', 'child_id' => $child_id]);
        exit;
    } else {
        // Execution failed but no exception — return generic error
        echo json_encode(['success' => false, 'message' => 'Failed to insert child record']);
        exit;
    }
} catch (PDOException $e) {
    // Log the detailed error to server logs; return a safe message to client
    error_log("Add child error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    exit;
}
