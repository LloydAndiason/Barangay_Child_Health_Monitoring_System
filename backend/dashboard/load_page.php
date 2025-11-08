<?php
// backend/dashboard/load_page.php
// ---------------------------------------------------------------
//  Returns JSON { success: true, html: "..."} for the dashboard
//  AJAX loader.  All HTML is built inside functions – no stray
//  code outside of them.
// ---------------------------------------------------------------

error_reporting(E_ALL);
ini_set('display_errors', 0);   // keep UI clean
ini_set('log_errors', 1);

session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

require_once '../database/config.php';

$page    = $_GET['page'] ?? 'dashboard';
$user_id = $_SESSION['user_id'];
$role    = $_SESSION['role'];
$role = strtolower($role);


try {
    switch ($page) {
        case 'view-records':
            echo json_encode([
                'success' => true,
                'html'    => $role === 'admin'
                    ? generateAdminViewRecordsHTML()
                    : generateBNSRecordsHTML($user_id)
            ]);
            break;

        case 'child-list':
            echo json_encode(
                $role === 'bns'
                    ? ['success' => true, 'html' => generateChildListHTML($user_id)]
                    : ['success' => false, 'message' => 'Access denied']
            );
            break;

        case 'child-records':
            if ($role !== 'bns' && $role !== 'admin') {
                echo json_encode(['success' => false, 'message' => 'Access denied']);
                break;
            }
            $child_id = $_GET['child_id'] ?? '';
            if (empty($child_id)) {
                echo json_encode(['success' => false, 'message' => 'Child ID required']);
                break;
            }
            echo json_encode([
                'success' => true,
                'html'    => generateChildRecordsHTML($child_id, $user_id, $role)
            ]);
            break;



        case 'add-records':
            echo json_encode(
                $role === 'admin'
                    ? ['success' => true, 'html' => generateAddRecordsHTML()]
                    : ['success' => false, 'message' => 'Access denied']
            );
            break;

        case 'edit-records':
            echo json_encode(
                $role === 'admin'
                    ? ['success' => true, 'html' => generateEditRecordsHTML()]
                    : ['success' => false, 'message' => 'Access denied']
            );
            break;

        case 'view-reports':
            echo json_encode(
                $role === 'admin'
                    ? ['success' => true, 'html' => generateAdminReportsHTML()]
                    : ['success' => false, 'message' => 'Access denied']
            );
            break;

        case 'notifications':
            echo json_encode(['success' => true, 'html' => generateNotificationsHTML($user_id)]);
            break;

        case 'admin-child-list':
            echo json_encode(
                $role === 'admin'
                    ? ['success' => true, 'html' => generateAdminChildListHTML()]
                    : ['success' => false, 'message' => 'Access denied']
            );
            break;

        case 'admin-bns-list':
            echo json_encode(
                $role === 'admin'
                    ? ['success' => true, 'html' => generateAdminBNSListHTML()]
                    : ['success' => false, 'message' => 'Access denied']
            );
            break;

        case 'admin-view-child-list':
            echo json_encode(
                $role === 'admin'
                    ? ['success' => true, 'html' => generateAdminViewChildListHTML()]
                    : ['success' => false, 'message' => 'Access denied']
            );
            break;

        case 'admin-view-bns-list':
            echo json_encode(
                $role === 'admin'
                    ? ['success' => true, 'html' => generateAdminViewBNSListHTML()]
                    : ['success' => false, 'message' => 'Access denied']
            );
            break;

        case 'admin-edit-child':
            if ($role !== 'admin') {
                echo json_encode(['success' => false, 'message' => 'Access denied']);
                break;
            }
            $child_id = $_GET['child_id'] ?? '';
            if (empty($child_id)) {
                echo json_encode(['success' => false, 'message' => 'Child ID required']);
                break;
            }
            echo json_encode([
                'success' => true,
                'html'    => generateAdminChildEditHTML($child_id)
            ]);
            break;

        case 'admin-edit-bns':
            if ($role !== 'admin') {
                echo json_encode(['success' => false, 'message' => 'Access denied']);
                break;
            }
            $bns_id = $_GET['bns_id'] ?? '';
            if (empty($bns_id)) {
                echo json_encode(['success' => false, 'message' => 'BNS ID required']);
                break;
            }
            echo json_encode([
                'success' => true,
                'html'    => generateAdminBNSEditHTML($bns_id)
            ]);
            break;

        case 'create-report':
                ob_start();
                ?>
                <div class="card" style="padding: 20px;">
                    <h2>Create Monthly Report</h2>
                    <div style="display: flex; gap: 10px; margin-bottom: 20px;">
                        <select id="reportMonth" style="padding: 8px;">
                            <?php
                            for ($i = 1; $i <= 12; $i++) {
                                $monthName = date('F', mktime(0, 0, 0, $i, 10));
                                $selected = ($i == date('n')) ? 'selected' : '';
                                echo "<option value='$i' $selected>$monthName</option>";
                            }
                            ?>
                        </select>
                        <select id="reportYear" style="padding: 8px;">
                            <?php
                            for ($y = 2023; $y <= date('Y') + 1; $y++) {
                                $selected = ($y == date('Y')) ? 'selected' : '';
                                echo "<option value='$y' $selected>$y</option>";
                            }
                            ?>
                        </select>
                        <button class="btn primary" onclick="generateBnsReport()">Generate Report</button>
                    </div>

                    <div id="reportTableArea"></div>
                </div>
                <?php
                $html = ob_get_clean();
                echo json_encode(['success' => true, 'html' => $html]);
                break;


        default:
            echo json_encode(['success' => false, 'message' => 'Page not found']);
    }
} catch (Exception $e) {
    error_log('Load page error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error loading page']);
}

/* ================================================================
   HTML GENERATORS
   ================================================================ */

function generateAdminViewRecordsHTML(): string
{
    return '

    <div class="card">
        <div style="display: flex; gap: 20px; margin-bottom: 30px; border-bottom: 1px solid #ddd; padding-bottom: 20px;">
            <button class="btn primary" onclick="loadPage(\'admin-view-child-list\')">View Children</button>
            <button class="btn secondary" onclick="loadPage(\'admin-view-bns-list\')">View BNS</button>
        </div>
        <div id="viewRecordsContent"><p>Select an option above to view records.</p></div>
    </div>';
}

