<?php
// Ensure session is started to read user info securely
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$current_page = basename($_SERVER['PHP_SELF']);

// ─── SAFE SESSION RECOVERY FOR USER PROFILE CONTAINER ───
// Prioritizes explicit session properties, falling back to database column shapes
$first_name = $_SESSION['first_name'] ?? ($_SESSION['username'] ?? 'arvin');
$last_name  = $_SESSION['last_name'] ?? '';
$full_name  = !empty($last_name) ? "$first_name $last_name" : $first_name;

$user_role  = $_SESSION['role'] ?? 'Member';

// Generate profile initials dynamically matching user badge layout grids
$initials   = strtoupper(substr($first_name, 0, 1) . (!empty($last_name) ? substr($last_name, 0, 1) : ''));
if (empty($initials)) { 
    $initials = !empty($first_name) ? strtoupper(substr($first_name, 0, 2)) : "US"; 
}

// Check if current view is nested deeper inside the process directory folder structure
$is_nested_process = (strpos($_SERVER['PHP_SELF'], '/process/') !== false);
$prefix = $is_nested_process ? '../' : '';
?>
<aside class="w-64 bg-white border-r border-gray-200 flex flex-col justify-between fixed h-full z-10">
    <div>
        <div class="p-6 border-b border-gray-100">
            <h1 class="text-2xl font-serif font-semibold tracking-wide text-gray-900">TrustFund</h1>
        </div>
        
        <nav class="p-4 space-y-1">
            <p class="px-3 text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Main</p>
            
            <a href="<?= $prefix ?>dashboard.php" class="flex items-center space-x-3 px-3 py-2.5 rounded-lg transition <?= ($current_page == 'dashboard.php') ? 'bg-orange-50 text-orange-600 font-medium' : 'text-gray-600 hover:bg-gray-50' ?>">
                <i class="fa-solid fa-house text-lg"></i><span>Dashboard</span>
            </a>
            
            <a href="<?= $prefix ?>my_groups.php" class="flex items-center space-x-3 px-3 py-2.5 rounded-lg transition <?= ($current_page == 'my_groups.php' || $current_page == 'group_details.php' || $current_page == 'process_create_group.php' || $current_page == 'process_join_group.php') ? 'bg-orange-50 text-orange-600 font-medium' : 'text-gray-600 hover:bg-gray-50' ?>">
                <i class="fa-solid fa-users text-lg"></i><span>My Groups</span>
            </a>
            
            <a href="<?= $prefix ?>payments.php" class="flex items-center space-x-3 px-3 py-2.5 rounded-lg transition <?= ($current_page == 'payments.php') ? 'bg-orange-50 text-orange-600 font-medium' : 'text-gray-600 hover:bg-gray-50' ?>">
                <i class="fa-solid fa-wallet text-lg"></i><span>Payments</span>
            </a>
            
            <a href="<?= $prefix ?>my_profile.php" class="flex items-center space-x-3 px-3 py-2.5 rounded-lg transition <?= ($current_page == 'my_profile.php') ? 'bg-orange-50 text-orange-600 font-medium' : 'text-gray-600 hover:bg-gray-50' ?>">
                <i class="fa-solid fa-user text-lg"></i><span>My Profile</span>
            </a>

            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                <div class="pt-4 mt-4 border-t border-gray-100">
                    <p class="px-3 text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Admin</p>
                    <a href="<?= $prefix ?>admin.php" class="flex items-center space-x-3 px-3 py-2.5 rounded-lg transition <?= ($current_page == 'admin.php' || $current_page == 'admin_process.php') ? 'bg-orange-50 text-orange-600 font-medium' : 'text-gray-600 hover:bg-gray-50' ?>">
                        <i class="fa-solid fa-shield-halved text-lg"></i><span>Admin Panel</span>
                    </a>
                </div>
            <?php endif; ?>
        </nav>
    </div>

    <div class="p-4 border-t border-gray-200 flex items-center justify-between bg-white">
        <div class="flex items-center space-x-3">
            <div class="w-10 h-10 rounded-full bg-orange-200 text-orange-700 font-semibold text-sm flex items-center justify-center tokens-avatar">
                <?= htmlspecialchars($initials); ?>
            </div>
            <div>
                <h4 class="text-sm font-semibold text-gray-900 truncate max-w-[110px]" title="<?= htmlspecialchars($full_name); ?>">
                    <?= htmlspecialchars($full_name); ?>
                </h4>
                <p class="text-xs text-gray-500 capitalize"><?= htmlspecialchars($user_role); ?></p>
            </div>
        </div>
        <a href="<?= $prefix ?>logout.php" class="p-2 border border-gray-200 text-gray-500 hover:text-red-600 hover:bg-red-50 rounded-xl transition" title="Sign Out">
            <i class="fa-solid fa-arrow-right-from-bracket text-lg"></i>
        </a>
    </div>
</aside>