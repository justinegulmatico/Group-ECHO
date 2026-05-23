<?php
session_start();
include "db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$current_user_id = $_SESSION['user_id'];

// 1. Get Logged-in User Profile Information
$user_query = "SELECT first_name, last_name, role FROM users WHERE user_id = '$current_user_id'";
$user_result = mysqli_query($conn, $user_query);
$user_data = mysqli_fetch_assoc($user_result);

$full_name = ($user_data) ? $user_data['first_name'] . " " . $user_data['last_name'] : "User";
$user_role = ($user_data) ? ucfirst($user_data['role']) : "Member";

$initials = "U";
if ($user_data) {
    $initials = strtoupper(substr($user_data['first_name'], 0, 1));
    if(!empty($user_data['last_name'])) {
        $initials .= strtoupper(substr($user_data['last_name'], 0, 1));
    }
}

// 2. Aggregate Stats
$count_query = "SELECT COUNT(*) as active_count FROM group_members WHERE user_id = '$current_user_id' AND status = 'active'";
$count_res = mysqli_query($conn, $count_query);
$count_data = mysqli_fetch_assoc($count_res);
$active_groups_count = $count_data['active_count'] ?? 0;

$contrib_query = "SELECT SUM(amount) as total_contributed FROM contributions WHERE member_id IN (SELECT member_id FROM group_members WHERE user_id = '$current_user_id') AND status = 'paid'";
$contrib_res = mysqli_query($conn, $contrib_query);
$contrib_data = mysqli_fetch_assoc($contrib_res);
$total_contributed = $contrib_data['total_contributed'] ?? 0.00;

$payout_query = "SELECT SUM(amount) as total_received FROM payouts WHERE member_id IN (SELECT member_id FROM group_members WHERE user_id = '$current_user_id') AND status = 'released'";
$payout_res = mysqli_query($conn, $payout_query);
$payout_data = mysqli_fetch_assoc($payout_res);
$total_received = $payout_data['total_received'] ?? 0.00;