/* ---------------------------------------------------------------- */
function generateAdminViewChildListHTML(): string
{
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            SELECT 
                c.*,
                TIMESTAMPDIFF(MONTH, c.birthdate, CURDATE()) as age_months,
                cr.health_status as latest_status,
                cr.record_date as last_record_date,
                u.fullname as bns_name
            FROM children c
            LEFT JOIN child_records cr ON c.id = cr.child_id 
                AND cr.record_date = (SELECT MAX(record_date) FROM child_records WHERE child_id = c.id)
            LEFT JOIN users u ON c.assigned_bns_id = u.id
            ORDER BY c.full_name
        ");
        $stmt->execute();
        $children = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $html = '<h2>View Child Records</h2>';
        if (empty($children)) {
            return $html . '<div class="card"><p>No children found.</p></div>';
        }

        $html .= '
        <div class="filters">
            <input type="text" id="searchChildren" placeholder="Search children by name..." style="padding: 8px; width: 300px; border-color: #1976d2;">
            <select id="statusFilter" style="border-color: #1976d2;">
                <option value="">All Status</option>
                <option value="normal">Normal</option>
                <option value="underweight">Underweight</option>
                <option value="overweight">Overweight</option>
                <option value="critical">Critical</option>
            </select>
            <select id="sortFilter" style="border-color: #1976d2;">
                <option value="name_asc">A to Z</option>
                <option value="name_desc">Z to A</option>
                <option value="status">By Status</option>
            </select>
        </div>
        <div class="children-list">';

        foreach ($children as $c) {
            $status      = $c['latest_status'] ?: 'No records';
            $statusClass = strtolower(str_replace(' ', '-', $status));
            $lastRecord  = $c['last_record_date'] ? date('M j, Y', strtotime($c['last_record_date'])) : 'No records';

            $html .= "
            <div class='child-list-item {$statusClass}'
                 data-child-id='{$c['id']}' data-name='{$c['full_name']}' data-status='{$statusClass}' data-age='{$c['age_months']}'>
                <div class='child-list-content'>
                    <div class='child-main-info'>
                        <h4>{$c['full_name']}</h4>
                        <span class='status-badge {$statusClass}'>" . ucfirst($status) . "</span>
                    </div>
                    <div class='child-secondary-info'>
                        <span class='bns-assigned'>BNS: {$c['bns_name']}</span>
                        <span class='last-record'>Last: {$lastRecord}</span>
                    </div>
                </div>
                <div class='child-actions'>
                    <button class='btn primary' onclick='viewChildRecords({$c['id']}, \"{$c['full_name']}\")'>View Record History</button>
                </div>
            </div>";
        }
        $html .= '</div>';
        return $html;
    } catch (PDOException $e) {
        error_log('Admin view child list error: ' . $e->getMessage());
        return '<div class="card"><p>Error loading child list.</p></div>';
    }
}

/* ---------------------------------------------------------------- */
function generateAdminViewBNSListHTML(): string
{
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            SELECT id, fullname, username, assigned_area, created_at 
            FROM users WHERE role = 'bns' ORDER BY fullname
        ");
        $stmt->execute();
        $bns_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $html = '<h2>View BNS Records</h2>';
        if (empty($bns_list)) {
            return $html . '<div class="card"><p>No BNS personnel found.</p></div>';
        }

        $html .= '
        <div class="filters">
            <input type="text" id="searchBNS" placeholder="Search BNS by name..." style="padding: 8px; width: 300px; border-color: #1976d2;">
            <select id="sortBNSFilter" style="border-color: #1976d2;">
                <option value="name_asc">A to Z</option>
                <option value="name_desc">Z to A</option>
                <option value="area">By Area</option>
            </select>
        </div>
        <div class="children-list">';

        foreach ($bns_list as $b) {
            $created = date('M j, Y', strtotime($b['created_at']));
            $html   .= "
            <div class='child-list-item' data-bns-id='{$b['id']}' data-name='{$b['fullname']}' data-area='{$b['assigned_area']}'>
                <div class='child-list-content'>
                    <div class='child-main-info'>
                        <h4>{$b['fullname']}</h4>
                        <span class='status-badge normal'>BNS</span>
                    </div>
                    <div class='child-secondary-info'>
                        <span class='bns-assigned'>Area: {$b['assigned_area']}</span>
                        <span class='last-record'>Created: {$created}</span>
                    </div>
                </div>
                <div class='child-actions'>
                    <button class='btn primary' onclick='viewBNSRecords({$b['id']}, \"{$b['fullname']}\")'>View BNS Details</button>
                </div>
            </div>";
        }
        $html .= '</div>';
        return $html;
    } catch (PDOException $e) {
        error_log('Admin view BNS list error: ' . $e->getMessage());
        return '<div class="card"><p>Error loading BNS list.</p></div>';
    }
}

