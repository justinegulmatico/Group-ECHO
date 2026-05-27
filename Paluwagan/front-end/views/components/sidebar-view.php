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
$prefix = $is_nested_process ? '../' : '';
?>
<aside class="sidebar">
  <div class="sidebar-logo">TrustFund</div>

  <nav class="sidebar-nav">
    <div class="sidebar-section-label">Main</div>

    <a href="<?= $prefix ?>dashboard.php" class="sidebar-nav-item <?= ($current_page == 'dashboard.php') ? 'active' : '' ?>">
      <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
        <path d="M3 9.5L12 3l9 6.5V20a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V9.5z"/><path d="M9 21V12h6v9"/>
      </svg>
      Dashboard
    </a>

    <a href="<?= $prefix ?>my_groups.php" class="sidebar-nav-item <?= ($current_page == 'my_groups.php' || $current_page == 'group_details.php' || $current_page == 'process_create_group.php' || $current_page == 'process_join_group.php') ? 'active' : '' ?>">
      <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>
      </svg>
      My Groups
    </a>

    <a href="<?= $prefix ?>payments.php" class="sidebar-nav-item <?= ($current_page == 'payments.php') ? 'active' : '' ?>">
      <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
        <rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/>
      </svg>
      Payments
    </a>

    <a href="<?= $prefix ?>my_profile.php" class="sidebar-nav-item <?= ($current_page == 'my_profile.php') ? 'active' : '' ?>">
      <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>
      </svg>
      My Profile
    </a>

    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
      <div class="sidebar-section-label" style="margin-top: 20px;">Admin</div>
      <a href="<?= $prefix ?>admin.php" class="sidebar-nav-item <?= ($current_page == 'admin.php' || $current_page == 'admin_process.php') ? 'active' : '' ?>">
        <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
          <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
          <polyline points="9 12 11 14 15 10"/>
        </svg>
        Admin Panel
      </a>
    <?php endif; ?>
  </nav>

  <div class="sidebar-footer">
    <div class="avatar" id="sidebar-avatar"><?= htmlspecialchars($initials); ?></div>
    <div class="sidebar-user-info">
      <div class="sidebar-user-name" title="<?= htmlspecialchars($full_name); ?>"><?= htmlspecialchars($full_name); ?></div>
      <div class="sidebar-user-role" style="text-transform: capitalize;"><?= htmlspecialchars($user_role); ?></div>
    </div>
    <a href="<?= $prefix ?>logout.php" class="sidebar-logout-btn" title="Sign Out">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
        <polyline points="16 17 21 12 16 7"/>
        <line x1="21" y1="12" x2="9" y2="12"/>
      </svg>
    </a>
  </div>
</aside>