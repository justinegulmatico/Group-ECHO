<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>TrustFund — Controls | Admin</title>
  <link rel="stylesheet" href="../../../assets/css/global.css?v=<?= filemtime(__DIR__ . '/../../../assets/css/global.css') ?>" />
  <link rel="stylesheet" href="../../../assets/css/admin-panel.css?v=<?= filemtime(__DIR__ . '/../../../assets/css/admin-panel.css') ?>" />
</head>
<body>
  <div class="app-layout">
    <?php include __DIR__ . '/../components/sidebar-view.php'; ?>

    <div class="main-content">
      <header class="topbar">
        <div class="topbar-left">
          <span class="topbar-title">Admin → Controls</span>
        </div>
        <div class="topbar-right">
          <a href="index.php" class="btn-outline" style="padding:6px 14px; font-size:13px; text-decoration:none;">← Dashboard</a>
        </div>
      </header>

      <div class="page-content">

        <?php if (isset($_GET['success'])): ?>
          <div style="background:#E8F5EE; border:1px solid #a7f3d0; color:#166534; padding:12px 18px; border-radius:10px; margin-bottom:20px;">
            ✅ <?= htmlspecialchars($_GET['success']) ?>
          </div>
        <?php endif; ?>

        <?php if (isset($_GET['error'])): ?>
          <div style="background:#FEF2F2; border:1px solid #fecaca; color:#991b1b; padding:12px 18px; border-radius:10px; margin-bottom:20px;">
            ⚠️ <?= htmlspecialchars($_GET['error']) ?>
          </div>
        <?php endif; ?>

        <div class="admin-hero" style="margin-bottom:18px;">
          <div>
            <div class="admin-hero-title">Admin Controls</div>
            <div class="admin-hero-sub">Powerful simulation &amp; cycle management tools. Use carefully — these directly modify live group state and payment records.</div>
          </div>
        </div>

        <div style="font-size:12px; background:#fff7ed; border:1px solid #fed7aa; color:#9a3412; padding:8px 14px; border-radius:8px; margin-bottom:20px;">
          <strong>Tip:</strong> The table shows real-time paid progress per group. Use <strong>Force Paid</strong> or the bulk tool when you want to fast-forward a demo. Payout release is now guarded — it will refuse if contributions aren't 100% paid.
        </div>

        <!-- Active Groups Quick Controls -->
        <div class="section-title" style="font-size:16px; margin-bottom:12px;">Active Groups — Quick Actions</div>

        <?php if (mysqli_num_rows($controls) > 0): ?>
          <div class="table-wrap" style="margin-bottom:32px;">
            <table>
              <thead>
                <tr>
                  <th>Group</th>
                  <th>Current Cycle</th>
                  <th>Members</th>
                  <th>Contributions Paid</th>
                  <th>Payout Status</th>
                  <th style="text-align:right; min-width: 260px;">Quick Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php 
                // Reset pointer in case
                mysqli_data_seek($controls, 0);
                while ($cg = mysqli_fetch_assoc($controls)): 
                  $paid = (int)($cg['paid_count'] ?? 0);
                  $total = (int)($cg['total_contribs'] ?? $cg['members']);
                  $pct = $total > 0 ? round(($paid / $total) * 100) : 0;
                  $payout = $cg['payout_status'] ?? 'pending';
                ?>
                  <tr>
                    <td><strong><?= htmlspecialchars($cg['group_name']) ?></strong></td>
                    <td><strong>#<?= $cg['current_cycle'] ?></strong><br><small style="color:#6B6560;"><?= htmlspecialchars($cg['current_start'] ?? '—') ?></small></td>
                    <td><?= $cg['members'] ?></td>
                    <td>
                      <div style="font-weight:600;"><?= $paid ?> / <?= $total ?></div>
                      <div style="height:6px; background:#E4DDD4; border-radius:999px; margin-top:4px; overflow:hidden;">
                        <div style="height:100%; width:<?= $pct ?>%; background:<?= $paid >= $total ? '#166534' : '#E15225' ?>; transition:width .2s;"></div>
                      </div>
                      <div style="font-size:10px; color:#6B6560;"><?= $pct ?>% paid</div>
                    </td>
                    <td>
                      <?php if ($payout === 'released'): ?>
                        <span class="badge" style="background:#dcfce7; color:#166534; border:1px solid #86efac;">Released</span>
                      <?php else: ?>
                        <span class="badge badge-active" style="background:#fef3c7; color:#854d0e;">Pending</span>
                      <?php endif; ?>
                    </td>
                    <td style="text-align:right;">
                      <form method="POST" action="controls.php" style="display:inline;" onsubmit="return confirm('Advance this group to the next cycle? New contribution records will be created.');">
                        <input type="hidden" name="group_id" value="<?= $cg['group_id'] ?>">
                        <button type="submit" name="admin_advance_cycle" class="btn-row-view" style="font-size:11px; padding:4px 9px;">Advance Cycle</button>
                      </form>

                      <form method="POST" action="controls.php" style="display:inline; margin-left:4px;" onsubmit="return confirm('Mark ALL pending contributions as PAID for the current cycle?');">
                        <input type="hidden" name="group_id" value="<?= $cg['group_id'] ?>">
                        <input type="hidden" name="cycle_number" value="<?= $cg['current_cycle'] ?>">
                        <button type="submit" name="admin_force_paid" class="btn-row-view" style="font-size:11px; padding:4px 9px; background:#1e40af; color:white;">Force Paid</button>
                      </form>

                      <?php if ($payout !== 'released' && $paid >= $total && $total > 0): ?>
                        <form method="POST" action="controls.php" style="display:inline; margin-left:4px;" onsubmit="return confirm('Release payout for this cycle? This will create a payout record for the assigned recipient.');">
                          <input type="hidden" name="group_id" value="<?= $cg['group_id'] ?>">
                          <input type="hidden" name="cycle_number" value="<?= $cg['current_cycle'] ?>">
                          <button type="submit" name="admin_release_payout" class="btn-row-view" style="font-size:11px; padding:4px 9px; background:#166534; color:white;">Release Payout</button>
                        </form>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endwhile; ?>
              </tbody>
            </table>
          </div>
        <?php else: ?>
          <div class="table-wrap" style="display: flex; align-items: center; justify-content: center; min-height: 180px; margin-bottom:32px; text-align: center;">
            <div>
              <div style="font-size: 17px; font-weight: 600; color: #1A1A1A; margin-bottom: 6px;">No active groups.</div>
              <div style="font-size: 14px; color: #6B6560;">Groups will appear here when they are active.</div>
            </div>
          </div>
        <?php endif; ?>

        <!-- Manual Tools -->
        <div class="section-title" style="font-size:16px; margin-bottom:12px;">Manual / Targeted Tools</div>

        <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(340px, 1fr)); gap: 20px;">

          <!-- Update Cycle Date - Group + Cycle aware -->
          <div class="admin-tool-card">
            <div style="font-weight:700; margin-bottom:10px; font-size:15px;">📅 Update Cycle Start Date</div>
            <div style="font-size:12px; color:#6B6560; margin-bottom:10px;">Select group and cycle number instead of guessing IDs.</div>
            <form method="POST" action="controls.php">
              <div class="form-group">
                <label class="input-label">Group</label>
                <select name="group_id" class="input-field" required>
                  <option value="">— Select Group —</option>
                  <?php 
                  if ($groups_select) {
                    mysqli_data_seek($groups_select, 0);
                    while ($g = mysqli_fetch_assoc($groups_select)): 
                  ?>
                    <option value="<?= $g['group_id'] ?>"><?= htmlspecialchars($g['group_name']) ?> (current cycle #<?= $g['current_cycle'] ?>)</option>
                  <?php endwhile; } ?>
                </select>
              </div>
              <div class="form-group">
                <label class="input-label">Cycle Number</label>
                <input type="number" name="cycle_number" class="input-field" value="1" min="1" required>
              </div>
              <div class="form-group">
                <label class="input-label">New Start Date</label>
                <input type="date" name="start_date" class="input-field" value="<?= date('Y-m-d') ?>" required>
              </div>
              <button type="submit" name="admin_set_cycle_date" class="btn-primary" style="width:100%;">Update Cycle Date</button>
            </form>
          </div>

          <!-- Release Payout - Group aware -->
          <div class="admin-tool-card">
            <div style="font-weight:700; margin-bottom:10px; font-size:15px;">💰 Release Payout</div>
            <div style="font-size:12px; color:#6B6560; margin-bottom:10px;">Payout will only be released if all contributions for the cycle are paid.</div>
            <form method="POST" action="controls.php" onsubmit="return confirm('Release payout? The system will verify all payments are complete.');">
              <div class="form-group">
                <label class="input-label">Group</label>
                <select name="group_id" class="input-field" required>
                  <option value="">— Select Group —</option>
                  <?php 
                  if ($groups_select) {
                    mysqli_data_seek($groups_select, 0);
                    while ($g = mysqli_fetch_assoc($groups_select)): 
                  ?>
                    <option value="<?= $g['group_id'] ?>"><?= htmlspecialchars($g['group_name']) ?> (current: #<?= $g['current_cycle'] ?>)</option>
                  <?php endwhile; } ?>
                </select>
              </div>
              <div class="form-group">
                <label class="input-label">Cycle Number (usually current)</label>
                <input type="number" name="cycle_number" class="input-field" value="1" min="1" required>
              </div>
              <button type="submit" name="admin_release_payout" class="btn-primary" style="width:100%; background:#166534;">Release Payout for Cycle</button>
              <div style="font-size:11.5px; color:#6B6560; margin-top:8px;">Creates a payout record for the member in the payout position for that cycle.</div>
            </form>
          </div>

        </div>

        <!-- Bulk Simulation Tools -->
        <div style="margin-top: 24px;">
          <div class="section-title" style="font-size:15px; margin-bottom:10px;">Bulk / Demo Tools</div>
          <div class="admin-tool-card" style="max-width: 520px;">
            <div style="margin-bottom: 10px;">
              <strong>Bulk Force All Paid</strong>
              <div style="font-size:12.5px; color:#6B6560;">Marks every pending contribution as paid across <strong>all active groups</strong>. Great for rapid demo / testing.</div>
            </div>
            <form method="POST" action="controls.php" onsubmit="return confirm('This will force-mark ALL unpaid contributions as paid for every active group. Continue?');">
              <button type="submit" name="admin_bulk_force_paid" class="btn-primary" style="background:#854d0e; width:100%;">
                ⚡ Force Paid — All Active Groups
              </button>
            </form>
          </div>
        </div>

      </div>
    </div>
  </div>
</body>
</html>