/* ---------------------------------------------------------------- */
function generateBNSRecordsHTML(int $bns_id): string
{
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            SELECT 
                c.*,
                TIMESTAMPDIFF(MONTH, c.birthdate, CURDATE()) as age_months,
                cr.health_status as latest_status,
                cr.record_date as last_record_date,
                u.fullname as bns_name
            FROM children c
            LEFT JOIN child_records cr ON c.id = cr.child_id 
                AND cr.record_date = (SELECT MAX(record_date) FROM child_records WHERE child_id = c.id)
            LEFT JOIN users u ON c.assigned_bns_id = u.id
            WHERE c.assigned_bns_id = ?
            ORDER BY c.full_name
        ");
        $stmt->execute([$bns_id]);
        $children = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($children)) {
            return $html . '<div class="card"><p>No children assigned to you.</p></div>';
        }

        $html .= '
        <div class="filters">
            <input type="text" id="searchChildren" placeholder="Search children by name..." style="padding: 8px; width: 300px;">
            <select id="statusFilter">
                <option value="">All Status</option>
                <option value="normal">Normal</option>
                <option value="underweight">Underweight</option>
                <option value="overweight">Overweight</option>
                <option value="critical">Critical</option>
            </select>
            <select id="sortFilter">
                <option value="name_asc">A to Z</option>
                <option value="name_desc">Z to A</option>
                <option value="status">By Status</option>
            </select>
        </div>
        <div class="children-list">';

        foreach ($children as $c) {
            $status      = $c['latest_status'] ?: 'No records';
            $statusClass = strtolower(str_replace(' ', '-', $status));
            $lastRecord  = $c['last_record_date'] ? date('M j, Y', strtotime($c['last_record_date'])) : 'No records';

            $html .= "
            <div class='child-list-item {$statusClass}'
                 data-child-id='{$c['id']}' data-name='{$c['full_name']}' data-status='{$statusClass}' data-age='{$c['age_months']}'>
                <div class='child-list-content'>
                    <div class='child-main-info'>
                        <h4>{$c['full_name']}</h4>
                        <span class='status-badge {$statusClass}'>" . ucfirst($status) . "</span>
                    </div>
                    <div class='child-secondary-info'>
                        <span class='bns-assigned'>BNS: {$c['bns_name']}</span>
                        <span class='last-record'>Last: {$lastRecord}</span>
                    </div>
                </div>
                <div class='child-actions'>
                    <button class='btn secondary' onclick='viewChildRecords({$c['id']}, \"{$c['full_name']}\")'>View Record History</button>
                </div>
            </div>";
        }
        $html .= '</div>';
        return $html;
    } catch (PDOException $e) {
        error_log('BNS records error: ' . $e->getMessage());
        return '<div class="card"><p>Error loading records.</p></div>';
    }
}

