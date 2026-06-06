<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>TrustFund — Admin Panel</title>
  <link rel="stylesheet" href="../../assets/css/global.css" />
  <link rel="stylesheet" href="../../assets/css/admin-panel.css?v=<?= filemtime('../../assets/css/admin-panel.css'); ?>" />
  <link rel="stylesheet" href="../../assets/css/group-details.css" />
</head>
<body>
  <div class="app-layout">

    <?php include "components/sidebar-view.php"; ?>

    <div class="main-content">

      <header class="topbar">
        <div class="topbar-left">
          <span class="topbar-title">Admin Panel</span>
        </div>
        <div class="topbar-right">
          <button class="notif-btn" id="notif-btn">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9M13.73 21a2 2 0 0 1-3.46 0"/>
            </svg>
          </button>
        </div>
      </header>

      <div class="page-content">

        <?php if (isset($_GET['success'])): ?>
          <div style="background-color: #f0fdf4; border: 1px solid #bbf7d0; color: #166534; padding: 12px 16px; border-radius: 12px; margin-bottom: 24px; font-size: 14px; font-weight: 500;">
            <?= htmlspecialchars($_GET['success']); ?>
          </div>
        <?php endif; ?>

        <div class="admin-banner">
          <div class="admin-banner-icon">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
              <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10zM9 12l2 2 4-4"/>
            </svg>
          </div>
          <div>
            <div class="admin-banner-title">Admin Panel</div>
            <div class="admin-banner-sub">Manage users, verify accounts, oversee all groups</div>
          </div>
        </div>

        <div class="stat-cards">
          <div class="stat-card" onclick="openSection('verifications-section')" style="cursor: pointer;">
            <div class="stat-card-label">Pending Verification</div>
            <div class="stat-card-value amber"><?= $pending_verifications; ?></div>
          </div>
          <div class="stat-card" onclick="openSection('users-section')" style="cursor: pointer;">
            <div class="stat-card-label">Total Users</div>
            <div class="stat-card-value"><?= $total_users; ?></div>
          </div>
          <div class="stat-card" onclick="openSection('groups-section')" style="cursor: pointer;">
            <div class="stat-card-label">Total Groups</div>
            <div class="stat-card-value"><?= $total_groups; ?></div>
          </div>
          <div class="stat-card" onclick="openSection('groups-section')" style="cursor: pointer;">
            <div class="stat-card-label">Active Groups</div>
            <div class="stat-card-value green"><?= $active_groups; ?></div>
          </div>
        </div>

        <div class="detail-tabs" style="margin-bottom: 24px;">
          <button class="detail-tab active" id="btn-verifications-section" onclick="openSection('verifications-section')">Pending Approvals</button>
          <button class="detail-tab" id="btn-users-section" onclick="openSection('users-section')">User Management</button>
          <button class="detail-tab" id="btn-groups-section" onclick="openSection('groups-section')">Group Monitoring</button>
          <button class="detail-tab" id="btn-controls-section" onclick="openSection('controls-section')">Admin Controls</button>
        </div>

