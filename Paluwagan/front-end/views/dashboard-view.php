<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>TrustFund — Dashboard</title>
  <link rel="stylesheet" href="../../assets/css/global.css?v=<?= filemtime(__DIR__ . '/../../assets/css/global.css') ?>" />
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

        <div class="stat-cards stat-cards-5">
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

          <!-- Wallet Balance Card -->
          <div class="stat-card wallet-card">
            <div class="stat-card-label" style="color:#9E9790; font-size:11px; letter-spacing:0.02em;">WALLET BALANCE</div>
            <div class="wallet-balance-amount">₱<?= number_format($wallet_balance, 2); ?></div>
            <div class="wallet-actions">
              <button type="button" class="wallet-btn wallet-btn-deposit" id="btn-deposit">+ Deposit</button>
              <button type="button" class="wallet-btn wallet-btn-withdraw" id="btn-withdraw">↑ Withdraw</button>
            </div>
          </div>
        </div>

        <div class="section-header">
          <span class="section-title">Active Groups</span>
          <button class="new-group-btn" id="open-group-choice">+ New Group</button>
        </div>

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
            <!-- Polished singular dashboard alert panel (replaces loose notice bars) -->
            <div class="dashboard-alert">
              <div class="dashboard-alert-icon">💰</div>
              <div class="dashboard-alert-content">
                <div class="dashboard-alert-title">You're in <?= $active_groups_count; ?> active savings loop<?= $active_groups_count > 1 ? 's' : ''; ?>.</div>
                <?php if (isset($next_payout_info) && strpos($next_payout_info, 'Join or create') === false): ?>
                  <div class="dashboard-alert-sub"><strong>Next in rotation:</strong> <?= htmlspecialchars($next_payout_info) ?></div>
                <?php endif; ?>
                <a href="my_groups.php" class="dashboard-alert-link">Track detailed progress and payouts in My Groups →</a>
              </div>
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

  <!-- ==================== DEPOSIT FUNDS MODAL ==================== -->
  <div class="modal-overlay" id="deposit-modal" style="display:none;">
    <div class="tx-modal">
      <div class="tx-modal-header">
        <span class="tx-modal-title">Deposit Funds</span>
        <button type="button" class="tx-modal-close" id="close-deposit">&times;</button>
      </div>

      <form method="POST" action="dashboard.php" enctype="multipart/form-data" id="deposit-form">
        <input type="hidden" name="action_wallet_deposit" value="1">

        <div class="tx-form-group">
          <label class="tx-input-label">Amount</label>
          <div class="tx-amount-wrap">
            <span class="tx-currency">₱</span>
            <input type="number" name="amount" class="tx-amount-input" placeholder="0.00" step="0.01" min="1" required>
          </div>
        </div>

        <div class="tx-form-group">
          <label class="tx-input-label">Payment Method</label>
          <select name="payment_method" class="tx-input-field" required>
            <option value="GCash">GCash</option>
            <option value="Maya">Maya</option>
            <option value="Bank Transfer">Bank Transfer</option>
            <option value="Cash">Cash (in-person)</option>
            <option value="Other">Other</option>
          </select>
        </div>

        <div class="tx-form-group">
          <label class="tx-input-label">Upload Receipt / Screenshot <span style="font-weight:400;color:#9E9790;">(required for verification)</span></label>
          <input type="file" name="receipt" class="tx-file-input" accept="image/*,.pdf" required>
          <div class="tx-hint">JPG, PNG or PDF up to 5MB</div>
        </div>

        <div class="tx-notice">
          Transactions are subject to admin review. Funds will reflect upon approval.
        </div>

        <div class="tx-modal-actions">
          <button type="button" class="tx-btn tx-btn-cancel" id="cancel-deposit">Cancel</button>
          <button type="submit" class="tx-btn tx-btn-submit">Submit Request</button>
        </div>
      </form>
    </div>
  </div>

  <!-- ==================== WITHDRAW FUNDS MODAL ==================== -->
  <div class="modal-overlay" id="withdraw-modal" style="display:none;">
    <div class="tx-modal">
      <div class="tx-modal-header">
        <span class="tx-modal-title">Withdraw Funds</span>
        <button type="button" class="tx-modal-close" id="close-withdraw">&times;</button>
      </div>

      <form method="POST" action="dashboard.php" id="withdraw-form">
        <input type="hidden" name="action_wallet_withdraw" value="1">

        <div class="tx-form-group">
          <label class="tx-input-label">Amount</label>
          <div class="tx-amount-wrap">
            <span class="tx-currency">₱</span>
            <input type="number" name="amount" class="tx-amount-input" placeholder="0.00" step="0.01" min="1" required>
          </div>
          <div class="tx-hint">Available: ₱<?= number_format($wallet_balance, 2); ?></div>
        </div>

        <div class="tx-form-group">
          <label class="tx-input-label">Payment Method / Destination</label>
          <select name="payment_method" class="tx-input-field" required>
            <option value="GCash">GCash</option>
            <option value="Maya">Maya</option>
            <option value="Bank Transfer">Bank Transfer</option>
            <option value="Other">Other</option>
          </select>
        </div>

        <div class="tx-form-group">
          <label class="tx-input-label">Account Details</label>
          <textarea name="account_details" class="tx-input-field tx-textarea" rows="2" placeholder="GCash number / Bank account name &amp; number / E-wallet ID" required></textarea>
        </div>

        <div class="tx-notice">
          Transactions are subject to admin review. Funds will reflect upon approval.
        </div>

        <div class="tx-modal-actions">
          <button type="button" class="tx-btn tx-btn-cancel" id="cancel-withdraw">Cancel</button>
          <button type="submit" class="tx-btn tx-btn-submit">Submit Request</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Deposit Pending Approval Modal (shown after regular user submits a deposit) -->
  <div class="modal-overlay" id="deposit-pending-modal" style="display:none;">
    <div class="tx-modal" style="max-width: 420px; text-align: center;">
      <div class="tx-modal-header" style="border-bottom: none; justify-content: center; padding-bottom: 8px;">
        <span class="tx-modal-title" style="font-size: 20px;">Deposit Request Submitted</span>
      </div>

      <div style="padding: 10px 0 20px;">
        <div style="font-size: 42px; margin-bottom: 12px;">⏳</div>
        
        <?php if (!empty($show_deposit_pending_modal) && $show_deposit_pending_modal && $pending_deposit_amount > 0): ?>
          <p style="font-size: 15px; font-weight: 600; color: #1A1A1A; margin-bottom: 8px;">
            Your deposit of <strong>₱<?= number_format($pending_deposit_amount, 2); ?></strong> has been submitted successfully.
          </p>
        <?php else: ?>
          <p style="font-size: 15px; font-weight: 600; color: #1A1A1A; margin-bottom: 8px;">
            Your deposit request has been submitted successfully.
          </p>
        <?php endif; ?>
        
        <p style="font-size: 14px; color: #6B6560; line-height: 1.5;">
          Please wait for an admin to review and approve your request.<br>
          The funds will be added to your wallet balance once approved.<br>
          You can monitor the status in the Transaction Approvals section (if admin) or by refreshing your Dashboard.
        </p>
      </div>

      <div class="tx-modal-actions" style="border-top: 1px solid #E4DDD4; padding-top: 16px; justify-content: center;">
        <button type="button" class="tx-btn tx-btn-submit" onclick="closeDepositPendingModal()" style="flex: none; min-width: 140px;">
          OK, I Understand
        </button>
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

    // ==================== WALLET DEPOSIT / WITHDRAW MODALS ====================
    const btnDeposit = document.getElementById('btn-deposit');
    const btnWithdraw = document.getElementById('btn-withdraw');
    const depositModal = document.getElementById('deposit-modal');
    const withdrawModal = document.getElementById('withdraw-modal');

    function showModal(modal) {
      if (modal) modal.style.display = 'flex';
    }
    function hideModal(modal) {
      if (modal) modal.style.display = 'none';
    }

    if (btnDeposit && depositModal) {
      btnDeposit.addEventListener('click', () => showModal(depositModal));
    }
    if (btnWithdraw && withdrawModal) {
      btnWithdraw.addEventListener('click', () => showModal(withdrawModal));
    }

    // Close buttons
    const closeDep = document.getElementById('close-deposit');
    const cancelDep = document.getElementById('cancel-deposit');
    if (closeDep) closeDep.addEventListener('click', () => hideModal(depositModal));
    if (cancelDep) cancelDep.addEventListener('click', () => hideModal(depositModal));

    const closeW = document.getElementById('close-withdraw');
    const cancelW = document.getElementById('cancel-withdraw');
    if (closeW) closeW.addEventListener('click', () => hideModal(withdrawModal));
    if (cancelW) cancelW.addEventListener('click', () => hideModal(withdrawModal));

    // Click outside to close
    if (depositModal) {
      depositModal.addEventListener('click', function(e) {
        if (e.target === depositModal) hideModal(depositModal);
      });
    }
    if (withdrawModal) {
      withdrawModal.addEventListener('click', function(e) {
        if (e.target === withdrawModal) hideModal(withdrawModal);
      });
    }

    // Optional: reset form on close (simple)
    if (depositModal) {
      const depForm = depositModal.querySelector('form');
      if (depForm) {
        depositModal.addEventListener('click', function(e){
          if (e.target === depositModal) depForm.reset();
        });
      }
    }

    // Show "wait for admin approval" modal after a regular user deposit
    <?php if (!empty($show_deposit_pending_modal) && $show_deposit_pending_modal): ?>
    (function() {
      const pendingModal = document.getElementById('deposit-pending-modal');
      if (pendingModal) {
        pendingModal.style.display = 'flex';
      }
    })();
    <?php endif; ?>
  });

  // Close handler for the deposit pending approval modal
  function closeDepositPendingModal() {
    const m = document.getElementById('deposit-pending-modal');
    if (m) m.style.display = 'none';
  }

  // Also allow closing by clicking the overlay background
  const pendingOverlay = document.getElementById('deposit-pending-modal');
  if (pendingOverlay) {
    pendingOverlay.addEventListener('click', function(e) {
      if (e.target === pendingOverlay) {
        pendingOverlay.style.display = 'none';
      }
    });
  }
</script>
  <script src="../js/notifications.js"></script>
</body>
</html>