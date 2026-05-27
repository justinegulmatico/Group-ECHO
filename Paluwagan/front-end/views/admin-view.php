<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>TrustFund — Admin Panel</title>
    <link rel="stylesheet" href="../../assets/css/global.css" />
    <link rel="stylesheet" href="../../assets/css/admin-panel.css" />
</head>
<body class="flex min-h-screen text-gray-800 antialiased">

    <?php include "components/sidebar-view.php"; ?>

    <div class="flex-1 flex flex-col pl-64">
        <header class="h-16 bg-white border-b border-gray-200 flex items-center justify-between px-8 sticky top-0 z-10">
            <div class="flex items-center space-x-3">
                <button class="p-1.5 text-gray-500 bg-gray-50 border border-gray-200 rounded-lg hover:bg-gray-100">
                    <i class="fa-solid fa-bars text-md"></i>
                </button>
                <h2 class="text-xl font-medium text-gray-800">Admin Panel</h2>
            </div>
            <button class="p-2 text-gray-500 hover:text-gray-800 relative bg-gray-50 rounded-full border border-gray-200">
                <i class="fa-solid fa-bell text-lg"></i>
            </button>
        </header>

        <main class="p-8 max-w-7xl w-full mx-auto space-y-8">
            
            <?php if (isset($_GET['success'])): ?>
                <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-xl text-sm">
                    <?= htmlspecialchars($_GET['success']); ?>
                </div>
            <?php endif; ?>

            <div class="bg-[#2D2D2D] text-white rounded-2xl p-6 flex items-center space-x-4 shadow-sm">
                <div class="w-12 h-12 rounded-xl bg-orange-500/10 border border-orange-500 flex items-center justify-center text-orange-500 text-xl">
                    <i class="fa-solid fa-shield-halved"></i>
                </div>
                <div>
                    <h3 class="text-xl font-semibold tracking-wide">Admin Panel</h3>
                    <p class="text-sm text-gray-400">Manage users, verify accounts, oversee all groups</p>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                <div class="bg-white p-5 rounded-2xl border border-gray-200 shadow-sm">
                    <p class="text-sm font-medium text-gray-400">Total Users</p>
                    <h4 class="text-4xl font-normal mt-2 text-gray-900"><?= $total_users; ?></h4>
                </div>
                <div class="bg-white p-5 rounded-2xl border border-gray-200 shadow-sm">
                    <p class="text-sm font-medium text-gray-400">Pending Verification</p>
                    <h4 class="text-4xl font-normal mt-2 text-amber-600"><?= $pending_verifications; ?></h4>
                </div>
                <div class="bg-white p-5 rounded-2xl border border-gray-200 shadow-sm">
                    <p class="text-sm font-medium text-gray-400">Total Groups</p>
                    <h4 class="text-4xl font-normal mt-2 text-gray-900"><?= $total_groups; ?></h4>
                </div>
                <div class="bg-white p-5 rounded-2xl border border-gray-200 shadow-sm">
                    <p class="text-sm font-medium text-gray-400">Active Groups</p>
                    <h4 class="text-4xl font-normal mt-2 text-emerald-600"><?= $active_groups; ?></h4>
                </div>
            </div>

            <div class="space-y-3">
                <h3 class="text-lg font-semibold text-gray-900">All Users</h3>
                <div class="bg-white border border-gray-200 rounded-2xl overflow-hidden shadow-sm">
                    <table class="w-full text-left border-collapse text-sm">
                        <thead>
                            <tr class="bg-gray-50 border-b border-gray-200 text-xs font-semibold uppercase tracking-wider text-gray-400">
                                <th class="px-6 py-3.5">User</th>
                                <th class="px-6 py-3.5">Email</th>
                                <th class="px-6 py-3.5">Status</th>
                                <th class="px-6 py-3.5">Joined</th>
                                <th class="px-6 py-3.5">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 font-medium">
                            <?php if (mysqli_num_rows($users_res) > 0): ?>
                                <?php while ($row = mysqli_fetch_assoc($users_res)): 
                                    $user_initial = strtoupper(substr($row['first_name'], 0, 1));
                                    $status_label = "suspended"; 
                                    $status_class = "bg-rose-50 text-rose-600 border-rose-100";
                                ?>
                                    <tr class="hover:bg-gray-50/50 transition">
                                        <td class="px-6 py-4 flex items-center space-x-3">
                                            <div class="w-9 h-9 rounded-full bg-orange-100 text-orange-700 flex items-center justify-center font-bold text-xs">
                                                <?= $user_initial; ?>
                                            </div>
                                            <div>
                                                <p class="text-gray-900 font-semibold"><?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></p>
                                                <p class="text-xs text-gray-400 font-normal">@<?= htmlspecialchars($row['username']); ?></p>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-gray-500 font-normal"><?= htmlspecialchars($row['email']); ?></td>
                                        <td class="px-6 py-4">
                                            <span class="text-xs px-2.5 py-0.5 border rounded-md font-semibold tracking-wide <?= $status_class; ?>"><?= $status_label; ?></span>
                                        </td>
                                        <td class="px-6 py-4 text-gray-400 font-normal"><?= date('m/d/Y', strtotime($row['created_at'])); ?></td>
                                        <td class="px-6 py-4">
                                            <a href="process/admin_process.php?activate_user=<?= $row['user_id']; ?>" class="text-xs bg-emerald-100 border border-emerald-200 text-emerald-700 px-3 py-1 rounded-lg hover:bg-emerald-200 transition">Activate</a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="5" class="px-6 py-8 text-center text-gray-400">No active members found in the database system logs.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="space-y-3">
                <div class="flex justify-between items-center">
                    <h3 class="text-lg font-semibold text-gray-900">All Groups</h3>
                    <button class="bg-[#FF5722] hover:bg-orange-600 text-white font-medium text-xs px-4 py-2 rounded-xl transition shadow-sm">+ Create Group</button>
                </div>
                <div class="bg-white border border-gray-200 rounded-2xl overflow-hidden shadow-sm">
                    <table class="w-full text-left border-collapse text-sm">
                        <thead>
                            <tr class="bg-gray-50 border-b border-gray-200 text-xs font-semibold uppercase tracking-wider text-gray-400">
                                <th class="px-6 py-3.5">Group</th>
                                <th class="px-6 py-3.5">Owner</th>
                                <th class="px-6 py-3.5">Members</th>
                                <th class="px-6 py-3.5">Collected</th>
                                <th class="px-6 py-3.5">Status</th>
                                <th class="px-6 py-3.5">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 font-medium">
                            <?php if (mysqli_num_rows($groups_res) > 0): ?>
                                <?php while ($group = mysqli_fetch_assoc($groups_res)): 
                                    $group_status = ($group['is_active'] == 1) ? 'active' : 'closed';
                                    $group_status_class = ($group['is_active'] == 1) ? 'bg-emerald-50 text-emerald-700 border-emerald-100' : 'bg-gray-100 text-gray-600 border-gray-200';
                                ?>
                                    <tr class="hover:bg-gray-50/50 transition">
                                        <td class="px-6 py-4">
                                            <p class="text-gray-900 font-semibold"><?= htmlspecialchars($group['group_name']); ?></p>
                                            <p class="text-xs text-gray-400 font-mono font-normal">Code: <?= htmlspecialchars($group['invite_code']); ?></p>
                                        </td>
                                        <td class="px-6 py-4 text-gray-600 font-normal"><?= htmlspecialchars($group['first_name'] . ' ' . $group['last_name']); ?></td>
                                        <td class="px-6 py-4 text-gray-500 font-normal"><?= $group['total_members']; ?>/<?= $group['cycle_length']; ?></td>
                                        <td class="px-6 py-4 text-gray-900 font-semibold">₱0</td>
                                        <td class="px-6 py-4">
                                            <span class="text-xs px-2.5 py-0.5 border rounded-md font-semibold tracking-wide uppercase <?= $group_status_class; ?>"><?= $group_status; ?></span>
                                        </td>
                                        <td class="px-6 py-4 flex items-center space-x-2">
                                            <a href="group_details.php?id=<?= $group['group_id']; ?>" class="text-xs bg-gray-100 text-gray-700 px-3 py-1.5 rounded-lg border border-gray-200 hover:bg-gray-200 transition">View</a>
                                            
                                            <form method="POST" action="process/admin_process.php" class="inline-flex items-center">
                                                <input type="hidden" name="group_id" value="<?= $group['group_id']; ?>">
                                                <select name="group_status" onchange="this.form.submit()" class="text-xs bg-white text-gray-500 px-2 py-1.5 border border-gray-200 rounded-lg cursor-pointer focus:outline-none focus:border-orange-500">
                                                    <option value="" disabled selected>Change status</option>
                                                    <option value="active">Active</option>
                                                    <option value="closed">Closed</option>
                                                </select>
                                                <input type="hidden" name="action_update_group_status" value="1">
                                            </form>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="6" class="px-6 py-8 text-center text-gray-400">No savings vaults have been generated yet.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</body>
</html>