<div class="admin-panel-section" id="verifications-section">
          <div class="section-title">Pending Identity Approvals</div>
          
          <div class="tf-action-bar">
            <input type="text" id="verify-search" class="tf-search-input" placeholder="Search by name, @username, or email..." onkeyup="filterVerifications()">
            <select id="verify-sort" class="tf-filter-select" onchange="sortVerifications()">
              <option value="newest">Sort by: Newest First</option>
              <option value="oldest">Sort by: Oldest First</option>
              <option value="name_az">First Name (A-Z)</option>
              <option value="name_za">First Name (Z-A)</option>
            </select>
          </div>

          <div class="table-wrap">
            <table id="verifications-table">
              <thead>
                <tr>
                  <th>User Details</th>
                  <th>Email</th>
                  <th>Account Creation Date</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody id="verifications-tbody">
                <?php if (mysqli_num_rows($verifications_res) > 0): ?>
                  <?php while ($v_row = mysqli_fetch_assoc($verifications_res)): ?>
                    <tr class="verify-row" 
                        data-date="<?= strtotime($v_row['created_at']); ?>" 
                        data-firstname="<?= htmlspecialchars(strtolower($v_row['first_name'])); ?>">
                      
                      <td class="verify-data-name">
                        <div class="user-cell">
                          <div class="user-cell-avatar"><?= strtoupper(substr($v_row['first_name'], 0, 1)); ?></div>
                          <div>
                            <div class="user-cell-name"><?= htmlspecialchars($v_row['first_name'] . " " . $v_row['last_name']); ?></div>
                            <div class="user-cell-sub">@<?= htmlspecialchars($v_row['username']); ?></div>
                          </div>
                        </div>
                      </td>
                      <td class="verify-data-email"><?= htmlspecialchars($v_row['email']); ?></td>
                      <td style="color: var(--color-text-muted);">
                        <?= date('M d, Y · h:i A', strtotime($v_row['created_at'])); ?>
                      </td>
                      <td>
                        <button type="button" class="btn-row-view"
                            data-vid="<?= htmlspecialchars($v_row['verification_id'] ?? ''); ?>"
                            data-uid="<?= htmlspecialchars($v_row['user_id'] ?? ''); ?>"
                            data-username="<?= htmlspecialchars($v_row['username'] ?? ''); ?>"
                            data-firstname="<?= htmlspecialchars($v_row['first_name'] ?? ''); ?>"
                            data-lastname="<?= htmlspecialchars($v_row['last_name'] ?? ''); ?>"
                            data-email="<?= htmlspecialchars($v_row['email'] ?? ''); ?>"
                            data-phone="<?= htmlspecialchars($v_row['phone'] ?? ''); ?>"
                            data-occupation="<?= htmlspecialchars($v_row['occupation'] ?? ''); ?>"
                            data-address="<?= htmlspecialchars($v_row['address'] ?? ''); ?>"
                            data-doc="<?= htmlspecialchars($v_row['document'] ?? ''); ?>"
                            onclick="openReviewModal(this)">
                            Review Account
                        </button>
                      </td>
                    </tr>
                  <?php endwhile; ?>
                <?php else: ?>
                  <tr><td colspan="4" class="table-empty"><p>No accounts pending evaluation.</p></td></tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>

        <div class="admin-panel-section" id="users-section" style="display:none;">
          <div class="section-title">All Registered Members</div>
          
          <div class="tf-action-bar">
            <input type="text" id="user-search" class="tf-search-input" placeholder="Search by name or @username..." onkeyup="filterUsers()">
            <select id="user-filter" class="tf-filter-select" onchange="filterUsers()">
              <option value="all">All Statuses</option>
              <option value="activated">Activated</option> <option value="pending">Pending</option>
              <option value="suspended">Suspended</option>
            </select>
          </div>

          <div class="table-wrap">
            <table id="users-table">
              <thead>
                <tr>
                  <th>User</th><th>Email</th><th>Status</th><th>Joined</th><th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php if (mysqli_num_rows($users_res) > 0): ?>
                  <?php while ($row = mysqli_fetch_assoc($users_res)): 
                      
                      $current_status = strtolower($row['status'] ?? 'pending');
                      
                      // SAFETY CHECK: If the DB has a blank space, treat it as pending
                      if ($current_status == '') {
                          $current_status = 'pending'; 
                      }

                      // Badge Colors
                      $badge_class = 'badge-pending'; // Default gray
                      if ($current_status == 'activated') $badge_class = 'badge-active'; // Green
                      if ($current_status == 'suspended' || $current_status == 'denied') $badge_class = 'badge-suspended'; // Red
                  ?>
                    <tr class="user-row">
                      <td class="user-data-name">
                        <div class="user-cell">
                          <div class="user-cell-avatar" style="background-color: #ffedd5; color: #c2410c;">
                            <?= strtoupper(substr($row['first_name'], 0, 1)); ?>
                          </div>
                          <div>
                            <div class="user-cell-name"><?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></div>
                            <div class="user-cell-sub">@<?= htmlspecialchars($row['username']); ?></div>
                          </div>
                        </div>
                      </td>
                      <td><?= htmlspecialchars($row['email']); ?></td>
                      <td>
                        <span class="badge <?= $badge_class; ?> user-data-status"><?= ucfirst($current_status); ?></span>
                      </td>
                      <td style="color: var(--color-text-muted);"><?= date('M d, Y · h:i A', strtotime($row['created_at'])); ?></td>
                      <td>
                        <?php if ($current_status != 'suspended'): ?>
                          <a href="admin.php?action=suspend&id=<?= $row['user_id']; ?>" style="font-size: 12px; background-color:#fee2e2; border:1px solid #fca5a5; color:#991b1b; padding:6px 12px; border-radius:8px; text-decoration:none; font-weight:600;">Suspend</a>
                        <?php else: ?>
                          <a href="admin.php?action=activate&id=<?= $row['user_id']; ?>" style="font-size: 12px; background-color:#d1fae5; border:1px solid #a7f3d0; color:#065f46; padding:6px 12px; border-radius:8px; text-decoration:none; font-weight:600;">Activate</a>
                        <?php endif; ?>
                      </td>
                    </tr>
                  <?php endwhile; ?>
                <?php else: ?>
                  <tr><td colspan="5" class="table-empty"><p>No active directory records found.</p></td></tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>

