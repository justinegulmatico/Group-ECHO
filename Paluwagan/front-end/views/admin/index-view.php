<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>TrustFund — Admin Dashboard</title>
  <link rel="stylesheet" href="../../../assets/css/global.css?v=<?= filemtime(__DIR__ . '/../../../assets/css/global.css') ?>" />
  <link rel="stylesheet" href="../../../assets/css/admin-panel.css?v=<?= filemtime(__DIR__ . '/../../../assets/css/admin-panel.css') ?>" />
  <style>
    .topbar-title {
      font-family: var(--font-body);
      font-weight: 700;
      color: var(--color-text-primary);
    }
    .admin-hero-title {
      font-family: var(--font-display);
      font-weight: 700;
    }
    .admin-hero-sub {
      font-family: var(--font-body);
    }
  </style>
</head>
<body>
  <div class="app-layout">
    <?php include __DIR__ . '/../components/sidebar-view.php'; ?>

    <div class="main-content">
      <header class="topbar">
        <div class="topbar-left">
          <span class="topbar-title">Admin Dashboard</span>
        </div>
        <div class="topbar-right"></div>
      </header>

      <div class="page-content">

        <?php if (isset($_GET['success'])): ?>
          <div style="background:#E8F5EE; border:1px solid #a7f3d0; color:#166534; padding:12px 18px; border-radius:10px; margin-bottom:24px;">
            <?= htmlspecialchars($_GET['success']) ?>
          </div>
        <?php endif; ?>

        <!-- Hero -->
        <div class="admin-hero">
          <div>
            <div class="admin-hero-title">Welcome back, Admin</div>
            <div class="admin-hero-sub">Here's what's happening in the TrustFund platform right now.</div>
          </div>
        </div>

        <!-- Quick Stats -->
        <div class="stat-cards" style="margin-bottom: 28px;">
          <a href="verifications.php" class="stat-card admin-stat">
            <div class="stat-card-label">Pending Verifications</div>
            <div class="stat-card-value amber"><?= $pending_verifications ?></div>
            <div class="stat-card-sub">awaiting review →</div>
          </a>
          <a href="users.php" class="stat-card admin-stat">
            <div class="stat-card-label">Total Users</div>
            <div class="stat-card-value"><?= $total_users ?></div>
            <div class="stat-card-sub">registered members →</div>
          </a>
          <a href="groups.php" class="stat-card admin-stat">
            <div class="stat-card-label">Total Groups</div>
            <div class="stat-card-value"><?= $total_groups ?></div>
            <div class="stat-card-sub">all savings circles →</div>
          </a>
          <a href="groups.php" class="stat-card admin-stat">
            <div class="stat-card-label">Active Groups</div>
            <div class="stat-card-value green"><?= $active_groups ?></div>
            <div class="stat-card-sub">currently running →</div>
          </a>
        </div>

        <!-- Main Navigation Cards -->
        <div class="section-title" style="margin-bottom: 12px; font-size: 17px;">Admin Sections</div>

        <div class="admin-grid">
          <a href="verifications.php" class="admin-card">
            <div class="admin-card-icon" style="background:#fef3c7; color:#b45309;">📋</div>
            <div class="admin-card-content">
              <div class="admin-card-title">Pending Verifications</div>
              <div class="admin-card-desc">Review and approve new member identity documents.</div>
              <div class="admin-card-count"><?= $pending_verifications ?> pending</div>
            </div>
          </a>

          <a href="users.php" class="admin-card">
            <div class="admin-card-icon" style="background:#e0f2fe; color:#0369a1;">👥</div>
            <div class="admin-card-content">
              <div class="admin-card-title">User Management</div>
              <div class="admin-card-desc">View all members, suspend or reactivate accounts.</div>
              <div class="admin-card-count"><?= $total_users ?> users</div>
            </div>
          </a>

          <a href="groups.php" class="admin-card">
            <div class="admin-card-icon" style="background:#dcfce7; color:#166534;">📊</div>
            <div class="admin-card-content">
              <div class="admin-card-title">Group Monitoring</div>
              <div class="admin-card-desc">See all groups, member lists, and force-close if needed.</div>
              <div class="admin-card-count"><?= $total_groups ?> groups</div>
            </div>
          </a>
        </div>

        <div style="margin-top: 40px; font-size: 13px; color: #8a7f74;">
          Tip: Use the sidebar to quickly jump between regular user pages. All admin actions are logged via redirects and success messages.
        </div>

      </div>
    </div>
  </div>

</body>
</html>
