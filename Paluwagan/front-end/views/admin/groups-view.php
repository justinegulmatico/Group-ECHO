<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>TrustFund — Groups | Admin</title>
  <link rel="stylesheet" href="../../../assets/css/global.css?v=<?= filemtime(__DIR__ . '/../../../assets/css/global.css') ?>" />
  <link rel="stylesheet" href="../../../assets/css/admin-panel.css?v=<?= filemtime(__DIR__ . '/../../../assets/css/admin-panel.css') ?>" />
</head>
<body>
  <div class="app-layout">
    <?php include __DIR__ . '/../components/sidebar-view.php'; ?>

    <div class="main-content">
      <header class="topbar">
        <div class="topbar-left">
          <span class="topbar-title">Admin → Groups</span>
        </div>
        <div class="topbar-right">
          <a href="index.php" class="btn-outline" style="padding:6px 14px; font-size:13px; text-decoration:none;">← Dashboard</a>
        </div>
      </header>

      <div class="page-content">

        <?php if (isset($_GET['success'])): ?>
          <div style="background:#E8F5EE; border:1px solid #a7f3d0; color:#166534; padding:12px 18px; border-radius:10px; margin-bottom:20px;">
            <?= htmlspecialchars($_GET['success']) ?>
          </div>
        <?php endif; ?>

        <div class="admin-hero" style="margin-bottom:18px;">
          <div>
            <div class="admin-hero-title">Group Monitoring</div>
            <div class="admin-hero-sub">View all savings groups and manage their status.</div>
          </div>
        </div>

        <div class="tf-action-bar" style="margin-bottom:16px;">
          <input type="text" id="search" class="tf-search-input" placeholder="Search group name or owner..." onkeyup="filterGroups()">
          <select id="status-filter" class="tf-filter-select" onchange="filterGroups()">
            <option value="all">All Statuses</option>
            <option value="active">Active</option>
            <option value="pending">Pending</option>
            <option value="closed">Closed</option>
            <option value="finished">Finished</option>
          </select>
        </div>

        <?php if (mysqli_num_rows($groups) > 0): ?>
          <div class="table-wrap">
            <table id="groups-table">
              <thead>
                <tr>
                  <th>Group</th>
                  <th>Owner</th>
                  <th>Details</th>
                  <th>Status</th>
                  <th style="text-align:right;">Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php while ($g = mysqli_fetch_assoc($groups)): 
                  $status = strtolower($g['status'] ?? 'active');
                  $badge = 'badge-active';
                  if ($status === 'closed') $badge = 'badge-suspended';
                  if ($status === 'finished') $badge = 'badge-finished';
                ?>
                  <tr class="group-row">
                    <td>
                      <div style="font-weight:600;"><?= htmlspecialchars($g['group_name']) ?></div>
                      <div style="font-size:12px; color:#6B6560;">Created <?= date('M d, Y', strtotime($g['created_at'])) ?></div>
                    </td>
                    <td>
                      <div><?= htmlspecialchars(($g['owner_first'] ?? '—') . ' ' . ($g['owner_last'] ?? '')) ?></div>
                      <div style="font-size:12px; color:#6B6560;">@<?= htmlspecialchars($g['owner_user'] ?? 'N/A') ?></div>
                    </td>
                    <td>
                      <?= ucfirst($g['frequency']) ?> · ₱<?= number_format($g['contribution_amount']) ?><br>
                      <span style="font-size:12px; color:#6B6560;"><?= (int)$g['member_count'] ?> members</span>
                    </td>
                    <td><span class="badge <?= $badge ?>"><?= ucfirst($status) ?></span></td>
                    <td style="text-align:right;">
                      <button class="btn-row-view" 
                        data-gid="<?= $g['group_id'] ?>"
                        data-gname="<?= htmlspecialchars($g['group_name']) ?>"
                        data-owner="<?= htmlspecialchars(($g['owner_first'] ?? '') . ' ' . ($g['owner_last'] ?? '')) ?>"
                        data-freq="<?= htmlspecialchars($g['frequency']) ?>"
                        data-amount="<?= $g['contribution_amount'] ?>"
                        data-members="<?= $g['member_count'] ?>"
                        data-status="<?= $status ?>"
                        data-memberlist="<?= htmlspecialchars($g['member_list'] ?? 'No members') ?>"
                        data-created="<?= date('M d, Y', strtotime($g['created_at'])) ?>"
                        onclick="openGroupModal(this)">
                        View
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
              <div style="font-size: 17px; font-weight: 600; color: #1A1A1A; margin-bottom: 6px;">No groups found.</div>
              <div style="font-size: 14px; color: #6B6560;">All savings groups will appear here for monitoring.</div>
            </div>
          </div>
        <?php endif; ?>

      </div>
    </div>
  </div>

  <!--Group Detail Modal-->
  <div id="group-modal" class="tf-modal-overlay">
    <div class="tf-modal-box" style="max-width:520px;">
      <div class="tf-modal-header">
        <h3 class="tf-modal-title">Group Details</h3>
        <button class="tf-modal-close" onclick="closeGroupModal()">&times;</button>
      </div>

      <div class="tf-grid" style="margin-bottom:20px;">
        <div class="tf-data-card tf-grid-full">
          <div class="tf-label">Group Name</div>
          <div id="g-name" class="tf-value" style="font-size:17px;"></div>
        </div>
        <div class="tf-data-card">
          <div class="tf-label">Owner</div>
          <div id="g-owner" class="tf-value"></div>
        </div>
        <div class="tf-data-card">
          <div class="tf-label">Members</div>
          <div id="g-members" class="tf-value"></div>
        </div>
        <div class="tf-data-card">
          <div class="tf-label">Contribution</div>
          <div id="g-amount" class="tf-value"></div>
        </div>
        <div class="tf-data-card">
          <div class="tf-label">Frequency</div>
          <div id="g-freq" class="tf-value" style="text-transform:capitalize;"></div>
        </div>
        <div class="tf-data-card">
          <div class="tf-label">Created</div>
          <div id="g-created" class="tf-value"></div>
        </div>
        <div class="tf-data-card tf-grid-full">
          <div class="tf-label">Current Members</div>
          <div id="g-memberlist" class="tf-value" style="font-size:13.5px; line-height:1.5;"></div>
        </div>
      </div>

      <form method="POST" action="groups.php">
        <input type="hidden" name="target_group_id" id="g-gid">
        <button type="submit" name="action_close_group" id="close-btn" class="tf-btn-reject" style="width:100%;">Force Close Group</button>
      </form>
    </div>
  </div>

  <script>
    function filterGroups() {
      const search = document.getElementById('search').value.toLowerCase();
      const filter = document.getElementById('status-filter').value.toLowerCase();

      document.querySelectorAll('#groups-table tbody tr').forEach(row => {
        const name = row.querySelector('td:first-child')?.textContent.toLowerCase() || '';
        const owner = row.querySelector('td:nth-child(2)')?.textContent.toLowerCase() || '';
        const statusEl = row.querySelector('.badge');
        let status = statusEl ? statusEl.textContent.toLowerCase().replace(/\s+/g, '_') : '';

        const match = (name.includes(search) || owner.includes(search)) && 
                      (filter === 'all' || status === filter);
        row.style.display = match ? '' : 'none';
      });
    }

    function openGroupModal(btn) {
      const d = btn.dataset;
      document.getElementById('g-name').textContent = d.gname;
      document.getElementById('g-owner').textContent = d.owner;
      document.getElementById('g-members').textContent = d.members + ' members';
      document.getElementById('g-amount').textContent = '₱' + parseInt(d.amount).toLocaleString();
      document.getElementById('g-freq').textContent = d.freq;
      document.getElementById('g-created').textContent = d.created;
      document.getElementById('g-memberlist').textContent = d.memberlist || 'No members';
      document.getElementById('g-gid').value = d.gid;

      const closeBtn = document.getElementById('close-btn');
      if (d.status === 'closed' || d.status === 'finished') {
        closeBtn.style.display = 'none';
      } else {
        closeBtn.style.display = 'block';
      }

      document.getElementById('group-modal').style.display = 'flex';
    }

    function closeGroupModal() {
      document.getElementById('group-modal').style.display = 'none';
    }

    window.addEventListener('click', function(e) {
      if (e.target.id === 'group-modal') closeGroupModal();
    });
  </script>
</body>
</html>
