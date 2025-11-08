<?php
require_once(__DIR__ . '/../database/config.php');
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'bns') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$entries = $input['entries'] ?? [];

if (empty($entries)) {
    echo json_encode(['success' => false, 'message' => 'No entries to update']);
    exit;
}

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("
        UPDATE pending_entry
        SET height = ?, weight = ?, muac = ?, remarks = ?
        WHERE id = ? AND bns_id = ?
    ");

    foreach ($entries as $entry) {
        $stmt->execute([
            $entry['height'] ?? null,
            $entry['weight'] ?? null,
            $entry['muac'] ?? null,
            $entry['remarks'] ?? null,
            $entry['id'],
            $_SESSION['user_id']
        ]);
    }

    $pdo->commit();
    echo json_encode(['success' => true, 'message' => 'All entries updated successfully.']);
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
