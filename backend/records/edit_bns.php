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

// Accept both JSON and standard form submissions
$rawInput = file_get_contents('php://input');
$input = json_decode($rawInput, true);

if (!$input) {
    // fallback for regular form POST
    $input = $_POST;
}

$required = ['bns_id', 'fullname', 'username', 'assigned_area'];
foreach ($required as $f) {
    if (empty($input[$f])) {
        echo json_encode(['success' => false, 'message' => "Field '$f' is required"]);
        exit;
    }
}

$bns_id        = $input['bns_id'];
$fullname      = $input['fullname'];
$username      = $input['username'];
$assigned_area = $input['assigned_area'];
$password      = $input['password'] ?? '';   // optional

if ($password !== '' && strlen($password) < 6) {
    echo json_encode(['success' => false, 'message' => 'Password must be ≥6 characters']);
    exit;
}

try {
    // Verify BNS exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE id = ? AND role = 'bns'");
    $stmt->execute([$bns_id]);
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'BNS not found']);
        exit;
    }

    // Username uniqueness (except for the same user)
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
    $stmt->execute([$username, $bns_id]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Username already taken']);
        exit;
    }

    // ✅ Save password as plain text (no hashing)
    if ($password !== '') {
        $sql = "
            UPDATE users SET
                fullname      = ?,
                username      = ?,
                assigned_area = ?,
                password      = ?
            WHERE id = ?
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$fullname, $username, $assigned_area, $password, $bns_id]);
    } else {
        $sql = "
            UPDATE users SET
                fullname      = ?,
                username      = ?,
                assigned_area = ?
            WHERE id = ?
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$fullname, $username, $assigned_area, $bns_id]);
    }

    echo json_encode(['success' => true, 'message' => 'BNS updated successfully (password saved as plain text)']);
} catch (PDOException $e) {
    error_log("Edit BNS error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?>
