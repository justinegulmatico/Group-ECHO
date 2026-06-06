<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>TrustFund — Payments</title>
  <link rel="stylesheet" href="../../assets/css/global.css" />
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

        <?php if (mysqli_num_rows($payments_log_res) > 0): ?>
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
                  <th style="text-align: center;">Action</th>
                </tr>
              </thead>
              <tbody>
                <?php while ($pay_row = mysqli_fetch_assoc($payments_log_res)): 
                    $is_payout = (isset($pay_row['tx_type']) && $pay_row['tx_type'] === 'payout');
                    $status_cleaned = strtolower(htmlspecialchars($pay_row['status']));
                    $badge_class = ($status_cleaned === 'paid' || $status_cleaned === 'success' || $status_cleaned === 'approved') ? 'badge-paid' : 'badge-pending';
                ?>
                  <tr>
                    <td class="cell-group"><?= htmlspecialchars($pay_row['group_name']); ?></td>
                    <td>#<?= isset($pay_row['cycle_number']) ? htmlspecialchars($pay_row['cycle_number']) : '1'; ?></td>
                    
                    <td class="cell-amount <?= $is_payout ? 'received' : ''; ?>">
                      ₱<?= number_format($pay_row['amount'], 2); ?>
                    </td>
                    
                    <td class="cell-method" style="text-transform: capitalize; font-family: monospace;">
                      <?= htmlspecialchars($pay_row['payment_method'] ?? 'System Ledger'); ?>
                    </td>
                    <td class="cell-date">
                      <?= isset($pay_row['due_date']) ? date('M d, Y', strtotime($pay_row['due_date'])) : (isset($pay_row['created_at']) ? date('M d, Y', strtotime($pay_row['created_at'])) : date('M d, Y')); ?>
                    </td>
                    <td>
                      <span class="badge <?= $badge_class; ?>" style="text-transform: capitalize;">
                        <?= htmlspecialchars($pay_row['status']); ?>
                      </span>
                    </td>
                    <td style="text-align: center;">
                      <?php if ($status_cleaned === 'pending' || $status_cleaned === 'late'): ?>
                        <button type="button" 
                                class="btn-create" 
                                style="padding: 6px 12px; font-size: 12px; border-radius: 6px; margin: 0; cursor: pointer;"
                                onclick="processQuickPayment(<?= $pay_row['contribution_id']; ?>)">
                          Pay Now
                        </button>
                      <?php else: ?>
                        <span style="font-size: 13px; color: #166534; font-weight: 500;">✓ Settled</span>
                      <?php endif; ?>
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
  
  <div class="toast-container" id="toast-container" style="position: fixed; top: 20px; right: 20px; z-index: 9999;"></div>
  
  <script>
  function processQuickPayment(contributionId) {
      if (!confirm("Are you sure you want to record this contribution payment?")) return;

      const formData = new FormData();
      formData.append('contribution_id', contributionId);
      formData.append('action_record_payment', '1');

      // Enhanced Fetch logic to handle and read standard errors or raw formatting issues
      fetch('../../back-end/php/payments.php', {
          method: 'POST',
          body: formData
      })
      .then(async response => {
          const textData = await response.text();
          try {
              return JSON.parse(textData);
          } catch(e) {
              // If the backend crashed or returned a 404 webpage layout instead of data, catch it here
              console.error("Raw response received from server:", textData);
              throw new Error("Server returned an invalid format. Check console logs.");
          }
      })
      .then(data => {
          if (data.success) {
              showToastNotification("Record Payment Successfully!", "success");
              setTimeout(() => {
                  window.location.reload();
              }, 1500);
          } else {
              showToastNotification(data.message || "Execution error.", "error");
          }
      })
      .catch(error => {
          console.error('Error Details:', error);
          showToastNotification(error.message || "Network connection failure.", "error");
      });
  }

  function showToastNotification(message, type) {
      const container = document.getElementById('toast-container');
      if (!container) return;

      const toast = document.createElement('div');
      toast.style.cssText = `
          background-color: ${type === 'success' ? '#166534' : '#b91c1c'};
          color: white;
          padding: 14px 24px;
          border-radius: 10px;
          margin-bottom: 10px;
          font-family: sans-serif;
          font-size: 14px;
          font-weight: 500;
          box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1);
          min-width: 250px;
          animation: slideIn 0.3s ease-out;
      `;
      toast.innerText = message;

      container.appendChild(toast);

      setTimeout(() => {
          toast.remove();
      }, 4000);
  }
  </script>
  <script src="../../front-end/js/notifications.js"></script>
</body>
</html>