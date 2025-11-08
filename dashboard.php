<?php
// Set session settings BEFORE starting the session
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);

session_start();

if (!isset($_SESSION['user_id'])) {
    // Redirect to login with error message
    header('Location: login.html?error=session_expired');
    exit;
}

// Session timeout (8 hours)
$session_duration = 8 * 60 * 60; // 8 hours in seconds
if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time'] > $session_duration)) {
    session_unset();
    session_destroy();
    header('Location: login.html?error=session_timeout');
    exit;
}

// Regenerate session ID periodically for security
if (!isset($_SESSION['last_regeneration']) || (time() - $_SESSION['last_regeneration'] > 300)) {
    session_regenerate_id(true);
    $_SESSION['last_regeneration'] = time();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard • Child Health Monitoring</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="assets/css/child_monitoring.css">
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <div class="brand">
                Main Menu
            </div>
        </div>
        
        <div class="sidebar-menu">
            <?php if ($_SESSION['role'] === 'bns'): ?>
                <!-- BNS Menu -->
                <button class="menu-item active" data-page="dashboard">
                    <span>📊 Dashboard</span>
                </button>
                <button class="menu-item" data-page="view-records">
                    <span>👁️ View Records</span>
                </button>
                <button class="menu-item" data-page="update-records">
                    <span>✏️ Update Records</span>
                </button>
                <button class="menu-item" data-page="create-report">
                    <span>📋 Create Report</span>
                </button>
                <button class="menu-item" data-page="notifications">
                    <span>🔔 Notifications</span>
                </button>
            <?php else: ?>
                <!-- Admin Menu -->
                <button class="menu-item active" data-page="dashboard">
                    <span>📊 Dashboard</span>
                </button>
                <button class="menu-item" data-page="view-records">
                    <span>👁️ View Records</span>
                </button>
                <button class="menu-item" data-page="add-records">
                    <span>➕ Add Records</span>
                </button>
                <button class="menu-item" data-page="edit-records">
                    <span>✏️ Edit Records</span>
                </button>
                <button class="menu-item" data-page="view-reports">
                    <span>📋 View Reports</span>
                </button>
            <?php endif; ?>
            
            <a href="backend/auth/logout.php" class="menu-item logout">
                <span>🚪 Logout</span>
            </a>
        </div>
    </div>
    
    <!-- Main Content -->
    <div class="main-content">
        <!-- Header -->
        <div class="header">
            <button class="mobile-menu-btn" onclick="toggleSidebar()">☰</button>
            <div class="header-title">Child Health Monitoring</div>
        </div>

        
        <!-- Content Area -->
        <div class="content" id="contentArea">
            <!-- Dashboard content will be loaded here -->

                
            <div id="dashboardContent">

                <div class="dashboard-profile-card" style="
                    display: flex;
                    align-items: center;
                    gap: 20px;
                    background: white;
                    padding: 20px;
                    border-radius: 10px;
                    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
                    margin-bottom: 25px;
                ">
                    <div class="profile-icon" style="
                        font-size: 48px;
                        width: 80px;
                        height: 80px;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        border-radius: 50%;
                        background: #e8f5e9;
                    ">👤</div>
                    <div>
                        <h2 style="margin: 0;"><?php echo htmlspecialchars($_SESSION['fullname']); ?></h2>
                        <p style="margin: 5px 0;">Role: <strong><?php echo ucfirst($_SESSION['role']); ?></strong></p>
                        <p style="margin: 5px 0;">Assigned Area: <strong><?php echo htmlspecialchars($_SESSION['assigned_area']); ?></strong></p>
                    </div>
                </div>

                <div class="dashboard-cards">
                    <div class="card">
                        <h3>Children Monitored</h3>
                        <div class="number" id="childrenCount">0</div>
                    </div>
                    
                    <?php if ($_SESSION['role'] === 'admin'): ?>
                    <div class="card">
                        <h3>BNS Personnel</h3>
                        <div class="number" id="bnsCount">0</div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="card">
                        <h3>Pending Reports</h3>
                        <div class="number" id="pendingReports">0</div>
                    </div>
                    
                    <div class="card">
                        <h3>This Month Records</h3>
                        <div class="number" id="monthRecords">0</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>

        function openEditBnsModal(bnsId) {
            fetch(`admin_edit_bns.php?bns_id=${bnsId}`, { credentials: 'include' })
                .then(r => r.json())
                .then(res => {
                    if (!res.success) { alert(res.message); return; }
                    document.body.insertAdjacentHTML('beforeend', res.html);

                    const form = document.getElementById('editBnsForm');
                    form.onsubmit = function(e) {
                        e.preventDefault();
                        const payload = Object.fromEntries(new FormData(form).entries());

                        fetch('backend/records/edit_bns.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify(payload),
                            credentials: 'include'
                        })
                        .then(r => r.json())
                        .then(resp => {
                            if (resp.success) {
                                alert('BNS updated!');
                                closeModal('editBnsModal', 'editBnsBackdrop');
                                loadPage('bns-management'); // refresh list
                            } else {
                                alert(resp.message);
                            }
                        });
                    };
                })
                .catch(() => alert('Network error'));
        }

        // Global variables
        const currentUser = {
            id: <?php echo $_SESSION['user_id']; ?>,
            role: '<?php echo $_SESSION['role']; ?>',
            fullname: '<?php echo $_SESSION['fullname']; ?>',
            area: '<?php echo $_SESSION['assigned_area']; ?>'
        };

        console.log('Dashboard initialized for:', currentUser);

        // Mobile sidebar toggle
        function toggleSidebar() {
            const sidebar = document.querySelector('.sidebar');
            sidebar.classList.toggle('active');
        }

        // Page navigation
        document.querySelectorAll('.menu-item[data-page]').forEach(item => {
            item.addEventListener('click', function(e) {
                e.preventDefault();
                
                // Remove active class from all items
                document.querySelectorAll('.menu-item').forEach(i => i.classList.remove('active'));
                // Add active class to clicked item
                this.classList.add('active');
                
                const page = this.getAttribute('data-page');
                loadPage(page);
            });
        });

        // Helper function to escape HTML
        function escapeHtml(unsafe) {
            if (!unsafe) return '';
            return unsafe
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
        }

        // Enhanced error handling for JSON parsing
        function safeJsonParse(text) {
            try {
                return JSON.parse(text);
            } catch (error) {
                console.error('JSON parse error:', error);
                console.error('Response text:', text);
                return {
                    success: false,
                    message: 'Server returned invalid response. Please check if the backend services are running properly.'
                };
            }
        }

        // DEBUG FUNCTION: Test edit functionality
        async function testEditFunctionality() {
            console.log('Testing edit functionality...');
            
            // Test edit_child.php
            try {
                const testData = {
                    child_id: 1,
                    full_name: 'Test Child Updated',
                    sex: 'Male',
                    birthdate: '2020-01-01',
                    birthplace: 'Test City',
                    blood_type: 'O+',
                    parent_guardian: 'Test Parent',
                    current_address: 'Test Address',
                    assigned_bns_id: 2
                };
                
                const response = await fetch('backend/records/edit_child.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(testData),
                    credentials: 'include'
                });
                
                const result = await response.text();
                console.log('Edit child test response:', result);
            } catch (error) {
                console.error('Edit child test failed:', error);
            }
        }

        function openAdminEditChild(childId, childName) {
            console.log('Opening edit form for child:', childId, childName);
            // Call loadPage directly with the correct format
            loadPage(`admin-edit-child&child_id=${childId}`);
        }

