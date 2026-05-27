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

    <?php include "components/sidebar-view.php"; ?>

    <div class="flex-1 flex flex-col pl-64">
        <header class="h-16 bg-white border-b border-gray-200 flex items-center justify-between px-8 sticky top-0 z-10">
            <h2 class="text-2xl font-medium text-gray-800">Payments</h2>
            <button class="p-2 text-gray-500 hover:text-gray-800 bg-gray-50 rounded-full border border-gray-200 relative"><i class="fa-solid fa-bell text-lg"></i></button>
        </header>

        <main class="p-8 max-w-7xl w-full mx-auto space-y-6">
            <h3 class="text-xl font-medium text-gray-900">Payment History Ledger</h3>

            <div class="space-y-4">
                <?php if (mysqli_num_rows($payments_log_res) > 0): ?>
                    <?php while ($pay_row = mysqli_fetch_assoc($payments_log_res)): ?>
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
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="bg-white p-8 rounded-2xl border border-gray-200 text-center text-gray-400 text-sm">No transaction records logged inside this profile pipeline.</div>
                <?php endif; ?>
            </div>
        </main>
    </div>

</body>
</html>