/* ---------------------------------------------------------------- */
function generateChildListHTML(int $bns_id): string
{
    global $pdo;

    try {
        // ✅ Select children assigned to this BNS
        // Includes whether they have a pending entry this month
        $stmt = $pdo->prepare("
            SELECT 
                c.*,
                TIMESTAMPDIFF(MONTH, c.birthdate, CURDATE()) AS age_months,
                cr.health_status AS latest_status,
                cr.record_date AS last_record_date,
                u.fullname AS bns_name,
                (
                    SELECT COUNT(*) 
                    FROM pending_entry pe 
                    WHERE pe.child_id = c.id 
                      AND pe.bns_id = c.assigned_bns_id 
                      AND pe.month = MONTH(CURDATE()) 
                      AND pe.year = YEAR(CURDATE())
                ) AS has_entry_this_month
            FROM children c
            LEFT JOIN child_records cr 
                ON c.id = cr.child_id 
                AND cr.record_date = (
                    SELECT MAX(record_date) 
                    FROM child_records 
                    WHERE child_id = c.id
                )
            LEFT JOIN users u 
                ON c.assigned_bns_id = u.id
            WHERE c.assigned_bns_id = ?
            ORDER BY c.full_name
        ");
        $stmt->execute([$bns_id]);
        $children = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // ✅ Handle temporary records (unsaved)
        $tempCount = $_SESSION['temporary_records'] ?? [];
        $tempCount = is_array($tempCount) ? count($tempCount) : 0;
        $html = '';

        if ($tempCount) {
            $html .= "
            <div class='card' style='background:#fff3e0;border-left:4px solid #ff9800;margin-bottom:20px;'>
                <h3 style='color:#ef6c00;'>You have {$tempCount} unsaved record(s)</h3>
                <p>These records will be lost if you log out without creating a report.</p>
                <button class='btn warning' onclick='viewTemporaryRecords()'>View Temporary Records</button>
            </div>";
        }

        if (empty($children)) {
            return $html . '<div class="card"><p>No children assigned to you.</p></div>';
        }

        // ✅ Filters (Search, Status, Sort)
        $html .= '
        <div class="filters">
            <input type="text" id="searchChildren" placeholder="Search children by name..." style="padding:8px;width:300px;">
            <select id="statusFilter">
                <option value="">All Status</option>
                <option value="normal">Normal</option>
                <option value="underweight">Underweight</option>
                <option value="overweight">Overweight</option>
                <option value="critical">Critical</option>
            </select>
            <select id="sortFilter">
                <option value="all">All</option>
                <option value="no_entry">No Entry This Month</option>
                <option value="has_entry">Have Entry This Month</option>
                <option value="name_asc">A to Z</option>
                <option value="name_desc">Z to A</option>
            </select>
        </div>
        <div class="children-list">';

        foreach ($children as $c) {
            $status      = $c['latest_status'] ?: 'No records';
            $statusClass = strtolower(str_replace(' ', '-', $status));
            $lastRecord  = $c['last_record_date'] ? date('M j, Y', strtotime($c['last_record_date'])) : 'No records';

            // ✅ Determine if this child has entries this month
            $hasEntry = ($c['has_entry_this_month'] > 0) ? '1' : '0';

            // ✅ Add visual label for entry status
            $entryLabel = $hasEntry === '1'
                ? "<span class='status-badge success' style='margin-left:10px;'>Updated</span>"
                : "<span class='status-badge gray' style='margin-left:10px;'>No Entry</span>";

            // ✅ Check temporary unsaved record badge
            $tempBadge = '';
            if (isset($_SESSION['temporary_records'])) {
                $cnt = 0;
                foreach ($_SESSION['temporary_records'] as $tr) {
                    if (($tr['child_id'] ?? 0) == $c['id']) $cnt++;
                }
                if ($cnt) $tempBadge = "<span class='status-badge warning' style='margin-left:10px;'>{$cnt} unsaved</span>";
            }

            // ✅ Build each child item
            $html .= "
            <div class='child-list-item {$statusClass}'
                data-child-id='{$c['id']}'
                data-name='{$c['full_name']}'
                data-status='{$statusClass}'
                data-age='{$c['age_months']}'
                data-has-entry='{$hasEntry}'>
                <div class='child-list-content'>
                    <div class='child-main-info'>
                        <h4>{$c['full_name']} {$tempBadge} {$entryLabel}</h4>
                        <span class='status-badge {$statusClass}'>" . ucfirst($status) . "</span>
                    </div>
                    <div class='child-secondary-info'>
                        <span class='bns-assigned'>BNS: {$c['bns_name']}</span>
                        <span class='last-record'>Last: {$lastRecord}</span>
                    </div>
                </div>
                <div class='child-actions'>
                    <button class='btn primary' onclick='openUpdateForm({$c['id']}, \"{$c['full_name']}\")'>Update Record</button>
                </div>
            </div>";
        }


        $html .= '</div>';
        return $html;

    } catch (PDOException $e) {
        error_log('Child list error: ' . $e->getMessage());
        return '<div class="card"><p>Error loading child list: ' . htmlspecialchars($e->getMessage()) . '</p></div>';
    }
}


/* ---------------------------------------------------------------- */
function generateChildRecordsHTML(int $child_id, int $bns_id, string $role = 'bns'): string
{
    global $pdo;
    try {
        // ---- verify child & permissions ---------------------------------
        if ($role === 'bns') {
            $stmt = $pdo->prepare("
                SELECT c.*, u.fullname as bns_name 
                FROM children c 
                LEFT JOIN users u ON c.assigned_bns_id = u.id 
                WHERE c.id = ? AND c.assigned_bns_id = ?
            ");
            $stmt->execute([$child_id, $bns_id]);
        } else {
            $stmt = $pdo->prepare("
                SELECT c.*, u.fullname as bns_name 
                FROM children c 
                LEFT JOIN users u ON c.assigned_bns_id = u.id 
                WHERE c.id = ?
            ");
            $stmt->execute([$child_id]);
        }
        $child = $stmt->fetch();
        if (!$child) {
            return '<div class="card"><p>Child not found or access denied.</p></div>';
        }

        // ---- fetch all records -----------------------------------------
        if ($role === 'bns') {
            $stmt = $pdo->prepare("
                SELECT cr.*, u.fullname as bns_name 
                FROM child_records cr 
                JOIN users u ON cr.bns_id = u.id 
                WHERE cr.child_id = ? AND cr.bns_id = ?
                ORDER BY cr.record_date DESC
            ");
            $stmt->execute([$child_id, $bns_id]);
        } else {
            $stmt = $pdo->prepare("
                SELECT cr.*, u.fullname as bns_name 
                FROM child_records cr 
                JOIN users u ON cr.bns_id = u.id 
                WHERE cr.child_id = ? 
                ORDER BY cr.record_date DESC
            ");
            $stmt->execute([$child_id]);
        }
        $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // ---- chart data ------------------------------------------------
        $chartData = ['labels' => [], 'weight' => [], 'height' => [], 'muac' => [], 'status' => []];
        $chronological = array_reverse($records);
        foreach ($chronological as $r) {
            $chartData['labels'][] = date('M j, Y', strtotime($r['record_date']));
            $chartData['weight'][] = (float)$r['weight'];
            $chartData['height'][] = (float)$r['height'];
            $chartData['muac'][]   = $r['muac'] ? (float)$r['muac'] : null;
            $chartData['status'][] = $r['health_status'];
        }

        // ---- start HTML ------------------------------------------------
        $ageMonths = floor((time() - strtotime($child['birthdate'])) / (30 * 24 * 60 * 60));
        $html = "
        <div class='child-records-header'>
            <button class='btn secondary' onclick='goBackToRecords()' style='margin-bottom:20px;'>
                Back to Records
            </button>
            <h2>Record History</h2>
            <div class='child-info-card' 
                style='background:#f9f9f9;padding:20px;border-radius:12px;
                        border:1px solid #ddd;margin-top:15px;margin-bottom:25px;
                        line-height:1.6;'>
                <div style='display:grid;grid-template-columns:1fr 1fr;gap:8px 20px;'>
                    <p><strong>Full Name:</strong> {$child['full_name']}</p>
                    <p><strong>Age:</strong> {$ageMonths} months</p>
                    <p><strong>Sex:</strong> {$child['sex']}</p>
                    <p><strong>Birthdate:</strong> {$child['birthdate']}</p>
                    <p><strong>Birthplace:</strong> {$child['birthplace']}</p>
                    <p><strong>Blood Type:</strong> {$child['blood_type']}</p>
                    <p><strong>Parent/Guardian:</strong> {$child['parent_guardian']}</p>
                    <p><strong>Current Address:</strong> {$child['current_address']}</p>
                    <p><strong>Assigned BNS:</strong> {$child['bns_name']}</p>
                </div>
            </div>

        </div>";

        // ---- no records ------------------------------------------------
        if (empty($records)) {
            $html .= '
            <div class="card" style="text-align:center;padding:40px;">
                <h3>No Records Available</h3>
                <p>No measurement records found for this child.</p>
                <p>Start by adding the first health record through the "Update Records" feature.</p>
            </div>
            <div class="charts-section" style="display:grid;grid-template-columns:1fr;gap:30px;margin-bottom:40px;">
                <div class="card"><h3>Height Progress (cm)</h3><div style="text-align:center;padding:60px;color:#666;"><p>No height data available</p></div></div>
                <div class="card"><h3>Weight Progress (kg)</h3><div style="text-align:center;padding:60px;color:#666;"><p>No weight data available</p></div></div>
                <div class="card"><h3>MUAC Progress (cm)</h3><div style="text-align:center;padding:60px;color:#666;"><p>No MUAC data available</p></div></div>
            </div>';
            return $html;
        }

        // ---- charts ----------------------------------------------------
        $html .= '
        <div class="charts-section" style="display:grid;grid-template-columns:1fr;gap:30px;margin-bottom:40px;">
            <div class="card">
                <h3>Height Progress (cm)</h3>
                <div class="chart-container"><canvas id="heightChart"></canvas></div>
            </div>
            <div class="card">
                <h3>Weight Progress (kg)</h3>
                <div class="chart-container"><canvas id="weightChart"></canvas></div>
            </div>
            <div class="card">
                <h3>MUAC Progress (cm)</h3>
                <div class="chart-container"><canvas id="muacChart"></canvas></div>
            </div>
        </div>';

        // ---- table -----------------------------------------------------
        $html .= '
        <div class="card">
            <h3>All Measurement Records</h3>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Date</th><th>BNS</th><th>Height (cm)</th><th>Weight (kg)</th>
                            <th>MUAC (cm)</th><th>Health Status</th><th>Notes</th>
                        </tr>
                    </thead>
                    <tbody>';

        foreach ($records as $r) {
            $statusClass = strtolower($r['health_status']);
            $muac        = $r['muac'] ?: 'N/A';
            $notes       = $r['notes'] ?: '';
            $html       .= "
                <tr>
                    <td>{$r['record_date']}</td>
                    <td>{$r['bns_name']}</td>
                    <td>{$r['height']}</td>
                    <td>{$r['weight']}</td>
                    <td>{$muac}</td>
                    <td><span class='status-badge {$statusClass}'>{$r['health_status']}</span></td>
                    <td>{$notes}</td>
                </tr>";
        }

        $html .= '</tbody></table></div></div>';   // end table & card

        // ---- Chart.js script (debug version) ---------------------------
        $html .= "
        <script>
        console.log('STARTING CHART SCRIPT');

        const data = " . json_encode($chartData) . ";
        console.log('Chart data:', data);

        if (!data || !data.labels) {
            console.error('Invalid chart data');
            return;
        }

        if (typeof Chart === 'undefined') {
            const s = document.createElement('script');
            s.src = 'https://cdn.jsdelivr.net/npm/chart.js';
            s.onload = () => setTimeout(createCharts, 300);
            s.onerror = () => console.error('Failed to load Chart.js');
            document.head.appendChild(s);
        } else {
            setTimeout(createCharts, 300);
        }

        function createCharts() {
            const cfg = [
                {id:'heightChart', label:'Height (cm)', data:data.height, color:'#42a5f5'},
                {id:'weightChart', label:'Weight (kg)', data:data.weight, color:'#66bb6a'},
                {id:'muacChart',   label:'MUAC (cm)',   data:data.muac,   color:'#ffca28'}
            ];
            cfg.forEach(c => {
                const ctx = document.getElementById(c.id);
                if (!ctx) return console.error('Canvas not found:', c.id);
                new Chart(ctx, {
                    type:'line',
                    data:{labels:data.labels, datasets:[{
                        label:c.label,
                        data:c.data,
                        borderColor:c.color,
                        backgroundColor:c.color+'20',
                        fill:true,
                        tension:0.3
                    }]},
                    options:{responsive:true, maintainAspectRatio:false}
                });
            });
        }
        </script>";

        return $html;
    } catch (PDOException $e) {
        error_log('Child records error: ' . $e->getMessage());
        return '<div class="card"><p>Error loading child records.</p></div>';
    }
}

/* ---------------------------------------------------------------- */
function generateCreateReportHTML(int $bns_id): string
{
    global $pdo;
    $current = date('Y-m');
    $first   = date('Y-m-01');
    $last    = date('Y-m-t');

    $stmt = $pdo->prepare("
        SELECT report_month, status, submitted_at 
        FROM reports WHERE bns_id = ? 
        ORDER BY submitted_at DESC LIMIT 5
    ");
    $stmt->execute([$bns_id]);
    $recent = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($recent) {
        $html .= '
        <div class="card" style="margin-bottom:20px;">
            <h3>Recent Reports</h3>
            <div class="table-responsive">
                <table class="data-table">
                    <thead><tr><th>Report Month</th><th>Status</th><th>Submitted</th></tr></thead>
                    <tbody>';
        foreach ($recent as $r) {
            $status = $r['status'];
            $date   = $r['submitted_at'] ? date('M j, Y', strtotime($r['submitted_at'])) : 'Draft';
            $html  .= "<tr>
                <td>{$r['report_month']}</td>
                <td><span class='status-badge {$status}'>" . ucfirst($status) . "</span></td>
                <td>{$date}</td>
            </tr>";
        }
        $html .= '</tbody></table></div></div>';
    }

    $html .= '
    <div class="card">
        <h3>Generate New Report</h3>
        <form id="createReportForm">
            <div class="form-group">
                <label for="reportMonth">Report Month:</label>
                <input type="month" id="reportMonth" value="' . $current . '" required>
                <small>Select the month you are reporting for</small>
            </div>
            <div class="form-row" style="display:grid;grid-template-columns:1fr 1fr;gap:15px;">
                <div class="form-group">
                    <label for="startDate">Start Date:</label>
                    <input type="date" id="startDate" value="' . $first . '" required>
                </div>
                <div class="form-group">
                    <label for="endDate">End Date:</label>
                    <input type="date" id="endDate" value="' . $last . '" required>
                </div>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn primary">Generate Report Preview</button>
            </div>
        </form>
        <div id="reportPreview" style="display:none;margin-top:30px;"></div>
    </div>';
    return $html;
}

/* ---------------------------------------------------------------- */
function generateAddRecordsHTML(): string
{
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT id, fullname, assigned_area FROM users WHERE role='bns' ORDER BY fullname");
        $stmt->execute();
        $bns = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $options = '';
        foreach ($bns as $b) {
            $options .= "<option value='{$b['id']}'>{$b['fullname']} - {$b['assigned_area']}</option>";
        }

        return '
        <div class="card">
            <div style="display:flex;gap:20px;margin-bottom:30px;border-bottom:1px solid #ddd;padding-bottom:20px;">
                <button class="btn primary" onclick="showAddChildForm()">Add Child</button>
                <button class="btn secondary" onclick="showAddBNSForm()">Add BNS</button>
            </div>

            <div id="addRecordsContent">
                <div id="addChildSection">
                    <h3>Add New Child</h3>
                    <form id="addChildForm" class="record-form" onsubmit="addChild(event); return false;">
                        <div class="form-group">
                            <label for="full_name">Full Name:</label>
                            <input type="text" id="full_name" required placeholder="Enter child\'s full name">
                        </div>

                        <div class="form-row" style="display:grid;grid-template-columns:1fr 1fr;gap:15px;">
                            <div class="form-group">
                                <label for="sex">Sex:</label>
                                <select id="sex" required>
                                    <option value="">Select</option>
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="birthdate">Birthdate:</label>
                                <input type="date" id="birthdate" required max="' . date('Y-m-d') . '">
                            </div>
                        </div>

                        <div class="form-row" style="display:grid;grid-template-columns:1fr 1fr;gap:15px;">
                            <div class="form-group">
                                <label for="birthplace">Birthplace:</label>
                                <input type="text" id="birthplace" placeholder="City/Municipality">
                            </div>
                            <div class="form-group">
                                <label for="blood_type">Blood Type:</label>
                                <select id="blood_type">
                                    <option value="">Select</option>
                                    <option value="A+">A+</option><option value="A-">A-</option>
                                    <option value="B+">B+</option><option value="B-">B-</option>
                                    <option value="AB+">AB+</option><option value="AB-">AB-</option>
                                    <option value="O+">O+</option><option value="O-">O-</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="parent_guardian">Parent/Guardian:</label>
                            <input type="text" id="parent_guardian" required placeholder="Full name of parent or guardian">
                        </div>

                        <div class="form-group">
                            <label for="current_address">Current Address:</label>
                            <textarea id="current_address" required rows="3" placeholder="Complete current address for monitoring"></textarea>
                        </div>

                        <div class="form-group">
                            <label for="assigned_bns_id">Assigned BNS:</label>
                            <select id="assigned_bns_id" required>
                                <option value="">Select BNS</option>' . $options . '
                            </select>
                            <small>Barangay Nutrition Scholar responsible for monitoring this child</small>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn primary">Add Child</button>
                            <button type="button" class="btn secondary" onclick="clearAddChildForm()">Clear Form</button>
                        </div>
                        <div id="addChildMessage"></div>
                    </form>
                </div>
            </div>
        </div>';
    } catch (PDOException $e) {
        error_log('Add records error: ' . $e->getMessage());
        return '<div class="card"><p>Error loading add records form.</p></div>';
    }
}

/* ---------------------------------------------------------------- */
function generateEditRecordsHTML(): string
{
    return '
    <div class="card">
        <div style="display:flex;gap:20px;margin-bottom:30px;border-bottom:1px solid #ddd;padding-bottom:20px;">
            <button class="btn primary" onclick="loadPage(\'admin-child-list\')">Edit Child</button>
            <button class="btn secondary" onclick="loadPage(\'admin-bns-list\')">Edit BNS</button>
        </div>
        <div id="editRecordsContent"><p>Select an option above to edit records.</p></div>
    </div>';
}

/* ---------------------------------------------------------------- */
function generateAdminChildListHTML(): string
{
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            SELECT 
                c.*,
                TIMESTAMPDIFF(MONTH, c.birthdate, CURDATE()) as age_months,
                cr.health_status as latest_status,
                cr.record_date as last_record_date,
                u.fullname as bns_name
            FROM children c
            LEFT JOIN child_records cr ON c.id = cr.child_id 
                AND cr.record_date = (SELECT MAX(record_date) FROM child_records WHERE child_id = c.id)
            LEFT JOIN users u ON c.assigned_bns_id = u.id
            ORDER BY c.full_name
        ");
        $stmt->execute();
        $children = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $html = '<h2>Edit Child Records</h2>';
        if (empty($children)) {
            return $html . '<div class="card"><p>No children found.</p></div>';
        }

        $html .= '
        <div class="filters">
            <input type="text" id="searchChildren" placeholder="Search children by name..." style="padding:8px;width:300px;border-color:#1976d2;">
            <select id="statusFilter" style="border-color:#1976d2;">
                <option value="">All Status</option>
                <option value="normal">Normal</option>
                <option value="underweight">Underweight</option>
                <option value="overweight">Overweight</option>
                <option value="critical">Critical</option>
            </select>
            <select id="sortFilter" style="border-color:#1976d2;">
                <option value="name_asc">A to Z</option>
                <option value="name_desc">Z to A</option>
                <option value="status">By Status</option>
            </select>
        </div>
        <div class="children-list">';

        foreach ($children as $c) {
            $status      = $c['latest_status'] ?: 'No records';
            $statusClass = strtolower(str_replace(' ', '-', $status));
            $lastRecord  = $c['last_record_date'] ? date('M j, Y', strtotime($c['last_record_date'])) : 'No records';

            $html .= "
            <div class='child-list-item {$statusClass}'
                 data-child-id='{$c['id']}' data-name='{$c['full_name']}' data-status='{$statusClass}' data-age='{$c['age_months']}'>
                <div class='child-list-content'>
                    <div class='child-main-info'>
                        <h4>{$c['full_name']}</h4>
                        <span class='status-badge {$statusClass}'>" . ucfirst($status) . "</span>
                    </div>
                    <div class='child-secondary-info'>
                        <span class='bns-assigned'>BNS: {$c['bns_name']}</span>
                        <span class='last-record'>Last: {$lastRecord}</span>
                    </div>
                </div>
                <div class='child-actions'>
                    <button class='btn primary' onclick='loadPage(\"admin-edit-child&child_id={$c['id']}\")'>Edit Child</button>
                    <button class='btn danger' onclick='deleteChild({$c['id']}, \"{$c['full_name']}\")'>Delete</button>
                </div>
            </div>";
        }
        $html .= '</div>';
        return $html;
    } catch (PDOException $e) {
        error_log('Admin child list error: ' . $e->getMessage());
        return '<div class="card"><p>Error loading child list.</p></div>';
    }
}

/* ---------------------------------------------------------------- */
function generateAdminBNSListHTML(): string
{
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            SELECT id, fullname, username, assigned_area, created_at 
            FROM users WHERE role='bns' ORDER BY fullname
        ");
        $stmt->execute();
        $bns = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $html = '<h2>Edit BNS Records</h2>';
        if (empty($bns)) {
            return $html . '<div class="card"><p>No BNS personnel found.</p></div>';
        }

        $html .= '
        <div class="filters">
            <input type="text" id="searchBNS" placeholder="Search BNS by name..." style="padding:8px;width:300px;border-color:#1976d2;">
            <select id="sortBNSFilter" style="border-color:#1976d2;">
                <option value="name_asc">A to Z</option>
                <option value="name_desc">Z to A</option>
                <option value="area">By Area</option>
            </select>
        </div>
        <div class="children-list">';

        foreach ($bns as $b) {
            $created = date('M j, Y', strtotime($b['created_at']));
            $html   .= "
            <div class='child-list-item' data-bns-id='{$b['id']}' data-name='{$b['fullname']}' data-area='{$b['assigned_area']}'>
                <div class='child-list-content'>
                    <div class='child-main-info'>
                        <h4>{$b['fullname']}</h4>
                        <span class='status-badge normal'>BNS</span>
                    </div>
                    <div class='child-secondary-info'>
                        <span class='bns-assigned'>Area: {$b['assigned_area']}</span>
                        <span class='last-record'>Created: {$created}</span>
                    </div>
                </div>
                <div class='child-actions'>
                    <button class='btn primary' onclick='openAdminEditBNS({$b['id']}, \"".addslashes($b['fullname'])."\")'>Edit BNS</button>
                    <button class='btn danger' onclick='deleteBNS({$b['id']}, \"".addslashes($b['fullname'])."\")'>Delete</button>
                </div>
            </div>";
        }
        $html .= '</div>';
        return $html;
    } catch (PDOException $e) {
        error_log('Admin BNS list error: ' . $e->getMessage());
        return '<div class="card"><p>Error loading BNS list.</p></div>';
    }
}

/* ---------------------------------------------------------------- */
function generateAdminChildEditHTML(int $child_id): string
{
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            SELECT c.*, u.fullname as bns_name 
            FROM children c 
            LEFT JOIN users u ON c.assigned_bns_id = u.id 
            WHERE c.id = ?
        ");
        $stmt->execute([$child_id]);
        $c = $stmt->fetch();
        if (!$c) return '<div class="card"><p>Child not found.</p></div>';

        $stmt = $pdo->prepare("SELECT id, fullname, assigned_area FROM users WHERE role='bns' ORDER BY fullname");
        $stmt->execute();
        $bns = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $opts = '';
        foreach ($bns as $b) {
            $sel = $b['id'] == $c['assigned_bns_id'] ? 'selected' : '';
            $opts .= "<option value='{$b['id']}' $sel>{$b['fullname']} - {$b['assigned_area']}</option>";
        }

        // ✅ Safe, clean HTML with correct quote escaping
        return '
        <div class="card">
            <div style="display:flex;gap:10px;margin-bottom:20px;border-bottom:1px solid #ddd;padding-bottom:10px;">
                <button class="btn secondary" onclick="loadPage(\'admin-child-list\')">Back to Child List</button>
                <button class="btn primary" id="editChildBtn" onclick="showEditChildForm()">Edit Child</button>
                <button class="btn warning" id="editRecordBtn" onclick="showEditRecordHistory(' . $c['id'] . ')">Edit Record History</button>
            </div>

            <div id="editChildContent">
                <h2>Edit Child: ' . htmlspecialchars($c['full_name']) . '</h2>
                <form id="editChildForm" class="record-form">
                    <input type="hidden" id="childId" value="' . $c['id'] . '">

                    <div class="form-group">
                        <label for="full_name">Full Name:</label>
                        <input type="text" id="full_name" value="' . htmlspecialchars($c['full_name']) . '" required>
                    </div>

                    <div class="form-row" style="display:grid;grid-template-columns:1fr 1fr;gap:15px;">
                        <div class="form-group">
                            <label for="sex">Sex:</label>
                            <select id="sex" required>
                                <option value="">Select</option>
                                <option value="Male" ' . ($c['sex']=='Male'?'selected':'') . '>Male</option>
                                <option value="Female" ' . ($c['sex']=='Female'?'selected':'') . '>Female</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="birthdate">Birthdate:</label>
                            <input type="date" id="birthdate" value="' . $c['birthdate'] . '" required max="' . date('Y-m-d') . '">
                        </div>
                    </div>

                    <div class="form-row" style="display:grid;grid-template-columns:1fr 1fr;gap:15px;">
                        <div class="form-group">
                            <label for="birthplace">Birthplace:</label>
                            <input type="text" id="birthplace" value="' . htmlspecialchars($c['birthplace']) . '">
                        </div>
                        <div class="form-group">
                            <label for="blood_type">Blood Type:</label>
                            <select id="blood_type">
                                <option value="">Select</option>
                                <option value="A+" ' . ($c['blood_type']=='A+'?'selected':'') . '>A+</option>
                                <option value="A-" ' . ($c['blood_type']=='A-'?'selected':'') . '>A-</option>
                                <option value="B+" ' . ($c['blood_type']=='B+'?'selected':'') . '>B+</option>
                                <option value="B-" ' . ($c['blood_type']=='B-'?'selected':'') . '>B-</option>
                                <option value="AB+" ' . ($c['blood_type']=='AB+'?'selected':'') . '>AB+</option>
                                <option value="AB-" ' . ($c['blood_type']=='AB-'?'selected':'') . '>AB-</option>
                                <option value="O+" ' . ($c['blood_type']=='O+'?'selected':'') . '>O+</option>
                                <option value="O-" ' . ($c['blood_type']=='O-'?'selected':'') . '>O-</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="parent_guardian">Parent/Guardian:</label>
                        <input type="text" id="parent_guardian" value="' . htmlspecialchars($c['parent_guardian']) . '" required>
                    </div>

                    <div class="form-group">
                        <label for="current_address">Current Address:</label>
                        <textarea id="current_address" required rows="3">' . htmlspecialchars($c['current_address']) . '</textarea>
                    </div>

                    <div class="form-group">
                        <label for="assigned_bns_id">Assigned BNS:</label>
                        <select id="assigned_bns_id" required>
                            <option value="">Select BNS</option>' . $opts . '
                        </select>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn primary">Update Child</button>
                        <button type="button" class="btn danger" onclick="deleteChild(' . $c['id'] . ', \'' . addslashes($c['full_name']) . '\')">Delete Child</button>
                    </div>
                    <div id="editChildMessage"></div>
                </form>
            </div>

            <div id="recordHistorySection" style="display:none;"></div>
        </div>';
    } catch (PDOException $e) {
        error_log("Admin child edit error: " . $e->getMessage());
        return "<div class='card'><p>Error loading child edit form.</p></div>";
    }
}


/* ---------------------------------------------------------------- */
function generateAdminBNSEditHTML(int $bns_id): string
{
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id=? AND role='bns'");
        $stmt->execute([$bns_id]);
        $b = $stmt->fetch();
        if (!$b) return '<div class="card"><p>BNS not found.</p></div>';

        return "
        <div class='card'>
            <button class='btn secondary' onclick='loadPage(\"admin-bns-list\")' style='margin-bottom:20px;'>
                Back to BNS List
            </button>
            <h2>Edit BNS: {$b['fullname']}</h2>
            <form id='editBNSForm' class='record-form'>
                <input type='hidden' id='bnsId' value='{$b['id']}'>
                <div class='form-group'>
                    <label for='bnsFullname'>Full Name:</label>
                    <input type='text' id='bnsFullname' value='{$b['fullname']}' required>
                </div>
                <div class='form-group'>
                    <label for='bnsUsername'>Username:</label>
                    <input type='text' id='bnsUsername' value='{$b['username']}' required>
                </div>
                <div class='form-group'>
                    <label for='bnsArea'>Assigned Area:</label>
                    <input type='text' id='bnsArea' value='{$b['assigned_area']}' required>
                </div>
                <div class='form-group'>
                    <label for='bnsPassword'>New Password (leave blank to keep current):</label>
                    <input type='password' id='bnsPassword' placeholder='Enter new password'>
                    <small>Minimum 6 characters</small>
                </div>
                <div class='form-actions'>
                    <button type='submit' class='btn primary'>Update BNS</button>
                    <button type='button' class='btn danger' onclick='deleteBNS({$b['id']}, \"{$b['fullname']}\")'>Delete BNS</button>
                </div>
                <div id='editBNSMessage'></div>
            </form>
        </div>";
    } catch (PDOException $e) {
        error_log('Admin BNS edit error: ' . $e->getMessage());
        return '<div class="card"><p>Error loading BNS edit form.</p></div>';
    }
}

/* ---------------------------------------------------------------- */
function generateAdminReportsHTML(): string
{
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            SELECT r.*, u.fullname as bns_name, u.assigned_area 
            FROM reports r 
            JOIN users u ON r.bns_id = u.id 
            ORDER BY r.submitted_at DESC
        ");
        $stmt->execute();
        $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);


        if (empty($reports)) {
            return $html . '<div class="card"><p>No reports found.</p></div>';
        }


        foreach ($reports as $r) {
            $statusClass = $r['status'];
            $submitted   = date('M j, Y g:i A', strtotime($r['submitted_at']));
            $html       .= "
            <div class='report-card {$statusClass}' data-report-id='{$r['id']}'>
                <div class='report-header'>
                    <div>
                        <h3>Report from {$r['bns_name']}</h3>
                        <p style='margin:5px 0;color:#666;'>{$r['assigned_area']}</p>
                    </div>
                    <span class='status-badge {$statusClass}'>" . ucfirst($statusClass) . "</span>
                </div>
                <div class='report-details'>
                    <p><strong>Report Month:</strong> {$r['report_month']}</p>
                    <p><strong>Submitted:</strong> {$submitted}</p>
                </div>
                <div class='report-actions'>
                    <button class='btn primary' onclick='viewReportDetails({$r['id']})'>View Details</button>
                    <button class='btn success' onclick='acceptReport({$r['id']}, \"accepted\")'>Accept</button>
                    <button class='btn danger' onclick='declineReport({$r['id']}, \"declined\")'>Decline</button>
                    <button class='btn danger' onclick='deleteReport({$r['id']}, \"deleted\")'>Delete</button>
                </div>
            </div>";
        }
        $html .= '</div>';
        return $html;
    } catch (PDOException $e) {
        error_log('Admin reports error: ' . $e->getMessage());
        return '<div class="card"><p>Error loading reports.</p></div>';
    }
}