async function openAdminEditBNS(bnsId, bnsName) {
    console.log('Opening edit form for BNS:', bnsId, bnsName);
    const contentArea = document.getElementById('contentArea');
    if (!contentArea) return;

    contentArea.innerHTML = `
        <div style="padding: 40px; text-align: center;">
            <div class="loading"></div> Loading BNS details...
        </div>
    `;

    try {
        const response = await fetch(`backend/admin_edit_bns.php?bns_id=${bnsId}`, { credentials: 'include' });
        const text = await response.text();

        let res;
        try {
            res = JSON.parse(text);
        } catch (err) {
            alert("⚠️ Invalid response from server:\n" + text.substring(0, 200));
            throw err;
        }

        if (!res.success) {
            contentArea.innerHTML = `<div style="padding: 40px; color: red;">Error: ${res.message}</div>`;
            return;
        }

        // ✅ Rebuild edit form styled like Add BNS
        contentArea.innerHTML = `
            <div class="card" style="padding: 30px; border-radius: 8px; background: white; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                <h2 style="color: #2e7d32; font-weight: 600; margin-bottom: 20px;">Edit BNS Record</h2>

                <form id="editBnsForm" class="record-form" style="display: flex; flex-direction: column; gap: 18px;">

                    <input type="hidden" name="bns_id" value="${res.bns.id}">

                    <div class="form-group" style="display: flex; flex-direction: column;">
                        <label style="font-weight: 500; color: #333;">Full Name:</label>
                        <input type="text" name="fullname" value="${res.bns.fullname}" required
                            placeholder="Enter BNS full name"
                            style="padding: 10px; border: 1px solid #ccc; border-radius: 5px;">
                    </div>

                    <div class="form-row" style="display: flex; gap: 20px;">
                        <div class="form-group" style="flex: 1; display: flex; flex-direction: column;">
                            <label style="font-weight: 500; color: #333;">Username:</label>
                            <input type="text" name="username" value="${res.bns.username}" required
                                placeholder="Enter username for login"
                                style="padding: 10px; border: 1px solid #ccc; border-radius: 5px;">
                        </div>
                        <div class="form-group" style="flex: 1; display: flex; flex-direction: column;">
                            <label style="font-weight: 500; color: #333;">New Password:</label>
                            <input type="password" name="password"
                                placeholder="Enter new password (optional)"
                                style="padding: 10px; border: 1px solid #ccc; border-radius: 5px;">
                        </div>
                    </div>

                    <div class="form-group" style="display: flex; flex-direction: column;">
                        <label style="font-weight: 500; color: #333;">Assigned Area:</label>
                        <input type="text" name="assigned_area" value="${res.bns.assigned_area}" required
                            placeholder="Enter assigned barangay/area"
                            style="padding: 10px; border: 1px solid #ccc; border-radius: 5px;">
                    </div>

                    <div class="form-actions" style="margin-top: 25px; display: flex; gap: 10px;">
                        <button type="submit" class="btn primary" 
                            style="background: #2e7d32; color: white; border: none; padding: 10px 16px; border-radius: 6px; cursor: pointer;">
                            Save Changes
                        </button>
                        <button type="button" class="btn secondary" id="cancelEditBns"
                            style="background: #888; color: white; border: none; padding: 10px 16px; border-radius: 6px; cursor: pointer;">
                            Cancel
                        </button>
                    </div>

                    <div id="editBnsMessage" style="margin-top: 15px; font-weight: 500;"></div>
                </form>
            </div>
        `;

        // ✅ Save changes handler
        const form = document.getElementById('editBnsForm');
        const msgBox = document.getElementById('editBnsMessage');
        form.addEventListener('submit', async function (e) {
            e.preventDefault();
            msgBox.innerHTML = '<p style="color:#555;">⏳ Saving changes...</p>';

            const payload = Object.fromEntries(new FormData(form).entries());
            try {
                const saveRes = await fetch('backend/records/edit_bns.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload),
                    credentials: 'include'
                });

                const resultText = await saveRes.text();
                const result = JSON.parse(resultText);

                if (result.success) {
                    msgBox.innerHTML = '<p style="color:green;">✅ BNS updated successfully!</p>';
                    setTimeout(() => loadPage('admin-bns-list'), 1500);
                } else {
                    msgBox.innerHTML = `<p style="color:red;">❌ ${result.message}</p>`;
                }
            } catch (err) {
                console.error(err);
                msgBox.innerHTML = '<p style="color:red;">❌ Network or server error.</p>';
            }
        });

        // ❌ Cancel button handler
        document.getElementById('cancelEditBns').addEventListener('click', () => {
            loadPage('admin-bns-list');
        });

    } catch (error) {
        console.error('Error loading BNS form:', error);
        contentArea.innerHTML = `
            <div style="padding: 40px; color: red;">
                ⚠️ Failed to load edit form. Please try again later.
            </div>
        `;
    }
}



        // Edit Child Form Handler
        function initializeEditChildForm() {
            console.log('🔄 Looking for edit child form...');
            const form = document.getElementById('editChildForm');
            
            if (form) {
                console.log('✅ Edit child form found, attaching submit handler');
                // Remove any existing listeners to prevent duplicates
                form.removeEventListener('submit', updateChild);
                // Add the submit handler
                form.addEventListener('submit', updateChild);
                console.log('✅ Edit child form handler initialized successfully');
            } else {
                console.warn('⚠️ Edit child form not found in DOM. Available forms:');
                // Debug: list all forms on the page
                const allForms = document.querySelectorAll('form');
                allForms.forEach((f, i) => {
                    console.log(`Form ${i}:`, f.id, f);
                });
            }
        }

        // Edit BNS Form Handler
        function initializeEditBNSForm() {
            const form = document.getElementById('editBnsForm') || document.getElementById('editBNSForm');
            if (form) {
                console.log('✅ BNS form detected, binding submit handler');
                form.addEventListener('submit', updateBNS);
            } else {
                console.warn('⚠️ Edit BNS form not found in DOM.');
            }
        }


        // Update Child with better error handling
        async function updateChild(e) {
            e.preventDefault();
            
            const formData = {
                child_id: document.getElementById('childId').value,
                full_name: document.getElementById('full_name').value,
                sex: document.getElementById('sex').value,
                birthdate: document.getElementById('birthdate').value,
                birthplace: document.getElementById('birthplace').value,
                blood_type: document.getElementById('blood_type').value,
                parent_guardian: document.getElementById('parent_guardian').value,
                current_address: document.getElementById('current_address').value,
                assigned_bns_id: document.getElementById('assigned_bns_id').value
            };
            
            console.log('Sending update data:', formData);
            
            // Validation
            const required = ['full_name', 'sex', 'birthdate', 'parent_guardian', 'current_address', 'assigned_bns_id'];
            for (const field of required) {
                if (!formData[field]) {
                    showEditChildMessage('Please fill in all required fields', 'error');
                    return;
                }
            }
            
            const btn = e.target.querySelector('button[type="submit"]');
            const originalText = btn.textContent;
            btn.disabled = true;
            btn.textContent = 'Updating...';
            
            try {
                const response = await fetch('backend/records/edit_child.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(formData),
                    credentials: 'include'
                });
                
                console.log('Response status:', response.status);
                const responseText = await response.text();
                console.log('Raw response:', responseText);
                
                const data = safeJsonParse(responseText);
                console.log('Parsed response:', data);
                
                if (data.success) {
                    showEditChildMessage('✅ Child updated successfully!', 'success');
                    setTimeout(() => {
                        loadPage('admin-child-list');
                    }, 2000);
                } else {
                    showEditChildMessage('Error: ' + data.message, 'error');
                }
            } catch (error) {
                console.error('Error updating child:', error);
                showEditChildMessage('Network error. Please try again.', 'error');
            } finally {
                btn.disabled = false;
                btn.textContent = originalText;
            }
        }

        // Show message function
        function showEditChildMessage(message, type) {
            const messageDiv = document.getElementById('editChildMessage');
            if (messageDiv) {
                const color = type === 'success' ? 'green' : 'red';
                messageDiv.innerHTML = message ? 
                    `<div style="color: ${color}; margin-top: 10px; padding: 10px; border-radius: 5px; background: ${color}20; border-left: 4px solid ${color};">${message}</div>` : '';
            }
        }

        // Update BNS with better error handling
        async function updateBNS(e) {
            e.preventDefault();
            
            const formData = {
                bns_id: document.getElementById('bnsId').value,
                fullname: document.getElementById('bnsFullname').value,
                username: document.getElementById('bnsUsername').value,
                assigned_area: document.getElementById('bnsArea').value,
                password: document.getElementById('bnsPassword').value
            };
            
            console.log('Sending update data:', formData);
            
            // Validation
            if (!formData.fullname || !formData.username || !formData.assigned_area) {
                showEditBNSMessage('Please fill in all required fields', 'error');
                return;
            }
            
            if (formData.password && formData.password.length < 6) {
                showEditBNSMessage('Password must be at least 6 characters long', 'error');
                return;
            }
            
            const btn = e.target.querySelector('button[type="submit"]');
            const originalText = btn.textContent;
            btn.disabled = true;
            btn.textContent = 'Updating...';
            
            try {
                const response = await fetch('backend/records/edit_bns.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(formData),
                    credentials: 'include'
                });
                
                console.log('Response status:', response.status);
                const responseText = await response.text();
                console.log('Raw response:', responseText);
                
                const data = safeJsonParse(responseText);
                console.log('Parsed response:', data);
                
                if (data.success) {
                    showEditBNSMessage('✅ BNS updated successfully!', 'success');
                    setTimeout(() => {
                        loadPage('admin-bns-list');
                    }, 2000);
                } else {
                    showEditBNSMessage('Error: ' + data.message, 'error');
                }
            } catch (error) {
                console.error('Error updating BNS:', error);
                showEditBNSMessage('Network error. Please try again.', 'error');
            } finally {
                btn.disabled = false;
                btn.textContent = originalText;
            }
        }

        // Delete Child (from list view)
        async function deleteChild(childId, childName) {
            if (!confirm(`Are you sure you want to delete "${childName}"? This will also delete all their health records. This action cannot be undone.`)) {
                return;
            }
            
            try {
                const response = await fetch('backend/records/delete_child.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ child_id: childId }),
                    credentials: 'include'
                });
                
                const data = await safeJsonParse(await response.text());
                
                if (data.success) {
                    alert('✅ ' + data.message);
                    loadPage('admin-child-list');
                } else {
                    alert('❌ ' + data.message);
                }
            } catch (error) {
                console.error('Error deleting child:', error);
                alert('❌ Network error. Please try again.');
            }
        }

        // ===============================
        // DELETE BNS FUNCTION (SAFE + CLEAN)
        // ===============================
        async function deleteBNS(bnsId, bnsName) {
            if (!confirm(`⚠️ Are you sure you want to delete BNS "${bnsName}"?\n\nThis action cannot be undone.`)) return;

            const contentArea = document.getElementById('contentArea');
            contentArea.innerHTML = `
                <div class="card" style="padding: 30px; text-align: center;">
                    <p style="color: #555;">⏳ Deleting BNS <strong>${bnsName}</strong>...</p>
                </div>
            `;

            try {
                const response = await fetch('backend/records/delete_bns.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ bns_id: bnsId }),
                    credentials: 'include'
                });

                const text = await response.text();
                const res = JSON.parse(text);

                if (res.success) {
                    contentArea.innerHTML = `
                        <div class="card" style="padding: 30px; text-align: center;">
                            <p style="color: green;">✅ ${res.message}</p>
                        </div>
                    `;
                    setTimeout(() => loadPage('admin-bns-list'), 1500);
                } else {
                    contentArea.innerHTML = `
                        <div class="card" style="padding: 30px; text-align: center;">
                            <p style="color: red;">❌ ${res.message}</p>
                        </div>
                    `;
                }
            } catch (error) {
                console.error('Error deleting BNS:', error);
                contentArea.innerHTML = `
                    <div class="card" style="padding: 30px; text-align: center;">
                        <p style="color: red;">❌ Network or server error while deleting BNS.</p>
                    </div>
                `;
            }
        }



        // Message functions
        function showEditChildMessage(message, type) {
            const messageDiv = document.getElementById('editChildMessage');
            if (messageDiv) {
                const color = type === 'success' ? 'green' : 'red';
                messageDiv.innerHTML = message ? 
                    `<div style="color: ${color}; margin-top: 10px; padding: 10px; border-radius: 5px; background: ${color}20; border-left: 4px solid ${color};">${message}</div>` : '';
            }
        }

        function showEditBNSMessage(message, type) {
            const messageDiv = document.getElementById('editBNSMessage');
            if (messageDiv) {
                const color = type === 'success' ? 'green' : 'red';
                messageDiv.innerHTML = message ? 
                    `<div style="color: ${color}; margin-top: 10px; padding: 10px; border-radius: 5px; background: ${color}20; border-left: 4px solid ${color};">${message}</div>` : '';
            }
        }

        // Load page content
        async function loadPage(page) {
            const contentArea = document.getElementById('contentArea');
            
            // Show loading
            contentArea.innerHTML = '<div style="padding: 40px; text-align: center;"><div class="loading"></div> Loading...</div>';
            
            try {
                console.log('Loading page:', page);
                
                if (page === 'dashboard') {
                    const content = `
                        <div id="dashboardContent">
                            <div class="dashboard-profile-card" style="
                                display: flex;
                                align-items: center;
                                gap: 20px;
                                background: white;
                                padding: 20px;
                                border-radius: 10px;
                                box-shadow: 0 2px 8px rgba(0,0,0,0.1);
                                margin-bottom: 25px;
                            ">
                                <div class="profile-icon" style="
                                    font-size: 48px;
                                    width: 80px;
                                    height: 80px;
                                    display: flex;
                                    align-items: center;
                                    justify-content: center;
                                    border-radius: 50%;
                                    background: #e8f5e9;
                                ">👤</div>
                                <div>
                                    <h2 style="margin: 0;">${escapeHtml(currentUser.fullname)}</h2>
                                    <p style="margin: 5px 0;">Role: <strong>${escapeHtml(currentUser.role)}</strong></p>
                                    <p style="margin: 5px 0;">Assigned Area: <strong>${escapeHtml(currentUser.area)}</strong></p>
                                </div>
                            </div>

                            <div class="dashboard-cards">
                                <div class="card"><h3>Children Monitored</h3><div class="number" id="childrenCount">0</div></div>
                                ${currentUser.role === 'admin' ? '<div class="card"><h3>BNS Personnel</h3><div class="number" id="bnsCount">0</div></div>' : ''}
                                <div class="card"><h3>Pending Reports</h3><div class="number" id="pendingReports">0</div></div>
                                <div class="card"><h3>This Month Records</h3><div class="number" id="monthRecords">0</div></div>
                            </div>
                        </div>
                    `;

                    document.getElementById('contentArea').innerHTML = content;
                    await loadDashboard();
                    return;
                } else if ((page === 'update-records' || page === 'view-records') && currentUser.role === 'bns') {
                    // Both Update Records and View Records use the same child list for BNS
                    await loadChildList(page);
                } else if (page === 'profile') {
                    await loadProfile();
                } else {
                    const response = await fetch(`backend/dashboard/load_page.php?page=${page}`, {
                        credentials: 'include'
                    });
                    
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    
                    const responseText = await response.text();
                    console.log('Raw response for', page, ':', responseText);
                    
                    let data = safeJsonParse(responseText);
                    
                    if (data.success) {
                        contentArea.innerHTML = data.html;
                        
                        // Wait a tiny bit for DOM to render, then initialize
                        setTimeout(() => {
                            initializePageScripts(page.split('?')[0]);
                        }, 100);
                    } else {
                        contentArea.innerHTML = `<div style="padding: 40px; text-align: center; color: red;">Error: ${data.message}</div>`;
                        console.error('Page load error:', data.message);
                    }
                }
            } catch (error) {
                console.error('Error loading page:', error);
                contentArea.innerHTML = `
                    <div style="padding: 40px; text-align: center; color: red;">
                        <h3>Network Error</h3>
                        <p>Could not load the page.</p>
                        <p><strong>Error details:</strong> ${error.message}</p>
                        <p><strong>Page requested:</strong> ${page}</p>
                    </div>
                `;
            }
        }

        // Load dashboard data
        async function loadDashboard() {
            try {
                const response = await fetch('backend/dashboard/get_stats.php', {
                    credentials: 'include'
                });
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                // First get as text to debug
                const responseText = await response.text();
                console.log('Dashboard stats raw response:', responseText);
                
                let data = safeJsonParse(responseText);
                
                if (data.success) {
                    // Safely update counts
                    const childrenCountEl = document.getElementById('childrenCount');
                    const bnsCountEl = document.getElementById('bnsCount');
                    const pendingReportsEl = document.getElementById('pendingReports');
                    const monthRecordsEl = document.getElementById('monthRecords');
                    
                    if (childrenCountEl) childrenCountEl.textContent = data.childrenCount || 0;
                    if (bnsCountEl) bnsCountEl.textContent = data.bnsCount || 0;
                    if (pendingReportsEl) pendingReportsEl.textContent = data.pendingReports || 0;
                    if (monthRecordsEl) monthRecordsEl.textContent = data.monthRecords || 0;
                } else {
                    console.error('Dashboard stats error:', data.message);
                }
            } catch (error) {
                console.error('Error loading dashboard:', error);
            }
        }

        // Load child list for BNS (used by both Update Records and View Records)
        async function loadChildList(pageType = 'update-records') {
            try {
                const page = pageType === 'update-records' ? 'child-list' : 'view-records';
                const response = await fetch(`backend/dashboard/load_page.php?page=${page}`, {
                    credentials: 'include'
                });
                
                const responseText = await response.text();
                console.log('Child list response:', responseText);
                
                let data = safeJsonParse(responseText);
                
                if (data.success) {
                    document.getElementById('contentArea').innerHTML = data.html;
                    
                    // Initialize based on page type
                    if (pageType === 'update-records') {
                        initializeChildList();
                    } else {
                        initializeViewRecordsPage();
                    }
                } else {
                    throw new Error(data.message || 'Failed to load child list');
                }
            } catch (error) {
                console.error('Error loading child list:', error);
                document.getElementById('contentArea').innerHTML = `
                    <div style="padding: 40px; text-align: center; color: red;">
                        Error loading child list: ${error.message}
                    </div>
                `;
            }
        }

        // Load profile page
        async function loadProfile() {
            const contentArea = document.getElementById('contentArea');
            if (!contentArea) return;
            
            contentArea.innerHTML = `
                <div class="card">
                    <div style="display: grid; grid-template-columns: auto 1fr; gap: 20px; align-items: start;">
                        <div class="profile-icon" style="width: 100px; height: 100px; font-size: 32px;">👤</div>
                        <div>
                            <h3>${currentUser.fullname}</h3>
                            <p><strong>Position:</strong> ${currentUser.role.toUpperCase()}</p>
                            <p><strong>Assigned Area:</strong> ${currentUser.area}</p>
                            <p><strong>User ID:</strong> ${currentUser.id}</p>
                        </div>
                    </div>
                    
                    <div class="dashboard-cards" style="margin-top: 30px;">
                        <div class="card">
                            <h3>Children Monitoring</h3>
                            <div class="number" id="profileChildrenCount">0</div>
                        </div>
                        ${currentUser.role === 'admin' ? `
                        <div class="card">
                            <h3>BNS Supervising</h3>
                            <div class="number" id="profileBNSCount">0</div>
                        </div>
                        ` : ''}
                        <div class="card">
                            <h3>This Month</h3>
                            <div class="number" id="profileMonthCount">0</div>
                        </div>
                    </div>
                </div>
            `;
            
            // Load profile stats
            try {
                const response = await fetch('backend/dashboard/get_stats.php', {
                    credentials: 'include'
                });
                
                const responseText = await response.text();
                let data = safeJsonParse(responseText);
                
                if (data.success) {
                    const childrenCountEl = document.getElementById('profileChildrenCount');
                    const bnsCountEl = document.getElementById('profileBNSCount');
                    const monthCountEl = document.getElementById('profileMonthCount');
                    
                    if (childrenCountEl) childrenCountEl.textContent = data.childrenCount || 0;
                    if (bnsCountEl) bnsCountEl.textContent = data.bnsCount || 0;
                    if (monthCountEl) monthCountEl.textContent = data.monthRecords || 0;
                }
            } catch (error) {
                console.error('Error loading profile stats:', error);
            }
        }

        function initializePageScripts(page) {
            console.log('Initializing page scripts for:', page);
            
            // Handle pages with query parameters
            const basePage = page.split('?')[0];
            const basePageWithoutParams = page.split('&')[0];
            
            console.log('Base page for initialization:', basePageWithoutParams);
            
            switch (basePageWithoutParams) {
                case 'view-records':
                    if (currentUser.role === 'admin') {
                        // Admin view records has sub-buttons, no direct initialization needed
                    } else {
                        initializeViewRecordsPage();
                    }
                    break;
                case 'admin-view-child-list':
                    initializeAdminViewChildList();
                    break;
                case 'admin-view-bns-list':
                    initializeAdminViewBNSList();
                    break;
                case 'view-reports':
                    initializeReportsPage();
                    break;
                case 'child-list':
                case 'update-records':
                    initializeChildList();
                    break;
                case 'admin-child-list':
                    initializeAdminChildList();
                    break;
                case 'admin-bns-list':
                    initializeAdminBNSList();
                    break;
                case 'create-report':
                    initializeCreateReport();
                    break;
                case 'add-records':
                    initializeAddRecords();
                    break;
                case 'notifications':
                    initializeNotifications();
                    break;
                case 'admin-edit-child':
                    console.log('🔄 Initializing admin edit child form...');
                    initializeEditChildForm();
                    break;
                case 'admin-edit-bns':
                    initializeEditBNSForm();
                    break;
                default:
                    console.log('No special initialization for page:', basePageWithoutParams);
            }
        }

        // Initialize Add Records page
        function initializeAddRecords() {
            console.log('Initializing add records page');
            
            // Set up event listeners for the sub-buttons
            const addChildBtn = document.querySelector('.btn.primary');
            const addBNSBtn = document.querySelector('.btn.secondary');
            
            if (addChildBtn) {
                addChildBtn.addEventListener('click', showAddChildForm);
            }
            if (addBNSBtn) {
                addBNSBtn.addEventListener('click', showAddBNSForm);
            }
            
            // Load BNS list for dropdown
            loadBNSList();
        }

        // Show Add Child Form
        function showAddChildForm() {
            const contentArea = document.getElementById('addRecordsContent');
            if (!contentArea) return;
            
            contentArea.innerHTML = `
                <div id="addChildSection">
                    <h3>Add New Child</h3>
                    <form id="addChildForm" class="record-form">
                        <div class="form-group">
                            <label for="full_name">Full Name:</label>
                            <input type="text" id="full_name" required placeholder="Enter child's full name">
                        </div>
                        
                        <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
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
                                <input type="date" id="birthdate" required max="${new Date().toISOString().split('T')[0]}">
                            </div>
                        </div>
                        
                        <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                            <div class="form-group">
                                <label for="birthplace">Birthplace:</label>
                                <input type="text" id="birthplace" placeholder="City/Municipality">
                            </div>
                            
                            <div class="form-group">
                                <label for="blood_type">Blood Type:</label>
                                <select id="blood_type">
                                    <option value="">Select</option>
                                    <option value="A+">A+</option>
                                    <option value="A-">A-</option>
                                    <option value="B+">B+</option>
                                    <option value="B-">B-</option>
                                    <option value="AB+">AB+</option>
                                    <option value="AB-">AB-</option>
                                    <option value="O+">O+</option>
                                    <option value="O-">O-</option>
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
                                <option value="">Select BNS</option>
                                <div id="bnsDropdownOptions"></div>
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
            `;
            
            // Load BNS list into dropdown
            loadBNSListForDropdown();
            
            // Add form submit handler
            document.getElementById('addChildForm').addEventListener('submit', addChild);
        }

        // Show Add BNS Form
        function showAddBNSForm() {
            const contentArea = document.getElementById('addRecordsContent');
            if (!contentArea) return;
            
            contentArea.innerHTML = `
                <div id="addBNSSection">
                    <h3>Add New BNS</h3>
                    <form id="addBNSForm" class="record-form">
                        <div class="form-group">
                            <label for="bns_fullname">Full Name:</label>
                            <input type="text" id="bns_fullname" required placeholder="Enter BNS full name">
                        </div>
                        
                        <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                            <div class="form-group">
                                <label for="bns_username">Username:</label>
                                <input type="text" id="bns_username" required placeholder="Enter username for login">
                            </div>
                            
                            <div class="form-group">
                                <label for="bns_password">Password:</label>
                                <input type="password" id="bns_password" required placeholder="Enter password for login">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="bns_assigned_area">Assigned Area:</label>
                            <input type="text" id="bns_assigned_area" required placeholder="Enter assigned barangay/area">
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="btn primary">Add BNS</button>
                            <button type="button" class="btn secondary" onclick="clearAddBNSForm()">Clear Form</button>
                        </div>
                        
                        <div id="addBNSMessage"></div>
                    </form>
                </div>
            `;
            
            // Add form submit handler
            document.getElementById('addBNSForm').addEventListener('submit', addBNS);
        }

        // Load BNS list for dropdown
        async function loadBNSListForDropdown() {
            try {
                const response = await fetch('backend/records/get_bns_list.php', {
                    credentials: 'include'
                });
                
                const data = await safeJsonParse(await response.text());
                
                if (data.success && data.bns_list) {
                    const dropdown = document.getElementById('bnsDropdownOptions');
                    if (dropdown) {
                        dropdown.innerHTML = data.bns_list.map(bns => 
                            `<option value="${bns.id}">${bns.fullname} - ${bns.assigned_area}</option>`
                        ).join('');
                    }
                }
            } catch (error) {
                console.error('Error loading BNS list:', error);
            }
        }

        // Load BNS list
        async function loadBNSList() {
            // This function can be used to load BNS list for other purposes
            try {
                const response = await fetch('backend/records/get_bns_list.php', {
                    credentials: 'include'
                });
                
                return await safeJsonParse(await response.text());
            } catch (error) {
                console.error('Error loading BNS list:', error);
                return { success: false, message: error.message };
            }
        }

        // Add Child function
        async function addChild(e) {
            e.preventDefault();
            console.log("Add Child form submitted via JS");

            
            const formData = {
                full_name: document.getElementById('full_name').value,
                sex: document.getElementById('sex').value,
                birthdate: document.getElementById('birthdate').value,
                birthplace: document.getElementById('birthplace').value,
                blood_type: document.getElementById('blood_type').value,
                parent_guardian: document.getElementById('parent_guardian').value,
                current_address: document.getElementById('current_address').value,
                assigned_bns_id: document.getElementById('assigned_bns_id').value
            };
            
            // Validation
            if (!formData.full_name || !formData.sex || !formData.birthdate || !formData.parent_guardian || !formData.current_address || !formData.assigned_bns_id) {
                showAddChildMessage('Please fill in all required fields', 'error');
                return;
            }
            
            const btn = e.target.querySelector('button[type="submit"]');
            btn.disabled = true;
            btn.textContent = 'Adding Child...';
            
            try {
                const response = await fetch('backend/records/add_child.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(formData),
                    credentials: 'include'
                });
                
                const data = await safeJsonParse(await response.text());
                
                if (data.success) {
                    alert(`Child "${formData.full_name}" has been successfully added!`);

                    clearAddChildForm();
                    setTimeout(() => {
                        loadPage('add-records');
                    }, 2000);
                } else {
                    showAddChildMessage(data.message, 'error');
                }
            } catch (error) {
                console.error('Error adding child:', error);
                showAddChildMessage('Network error. Please try again.', 'error');
            } finally {
                btn.disabled = false;
                btn.textContent = 'Add Child';
            }
        }

        // Add BNS function
        async function addBNS(e) {
            e.preventDefault();
            
            const formData = {
                fullname: document.getElementById('bns_fullname').value,
                username: document.getElementById('bns_username').value,
                password: document.getElementById('bns_password').value,
                assigned_area: document.getElementById('bns_assigned_area').value
            };
            
            // Validation
            if (!formData.fullname || !formData.username || !formData.password || !formData.assigned_area) {
                showAddBNSMessage('Please fill in all required fields', 'error');
                return;
            }
            
            if (formData.password.length < 6) {
                showAddBNSMessage('Password must be at least 6 characters long', 'error');
                return;
            }
            
            const btn = e.target.querySelector('button[type="submit"]');
            btn.disabled = true;
            btn.textContent = 'Adding BNS...';
            
            try {
                const response = await fetch('backend/records/add_bns.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(formData),
                    credentials: 'include'
                });
                
                const data = await safeJsonParse(await response.text());
                
                if (data.success) {
                    alert(`BNS "${formData.fullname}" has been successfully added!`);
                    clearAddBNSForm();
                    setTimeout(() => {
                        loadPage('add-records');
                    }, 2000);
                } else {
                    showAddBNSMessage(data.message, 'error');
                }
            } catch (error) {
                console.error('Error adding BNS:', error);
                showAddBNSMessage('Network error. Please try again.', 'error');
            } finally {
                btn.disabled = false;
                btn.textContent = 'Add BNS';
            }
        }

        // Clear Add Child Form
        function clearAddChildForm() {
            const form = document.getElementById('addChildForm');
            if (form) {
                form.reset();
                showAddChildMessage('', '');
            }
        }

        // Clear Add BNS Form
        function clearAddBNSForm() {
            const form = document.getElementById('addBNSForm');
            if (form) {
                form.reset();
                showAddBNSMessage('', '');
            }
        }

        // Show Add Child Message
        function showAddChildMessage(message, type) {
            const messageDiv = document.getElementById('addChildMessage');
            if (messageDiv) {
                const color = type === 'success' ? 'green' : 'red';
                messageDiv.innerHTML = message ? 
                    `<div style="color: ${color}; margin-top: 10px; padding: 10px; border-radius: 5px; background: ${color}20; border-left: 4px solid ${color};">${message}</div>` : '';
            }
        }

        // Show Add BNS Message
        function showAddBNSMessage(message, type) {
            const messageDiv = document.getElementById('addBNSMessage');
            if (messageDiv) {
                const color = type === 'success' ? 'green' : 'red';
                messageDiv.innerHTML = message ? 
                    `<div style="color: ${color}; margin-top: 10px; padding: 10px; border-radius: 5px; background: ${color}20; border-left: 4px solid ${color};">${message}</div>` : '';
            }
        }

        // Initialize Admin View Child List
        function initializeAdminViewChildList() {
            console.log('Initializing admin view child list with search and filter');
            
            const searchInput = document.getElementById('searchChildren');
            const statusFilter = document.getElementById('statusFilter');
            const sortFilter = document.getElementById('sortFilter');
            
            if (searchInput) {
                searchInput.addEventListener('input', filterChildList);
                console.log('Admin View - Search input listener added');
            }
            if (statusFilter) {
                statusFilter.addEventListener('change', filterChildList);
                console.log('Admin View - Status filter listener added');
            }
            if (sortFilter) {
                sortFilter.addEventListener('change', sortChildList);
                console.log('Admin View - Sort filter listener added');
            }
        }

        // Initialize Admin View BNS List
        function initializeAdminViewBNSList() {
            console.log('Initializing admin view BNS list with search');
            
            const searchInput = document.getElementById('searchBNS');
            const sortFilter = document.getElementById('sortBNSFilter');
            
            if (searchInput) {
                searchInput.addEventListener('input', filterBNSList);
                console.log('Admin View BNS - Search input listener added');
            }
            if (sortFilter) {
                sortFilter.addEventListener('change', sortBNSList);
                console.log('Admin View BNS - Sort filter listener added');
            }
        }

        // Initialize Admin Child List (Edit)
        function initializeAdminChildList() {
            console.log('Initializing admin child list with search and filter');
            
            const searchInput = document.getElementById('searchChildren');
            const statusFilter = document.getElementById('statusFilter');
            const sortFilter = document.getElementById('sortFilter');
            
            if (searchInput) {
                searchInput.addEventListener('input', filterChildList);
                console.log('Admin Edit - Search input listener added');
            }
            if (statusFilter) {
                statusFilter.addEventListener('change', filterChildList);
                console.log('Admin Edit - Status filter listener added');
            }
            if (sortFilter) {
                sortFilter.addEventListener('change', sortChildList);
                console.log('Admin Edit - Sort filter listener added');
            }
        }

        // Initialize Admin BNS List (Edit)
        function initializeAdminBNSList() {
            console.log('Initializing admin BNS list with search');
            
            const searchInput = document.getElementById('searchBNS');
            const sortFilter = document.getElementById('sortBNSFilter');
            
            if (searchInput) {
                searchInput.addEventListener('input', filterBNSList);
                console.log('Admin Edit BNS - Search input listener added');
            }
            if (sortFilter) {
                sortFilter.addEventListener('change', sortBNSList);
                console.log('Admin Edit BNS - Sort filter listener added');
            }
        }

        // Initialize child list with search and filter functionality
        function initializeChildList() {
            console.log('Initializing child list with search and filter');
            
            const searchInput = document.getElementById('searchChildren');
            const statusFilter = document.getElementById('statusFilter');
            const sortFilter = document.getElementById('sortFilter');
            
            if (searchInput) {
                searchInput.addEventListener('input', filterChildList);
                console.log('Search input listener added');
            }
            if (statusFilter) {
                statusFilter.addEventListener('change', filterChildList);
                console.log('Status filter listener added');
            }
            if (sortFilter) {
                sortFilter.addEventListener('change', sortChildList);
                console.log('Sort filter listener added');
            }
            
            // Also initialize any temporary records display
            initializeTemporaryRecords();
        }

        // Initialize temporary records functionality
        function initializeTemporaryRecords() {
            const tempRecordsBtn = document.querySelector('.btn.warning');
            if (tempRecordsBtn) {
                tempRecordsBtn.addEventListener('click', viewTemporaryRecords);
            }
        }

        // Enhanced child list filtering
        function filterChildList() {
            const searchInput = document.getElementById('searchChildren');
            const statusFilter = document.getElementById('statusFilter');
            
            if (!searchInput || !statusFilter) {
                console.log('Filter elements not found');
                return;
            }
            
            const searchTerm = searchInput.value.toLowerCase();
            const statusValue = statusFilter.value;
            const children = document.querySelectorAll('.child-list-item');
            
            console.log(`Filtering: search="${searchTerm}", status="${statusValue}", found ${children.length} children`);
            
            let visibleCount = 0;
            children.forEach(child => {
                const name = child.getAttribute('data-name').toLowerCase();
                const status = child.getAttribute('data-status');
                const matchesSearch = name.includes(searchTerm);
                const matchesStatus = !statusValue || status === statusValue;
                
                if (matchesSearch && matchesStatus) {
                    child.style.display = 'flex';
                    visibleCount++;
                } else {
                    child.style.display = 'none';
                }
            });
            
            console.log(`Filtered to ${visibleCount} visible children`);
        }

        // 🌟 Safe Filtering + Sorting for Child List
        function sortChildList() {
            const sortFilter = document.getElementById('sortFilter');
            if (!sortFilter) return;

            const sortBy = sortFilter.value;
            const container = document.querySelector('.children-list');
            if (!container) return;

            const children = Array.from(container.querySelectorAll('.child-list-item'));
            if (!children.length) return;

            // 🔄 Always reset visibility before applying filter
            children.forEach(c => c.style.display = '');

            // 🧩 Filter: No Entry This Month
            if (sortBy === 'no_entry') {
                children.forEach(c => {
                    const hasEntry = c.getAttribute('data-has-entry');
                    c.style.display = (hasEntry === '0') ? '' : 'none';
                });
                console.log('Showing children with NO entries this month');
                return;
            }

            // 🧩 Filter: Have Entry This Month
            if (sortBy === 'has_entry') {
                children.forEach(c => {
                    const hasEntry = c.getAttribute('data-has-entry');
                    c.style.display = (hasEntry === '1') ? '' : 'none';
                });
                console.log('Showing children WITH entries this month');
                return;
            }

            // 🧩 Filter: Show All
            if (sortBy === 'all') {
                children.forEach(c => c.style.display = '');
                console.log('Showing ALL children');
                return;
            }

            // 🧩 Sorting logic (A–Z, Z–A, etc.)
            const sortedChildren = [...children].sort((a, b) => {
                const nameA = a.getAttribute('data-name').toLowerCase();
                const nameB = b.getAttribute('data-name').toLowerCase();
                const statusA = a.getAttribute('data-status');
                const statusB = b.getAttribute('data-status');
                const ageA = parseInt(a.getAttribute('data-age')) || 0;
                const ageB = parseInt(b.getAttribute('data-age')) || 0;

                switch (sortBy) {
                    case 'name_asc': return nameA.localeCompare(nameB);
                    case 'name_desc': return nameB.localeCompare(nameA);
                    case 'status': return statusA.localeCompare(statusB) || nameA.localeCompare(nameB);
                    case 'age_asc': return ageA - ageB;
                    case 'age_desc': return ageB - ageA;
                    default: return 0;
                }
            });

            // 🧩 Reorder visible children without deleting any
            sortedChildren.forEach(child => container.appendChild(child));

            console.log(`Sorted ${sortedChildren.length} children by: ${sortBy}`);
        }

        // BNS list filtering (no health status filter)
        function filterBNSList() {
            const searchInput = document.getElementById('searchBNS');
            
            if (!searchInput) {
                console.log('BNS search element not found');
                return;
            }
            
            const searchTerm = searchInput.value.toLowerCase();
            const bnsItems = document.querySelectorAll('.child-list-item');
            
            console.log(`Filtering BNS: search="${searchTerm}", found ${bnsItems.length} items`);
            
            let visibleCount = 0;
            bnsItems.forEach(item => {
                const name = item.getAttribute('data-name').toLowerCase();
                const area = item.getAttribute('data-area').toLowerCase();
                const matchesSearch = name.includes(searchTerm) || area.includes(searchTerm);
                
                if (matchesSearch) {
                    item.style.display = 'flex';
                    visibleCount++;
                } else {
                    item.style.display = 'none';
                }
            });
            
            console.log(`Filtered to ${visibleCount} visible BNS`);
        }

        // BNS list sorting
        function sortBNSList() {
            const sortFilter = document.getElementById('sortBNSFilter');
            if (!sortFilter) return;
            
            const sortBy = sortFilter.value;
            const container = document.querySelector('.children-list');
            if (!container) return;
            
            const bnsItems = Array.from(container.querySelectorAll('.child-list-item'));
            
            bnsItems.sort((a, b) => {
                const nameA = a.getAttribute('data-name').toLowerCase();
                const nameB = b.getAttribute('data-name').toLowerCase();
                const areaA = a.getAttribute('data-area').toLowerCase();
                const areaB = b.getAttribute('data-area').toLowerCase();
                
                switch (sortBy) {
                    case 'name_asc':
                        return nameA.localeCompare(nameB);
                    case 'name_desc':
                        return nameB.localeCompare(nameA);
                    case 'area':
                        return areaA.localeCompare(areaB) || nameA.localeCompare(nameB);
                    default:
                        return 0;
                }
            });
            
            // Clear and reappend sorted BNS items
            container.innerHTML = '';
            bnsItems.forEach(item => container.appendChild(item));
            
            console.log(`Sorted ${bnsItems.length} BNS by: ${sortBy}`);
        }

        // Initialize View Records page
        function initializeViewRecordsPage() {
            console.log('Initializing view records page with search and filter');
            
            const searchInput = document.getElementById('searchChildren');
            const statusFilter = document.getElementById('statusFilter');
            const sortFilter = document.getElementById('sortFilter');
            
            if (searchInput) {
                searchInput.addEventListener('input', filterChildList);
                console.log('View Records - Search input listener added');
            }
            if (statusFilter) {
                statusFilter.addEventListener('change', filterChildList);
                console.log('View Records - Status filter listener added');
            }
            if (sortFilter) {
                sortFilter.addEventListener('change', sortChildList);
                console.log('View Records - Sort filter listener added');
            }
        }

        // View child records
        async function viewChildRecords(childId, childName) {
            const contentArea = document.getElementById('contentArea');
            contentArea.innerHTML = '<div style="padding: 40px; text-align: center;"><div class="loading"></div> Loading child records...</div>';
            
            try {
                console.log('🔄 Loading child records for:', childId, childName);
                
                const response = await fetch(`backend/dashboard/load_page.php?page=child-records&child_id=${childId}`, {
                    credentials: 'include'
                });
                
                console.log('📥 Response received for child records');
                const responseText = await response.text();
                console.log('📄 Raw response text length:', responseText.length);
                
                let data = safeJsonParse(responseText);
                console.log('✅ Parsed response:', data.success ? 'Success' : 'Failed');
                
                if (data.success) {
                    contentArea.innerHTML = data.html;
                    console.log('🎨 HTML content loaded, waiting for charts...');    
                    // The charts will be initialized by the script in the returned HTML
                    setTimeout(() => loadChildCharts(childId), 500);
                } else {
                    console.error('❌ Error loading child records:', data.message);
                    contentArea.innerHTML = `<div style="padding: 40px; text-align: center; color: red;">Error: ${data.message}</div>`;
                }
            } catch (error) {
                console.error('💥 Error loading child records:', error);
                contentArea.innerHTML = '<div style="padding: 40px; text-align: center; color: red;">Network error loading child records</div>';
            }
        }

        // View BNS records (admin only)
        async function viewBNSRecords(bnsId, bnsName) {
            const contentArea = document.getElementById('contentArea');
            contentArea.innerHTML = '<div style="padding: 40px; text-align: center;"><div class="loading"></div> Loading BNS details...</div>';
            
            // For now, show a simple BNS details page
            contentArea.innerHTML = `
                <div class="card">
                    <button class="btn secondary" onclick="loadPage('admin-view-bns-list')" style="margin-bottom: 20px;">
                        ← Back to BNS List
                    </button>
                    <h2>BNS Details: ${bnsName}</h2>
                    <div class="child-info-card">
                        <p><strong>BNS ID:</strong> ${bnsId}</p>
                        <p><strong>Name:</strong> ${bnsName}</p>
                        <p><strong>More details coming soon...</strong></p>
                    </div>
                </div>
            `;
        }

        // Initialize child charts
        function initializeChildCharts() {
            if (typeof childChartData === 'undefined') return;
            
            // Weight Chart
            const weightCtx = document.getElementById('weightChart')?.getContext('2d');
            if (weightCtx) {
                new Chart(weightCtx, {
                    type: 'line',
                    data: {
                        labels: childChartData.labels,
                        datasets: [{
                            label: 'Weight (kg)',
                            data: childChartData.weight,
                            borderColor: '#2e7d32',
                            backgroundColor: 'rgba(46, 125, 50, 0.1)',
                            fill: true,
                            tension: 0.4
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                beginAtZero: false,
                                title: {
                                    display: true,
                                    text: 'Weight (kg)'
                                }
                            }
                        }
                    }
                });
            }
            
            // Height Chart
            const heightCtx = document.getElementById('heightChart')?.getContext('2d');
            if (heightCtx) {
                new Chart(heightCtx, {
                    type: 'line',
                    data: {
                        labels: childChartData.labels,
                        datasets: [{
                            label: 'Height (cm)',
                            data: childChartData.height,
                            borderColor: '#1976d2',
                            backgroundColor: 'rgba(25, 118, 210, 0.1)',
                            fill: true,
                            tension: 0.4
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                beginAtZero: false,
                                title: {
                                    display: true,
                                    text: 'Height (cm)'
                                }
                            }
                        }
                    }
                });
            }
        }

        // Go back to records list
        function goBackToRecords() {
            // Determine which page to go back to based on current context
            const currentPage = document.querySelector('.menu-item.active')?.getAttribute('data-page');
            if (currentPage === 'update-records') {
                loadPage('update-records');
            } else if (currentPage === 'view-records' && currentUser.role === 'admin') {
                loadPage('admin-view-child-list');
            } else {
                loadPage('view-records');
            }
        }

        // Open update form for a child with temporary storage support
        function openUpdateForm(childId, childName) {
            const contentArea = document.getElementById('contentArea');
            contentArea.innerHTML = '<div style="padding: 40px; text-align: center;"><div class="loading"></div> Loading child data...</div>';
            
            // Load child data first
            setTimeout(() => {
                contentArea.innerHTML = generateUpdateFormHTML(childId, childName);
                initializeUpdateForm(childId);
            }, 500);
        }

        function generateUpdateFormHTML(childId, childName) {
            return `
            <div class="card">
                <button class="btn secondary" onclick="loadPage('update-records')" style="margin-bottom: 20px;">
                    ← Back to Child List
                </button>
                <h2>Update Record for ${childName}</h2>
                
                <form id="updateRecordForm" class="record-form">
                    <input type="hidden" id="childId" value="${childId}">
                    
                    <div class="form-group">
                        <label for="recordDate">Record Date:</label>
                        <input type="date" id="recordDate" value="${new Date().toISOString().split('T')[0]}" required>
                    </div>
                    
                    <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                        <div class="form-group">
                            <label for="height">Height (cm):</label>
                            <input type="number" id="height" step="0.1" min="0" required placeholder="Enter height">
                        </div>
                        
                        <div class="form-group">
                            <label for="weight">Weight (kg):</label>
                            <input type="number" id="weight" step="0.1" min="0" required placeholder="Enter weight">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="muac">MUAC (cm):</label>
                        <input type="number" id="muac" step="0.1" min="0" placeholder="Enter MUAC measurement">
                        <small id="muacWarning" style="color: #ff9800; display: none;">MUAC is required for children 6 months and older</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="notes">Notes (Optional):</label>
                        <textarea id="notes" rows="3" placeholder="Any additional observations"></textarea>
                    </div>
                    
                    <div id="healthStatusPreview" style="margin: 15px 0; padding: 10px; border-radius: 5px; display: none;">
                        <strong>Estimated Health Status:</strong> <span id="statusText"></span>
                    </div>
                    
                    <div class="form-actions">
                        <button type="button" class="btn secondary" id="clearChangesBtn">🧹 Clear Changes</button>
                        <button type="button" class="btn primary" id="saveEntryBtn">💾 Save</button>
                    </div>

                    <div id="saveMessage" style="margin-top:10px; color:red; font-weight:bold;"></div>

                    
                    <div id="formMessage"></div>
                </form>
 
            </div>`;

        }

        function initializeUpdateForm(childId) {
            const heightInput = document.getElementById('height');
            const weightInput = document.getElementById('weight');
            const muacInput = document.getElementById('muac');
            const remarksInput = document.getElementById('notes');
            const saveBtn = document.getElementById('saveEntryBtn');
            const clearBtn = document.getElementById('clearChangesBtn');
            const messageDiv = document.getElementById('saveMessage');

            // Real-time BMI / health status
            [heightInput, weightInput, muacInput].forEach(input => {
                input.addEventListener('input', calculateHealthStatus);
            });

            // ✅ Save button logic
            saveBtn.addEventListener('click', async () => {
                const weight = parseFloat(weightInput.value);
                const height = parseFloat(heightInput.value);
                const remarks = remarksInput.value.trim();

                messageDiv.textContent = "";
                messageDiv.style.color = "red";

                if (!childId || !weight || !height) {
                    messageDiv.textContent = "Please fill in all required fields.";
                    return;
                }

                try {
                    const muac = parseFloat(muacInput.value);
                    const response = await fetch("/child_monitoring/backend/child/save_pending_entry.php", {
                        method: "POST",
                        headers: { "Content-Type": "application/json" },
                        body: JSON.stringify({
                            child_id: childId,
                            height: height,
                            weight: weight,
                            muac: muac,
                            remarks: remarks
                        }),
                        credentials: "include"
                    });


                    const text = await response.text();
                    let data;
                    try {
                        data = JSON.parse(text);
                    } catch {
                        messageDiv.textContent = "Invalid server response.";
                        console.error("Server response:", text);
                        return;
                    }

                    if (data.success) {
                        messageDiv.textContent = "✅ Record saved successfully!";
                        messageDiv.style.color = "green";
                    } else {
                        messageDiv.textContent = "❌ " + (data.message || "Error saving record.");
                    }
                } catch (error) {
                    console.error("Save error:", error);
                    messageDiv.textContent = "Server error. Please ensure backend services are running.";
                }
            });

            // ✅ Clear button logic
            clearBtn.addEventListener('click', () => {
                weightInput.value = "";
                heightInput.value = "";
                muacInput.value = "";
                remarksInput.value = "";
                messageDiv.textContent = "";
                document.getElementById('healthStatusPreview').style.display = 'none';
            });

            checkMUACRequirement(childId);
        }


        async function checkMUACRequirement(childId) {
            try {
                // This would typically fetch child age from server
                // For now, we'll assume we need to check MUAC requirement
                const muacWarning = document.getElementById('muacWarning');
                const muacInput = document.getElementById('muac');
                
                // Simulate age check - in real implementation, fetch from server
                const requiresMUAC = true; // This should come from server based on child age
                
                if (requiresMUAC) {
                    muacWarning.style.display = 'block';
                    muacInput.required = true;
                }
            } catch (error) {
                console.error('Error checking MUAC requirement:', error);
            }
        }

        function showMessage(message, type) {
            const messageDiv = document.getElementById('formMessage');
            const color = type === 'success' ? 'green' : 'red';
            messageDiv.innerHTML = message ? 
                `<div style="color: ${color}; margin-top: 10px; padding: 10px; border-radius: 5px; background: ${color}20; border-left: 4px solid ${color};">${message}</div>` : '';
        }

        // Open admin update form for a child
        function openAdminUpdateForm(childId, childName) {
            // For now, use the same form as BNS
            openUpdateForm(childId, childName);
        }

        // Open admin BNS edit form
        function openAdminBNSEditForm(bnsId, bnsName) {
            const contentArea = document.getElementById('contentArea');
            contentArea.innerHTML = `
                <div class="card">
                    <button class="btn secondary" onclick="loadPage('admin-bns-list')" style="margin-bottom: 20px;">
                        ← Back to BNS List
                    </button>
                    <h2>Edit BNS: ${bnsName}</h2>
                    <form id="editBNSForm" class="record-form">
                        <input type="hidden" id="bnsId" value="${bnsId}">
                        
                        <div class="form-group">
                            <label for="bnsFullname">Full Name:</label>
                            <input type="text" id="bnsFullname" value="${bnsName}" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="bnsArea">Assigned Area:</label>
                            <input type="text" id="bnsArea" required placeholder="Enter assigned area">
                        </div>
                        
                        <div class="form-group">
                            <label for="bnsUsername">Username:</label>
                            <input type="text" id="bnsUsername" required placeholder="Enter username">
                        </div>
                        
                        <div class="form-group">
                            <label for="bnsPassword">New Password (leave blank to keep current):</label>
                            <input type="password" id="bnsPassword" placeholder="Enter new password">
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="btn primary">Update BNS</button>
                            <button type="button" class="btn danger" onclick="deleteBNS(${bnsId}, '${bnsName}')">Delete BNS</button>
                        </div>
                        
                        <div id="bnsFormMessage"></div>
                    </form>
                </div>
            `;
            
            // TODO: Load current BNS data and populate form
            document.getElementById('editBNSForm').addEventListener('submit', saveBNSEdit);
        }

        // Calculate health status in real-time
        function calculateHealthStatus() {
            const height = parseFloat(document.getElementById('height').value) || 0;
            const weight = parseFloat(document.getElementById('weight').value) || 0;
            const preview = document.getElementById('healthStatusPreview');
            const statusText = document.getElementById('statusText');
            
            if (height > 0 && weight > 0) {
                const bmi = weight / ((height/100) * (height/100));
                let status = 'Normal';
                let color = '#4caf50';
                
                if (bmi < 16) {
                    status = 'Critical';
                    color = '#f44336';
                } else if (bmi < 18.5) {
                    status = 'Underweight';
                    color = '#ff9800';
                } else if (bmi > 25) {
                    status = 'Overweight';
                    color = '#ff5722';
                }
                
                statusText.textContent = `${status} (BMI: ${bmi.toFixed(1)})`;
                preview.style.display = 'block';
                preview.style.backgroundColor = color + '20';
                preview.style.borderLeft = `4px solid ${color}`;
            } else {
                preview.style.display = 'none';
            }
        }

        // Initialize other page functions (stubs for now)
        async function initializeCreateReport() {
            const content = `
            <h2>Create Monthly Report</h2>
                <div class="card" style="padding: 20px;">     
                    <div style="display: flex; gap: 10px; margin-bottom: 20px;">
                        <select id="reportMonth" style="padding: 8px; width: 200px; border-color: #1976d2; border-radius: 4px">
                            ${[...Array(12)].map((_, i) => `<option value="${i+1}" ${i+1 === new Date().getMonth()+1 ? 'selected' : ''}>${new Date(0, i).toLocaleString('default', {month:'long'})}</option>`).join('')}
                        </select>
                        <select id="reportYear" style="padding: 8px; border-color: #1976d2; border-radius: 4px">
                            ${[2023, 2024, 2025].map(y => `<option value="${y}" ${y === new Date().getFullYear() ? 'selected' : ''}>${y}</option>`).join('')}
                        </select>
                        <button class="btn primary" onclick="generateBnsReport()">Generate Report</button>
                    </div>

                    <div id="reportTableArea"></div>
                </div>
            `;
            document.getElementById('contentArea').innerHTML = content;
        }

        async function generateBnsReport() {
            const month = document.getElementById('reportMonth').value;
            const year = document.getElementById('reportYear').value;
            const area = document.getElementById('reportTableArea');
            area.innerHTML = '<p>Loading...</p>';

            const res = await fetch(`backend/bns/get_report_entries.php?month=${month}&year=${year}`, { credentials: 'include' });
            const data = await res.json();

            if (!data.success) {
                area.innerHTML = `<p style="color:red;">${data.message}</p>`;
                return;
            }

            if (data.entries.length === 0) {
                area.innerHTML = `<p>No entries found for this period.</p>`;
                return;
            }

            let table = `
                <div class="card">
                    <h3 style="margin-bottom: 10px;">Generated Report</h3>
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th><input type="checkbox" id="selectAll"></th>
                                    <th>Child</th>
                                    <th>Height (cm)</th>
                                    <th>Weight (kg)</th>
                                    <th>MUAC</th>
                                    <th>Remarks</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${data.entries.map(e => `
                                    <tr data-id="${e.id}">
                                        <td><input type="checkbox" class="entryCheck"></td>
                                        <td>${e.child_name}</td>
                                        <td contenteditable="true" class="editable" data-field="height">${e.height}</td>
                                        <td contenteditable="true" class="editable" data-field="weight">${e.weight}</td>
                                        <td contenteditable="true" class="editable" data-field="muac">${e.muac}</td>
                                        <td contenteditable="true" class="editable" data-field="remarks">${e.remarks}</td>
                                        <td><span class="status-badge ${e.status.toLowerCase()}">${e.status}</span></td>
                                        <td>${e.date_created}</td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    </div>

                    <div style="display:flex; justify-content:space-between; align-items:center; margin-top:15px; gap:10px;">
                        <button class="btn danger" onclick="deleteSelected()">🗑️ Delete Selected</button>
                        <button class="btn primary" onclick="saveAllEntries()">💾 Save All Changes</button>
                        <button class="btn success" onclick="submitBnsReport(${month}, ${year})">📤 Submit Report</button>
                    </div>
                </div>
            `;

            area.innerHTML = table;

            // Handle select-all checkbox
            document.getElementById('selectAll').addEventListener('change', (e) => {
                document.querySelectorAll('.entryCheck').forEach(c => c.checked = e.target.checked);
            });

        }

        async function saveAllEntries() {
            const rows = document.querySelectorAll('.data-table tbody tr');
            const updates = [];

            rows.forEach(row => {
                const id = row.getAttribute('data-id');
                const data = {};
                row.querySelectorAll('.editable').forEach(cell => {
                    const field = cell.getAttribute('data-field');
                    const value = cell.textContent.trim();
                    data[field] = value;
                });
                updates.push({ id, ...data });
            });

            if (updates.length === 0) {
                alert("No rows found to save.");
                return;
            }

            if (!confirm("Save all changes to the database?")) return;

            try {
                const res = await fetch('backend/bns/save_all_entries.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ entries: updates }),
                    credentials: 'include'
                });

                const data = await res.json();
                if (data.success) {
                    alert("✅ All changes have been saved successfully!");
                } else {
                    alert("⚠️ Failed to save: " + data.message);
                }
            } catch (err) {
                console.error("Save error:", err);
                alert("⚠️ Error saving changes. Please try again.");
            }
        }


        async function saveEntryEdit(id, btn) {
            const row = btn.closest('tr');
            const height = parseFloat(row.querySelector('[data-field="height"]').innerText);
            const weight = parseFloat(row.querySelector('[data-field="weight"]').innerText);
            const muac = parseFloat(row.querySelector('[data-field="muac"]').innerText);
            const remarks = row.querySelector('[data-field="remarks"]').innerText;

            btn.disabled = true;
            const res = await fetch('backend/bns/edit_entry.php', {
                method: 'POST',
                headers: {'Content-Type':'application/json'},
                body: JSON.stringify({id, height, weight, muac, remarks}),
                credentials: 'include'
            });
            const data = await res.json();
            btn.disabled = false;
            alert(data.message);
        }

        async function deleteEntry(ids) {
            if (!confirm("Are you sure you want to delete this entry?")) return;

            const res = await fetch('backend/bns/delete_entry.php', {
                method: 'POST',
                headers: {'Content-Type':'application/json'},
                body: JSON.stringify({ids}),
                credentials: 'include'
            });
            const data = await res.json();
            alert(data.message);
            generateBnsReport();
        }

        function deleteSelected() {
            const selected = [...document.querySelectorAll('.entryCheck:checked')].map(cb => cb.closest('tr').dataset.id);
            if (selected.length === 0) {
                alert('No entries selected');
                return;
            }
            deleteEntry(selected);
        }

        async function submitBnsReport(month, year) {
            if (!confirm(`Submit report for ${new Date(year, month - 1).toLocaleString('default', { month: 'long' })} ${year}?`)) return;

            try {
                const res = await fetch('backend/bns/submit_report.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ month, year }),
                    credentials: 'include'
                });

                const text = await res.text();
                let data;
                try {
                    data = JSON.parse(text);
                } catch {
                    alert('Invalid server response:\n' + text);
                    return;
                }

                alert(data.message);
                if (data.success) loadPage('create-report');
            } catch (err) {
                console.error('Submit report error:', err);
                alert('Network or server error. Please check backend.');
            }
        }



        function initializeReportsPage() {
            console.log('Initialize reports page');
        }

        async function initializeNotifications() {
            const contentArea = document.getElementById('contentArea');
            contentArea.innerHTML = '<div class="card"><p>Loading notifications...</p></div>';

            try {
                // ✅ Corrected path
                const res = await fetch('backend/dashboard/load_page.php?page=notifications', {
                    credentials: 'include'
                });

                const data = await res.json();
                if (!data.success) {
                    contentArea.innerHTML = `<div class="card"><p>Error: ${data.message}</p></div>`;
                    return;
                }

                contentArea.innerHTML = data.html;

                // 🗑️ Handle notification deletion
                document.querySelectorAll('.delete-notif-btn').forEach(btn => {
                    btn.addEventListener('click', async (e) => {
                        e.stopPropagation(); // Prevent parent click
                        const id = btn.getAttribute('data-id');

                        if (!confirm("Are you sure you want to delete this notification?")) return;

                        try {
                            const res = await fetch('backend/dashboard/delete_notification.php', {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                                body: 'id=' + id,
                                credentials: 'include'
                            });

                            const data = await res.json();
                            alert(data.message);

                            if (data.success) {
                                // Remove the notification element from UI
                                btn.closest('.notification-item').remove();

                                // Optional: if no notifications remain, show message
                                if (document.querySelectorAll('.notification-item').length === 0) {
                                    document.querySelector('.notifications-list').innerHTML =
                                        '<div class="card"><p>No notifications found.</p></div>';
                                }
                            }
                        } catch (err) {
                            console.error('Error deleting notification:', err);
                            alert('Error deleting notification.');
                        }
                    });
                });


                // Optional: allow clicking to mark as read
                document.querySelectorAll('.notification-item').forEach(item => {
                    item.addEventListener('click', async () => {
                        const notifId = item.getAttribute('data-notification-id');
                        item.classList.remove('unread');
                        item.classList.add('read');

                        await fetch('backend/dashboard/mark_notification_read.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                            body: `id=${notifId}`,
                            credentials: 'include'
                        });
                    });
                });
            } catch (err) {
                console.error('Error loading notifications:', err);
                contentArea.innerHTML = '<div class="card"><p>Error loading notifications.</p></div>';
            }
        }

        // Report management functions (stubs)
        async function viewReportDetails(reportId) {
            const contentArea = document.getElementById('contentArea');
            contentArea.innerHTML = '<div class="card"><p>Loading report details...</p></div>';

            try {
                const res = await fetch(`backend/admin/get_report_details.php?id=${reportId}`, { credentials: 'include' });
                const data = await res.json();

                if (!data.success) {
                    contentArea.innerHTML = `<div class="card"><p style="color:red;">${data.message}</p></div>`;
                    return;
                }

                const r = data.report;
                const entries = data.entries;

                if (entries.length === 0) {
                    contentArea.innerHTML = `
                        <div class="card">
                            <button class="btn secondary" onclick="loadPage('view-reports')">← Back</button>
                            <h2>Report: ${r.bns_name} (${r.assigned_area})</h2>
                            <p>No data entries found for ${r.month_name} ${r.year}.</p>
                        </div>`;
                    return;
                }

                // Build table
                let table = `
                    <div class="card">
                        <button class="btn secondary" onclick="loadPage('view-reports')">← Back</button>
                        <h2>Report for ${r.bns_name} (${r.assigned_area})</h2>
                        <p><strong>Month:</strong> ${r.month_name} ${r.year}</p>
                        <p><strong>Status:</strong> <span class="status-badge ${r.status}">${r.status.toUpperCase()}</span></p>
                        <div class="table-responsive" style="margin-top:20px;">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Child</th>
                                        <th>Height (cm)</th>
                                        <th>Weight (kg)</th>
                                        <th>MUAC (cm)</th>
                                        <th>Remarks</th>
                                        <th>Status</th>
                                        <th>Date Recorded</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${entries.map(e => `
                                        <tr>
                                            <td>${e.child_name}</td>
                                            <td>${e.height}</td>
                                            <td>${e.weight}</td>
                                            <td>${e.muac}</td>
                                            <td>${e.remarks || ''}</td>
                                            <td>${e.status}</td>
                                            <td>${new Date(e.date_created).toLocaleDateString()}</td>
                                        </tr>
                                    `).join('')}
                                </tbody>
                            </table>
                        </div>
                    </div>
                `;

                contentArea.innerHTML = table;
            } catch (err) {
                console.error('Error loading report details:', err);
                contentArea.innerHTML = `<div class="card"><p>Error loading report details.</p></div>`;
            }
        }


        // --- Accept Report ---
        async function acceptReport(reportId) {
            if (!confirm("Accept this report and move its data to child records?")) return;

            const btnContainer = document.querySelector(`#report-buttons-${reportId}`);

            try {
                const res = await fetch('backend/admin/accept_report.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'id=' + reportId,
                    credentials: 'include'
                });

                const text = await res.text();
                let data;
                try {
                    data = JSON.parse(text);
                } catch {
                    alert("⚠️ Invalid server response:\n" + text);
                    return;
                }

                alert(data.message);

                if (data.success) {
                    if (btnContainer) btnContainer.remove();
                    const statusCell = document.querySelector(`#report-status-${reportId}`);
                    if (statusCell) statusCell.textContent = "Accepted ✅";
                }
            } catch (err) {
                console.error('Error accepting report:', err);
                alert('Server or network error.');
            }
        }


        // --- Decline Report ---
        async function declineReport(reportId) {
            if (!confirm("Mark this report as declined?")) return;

            const btnContainer = document.querySelector(`#report-buttons-${reportId}`);

            try {
                const res = await fetch('backend/admin/update_report_status.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ report_id: reportId, status: 'declined' }),
                    credentials: 'include'
                });

                const text = await res.text();
                let data;
                try {
                    data = JSON.parse(text);
                } catch {
                    alert("⚠️ Invalid server response:\n" + text);
                    return;
                }

                alert(data.message);

                if (data.success) {
                    if (btnContainer) btnContainer.remove();
                    const statusCell = document.querySelector(`#report-status-${reportId}`);
                    if (statusCell) statusCell.textContent = "Declined ❌";
                }
            } catch (err) {
                console.error('Error declining report:', err);
                alert('Server or network error.');
            }
        }


        // --- Delete Report ---
        async function deleteReport(reportId) {
            if (!confirm("⚠️ Are you sure you want to permanently delete this report?")) return;

            const btnContainer = document.querySelector(`#report-buttons-${reportId}`);

            try {
                const res = await fetch('backend/admin/update_report_status.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ report_id: reportId, status: 'delete' }),
                    credentials: 'include'
                });

                const text = await res.text();
                let data;
                try {
                    data = JSON.parse(text);
                } catch {
                    alert("⚠️ Invalid server response:\n" + text);
                    return;
                }

                alert(data.message);

                if (data.success) {
                    if (btnContainer) btnContainer.remove();
                    const statusCell = document.querySelector(`#report-status-${reportId}`);
                    if (statusCell) statusCell.textContent = "Deleted 🗑️";
                }
            } catch (err) {
                console.error('Error deleting report:', err);
                alert('Server or network error.');
            }
        }

        // BNS management functions (stubs)
        function saveBNSEdit(e) {
            e.preventDefault();
            alert('BNS edit functionality coming soon');
        }

        async function deleteBNS(bnsId, bnsName) {
            // Ask for confirmation before deleting
            const confirmDelete = confirm(`⚠️ Are you sure you want to delete BNS "${bnsName}"?\n\nThis action cannot be undone.`);
            if (!confirmDelete) return;

            const contentArea = document.getElementById('contentArea');
            contentArea.innerHTML = `
                <div class="card" style="padding: 30px; text-align: center;">
                    <p style="color: #555;">⏳ Deleting <strong>${bnsName}</strong>...</p>
                </div>
            `;

            try {
                const response = await fetch('backend/records/delete_bns.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ bns_id: bnsId }),
                    credentials: 'include'
                });

                const text = await response.text();
                console.log("Delete BNS raw response:", text);

                let result;
                try {
                    result = JSON.parse(text);
                } catch (e) {
                    // ✨ Show clean, exact format
                    contentArea.innerHTML = `
                        <div class="card" style="padding: 30px; color: red; white-space: pre-line;">
                            ⚠️ Invalid response from server:\n\n${text}
                        </div>
                    `;
                    return;
                }


                if (result.success) {
                    contentArea.innerHTML = `
                        <div class="card" style="padding: 30px; text-align: center;">
                            <p style="color: green;"> ${result.message}</p>
                        </div>
                    `;
                    setTimeout(() => loadPage('admin-bns-list'), 1500);
                } else {
                    contentArea.innerHTML = `
                        <div class="card" style="padding: 30px; text-align: center;">
                            <p style="color: red;"> ${result.message}</p>
                        </div>
                    `;
                }
            } catch (error) {
                console.error('Delete BNS Error:', error);
                contentArea.innerHTML = `
                    <div class="card" style="padding: 30px; text-align: center;">
                        <p style="color: red;">❌ Network error while deleting BNS.</p>
                    </div>
                `;
            }
        }