$net_position = $total_received - $total_contributed;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TrustFund - Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style> body { background-color: #FDFBF7; } </style>
</head>
<body class="flex min-h-screen text-gray-800 antialiased">

    <?php include "sidebar.php"; ?>

    <div class="flex-1 flex flex-col pl-64">
        <header class="h-16 bg-white border-b border-gray-200 flex items-center justify-between px-8 sticky top-0 z-10">
            <div class="flex items-center space-x-4">
                <button class="p-2 text-gray-606 hover:bg-gray-50 rounded-lg border border-gray-200"><i class="fa-solid fa-bars text-lg"></i></button>
                <h2 class="text-2xl font-medium text-gray-800">Dashboard</h2>
            </div>
            <button class="p-2 text-gray-500 hover:text-gray-800 relative bg-gray-50 rounded-full border border-gray-200"><i class="fa-solid fa-bell text-lg"></i></button>
        </header>

        <main class="flex-1 p-8 max-w-7xl w-full mx-auto">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <div onclick="window.location.href='my_groups.php'" class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm hover:border-orange-200 hover:shadow-md cursor-pointer transition duration-150 group">
                    <div class="flex justify-between items-center">
                        <p class="text-xs font-medium text-gray-500 group-hover:text-orange-600 transition">Active Groups</p>
                        <i class="fa-solid fa-arrow-up-right-from-square text-[10px] text-gray-300 group-hover:text-orange-500 transition"></i>
                    </div>
                    <p class="text-3xl font-semibold text-orange-600 my-1"><?= $active_groups_count; ?></p>
                    <p class="text-xs text-gray-400"><?= $active_groups_count; ?> total joined</p>
                </div>

                <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm">
                    <p class="text-xs font-medium text-gray-500">Total Contributed</p>
                    <p class="text-3xl font-semibold text-gray-900 my-1">₱<?= number_format($total_contributed, 2); ?></p>
                    <p class="text-xs text-gray-400">across all groups</p>
                </div>

                <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm">
                    <p class="text-xs font-medium text-gray-500">Total Received</p>
                    <p class="text-3xl font-semibold text-green-600 my-1">₱<?= number_format($total_received, 2); ?></p>
                    <p class="text-xs text-gray-400">payouts received</p>
                </div>

                <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm">
                    <p class="text-xs font-medium text-gray-500">Net Position</p>
                    <p class="text-3xl font-semibold <?= $net_position >= 0 ? 'text-green-600' : 'text-red-600'; ?> my-1">
                        ₱<?= number_format(abs($net_position), 2); ?>
                    </p>
                    <p class="text-xs text-gray-400"><?= $net_position >= 0 ? 'net positive status' : 'net negative positions'; ?></p>
                </div>
            </div>

            <div class="flex justify-between items-center mb-6">
                <h3 class="text-xl font-medium text-gray-900">Active Groups</h3>
                <button onclick="window.location.href='my_groups.php'" class="bg-[#FF5722] hover:bg-orange-600 text-white font-medium px-4 py-2 rounded-xl shadow-sm transition flex items-center space-x-2">
                    <span>+ New Group</span>
                </button>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php
                $groups_list_query = "SELECT g.*, m.member_id FROM groups g 
                                      JOIN group_members m ON g.group_id = m.group_id 
                                      WHERE m.user_id = '$current_user_id' AND m.status = 'active'";
                $groups_list_res = mysqli_query($conn, $groups_list_query);

                if (mysqli_num_rows($groups_list_res) > 0):
                    while ($group = mysqli_fetch_assoc($groups_list_res)):
                        $gid = $group['group_id'];

                        $slots_filled_q = "SELECT COUNT(*) as slots_count FROM group_members WHERE group_id = '$gid' AND status = 'active'";
                        $slots_filled_res = mysqli_query($conn, $slots_filled_q);
                        $slots_filled_data = mysqli_fetch_assoc($slots_filled_res);
                        $slots_filled = $slots_filled_data['slots_count'] ?? 0;

                        $pool_q = "SELECT SUM(c.amount) as collected_pool FROM contributions c 
                                   JOIN cycles cy ON c.cycle_id = cy.cycle_id 
                                   WHERE cy.group_id = '$gid' AND c.status = 'paid'";
                        $pool_res = mysqli_query($conn, $pool_q);
                        $pool_data = mysqli_fetch_assoc($pool_res);
                        $collected_pool = $pool_data['collected_pool'] ?? 0.00;

                        $max_cycle_pot = $group['contribution_amount'] * $group['cycle_length'];
                        $progress_percent = $max_cycle_pot > 0 ? min(100, ($collected_pool / $max_cycle_pot) * 100) : 0;
                ?>
                    <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm flex flex-col justify-between hover:border-orange-200 transition duration-150 cursor-pointer" onclick="window.location.href='group_details.php?id=<?= $gid; ?>'">
                        <div>
                            <div class="flex justify-between items-start mb-2">
                                <h4 class="text-lg font-medium text-gray-900"><?= htmlspecialchars($group['group_name']); ?></h4>
                                <span class="bg-emerald-50 text-emerald-700 text-xs px-2 py-0.5 rounded-full font-medium capitalize">Active</span>
                            </div>
                            <p class="text-sm text-gray-500 mb-4">Monthly · ₱<?= number_format($group['contribution_amount'], 2); ?>/cycle</p>
                            <div class="flex justify-between text-xs text-gray-500 font-medium">
                                <span>₱<?= number_format($collected_pool, 2); ?> collected</span>
                                <span><?= (int)$progress_percent; ?>%</span>
                            </div>
                            <div class="w-full bg-amber-50 h-2 rounded-full mt-1.5 overflow-hidden">
                                <div class="bg-[#FFDCA9] h-full rounded-full transition-all duration-300" style="width: <?= $progress_percent; ?>%"></div>
                            </div>
                        </div>
                        <div class="mt-6 pt-4 border-t border-gray-100 flex justify-between items-center">
                            <div class="w-7 h-7 rounded-full bg-rose-200 text-rose-700 font-semibold text-xs flex items-center justify-center"><?= strtoupper(substr($group['group_name'], 0, 2)); ?></div>
                            <span class="text-xs text-gray-500 font-medium"><?= $slots_filled; ?>/<?= $group['cycle_length']; ?> members</span>
                        </div>
                    </div>
                <?php 
                    endwhile;
                else: 
                ?>
                    <div class="col-span-full bg-white p-8 rounded-xl border border-gray-200 shadow-sm text-center text-gray-400">You have not joined any community savings groups yet.</div>
                <?php endif; ?>
            </div>
        </main>
    </div>

</body>
</html>