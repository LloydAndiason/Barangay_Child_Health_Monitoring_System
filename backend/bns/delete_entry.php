<?php
require_once(__DIR__ . '/../database/config.php');
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$bns_id = $_SESSION['user_id'];
$input = json_decode(file_get_contents("php://input"), true);
$ids = $input['ids'] ?? [];

if (empty($ids)) {
    echo json_encode(['success' => false, 'message' => 'No entries selected']);
    exit;
}

try {
    // ✅ Only delete entries owned by the logged-in BNS
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $pdo->prepare("DELETE FROM pending_entry WHERE id IN ($placeholders) AND bns_id = ?");
    
    $params = array_merge($ids, [$bns_id]);
    $stmt->execute($params);

    echo json_encode(['success' => true, 'message' => '🗑️ Selected entries deleted successfully']);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