// ========================================================
// ✅ CHILD RECORD CHART LOADING (Dynamic per child)
// ========================================================
async function loadChildCharts(childId) {
    try {
        const response = await fetch(`backend/dashboard/get_stats.php?child_id=${childId}`, {
            credentials: 'include'
        });
        const data = await response.json();

        console.log('✅ Chart data fetched for child', childId, data);

        if (!data.labels?.length) {
            console.warn('⚠️ No records available for child:', childId);
            return;
        }

        const chartConfigs = [
            { id: 'heightChart', label: 'Height (cm)', data: data.height, color: '#1976d2' },
            { id: 'weightChart', label: 'Weight (kg)', data: data.weight, color: '#2e7d32' },
            { id: 'muacChart', label: 'MUAC (cm)', data: data.muac, color: '#ff9800' }
        ];

        chartConfigs.forEach(cfg => {
            const ctx = document.getElementById(cfg.id);
            if (!ctx) return;

            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: data.labels,
                    datasets: [{
                        label: cfg.label,
                        data: cfg.data,
                        borderColor: cfg.color,
                        backgroundColor: cfg.color + '33',
                        fill: true,
                        tension: 0.3
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: true } },
                    scales: {
                        y: {
                            beginAtZero: false,
                            title: { display: true, text: cfg.label }
                        }
                    }
                }
            });
        });
    } catch (error) {
        console.error('❌ Error loading charts:', error);
    }
}



        // Load dashboard on startup
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Dashboard loaded for user:', currentUser);
            loadDashboard();
            
            // Uncomment to test edit functionality automatically
            // testEditFunctionality();
        });

        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(e) {
            if (window.innerWidth <= 768) {
                const sidebar = document.querySelector('.sidebar');
                const menuBtn = document.querySelector('.mobile-menu-btn');
                if (sidebar && menuBtn && sidebar.classList.contains('active') && 
                    !sidebar.contains(e.target) && 
                    !menuBtn.contains(e.target)) {
                    sidebar.classList.remove('active');
                }
            }
        });

    // ✅ Attach the listener once the page loads dynamically
    document.addEventListener("change", (e) => {
    if (e.target && e.target.id === "sortFilter") {
        console.log("Sort filter changed:", e.target.value);
        sortChildList();
    }
    });

    document.addEventListener("DOMContentLoaded", () => {
    const form = document.getElementById("addChildForm");
    if (form) form.addEventListener("submit", addChild);
    });

