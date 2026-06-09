<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$current_page = basename($_SERVER['PHP_SELF']);

$first_name = $_SESSION['first_name'] ?? ($_SESSION['username'] ?? 'User');
$last_name  = $_SESSION['last_name'] ?? '';
$full_name  = !empty($last_name) ? "$first_name $last_name" : $first_name;
$user_role  = $_SESSION['role'] ?? 'Member';

$initials   = strtoupper(substr($first_name, 0, 1) . (!empty($last_name) ? substr($last_name, 0, 1) : ''));
if (empty($initials)) { 
    $initials = !empty($first_name) ? strtoupper(substr($first_name, 0, 2)) : "US"; 
}

$is_nested_process = (strpos($_SERVER['PHP_SELF'], '/process/') !== false);
$is_admin_sub = (strpos($_SERVER['PHP_SELF'], '/admin/') !== false);
$prefix = $is_nested_process ? '../' : ($is_admin_sub ? '../' : '');

$current_page = basename($_SERVER['PHP_SELF']);

$js_path = $is_admin_sub ? '../../../assets/js/' : '../../assets/js/';
$js_file = __DIR__ . '/../../../assets/js/sidebar.js';
$js_version = file_exists($js_file) ? filemtime($js_file) : time();
?>
<aside class="sidebar">
  <div class="sidebar-logo">
    <span class="sidebar-logo-text">TrustFund</span>
    <button id="sidebar-toggle" class="sidebar-collapse-btn" title="Toggle sidebar">
      &laquo;
    </button>
  </div>

  <nav class="sidebar-nav">
    <?php if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin'): ?>
    <div class="sidebar-section-label">Main</div>

    <a href="<?= $prefix ?>dashboard.php" class="sidebar-nav-item <?= ($current_page == 'dashboard.php') ? 'active' : '' ?>">
      <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
        <path d="M3 9.5L12 3l9 6.5V20a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V9.5z"/><path d="M9 21V12h6v9"/>
      </svg>
      <span class="nav-text">Dashboard</span>
    </a>

    <a href="<?= $prefix ?>my_groups.php" class="sidebar-nav-item <?= ($current_page == 'my_groups.php' || $current_page == 'group_details.php') ? 'active' : '' ?>">
      <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>
      </svg>
      <span class="nav-text">My Groups</span>
    </a>

    <a href="<?= $prefix ?>payments.php" class="sidebar-nav-item <?= ($current_page == 'payments.php') ? 'active' : '' ?>">
      <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
        <rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/>
      </svg>
      <span class="nav-text">Payments</span>
    </a>
    <?php endif; ?>

    <a href="<?= $prefix ?>my_profile.php" class="sidebar-nav-item <?= ($current_page == 'my_profile.php') ? 'active' : '' ?>">
      <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>
      </svg>
      <span class="nav-text">My Profile</span>
    </a>

    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
      <div class="sidebar-section-label" style="margin-top: 20px;">Admin</div>
      <a href="<?= $prefix ?>admin/index.php" class="sidebar-nav-item <?= ($current_page === 'index.php' && strpos($_SERVER['PHP_SELF'], '/admin/') !== false) ? 'active' : '' ?>">
        <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
          <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
          <polyline points="9 12 11 14 15 10"/>
        </svg>
        <span class="nav-text">Admin Panel</span>
      </a>
      <a href="<?= $prefix ?>admin/transactions.php" class="sidebar-nav-item <?= ($current_page === 'transactions.php') ? 'active' : '' ?>">
        <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
          <rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/>
          <path d="M6 15h.01M10 15h4"/>
        </svg>
        <span class="nav-text">Transaction Approvals</span>
      </a>
      <a href="<?= $prefix ?>admin/analytics.php" class="sidebar-nav-item <?= ($current_page === 'analytics.php') ? 'active' : '' ?>">
        <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
          <path d="M3 3v18h18"/><path d="M18 17V9"/><path d="M13 17V5"/><path d="M8 17v-3"/>
        </svg>
        <span class="nav-text">OLAP Analytics</span>
      </a>
    <?php endif; ?>
  </nav>

  <div class="sidebar-footer">
    <div class="avatar" id="sidebar-avatar"><?= htmlspecialchars($initials); ?></div>
    <div class="sidebar-user-info">
      <div class="sidebar-user-name" title="<?= htmlspecialchars($full_name); ?>"><?= htmlspecialchars($full_name); ?></div>
      <div class="sidebar-user-role" style="text-transform: capitalize;"><?= htmlspecialchars($user_role); ?></div>
    </div>
    <button type="button" class="sidebar-logout-btn" id="logout-trigger" data-logout-url="<?= $prefix ?>logout.php" title="Sign Out" style="font:inherit; padding:0; margin:0; appearance:none; -webkit-appearance:none;">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
        <polyline points="16 17 21 12 16 7"/>
        <line x1="21" y1="12" x2="9" y2="12"/>
      </svg>
    </button>
  </div>
