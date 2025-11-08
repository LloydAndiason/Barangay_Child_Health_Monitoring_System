<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

try {
    // Include database configuration
    require_once '../database/config.php';
    
    $user_id = $_SESSION['user_id'];
    $role = $_SESSION['role'];
    
    $response = ['success' => true];

    // Children count
    if ($role === 'admin') {
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM children");
        $stmt->execute();
    } else {
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM children WHERE assigned_bns_id = ?");
        $stmt->execute([$user_id]);
    }
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $response['childrenCount'] = $result['count'] ?? 0;
    
    // BNS count (admin only)
    if ($role === 'admin') {
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM users WHERE role = 'bns'");
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $response['bnsCount'] = $result['count'] ?? 0;
    }
    
    // Pending reports
    if ($role === 'admin') {
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM reports WHERE status = 'pending'");
        $stmt->execute();
    } else {
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM reports WHERE bns_id = ? AND status = 'pending'");
        $stmt->execute([$user_id]);
    }
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $response['pendingReports'] = $result['count'] ?? 0;
    
    // This month records
    $currentMonth = date('Y-m');
    if ($role === 'admin') {
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM child_records WHERE DATE_FORMAT(record_date, '%Y-%m') = ?");
        $stmt->execute([$currentMonth]);
    } else {
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM child_records WHERE bns_id = ? AND DATE_FORMAT(record_date, '%Y-%m') = ?");
        $stmt->execute([$user_id, $currentMonth]);
    }
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $response['monthRecords'] = $result['count'] ?? 0;

// =======================================================
// ✅ Chart data for Height, Weight, and MUAC (Dynamic)
// =======================================================

// If a child_id is provided (e.g., via GET), show data for that child
$child_id = $_GET['child_id'] ?? null;

if ($child_id) {
    // Fetch health records for that specific child, sorted by date
    $stmt = $pdo->prepare("
        SELECT record_date, height, weight, muac
        FROM child_records
        WHERE child_id = ?
        ORDER BY record_date ASC
    ");
    $stmt->execute([$child_id]);
} else {
    // Otherwise, load all child records (admin overview)
    if ($role === 'admin') {
        $stmt = $pdo->prepare("
            SELECT record_date, AVG(height) AS height, AVG(weight) AS weight, AVG(muac) AS muac
            FROM child_records
            GROUP BY record_date
            ORDER BY record_date ASC
        ");
        $stmt->execute();
    } else {
        // For BNS users, show only their assigned children
        $stmt = $pdo->prepare("
            SELECT record_date, AVG(height) AS height, AVG(weight) AS weight, AVG(muac) AS muac
            FROM child_records
            WHERE bns_id = ?
            GROUP BY record_date
            ORDER BY record_date ASC
        ");
        $stmt->execute([$user_id]);
    }
}

$records = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Prepare data for Chart.js
$response['labels'] = [];
$response['height'] = [];
$response['weight'] = [];
$response['muac']   = [];

foreach ($records as $row) {
    $response['labels'][] = date('M d, Y', strtotime($row['record_date']));
    $response['height'][] = (float)$row['height'];
    $response['weight'][] = (float)$row['weight'];
    $response['muac'][]   = (float)$row['muac'];
}


    // Output all combined data
    echo json_encode($response);

} catch (PDOException $e) {
    // Log the error but don't expose details to user
    error_log("Dashboard stats error: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Unable to load dashboard statistics'
    ]);
} catch (Exception $e) {
    error_log("Dashboard general error: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'An error occurred while loading dashboard'
    ]);
}
?>
