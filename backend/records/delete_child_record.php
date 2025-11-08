<?php
require_once '../database/config.php';
header('Content-Type: application/json');

$id = $_GET['id'] ?? '';

if (empty($id)) {
    echo json_encode(['success' => false, 'message' => 'Missing record ID']);
    exit;
}

try {
    $stmt = $pdo->prepare("DELETE FROM child_records WHERE id = ?");
    $stmt->execute([$id]);
    echo json_encode(['success' => true, 'message' => 'Record deleted successfully']);
} catch (PDOException $e) {
    error_log('delete_child_record error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Failed to delete record']);
}
?>