<div class="admin-panel-section" id="groups-section" style="display:none;">
          <div class="section-title">All Configured Savings Pools</div>
          
          <div class="tf-action-bar">
            <input type="text" id="group-search" class="tf-search-input" placeholder="Search by group name or owner..." onkeyup="filterGroups()">
            <select id="group-filter" class="tf-filter-select" onchange="filterGroups()">
              <option value="all">All Statuses</option>
              <option value="active">Active</option>
              <option value="finished">Finished</option>
              <option value="closed">Closed</option>
            </select>
          </div>

          <div class="table-wrap">
            <table id="groups-table">
              <thead>
                <tr>
                  <th>Group Name</th><th>Owner</th><th>Frequency Details</th><th>Status</th><th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php if (mysqli_num_rows($groups_res) > 0): ?>
                  <?php while ($group = mysqli_fetch_assoc($groups_res)): 
                      $g_status = strtolower($group['status'] ?? 'active');
                      $g_badge = 'badge-active';
                      if ($g_status == 'finished') $g_badge = 'badge-finished';
                      if ($g_status == 'closed') $g_badge = 'badge-suspended'; // Red
                  ?>
                    <tr class="group-row">
                      <td class="group-data-name">
                        <div style="font-weight: 600; color: #111827;"><?= htmlspecialchars($group['group_name']); ?></div>
                        </td>
                      <td class="group-data-owner">
                        <div style="font-weight: 500; color: #111827;"><?= htmlspecialchars(($group['owner_first'] ?? 'Unknown') . ' ' . ($group['owner_last'] ?? '')); ?></div>
                        <div style="font-size: 12px; color: #6B6560;">@<?= htmlspecialchars($group['owner_user'] ?? 'N/A'); ?></div>
                      </td>
                      <td style="text-transform: capitalize;"><?= htmlspecialchars($group['frequency']); ?> · ₱<?= number_format($group['contribution_amount'], 0); ?></td>
                      <td><span class="badge <?= $g_badge; ?> group-data-status"><?= ucfirst($g_status); ?></span></td>
                      <td>
                        <button type="button" class="btn-row-view"
                            data-gid="<?= htmlspecialchars($group['group_id'] ?? ''); ?>"
                            data-gname="<?= htmlspecialchars($group['group_name'] ?? ''); ?>"
                            data-owner="<?= htmlspecialchars(($group['owner_first'] ?? 'Unknown') . ' ' . ($group['owner_last'] ?? '')); ?>"
                            data-freq="<?= htmlspecialchars($group['frequency'] ?? ''); ?>"
                            data-amount="<?= htmlspecialchars($group['contribution_amount'] ?? '0'); ?>"
                            data-members="<?= htmlspecialchars($group['member_count'] ?? '0'); ?>"
                            data-status="<?= htmlspecialchars($g_status); ?>"
                            data-memberlist="<?= htmlspecialchars($group['member_list'] ?? 'No members joined yet'); ?>"
                            
                            data-created="<?= date('M d, Y · h:i A', strtotime($group['created_at'])); ?>"
                            
                            onclick="openGroupModal(this)">
                            View Details
                        </button>
                      </td>
                    </tr>
                  <?php endwhile; ?>
                <?php else: ?>
                  <tr><td colspan="5" class="table-empty"><p>No savings circles exist.</p></td></tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>

        <!-- NEW ADMIN CONTROLS SECTION -->
        <div class="admin-panel-section" id="controls-section" style="display:none;">
          <div class="section-title">Cycle, Date &amp; Group Controls</div>
          <p style="margin-bottom:16px; color:#6b5a4a;">Use these tools to manage cycles, dates, and force simulation-like actions for all groups (no dummy accounts needed).</p>

          <div class="table-wrap" style="margin-bottom:24px;">
            <table>
              <thead>
                <tr>
                  <th>Group</th>
                  <th>Current Cycle</th>
                  <th>Start Date</th>
                  <th>Members</th>
                  <th>Status</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php while ($cg = mysqli_fetch_assoc($controls_groups_res)): ?>
                  <tr>
                    <td><strong><?= htmlspecialchars($cg['group_name']) ?></strong></td>
                    <td>#<?= $cg['current_cycle'] ?></td>
                    <td><?= $cg['current_start'] ? htmlspecialchars($cg['current_start']) : 'N/A' ?></td>
                    <td><?= $cg['members'] ?></td>
                    <td><span class="badge badge-active"><?= ucfirst($cg['status']) ?></span></td>
                    <td>
                      <form method="POST" action="admin.php" style="display:inline;">
                        <input type="hidden" name="group_id" value="<?= $cg['group_id'] ?>">
                        <button type="submit" name="admin_advance_cycle" class="btn-row-view" style="font-size:11px; padding:3px 8px;">Advance Cycle</button>
                      </form>

                      <form method="POST" action="admin.php" style="display:inline; margin-left:4px;">
                        <input type="hidden" name="group_id" value="<?= $cg['group_id'] ?>">
                        <input type="hidden" name="cycle_number" value="<?= $cg['current_cycle'] ?>">
                        <button type="submit" name="admin_force_paid" class="btn-row-view" style="font-size:11px; padding:3px 8px; background:#1e40af; color:white;">Force Paid</button>
                      </form>
                    </td>
                  </tr>
                <?php endwhile; ?>
              </tbody>
            </table>
          </div>

          <div style="display:flex; gap:24px; flex-wrap:wrap;">
            <!-- Set Cycle Date -->
            <div style="flex:1; min-width:280px;">
              <div class="section-title" style="font-size:15px;">Set Cycle Start Date</div>
              <form method="POST" action="admin.php">
                <div class="form-group">
                  <label class="input-label">Cycle ID</label>
                  <input type="number" name="cycle_id" class="input-field" required>
                </div>
                <div class="form-group">
                  <label class="input-label">New Start Date</label>
                  <input type="date" name="start_date" class="input-field" value="<?= date('Y-m-d') ?>" required>
                </div>
                <button type="submit" name="admin_set_cycle_date" class="btn-primary" style="width:100%;">Update Date</button>
              </form>
            </div>

            <!-- Release Payout -->
            <div style="flex:1; min-width:280px;">
              <div class="section-title" style="font-size:15px;">Release Payout for Cycle</div>
              <form method="POST" action="admin.php">
                <div class="form-group">
                  <label class="input-label">Cycle ID</label>
                  <input type="number" name="cycle_id" class="input-field" required>
                </div>
                <button type="submit" name="admin_release_payout" class="btn-primary" style="width:100%; background:#166534;">Release Payout</button>
              </form>
            </div>
          </div>
        </div>

      </div>
    </div>
  </div>

  <div id="details-modal" class="tf-modal-overlay">
    <div class="tf-modal-box">
      
      <div class="tf-modal-header">
        <h3 class="tf-modal-title">Account Review</h3>
        <button class="tf-modal-close" onclick="closeModal()">&times;</button>
      </div>
      
      <div class="tf-hero">
        <div id="modal-avatar" class="tf-avatar">A</div>
        <div>
          <div id="modal-name" class="tf-hero-name">Full Name</div>
          <div id="modal-user" class="tf-hero-user">@username</div>
        </div>
      </div>
      
      <div class="tf-grid">
        <div class="tf-data-card">
          <div class="tf-label">User ID</div>
          <div id="modal-display-uid" class="tf-value">N/A</div>
        </div>
        <div class="tf-data-card">
          <div class="tf-label">Phone Number</div>
          <div id="modal-phone" class="tf-value">N/A</div>
        </div>
        <div class="tf-data-card">
          <div class="tf-label">First Name</div>
          <div id="modal-firstname" class="tf-value">N/A</div>
        </div>
        <div class="tf-data-card">
          <div class="tf-label">Last Name</div>
          <div id="modal-lastname" class="tf-value">N/A</div>
        </div>
        <div class="tf-data-card">
          <div class="tf-label">Email Address</div>
          <div id="modal-email" class="tf-value">N/A</div>
        </div>
        <div class="tf-data-card">
          <div class="tf-label">Occupation</div>
          <div id="modal-occupation" class="tf-value">N/A</div>
        </div>
        <div class="tf-data-card tf-grid-full">
          <div class="tf-label">Complete Address</div>
          <div id="modal-address" class="tf-value">N/A</div>
        </div>
        <div class="tf-data-card tf-grid-full">
          <div class="tf-label">Identity Verification</div>
          <a id="modal-doc" href="#" target="_blank" class="tf-doc-btn">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
            View Submitted Document
          </a>
        </div>
      </div>
      
      <form method="POST" action="admin.php" class="tf-actions">
        <input type="hidden" name="verification_id" id="modal-vid">
        <input type="hidden" name="target_user_id" id="modal-uid">
        <input type="hidden" name="action_verify" value="1">
        
        <button type="submit" name="status" value="denied" class="tf-btn-reject">Reject User</button>
        <button type="submit" name="status" value="approved" class="tf-btn-approve">Approve Account</button>
      </form>

    </div>
  </div>
  
  <div id="group-modal" class="tf-modal-overlay">
    <div class="tf-modal-box" style="max-width: 500px;">
      
      <div class="tf-modal-header">
        <h3 class="tf-modal-title">Group Details</h3>
        <button class="tf-modal-close" onclick="closeGroupModal()">&times;</button>
      </div>
      
      <div class="tf-grid" style="margin-bottom: 24px;">
        <div class="tf-data-card tf-grid-full">
          <div class="tf-label">Group Name</div>
          <div id="gmodal-name" class="tf-value" style="font-size: 18px; color: var(--color-primary);">N/A</div>
        </div>
        <div class="tf-data-card">
          <div class="tf-label">Owner / Admin</div>
          <div id="gmodal-owner" class="tf-value">N/A</div>
        </div>
        <div class="tf-data-card">
          <div class="tf-label">Total Members</div>
          <div id="gmodal-members" class="tf-value">0</div>
        </div>
        <div class="tf-data-card">
          <div class="tf-label">Contribution Amount</div>
          <div id="gmodal-amount" class="tf-value">₱0</div>
        </div>
        <div class="tf-data-card">
          <div class="tf-label">Frequency</div>
          <div id="gmodal-freq" class="tf-value" style="text-transform: capitalize;">N/A</div>
        </div>
        <div class="tf-data-card">
          <div class="tf-label">Date Created</div>
          <div id="gmodal-created" class="tf-value" style="font-size: 14px; color: var(--color-text-secondary, #666);">N/A</div>
        </div>
        <div class="tf-data-card tf-grid-full">
        <div class="tf-label">Joined Members</div>
          <div id="gmodal-memberlist" class="tf-value" style="font-weight: 500; color: var(--color-text-secondary, #666); line-height: 1.6;">
            None
          </div>
        </div>
        </div>
      
      <form method="POST" action="admin.php" class="tf-actions">
        <input type="hidden" name="target_group_id" id="gmodal-gid">
        
        <button type="submit" name="action_close_group" id="btn-force-close" class="tf-btn-reject" style="flex: 1;">Force Close Group</button>
      </form>

    </div>
  </div>