// Show Record History for a Child (Admin)
async function showChildRecordHistory(childId) {
    try {
        const container = document.getElementById('recordHistorySection');
        if (!container) return;

        // Toggle visibility
        container.style.display = 'block';
        container.innerHTML = '<div class="card"><p>Loading record history...</p></div>';

        const response = await fetch(`backend/records/get_child_records.php?child_id=${childId}`);
        const data = await safeJsonParse(await response.text());

        if (!data.success) {
            container.innerHTML = `<div class="card"><p>${data.message || 'Error loading records.'}</p></div>`;
            return;
        }

        // Build the record table
        const rows = data.records.map(r => `
            <tr>
                <td>${r.record_date}</td>
                <td>${r.height}</td>
                <td>${r.weight}</td>
                <td>${r.muac || 'N/A'}</td>
                <td>${r.health_status}</td>
                <td>${r.notes || ''}</td>
                <td>
                    <button class="btn primary btn-sm" onclick="editRecord(${r.id})">Edit</button>
                    <button class="btn danger btn-sm" onclick="deleteRecord(${r.id}, ${childId})">Delete</button>
                </td>
            </tr>
        `).join('');

        container.innerHTML = `
            <div class="card">
                <h3>Record History for ${data.child_name}</h3>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Date</th><th>Height</th><th>Weight</th>
                                <th>MUAC</th><th>Status</th><th>Notes</th><th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>${rows}</tbody>
                    </table>
                </div>
            </div>
        `;
    } catch (err) {
        console.error('Error fetching record history:', err);
    }
}

