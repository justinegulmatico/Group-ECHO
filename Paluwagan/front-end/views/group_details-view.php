<?php
// DO NOT put session_start() or database include statements here. 
// This file safely uses the data variables already pulled by group_details.php.
?>
<div class="group-container" style="padding: 20px; font-family: Arial, sans-serif; background-color: #faf9f6; min-height: 100vh;">
    
    <div class="group-header-card" style="background: #1a1a1a; color: #fff; padding: 25px; border-radius: 12px; margin-bottom: 25px;">
        <div style="display: flex; justify-content: space-between; align-items: flex-start;">
            <div>
                <h2 style="margin: 0 0 5px 0; font-size: 24px; font-weight: 600; text-transform: lowercase;"><?= htmlspecialchars($group['group_name'] ?? 'group'); ?></h2>
                <p style="margin: 0; color: #9ca3af; font-size: 14px;"><?= htmlspecialchars($group['description'] ?? 'TrustFund Paluwagan Savings Pool Group Circle.'); ?></p>
            </div>
            
            <div>
                <?= $top_action_buttons_html ?? ''; ?>
            </div>
        </div>

        <div style="display: flex; gap: 30px; margin-top: 20px; font-size: 13px;">
            <div>
                <span style="display: block; color: #6b7280; margin-bottom: 4px;">Contribution</span>
                <span style="font-weight: 600; font-size: 15px;">₱<?= number_format($group['amount_per_cycle'] ?? 0); ?></span>
            </div>
            <div>
                <span style="display: block; color: #6b7280; margin-bottom: 4px;">Total Slots</span>
                <span style="font-weight: 600; font-size: 15px;"><?= $slots_filled ?? 1; ?>/<?= $group['max_members'] ?? 5; ?></span>
            </div>
            <div>
                <span style="display: block; color: #6b7280; margin-bottom: 4px;">Collected</span>
                <span style="font-weight: 600; font-size: 15px;">₱<?= number_format($total_collected ?? 0); ?></span>
            </div>
            <div>
                <span style="display: block; color: #6b7280; margin-bottom: 4px;">Frequency</span>
                <span style="font-weight: 600; font-size: 15px; text-transform: capitalize;"><?= htmlspecialchars($group['cycle_duration'] ?? 'Weekly'); ?></span>
            </div>
            <div>
                <span style="display: block; color: #6b7280; margin-bottom: 4px;">Status</span>
                <span style="background: #10b981; color: #fff; padding: 2px 8px; border-radius: 4px; font-size: 11px; font-weight: bold;">Active</span>
            </div>
        </div>

        <div style="margin-top: 15px; display: inline-block; background: #2a2a2a; padding: 4px 10px; border-radius: 6px; font-size: 12px;">
            <span style="color: #9ca3af;">Invite Code:</span> <strong style="color: #fff; letter-spacing: 0.5px;"><?= htmlspecialchars($group['invite_code'] ?? 'N/A'); ?></strong>
        </div>
    </div>

    <div class="tabs-nav-bar" style="display: flex; border-bottom: 1px solid #e5e7eb; margin-bottom: 20px;">
        <a href="?id=<?= $group_id; ?>&tab=overview" style="padding: 10px 20px; text-decoration: none; font-size: 14px; font-weight: 500; border-bottom: 2px solid <?= $active_tab === 'overview' ? '#f43f5e' : 'transparent'; ?>; color: <?= $active_tab === 'overview' ? '#f43f5e' : '#4b5563'; ?>;">Overview</a>
        <a href="?id=<?= $group_id; ?>&tab=members" style="padding: 10px 20px; text-decoration: none; font-size: 14px; font-weight: 500; border-bottom: 2px solid <?= $active_tab === 'members' ? '#f43f5e' : 'transparent'; ?>; color: <?= $active_tab === 'members' ? '#f43f5e' : '#4b5563'; ?>;">Members</a>
        <a href="?id=<?= $group_id; ?>&tab=schedule" style="padding: 10px 20px; text-decoration: none; font-size: 14px; font-weight: 500; border-bottom: 2px solid <?= $active_tab === 'schedule' ? '#f43f5e' : 'transparent'; ?>; color: <?= $active_tab === 'schedule' ? '#f43f5e' : '#4b5563'; ?>;">Schedule</a>
        <a href="?id=<?= $group_id; ?>&tab=payments" style="padding: 10px 20px; text-decoration: none; font-size: 14px; font-weight: 500; border-bottom: 2px solid <?= $active_tab === 'payments' ? '#f43f5e' : 'transparent'; ?>; color: <?= $active_tab === 'payments' ? '#f43f5e' : '#4b5563'; ?>;">Payments</a>
    </div>

    <div class="tab-content-window" style="background: #fff; border-radius: 8px; padding: 20px; border: 1px solid #e5e7eb; min-height: 200px;">
        
        <?php if ($active_tab === 'overview'): ?>
            <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; margin-bottom: 25px;">
                <div style="background: #fafafa; border: 1px solid #f3f4f6; padding: 20px; border-radius: 10px;">
                    <span style="font-size: 12px; color: #6b7280; display: block; margin-bottom: 6px;">Total Collected</span>
                    <strong style="font-size: 20px; color: #111827;">₱<?= number_format($total_collected ?? 0); ?></strong>
                </div>
                <div style="background: #fafafa; border: 1px solid #f3f4f6; padding: 20px; border-radius: 10px;">
                    <span style="font-size: 12px; color: #6b7280; display: block; margin-bottom: 6px;">Total Paid Out</span>
                    <strong style="font-size: 20px; color: #065f46;">₱<?= number_format($total_paid_out ?? 0); ?></strong>
                </div>
                <div style="background: #fafafa; border: 1px solid #f3f4f6; padding: 20px; border-radius: 10px;">
                    <span style="font-size: 12px; color: #6b7280; display: block; margin-bottom: 6px;">In Pool</span>
                    <strong style="font-size: 20px; color: #065f46;">₱<?= number_format($in_pool ?? 0); ?></strong>
                </div>
                <div style="background: #fafafa; border: 1px solid #f3f4f6; padding: 20px; border-radius: 10px;">
                    <span style="font-size: 12px; color: #6b7280; display: block; margin-bottom: 6px;">My Balance Due</span>
                    <strong style="font-size: 20px; color: #991b1b;">₱<?= number_format($my_balance_due ?? 0); ?></strong>
                </div>
            </div>
            
            <div style="border: 1px solid #f0f0f0; border-radius: 8px; padding: 15px;">
                <h4 style="margin: 0 0 15px 0; font-size: 14px; color: #374151;">Upcoming Payouts</h4>
                <div style="background: #fff5f5; border-radius: 6px; padding: 12px; display: flex; justify-content: space-between; align-items: center; border-left: 4px solid #f43f5e;">
                    <span style="font-size: 13px; font-weight: 500; color: #1f2937;">1. jeny <span style="color:#6b7280; font-size:12px;">(You)</span></span>
                    <strong style="font-size: 14px; color: #111827;">₱<?= number_format(($group['amount_per_cycle'] ?? 0) * 5); ?></strong>
                </div>
            </div>

        <?php elseif ($active_tab === 'schedule'): ?>
            <?php if (!$schedule_initialized): ?>
                <div style="text-align: center; padding: 40px 20px;">
                    <p style="color: #6b7280; font-size: 14px; margin-bottom: 15px;">No cycle tracks initialized yet for this circle setup framework.</p>
                    <?php if ($current_user_id == $creator): ?>
                        <form method="POST" action="../../back-end/components/process-tracking.php">
                            <input type="hidden" name="group_id" value="<?= $group_id ?>">
                            <button type="submit" name="initialize_schedule" style="background-color: #d9534f; color: white; border: none; padding: 10px 20px; border-radius: 6px; font-weight: 500; cursor: pointer;">
                                Initialize Group Schedule Matrix
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <table style="width: 100%; border-collapse: collapse; font-size: 13px; text-align: left;">
                    <thead>
                        <tr style="background: #f9fafb; color: #4b5563; border-bottom: 1px solid #e5e7eb;">
                            <th style="padding: 12px;">TARGET MEMBER</th>
                            <th style="padding: 12px;">CYCLE # INDEX</th>
                            <th style="padding: 12px;">ALLOCATED EXPECTED AMOUNT</th>
                            <th style="padding: 12px;">COLLECTION DUE DATE TARGET</th>
                            <th style="padding: 12px;">COLLECTION LEDGER STATUS</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = mysqli_fetch_assoc($schedule_res)): ?>
                            <tr style="border-bottom: 1px solid #f3f4f6;">
                                <td style="padding: 12px; font-weight: 500;"><?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></td>
                                <td style="padding: 12px;">Cycle <?= htmlspecialchars($row['cycle_number']); ?></td>
                                <td style="padding: 12px;">₱<?= number_format($row['amount']); ?></td>
                                <td style="padding: 12px; color: #6b7280;"><?= date('F d, Y', strtotime($row['due_date'])); ?></td>
                                <td style="padding: 12px;">
                                    <span style="padding: 3px 8px; border-radius: 4px; font-size: 11px; font-weight: 600; background: <?= $row['status'] === 'paid' ? '#d1fae5; color: #065f46;' : '#fef3c7; color: #92400e;'; ?>">
                                        <?= ucfirst(htmlspecialchars($row['status'])); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php endif; ?>

        <?php elseif ($active_tab === 'members'): ?>
            <table style="width: 100%; border-collapse: collapse; font-size: 13px; text-align: left;">
                <thead>
                    <tr style="background: #f9fafb; color: #4b5563; border-bottom: 1px solid #e5e7eb;">
                        <th style="padding: 12px;">NAME</th>
                        <th style="padding: 12px;">EMAIL ADDRESS</th>
                        <th style="padding: 12px;">JOINED DATE</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($m = mysqli_fetch_assoc($members_res)): ?>
                        <tr style="border-bottom: 1px solid #f3f4f6;">
                            <td style="padding: 12px; font-weight: 500;"><?= htmlspecialchars($m['first_name'] . ' ' . $m['last_name']); ?></td>
                            <td style="padding: 12px; color: #4b5563;"><?= htmlspecialchars($m['email']); ?></td>
                            <td style="padding: 12px; color: #6b7280;"><?= date('F d, Y', strtotime($m['joined_at'] ?? 'now')); ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>

        <?php elseif ($active_tab === 'payments'): ?>
            <table style="width: 100%; border-collapse: collapse; font-size: 13px; text-align: left;">
                <thead>
                    <tr style="background: #f9fafb; color: #4b5563; border-bottom: 1px solid #e5e7eb;">
                        <th style="padding: 12px;">MEMBER</th>
                        <th style="padding: 12px;">CYCLE INTERACTION</th>
                        <th style="padding: 12px;">PAID AMOUNT</th>
                        <th style="padding: 12px;">CLEARANCE DATE</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($group_payments_res) === 0): ?>
                        <tr><td colspan="4" style="text-align: center; color: #6b7280; padding: 30px;">No transaction clearance records archived yet.</td></tr>
                    <?php else: ?>
                        <?php while ($p = mysqli_fetch_assoc($group_payments_res)): ?>
                            <tr style="border-bottom: 1px solid #f3f4f6;">
                                <td style="padding: 12px; font-weight: 500;"><?= htmlspecialchars($p['first_name'] . ' ' . $p['last_name']); ?></td>
                                <td style="padding: 12px;">Cycle <?= htmlspecialchars($p['cycle_number']); ?></td>
                                <td style="padding: 12px; color: #059669; font-weight: 600;">+ ₱<?= number_format($p['amount']); ?></td>
                                <td style="padding: 12px; color: #6b7280;"><?= date('M d, Y', strtotime($p['paid_at'])); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>