<script>
function openSection(targetId) {
    document.querySelectorAll('.admin-panel-section').forEach(panel => { panel.style.display = 'none'; });
    document.querySelectorAll('.detail-tab').forEach(tab => { tab.classList.remove('active'); });
    document.getElementById(targetId).style.display = 'block';
    document.getElementById('btn-' + targetId).classList.add('active');
}

function openReviewModal(btn) {
    const data = btn.dataset;

    // Header logic
    document.getElementById('modal-name').textContent = (data.firstname || '') + ' ' + (data.lastname || '');
    document.getElementById('modal-user').textContent = data.username ? '@' + data.username : '';
    const avatarEl = document.getElementById('modal-avatar');
    if (avatarEl && data.firstname) { avatarEl.textContent = data.firstname.charAt(0).toUpperCase(); }

    // Grid logic (Uses "Not Provided" if database column is empty or missing)
    document.getElementById('modal-display-uid').textContent = data.uid || 'Not Provided';
    document.getElementById('modal-firstname').textContent = data.firstname || 'Not Provided';
    document.getElementById('modal-lastname').textContent = data.lastname || 'Not Provided';
    document.getElementById('modal-email').textContent = data.email || 'Not Provided';
    document.getElementById('modal-phone').textContent = data.phone || 'Not Provided';
    document.getElementById('modal-occupation').textContent = data.occupation || 'Not Provided';
    document.getElementById('modal-address').textContent = data.address || 'Not Provided';

    // Document and hidden inputs
    // Fixed path relative to app root (from admin.php served at back-end/php/)
    document.getElementById('modal-doc').href = '../../assets/uploads/' + data.doc;
    document.getElementById('modal-vid').value = data.vid;
    document.getElementById('modal-uid').value = data.uid;

    document.getElementById('details-modal').style.display = 'flex';
}

