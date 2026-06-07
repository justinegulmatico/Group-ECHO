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
        <div class="topbar-right"></div>
      </header>

      <div class="page-content">

        <?php if (isset($_GET['success'])): ?>
          <div style="background:#E8F5EE; border:1px solid #a7f3d0; color:#166534; padding:12px 16px; border-radius:10px; margin-bottom:16px; font-size:14px;">
            <?= htmlspecialchars($_GET['success']) ?>
          </div>
        <?php endif; ?>
        <?php if (isset($_GET['error'])): ?>
          <div style="background:#fee2e2; border:1px solid #fca5a5; color:#991b1b; padding:12px 16px; border-radius:10px; margin-bottom:16px; font-size:14px;">
            <?= htmlspecialchars($_GET['error']) ?>
          </div>
        <?php endif; ?>

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

        <?php if (isset($_GET['payment_success'])): 
          $ps_amount   = isset($_GET['amount']) ? (float)$_GET['amount'] : 0;
          $ps_cycle    = isset($_GET['cycle']) ? (int)$_GET['cycle'] : 0;
          $ps_group    = isset($_GET['group']) ? htmlspecialchars($_GET['group']) : 'Group';
        ?>
          <!-- Visible "Record Payment" style confirmation (what users see in group details) -->
          <div class="payment-record-success" style="background:#E8F5EE; border:1px solid #a7f3d0; color:#166534; padding:14px 18px; border-radius:12px; margin-bottom:20px; font-size:14px; display:flex; align-items:flex-start; gap:12px;">
            <span style="font-size:20px; line-height:1; margin-top:1px;">✅</span>
            <div style="flex:1;">
              <strong>Payment recorded.</strong>
              ₱<?= number_format($ps_amount, 0) ?> for Cycle <?= $ps_cycle ?> in "<?= $ps_group ?>" has been deducted from your wallet.
              <a href="my_groups.php" style="margin-left:8px; color:#166534; font-weight:600; text-decoration:underline;">View in My Groups →</a>
            </div>
          </div>
        <?php endif; ?>

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
            <?php if (!empty($pending_payments)): ?>
                <!-- High-Impact Pending Payments & Cycles Tracker -->
                <div class="pending-payments-stack">
                    <?php foreach ($pending_payments as $pp): ?>
                        <div class="payment-alert-card">
                            <!-- Left: Group Info + Icon -->
                            <div class="pac-left">
                                <div class="pac-icon">
                                    <!-- Vibrant orange money / alert pouch icon -->
                                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M12 2C9.5 2 7.5 3.8 7.5 6v1.2H6c-1.1 0-2 .9-2 2v9.6c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V9.2c0-1.1-.9-2-2-2h-1.5V6c0-2.2-2-4-4.5-4z" fill="#E15225"/>
                                        <path d="M9 11.5h6M9 14.5h4" stroke="#fff" stroke-width="1.6" stroke-linecap="round"/>
                                    </svg>
                                </div>
                                <div class="pac-group-meta">
                                    <div class="pac-group-title"><?= htmlspecialchars($pp['group_name']); ?></div>
                                    <div class="pac-meta-tag">Cycle <?= (int)$pp['cycle_number']; ?> of <?= (int)$pp['cycle_length']; ?> • Contribution: ₱<?= number_format($pp['contribution'], 0); ?></div>
                                </div>
                            </div>

                            <!-- Middle: Receiver Spotlight (cream pill) -->
                            <div class="pac-receiver">
                                <div class="pac-receiver-label">Receiver</div>
                                <div class="pac-receiver-pill"><?= htmlspecialchars($pp['receiver_name']); ?></div>
                            </div>

                            <!-- Middle-Right: Status + thin progress -->
                            <div class="pac-status">
                                <div class="pac-progress-label"><?= (int)$pp['paid_count']; ?>/<?= (int)$pp['total_members']; ?> Members Paid</div>
                                <div class="pac-progress-track">
                                    <?php
                                        $pct = $pp['total_members'] > 0 ? min(100, max(0, round(($pp['paid_count'] / $pp['total_members']) * 100))) : 0;
                                    ?>
                                    <div class="pac-progress-fill" style="width: <?= $pct; ?>%;"></div>
                                </div>
                            </div>

                            <!-- Far Right: Direct Pay Action -->
                            <div class="pac-action">
                                <form method="POST" action="dashboard.php" style="margin:0;">
                                    <input type="hidden" name="action_pay_dashboard" value="1">
                                    <input type="hidden" name="group_id" value="<?= (int)$pp['group_id']; ?>">
                                    <input type="hidden" name="cycle_number" value="<?= (int)$pp['cycle_number']; ?>">
                                    <input type="hidden" name="amount" value="<?= (float)$pp['contribution']; ?>">
                                    <input type="hidden" name="member_id" value="<?= (int)$pp['member_id']; ?>">
                                    <button type="submit" class="pay-now-btn">
                                        Pay ₱<?= number_format($pp['contribution'], 0); ?> Now
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <!-- Healthy / Paid-Up State -->
                <div class="all-caught-up-card">
                    <div class="acu-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <circle cx="12" cy="12" r="10" fill="#E8F5EE"/>
                            <path d="M7 12.5l3 3 6-6" stroke="#2D7A45" stroke-width="2.25" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                    <div class="acu-content">
                        <div class="acu-title">All caught up! No pending contributions for this week's rotation cycles.</div>
                        <a href="my_groups.php" class="acu-link">Go to My Groups to track detailed individual charts →</a>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <?php if (!empty($newly_created_invite_code)): ?>
          <!-- Improved post-create join code sharing (high-visibility, copyable) -->
          <div class="invite-share-card">
            <div class="invite-header">
              <span class="invite-icon">🔑</span>
              <div>
                <strong>Group "<?= htmlspecialchars($newly_created_group_name) ?>" created!</strong>
                <div style="font-size:12.5px; color:#6B6560; margin-top:1px;">Share this invite code with others so they can join:</div>
              </div>
            </div>

            <div class="invite-code-row">
              <div class="invite-code-box" id="invite-code-box" onclick="copyInviteCode()" title="Click to copy">
                <?= htmlspecialchars($newly_created_invite_code) ?>
              </div>
              <button type="button" class="invite-copy-btn" onclick="copyInviteCode(event)">Copy Code</button>
            </div>

            <div class="invite-hint">Code is case-insensitive • 6 characters • Valid for this private group only</div>
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