async function editRecord(id) {
    const newHeight = prompt("Enter new height (cm):");
    const newWeight = prompt("Enter new weight (kg):");
    const newStatus = prompt("Enter new health status:");

    if (!newHeight || !newWeight || !newStatus) return alert("Incomplete data.");

    const payload = {
        id,
        height: newHeight,
        weight: newWeight,
        health_status: newStatus,
        record_date: new Date().toISOString().slice(0,10),
        muac: null,
        notes: ''
    };

    const response = await fetch('backend/records/edit_child_record.php', {
        method: 'POST',
        headers: {'Content-Type':'application/json'},
        body: JSON.stringify(payload)
    });

    const data = await response.json();
    alert(data.message || 'Update complete');
}

async function deleteRecord(id, childId) {
    if (!confirm('Are you sure you want to delete this record?')) return;
    const res = await fetch(`backend/records/delete_child_record.php?id=${id}`);
    const data = await res.json();
    alert(data.message || 'Record deleted');
    showChildRecordHistory(childId);
}

// Switch to the child edit form
function showEditChildForm() {
    const formSection = document.getElementById('editChildContent');
    const recordSection = document.getElementById('recordHistorySection');
    if (!formSection || !recordSection) return;
    formSection.style.display = 'block';
    recordSection.style.display = 'none';
    console.log('Switched to Edit Child form');
}

