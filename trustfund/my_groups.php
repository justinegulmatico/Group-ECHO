<?php
session_start();
include "db.php";

// 1. Force safety checkpoint redirect if no logged-in user session exists
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$current_user_id = $_SESSION['user_id'];

// Get Logged-in User Profile Context
$user_query = "SELECT first_name, last_name, role FROM users WHERE user_id = '$current_user_id'";
$user_result = mysqli_query($conn, $user_query);
$user_data = mysqli_fetch_assoc($user_result);
$full_name = ($user_data) ? $user_data['first_name'] . " " . $user_data['last_name'] : "User";
$user_role = ($user_data) ? ucfirst($user_data['role']) : "Member";

$initials = "U";
if ($user_data) {
    $initials = strtoupper(substr($user_data['first_name'], 0, 1) . substr($user_data['last_name'], 0, 1));
}

// ─── ACTION A: PROCESSING THE "+ CREATE GROUP" FORM SUBMISSION ───
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action_create_group'])) {
    $group_name = mysqli_real_escape_string($conn, trim($_POST['group_name']));
    $contribution_amount = floatval($_POST['contribution']);
    $frequency = mysqli_real_escape_string($conn, $_POST['frequency']);
    $cycle_length = intval($_POST['cycle_length']); // Track max pool slot limit capacity
    
    // Generate unique alphanumeric 6-character invitation code string
    $invite_code = strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 6));

    // Maps exactly to your DESC groups terminal layout schema blueprint keys
    $insert_group = "INSERT INTO groups (group_name, description, contribution_amount, frequency, cycle_length, invite_code, is_active, created_by) 
                     VALUES ('$group_name', 'TrustFund Paluwagan Savings Pool Group Circle.', '$contribution_amount', '$frequency', '$cycle_length', '$invite_code', 1, '$current_user_id')";

    if (mysqli_query($conn, $insert_group)) {
        $new_group_id = mysqli_insert_id($conn);

        // Auto-join the creator as an active member inside their group pool tracker layout matrix
        $join_creator = "INSERT INTO group_members (user_id, group_id, status) VALUES ('$current_user_id', '$new_group_id', 'active')";
        mysqli_query($conn, $join_creator);

        header("Location: my_groups.php?success=" . urlencode("Group created successfully! Invite Code: $invite_code"));
        exit();
    } else {
        $error_message = "Database writing fault failure string: " . mysqli_error($conn);
    }
}

// ─── ACTION B: PROCESSING THE "JOIN WITH CODE" FORM SUBMISSION ───
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action_join_group'])) {
    $target_code = strtoupper(trim(mysqli_real_escape_string($conn, $_POST['target_invite_code'])));
    
    // Search database for active matching group tokens
    $lookup = mysqli_query($conn, "SELECT * FROM groups WHERE invite_code = '$target_code' AND is_active = 1 LIMIT 1");
    
    if (mysqli_num_rows($lookup) > 0) {
        $group_row = mysqli_fetch_assoc($lookup);
        $found_group_id = $group_row['group_id'];
        
        // Prevent duplicate group records entry exceptions
        $duplicate_check = mysqli_query($conn, "SELECT * FROM group_members WHERE group_id = '$found_group_id' AND user_id = '$current_user_id'");
        
        if (mysqli_num_rows($duplicate_check) == 0) {
            mysqli_query($conn, "INSERT INTO group_members (group_id, user_id, status) VALUES ('$found_group_id', '$current_user_id', 'active')");
            header("Location: my_groups.php?success=" . urlencode("Successfully joined the savings pool!"));
            exit();
        } else {
            $error_message = "You are already a registered active member of this circle group!";
        }
    } else {
        $error_message = "Invalid or inactive invitation code. Please verify parameter values.";
    }
}
?>
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

    <aside class="w-64 bg-white border-r border-gray-200 flex flex-col justify-between fixed h-full z-10">
        <div>
            <div class="p-6 border-b border-gray-100">
                <h1 class="text-2xl font-serif font-semibold tracking-wide text-gray-900">TrustFund</h1>
            </div>
            <nav class="p-4 space-y-1">
                <p class="px-3 text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Main</p>
                <a href="dashboard.php" class="flex items-center space-x-3 px-3 py-2.5 rounded-lg text-gray-600 hover:bg-gray-50 transition">
                    <i class="fa-solid fa-house text-lg"></i><span>Dashboard</span>
                </a>
                <a href="my_groups.php" class="flex items-center space-x-3 px-3 py-2.5 rounded-lg bg-orange-50 text-orange-600 font-medium">
                    <i class="fa-solid fa-users text-lg"></i><span>My Groups</span>
                </a>
                <a href="payments.php" class="flex items-center space-x-3 px-3 py-2.5 rounded-lg text-gray-600 hover:bg-gray-50 transition">
                    <i class="fa-solid fa-wallet text-lg"></i><span>Payments</span>
                </a>
                <a href="my_profile.php" class="flex items-center space-x-3 px-3 py-2.5 rounded-lg text-gray-600 hover:bg-gray-50 transition">
                    <i class="fa-solid fa-user text-lg"></i><span>My Profile</span>
                </a>
                <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                    <div class="pt-4 mt-4 border-t border-gray-100">
                        <p class="px-3 text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Admin</p>
                        <a href="admin.php" class="flex items-center space-x-3 px-3 py-2.5 rounded-lg text-gray-600 hover:bg-gray-50 transition">
                            <i class="fa-solid fa-shield-halved text-lg"></i><span>Admin Panel</span>
                        </a>
                    </div>
                <?php endif; ?>
            </nav>
        </div>
        <div class="p-4 border-t border-gray-200 flex items-center justify-between bg-white">
            <div class="flex items-center space-x-3">
                <div class="w-10 h-10 rounded-full bg-orange-200 text-orange-700 font-semibold text-sm flex items-center justify-center"><?= $initials; ?></div>
                <div>
                    <h4 class="text-sm font-semibold text-gray-900 truncate max-w-[110px]"><?= htmlspecialchars($full_name); ?></h4>
                    <p class="text-xs text-gray-500"><?= $user_role; ?></p>
                </div>
            </div>
            <a href="logout.php" class="p-2 border border-gray-200 text-gray-500 hover:text-red-600 hover:bg-red-50 rounded-xl transition"><i class="fa-solid fa-arrow-right-from-bracket text-lg"></i></a>
        </div>
    </aside>

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
                <?php
                // Fetches active user group association records safely based on your exact definitions
                $groups_query = "SELECT g.*, 
                                 (SELECT COUNT(*) FROM group_members WHERE group_id = g.group_id AND status = 'active') as members_count 
                                 FROM groups g 
                                 JOIN group_members m ON g.group_id = m.group_id 
                                 WHERE m.user_id = '$current_user_id' AND m.status = 'active'";
                $groups_res = mysqli_query($conn, $groups_query);

                if (mysqli_num_rows($groups_res) > 0):
                    while ($group = mysqli_fetch_assoc($groups_res)):
                        $progress = 0; 
                        $status_label = ($group['is_active'] == 1) ? 'active' : 'closed';
                        $status_badge = ($group['is_active'] == 1) ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-gray-100 text-gray-500 border-gray-200';
                ?>
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
                <?php 
                    endwhile;
                else: 
                ?>
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