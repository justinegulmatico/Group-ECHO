<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>TrustFund — Group Details</title>
  
  <link rel="stylesheet" href="../../assets/css/global.css?v=<?= filemtime(__DIR__ . '/../../assets/css/global.css') ?>" />
  <link rel="stylesheet" href="../../assets/css/group-details.css?v=<?= filemtime(__DIR__ . '/../../assets/css/group-details.css') ?>" />
  <style>
    .wallet-balance-box {
      background: #F9F6F1;
      border: 1.5px solid #E4DDD4;
      border-radius: 10px;
      padding: 12px 14px;
      margin-bottom: 14px;
    }
    .wb-label {
      font-size: 11px;
      font-weight: 600;
      color: #6B6560;
      text-transform: uppercase;
      letter-spacing: 0.04em;
    }
    .wb-amount {
      font-size: 20px;
      font-weight: 700;
      color: #1A1A1A;
      line-height: 1.1;
      margin-top: 2px;
    }
    .wb-warning {
      font-size: 11.5px;
      color: #C0392B;
      margin-top: 4px;
    }
  </style>
</head>
<body>
  <div class="app-layout">

    <?php include "components/sidebar-view.php"; ?>

    <div class="main-content">

      <header class="topbar">
        <div class="topbar-left">
          <span class="topbar-title">Group Details</span>
        </div>
        <div class="topbar-right"></div>
      </header>

      <div class="page-content" id="page-content">

        <?php if (isset($_GET['success'])): ?>
          <div style="background:#E8F5EE; border:1px solid #a7f3d0; color:#166534; padding:12px 16px; border-radius:10px; margin-bottom:16px; font-size:14px;">
            <?= htmlspecialchars($_GET['success']) ?>
          </div>
        <?php endif; ?>

        <?php if (isset($_GET['error'])): ?>
          <div style="background:#FDE8E2; border:1px solid #FCA5A5; color:#C0392B; padding:12px 16px; border-radius:10px; margin-bottom:16px; font-size:14px;">
            <?= htmlspecialchars($_GET['error']) ?>
          </div>
        <?php endif; ?>

        <div class="group-hero">
          <div class="hero-left">
            <div class="hero-name"><?= htmlspecialchars($current_group['group_name']); ?></div>
            <div class="hero-desc"><?= htmlspecialchars($current_group['description'] ?: 'No description configured.'); ?></div>
            
            <div class="hero-meta-row">
              <div class="meta-item">
                <span class="meta-label">Contribution</span>
                <span class="meta-value">₱<?= number_format($current_group['contribution_amount'], 0); ?></span>
              </div>
              <div class="meta-item">
                <span class="meta-label">Total Slots</span>
                <span class="meta-value"><?= $slots_filled; ?>/<?= $current_group['cycle_length']; ?></span>
              </div>
              <div class="meta-item">
                <span class="meta-label">Collected</span>
                <span class="meta-value">₱<?= number_format($total_collected, 0); ?></span>
              </div>
              <div class="meta-item">
                <span class="meta-label">Frequency</span>
                <span class="meta-value" style="text-transform: capitalize;"><?= htmlspecialchars($current_group['frequency']); ?></span>
              </div>
              <div class="meta-item">
                <span class="meta-label">Status</span>
                <span class="meta-value"><?= ucfirst($current_group['status'] ?? ($current_group['is_active'] == 1 ? 'active' : 'closed')); ?></span>
              </div>
              <?php if ($current_group['privacy'] === 'private' && !empty($current_group['invite_code'])): ?>
              <div class="meta-item">
                <span class="meta-label">Invite Code</span>
                <span class="meta-value invite-code" id="invite-code"><?= htmlspecialchars($current_group['invite_code']); ?></span>
              </div>
              <?php endif; ?>
            </div>
          </div>

          <div class="hero-right">
            <?php if (!empty($is_member)): ?>
              <button class="hero-btn primary" onclick="openRecordPaymentModal()">+ Payment</button>
            <?php endif; ?>

            <?php if ($current_group['status'] === 'pending' && (int)$current_group['created_by'] === $current_user_id): ?>
              <?php if ($slots_filled >= 3): ?>
                <form method="POST" style="display:inline;" id="activateForm">
                  <input type="hidden" name="activate_paluwagan" value="1">
                  <button type="button" class="hero-btn secondary" 
                          onclick="openActivationModal(<?= (int)$slots_filled ?>)">
                    Activate (<?= (int)$slots_filled ?> members)
                  </button>
                </form>
              <?php else: ?>
                <span class="hero-btn secondary" style="opacity:0.5; cursor:not-allowed; font-size:12px;" title="Need at least 3 members total to activate this group">Activate (need 3+ members)</span>
              <?php endif; ?>
            <?php endif; ?>
          </div>
        </div>

        <?php if (isset($is_member) && !$is_member && $current_group['privacy'] === 'public'): ?>
          <div style="background:#fef3c7; border:1px solid #f59e0b; padding:14px 18px; border-radius:10px; margin: 12px 0; display:flex; justify-content:space-between; align-items:center;">
            <div>
              <strong>This is a public group.</strong> Join now to participate in the rotation.
            </div>
            <form method="POST" action="my_groups.php">
              <input type="hidden" name="action_join_public" value="1">
              <input type="hidden" name="group_id" value="<?= (int)$group_id ?>">
              <button type="submit" class="btn-create" style="margin:0; padding: 8px 16px;">Join this Group</button>
            </form>
          </div>
        <?php endif; ?>

        <div class="detail-tabs">
          <a href="group_details.php?id=<?= $group_id; ?>&tab=overview" class="detail-tab <?= $active_tab === 'overview' ? 'active' : '' ?>">Overview</a>
          <a href="group_details.php?id=<?= $group_id; ?>&tab=schedule" class="detail-tab <?= $active_tab === 'schedule' ? 'active' : '' ?>">Schedule</a>
          <a href="group_details.php?id=<?= $group_id; ?>&tab=payments" class="detail-tab <?= $active_tab === 'payments' ? 'active' : '' ?>">Payments</a>
          <a href="group_details.php?id=<?= $group_id; ?>&tab=history" class="detail-tab <?= $active_tab === 'history' ? 'active' : '' ?>">History</a>
        </div>

        <?php if ($active_tab === 'overview'): ?>
          <div class="tab-panel active">
            <div class="overview-stats">
              <div class="overview-stat-card">
                <div class="ov-label">Total Collected</div>
                <div class="ov-value">₱<?= number_format($total_collected, 0); ?></div>
              </div>
              <div class="overview-stat-card">
                <div class="ov-label">Total Paid Out</div>
                <div class="ov-value green">₱<?= number_format($total_paid_out, 0); ?></div>
              </div>
              <div class="overview-stat-card">
                <div class="ov-label">In Pool</div>
                <div class="ov-value green">₱<?= number_format($in_pool, 0); ?></div>
              </div>
              <div class="overview-stat-card">
                <div class="ov-label">My Balance Due</div>
                <div class="ov-value red">₱<?= number_format($my_balance_due, 0); ?></div>
              </div>
            </div>

            <?php if (!empty($active_cycle) && !empty($active_cycle['receiver'])): 
              $ac = $active_cycle;
              $rec = $ac['receiver'];
              $rec_name = htmlspecialchars($rec['first_name'] . ' ' . $rec['last_name']);
              $rec_initials = strtoupper(substr($rec['first_name'],0,1) . substr($rec['last_name'] ?: '', 0, 1));
              $ac_cycle_num = (int)$ac['cycle_number'];
              $ac_collected = (float)($ac['collected'] ?? 0);
              $ac_full = (float)($ac['full_pot'] ?? ($current_group['contribution_amount'] * count($group_members)));
              $ac_paid_cnt = (int)($ac['paid_count'] ?? 0);
              $ac_expected = max(1, count($group_members));
              $ac_progress = min(100, (int)round( ($ac_paid_cnt / $ac_expected) * 100 ));
              $ac_status = ($ac_collected + 0.5 >= $ac_full) ? 'Ready to Collect' : 'Awaiting Pool Completion';
              $ac_status_class = ($ac_collected + 0.5 >= $ac_full) ? 'ready' : 'awaiting';

              // Has the logged-in user paid for this active cycle?
              $user_paid_active = false;
              if (!empty($my_member_id)) {
                // We compute this in controller and expose as $user_paid_for_active_cycle
                $user_paid_active = !empty($user_paid_for_active_cycle);
              }
            ?>
            <!-- Active Cycle Highlight Card -->
            <div class="active-cycle-card">
              <!-- Left: Receiver Spotlight -->
              <div class="ach-receiver-block">
                <div class="ach-section-label">Current Receiver</div>
                <div class="ach-receiver-main">
                  <div class="ach-avatar" style="background:#FDE8E2; color:#E15225; border:2px solid #FAD2C0;"><?= $rec_initials ?></div>
                  <div class="ach-receiver-info">
                    <div class="ach-receiver-name"><?= $rec_name ?></div>
                    <div class="ach-receiver-pos">Position #<?= $ac_cycle_num ?></div>
                  </div>
                </div>
                <span class="ach-status-badge <?= $ac_status_class ?>"><?= $ac_status ?></span>
              </div>

              <!-- Center: Progress -->
              <div class="ach-progress-block">
                <div class="ach-section-label">Collection Progress</div>
                <div class="ach-progress-container">
                  <div class="ach-progress-track">
                    <div class="ach-progress-fill" style="width: <?= $ac_progress ?>%;"></div>
                  </div>
                  <div class="ach-progress-meta">
                    <span class="ach-count"><?= $ac_paid_cnt ?> / <?= $ac_expected ?> Paid</span>
                    <span class="ach-pct-pill"><?= $ac_progress ?>%</span>
                  </div>
                </div>
              </div>

              <!-- Right: Pot + Action -->
              <div class="ach-pot-block">
                <div class="ach-pot-mini">
                  <div class="ach-pot-label">Pot to be Distributed</div>
                  <div class="ach-pot-value">₱<?= number_format($ac_full, 0) ?></div>
                </div>

                <?php if (!empty($is_member)): ?>
                  <?php 
                    $can_contribute = ($current_group['status'] === 'active') && !$user_paid_active;
                  ?>
                  <?php if ($user_paid_active): ?>
                    <div class="ach-contrib-done">
                      <span class="check">✓</span> Your Contribution Submitted
                    </div>
                  <?php elseif ($can_contribute): ?>
                    <button type="button" class="ach-contrib-btn" onclick="openRecordPaymentModal()">
                      Contribute ₱<?= number_format($current_group['contribution_amount'], 0) ?>
                    </button>
                  <?php else: ?>
                    <?php if ($current_group['status'] !== 'active'): ?>
                      <div class="ach-contrib-done" style="background:#f3f4f6; color:#666; font-size:13px;">
                        Waiting for activation
                      </div>
                    <?php endif; ?>
                  <?php endif; ?>
                <?php else: ?>
                  <!-- Non-members see the join banner instead of contribute action -->
                <?php endif; ?>
              </div>
            </div>
            <?php endif; ?>

            <!-- Members & Positions (kept in Overview) -->
            <div style="margin-top: 24px;">
              <div style="font-size:14px; font-weight:700; color:#1F1C19; margin-bottom:10px;">Members &amp; Positions</div>
              <div class="table-wrap">
                <table>
                  <thead>
                    <tr>
                      <th>Position</th>
                      <th>Member</th>
                      <th style="text-align:right;">Status</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php 
                      $active_receiver_pos = (!empty($active_cycle) && !empty($active_cycle['cycle_number'])) ? (int)$active_cycle['cycle_number'] : 0;
                      foreach ($group_members as $mem): 
                        $is_current_user = (int)$mem['user_id'] === $current_user_id;
                        $is_active_receiver = ((int)$mem['position'] === $active_receiver_pos);
                        $row_style = $is_active_receiver ? 'background:#FDF6F0; border-left: 3px solid #E15225;' : '';
                    ?>
                      <tr style="<?= $row_style ?>">
                        <td><strong>#<?= $mem['position'] ?></strong></td>
                        <td>
                          <?= htmlspecialchars($mem['first_name'] . ' ' . $mem['last_name']) ?>
                          <?php if ($is_current_user): ?>
                            <span style="color:#E15225; font-size:11px; font-weight:600;"> (You)</span>
                          <?php endif; ?>
                          <?php if ($is_active_receiver): ?>
                            <span style="margin-left:6px; font-size:10px; background:#FAD2C0; color:#C7461D; padding:1px 6px; border-radius:999px; font-weight:700;">RECEIVING</span>
                          <?php endif; ?>
                        </td>
                        <td style="text-align:right;"><span class="status-released">Active</span></td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
              <p style="font-size:12px; color:#8A837A; margin-top:10px;">Lower position number = receives the pot earlier in the rotation.</p>
            </div>
          </div>
        <?php endif; ?>

        <?php if ($active_tab === 'schedule'): ?>
          <div class="tab-panel active">
            <div class="schedule-card">
              <div class="schedule-title">Payout Schedule (Cycle Rotation)</div>
              <table class="schedule-table">
                <thead>
                  <tr>
                    <th>Cycle</th>
                    <th>Payout Date</th>
                    <th>Receiver (Position)</th>
                    <th>Collected</th>
                    <th>Pot</th>
                    <th>Status</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($cycles as $cy): 
                    $rec = $cy['receiver'];
                    $recName = $rec ? htmlspecialchars($rec['first_name'] . ' ' . $rec['last_name']) : 'Position ' . $cy['cycle_number'];
                    $isMine = $rec && (int)$rec['user_id'] === $current_user_id;
                    $pot = (float)$current_group['contribution_amount'] * max(1, count($group_members));
                  ?>
                    <tr>
                      <td><strong>#<?= $cy['cycle_number'] ?></strong></td>
                      <td><?= htmlspecialchars($cy['start_date'] ?? 'TBD') ?></td>
                      <td>
                        <?= $recName ?>
                        <?php if ($isMine): ?><span class="you-tag"> (You)</span><?php endif; ?>
                      </td>
                      <td>₱<?= number_format($cy['collected'], 0) ?></td>
                      <td>
                        <?php if ($isMine): ?>
                          ₱<?= number_format($pot - $current_group['contribution_amount'], 0) ?>
                          <div class="pot-sub">(your contribution auto-deducted)</div>
                        <?php else: ?>
                          ₱<?= number_format($pot, 0) ?>
                        <?php endif; ?>
                      </td>
                      <td>
                        <?php if ($cy['payout_status'] === 'released'): ?>
                          <span class="status-released">Released</span>
                        <?php else: ?>
                          <span class="status-pending">Pending</span>
                          <?php if ($isMine): ?>
                            <div class="status-indicator">Receiving this cycle <span class="green-text">(no payment required)</span></div>
                          <?php endif; ?>
                        <?php endif; ?>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
            <p class="table-microcopy">Each cycle pays out to the assigned position in rotation order.</p>
          </div>
        <?php endif; ?>

        <?php if ($active_tab === 'payments'): ?>
          <div class="tab-panel active">
            <div class="table-wrap">
              <table>
                <thead>
                  <tr>
                    <th>Cycle</th>
                    <th>Receiver</th>
                    <th>Your Payment</th>
                    <th>Cycle Total</th>
                    <th>Status</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($cycles as $cy): 
                    $rec = $cy['receiver'];
                    $recName = $rec ? htmlspecialchars($rec['first_name']) : 'Pos ' . $cy['cycle_number'];
                    // Check if current user paid this cycle
                    $myPaid = 0;
                    // Simple: we can query but for student code we show collected
                  ?>
                    <tr>
                      <td><strong>#<?= $cy['cycle_number'] ?></strong></td>
                      <td><?= $recName ?></td>
                      <td>
                        <?php if ($cy['collected'] > 0): ?>
                          ₱<?= number_format($current_group['contribution_amount'], 0) ?>
                        <?php else: ?>
                          —
                        <?php endif; ?>
                      </td>
                      <td>₱<?= number_format($cy['collected'], 0) ?> / ₱<?= number_format($current_group['contribution_amount'] * count($group_members), 0) ?></td>
                      <td>
                        <?php if ($cy['payout_status'] === 'released'): ?>
                          <span class="status-released">Payout Done</span>
                        <?php else: ?>
                          <span class="status-pending">Collecting</span>
                        <?php endif; ?>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
            <p style="margin-top:12px; font-size:13px; color:#555;">Use the buttons in the Overview tab to record your hulugan (contribution) for each cycle.</p>
          </div>
        <?php endif; ?>

        <?php if ($active_tab === 'history'): ?>
          <div class="tab-panel active">
            <div class="history-ledger">
              <h4>Group Activity History</h4>
              <?php if (!empty($group_history)): ?>
                <div class="ledger-timeline">
                  <?php foreach ($group_history as $h): 
                    $date = !empty($h['created_at']) ? date('Y-m-d H:i', strtotime($h['created_at'])) : 'N/A';
                    $actor = !empty($h['actor_first']) ? htmlspecialchars($h['actor_first'] . ' ' . $h['actor_last']) : '';
                    $target = !empty($h['target_first']) ? htmlspecialchars($h['target_first'] . ' ' . $h['target_last']) : '';
                    $evt = $h['event_type'] ?? 'event';
                    $desc = !empty($h['description']) ? htmlspecialchars($h['description']) : ucfirst(str_replace('_', ' ', $evt));
                    $amt = isset($h['amount']) && $h['amount'] > 0 ? '₱' . number_format($h['amount'], 0) : '';
                    $cycle = !empty($h['cycle_number']) ? 'Cycle #' . $h['cycle_number'] : '';
                  ?>
                    <div class="ledger-entry">
                      <div class="entry-date"><?= $date ?></div>
                      <div class="entry-body">
                        <div class="entry-main">
                          <?php if ($evt === 'payment'): ?>
                            <?= $target ? $target : 'Member' ?> paid <strong><?= $amt ?></strong>
                            <span class="entry-status completed">Completed</span>
                          <?php elseif ($evt === 'payout'): ?>
                            Payout <?= $amt ?> released to <?= $target ?: 'receiver' ?>
                            <span class="entry-status completed">Completed</span>
                          <?php elseif ($evt === 'member_joined'): ?>
                            <?= $target ?: 'New member' ?> joined the group
                            <span class="entry-status completed">Joined</span>
                          <?php elseif ($evt === 'group_created'): ?>
                            Group created
                            <span class="entry-status completed">System</span>
                          <?php elseif ($evt === 'group_activated' || $evt === 'group_closed'): ?>
                            <?= ucfirst(str_replace('_', ' ', $evt)) ?>
                            <span class="entry-status completed">System</span>
                          <?php else: ?>
                            <?= $desc ?>
                          <?php endif; ?>
                        </div>
                        <div class="entry-details">
                          <?= $cycle ? $cycle . ' • ' : '' ?><?= $desc ?>
                          <?php if ($actor): ?> • by <?= $actor ?><?php endif; ?>
                        </div>
                      </div>
                    </div>
                  <?php endforeach; ?>
                </div>
              <?php else: ?>
                <div class="history-ledger" style="padding: 20px; text-align: center; color: #8A837A;">
                  No history recorded yet for this group.<br>
                  Payments, payouts, member joins, and group status changes will appear here.
                </div>
              <?php endif; ?>
            </div>
          </div>
        <?php endif; ?>

      </div></div></div>

  <?php if ($current_group['status'] === 'pending' && (int)$current_group['created_by'] === $current_user_id): ?>
  <!-- Activation Confirmation Modal -->
  <div class="modal-backdrop" id="activationModal">
    <div class="modal" style="max-width: 460px;">
      <div class="modal-header">
        <span class="modal-title">Activate Group</span>
        <button class="modal-close-btn" onclick="closeActivationModal()">✕</button>
      </div>
      <div class="modal-body" style="padding-top: 8px;">
        <p style="margin: 0; font-size: 15px; line-height: 1.55; color: #3a3632;">
          Activate now with the current <strong id="activationMemberCount">3</strong> members?
          This will lock the roster (no more joins allowed) and start the rotation.
          The pool size will be based on current members only.
        </p>
      </div>
      <div style="display: flex; justify-content: space-between; align-items: center; gap: 12px; border-top: 1px solid #E4DDD4; padding-top: 16px; margin-top: 20px;">
        <button type="button" onclick="closeActivationModal()" 
                style="background: #F5F0E8; color: #555; border: 1px solid #E4DDD4; padding: 10px 18px; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer;"
                onmouseover="this.style.background='#EDE6DC'" onmouseout="this.style.background='#F5F0E8'">
          Cancel
        </button>
        <button type="button" onclick="confirmAndActivate()" 
                style="background: #E15225; color: #fff; border: none; padding: 10px 18px; border-radius: 8px; font-size: 14px; font-weight: 700; cursor: pointer;"
                onmouseover="this.style.background='#c7461d'" onmouseout="this.style.background='#E15225'">
          Yes, Activate Now
        </button>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <script>
    // Activation modal functions (available when the activate button is shown)
    function openActivationModal(memberCount) {
      const modal = document.getElementById('activationModal');
      const countEl = document.getElementById('activationMemberCount');
      if (countEl) countEl.textContent = memberCount || '';
      if (modal) modal.classList.add('open');
    }

    function closeActivationModal() {
      const modal = document.getElementById('activationModal');
      if (modal) modal.classList.remove('open');
    }

    function confirmAndActivate() {
      const form = document.getElementById('activateForm');
      if (form) {
        // Submit the original form to trigger the activate_paluwagan POST
        form.submit();
      }
      closeActivationModal();
    }

    // Close activation modal on backdrop click
    (function() {
      const actModal = document.getElementById('activationModal');
      if (actModal) {
        actModal.addEventListener('click', function(e) {
          if (e.target === this) closeActivationModal();
        });
      }
    })();
  </script>

  <?php if (!empty($is_member)): ?>
  <div class="modal-backdrop" id="paymentRecordModal">
    <div class="modal">
      <div class="modal-header">
        <span class="modal-title">Record Payment</span>
        <button class="modal-close-btn" onclick="closeRecordPaymentModal()">✕</button>
      </div>
      <div class="modal-body">
        <form action="../../back-end/process/process_contribution.php" method="POST" id="recordPaymentForm">
          <input type="hidden" name="group_id" value="<?= $group_id; ?>">
          <input type="hidden" name="member_id" value="<?= $my_member_id; ?>">
          
          <div class="form-group">
            <label class="input-label">Member</label>
            <input class="input-field" type="text" value="<?= htmlspecialchars($full_name); ?>" readonly style="background-color: #f3f4f6; cursor: not-allowed;" />
          </div>

          <!-- Wallet Balance (replaces Payment Method) -->
          <div class="wallet-balance-box">
            <div class="wb-label">Paying from your Wallet Balance</div>
            <div class="wb-amount">₱<?= number_format($wallet_balance, 2); ?></div>
            <?php if ($wallet_balance <= 0): ?>
              <div class="wb-warning">No balance yet. Deposit funds from the Dashboard first.</div>
            <?php endif; ?>
          </div>

          <div class="form-row">
            <div class="form-group" style="flex: 1;">
              <label class="input-label">Amount (₱)</label>
              <input class="input-field" type="number" name="amount" id="pay-amount" value="<?= number_format($current_group['contribution_amount'], 2, '.', ''); ?>" step="0.01" min="1" required />
            </div>
            <div class="form-group" style="flex: 1;">
              <label class="input-label">Cycle #</label>
              <select name="cycle_number" class="input-field">
                <?php 
                  // Compute current user's position for receiver filtering
                  $current_user_pos = 0;
                  foreach ($group_members as $m) {
                      if ((int)$m['user_id'] === $current_user_id) {
                          $current_user_pos = (int)$m['position'];
                          break;
                      }
                  }

                  foreach ($cycles as $cyc): 
                    $cyc_num = (int)$cyc['cycle_number'];
                    $is_my_payout_cycle = ($current_user_pos > 0 && $current_user_pos === $cyc_num);
                    $is_released = (($cyc['payout_status'] ?? 'pending') === 'released');

                    // Skip cycles where user is the receiver or already paid out
                    if ($is_my_payout_cycle || $is_released) {
                        continue;
                    }
                ?>
                  <option value="<?= $cyc_num ?>">Cycle #<?= $cyc_num ?></option>
                <?php endforeach; ?>
              </select>
              <small style="color:#666; font-size:11px;">Cycles where you are the receiver are hidden (you receive instead of contributing).</small>
            </div>
          </div>

          <button class="btn-primary" type="submit" style="margin-top: 16px; width: 100%;" id="record-pay-btn">Record Payment from Wallet</button>
        </form>

        <div id="insufficient-warning" style="display:none; margin-top:8px; font-size:12.5px; color:#C0392B; background:#FDE8E2; padding:8px 10px; border-radius:6px;">
          Insufficient wallet balance for this amount.
        </div>

        <p style="font-size:12px; color:#666; margin-top:10px;">This will deduct the amount directly from your TrustFund wallet balance.</p>
      </div>
    </div>
  </div>

  <script>
      function openRecordPaymentModal() {
          document.getElementById('paymentRecordModal').classList.add('open');
      }
      function closeRecordPaymentModal() {
          document.getElementById('paymentRecordModal').classList.remove('open');
      }
      // Backdrop close registration layout engine
      document.getElementById('paymentRecordModal').addEventListener('click', function(e) {
          if (e.target === this) this.classList.remove('open');
      });

      // Live insufficient balance warning (client-side convenience)
      (function() {
        const amountInput = document.getElementById('pay-amount');
        const warning = document.getElementById('insufficient-warning');
        const submitBtn = document.getElementById('record-pay-btn');
        const currentBalance = <?= (float)$wallet_balance; ?>;

        function checkBalance() {
          if (!amountInput || !warning) return;
          const val = parseFloat(amountInput.value) || 0;
          if (val > currentBalance && currentBalance >= 0) {
            warning.style.display = 'block';
            if (submitBtn) submitBtn.style.opacity = '0.6';
          } else {
            warning.style.display = 'none';
            if (submitBtn) submitBtn.style.opacity = '1';
          }
        }

        if (amountInput) {
          amountInput.addEventListener('input', checkBalance);
          amountInput.addEventListener('change', checkBalance);
          // initial
          setTimeout(checkBalance, 50);
        }
      })();
  </script>
  <?php endif; ?>
</body>
</html>