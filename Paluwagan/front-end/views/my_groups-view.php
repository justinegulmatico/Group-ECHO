<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>TrustFund — My Groups</title>
  <link rel="stylesheet" href="../../assets/css/global.css" />
  <link rel="stylesheet" href="../../assets/css/my-groups.css" />
</head>
<body>
  <div class="app-layout">

    <?php include "components/sidebar-view.php"; ?>

    <div class="main-content">

      <header class="topbar">
        <div class="topbar-left">
          <span class="topbar-title">My Groups</span>
        </div>
        <div class="topbar-right">
          <button class="notif-btn" id="notif-btn" aria-label="Notifications">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
              <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
            </svg>
            <span class="notif-badge" id="notif-badge" style="display:none;"></span>
          </button>
        </div>
      </header>

      <div class="page-content">

        <?php if (isset($_GET['error'])): ?>
          <div style="background-color: #fef2f2; border: 1px solid #fecaca; color: #991b1b; padding: 14px 18px; border-radius: 12px; margin-bottom: 24px; font-size: 14px; font-weight: 600; display: flex; align-items: center; gap: 10px; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink: 0;">
              <circle cx="12" cy="12" r="10"></circle>
              <line x1="12" y1="8" x2="12" y2="12"></line>
              <line x1="12" y1="16" x2="12.01" y2="16"></line>
            </svg>
            <span><?= htmlspecialchars($_GET['error']); ?></span>
          </div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
            <div style="background-color: #fef2f2; border: 1px solid #fca5a5; color: #b91c1c; padding: 12px 16px; border-radius: 12px; margin-bottom: 20px; font-size: 14px;">
                <?= $error_message; ?>
            </div>
        <?php endif; ?>
        <?php if (isset($_GET['success'])): ?>
            <div style="background-color: #f0fdf4; border: 1px solid #bbf7d0; color: #166534; padding: 12px 16px; border-radius: 12px; margin-bottom: 20px; font-size: 14px;">
                <?= htmlspecialchars($_GET['success']); ?>
            </div>
        <?php endif; ?>

        <div class="page-toolbar">
          <div class="toolbar-left">
            <div class="search-wrap">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="11" cy="11" r="8"/>
                <path d="M21 21l-4.35-4.35"/>
              </svg>
              <input class="search-input" type="text" placeholder="Search groups…" />
            </div>
          </div>
          <div class="toolbar-right">
            <button class="btn-outline" onclick="toggleJoinModal(true)">Join with Code</button>
            <button class="btn-create" onclick="toggleCreateModal(true)">+ Create Group</button>
          </div>
        </div>

        <?php if (mysqli_num_rows($groups_res) > 0): ?>
          <div class="group-cards-grid">
            <?php while ($group = mysqli_fetch_assoc($groups_res)):
                $progress = 0; 
                
                // DEFENSIVE FIX: Automatically determine if database row uses 'group_id' or 'id'
                $actual_group_id = isset($group['group_id']) ? $group['group_id'] : (isset($group['id']) ? $group['id'] : 0);
                
                // Corrected layout: Evaluate group closure status strictly using the actual is_active schema column
                $is_closed = (intval($group['is_active']) === 0);
                $status_label = $is_closed ? 'force closed' : 'active';
                $status_badge_class = $is_closed ? 'badge-closed' : 'badge-active';
                
                // Disallow click events and alert with the specific closed phrase if a deactivated row bypasses filters
                $click_action = $is_closed 
                  ? "alert('Unable to enter: the group pool is closed.'); return false;" 
                  : "window.location.href='group_details.php?id=" . $actual_group_id . "'";
            ?>
              <div onclick="<?= $click_action; ?>" class="group-card" style="cursor: pointer; <?= $is_closed ? 'opacity: 0.75;' : ''; ?>">
                <div class="group-card-top">
                  <div>
                    <div class="group-card-name"><?= htmlspecialchars($group['group_name']); ?></div>
                    <div class="group-card-meta" style="text-transform: capitalize;"><?= htmlspecialchars($group['frequency'] ?? 'monthly'); ?> · ₱<?= number_format($group['contribution_amount'], 0); ?>/cycle</div>
                  </div>
                  <span class="badge <?= $status_badge_class; ?>" style="<?= $is_closed ? 'background-color: #fee2e2; color: #991b1b;' : ''; ?>"><?= $status_label; ?></span>
                </div>
                
                <div class="group-card-meta" style="font-family: monospace; font-size: 11px; margin-bottom: 4px; color: #9ca3af;">
                  Invite Code: <?= htmlspecialchars($group['invite_code'] ?? '—'); ?>
                </div>

                <div class="group-card-collected">₱0 collected</div>
                <div class="progress-bar-wrap">
                  <div class="progress-bar-fill" style="width: <?= $progress; ?>%"></div>
                </div>
                
                <div class="group-card-bottom">
                  <div class="member-chips">
                    <div class="member-chip" style="background-color: #f1f5f9; color: #475569; font-size: 10px; font-weight: bold; width: 24px; height: 24px; border-radius: 50%; display: flex; align-items: center; justify-content: center;">TF</div>
                  </div>
                  <span class="group-card-members"><?= $group['members_count']; ?> / <?= intval($group['cycle_length']); ?> members</span>
                </div>
              </div>
            <?php endwhile; ?>
          </div>
        <?php else: ?>
          <div class="empty-state">
            <svg class="empty-state-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round">
              <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
              <circle cx="9" cy="7" r="4"/>
              <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
              <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
            </svg>
            <div class="empty-state-title">No Groups Found</div>
            <div class="empty-state-desc">Create a new group or join one with an invite code to get started.</div>
          </div>
        <?php endif; ?>

      </div> 
    </div> 
  </div> 

  <div class="modal-overlay" id="createGroupModal" style="display:none;">
    <div class="modal">
      <div class="modal-header">
        <span class="modal-title">Create New Group</span>
        <button class="modal-close" onclick="toggleCreateModal(false)">✕</button>
      </div>
      <div class="modal-body">
        <form method="POST" action="../../back-end/php/my_groups.php">
          <div class="form-group">
            <label class="input-label" for="cg-name">Group Name</label>
            <input class="input-field" id="cg-name" type="text" name="group_name" required placeholder="e.g. Office Savings Pool" />
          </div>

          <div class="form-row">
            <div class="form-group">
              <label class="input-label" for="cg-amount">Contribution Amount (₱)</label>
              <input class="input-field" id="cg-amount" type="number" name="contribution" required placeholder="1000" min="50" />
            </div>
            <div class="form-group">
              <label class="input-label" for="cg-slots">Total Slots</label>
              <input class="input-field" id="cg-slots" type="number" name="cycle_length" required placeholder="10" min="2" max="20" />
            </div>
          </div>

          <div class="form-group">
            <label class="input-label" for="cg-frequency">Payment Frequency</label>
            <select class="input-field select-field" id="cg-frequency" name="frequency" style="appearance: auto;">
              <option value="monthly">Monthly</option>
              <option value="weekly">Weekly</option>
            </select>
          </div>

          <div class="modal-footer" style="padding: 16px 0 0 0; border: none;">
            <button class="btn-outline" type="button" onclick="toggleCreateModal(false)">Cancel</button>
            <button class="btn-create" type="submit" name="action_create_group">Create Group</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <div class="modal-overlay" id="joinGroupModal" style="display:none;">
    <div class="modal">
      <div class="modal-header">
        <span class="modal-title">Join a Group</span>
        <button class="modal-close" onclick="toggleJoinModal(false)">✕</button>
      </div>
      <div class="modal-body">
        <form method="POST" action="../../back-end/php/my_groups.php">
          <div class="form-group">
            <label class="input-label" for="join-code">Invite Code</label>
            <input class="input-field" id="join-code" type="text" name="target_invite_code" required placeholder="Enter 6-character code" maxlength="8" style="text-transform: uppercase; font-family: monospace; letter-spacing: 0.1em;" />
          </div>

          <div class="modal-footer" style="padding: 16px 0 0 0; border: none;">
            <button class="btn-outline" type="button" onclick="toggleJoinModal(false)">Cancel</button>
            <button class="btn-create" type="submit" name="action_join_group">Join Group</button>
          </div>
        </form>
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

  <script>
  function toggleCreateModal(show) {
      const modal = document.getElementById('createGroupModal');
      if (modal) modal.style.display = show ? 'flex' : 'none';
  }
  function toggleJoinModal(show) {
      const modal = document.getElementById('joinGroupModal');
      if (modal) modal.style.display = show ? 'flex' : 'none';
  }

  window.addEventListener('click', function(e) {
      const createModal = document.getElementById('createGroupModal');
      const joinModal = document.getElementById('joinGroupModal');
      if (e.target === createModal) toggleCreateModal(false);
      if (e.target === joinModal) toggleJoinModal(false);
  });
  </script>
  <script src="../../front-end/js/notifications.js"></script>
</body>
</html>