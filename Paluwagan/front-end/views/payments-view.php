<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>TrustFund — Payments</title>
  <link rel="stylesheet" href="../../assets/css/global.css?v=<?= filemtime(__DIR__ . '/../../assets/css/global.css') ?>" />
  <link rel="stylesheet" href="../../assets/css/payments.css" />
</head>
<body>
  <div class="app-layout">

    <?php include "components/sidebar-view.php"; ?>

    <div class="main-content">

      <header class="topbar">
        <div class="topbar-left">
          <span class="topbar-title">Payments</span>
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

        <div class="section-title">Payment History</div>

        <?php if ($payments_log_res && mysqli_num_rows($payments_log_res) > 0): ?>
          <div class="table-card">
            <table class="payments-table">
              <thead>
                <tr>
                  <th>Group</th>
                  <th>Cycle</th>
                  <th>Amount</th>
                  <th>Method</th>
                  <th>Date</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody>
                <?php while ($pay_row = mysqli_fetch_assoc($payments_log_res)): 
                    $is_payout = false; // payouts table separate; contributions here are inbound
                    $status_raw = strtolower($pay_row['status'] ?? 'pending');
                    $badge_class = (in_array($status_raw, ['paid','success','approved','released'])) ? 'badge-paid' : 'badge-pending';
                    $method = 'N/A'; // schema note: record handler accepts it; display defaults until extended
                    $dateStr = !empty($pay_row['paid_at']) ? date('M d, Y', strtotime($pay_row['paid_at'])) : (!empty($pay_row['due_date']) ? date('M d, Y', strtotime($pay_row['due_date'])) : date('M d, Y'));
                ?>
                  <tr>
                    <td class="cell-group"><?= htmlspecialchars($pay_row['group_name']); ?></td>
                    <td>#<?= isset($pay_row['cycle_number']) ? htmlspecialchars($pay_row['cycle_number']) : '1'; ?></td>
                    
                    <td class="cell-amount <?= $is_payout ? 'received' : ''; ?>">
                      ₱<?= number_format((float)$pay_row['amount'], 2); ?>
                    </td>
                    
                    <td class="cell-method" style="text-transform: capitalize; font-family: monospace;">
                      <?= htmlspecialchars($method); ?>
                    </td>
                    <td class="cell-date">
                      <?= $dateStr; ?>
                    </td>
                    <td>
                      <span class="badge <?= $badge_class; ?>" style="text-transform: capitalize;">
                        <?= htmlspecialchars($pay_row['status'] ?? 'pending'); ?>
                      </span>
                    </td>
                  </tr>
                <?php endwhile; ?>
              </tbody>
            </table>
          </div>
        <?php else: ?>
          <div class="empty-state">
            <div class="empty-state-title">No payment records</div>
            <div class="empty-state-desc">Join a group to start tracing payments.</div>
          </div>
        <?php endif; ?>

      </div></div></div><div class="notif-overlay" id="notif-overlay"></div>
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
  
  <script src="../../front-end/js/notifications.js"></script>
</body>
</html>