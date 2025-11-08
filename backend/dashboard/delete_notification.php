<?php
require_once(__DIR__ . '/../database/config.php');
session_start();
header('Content-Type: application/json');

// ✅ Verify login
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];
$notif_id = intval($_POST['id'] ?? 0);

if (!$notif_id) {
    echo json_encode(['success' => false, 'message' => 'Missing notification ID']);
    exit;
}

try {
    // 🗑️ Delete the notification only if it belongs to the logged-in user
    $stmt = $pdo->prepare("DELETE FROM notifications WHERE id = ? AND user_id = ?");
    $stmt->execute([$notif_id, $user_id]);

    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Notification deleted successfully.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Notification not found or already deleted.']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
