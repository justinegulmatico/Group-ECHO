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
        <div class="topbar-right"></div>
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
                    $is_payout = false; // payouts are separate table
                    $status_raw = strtolower($pay_row['status'] ?? 'pending');
                    $badge_class = (in_array($status_raw, ['paid','success','approved','released'])) ? 'badge-paid' : 'badge-pending';
                    $method = 'N/A'; // default display until extended
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

      </div></div></div>
</body>
</html>