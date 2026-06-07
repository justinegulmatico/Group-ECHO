<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>TrustFund — Verifications | Admin</title>
  <link rel="stylesheet" href="../../../assets/css/global.css?v=<?= filemtime(__DIR__ . '/../../../assets/css/global.css') ?>" />
  <link rel="stylesheet" href="../../../assets/css/admin-panel.css?v=<?= filemtime(__DIR__ . '/../../../assets/css/admin-panel.css') ?>" />
</head>
<body>
  <div class="app-layout">
    <?php include __DIR__ . '/../components/sidebar-view.php'; ?>

    <div class="main-content">
      <header class="topbar">
        <div class="topbar-left">
          <span class="topbar-title">Admin → Verifications</span>
        </div>
        <div class="topbar-right">
          <a href="index.php" class="btn-outline" style="padding: 6px 14px; font-size: 13px; text-decoration: none;">← Back to Dashboard</a>
        </div>
      </header>

      <div class="page-content">

        <?php if (isset($_GET['success'])): ?>
          <div style="background:#E8F5EE; border:1px solid #a7f3d0; color:#166534; padding:12px 18px; border-radius:10px; margin-bottom:20px;">
            <?= htmlspecialchars($_GET['success']) ?>
          </div>
        <?php endif; ?>

        <div class="admin-hero" style="margin-bottom: 20px;">
          <div>
            <div class="admin-hero-title">Pending Identity Verifications</div>
            <div class="admin-hero-sub">Review documents and approve or reject new members.</div>
          </div>
        </div>

        <div class="tf-action-bar" style="margin-bottom:16px;">
          <input type="text" id="search" class="tf-search-input" placeholder="Search name, username or email..." onkeyup="filterTable()">
        </div>

        <?php if (mysqli_num_rows($verifications) > 0): ?>
          <div class="table-wrap">
            <table id="verif-table">
              <thead>
                <tr>
                  <th>User</th>
                  <th>Email / Phone</th>
                  <th>Submitted</th>
                  <th style="text-align:right;">Action</th>
                </tr>
              </thead>
              <tbody>
                <?php while ($v = mysqli_fetch_assoc($verifications)): ?>
                  <tr class="verif-row">
                    <td>
                      <div class="user-cell">
                        <div class="user-cell-avatar"><?= strtoupper(substr($v['first_name'], 0, 1)) ?></div>
                        <div>
                          <div class="user-cell-name"><?= htmlspecialchars($v['first_name'] . ' ' . $v['last_name']) ?></div>
                          <div class="user-cell-sub">@<?= htmlspecialchars($v['username']) ?></div>
                        </div>
                      </div>
                    </td>
                    <td>
                      <div><?= htmlspecialchars($v['email']) ?></div>
                      <div style="font-size:12px; color:#6B6560;"><?= htmlspecialchars($v['phone'] ?? '—') ?></div>
                    </td>
                    <td style="color:#6B6560; font-size:13px;">
                      <?= date('M d, Y', strtotime($v['created_at'])) ?>
                    </td>
                    <td style="text-align:right;">
                      <button class="btn-row-view" 
                        data-vid="<?= $v['verification_id'] ?>"
                        data-uid="<?= $v['user_id'] ?>"
                        data-firstname="<?= htmlspecialchars($v['first_name']) ?>"
                        data-lastname="<?= htmlspecialchars($v['last_name']) ?>"
                        data-username="<?= htmlspecialchars($v['username']) ?>"
                        data-email="<?= htmlspecialchars($v['email']) ?>"
                        data-phone="<?= htmlspecialchars($v['phone'] ?? '') ?>"
                        data-occupation="<?= htmlspecialchars($v['occupation'] ?? '') ?>"
                        data-address="<?= htmlspecialchars($v['address'] ?? '') ?>"
                        data-doc="<?= htmlspecialchars($v['document'] ?? '') ?>"
                        onclick="openReviewModal(this)">
                        Review
                      </button>
                    </td>
                  </tr>
                <?php endwhile; ?>
              </tbody>
            </table>
          </div>
        <?php else: ?>
          <div class="table-wrap" style="display: flex; align-items: center; justify-content: center; min-height: 220px; text-align: center;">
            <div>
              <div style="font-size: 17px; font-weight: 600; color: #1A1A1A; margin-bottom: 6px;">No pending verifications.</div>
              <div style="font-size: 14px; color: #6B6560;">New account submissions will appear here for review.</div>
            </div>
          </div>
        <?php endif; ?>

      </div>
    </div>
  </div>

  <!-- Review Modal -->
  <div id="review-modal" class="tf-modal-overlay">
    <div class="tf-modal-box">
      <div class="tf-modal-header">
        <h3 class="tf-modal-title">Review Verification</h3>
        <button class="tf-modal-close" onclick="closeReviewModal()">&times;</button>
      </div>

      <div class="tf-hero">
        <div id="modal-avatar" class="tf-avatar">A</div>
        <div>
          <div id="modal-name" class="tf-hero-name"></div>
          <div id="modal-user" class="tf-hero-user"></div>
        </div>
      </div>

      <div class="tf-grid">
        <div class="tf-data-card"><div class="tf-label">Email</div><div id="modal-email" class="tf-value"></div></div>
        <div class="tf-data-card"><div class="tf-label">Phone</div><div id="modal-phone" class="tf-value"></div></div>
        <div class="tf-data-card"><div class="tf-label">Occupation</div><div id="modal-occupation" class="tf-value"></div></div>
        <div class="tf-data-card tf-grid-full"><div class="tf-label">Address</div><div id="modal-address" class="tf-value"></div></div>
        <div class="tf-data-card tf-grid-full">
          <div class="tf-label">Submitted Document</div>
          <a id="modal-doc" href="#" target="_blank" class="tf-doc-btn">View Document</a>
        </div>
      </div>

      <form method="POST" action="verifications.php">
        <input type="hidden" name="verification_id" id="modal-vid">
        <input type="hidden" name="target_user_id" id="modal-uid">
        <input type="hidden" name="action_verify" value="1">

        <div class="tf-actions">
          <button type="submit" name="status" value="denied" class="tf-btn-reject">Reject</button>
          <button type="submit" name="status" value="approved" class="tf-btn-approve">Approve Account</button>
        </div>
      </form>
    </div>
  </div>

  <script>
    function filterTable() {
      const term = document.getElementById('search').value.toLowerCase();
      document.querySelectorAll('#verif-table tbody tr').forEach(row => {
        row.style.display = row.textContent.toLowerCase().includes(term) ? '' : 'none';
      });
    }

    function openReviewModal(btn) {
      const d = btn.dataset;
      document.getElementById('modal-name').textContent = d.firstname + ' ' + d.lastname;
      document.getElementById('modal-user').textContent = '@' + d.username;
      document.getElementById('modal-avatar').textContent = d.firstname.charAt(0).toUpperCase();
      document.getElementById('modal-email').textContent = d.email || '—';
      document.getElementById('modal-phone').textContent = d.phone || '—';
      document.getElementById('modal-occupation').textContent = d.occupation || '—';
      document.getElementById('modal-address').textContent = d.address || '—';
      document.getElementById('modal-doc').href = '../../../assets/uploads/' + d.doc;
      document.getElementById('modal-vid').value = d.vid;
      document.getElementById('modal-uid').value = d.uid;

      document.getElementById('review-modal').style.display = 'flex';
    }

    function closeReviewModal() {
      document.getElementById('review-modal').style.display = 'none';
    }

    window.addEventListener('click', function(e) {
      if (e.target.id === 'review-modal') closeReviewModal();
    });
  </script>
</body>
</html>