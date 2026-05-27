<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>TrustFund — Group Details</title>
  
  <link rel="stylesheet" href="../../assets/css/global.css" />
  <link rel="stylesheet" href="../../assets/css/group-details.css" />
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
          <div class="hero-action-btns">
            <button class="hero-btn" onclick="openRecordPaymentModal()">+Payment</button>
            <button class="hero-btn">+Payout</button>
          </div>
          <div class="hero-name"><?= htmlspecialchars($current_group['group_name']); ?></div>
          <div class="hero-desc"><?= htmlspecialchars($current_group['description'] ?: 'No description configured.'); ?></div>
          
          <div class="hero-stats-row">
            <div class="hero-stat">
              <div class="hero-stat-label">Contribution</div>
              <div class="hero-stat-value">₱<?= number_format($current_group['contribution_amount'], 0); ?></div>
            </div>
            <div class="hero-stat">
              <div class="hero-stat-label">Total Slots</div>
              <div class="hero-stat-value"><?= $slots_filled; ?>/<?= $current_group['cycle_length']; ?></div>
            </div>
            <div class="hero-stat">
              <div class="hero-stat-label">Collected</div>
              <div class="hero-stat-value">₱<?= number_format($total_collected, 0); ?></div>
            </div>
            <div class="hero-stat">
              <div class="hero-stat-label">Frequency</div>
              <div class="hero-stat-value" style="text-transform: capitalize;"><?= htmlspecialchars($current_group['frequency']); ?></div>
            </div>
            <div class="hero-stat">
              <div class="hero-stat-label">Status</div>
              <div class="hero-stat-value"><?= $current_group['is_active'] == 1 ? 'Active' : 'Closed'; ?></div>
            </div>
          </div>
          
          <div class="hero-invite-row">
            <div class="invite-code-pill">
              Invite Code: <span class="invite-code-val" id="invite-code"><?= htmlspecialchars($current_group['invite_code']); ?></span>
            </div>
          </div>
        </div>

        

        <div class="detail-tabs">
          <a href="group_details.php?id=<?= $group_id; ?>&tab=overview" class="detail-tab <?= $active_tab === 'overview' ? 'active' : '' ?>" style="text-decoration: none;">Overview</a>
          <span class="detail-tab" style="opacity: 0.4; cursor: not-allowed;">Members</span>
          <span class="detail-tab" style="opacity: 0.4; cursor: not-allowed;">Schedule</span>
          <a href="group_details.php?id=<?= $group_id; ?>&tab=payments" class="detail-tab <?= $active_tab === 'payments' ? 'active' : '' ?>" style="text-decoration: none;">Payments</a>
          <span class="detail-tab" style="opacity: 0.4; cursor: not-allowed;">Chat</span>
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
            
            <div class="upcoming-section">
              <div class="upcoming-title">Upcoming Payouts</div>
              <div class="upcoming-row is-mine">
                <div class="upcoming-row-left">
                  <div class="upcoming-slot-num">1</div>
                  <div>
                    <div class="upcoming-name"><?= htmlspecialchars($full_name); ?> <span style="font-size: 12px; opacity: 0.6;">(You)</span></div>
                  </div>
                </div>
                <div class="upcoming-amount">₱<?= number_format($current_group['contribution_amount'] * $current_group['cycle_length'], 0); ?></div>
              </div>
            </div>
          </div>
        <?php endif; ?>

        <?php if ($active_tab === 'payments'): ?>
          <div class="tab-panel active">
            <div class="table-wrap">
              <table>
                <thead>
                  <tr>
                    <th>Member</th>
                    <th>Slot</th>
                    <th>Total Paid</th>
                    <th>Owed</th>
                    <th>Balance</th>
                    <th>Payout</th>
                  </tr>
                </thead>
                <tbody>
                  <tr>
                    <td>
                      <div style="display: flex; align-items: center; gap: 12px;">
                        <div style="width: 36px; height: 36px; border-radius: 50%; background-color: #ffedd5; color: #c2410c; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 12px;">
                          <?= substr($full_name, 0, 2); ?>
                        </div>
                        <div style="font-weight: 600; color: #111827;"><?= htmlspecialchars($full_name); ?></div>
                      </div>
                    </td>
                    <td>1</td>
                    <td style="color: #16a34a; font-weight: 600;">₱<?= number_format($total_collected, 0); ?></td>
                    <td>₱<?= number_format($current_group['contribution_amount'], 0); ?></td>
                    <td>
                      <?php if($my_balance_due > 0): ?>
                        <span class="text-red" style="font-weight: 600;">₱<?= number_format($my_balance_due, 0); ?> owed</span>
                      <?php else: ?>
                        <span style="color: #16a34a; font-weight: 600;">Settled</span>
                      <?php endif; ?>
                    </td>
                    <td>
                      <span class="badge badge-pending">Pending</span>
                    </td>
                  </tr>
                </tbody>
              </table>
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
        <form action="process/process_record_payment.php" method="POST">
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
              <input class="input-field" type="number" name="cycle_number" value="1" required />
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