// Switch to the record history view
async function showEditRecordHistory(childId) {
    const formSection = document.getElementById('editChildContent');
    const recordSection = document.getElementById('recordHistorySection');
    if (!formSection || !recordSection) return;

    formSection.style.display = 'none';
    recordSection.style.display = 'block';
    recordSection.innerHTML = '<div class="card"><p>Loading record history...</p></div>';

    try {
        const res = await fetch(`backend/records/get_child_records.php?child_id=${childId}`);
        const data = await safeJsonParse(await res.text());
        if (!data.success) {
            recordSection.innerHTML = `<div class="card"><p>${data.message}</p></div>`;
            return;
        }

        const rows = data.records.map(r => `
            <tr>
                <td>${r.record_date}</td>
                <td>${r.height}</td>
                <td>${r.weight}</td>
                <td>${r.muac || 'N/A'}</td>
                <td>${r.health_status}</td>
                <td>${r.notes || ''}</td>
                <td>
                <button class="btn primary" onclick="editRecord(${r.id})">Edit</button>
                <button class="btn danger" onclick="deleteRecord(${r.id}, ${childId})">Delete</button>
                </td>
            </tr>
        `).join('');

        recordSection.innerHTML = `
            <div class="card">
                <h3>Record History for ${data.child_name}</h3>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Date</th><th>Height</th><th>Weight</th>
                                <th>MUAC</th><th>Status</th><th>Notes</th><th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>${rows}</tbody>
                    </table>
                </div>
            </div>
        `;
    } catch (err) {
        recordSection.innerHTML = `<div class="card"><p>Error loading records</p></div>`;
        console.error(err);
    }
}