function closeModal() { document.getElementById('details-modal').style.display = 'none'; }
window.addEventListener('click', function(e) { if (e.target === document.getElementById('details-modal')) closeModal(); });

// --- VERIFICATIONS SEARCHING ---
function filterVerifications() {
    const searchText = document.getElementById('verify-search').value.toLowerCase();
    const rows = document.querySelectorAll('#verifications-table .verify-row');

    for (let i = 0; i < rows.length; i++) {
        // Search through Name, Username, and Email
        const nameData = rows[i].querySelector('.verify-data-name').textContent.toLowerCase();
        const emailData = rows[i].querySelector('.verify-data-email').textContent.toLowerCase();
        
        if (nameData.includes(searchText) || emailData.includes(searchText)) {
            rows[i].style.display = '';
        } else {
            rows[i].style.display = 'none';
        }
    }
}

// --- VERIFICATIONS SORTING ---
function sortVerifications() {
    const sortValue = document.getElementById('verify-sort').value;
    const tbody = document.getElementById('verifications-tbody');
    
    // Convert HTML collection to a standard Array so we can sort it
    const rows = Array.from(tbody.querySelectorAll('.verify-row'));

    rows.sort((a, b) => {
        if (sortValue === 'newest') {
            return b.dataset.date - a.dataset.date; // Highest timestamp first
        } else if (sortValue === 'oldest') {
            return a.dataset.date - b.dataset.date; // Lowest timestamp first
        } else if (sortValue === 'name_az') {
            return a.dataset.firstname.localeCompare(b.dataset.firstname); // Alphabetical
        } else if (sortValue === 'name_za') {
            return b.dataset.firstname.localeCompare(a.dataset.firstname); // Reverse Alphabetical
        }
    });

    // Re-insert the rows into the table in the new sorted order
    rows.forEach(row => tbody.appendChild(row));
}