/* ---------------------------------------------------------------- */
function generateNotificationsHTML(int $user_id): string {
    try {
        // ✅ Make sure PDO is available
        global $pdo;

        $stmt = $pdo->prepare("
            SELECT id, message, is_read, date_created
            FROM notifications
            WHERE user_id = ?
            ORDER BY date_created DESC
        ");
        $stmt->execute([$user_id]);
        $notifs = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($notifs)) {
            return '<div class="card"><p>No notifications found.</p></div>';
        }

        $html = '<div class="notifications-list">';
        foreach ($notifs as $n) {
            $read = $n['is_read'] ? 'read' : 'unread';
            $notifId = $n['id'];
            $message = htmlspecialchars($n['message'], ENT_QUOTES, 'UTF-8');
            $ago = date("M d, Y h:i A", strtotime($n['date_created']));

            $html .= "
                <div class='notification-item {$read}' data-notification-id='{$notifId}'>
                    <div class='notification-content'>
                        <p>{$message}</p>
                        <small>{$ago}</small>
                    </div>
                    <button class='btn danger btn-sm delete-notif-btn' data-id='{$notifId}'>Delete</button>
                </div>
            ";
        }
        $html .= '</div>';
        return $html;

    } catch (Exception $e) {
        error_log('Notifications error: ' . $e->getMessage());
        return '<div class="card"><p>Error loading notifications.</p></div>';
    }
}


/* ---------------------------------------------------------------- */
function getTimeAgo(string $datetime): string
{
    $diff = time() - strtotime($datetime);
    if ($diff < 60) return 'Just now';
    if ($diff < 3600) return floor($diff / 60) . ' minutes ago';
    if ($diff < 86400) return floor($diff / 3600) . ' hours ago';
    return floor($diff / 86400) . ' days ago';
}
?>