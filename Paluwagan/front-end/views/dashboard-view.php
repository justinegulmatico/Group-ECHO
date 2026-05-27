<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>TrustFund — Dashboard</title>
  <link rel="stylesheet" href="../../assets/css/global.css" />
  <link rel="stylesheet" href="../../assets/css/dashboard.css" />
</head>
<body>
  <div class="app-layout">

    <?php include "components/sidebar-view.php"; ?>

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
              <a href="my_groups.php" class="btn-primary" style="width:auto; padding:12px 28px; margin-top:8px; text-decoration:none; display:inline-block; text-align:center;">
                Browse Groups →
              </a>
            </div>
        <?php else: ?>
            <div class="active-groups-container" style="background:#fff; border:1px solid #e3e3e3; padding:20px; border-radius:12px;">
                <p style="color:#4a4a4a; font-weight:500;">You are currently participating in <?= $active_groups_count; ?> savings loop(s).</p>
                <a href="my_groups.php" style="color:var(--color-primary, #e8481a); font-size:0.9rem; font-weight:600; text-decoration:none; margin-top:10px; display:inline-block;">Go to My Groups dashboard to track individual charts →</a>
            </div>
        <?php endif; ?>

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

  <div class="modal-overlay" id="modal-create" style="display:none;">
    <div class="modal">
      <div class="modal-header">
        <span class="modal-title">Create New Group</span>
        <button class="modal-close" type="button">✕</button>
      </div>
      <form method="POST" action="process/process_create_group.php">
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
                <input class="input-field" id="cg-amount" name="contribution" type="number" placeholder="1000" min="1" required />
              </div>
              <div class="form-group" style="flex:1;">
                <label class="input-label" for="cg-slots">Total Slots</label>
                <input class="input-field" id="cg-slots" name="cycle_length" type="number" placeholder="10" min="2" max="50" required />
              </div>
            </div>
            <div class="form-row">
              <div class="form-group" style="flex:1;">
                <label class="input-label" for="cg-frequency">Payment Frequency</label>
                <select class="input-field" id="cg-frequency" name="frequency">
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