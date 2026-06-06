<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>TrustFund — Controls | Admin</title>
  <link rel="stylesheet" href="../../../assets/css/global.css?v=<?= filemtime(__DIR__ . '/../../../assets/css/global.css') ?>" />
  <link rel="stylesheet" href="../../../assets/css/admin-panel.css?v=<?= filemtime(__DIR__ . '/../../../assets/css/admin-panel.css') ?>" />
</head>
<body>
  <div class="app-layout">
    <?php include __DIR__ . '/../components/sidebar-view.php'; ?>

    <div class="main-content">
      <header class="topbar">
        <div class="topbar-left">
          <span class="topbar-title">Admin → Controls</span>
        </div>
        <div class="topbar-right">
          <a href="index.php" class="btn-outline" style="padding:6px 14px; font-size:13px; text-decoration:none;">← Dashboard</a>
        </div>
      </header>

      <div class="page-content">

        <?php if (isset($_GET['success'])): ?>
          <div style="background:#E8F5EE; border:1px solid #a7f3d0; color:#166534; padding:12px 18px; border-radius:10px; margin-bottom:20px;">
            <?= htmlspecialchars($_GET['success']) ?>
          </div>
        <?php endif; ?>

        <div class="admin-hero" style="margin-bottom:18px;">
          <div>
            <div class="admin-hero-title">Admin Controls</div>
            <div class="admin-hero-sub">Simulation &amp; cycle management tools for testing and oversight.</div>
          </div>
        </div>

        <!-- Active Groups Quick Controls -->
        <div class="section-title" style="font-size:16px; margin-bottom:12px;">Active Groups — Quick Actions</div>

        <?php if (mysqli_num_rows($controls) > 0): ?>
          <div class="table-wrap" style="margin-bottom:32px;">
            <table>
              <thead>
                <tr>
                  <th>Group</th>
                  <th>Current Cycle</th>
                  <th>Members</th>
                  <th>Status</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php while ($cg = mysqli_fetch_assoc($controls)): ?>
                  <tr>
                    <td><strong><?= htmlspecialchars($cg['group_name']) ?></strong></td>
                    <td>#<?= $cg['current_cycle'] ?></td>
                    <td><?= $cg['members'] ?></td>
                    <td><span class="badge badge-active"><?= ucfirst($cg['status']) ?></span></td>
                    <td>
                      <form method="POST" action="controls.php" style="display:inline;">
                        <input type="hidden" name="group_id" value="<?= $cg['group_id'] ?>">
                        <button type="submit" name="admin_advance_cycle" class="btn-row-view" style="font-size:12px; padding:5px 10px;">Advance Cycle</button>
                      </form>
                      <form method="POST" action="controls.php" style="display:inline; margin-left:6px;">
                        <input type="hidden" name="group_id" value="<?= $cg['group_id'] ?>">
                        <input type="hidden" name="cycle_number" value="<?= $cg['current_cycle'] ?>">
                        <button type="submit" name="admin_force_paid" class="btn-row-view" style="font-size:12px; padding:5px 10px; background:#1e40af; color:white;">Force All Paid</button>
                      </form>
                    </td>
                  </tr>
                <?php endwhile; ?>
              </tbody>
            </table>
          </div>
        <?php else: ?>
          <div class="table-wrap" style="display: flex; align-items: center; justify-content: center; min-height: 180px; margin-bottom:32px; text-align: center;">
            <div>
              <div style="font-size: 17px; font-weight: 600; color: #1A1A1A; margin-bottom: 6px;">No active groups.</div>
              <div style="font-size: 14px; color: #6B6560;">Groups will appear here when they are active.</div>
            </div>
          </div>
        <?php endif; ?>

        <!-- Manual Tools -->
        <div class="section-title" style="font-size:16px; margin-bottom:12px;">Manual Tools</div>

        <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 20px;">

          <!-- Set Date -->
          <div class="admin-tool-card">
            <div style="font-weight:700; margin-bottom:12px; font-size:15px;">Update Cycle Start Date</div>
            <form method="POST" action="controls.php">
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
          <div class="admin-tool-card">
            <div style="font-weight:700; margin-bottom:12px; font-size:15px;">Release Payout for Cycle</div>
            <form method="POST" action="controls.php">
              <div class="form-group">
                <label class="input-label">Cycle ID</label>
                <input type="number" name="cycle_id" class="input-field" required placeholder="e.g. 42">
              </div>
              <button type="submit" name="admin_release_payout" class="btn-primary" style="width:100%; background:#166534;">Release Payout</button>
              <div style="font-size:12px; color:#6B6560; margin-top:8px;">This marks the cycle as released and creates a payout record.</div>
            </form>
          </div>

        </div>

      </div>
    </div>
  </div>
  <div class="notif-overlay" id="notif-overlay"></div>
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
  <div class="toast-container" id="toast-container"></div>
  <script src="../../../front-end/js/notifications.js"></script>
</body>
</html>