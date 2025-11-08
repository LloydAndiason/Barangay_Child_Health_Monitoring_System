<?php
session_start();
header('Content-Type: application/json');

// Allow CORS for local development
header('Access-Control-Allow-Origin: http://localhost');
header('Access-Control-Allow-Credentials: true');

if (isset($_SESSION['user_id'])) {
    echo json_encode([
        'authenticated' => true,
        'user' => [
            'id' => $_SESSION['user_id'],
            'username' => $_SESSION['username'],
            'role' => $_SESSION['role'],
            'fullname' => $_SESSION['fullname'],
            'assigned_area' => $_SESSION['assigned_area']
        ]
    ]);
} else {
    echo json_encode([
        'authenticated' => false,
        'message' => 'No active session found'
    ]);
}
?>