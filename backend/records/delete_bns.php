<?php
header('Content-Type: application/json');
require_once '../database/config.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
$bns_id = isset($data['bns_id']) ? intval($data['bns_id']) : 0;

if ($bns_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid BNS ID']);
    exit;
}

try {
    // Verify the BNS exists
    $stmt = $pdo->prepare("SELECT fullname FROM users WHERE id = ? AND role = 'bns'");
    $stmt->execute([$bns_id]);
    $bns = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$bns) {
        echo json_encode(['success' => false, 'message' => 'BNS not found']);
        exit;
    }

    // Check if this BNS has assigned children
    $checkChild = $pdo->prepare("SELECT COUNT(*) FROM children WHERE assigned_bns_id = ?");
    $checkChild->execute([$bns_id]);
    $childCount = $checkChild->fetchColumn();

    if ($childCount > 0) {
        // ❌ Return exactly the message you requested
        echo json_encode([
            'success' => false,
            'message' => "❌ Cannot delete BNS \"{$bns['fullname']}\" because they are assigned to {$childCount} child record(s).\nPlease reassign or delete those first."
        ]);
        exit;
    }

    // Delete if safe
    $delete = $pdo->prepare("DELETE FROM users WHERE id = ? AND role = 'bns'");
    $delete->execute([$bns_id]);

    if ($delete->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => "✅ BNS \"{$bns['fullname']}\" deleted successfully!"]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Delete failed (no changes).']);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