function filterUsers() {
    // 1. Get the current text from search and the selected status from filter
    const searchText = document.getElementById('user-search').value.toLowerCase();
    const filterStatus = document.getElementById('user-filter').value.toLowerCase();
    
    // 2. Get all rows inside the users table
    const table = document.getElementById('users-table');
    const rows = table.getElementsByClassName('user-row');

    // 3. Loop through every row to check if it matches
    for (let i = 0; i < rows.length; i++) {
        const nameData = rows[i].querySelector('.user-data-name').textContent.toLowerCase();
        const statusData = rows[i].querySelector('.user-data-status').textContent.toLowerCase();

        // 4. Check conditions
        const matchesSearch = nameData.includes(searchText);
        const matchesFilter = (filterStatus === 'all') || (statusData === filterStatus);

        // 5. Hide or show the row instantly
        if (matchesSearch && matchesFilter) {
            rows[i].style.display = '';
        } else {
            rows[i].style.display = 'none';
        }
    }
}

// --- GROUP FILTERING ---
function filterGroups() {
    const searchText = document.getElementById('group-search').value.toLowerCase();
    const filterStatus = document.getElementById('group-filter').value.toLowerCase();
    const table = document.getElementById('groups-table');
    const rows = table.getElementsByClassName('group-row');

    for (let i = 0; i < rows.length; i++) {
        // We check both the Group Name AND the Owner's Name!
        const nameData = rows[i].querySelector('.group-data-name').textContent.toLowerCase();
        const ownerData = rows[i].querySelector('.group-data-owner').textContent.toLowerCase();
        const statusData = rows[i].querySelector('.group-data-status').textContent.toLowerCase();

        const matchesSearch = nameData.includes(searchText) || ownerData.includes(searchText);
        const matchesFilter = (filterStatus === 'all') || (statusData === filterStatus);

        rows[i].style.display = (matchesSearch && matchesFilter) ? '' : 'none';
    }
}