</aside>

<!-- Logout Confirmation Modal -->
<div id="logout-modal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.55); z-index:99999; align-items:center; justify-content:center;">
  <div onclick="event.target.id==='logout-modal' && hideLogoutModal()" style="position:absolute; inset:0;"></div>
  <div style="position:relative; background:#fff; border:1.5px solid #E4DDD4; border-radius:16px; padding:28px 32px; width:100%; max-width:340px; box-shadow:0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1); text-align:center;">
    <div style="margin-bottom:16px;">
      <div style="width:48px; height:48px; margin:0 auto 12px; background:#FEF2F2; border-radius:9999px; display:flex; align-items:center; justify-content:center;">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#c93d12" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
          <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
          <polyline points="16 17 21 12 16 7"/>
          <line x1="21" y1="12" x2="9" y2="12"/>
        </svg>
      </div>
      <div style="font-size:18px; font-weight:700; color:#1f2937; margin-bottom:6px;">Are you sure to log out?</div>
      <div style="font-size:13.5px; color:#6B6560; line-height:1.4;">You will be signed out of your account and redirected to the login page.</div>
    </div>

    <div style="display:flex; gap:10px; margin-top:20px;">
      <button type="button" onclick="hideLogoutModal()" style="flex:1; padding:10px 16px; font-size:14px; font-weight:600; border:1.5px solid #D1C9BE; background:#fff; color:#374151; border-radius:10px; cursor:pointer;">
        Cancel
      </button>
      <button type="button" id="confirm-logout-btn" style="flex:1; padding:10px 16px; font-size:14px; font-weight:600; border:none; background:#c93d12; color:#fff; border-radius:10px; cursor:pointer;">
        Log out
      </button>
    </div>
  </div>
</div>

<script>
  (function() {
    const trigger = document.getElementById('logout-trigger');
    const modal = document.getElementById('logout-modal');
    const confirmBtn = document.getElementById('confirm-logout-btn');

    if (!trigger || !modal) return;

    function showLogoutModal() {
      modal.style.display = 'flex';
      setTimeout(() => {
        const cancel = modal.querySelector('button');
        if (cancel) cancel.focus();
      }, 10);
    }

    window.hideLogoutModal = function() {
      modal.style.display = 'none';
    };

    trigger.addEventListener('click', function(e) {
      e.preventDefault();
      showLogoutModal();
    });

    // Confirm logout
    if (confirmBtn) {
      confirmBtn.addEventListener('click', function() {
        const url = trigger.getAttribute('data-logout-url') || 'logout.php';
        window.location.href = url;
      });
    }

    // Close on Escape key
    document.addEventListener('keydown', function(e) {
      if (e.key === 'Escape' && modal.style.display !== 'none') {
        hideLogoutModal();
      }
    });
  })();
</script>

<script src="<?= $js_path ?>sidebar.js?v=<?= $js_version ?>"></script>
