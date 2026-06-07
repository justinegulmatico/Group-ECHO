<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>TrustFund — My Groups</title>
  <link rel="stylesheet" href="../../assets/css/global.css">
  <link rel="stylesheet" href="../../assets/css/my-groups.css?v=<?= filemtime('../../assets/css/my-groups.css'); ?>" />
</head>
<body>
  <div class="app-layout">
    <?php include "components/sidebar-view.php"; ?>

    <div class="main-content">
      <header class="topbar">
        <div class="topbar-left">
          <span class="topbar-title">My Groups</span>
        </div>
        <div class="topbar-right">
          <button class="notif-btn" id="notif-btn">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
              <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
            </svg>
          </button>
        </div>
      </header>

      <div class="page-content">

        <!-- Success / Error Messages -->
        <?php if (isset($_GET['success'])): ?>
          <div style="background:#E8F5EE; border:1px solid #a7f3d0; color:#166534; padding:12px 16px; border-radius:12px; margin-bottom:20px;">
            <?= htmlspecialchars($_GET['success']) ?>
          </div>
        <?php endif; ?>
        <?php if (!empty($error_message)): ?>
          <div style="background:#fee2e2; border:1px solid #fca5a5; color:#991b1b; padding:12px 16px; border-radius:12px; margin-bottom:20px;">
            <?= htmlspecialchars($error_message) ?>
          </div>
        <?php endif; ?>

        <!-- Combined Tabs and Controls Bar -->
        <div class="detail-tabs">
          <button class="detail-tab active" id="tab-my" onclick="switchTab('my')">Your Groups</button>
          <button class="detail-tab" id="tab-public" onclick="switchTab('public')">Public Groups</button>
        </div>

        <!-- Search and Action Controls (inside tab view) -->
        <div class="page-toolbar" style="margin-bottom: 16px;">
          <div class="toolbar-left" style="flex: 1; display: flex; align-items: center; gap: 12px;">
            <div class="search-wrap" style="max-width: 300px; flex: 1;">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/>
              </svg>
              <input type="text" id="search-input" class="search-input" placeholder="Search groups..." onkeyup="filterCurrentTable()">
            </div>
            <!-- Sorting -->
            <div style="display: flex; align-items: center; gap: 6px; font-size: 13px; color: #6B6560;">
              <span>Sort by:</span>
              <select style="padding: 6px 10px; border: 1px solid #E4DDD4; border-radius: 6px; font-size: 13px; background: #fff;" onchange="sortCurrentTable(this.value)">
                <option value="Alphabetical">Alphabetical</option>
                <option value="Most Members">Most Members</option>
                <option value="Recent">Recent</option>
              </select>
            </div>

          </div>

          <div class="toolbar-right" style="display: flex; gap: 8px;">
            <button class="btn-outline" onclick="toggleJoinModal(true)">Join with Code</button>
            <button class="btn-create" onclick="toggleCreateModal(true)">+ Create New</button>
          </div>
        </div>

        <!-- YOUR GROUPS -->
        <div id="section-my">
          <div class="table-wrap">
            <table id="my-table">
              <thead>
                <tr>
                  <th>Group Name</th>
                  <th>Frequency & Amount</th>
                  <th>Progress</th>
                  <th>Members</th>
                  <th>Status</th>
                  <th style="text-align:right;">Action</th>
                </tr>
              </thead>
              <tbody id="my-tbody">
                <?php while ($group = mysqli_fetch_assoc($my_groups)): ?>
                  <tr onclick="window.location.href='group_details.php?id=<?= $group['group_id'] ?>'">
                    <td>
                      <?php 
                        $is_public = (isset($group['privacy']) && $group['privacy'] == 'public');
                      ?>
                      <?php if ($is_public): ?>
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#3b82f6" stroke-width="2" style="vertical-align: middle; margin-right: 6px;"><circle cx="12" cy="12" r="10"/><path d="M2 12h20"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
                      <?php else: ?>
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#6b7280" stroke-width="2" style="vertical-align: middle; margin-right: 6px;"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                      <?php endif; ?>
                      <strong><?= htmlspecialchars($group['group_name']) ?></strong>
                    </td>
                    <td><?= ucfirst($group['frequency']) ?> · ₱<?= number_format($group['contribution_amount']) ?></td>
                    <td>
                      <?php if ($group['status'] == 'active'): ?>
                        <?php 
                          $cycle_len = $group['cycle_length'] ?: $group['max_members'] ?: 5;
                          $pct = $cycle_len > 0 ? round( ($group['current_cycle'] / $cycle_len ) * 100 ) : 0;
                        ?>
                        <div style="display: flex; align-items: center; gap: 6px;">
                          <div class="progress-bar-wrap" style="flex: 1; height: 6px; margin: 0;">
                            <div class="progress-bar-fill" style="width: <?= $pct ?>%;"></div>
                          </div>
                          <span style="font-size: 11px; color: #E15225; white-space: nowrap;"><?= $pct ?>%</span>
                        </div>
                        <div style="font-size: 10px; color: #6B6560; margin-top: 2px;">Cycle <?= $group['current_cycle'] ?> of <?= $cycle_len ?></div>
                      <?php else: ?>
                        <span style="font-size: 11px; color: #6B6560;">Awaiting pool</span>
                      <?php endif; ?>
                    </td>
                    <td><?= $group['member_count'] ?> / <?= $group['max_members'] ?> (positions)</td>
                    <td>
                      <span class="badge <?= $group['status'] == 'active' ? 'badge-active' : ($group['status'] == 'pending' ? 'badge-pending' : 'badge-closed') ?>">
                        <?= ucfirst($group['status']) ?>
                      </span>
                    </td>
                    <td style="text-align:right;">
                      <button class="btn-row-view" onclick="event.stopImmediatePropagation(); window.location.href='group_details.php?id=<?= $group['group_id'] ?>'">View Details</button>
                    </td>
                  </tr>
                <?php endwhile; ?>
              </tbody>
            </table>
          </div>
        </div>

        <!-- PUBLIC GROUPS -->
        <div id="section-public" style="display: none;">
          <div class="table-wrap">
            <table id="public-table">
              <thead>
                <tr>
                  <th>Group Name</th>
                  <th>Frequency & Amount</th>
                  <th>Progress</th>
                  <th>Members</th>
                  <th>Status</th>
                  <th style="text-align:right;">Action</th>
                </tr>
              </thead>
              <tbody id="public-tbody">
                <?php 
                  if ($public_groups && mysqli_num_rows($public_groups) > 0) {
                      mysqli_data_seek($public_groups, 0);
                      while ($group = mysqli_fetch_assoc($public_groups)): 
                ?>
                  <tr onclick="window.location.href='group_details.php?id=<?= $group['group_id'] ?>'">
                    <td>
                      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#3b82f6" stroke-width="2" style="vertical-align: middle; margin-right: 6px;"><circle cx="12" cy="12" r="10"/><path d="M2 12h20"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
                      <strong><?= htmlspecialchars($group['group_name']) ?></strong>
                    </td>
                    <td><?= ucfirst($group['frequency']) ?> · ₱<?= number_format($group['contribution_amount']) ?></td>
                    <td>
                      <?php if ($group['status'] == 'active'): ?>
                        <?php 
                          $cycle_len = $group['cycle_length'] ?: $group['max_members'] ?: 5;
                          $pct = $cycle_len > 0 ? round( ($group['current_cycle'] / $cycle_len ) * 100 ) : 0;
                        ?>
                        <div style="display: flex; align-items: center; gap: 6px;">
                          <div class="progress-bar-wrap" style="flex: 1; height: 6px; margin: 0;">
                            <div class="progress-bar-fill" style="width: <?= $pct ?>%;"></div>
                          </div>
                          <span style="font-size: 11px; color: #E15225; white-space: nowrap;"><?= $pct ?>%</span>
                        </div>
                        <div style="font-size: 10px; color: #6B6560; margin-top: 2px;">Cycle <?= $group['current_cycle'] ?> of <?= $cycle_len ?></div>
                      <?php else: ?>
                        <span style="font-size: 11px; color: #6B6560;">Awaiting pool</span>
                      <?php endif; ?>
                    </td>
                    <td><?= $group['member_count'] ?> / <?= $group['max_members'] ?> (positions)</td>
                    <td>
                      <span class="badge <?= $group['status'] == 'active' ? 'badge-active' : ($group['status'] == 'pending' ? 'badge-pending' : 'badge-closed') ?>">
                        <?= ucfirst($group['status']) ?>
                      </span>
                    </td>
                    <td style="text-align:right;">
                      <form method="POST" action="my_groups.php" style="display:inline;" onclick="event.stopImmediatePropagation();">
                        <input type="hidden" name="action_join_public" value="1">
                        <input type="hidden" name="group_id" value="<?= $group['group_id'] ?>">
                        <button type="submit" class="btn-row-view" style="padding: 4px 10px; font-size: 12px;">Join</button>
                      </form>
                      <button class="btn-row-view" onclick="event.stopImmediatePropagation(); window.location.href='group_details.php?id=<?= $group['group_id'] ?>'" style="margin-left:4px;">View</button>
                    </td>
                  </tr>
                <?php endwhile; 
                  } else {
                      echo '<tr><td colspan="6" style="text-align:center; padding:20px; color:#8A837A;">No public groups available to join right now.</td></tr>';
                  }
                ?>
              </tbody>
            </table>
          </div>
        </div>

      </div>
    </div>
  </div>

  <!-- Modals -->
  <!-- (Keep your Create and Join modals here) -->

  <script>
    function switchTab(tab) {
      const publicSection = document.getElementById('section-public');
      const mySection = document.getElementById('section-my');
      const tabPublic = document.getElementById('tab-public');
      const tabMy = document.getElementById('tab-my');

      if (tab === 'my') {
        mySection.style.display = 'block';
        publicSection.style.display = 'none';
        tabMy.classList.add('active');
        tabPublic.classList.remove('active');
      } else {
        publicSection.style.display = 'block';
        mySection.style.display = 'none';
        tabPublic.classList.add('active');
        tabMy.classList.remove('active');
      }
    }

    function filterCurrentTable() {
      const search = document.getElementById('search-input').value.toLowerCase();
      const mySection = document.getElementById('section-my');
      const publicSection = document.getElementById('section-public');

      if (mySection && mySection.style.display !== 'none') {
        document.querySelectorAll('#my-tbody tr').forEach(row => {
          row.style.display = row.textContent.toLowerCase().includes(search) ? '' : 'none';
        });
      } else if (publicSection) {
        document.querySelectorAll('#public-tbody tr').forEach(row => {
          row.style.display = row.textContent.toLowerCase().includes(search) ? '' : 'none';
        });
      }
    }

    function sortCurrentTable(sortBy) {
      const mySection = document.getElementById('section-my');
      const publicSection = document.getElementById('section-public');
      let tbody = null;
      if (mySection && mySection.style.display !== 'none') {
        tbody = document.querySelector('#my-tbody');
      } else if (publicSection) {
        tbody = document.querySelector('#public-tbody');
      }
      if (!tbody) return;
      const rows = Array.from(tbody.querySelectorAll('tr'));
      rows.sort((a, b) => {
        if (sortBy === 'Alphabetical') {
          const nameA = a.cells[0].textContent.trim().toLowerCase();
          const nameB = b.cells[0].textContent.trim().toLowerCase();
          return nameA.localeCompare(nameB);
        } else if (sortBy === 'Most Members') {
          const memA = parseInt(a.cells[3].textContent.split('/')[0]) || 0;
          const memB = parseInt(b.cells[3].textContent.split('/')[0]) || 0;
          return memB - memA;
        } else if (sortBy === 'Recent') {
          // No date info, fallback to original order or reverse
          return 0;
        }
        return 0;
      });
      rows.forEach(row => tbody.appendChild(row));
    }

    function toggleCreateModal(show) {
      document.getElementById('createGroupModal').style.display = show ? 'flex' : 'none';
    }
    function toggleJoinModal(show) {
      document.getElementById('joinGroupModal').style.display = show ? 'flex' : 'none';
    }

    // Default show Your Groups
    window.onload = function() {
      document.getElementById('section-public').style.display = 'none';
      document.getElementById('section-my').style.display = 'block';
      document.getElementById('tab-my').classList.add('active');
      document.getElementById('tab-public').classList.remove('active');
    }
  </script>

  <!-- ==================== CREATE GROUP MODAL (exact visual match to system) ==================== -->
  <div class="modal-overlay" id="createGroupModal" style="display:none;">
    <div class="modal">
      <div class="modal-header">
        <span class="modal-title">Create New Group</span>
        <button class="modal-close" type="button" onclick="toggleCreateModal(false)">✕</button>
      </div>
      <form method="POST" action="my_groups.php">
        <input type="hidden" name="action_create_group" value="1">
        <div class="modal-body">
          <div class="form-group">
            <label class="input-label" for="cg-name">Group Name</label>
            <input class="input-field" id="cg-name" name="group_name" type="text" placeholder="e.g. Barkada Savings 2026" required />
          </div>
          
          <div class="form-group">
            <label class="input-label" for="cg-desc">Description <span style="font-weight:400;color:#7c7c7c;">(optional)</span></label>
            <input class="input-field" id="cg-desc" name="group_desc" type="text" placeholder="What is this group for?" />
          </div>

          <div class="form-row">
            <div class="form-group" style="flex:1;">
              <label class="input-label" for="cg-privacy">Privacy</label>
              <select class="input-field" id="cg-privacy" name="privacy" onchange="toggleInviteCodeMy()">
                <option value="public">Public (Anyone can join)</option>
                <option value="private">Private (Invite Code Required)</option>
              </select>
            </div>
            <div class="form-group" id="invite-code-container-my" style="flex:1; display:none;">
              <label class="input-label" for="cg-invitecode-my">Generated Invite Code</label>
              <input class="input-field" id="cg-invitecode-my" name="invite_code" type="text" readonly style="background:#eef2f5; font-family:monospace; letter-spacing:1px; font-weight:bold; color:#f47321; text-align:center;" />
            </div>
          </div>

          <div class="form-row">
            <div class="form-group" style="flex:1;">
              <label class="input-label" for="cg-amount">Contribution Amount (₱)</label>
              <input class="input-field" id="cg-amount" name="contribution" type="number" placeholder="1000" min="1" required />
            </div>
            <div class="form-group" style="flex:1;">
              <label class="input-label" for="cg-slots">Total Slots / Members</label>
              <input class="input-field" id="cg-slots" name="max_members" type="number" placeholder="5" min="2" max="50" required />
            </div>
          </div>

          <div class="form-row">
            <div class="form-group" style="flex:1;">
              <label class="input-label" for="cg-frequency">Payment Frequency</label>
              <select class="input-field" id="cg-frequency" name="frequency">
                <option value="monthly">Monthly</option>
                <option value="weekly">Weekly</option>
                <option value="biweekly">Bi-weekly</option>
              </select>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn-primary" type="submit" name="action_create_group">Create Group</button>
        </div>
      </form>
    </div>
  </div>

  <!-- ==================== JOIN GROUP MODAL (exact visual match) ==================== -->
  <div class="modal-overlay" id="joinGroupModal" style="display:none;">
    <div class="modal">
      <div class="modal-header">
        <span class="modal-title">Join a Group</span>
        <button class="modal-close" onclick="toggleJoinModal(false)">✕</button>
      </div>

      <div class="modal-body">
        <form method="POST" action="my_groups.php">
          <input type="hidden" name="action_join_group" value="1">
          
          <div class="form-group">
            <label class="input-label">Invite Code</label>
            <input class="input-field" 
                   type="text" 
                   name="invite_code" 
                   required 
                   placeholder="ENTER INVITE CODE"
                   style="text-transform: uppercase; font-family: monospace; letter-spacing: 2px; text-align: center;">
          </div>

          <?php if (!empty($error_message)): ?>
            <div style="color: #b91c1c; font-size: 13px; text-align: center; margin-top: 8px;">
              <?= htmlspecialchars($error_message) ?>
            </div>
          <?php endif; ?>

          <div class="modal-footer" style="display: flex; gap: 12px; justify-content: flex-end; margin-top: 20px;">
            <button type="button" class="btn-outline" onclick="toggleJoinModal(false)">Cancel</button>
            <button type="submit" class="btn-create">Join Group</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <script>
    function toggleInviteCodeMy() {
      const sel = document.getElementById('cg-privacy');
      const cont = document.getElementById('invite-code-container-my');
      const inp = document.getElementById('cg-invitecode-my');
      if (!sel || !cont) return;
      if (sel.value === 'private') {
        cont.style.display = 'block';
        if (inp && !inp.value) {
          const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
          let c = ''; for (let i=0; i<6; i++) c += chars.charAt(Math.floor(Math.random()*chars.length));
          inp.value = c;
        }
      } else {
        cont.style.display = 'none';
        if (inp) inp.value = '';
      }
    }

    // Close create/join modals on outside click (extend existing)
    (function(){
      const cm = document.getElementById('createGroupModal');
      if (cm) cm.addEventListener('click', function(e){ if (e.target === cm) cm.style.display='none'; });
      const jm = document.getElementById('joinGroupModal');
      if (jm) jm.addEventListener('click', function(e){ if (e.target === jm) jm.style.display='none'; });
      // init privacy toggle if present
      const p = document.getElementById('cg-privacy');
      if (p) p.addEventListener('change', toggleInviteCodeMy);
    })();
  </script>
</body>
</html>