<!-- Join Group Modal (improved join code UX) -->
<div class="modal-overlay" id="joinGroupModal" style="display:none;">
  <div class="modal">
    
    <!-- Header -->
    <div class="modal-header">
      <span class="modal-title">Join with Invite Code</span>
      <button class="modal-close" onclick="toggleJoinModal(false)">✕</button>
    </div>

    <div class="modal-body">
      <form method="POST" action="dashboard.php">
        <input type="hidden" name="action_join_group" value="1">
        
        <div class="join-instructions">
          Enter the 6-character private invite code shared by the group creator.<br>
          Codes are case-insensitive.
        </div>

        <div class="code-input-wrap">
          <!-- Key icon -->
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M21 2l-2 2m-7.61 7.61a5.5 5.5 0 1 1-7.778 7.778 5.5 5.5 0 0 1 7.777-7.777z"/>
            <path d="M15.5 7.5l3 3L22 7"/>
          </svg>
          <input type="text" 
                 name="join_code" 
                 required 
                 placeholder="ABC123"
                 maxlength="6"
                 autocomplete="off"
                 id="join-code-input">
        </div>

        <div class="modal-footer" style="display: flex; gap: 12px; justify-content: flex-end; margin-top: 20px; border-top: 1px solid #E4DDD4; padding-top: 16px;">
          <button type="button" class="btn-outline" onclick="toggleJoinModal(false)">
            Cancel
          </button>
          <button type="submit" class="btn-create" style="background:#E15225; color:#fff; border:none; padding:11px 22px; border-radius:10px; font-weight:700;">
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

    // Attach live formatter to the improved join code input (safe, after DOM ready)
    const joinCodeInput = document.getElementById('join-code-input');
    if (joinCodeInput) {
      joinCodeInput.addEventListener('input', function() {
        let cleaned = this.value.toUpperCase().replace(/[^A-Z0-9]/g, '');
        if (cleaned.length > 6) cleaned = cleaned.slice(0, 6);
        this.value = cleaned;
      });
      // Also clean on paste
      joinCodeInput.addEventListener('paste', function() {
        setTimeout(() => {
          let cleaned = this.value.toUpperCase().replace(/[^A-Z0-9]/g, '').slice(0, 6);
          this.value = cleaned;
        }, 0);
      });
    }

    // After showing the "Record Payment" success banner from dashboard quick-pay,
    // clean the URL so refreshing the page doesn't re-display the banner.
    if (window.location.search.includes('payment_success')) {
      const cleanUrl = window.location.pathname;
      window.history.replaceState({}, document.title, cleanUrl);
    }
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

  // ==================== IMPROVED JOIN CODE: copy after private group create ====================
  function copyInviteCode(e) {
    if (e) e.preventDefault();
    const box = document.getElementById('invite-code-box');
    if (!box) return;

    const code = box.textContent.trim();

    if (navigator.clipboard && navigator.clipboard.writeText) {
      navigator.clipboard.writeText(code).then(() => {
        const originalText = box.textContent;
        box.textContent = 'COPIED!';
        setTimeout(() => { box.textContent = originalText; }, 1400);
      }).catch(() => {
        // fallback
        fallbackCopy(code, box);
      });
    } else {
      fallbackCopy(code, box);
    }
  }

  function fallbackCopy(text, boxEl) {
    const ta = document.createElement('textarea');
    ta.value = text;
    ta.style.position = 'fixed';
    ta.style.opacity = '0';
    document.body.appendChild(ta);
    ta.select();
    try { document.execCommand('copy'); } catch(_) {}
    document.body.removeChild(ta);

    const original = boxEl.textContent;
    boxEl.textContent = 'COPIED!';
    setTimeout(() => { boxEl.textContent = original; }, 1400);
  }

  // ==================== JOIN CODE INPUT FORMATTER (auto uppercase + clean) ====================
  function formatJoinCode(input) {
    // Keep only A-Z 0-9, force uppercase, max 6 chars
    let cleaned = input.value.toUpperCase().replace(/[^A-Z0-9]/g, '');
    if (cleaned.length > 6) cleaned = cleaned.slice(0, 6);
    input.value = cleaned;
  }
</script>
</body>
</html>