async function editRecord(recordId) {
    try {
        const res = await fetch(`backend/records/get_child_record.php?id=${recordId}`);
        const data = await res.json();

        if (!data.success) {
            alert(data.message || 'Failed to load record.');
            return;
        }

        const r = data.record;

        // Remove any existing modal
        const oldModal = document.querySelector('.modal-overlay');
        if (oldModal) oldModal.remove();

        // ✅ Create the modal overlay properly
        const modal = document.createElement('div');
        modal.className = 'modal-overlay';
        modal.innerHTML = `
            <div class="modal-card">
                <h3>Edit Record for <strong>${r.record_date}</strong></h3>
                <div class="form-group">
                    <label>Height (cm):</label>
                    <input type="number" id="editHeight" value="${r.height || ''}" step="0.1">
                </div>
                <div class="form-group">
                    <label>Weight (kg):</label>
                    <input type="number" id="editWeight" value="${r.weight || ''}" step="0.1">
                </div>
                <div class="form-group">
                    <label>MUAC (cm):</label>
                    <input type="number" id="editMuac" value="${r.muac || ''}" step="0.1">
                </div>
                <div class="form-group">
                    <label>Remarks:</label>
                    <textarea id="editRemarks" rows="2">${r.remarks || ''}</textarea>
                </div>
                <div class="modal-actions">
                    <button class="btn primary" id="saveRecordBtn">Save</button>
                    <button class="btn secondary" onclick="closeModal()">Cancel</button>
                </div>
            </div>
        `;

        // ✅ Append modal directly to <body>
        document.body.appendChild(modal);

        document.getElementById('saveRecordBtn').onclick = async () => {
            const updated = {
                id: recordId,
                height: document.getElementById('editHeight').value,
                weight: document.getElementById('editWeight').value,
                muac: document.getElementById('editMuac').value,
                remarks: document.getElementById('editRemarks').value
            };

            const resp = await fetch('backend/records/update_child_record.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(updated)
            });

            const result = await resp.json();
            if (result.success) {
                alert(`✅ Record updated! Status recomputed: ${result.new_status}`);
                closeModal();
                showEditRecordHistory(r.child_id);
            } else {
                alert(result.message || 'Update failed.');
            }
        };
    } catch (err) {
        console.error('Edit Record Error:', err);
        alert('An unexpected error occurred.');
    }
}

function closeModal() {
    const modal = document.querySelector('.modal-overlay');
    if (modal) modal.remove();
}


function closeModal() {
    const modal = document.querySelector('.modal-overlay');
    if (modal) modal.remove();
}


    </script>
</body>
</html>