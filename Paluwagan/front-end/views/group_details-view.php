<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>TrustFund — Group Details</title>
    <link rel="stylesheet" href="../../assets/css/global.css" />
    <link rel="stylesheet" href="../../assets/css/group-details.css" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body class="flex min-h-screen text-gray-800 antialiased">

    <?php include "components/sidebar-view.php"; ?>

    <div class="flex-1 flex flex-col pl-64">
        <header class="h-16 bg-white border-b border-gray-200 flex items-center justify-between px-8 sticky top-0 z-10">
            <div class="flex items-center space-x-4">
                <button onclick="window.location.href='my_groups.php'" class="p-2 text-gray-600 hover:bg-gray-50 rounded-lg border border-gray-200"><i class="fa-solid fa-bars text-lg"></i></button>
                <h2 class="text-2xl font-medium text-gray-800">Group Details</h2>
            </div>
            <button class="p-2 text-gray-500 hover:text-gray-800 relative bg-gray-50 rounded-full border border-gray-200"><i class="fa-solid fa-bell text-lg"></i></button>
        </header>

        <main class="p-8 max-w-7xl w-full mx-auto space-y-6">
            
            <div class="bg-black text-white p-8 rounded-2xl shadow-xl relative overflow-hidden flex flex-col md:flex-row justify-between items-start md:items-center gap-6">
                <div>
                    <h2 class="text-3xl font-semibold mb-1"><?= htmlspecialchars($current_group['group_name']); ?></h2>
                    <p class="text-gray-400 text-sm mb-4"><?= htmlspecialchars($current_group['description'] ?: 'No description configured.'); ?></p>
                    
                    <div class="grid grid-cols-2 sm:grid-cols-5 gap-6 text-sm">
                        <div>
                            <p class="text-gray-400 text-xs uppercase tracking-wider font-medium">Contribution</p>
                            <p class="text-xl font-bold mt-0.5">₱<?= number_format($current_group['contribution_amount'], 0); ?></p>
                        </div>
                        <div>
                            <p class="text-gray-400 text-xs uppercase tracking-wider font-medium">Total Slots</p>
                            <p class="text-xl font-bold mt-0.5"><?= $slots_filled; ?>/<?= $current_group['cycle_length']; ?></p>
                        </div>
                        <div>
                            <p class="text-gray-400 text-xs uppercase tracking-wider font-medium">Collected</p>
                            <p class="text-xl font-bold mt-0.5">₱<?= number_format($total_collected, 0); ?></p>
                        </div>
                        <div>
                            <p class="text-gray-400 text-xs uppercase tracking-wider font-medium">Frequency</p>
                            <p class="text-xl font-bold mt-0.5 capitalize"><?= htmlspecialchars($current_group['frequency']); ?></p>
                        </div>
                        <div>
                            <p class="text-gray-400 text-xs uppercase tracking-wider font-medium">Status</p>
                            <p class="text-xl font-bold mt-0.5 text-emerald-400"><?= $current_group['is_active'] == 1 ? 'Active' : 'Closed'; ?></p>
                        </div>
                    </div>

                    <div class="mt-6 flex items-center space-x-2">
                        <span class="text-xs text-gray-400">Invite Code:</span>
                        <span class="bg-white/10 text-white font-mono text-xs px-3 py-1 rounded-lg border border-white/10 tracking-wider"><?= htmlspecialchars($current_group['invite_code']); ?></span>
                        <button class="text-xs bg-white/20 hover:bg-white/30 text-white px-2.5 py-1 rounded-lg transition font-medium">Copy</button>
                    </div>
                </div>
                
                <div class="flex flex-row md:flex-col gap-2 w-full md:w-auto">
                    <button onclick="openRecordPaymentModal()" class="w-full text-center bg-white/10 hover:bg-white/20 border border-white/15 text-white text-xs font-semibold px-4 py-2 rounded-xl transition">+Payment</button>
                    <button class="w-full text-center bg-white/10 hover:bg-white/20 border border-white/15 text-white text-xs font-semibold px-4 py-2 rounded-xl transition">+Payout</button>
                </div>
            </div>

            <div class="bg-gray-100 p-1.5 rounded-xl flex items-center space-x-1 max-w-md">
                <a href="group_details.php?id=<?= $group_id; ?>&tab=overview" class="flex-1 text-center py-2 text-sm font-medium rounded-lg transition <?= $active_tab === 'overview' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-500 hover:text-gray-800' ?>">Overview</a>
                <a href="#" class="flex-1 text-center py-2 text-sm font-medium rounded-lg text-gray-400 cursor-not-allowed">Members</a>
                <a href="#" class="flex-1 text-center py-2 text-sm font-medium rounded-lg text-gray-400 cursor-not-allowed">Schedule</a>
                <a href="group_details.php?id=<?= $group_id; ?>&tab=payments" class="flex-1 text-center py-2 text-sm font-medium rounded-lg transition <?= $active_tab === 'payments' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-500 hover:text-gray-800' ?>">Payments</a>
                <a href="#" class="flex-1 text-center py-2 text-sm font-medium rounded-lg text-gray-400 cursor-not-allowed">Chat</a>
            </div>

            <?php if ($active_tab === 'overview'): ?>
                <div class="grid grid-cols-1 sm:grid-cols-4 gap-6">
                    <div class="bg-white p-5 rounded-2xl border border-gray-200 shadow-sm">
                        <p class="text-xs font-medium text-gray-400 uppercase tracking-wide">Total Collected</p>
                        <p class="text-3xl font-semibold text-gray-900 mt-1">₱<?= number_format($total_collected, 0); ?></p>
                    </div>
                    <div class="bg-white p-5 rounded-2xl border border-gray-200 shadow-sm">
                        <p class="text-xs font-medium text-gray-400 uppercase tracking-wide">Total Paid Out</p>
                        <p class="text-3xl font-semibold text-emerald-600 mt-1">₱<?= number_format($total_paid_out, 0); ?></p>
                    </div>
                    <div class="bg-white p-5 rounded-2xl border border-gray-200 shadow-sm">
                        <p class="text-xs font-medium text-gray-400 uppercase tracking-wide">In Pool</p>
                        <p class="text-3xl font-semibold text-blue-600 mt-1">₱<?= number_format($in_pool, 0); ?></p>
                    </div>
                    <div class="bg-white p-5 rounded-2xl border border-gray-200 shadow-sm">
                        <p class="text-xs font-medium text-gray-400 uppercase tracking-wide">My Balance Due</p>
                        <p class="text-3xl font-semibold text-red-500 mt-1">₱<?= number_format($my_balance_due, 0); ?></p>
                    </div>
                </div>

                <div class="bg-white p-6 rounded-2xl border border-gray-200 shadow-sm">
                    <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-4">Upcoming Payouts</h3>
                    <div class="bg-red-50/60 border border-red-100 p-4 rounded-xl flex justify-between items-center text-sm">
                        <div class="flex items-center space-x-3">
                            <span class="w-6 h-6 rounded-md bg-red-100 text-red-600 flex items-center justify-center font-bold text-xs">1</span>
                            <div>
                                <p class="font-medium text-gray-900"><?= htmlspecialchars($full_name); ?> <span class="text-gray-400 text-xs font-normal">(You)</span></p>
                                <p class="text-xs text-gray-400">Scheduled: Within active cycle timeline execution parameters</p>
                            </div>
                        </div>
                        <span class="font-semibold text-gray-900">₱<?= number_format($current_group['contribution_amount'] * $current_group['cycle_length'], 0); ?></span>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($active_tab === 'payments'): ?>
                <div class="flex items-center space-x-3 mb-2">
                    <button onclick="openRecordPaymentModal()" class="bg-[#FF5722] hover:bg-orange-600 text-white font-medium text-sm px-4 py-2 rounded-xl transition shadow-sm">+Record Payment</button>
                    <button class="bg-gray-200 hover:bg-gray-300 text-gray-700 font-medium text-sm px-4 py-2 rounded-xl transition shadow-sm">+Record Payout</button>
                </div>

                <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-50 text-xs font-semibold text-gray-400 uppercase tracking-wider border-b border-gray-100">
                                <th class="p-4">Member</th>
                                <th class="p-4">Slot</th>
                                <th class="p-4">Total Paid</th>
                                <th class="p-4">Owed</th>
                                <th class="p-4">Balance</th>
                                <th class="p-4">Payout</th>
                            </tr>
                        </thead>
                        <tbody class="text-sm divide-y divide-gray-100">
                            <tr>
                                <td class="p-4 flex items-center space-x-3">
                                    <div class="w-8 h-8 rounded-full bg-orange-100 text-orange-700 font-bold text-xs flex items-center justify-center"><?= substr($full_name, 0, 2); ?></div>
                                    <span class="font-medium text-gray-900"><?= htmlspecialchars($full_name); ?></span>
                                </td>
                                <td class="p-4 text-gray-500">1</td>
                                <td class="p-4 font-medium text-emerald-600">₱<?= number_format($total_collected, 0); ?></td>
                                <td class="p-4 text-gray-900">₱<?= number_format($current_group['contribution_amount'], 0); ?></td>
                                <td class="p-4">
                                    <?php if($my_balance_due > 0): ?>
                                        <span class="text-red-500 font-medium">₱<?= number_format($my_balance_due, 0); ?> owed</span>
                                    <?php else: ?>
                                        <span class="text-emerald-600 font-medium">Settled</span>
                                    <?php endif; ?>
                                </td>
                                <td class="p-4">
                                    <div class="w-8 h-4 bg-gray-200 rounded-full relative cursor-not-allowed"><div class="w-3 h-3 bg-white rounded-full absolute top-0.5 left-0.5 shadow-sm"></div></div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <div id="paymentRecordModal" class="fixed inset-0 bg-black/40 backdrop-blur-sm hidden items-center justify-center z-50 transition-all">
        <div class="bg-white w-full max-w-md rounded-2xl shadow-2xl p-6 space-y-4 border border-gray-100">
            <div class="flex justify-between items-center pb-2 border-b border-gray-100">
                <h3 class="text-xl font-semibold text-gray-800">Record Payment</h3>
                <button onclick="closeRecordPaymentModal()" class="text-gray-400 hover:text-gray-600 p-1 rounded-lg hover:bg-gray-50 transition"><i class="fa-solid fa-xmark text-lg"></i></button>
            </div>
            
            <form action="process/process_record_payment.php" method="POST" class="space-y-4">
                <input type="hidden" name="group_id" value="<?= $group_id; ?>">
                <input type="hidden" name="member_id" value="<?= $my_member_id; ?>">
                
                <div>
                    <label class="block text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1">Member</label>
                    <div class="w-full border border-gray-200 rounded-xl px-4 py-2.5 bg-gray-50 text-gray-700 font-medium flex items-center justify-between">
                        <span><?= htmlspecialchars($full_name); ?></span>
                        <i class="fa-solid fa-chevron-down text-gray-400 text-xs"></i>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1">Amount (₱)</label>
                        <input type="number" name="amount" value="<?= (int)$current_group['contribution_amount']; ?>" step="0.01" required class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-gray-800 font-medium focus:outline-none focus:border-orange-500 bg-white">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1">Cycle #</label>
                        <input type="number" name="cycle_number" value="1" required class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-gray-800 font-medium focus:outline-none focus:border-orange-500 bg-white">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1">Payment Method</label>
                    <div class="relative">
                        <select name="payment_method" class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-gray-800 font-medium focus:outline-none focus:border-orange-500 bg-white appearance-none cursor-pointer">
                            <option value="cash">Cash</option>
                            <option value="gcash">GCash</option>
                            <option value="bank_transfer">Bank Transfer</option>
                        </select>
                        <span class="absolute inset-y-0 right-4 flex items-center pointer-events-none text-gray-400"><i class="fa-solid fa-chevron-down text-xs"></i></span>
                    </div>
                </div>

                <button type="submit" class="w-full bg-[#FF5722] hover:bg-orange-600 text-white font-medium py-3 rounded-xl transition shadow-md mt-2">Record Payment</button>
            </form>
        </div>
    </div>

    <script>
        function openRecordPaymentModal() {
            const modal = document.getElementById('paymentRecordModal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }
        function closeRecordPaymentModal() {
            const modal = document.getElementById('paymentRecordModal');
            modal.classList.remove('flex');
            modal.classList.add('hidden');
        }
    </script>
</body>
</html>