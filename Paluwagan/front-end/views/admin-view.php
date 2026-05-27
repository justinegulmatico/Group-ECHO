<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>TrustFund — Admin Panel</title>
  <link rel="stylesheet" href="../../assets/css/global.css" />
  <link rel="stylesheet" href="../../assets/css/admin-panel.css" />
  <link rel="stylesheet" href="../../assets/css/group-details.css" />
</head>
<body>
  <div class="app-layout">
    <?php include "components/sidebar-view.php"; ?>
    <div class="main-content">
      <header class="topbar">
        <span class="topbar-title">Admin Panel</span>
      </header>

      <div class="page-content">
        <?php if (isset($_GET['success'])): ?>
          <div style="background:#f0fdf4; color:#166534; padding:12px; margin-bottom:20px; border-radius:8px; border:1px solid #bbf7d0;">
            <?= htmlspecialchars($_GET['success']); ?>
          </div>
        <?php endif; ?>

        <div class="stat-cards">
          <div class="stat-card" onclick="openSection('verifications-section')">
            <div class="stat-card-label">Pending Approval</div>
            <div class="stat-card-value amber"><?= $pending_verifications; ?></div>
          </div>
          <div class="stat-card" onclick="openSection('users-section')">
            <div class="stat-card-label">Total Users</div>
            <div class="stat-card-value"><?= $total_users; ?></div>
          </div>
          <div class="stat-card" onclick="openSection('groups-section')">
            <div class="stat-card-label">Total Groups</div>
            <div class="stat-card-value"><?= $total_groups; ?></div>
          </div>
        </div>

        <div class="detail-tabs" style="margin-bottom:20px;">
          <button class="detail-tab active" id="btn-verifications-section" onclick="openSection('verifications-section')">Approvals</button>
          <button class="detail-tab" id="btn-users-section" onclick="openSection('users-section')">Users</button>
          <button class="detail-tab" id="btn-groups-section" onclick="openSection('groups-section')">Groups</button>
        </div>

        <div class="admin-panel-section" id="verifications-section">
          <div class="table-wrap">
            <table>
              <thead><tr><th>Name</th><th>Email</th><th>Date</th><th>Action</th></tr></thead>
              <tbody>
                <?php while ($v = mysqli_fetch_assoc($verifications_res)): ?>
                  <tr>
                    <td><?= htmlspecialchars($v['first_name']." ".$v['last_name']); ?></td>
                    <td><?= htmlspecialchars($v['email']); ?></td>
                    <td><?= date('M d, Y - h:i A', strtotime($v['submitted_at'])); ?></td>
                    <td>
                      <button onclick="openDetailsModal('<?= $v['verification_id']; ?>','<?= $v['first_name'].' '.$v['last_name']; ?>','<?= $v['username']; ?>','<?= $v['email']; ?>','<?= $v['phone']; ?>','<?= $v['occupation']; ?>','<?= $v['address']; ?>','<?= $v['user_id']; ?>','<?= $v['document']; ?>')" class="btn-primary">View Details</button>
                    </td>
                  </tr>
                <?php endwhile; ?>
              </tbody>
            </table>
          </div>
        </div>

        <div class="admin-panel-section" id="users-section" style="display:none;">
          <div class="table-wrap">
            <table>
              <thead><tr><th>User</th><th>Status</th><th>Action</th></tr></thead>
              <tbody>
                <?php while ($u = mysqli_fetch_assoc($users_res)): ?>
                  <tr>
                    <td><?= htmlspecialchars($u['first_name']." ".$u['last_name']); ?></td>
                    <td><?= ucfirst($u['status']); ?></td>
                    <td>
                      <a href="admin.php?action=<?= ($u['status']=='suspended')?'activate':'suspend'; ?>&id=<?= $u['user_id']; ?>">
                        <?= ($u['status']=='suspended')?'Unsuspend':'Suspend'; ?>
                      </a>
                    </td>
                  </tr>
                <?php endwhile; ?>
              </tbody>
            </table>
          </div>
        </div>

        <div class="admin-panel-section" id="groups-section" style="display:none;">
          <div class="table-wrap">
            <table>
              <thead><tr><th>Group</th><th>Status</th></tr></thead>
              <tbody>
                <?php while ($g = mysqli_fetch_assoc($groups_res)): ?>
                  <tr><td><?= htmlspecialchars($g['group_name']); ?></td><td><?= $g['is_active']?'Active':'Closed'; ?></td></tr>
                <?php endwhile; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="modal-backdrop" id="detailsModal">
    <div class="modal">
      <div class="modal-header"><h3>Application Details</h3></div>
      <div class="modal-body">
        <p>Name: <span id="md-name"></span></p>
        <p>Occupation: <span id="md-occ"></span></p>
        <a id="md-file" href="#" target="_blank">View Proof</a>
        <form method="POST" action="admin.php">
          <input type="hidden" name="verification_id" id="md-vid">
          <input type="hidden" name="target_user_id" id="md-uid">
          <input type="hidden" name="action_verify" value="1">
          <button type="submit" name="status" value="approved">Approve</button>
          <button type="submit" name="status" value="denied">Reject</button>
        </form>
      </div>
    </div>
  </div>

  <script>
    function openSection(id) {
        document.querySelectorAll('.admin-panel-section').forEach(p => p.style.display='none');
        document.querySelectorAll('.detail-tab').forEach(t => t.classList.remove('active'));
        document.getElementById(id).style.display='block';
        document.getElementById('btn-'+id).classList.add('active');
    }
    function openDetailsModal(vid, name, user, email, phone, occ, addr, uid, file) {
        document.getElementById('md-name').textContent = name;
        document.getElementById('md-occ').textContent = occ;
        document.getElementById('md-file').href = '../../assets/uploads/'+file;
        document.getElementById('md-vid').value = vid;
        document.getElementById('md-uid').value = uid;
        document.getElementById('detailsModal').classList.add('open');
    }
    function closeDetailsModal() { document.getElementById('detailsModal').classList.remove('open'); }
  </script>
</body>
</html>