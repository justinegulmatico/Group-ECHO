<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>TrustFund — Dashboard</title>
  <link rel="stylesheet" href="../../assets/css/global.css?v=<?= filemtime('../../assets/css/global.css'); ?>" />
  <link rel="stylesheet" href="../../assets/css/dashboard.css?v=<?= filemtime('../../assets/css/dashboard.css'); ?>" />
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
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
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
          <button class="new-group-btn" id="open-group-choice">+ New Group</button>
        </div>

        <!-- Simple Paluwagan rotation hint -->
        <?php if (isset($next_payout_info)): ?>
          <div style="background:#fff7ed; border:1px solid #fed7aa; padding:12px 16px; border-radius:10px; margin-bottom:20px; font-size:14px;">
            <strong>Next in your rotation:</strong> <?= $next_payout_info ?>
          </div>
        <?php endif; ?>

        <?php if ($active_groups_count == 0): ?>
            <div class="empty-state">
              <svg class="empty-state-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round">
                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                <circle cx="9" cy="7" r="4"/>
                <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
              </svg>
              <div class="empty-state-title">No Groups Yet</div>
              <div class="empty-state-desc">Create or join a savings group to get started</div>
              <a href="my_groups.php" class="btn-primary" style="width:auto; padding:12px 28px; margin-top:8px; text-decoration:none; display:inline-block; text-align:center;">Browse Groups →</a>
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

  <div class="modal-overlay" id="modal-create" style="display:none;">
    <div class="modal">
      <div class="modal-header">
        <span class="modal-title">Create New Group</span>
        <button class="modal-close" type="button">✕</button>
      </div>
      <form method="POST" action="dashboard.php">
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
                <label class="input-label" for="cg-privacy">Privacy</label>
                <select class="input-field" id="cg-privacy" name="privacy" onchange="toggleInviteCode()">
                  <option value="public">Public (Anyone can join)</option>
                  <option value="private">Private (Invite Code Required)</option>
                </select>
              </div>
              <div class="form-group" id="invite-code-container" style="flex:1; display:none;">
                <label class="input-label" for="cg-invitecode">Generated Invite Code</label>
                <input class="input-field" id="cg-invitecode" name="invite_code" type="text" readonly style="background:#eef2f5; font-family:monospace; letter-spacing:1px; font-weight:bold; color:#f47321; text-align:center;" />
              </div>
            </div>

            <div class="form-row">
              <div class="form-group" style="flex:1;">
                <label class="input-label" for="cg-amount">Contribution Amount (₱)</label>
                <input class="input-field" id="cg-amount" name="contribution" type="number" placeholder="1000" min="1" required />
              </div>
              <div class="form-group" style="flex:1;">
                <label class="input-label" for="cg-slots">Total Slots / Members</label>
                <input class="input-field" id="cg-slots" name="max_members" type="number" placeholder="10" min="2" max="50" required />
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

<!-- Group Choice Modal -->
<div class="modal-overlay" id="group-choice-modal" style="display:none;">
  <div class="modal" style="max-width: 400px; padding: 0;">
    
    <div class="modal-header" style="padding: 20px 24px 12px; border-bottom: 1px solid #E4DDD4;">
      <span class="modal-title" style="font-size: 18px;">What would you like to do?</span>
      <button class="modal-close" onclick="closeChoiceModal()">✕</button>
    </div>

    <div class="modal-body" style="padding: 20px 24px 28px;">
      
      <!-- Create Group -->
      <div onclick="chooseCreateGroup()" 
           style="display:flex; align-items:center; gap:14px; padding:16px; border:2px solid #E4DDD4; border-radius:12px; cursor:pointer; margin-bottom:12px; transition:all 0.2s;"
           onmouseover="this.style.borderColor='#E8481A'"
           onmouseout="this.style.borderColor='#E4DDD4'">
        <div style="width:44px; height:44px; background:#E8481A; border-radius:10px; display:flex; align-items:center; justify-content:center; color:white; font-size:22px; flex-shrink:0;">
          +
        </div>
        <div>
          <div style="font-weight:700; font-size:16px;">Create New Group</div>
          <div style="font-size:13px; color:#6B6560;">Start your own Paluwagan savings group</div>
        </div>
      </div>

      <!-- Join Group -->
      <div onclick="chooseJoinGroup()" 
           style="display:flex; align-items:center; gap:14px; padding:16px; border:2px solid #E4DDD4; border-radius:12px; cursor:pointer; transition:all 0.2s;"
           onmouseover="this.style.borderColor='#E8481A'"
           onmouseout="this.style.borderColor='#E4DDD4'">
        <div style="width:44px; height:44px; background:#F5F0E8; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:20px; flex-shrink:0;">
          🔑
        </div>
        <div>
          <div style="font-weight:700; font-size:16px;">Join Existing Group</div>
          <div style="font-size:13px; color:#6B6560;">Enter an invite code to join a group</div>
        </div>
      </div>

    </div>
  </div>
