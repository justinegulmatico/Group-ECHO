<?php
session_start();
// absolute file path fallback mapping to reach back-end configuration out of your local subfolder 
include "back-end/db.php";

// Core Session Security Gate: Kick unauthenticated guests back to index.php
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

$current_user_id = $_SESSION['user_id'];

// ─── DATA ENGINE: FETCH CURRENT ACTIVE USER PROFILE ───
$user_query = "SELECT first_name, last_name, role FROM users WHERE user_id = '$current_user_id'";
$user_res = mysqli_query($conn, $user_query);
$user_data = mysqli_fetch_assoc($user_res);

$display_name = htmlspecialchars($user_data['first_name'] ?? 'User');
$display_fullname = htmlspecialchars(($user_data['first_name'] ?? 'User') . ' ' . ($user_data['last_name'] ?? ''));
$display_role = htmlspecialchars(ucfirst($user_data['role'] ?? 'Member'));
$avatar_initial = strtoupper(substr($display_name, 0, 1));

// ─── CALCULATION ENGINE: COMPUTE PERSONAL PALUWAGAN STATS ───
// Count total active group memberships for this user
$count_my_groups_query = "SELECT COUNT(*) as total FROM group_members WHERE user_id = '$current_user_id' AND status = 'active'";
$count_my_groups_res = mysqli_query($conn, $count_my_groups_query);
$active_groups_count = mysqli_fetch_assoc($count_my_groups_res)['total'] ?? 0;

// Placeholder values for financial aggregation balances (can be wired to your upcoming payment logs tables)
$total_contributed = 0;
$total_received = 0;
$net_position = $total_received - $total_contributed;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>TrustFund — Dashboard</title>
  <link rel="stylesheet" href="../css/global.css" />
  <link rel="stylesheet" href="../css/dashboard.css" />