// --- GROUP MODAL LOGIC ---
function openGroupModal(btn) {
    const data = btn.dataset;

    // Populate data
    document.getElementById('gmodal-name').textContent = data.gname;
    document.getElementById('gmodal-owner').textContent = data.owner;
    document.getElementById('gmodal-members').textContent = data.members + " Member(s)";
    document.getElementById('gmodal-amount').textContent = "₱" + parseInt(data.amount).toLocaleString();
    document.getElementById('gmodal-freq').textContent = data.freq;
    document.getElementById('gmodal-gid').value = data.gid;
    document.getElementById('gmodal-memberlist').textContent = data.memberlist;
    document.getElementById('gmodal-created').textContent = data.created;

    // If the group is already closed or finished, disable/hide the close button
    const closeBtn = document.getElementById('btn-force-close');
    if (data.status === 'closed' || data.status === 'finished') {
        closeBtn.style.display = 'none';
    } else {
        closeBtn.style.display = 'block';
    }

    document.getElementById('group-modal').style.display = 'flex';
}

function closeGroupModal() {
    document.getElementById('group-modal').style.display = 'none';
}

// Add close click handler for the new modal
window.addEventListener('click', function(e) { 
    if (e.target === document.getElementById('group-modal')) {
        closeGroupModal();
    }
});

</script>
</body>
</html>