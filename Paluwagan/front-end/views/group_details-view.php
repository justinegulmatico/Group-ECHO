<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>TrustFund — Group Details</title>
  
  <link rel="stylesheet" href="../../assets/css/global.css?v=<?= filemtime(__DIR__ . '/../../assets/css/global.css') ?>" />
  <link rel="stylesheet" href="../../assets/css/group-details.css?v=<?= filemtime(__DIR__ . '/../../assets/css/group-details.css') ?>" />
</head>
<body>
  <div class="app-layout">

    <?php include "components/sidebar-view.php"; ?>

    <div class="main-content">

      <header class="topbar">
        <div class="topbar-left">
          <span class="topbar-title">Group Details</span>
        </div>
        <div class="topbar-right">
          <button class="notif-btn" id="notif-btn" aria-label="Notifications">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
              <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
            </svg>
            <span class="notif-badge" id="notif-badge" style="display:none;">0</span>
          </button>
        </div>
      </header>

      <div class="page-content" id="page-content">

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
              <div class="meta-item">
                <span class="meta-label">Invite Code</span>
                <span class="meta-value invite-code" id="invite-code"><?= htmlspecialchars($current_group['invite_code']); ?></span>
              </div>
            </div>
          </div>

          <div class="hero-right">
            <?php if (!empty($is_member)): ?>
              <button class="hero-btn primary" onclick="openRecordPaymentModal()">+ Payment</button>
            <?php endif; ?>
            <button class="hero-btn secondary">+ Payout</button>

            <?php if ($current_group['status'] === 'pending' && (int)$current_group['created_by'] === $current_user_id): ?>
              <form method="POST" style="display:inline;">
                <button type="submit" name="activate_paluwagan" class="hero-btn secondary" 
                        onclick="return confirm('Activate only when all slots are full. This will freeze the roster and start cycle 1.');">
                  Activate
                </button>
              </form>
            <?php endif; ?>
          </div>
        </div>

        <?php if (isset($is_member) && !$is_member && $current_group['privacy'] === 'public'): ?>
          <div style="background:#fef3c7; border:1px solid #f59e0b; padding:14px 18px; border-radius:10px; margin: 12px 0; display:flex; justify-content:space-between; align-items:center;">
            <div>
              <strong>This is a public group.</strong> Join now to participate in the rotation.
            </div>
            <form method="POST" action="my_groups.php">
              <input type="hidden" name="action_join_group" value="1">
              <input type="hidden" name="invite_code" value="<?= htmlspecialchars($current_group['invite_code']) ?>">
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
                    <?php foreach ($group_members as $mem): 
                      $is_current_user = (int)$mem['user_id'] === $current_user_id;
                    ?>
                      <tr>
                        <td><strong>#<?= $mem['position'] ?></strong></td>
                        <td>
                          <?= htmlspecialchars($mem['first_name'] . ' ' . $mem['last_name']) ?>
                          <?php if ($is_current_user): ?>
                            <span style="color:#E15225; font-size:11px; font-weight:600;"> (You)</span>
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
              <h4>Payment Ledger - Current Cycle</h4>
              <div class="ledger-timeline">
                <div class="ledger-entry">
                  <div class="entry-date">2026-06-01</div>
                  <div class="entry-body">
                    <div class="entry-main">
                      Jose Rizal paid <strong>₱500</strong> 
                      <span class="entry-status completed">Completed</span>
                    </div>
                    <div class="entry-details">Cycle #1 • For Payout • Status: Paid</div>
                  </div>
                </div>
                <div class="ledger-entry">
                  <div class="entry-date">2026-05-25</div>
                  <div class="entry-body">
                    <div class="entry-main">
                      Maria Clara paid <strong>₱500</strong> 
                      <span class="entry-status completed">Completed</span>
                    </div>
                    <div class="entry-details">Cycle #1 • For Payout</div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        <?php endif; ?>

      </div></div></div><div class="modal-backdrop" id="paymentRecordModal">
    <div class="modal">
      <div class="modal-header">
        <span class="modal-title">Record Payment</span>
        <button class="modal-close-btn" onclick="closeRecordPaymentModal()">✕</button>
      </div>
      <div class="modal-body">
        <form action="../process/process_record_payment.php" method="POST">
          <input type="hidden" name="group_id" value="<?= $group_id; ?>">
          <input type="hidden" name="member_id" value="<?= $my_member_id; ?>">
          
          <div class="form-group">
            <label class="input-label">Member</label>
            <input class="input-field" type="text" value="<?= htmlspecialchars($full_name); ?>" readonly style="background-color: #f3f4f6; cursor: not-allowed;" />
          </div>

          <div class="form-row">
            <div class="form-group" style="flex: 1;">
              <label class="input-label">Amount (₱)</label>
              <input class="input-field" type="number" name="amount" value="<?= (int)$current_group['contribution_amount']; ?>" step="0.01" required />
            </div>
            <div class="form-group" style="flex: 1;">
              <label class="input-label">Cycle #</label>
              <select name="cycle_number" class="input-field">
                <?php foreach ($cycles as $cyc): ?>
                  <option value="<?= $cyc['cycle_number'] ?>">Cycle #<?= $cyc['cycle_number'] ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

          <div class="form-group">
            <label class="input-label">Payment Method</label>
            <select name="payment_method" class="input-field" style="appearance: auto;">
              <option value="cash">Cash</option>
              <option value="gcash">GCash</option>
              <option value="bank_transfer">Bank Transfer</option>
            </select>
          </div>

          <button class="btn-primary" type="submit" style="margin-top: 16px; width: 100%;">Record Payment</button>
        </form>
        <p style="font-size:12px; color:#666; margin-top:10px;">Tip: You can also use the "Pay" buttons directly in the cycle schedule above.</p>
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
  </script>
</body>
</html>