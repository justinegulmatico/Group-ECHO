<?php
session_start();
include "back-end/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$current_user_id = $_SESSION['user_id'];

// Get Logged-In Identity Context Metadata parameters
$user_query = "SELECT first_name, last_name, role FROM users WHERE user_id = '$current_user_id'";
$user_result = mysqli_query($conn, $user_query);
$user_data = mysqli_fetch_assoc($user_result);

$full_name = ($user_data) ? $user_data['first_name'] . " " . $user_data['last_name'] : "User";
$user_role = ($user_data) ? ucfirst($user_data['role']) : "Member";

$initials = "U";
if ($user_data) {
    $initials = strtoupper(substr($user_data['first_name'], 0, 1) . substr($user_data['last_name'], 0, 1));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TrustFund - Payments</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style> body { background-color: #FDFBF7; } </style>
</head>
<body class="flex min-h-screen text-gray-800 antialiased">

    <?php include "sidebar.php"; ?>

    <div class="flex-1 flex flex-col pl-64">
        <header class="h-16 bg-white border-b border-gray-200 flex items-center justify-between px-8 sticky top-0 z-10">
            <h2 class="text-2xl font-medium text-gray-800">Payments</h2>
            <button class="p-2 text-gray-500 hover:text-gray-800 bg-gray-50 rounded-full border border-gray-200 relative"><i class="fa-solid fa-bell text-lg"></i></button>
        </header>

        <main class="p-8 max-w-7xl w-full mx-auto space-y-6">
            <h3 class="text-xl font-medium text-gray-900">Payment History Ledger</h3>

            <div class="space-y-4">
                <?php
                // Sub-query routing loop pulls recorded transactions tied to active group context profiles
                $payments_log_q = "SELECT c.*, g.group_name FROM contributions c
                                   JOIN group_members m ON c.member_id = m.member_id
                                   JOIN cycles cy ON c.cycle_id = cy.cycle_id
                                   JOIN groups g ON cy.group_id = g.group_id
                                   WHERE m.user_id = '$current_user_id' ORDER BY c.contribution_id DESC";
                $payments_log_res = mysqli_query($conn, $payments_log_q);

                if(mysqli_num_rows($payments_log_res) > 0):
                    while($pay_row = mysqli_fetch_assoc($payments_log_res)):
                ?>
                    <div class="bg-white p-5 rounded-2xl border border-gray-200 shadow-sm flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                        <div class="space-y-1">
                            <h4 class="text-base font-semibold text-gray-800"><?= htmlspecialchars($pay_row['group_name']); ?></h4>
                            <span class="inline-block bg-emerald-50 text-emerald-700 text-[11px] font-medium px-2 py-0.5 rounded-md uppercase"><?= htmlspecialchars($pay_row['status']); ?></span>
                            <p class="text-xs text-gray-400 pt-1">Method: <span class="capitalize font-mono"><?= htmlspecialchars($pay_row['payment_method']); ?></span></p>
                        </div>
                        <div class="text-left sm:text-right">
                            <p class="text-xs font-medium text-gray-400">Paid Amount</p>
                            <p class="text-xl font-bold text-emerald-600 mt-0.5">₱<?= number_format($pay_row['amount'], 2); ?></p>
                        </div>
                    </div>
                <?php 
                    endwhile;
                else: 
                ?>
                    <div class="bg-white p-8 rounded-2xl border border-gray-200 text-center text-gray-400 text-sm">No transaction records logged inside this profile pipeline.</div>
                <?php endif; ?>
            </div>
        </main>
    </div>

</body>
</html>