</div>

<!-- Join Group Modal -->
<div class="modal-overlay" id="joinGroupModal" style="display:none;">
  <div class="modal">
    
    <!-- Header -->
    <div class="modal-header">
      <span class="modal-title">Join a Group</span>
      <button class="modal-close" onclick="toggleJoinModal(false)">✕</button>
    </div>

    <div class="modal-body">
      <form method="POST" action="dashboard.php">
        <input type="hidden" name="action_join_group" value="1">
        
        <div class="form-group">
          <label class="input-label">Invite Code</label>
          <input class="input-field" 
                 type="text" 
                 name="join_code" 
                 required 
                 placeholder="ENTER INVITE CODE"
                 style="text-transform: uppercase; font-family: monospace; letter-spacing: 2px; text-align: center;">
        </div>

        <!-- Buttons using Global Styles -->
        <div class="modal-footer" style="display: flex; gap: 12px; justify-content: flex-end; margin-top: 20px;">
          <button type="button" class="btn-outline" onclick="toggleJoinModal(false)">
            Cancel
          </button>
          <button type="submit" class="btn-create">
            Join Group
          </button>
        </div>

      </form>
    </div>
  </div>
</div>
  
  <script>
  // ==================== + NEW GROUP BUTTON → OPENS CHOICE MODAL ====================
  const openChoiceBtn = document.getElementById('open-group-choice');
  const choiceModal = document.getElementById('group-choice-modal');

  if (openChoiceBtn && choiceModal) {
    openChoiceBtn.addEventListener('click', function() {
      choiceModal.style.display = 'flex';
    });
  }

  // Close Choice Modal
  function closeChoiceModal() {
    if (choiceModal) choiceModal.style.display = 'none';
  }

  // When clicking outside the choice modal
  if (choiceModal) {
    choiceModal.addEventListener('click', function(e) {
      if (e.target === choiceModal) {
        closeChoiceModal();
      }
    });
  }

  // ==================== CREATE GROUP ====================
  function chooseCreateGroup() {
    closeChoiceModal();
    const createModal = document.getElementById('modal-create');
    if (createModal) createModal.style.display = 'flex';
  }

  // ==================== JOIN GROUP ====================
  function chooseJoinGroup() {
    closeChoiceModal();
    const joinModal = document.getElementById('joinGroupModal');
    if (joinModal) joinModal.style.display = 'flex';
  }

  function toggleJoinModal(show) {
    const modal = document.getElementById('joinGroupModal');
    if (modal) modal.style.display = show ? 'flex' : 'none';
  }

  // Close Join Modal when clicking outside
  const joinModal = document.getElementById('joinGroupModal');
  if (joinModal) {
    joinModal.addEventListener('click', function(e) {
      if (e.target === joinModal) {
        toggleJoinModal(false);
      }
    });
  }

  function closeChoiceModal() {
  document.getElementById('group-choice-modal').style.display = 'none';
}
  // Close Create Modal when clicking outside (keep your existing behavior)
  const createModal = document.getElementById('modal-create');
  if (createModal) {
    createModal.addEventListener('click', function(e) {
      if (e.target === createModal) {
        createModal.style.display = 'none';
      }
    });
  }

  const modalCreate = document.getElementById('modal-create');
  const modalCreateCloseBtn = modalCreate ? modalCreate.querySelector('.modal-close') : null;

  // Close when clicking the ✕ button
  if (modalCreateCloseBtn) {
    modalCreateCloseBtn.addEventListener('click', function() {
      modalCreate.style.display = 'none';
    });
  }

  // Close when clicking outside the modal
  if (modalCreate) {
    modalCreate.addEventListener('click', function(e) {
      if (e.target === modalCreate) {
        modalCreate.style.display = 'none';
      }
    });
  }

  // ==================== INVITE CODE TOGGLE (for create privacy select) ====================
  function toggleInviteCode() {
    const privacySel = document.getElementById('cg-privacy');
    const container = document.getElementById('invite-code-container');
    const codeInput = document.getElementById('cg-invitecode');
    if (!privacySel || !container) return;

    if (privacySel.value === 'private') {
      container.style.display = 'block';
      // Generate a preview code client-side (server will authoritative generate on submit)
      if (codeInput && !codeInput.value) {
        const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        let code = '';
        for (let i = 0; i < 6; i++) code += chars.charAt(Math.floor(Math.random() * chars.length));
        codeInput.value = code;
      }
    } else {
      container.style.display = 'none';
      if (codeInput) codeInput.value = '';
    }
  }

  // Initialize invite visibility on load if needed
  document.addEventListener('DOMContentLoaded', function() {
    const privacySel = document.getElementById('cg-privacy');
    if (privacySel) {
      // run once
      setTimeout(toggleInviteCode, 0);
      privacySel.addEventListener('change', toggleInviteCode);
    }
  });
</script>
  <script src="../js/notifications.js"></script>
</body>
</html>