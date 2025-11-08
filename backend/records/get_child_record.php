<?php
require_once '../database/config.php';
header('Content-Type: application/json');

$id = $_GET['id'] ?? '';
if (empty($id)) {
    echo json_encode(['success' => false, 'message' => 'Missing record ID']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT * FROM child_records WHERE id = ?");
    $stmt->execute([$id]);
    $record = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$record) {
        echo json_encode(['success' => false, 'message' => 'Record not found']);
        exit;
    }

    echo json_encode(['success' => true, 'record' => $record]);
} catch (PDOException $e) {
    error_log('get_child_record error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?>
