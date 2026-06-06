<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>TrustFund — Transaction Approvals | Admin</title>
  <link rel="stylesheet" href="../../../assets/css/global.css?v=<?= filemtime(__DIR__ . '/../../../assets/css/global.css') ?>" />
  <link rel="stylesheet" href="../../../assets/css/admin-panel.css?v=<?= filemtime(__DIR__ . '/../../../assets/css/admin-panel.css') ?>" />
  <style>
    /* Small scoped polish for this page only if needed */
    .tx-ledger { margin-bottom: 32px; }
  </style>
</head>
<body>
  <div class="app-layout">
    <?php include __DIR__ . '/../components/sidebar-view.php'; ?>

    <div class="main-content">
      <header class="topbar">
        <div class="topbar-left">
          <span class="topbar-title">Admin → Transaction Approvals</span>
        </div>
        <div class="topbar-right">
          <a href="index.php" class="btn-outline" style="padding:6px 14px; font-size:13px; text-decoration:none;">← Back to Admin Dashboard</a>
        </div>
      </header>

      <div class="page-content">

        <?php if (isset($_GET['success'])): ?>
          <div style="background:#E8F5EE; border:1px solid #a7f3d0; color:#166534; padding:12px 18px; border-radius:10px; margin-bottom:22px;">
            <?= htmlspecialchars($_GET['success']) ?>
          </div>
        <?php endif; ?>

        <!-- Header -->
        <div class="admin-hero" style="margin-bottom:18px; padding:22px 26px;">
          <div>
            <div class="admin-hero-title">Transaction Approvals</div>
            <div class="admin-hero-sub">Review and approve or decline <strong>user-submitted</strong> deposit and withdrawal requests. <span style="color:#E15225;">Admin direct credits (including to your own account) are applied immediately (no validation required).</span></div>
          </div>
        </div>

        <!-- Admin Direct Deposit / Credit Form -->
        <div class="table-wrap" style="margin-bottom: 28px; padding: 20px 24px;">
          <div style="margin-bottom: 12px;">
            <div class="section-title" style="font-size:15px; margin-bottom: 4px;">Admin Direct Wallet Credit</div>
            <div style="font-size:12.5px; color:#6B6560;">Credit any user's wallet balance (including your own admin account) instantly. Admin deposits do <strong>not</strong> require review and reflect immediately.</div>
          </div>

          <form method="POST" action="transactions.php" style="display: grid; grid-template-columns: 2fr 1fr 1.4fr 1.2fr auto; gap: 12px; align-items: flex-end;">
            <input type="hidden" name="action_admin_direct_deposit" value="1">

            <div>
              <label class="input-label" style="font-size:11px; margin-bottom:4px;">User</label>
              <select name="target_user_id" class="input-field" required style="font-size:14px;">
                <option value="">-- Select User (admins can credit themselves) --</option>
                <?php foreach ($all_users as $u): 
                  $label = htmlspecialchars($u['first_name'] . ' ' . $u['last_name'] . ' (@' . $u['username'] . ')');
                  if (!empty($u['role']) && $u['role'] === 'admin') {
                    $label .= ' [Admin]';
                  }
                  $is_self = ((int)$u['user_id'] === (int)($_SESSION['user_id'] ?? 0));
                  if ($is_self) $label .= ' (You)';
                ?>
                  <option value="<?= (int)$u['user_id'] ?>"><?= $label ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <div>
              <label class="input-label" style="font-size:11px; margin-bottom:4px;">Amount (₱)</label>
              <input type="number" name="amount" class="input-field" step="0.01" min="1" placeholder="500.00" required>
            </div>

            <div>
              <label class="input-label" style="font-size:11px; margin-bottom:4px;">Source / Method</label>
              <select name="payment_method" class="input-field" style="font-size:14px;">
                <option value="Admin Direct Credit">Admin Direct Credit</option>
                <option value="Bank Transfer">Bank Transfer (Manual)</option>
                <option value="Cash">Cash Top-up</option>
                <option value="System Adjustment">System Adjustment</option>
                <option value="Other">Other</option>
              </select>
            </div>

            <div>
              <label class="input-label" style="font-size:11px; margin-bottom:4px;">Note (optional)</label>
              <input type="text" name="note" class="input-field" placeholder="e.g. Refund / Bonus / Cash received" style="font-size:14px;">
            </div>

            <div>
              <button type="submit" class="btn-primary" style="padding: 11px 20px; font-size:14px; white-space:nowrap;">
                Credit Wallet Now
              </button>
            </div>
          </form>
        </div>

        <!-- Pending Requests -->
        <div class="section-title" style="font-size:16px; margin-bottom:10px;">Pending Requests (User-Submitted)</div>

        <?php if ($pending_requests && mysqli_num_rows($pending_requests) > 0): ?>
          <div class="table-wrap tx-ledger">
            <table>
              <thead>
                <tr>
                  <th style="width: 22%;">User</th>
                  <th style="width: 10%;">Type</th>
                  <th style="width: 13%;">Amount</th>
                  <th style="width: 22%;">Reference / Receipt</th>
                  <th style="width: 16%;">Date &amp; Time</th>
                  <th style="width: 17%; text-align:right;">Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php while ($r = mysqli_fetch_assoc($pending_requests)): 
                  $full_name = htmlspecialchars(trim($r['first_name'] . ' ' . $r['last_name']));
                  $initials = strtoupper(substr($r['first_name'],0,1) . ( !empty($r['last_name']) ? substr($r['last_name'],0,1) : '' ));
                  $is_deposit = $r['type'] === 'deposit';
                ?>
                  <tr>
                    <td>
                      <div class="user-cell">
                        <div class="user-cell-avatar" style="background:#FDE8E2; color:#E15225;"><?= htmlspecialchars($initials) ?></div>
                        <div>
                          <div class="user-cell-name"><?= $full_name ?></div>
                          <div class="user-cell-sub">@<?= htmlspecialchars($r['username']) ?></div>
                        </div>
                      </div>
                    </td>

                    <td>
                      <?php if ($is_deposit): ?>
                        <span class="badge badge-active" style="background:#E8F5EE; color:#2D7A45;">DEPOSIT</span>
                      <?php else: ?>
                        <span class="badge badge-pending" style="background:#FEF3C7; color:#9F6B00;">WITHDRAW</span>
                      <?php endif; ?>
                    </td>

                    <td>
                      <div style="font-weight:700; font-size:15px; color:#1A1A1A;">
                        ₱<?= number_format($r['amount'], 2) ?>
                      </div>
                    </td>

                    <td>
                      <?php if ($is_deposit && !empty($r['attachment'])): ?>
                        <a href="../../../<?= htmlspecialchars($r['attachment']) ?>" target="_blank" 
                           class="tx-attachment-badge">
                          📎 View Attachment
                        </a>
                        <div style="font-size:10px; color:#9E9790; margin-top:2px;"><?= htmlspecialchars(pathinfo($r['attachment'], PATHINFO_EXTENSION)) ?></div>
                      <?php elseif (!$is_deposit && !empty($r['account_details'])): ?>
                        <div style="font-size:12.5px; line-height:1.3; color:#444; max-width:210px; word-break:break-word;">
                          <?= nl2br(htmlspecialchars($r['account_details'])) ?>
                        </div>
                      <?php else: ?>
                        <span style="color:#9E9790; font-size:12px;">—</span>
                      <?php endif; ?>

                      <?php if (!empty($r['payment_method'])): ?>
                        <div style="margin-top:3px; font-size:11px;">
                          <span style="background:#F5F0E8; color:#6B6560; padding:1px 7px; border-radius:999px; font-size:10px;"><?= htmlspecialchars($r['payment_method']) ?></span>
                        </div>
                      <?php endif; ?>
                    </td>

                    <td style="color:#6B6560; font-size:12.5px; line-height:1.35;">
                      <?= date('M d, Y', strtotime($r['created_at'])) ?><br>
                      <span style="font-size:11px; color:#9E9790;"><?= date('g:i A', strtotime($r['created_at'])) ?></span>
                    </td>

                    <td style="text-align:right;">
                      <form method="POST" action="transactions.php" style="display:inline-flex; gap:6px;" data-request-id="<?= (int)$r['request_id'] ?>">
                        <input type="hidden" name="request_id" value="<?= (int)$r['request_id'] ?>">
                        <button type="button" 
                                class="tx-action-btn tx-approve"
                                data-user="<?= htmlspecialchars($full_name) ?>"
                                data-amount="₱<?= number_format($r['amount'], 2) ?>"
                                data-type="<?= $is_deposit ? 'DEPOSIT' : 'WITHDRAW' ?>"
                                onclick="openApproveConfirmModal(this)">
                          Approve
                        </button>
                        <button type="submit" name="action_decline" value="1"
                                class="tx-action-btn tx-decline"
                                onclick="return confirm('Are you sure you want to DECLINE this request?')">
                          Decline
                        </button>
                      </form>
                    </td>
                  </tr>
                <?php endwhile; ?>
              </tbody>
            </table>
          </div>
        <?php else: ?>
          <div class="table-wrap" style="padding:32px 24px; text-align:center; color:#6B6560;">
            <div style="font-weight:600; color:#333; margin-bottom:4px;">No pending requests</div>
            <div style="font-size:13px;">New deposit and withdrawal requests will appear here for review.</div>
          </div>
        <?php endif; ?>

        <!-- Recently Processed -->
        <div style="margin-top: 34px;">
          <div class="section-title" style="font-size:15px; margin-bottom:10px; color:#6B6560;">Recently Processed</div>
          <?php if ($recent_processed && mysqli_num_rows($recent_processed) > 0): ?>
            <div class="table-wrap">
              <table>
                <thead>
                  <tr>
                    <th>User</th>
                    <th>Type</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>Date Reviewed</th>
                  </tr>
                </thead>
                <tbody>
                  <?php while ($r = mysqli_fetch_assoc($recent_processed)): 
                    $full_name = htmlspecialchars(trim($r['first_name'] . ' ' . $r['last_name']));
                    $initials = strtoupper(substr($r['first_name'],0,1) . (!empty($r['last_name']) ? substr($r['last_name'],0,1) : ''));
                    $status = $r['status'];
                  ?>
                    <tr>
                      <td>
                        <div class="user-cell">
                          <div class="user-cell-avatar"><?= htmlspecialchars($initials) ?></div>
                          <div>
                            <div class="user-cell-name"><?= $full_name ?></div>
                            <div class="user-cell-sub">@<?= htmlspecialchars($r['username']) ?></div>
                          </div>
                        </div>
                      </td>
                      <td>
                        <span style="font-size:12px; font-weight:600; color:<?= $r['type']==='deposit' ? '#2D7A45' : '#9F6B00' ?>;">
                          <?= strtoupper($r['type']) ?>
                        </span>
                      </td>
                      <td style="font-weight:600;">₱<?= number_format($r['amount'], 2) ?></td>
                      <td>
                        <?php if ($status === 'approved'): ?>
                          <span class="badge badge-active">Approved</span>
                        <?php else: ?>
                          <span class="badge badge-suspended" style="background:#FDE8E2;color:#C0392B;">Declined</span>
                        <?php endif; ?>
                      </td>
                      <td style="color:#6B6560; font-size:12.5px;">
                        <?= !empty($r['reviewed_at']) ? date('M d, Y g:i A', strtotime($r['reviewed_at'])) : '—' ?>
                      </td>
                    </tr>
                  <?php endwhile; ?>
                </tbody>
              </table>
            </div>
          <?php else: ?>
            <div style="font-size:13px; color:#9E9790; padding:10px 4px;">No recent activity.</div>
          <?php endif; ?>
        </div>

        <div style="margin-top:36px; font-size:12.5px; color:#8a7f74;">
          Note: Wallet balances are computed from approved transactions. <strong>User-submitted</strong> deposits &amp; withdrawals require review. <span style="color:#E15225;">Admin direct credits</span> (via the form above) are applied immediately with no validation step.
        </div>

      </div>
    </div>
  </div>

  <!-- Approve Confirmation Modal - Polished Design -->
  <div id="approve-confirm-modal" 
       style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.65); z-index: 999999; align-items: center; justify-content: center; backdrop-filter: blur(2px);">
    <div style="background: #fff; width: 92%; max-width: 420px; border-radius: 16px; box-shadow: 0 20px 50px rgba(0,0,0,0.3); overflow: hidden; border: 1.5px solid #E4DDD4;">
      
      <!-- Header -->
      <div style="display: flex; align-items: center; justify-content: space-between; padding: 18px 22px; background: #F9F6F1; border-bottom: 1px solid #E4DDD4;">
        <div style="display: flex; align-items: center; gap: 10px;">
          <div style="width: 32px; height: 32px; background: #DCFCE7; color: #166534; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 18px; font-weight: bold;">✓</div>
          <span style="font-size: 17px; font-weight: 700; color: #1A1A1A;">Confirm Approval</span>
        </div>
        <button onclick="closeApproveConfirmModal()" 
                style="background: none; border: none; font-size: 24px; color: #8A837A; cursor: pointer; line-height: 1; padding: 0 4px;">&times;</button>
      </div>

      <!-- Body -->
      <div style="padding: 22px 24px 10px;">
        <p style="font-size: 15.5px; font-weight: 600; color: #1A1A1A; margin: 0 0 14px 0; text-align: center;">
          Are you sure you want to <span style="color: #2D7A45; font-weight: 700;">APPROVE</span> this request?
        </p>

        <!-- Request Details Card -->
        <div id="approve-confirm-details" 
             style="background: #fff; border: 1.5px solid #E4DDD4; border-radius: 10px; padding: 14px 16px; margin-bottom: 16px; font-size: 14px; line-height: 1.45;">
          <!-- Dynamically filled by JS -->
        </div>

        <div style="background: #FEF3C7; border: 1px solid #FCD34D; border-radius: 8px; padding: 10px 12px; font-size: 13px; color: #92400E; margin-bottom: 8px;">
          ⚠️ This action is <strong>permanent</strong>. The user's wallet balance will be updated immediately.
        </div>
      </div>

      <!-- Footer Actions -->
      <div style="display: flex; gap: 10px; padding: 16px 24px; background: #F9F6F1; border-top: 1px solid #E4DDD4;">
        <button type="button" onclick="closeApproveConfirmModal()" 
                style="flex: 1; padding: 11px 0; background: #fff; color: #444; border: 1.5px solid #E4DDD4; border-radius: 10px; font-size: 14px; font-weight: 600; cursor: pointer;">
          Cancel
        </button>
        <button type="button" id="confirm-approve-btn" onclick="submitApprove()" 
                style="flex: 1.4; padding: 11px 0; background: #2D7A45; color: #fff; border: none; border-radius: 10px; font-size: 14px; font-weight: 700; cursor: pointer; box-shadow: 0 2px 6px rgba(45, 122, 69, 0.3);">
          Yes, Approve Request
        </button>
      </div>

    </div>
  </div>

  <div class="toast-container" id="toast-container"></div>

  <script>
    let currentApproveForm = null;

    function openApproveConfirmModal(btn) {
      const form = btn.closest('form');
      if (!form) return;

      currentApproveForm = form;

      const user = btn.getAttribute('data-user') || 'Unknown User';
      const amount = btn.getAttribute('data-amount') || 'N/A';
      const type = btn.getAttribute('data-type') || 'REQUEST';

      const detailsDiv = document.getElementById('approve-confirm-details');
      detailsDiv.innerHTML = `
        <div style="display:flex; justify-content:space-between; margin-bottom:6px;">
          <span style="color:#6B6560; font-size:12.5px;">User</span>
          <span style="font-weight:600; color:#1A1A1A;">${user}</span>
        </div>
        <div style="display:flex; justify-content:space-between; margin-bottom:6px;">
          <span style="color:#6B6560; font-size:12.5px;">Type</span>
          <span style="font-weight:700; color:#2D7A45;">${type}</span>
        </div>
        <div style="display:flex; justify-content:space-between;">
          <span style="color:#6B6560; font-size:12.5px;">Amount</span>
          <span style="font-weight:700; color:#1A1A1A; font-size:15px;">${amount}</span>
        </div>
      `;

      const modal = document.getElementById('approve-confirm-modal');
      modal.style.display = 'flex';
    }

    function closeApproveConfirmModal() {
      const modal = document.getElementById('approve-confirm-modal');
      modal.style.display = 'none';
      currentApproveForm = null;
    }

    function submitApprove() {
      if (currentApproveForm) {
        // Create a hidden submit button for approve and click it
        const hiddenBtn = document.createElement('button');
        hiddenBtn.type = 'submit';
        hiddenBtn.name = 'action_approve';
        hiddenBtn.value = '1';
        hiddenBtn.style.display = 'none';
        currentApproveForm.appendChild(hiddenBtn);
        hiddenBtn.click();
        // The form will submit and the modal will close on page reload
      }
      closeApproveConfirmModal();
    }

    // Close modal when clicking outside the tx-modal box
    const approveModal = document.getElementById('approve-confirm-modal');
    if (approveModal) {
      approveModal.addEventListener('click', function(e) {
        if (e.target === approveModal) {
          closeApproveConfirmModal();
        }
      });
    }

    // Also support pressing Escape key to close the approve modal
    document.addEventListener('keydown', function(e) {
      if (e.key === 'Escape') {
        const modal = document.getElementById('approve-confirm-modal');
        if (modal && modal.style.display === 'flex') {
          closeApproveConfirmModal();
        }
      }
    });

    // Simple toast for success messages already shown via GET (kept minimal)
    // If you want client-side toast on future AJAX, hook here.
  </script>
</body>
</html>