</head>
<body>
  <div class="app-layout">

    <aside class="sidebar">
      <div class="sidebar-logo">TrustFund</div>

      <nav class="sidebar-nav">
        <div class="sidebar-section-label">Main</div>

        <a href="dashboard.php" class="sidebar-nav-item active">
          <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"
               stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <path d="M3 9.5L12 3l9 6.5V20a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V9.5z"/>
            <path d="M9 21V12h6v9"/>
          </svg>
          Dashboard
        </a>

        <a href="my_groups.php" class="sidebar-nav-item">
          <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"
               stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
            <circle cx="9" cy="7" r="4"/>
            <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
            <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
          </svg>
          My Groups
        </a>

        <a href="payments.php" class="sidebar-nav-item">
          <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"
               stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <rect x="2" y="5" width="20" height="14" rx="2"/>
            <path d="M2 10h20"/>
          </svg>
          Payments
        </a>

        <a href="my_profile.php" class="sidebar-nav-item">
          <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"
               stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
            <circle cx="12" cy="7" r="4"/>
          </svg>
          My Profile
        </a>
      </nav>

      <div class="sidebar-footer">
        <div class="avatar" id="sidebar-avatar"><?= $avatar_initial; ?></div>
        <div class="sidebar-user-info">
          <div class="sidebar-user-name"><?= $display_name; ?></div>
          <div class="sidebar-user-role"><?= $display_role; ?></div>
        </div>
        <a href="back-end/logout.php" class="sidebar-logout-btn" title="Log out">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
               stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
            <polyline points="16 17 21 12 16 7"/>
            <line x1="21" y1="12" x2="9" y2="12"/>
          </svg>
        </a>
      </div>
    </aside>

    <div class="main-content">

      <header class="topbar">
        <div class="topbar-left">
          <span class="topbar-title">Dashboard</span>
        </div>
        <div class="topbar-right">
          <button class="notif-btn" id="notif-btn" aria-label="Notifications">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                 stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
              <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
            </svg>
            <span class="notif-badge" id="notif-badge" style="display:none;"></span>
          </button>
        </div>
      </header>

      <div class="page-content">

        <div class="stat-cards">
          <div class="stat-card">
            <div class="stat-card-label">Active Groups</div>
            <div class="stat-card-value"><?= $active_groups_count; ?></div>
            <div class="stat-card-sub">total joined</div>
          </div>
          <div class="stat-card">
            <div class="stat-card-label">Total Contributed</div>
            <div class="stat-card-value">₱<?= number_format($total_contributed); ?></div>
            <div class="stat-card-sub">across all groups</div>
          </div>
          <div class="stat-card">
            <div class="stat-card-label">Total Received</div>
            <div class="stat-card-value green">₱<?= number_format($total_received); ?></div>
            <div class="stat-card-sub">payouts received</div>
          </div>
          <div class="stat-card">
            <div class="stat-card-label">Net Position</div>
            <div class="stat-card-value <?= $net_position >= 0 ? 'green' : 'red'; ?>">₱<?= number_format($net_position); ?></div>
            <div class="stat-card-sub">received minus contributed</div>
          </div>
        </div>

        <div class="section-header">
          <span class="section-title">Active Groups</span>
          <button class="new-group-btn" id="open-create-modal">+ New Group</button>
        </div>

        <?php if ($active_groups_count == 0): ?>
            <div class="empty-state">
              <svg class="empty-state-icon" viewBox="0 0 24 24" fill="none"
                   stroke="currentColor" stroke-width="1.4" stroke-linecap="round"
                   stroke-linejoin="round">
                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                <circle cx="9" cy="7" r="4"/>
                <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
              </svg>
              <div class="empty-state-title">No Groups Yet</div>
              <div class="empty-state-desc">Create or join a savings group to get started</div>
              <a href="my-groups.php" class="btn-primary" style="width:auto; padding:12px 28px; margin-top:8px; text-decoration:none; display:inline-block; text-align:center;">
                Browse Groups →
              </a>
            </div>
        <?php else: ?>
            <div class="active-groups-container" style="background:#fff; border:1px solid #e3e3e3; padding:20px; border-radius:12px;">
                <p style="color:#4a4a4a; font-weight:500;">You are currently participating in <?= $active_groups_count; ?> savings loop(s).</p>
                <a href="my-groups.php" style="color:var(--color-primary, #e8481a); font-size:0.9rem; font-weight:600; text-decoration:none; margin-top:10px; display:inline-block;">Go to My Groups dashboard to track individual charts →</a>
            </div>
        <?php endif; ?>

      </div></div></div><div class="notif-overlay" id="notif-overlay"></div>
  <div class="notif-panel" id="notif-panel">
    <div class="notif-panel-header">
      <span class="notif-panel-title">Notifications</span>
      <button class="mark-all-btn" id="mark-all-btn">Mark all read</button>
    </div>
    <div class="notif-list" id="notif-list">
      <div class="notif-empty">
        <p>No notifications</p>
        <span>You're all caught up!</span>
      </div>
    </div>
  </div>

  <div class="modal-overlay" id="modal-create" style="display:none;">
    <div class="modal">
      <div class="modal-header">
        <span class="modal-title">Create New Group</span>
        <button class="modal-close" type="button">✕</button>
      </div>
      <form method="POST" action="back-end/create_group_process.php">
          <div class="modal-body">
            <div class="form-group">
              <label class="input-label" for="cg-name">Group Name</label>
              <input class="input-field" id="cg-name" name="group_name" type="text" placeholder="e.g. Barkada Savings 2026" required />
            </div>
            <div class="form-group">
              <label class="input-label" for="cg-desc">Description <span style="font-weight:400;color:#7c7c7c;">(optional)</span></label>
              <input class="input-field" id="cg-desc" name="group_desc" type="text" placeholder="What is this group for?" />
            </div>
            <div class="form-row">
              <div class="form-group" style="flex:1;">
                <label class="input-label" for="cg-amount">Contribution Amount (₱)</label>
                <input class="input-field" id="cg-amount" name="contribution_amount" type="number" placeholder="1000" min="1" required />
              </div>
              <div class="form-group" style="flex:1;">
                <label class="input-label" for="cg-slots">Total Slots</label>
                <input class="input-field" id="cg-slots" name="total_slots" type="number" placeholder="10" min="2" max="50" required />
              </div>
            </div>
            <div class="form-row">
              <div class="form-group" style="flex:1;">
                <label class="input-label" for="cg-frequency">Payment Frequency</label>
                <select class="input-field" id="cg-frequency" name="payment_frequency">
                  <option value="monthly">Monthly</option>
                  <option value="biweekly">Bi-weekly</option>
                  <option value="weekly">Weekly</option>
                </select>
              </div>
              <div class="form-group" style="flex:1;">
                <label class="input-label" for="cg-startdate">Start Date</label>
                <input class="input-field" id="cg-startdate" name="start_date" type="date" required />
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button class="btn-primary" type="submit" name="action_create_group">Create Group</button>
          </div>
      </form>
    </div>
  </div>

  <div class="toast-container" id="toast-container"></div>
  
  <script>
    const openCreateModal = document.getElementById('open-create-modal');
    const modalCreate = document.getElementById('modal-create');
    const modalClose = modalCreate?.querySelector('.modal-close');

    const closeModal = () => {
      if (modalCreate) modalCreate.style.display = 'none';
    };

    if (openCreateModal) {
      openCreateModal.addEventListener('click', () => {
        if (modalCreate) modalCreate.style.display = 'flex';
      });
    }

    if (modalClose) {
      modalClose.addEventListener('click', closeModal);
    }

    if (modalCreate) {
      modalCreate.addEventListener('click', event => {
        if (event.target === modalCreate) {
          closeModal();
        }
      });
    }
  </script>
  <script src="../js/notifications.js"></script>
</body>
</html>