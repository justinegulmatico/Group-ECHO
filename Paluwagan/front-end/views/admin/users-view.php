<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>TrustFund — Users | Admin</title>
  <link rel="stylesheet" href="../../../assets/css/global.css?v=<?= filemtime(__DIR__ . '/../../../assets/css/global.css') ?>" />
  <link rel="stylesheet" href="../../../assets/css/admin-panel.css?v=<?= filemtime(__DIR__ . '/../../../assets/css/admin-panel.css') ?>" />
</head>
<body>
  <div class="app-layout">
    <?php include __DIR__ . '/../components/sidebar-view.php'; ?>

    <div class="main-content">
      <header class="topbar">
        <div class="topbar-left">
          <span class="topbar-title">Admin → Users</span>
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
            <div class="admin-hero-title">User Management</div>
            <div class="admin-hero-sub">Suspend or reactivate member accounts.</div>
          </div>
        </div>

        <div class="tf-action-bar" style="margin-bottom:16px;">
          <input type="text" id="search" class="tf-search-input" placeholder="Search by name or username..." onkeyup="filterUsers()">
          <select id="status-filter" class="tf-filter-select" onchange="filterUsers()">
            <option value="all">All Statuses</option>
            <option value="activated">Activated</option>
            <option value="pending">Pending</option>
            <option value="suspended">Suspended</option>
            <option value="denied">Denied</option>
          </select>
        </div>

        <?php if (mysqli_num_rows($users) > 0): ?>
          <div class="table-wrap">
            <table id="users-table">
              <thead>
                <tr>
                  <th>User</th>
                  <th>Email</th>
                  <th>Status</th>
                  <th>Joined</th>
                  <th style="text-align:right;">Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php while ($u = mysqli_fetch_assoc($users)): 
                  $status = strtolower($u['status'] ?? 'pending');
                  if ($status === '') $status = 'pending';

                  $badge = 'badge-pending';
                  if ($status === 'activated') $badge = 'badge-active';
                  if ($status === 'suspended' || $status === 'denied') $badge = 'badge-suspended';
                ?>
                  <tr class="user-row">
                    <td>
                      <div class="user-cell">
                        <div class="user-cell-avatar" style="background:#ffedd5; color:#c2410c;">
                          <?= strtoupper(substr($u['first_name'], 0, 1)) ?>
                        </div>
                        <div>
                          <div class="user-cell-name"><?= htmlspecialchars($u['first_name'] . ' ' . $u['last_name']) ?></div>
                          <div class="user-cell-sub">@<?= htmlspecialchars($u['username']) ?></div>
                        </div>
                      </div>
                    </td>
                    <td><?= htmlspecialchars($u['email']) ?></td>
                    <td><span class="badge <?= $badge ?> user-status"><?= ucfirst($status) ?></span></td>
                    <td style="color:#6B6560; font-size:13px;"><?= date('M d, Y', strtotime($u['created_at'])) ?></td>
                    <td style="text-align:right;">
                      <?php if ($status !== 'suspended'): ?>
                        <a href="users.php?action=suspend&id=<?= $u['user_id'] ?>" 
                           class="btn-row-view" style="background:#fee2e2; color:#991b1b; border-color:#fca5a5;">Suspend</a>
                      <?php else: ?>
                        <a href="users.php?action=activate&id=<?= $u['user_id'] ?>" 
                           class="btn-row-view" style="background:#d1fae5; color:#065f46; border-color:#a7f3d0;">Activate</a>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endwhile; ?>
              </tbody>
            </table>
          </div>
        <?php else: ?>
          <div class="table-wrap" style="display: flex; align-items: center; justify-content: center; min-height: 220px; text-align: center;">
            <div>
              <div style="font-size: 17px; font-weight: 600; color: #1A1A1A; margin-bottom: 6px;">No users found.</div>
              <div style="font-size: 14px; color: #6B6560;">All registered members will appear here.</div>
            </div>
          </div>
        <?php endif; ?>

      </div>
    </div>
  </div>

  <script>
    function filterUsers() {
      const search = document.getElementById('search').value.toLowerCase();
      const filter = document.getElementById('status-filter').value.toLowerCase();

      document.querySelectorAll('#users-table tbody tr').forEach(row => {
        const name = row.querySelector('.user-cell-name')?.textContent.toLowerCase() || '';
        const statusEl = row.querySelector('.user-status');
        const status = statusEl ? statusEl.textContent.toLowerCase() : '';

        const matchSearch = name.includes(search);
        const matchFilter = (filter === 'all') || (status === filter);

        row.style.display = (matchSearch && matchFilter) ? '' : 'none';
      });
    }
  </script>
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