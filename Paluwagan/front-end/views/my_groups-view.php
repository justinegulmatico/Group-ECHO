<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TrustFund - My Groups</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style> body { background-color: #FDFBF7; } </style>
</head>
<body class="flex min-h-screen text-gray-800 antialiased">

    <!-- Corrected path: Targets the shared component in the local views/components folder -->
    <?php include "components/sidebar-view.php"; ?>

    <div class="flex-1 flex flex-col pl-64">
        <header class="h-16 bg-white border-b border-gray-200 flex items-center justify-between px-8 sticky top-0 z-10">
            <div class="flex items-center space-x-4">
                <h2 class="text-2xl font-medium text-gray-800">My Groups</h2>
            </div>
            <button class="p-2 text-gray-500 hover:text-gray-800 relative bg-gray-50 rounded-full border border-gray-200">
                <i class="fa-solid fa-bell text-lg"></i>
            </button>
        </header>

        <main class="p-8 max-w-7xl w-full mx-auto space-y-6">
            
            <?php if (isset($error_message)): ?>
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl text-sm"><?= $error_message; ?></div>
            <?php endif; ?>
            <?php if (isset($_GET['success'])): ?>
                <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-xl text-sm"><?= htmlspecialchars($_GET['success']); ?></div>
            <?php endif; ?>

            <div class="flex flex-col sm:flex-row justify-between items-stretch sm:items-center gap-4">
                <div class="relative flex-1 max-w-md">
                    <span class="absolute inset-y-0 left-4 flex items-center text-gray-400"><i class="fa-solid fa-magnifying-glass text-sm"></i></span>
                    <input type="text" placeholder="Search groups..." class="w-full pl-11 pr-4 py-2 border border-gray-200 rounded-xl bg-white text-sm text-gray-700 placeholder-gray-400 focus:outline-none focus:border-orange-500">
                </div>
                <div class="flex items-center space-x-2">
                    <button onclick="toggleJoinModal(true)" class="bg-gray-200 hover:bg-gray-300 text-gray-700 font-medium text-sm px-4 py-2 rounded-xl transition">Join with code</button>
                    <button onclick="toggleCreateModal(true)" class="bg-[#FF5722] hover:bg-orange-600 text-white font-medium text-sm px-4 py-2 rounded-xl shadow-sm transition">+ Create Group</button>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php if (mysqli_num_rows($groups_res) > 0): ?>
                    <?php while ($group = mysqli_fetch_assoc($groups_res)):
                        $progress = 0; 
                        $status_label = ($group['is_active'] == 1) ? 'active' : 'closed';
                        $status_badge = ($group['is_active'] == 1) ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-gray-100 text-gray-500 border-gray-200';
                    ?>
                        <!-- Corrected path: Script executes from back-end/php/, so it links directly to group_details.php -->
                        <div onclick="window.location.href='group_details.php?id=<?= $group['group_id']; ?>'" class="bg-white p-5 rounded-2xl border border-gray-200 shadow-sm flex flex-col justify-between hover:border-orange-200 transition duration-150 cursor-pointer">
                            <div>
                                <div class="flex justify-between items-start mb-1">
                                    <h4 class="text-lg font-semibold text-gray-900 truncate max-w-[180px]"><?= htmlspecialchars($group['group_name']); ?></h4>
                                    <span class="text-[11px] px-2 py-0.5 rounded-md font-medium tracking-wide border <?= $status_badge; ?> uppercase"><?= $status_label; ?></span>
                                </div>
                                <p class="text-xs text-gray-400 mb-1 font-mono tracking-wider">Invite Code: <?= htmlspecialchars($group['invite_code'] ?? '—'); ?></p>
                                <p class="text-xs text-gray-500 mb-3 capitalize font-medium"><?= htmlspecialchars($group['frequency'] ?? 'monthly'); ?> · ₱<?= number_format($group['contribution_amount'], 0); ?>/cycle</p>
                                
                                <div class="flex justify-between text-[11px] text-gray-400 font-medium mb-1">
                                    <span>₱0 collected</span>
                                    <span><?= $progress; ?>%</span>
                                </div>
                                <div class="w-full bg-amber-50 h-1.5 rounded-full overflow-hidden">
                                    <div class="bg-[#FFDCA9] h-full rounded-full w-0"></div>
                                </div>
                            </div>

                            <div class="mt-6 flex items-center justify-between">
                                <div class="w-6 h-6 rounded-full bg-rose-100 text-rose-600 font-bold text-[10px] flex items-center justify-center">TF</div>
                                <span class="text-xs text-gray-400 font-medium"><?= $group['members_count']; ?> / <?= intval($group['cycle_length']); ?> members</span>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="col-span-full bg-white p-12 rounded-2xl border border-gray-200 text-center text-gray-400 text-sm font-medium">No active savings circles found. Click "+ Create Group" to spin up a new custom vault!</div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <div id="createGroupModal" class="hidden fixed inset-0 bg-black/50 z-50 items-center justify-center p-4">
        <div class="bg-white w-full max-w-md p-6 rounded-2xl shadow-xl space-y-4">
            <div class="flex justify-between items-center">
                <h3 class="text-xl font-bold text-gray-900">Create Savings Group</h3>
                <button onclick="toggleCreateModal(false)" class="text-gray-400 hover:text-gray-600 text-2xl">&times;</button>
            </div>
            <!-- Submits to the parent PHP script in the same directory -->
            <form method="POST" action="my_groups.php" class="space-y-4">
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Group Title</label>
                    <input type="text" name="group_name" required placeholder="e.g., Office Savings Pool" class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:border-orange-500">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Contribution (₱)</label>
                        <input type="number" name="contribution" required placeholder="1000" min="50" class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:border-orange-500">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Max Capacity (Slots)</label>
                        <input type="number" name="cycle_length" required placeholder="10" min="2" max="20" class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:border-orange-500">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Rotation Frequency</label>
                    <select name="frequency" class="w-full border border-gray-200 bg-white rounded-xl px-3 py-2 text-sm focus:outline-none focus:border-orange-500 cursor-pointer">
                        <option value="monthly">Monthly</option>
                        <option value="weekly">Weekly</option>
                    </select>
                </div>
                <button type="submit" name="action_create_group" class="w-full bg-[#FF5722] hover:bg-orange-600 text-white font-medium text-sm py-2.5 rounded-xl transition">Launch Group Circle</button>
            </form>
        </div>
    </div>

    <div id="joinGroupModal" class="hidden fixed inset-0 bg-black/50 z-50 items-center justify-center p-4">
        <div class="bg-white w-full max-w-md p-6 rounded-2xl shadow-xl space-y-4">
            <div class="flex justify-between items-center">
                <h3 class="text-xl font-bold text-gray-900">Join Circle Group</h3>
                <button onclick="toggleJoinModal(false)" class="text-gray-400 hover:text-gray-600 text-2xl">&times;</button>
            </div>
            <!-- Submits to the parent PHP script in the same directory -->
            <form method="POST" action="my_groups.php" class="space-y-4">
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Invite Code</label>
                    <input type="text" name="target_invite_code" required placeholder="e.g., 1KUOD5" class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm font-mono uppercase tracking-widest focus:outline-none focus:border-orange-500">
                </div>
                <button type="submit" name="action_join_group" class="w-full bg-gray-800 hover:bg-gray-900 text-white font-medium text-sm py-2.5 rounded-xl transition">Enter Savings Group</button>
            </form>
        </div>
    </div>

    <script>
    function toggleCreateModal(show) {
        const modal = document.getElementById('createGroupModal');
        modal.classList.toggle('hidden', !show);
        modal.classList.toggle('flex', show);
    }
    function toggleJoinModal(show) {
        const modal = document.getElementById('joinGroupModal');
        modal.classList.toggle('hidden', !show);
        modal.classList.toggle('flex', show);
    }
    </script>
</body>
</html>