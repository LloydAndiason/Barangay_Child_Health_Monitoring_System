<?php
// ---------------------------------------------------------------------
// admin_edit_bns.php
// Returns BNS edit form as JSON for admin use
// ---------------------------------------------------------------------

header('Content-Type: application/json');
session_start();

// ✅ Correct path to your config file
require_once __DIR__ . '/database/config.php';

try {
    // -----------------------------------------------------------------
    // 1. AUTH CHECK
    // -----------------------------------------------------------------
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }

    // -----------------------------------------------------------------
    // 2. VALIDATE INPUT
    // -----------------------------------------------------------------
    if (!isset($_GET['bns_id']) || !ctype_digit($_GET['bns_id'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid or missing BNS ID']);
        exit;
    }

    $bns_id = (int) $_GET['bns_id'];

    // -----------------------------------------------------------------
    // 3. FETCH BNS DETAILS
    // -----------------------------------------------------------------
    $stmt = $pdo->prepare("
        SELECT id, fullname, username, assigned_area 
        FROM users 
        WHERE id = ? AND role = 'bns'
    ");
    $stmt->execute([$bns_id]);
    $bns = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$bns) {
        echo json_encode(['success' => false, 'message' => 'BNS not found']);
        exit;
    }

    // -----------------------------------------------------------------
    // 4. BUILD MODAL HTML
    // -----------------------------------------------------------------
    $html = '
    <div class="modal-backdrop active" id="editBnsBackdrop"></div>
    <div class="modal active" id="editBnsModal">
        <div class="modal-header">
            <h3>Edit BNS</h3>
            <button class="close-btn" onclick="closeModal(\'editBnsModal\', \'editBnsBackdrop\')">&times;</button>
        </div>
        <div class="modal-body">
            <form id="editBnsForm">
                <input type="hidden" name="bns_id" value="' . htmlspecialchars($bns['id']) . '">

                <div class="form-group">
                    <label>Full Name <span class="required">*</span></label>
                    <input type="text" name="fullname" value="' . htmlspecialchars($bns['fullname']) . '" required>
                </div>

                <div class="form-group">
                    <label>Username <span class="required">*</span></label>
                    <input type="text" name="username" value="' . htmlspecialchars($bns['username']) . '" required>
                </div>

                <div class="form-group">
                    <label>Assigned Area <span class="required">*</span></label>
                    <input type="text" name="assigned_area" value="' . htmlspecialchars($bns['assigned_area']) . '" required>
                </div>

                <div class="form-group">
                    <label>New Password <small>(leave blank to keep current)</small></label>
                    <input type="password" name="password" minlength="6">
                </div>

                <div class="modal-actions">
                    <button type="button" class="btn secondary" onclick="closeModal(\'editBnsModal\', \'editBnsBackdrop\')">Cancel</button>
                    <button type="submit" class="btn primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>';

    // -----------------------------------------------------------------
    // 5. OUTPUT JSON RESPONSE
    // -----------------------------------------------------------------
    echo json_encode([
        'success' => true,
        'html'    => $html,
        'bns'     => $bns
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}
