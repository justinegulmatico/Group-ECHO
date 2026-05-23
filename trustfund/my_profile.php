<?php
session_start();
include "db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$current_user_id = $_SESSION['user_id'];
$update_success = false;

// Dynamic Form updates handling engine
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $occupation = mysqli_real_escape_string($conn, $_POST['occupation']);
    $address = mysqli_real_escape_string($conn, $_POST['address']);
    
    $update_query = "UPDATE users SET phone='$phone', occupation='$occupation', address='$address' WHERE user_id='$current_user_id'";
    if(mysqli_query($conn, $update_query)) {
        $update_success = true;
    }
}

// Fetch fresh, verified parameters post action routing
$user_query = "SELECT * FROM users WHERE user_id = '$current_user_id'";
$user_result = mysqli_query($conn, $user_query);
$user_data = mysqli_fetch_assoc($user_result);

$full_name = ($user_data) ? $user_data['first_name'] . " " . $user_data['last_name'] : "User";
$user_role = ($user_data) ? ucfirst($user_data['role']) : "Member";
$username = $user_data ? ($user_data['username'] ?? 'username') : "username";
$email = $user_data ? ($user_data['email'] ?? 'user@email.com') : "user@email.com";

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
    <title>TrustFund - My Profile</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style> body { background-color: #FDFBF7; } </style>
</head>
<body class="flex min-h-screen text-gray-800 antialiased">

    <?php include "sidebar.php"; ?>

    <div class="flex-1 flex flex-col pl-64">
        <header class="h-16 bg-white border-b border-gray-200 flex items-center justify-between px-8 sticky top-0 z-10">
            <h2 class="text-2xl font-medium text-gray-800">My Profile</h2>
            <button class="p-2 text-gray-500 hover:text-gray-800 bg-gray-50 rounded-full border border-gray-200 relative"><i class="fa-solid fa-bell text-lg"></i></button>
        </header>

        <main class="p-8 max-w-4xl w-full mx-auto space-y-6">
            
            <div class="bg-[#424242] text-white p-6 rounded-2xl shadow-sm flex items-center space-x-4">
                <div class="w-16 h-16 rounded-full bg-orange-100 text-orange-600 font-bold text-xl flex items-center justify-center shadow-inner">
                    <?= $initials; ?>
                </div>
                <div>
                    <h3 class="text-xl font-semibold"><?= htmlspecialchars($full_name); ?></h3>
                    <p class="text-gray-300 text-sm">@<?= htmlspecialchars($username); ?> · <?= htmlspecialchars($email); ?></p>
                    <span class="inline-block bg-emerald-500/20 text-emerald-400 text-[10px] px-2 py-0.5 rounded-md font-medium mt-1">Status Active</span>
                </div>
            </div>

            <div class="bg-white p-6 rounded-2xl border border-gray-200 shadow-sm space-y-4">
                <h4 class="text-sm font-semibold text-gray-500 uppercase tracking-wider">Personal Details</h4>
                
                <?php if($update_success): ?>
                    <p class="text-sm text-emerald-600 font-medium bg-emerald-50 p-2.5 rounded-xl border border-emerald-100">Profile metrics saved successfully.</p>
                <?php endif; ?>

                <form method="POST" class="space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1">Full Name</label>
                            <input type="text" value="<?= htmlspecialchars($full_name); ?>" disabled class="w-full border border-gray-200 rounded-xl px-4 py-2.5 bg-gray-50 text-gray-400 cursor-not-allowed font-medium">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1">Phone Number</label>
                            <input type="text" name="phone" value="<?= htmlspecialchars($user_data['phone'] ?? ''); ?>" placeholder="Enter phone number" class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-gray-800 font-medium focus:outline-none focus:border-orange-500 bg-white">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1">Occupation</label>
                            <input type="text" name="occupation" value="<?= htmlspecialchars($user_data['occupation'] ?? ''); ?>" placeholder="Enter occupation" class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-gray-800 font-medium focus:outline-none focus:border-orange-500 bg-white">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1">Address</label>
                            <input type="text" name="address" value="<?= htmlspecialchars($user_data['address'] ?? ''); ?>" placeholder="Enter street address" class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-gray-800 font-medium focus:outline-none focus:border-orange-500 bg-white">
                        </div>
                    </div>
                    <button type="submit" class="bg-[#FF5722] hover:bg-orange-600 text-white font-medium text-sm px-5 py-2.5 rounded-xl transition shadow-sm">Update Profile Information</button>
                </form>
            </div>
        </main>
    </div>

</body>
</html>