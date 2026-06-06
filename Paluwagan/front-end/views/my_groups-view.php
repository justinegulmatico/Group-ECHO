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

        <!-- TOP BAR: Search + Buttons -->
        <div class="page-toolbar" style="margin-bottom: 24px;">
          <div class="toolbar-left" style="flex: 1;">
            <!-- Long Search Bar -->
            <div class="search-wrap" style="max-width: 100%; width: 100%;">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/>
              </svg>
              <input type="text" id="search-input" class="search-input" placeholder="Search groups..." onkeyup="filterCurrentTable()">
            </div>
          </div>

          <div class="toolbar-right">
            <button class="btn-outline" onclick="toggleJoinModal(true)">Join with Code</button>
            <button class="btn-create" onclick="toggleCreateModal(true)">+ Create New</button>
          </div>
        </div>

        <!-- TABS -->
        <div class="detail-tabs" style="margin-bottom: 20px;">
          <button class="detail-tab active" id="tab-my" onclick="switchTab('my')">Your Groups</button>
          <button class="detail-tab" id="tab-public" onclick="switchTab('public')">Public Groups</button>
        </div>

        <!-- YOUR GROUPS -->
        <div id="section-my">
          <div class="table-wrap">
            <table id="my-table">
              <thead>
                <tr>
                  <th>Group Name</th>
                  <th>Frequency & Amount</th>
                  <th>Members</th>
                  <th>Status</th>
                  <th style="text-align:right;">Action</th>
                </tr>
              </thead>
              <tbody id="my-tbody">
                <?php while ($group = mysqli_fetch_assoc($my_groups)): ?>
                  <tr onclick="window.location.href='group_details.php?id=<?= $group['group_id'] ?>'">
                    <td><strong><?= htmlspecialchars($group['group_name']) ?></strong></td>
                    <td><?= ucfirst($group['frequency']) ?> · ₱<?= number_format($group['contribution_amount']) ?></td>
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
                  <th>Members</th>
                  <th>Status</th>
                  <th style="text-align:right;">Action</th>
                </tr>
              </thead>
              <tbody id="public-tbody">
                <?php 
                  mysqli_data_seek($public_groups, 0);
                  while ($group = mysqli_fetch_assoc($public_groups)): 
                ?>
                  <tr onclick="window.location.href='group_details.php?id=<?= $group['group_id'] ?>'">
                    <td><strong><?= htmlspecialchars($group['group_name']) ?></strong></td>
                    <td><?= ucfirst($group['frequency']) ?> · ₱<?= number_format($group['contribution_amount']) ?></td>
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
                <?php endwhile; ?>
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

      if (mySection.style.display !== 'none') {
        document.querySelectorAll('#my-tbody tr').forEach(row => {
          row.style.display = row.textContent.toLowerCase().includes(search) ? '' : 'none';
        });
      } else {
        document.querySelectorAll('#public-tbody tr').forEach(row => {
          row.style.display = row.textContent.toLowerCase().includes(search) ? '' : 'none';
        });
      }
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