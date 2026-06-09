<?php
/**
 * Group-ECHO / Paluwagan - Demo Data Seeder
 * 
 * Generates realistic 3-year usage history for presentation/demo.
 * Target: 120+ users (many active), 25-30 groups (mostly public), 
 *         thousands of contributions, payouts, transactions, wallet activity.
 *
 * Usage (after importing trustfund_oltp.sql schema):
 *   1. Start XAMPP (Apache + MySQL)
 *   2. Via browser (easiest for progress):
 *        http://localhost/Group-ECHO/sql/seed_demo_data.php?reset=1
 *   3. Or CLI: C:\xampp\php\php.exe sql\seed_demo_data.php
 *
 * After seeding:
 *   - Login with any seeded user. All demo users use the password hash: $2y$10$GKfh18Ysah5fH9O7w2o0puPVdUo3E7hREQAR52DgPuPpLP8tD891u
 *   - Go to admin (set one user role='admin' if needed, or use pre-created admins)
 *   - Run ETL / OLAP sync from Admin > Analytics to populate charts with 3 years of data.
 *
 * This script is idempotent-friendly: use ?reset=1 or $RESET_FIRST = true to wipe previous demo data.
 */

date_default_timezone_set('Asia/Manila');

// === PERFORMANCE FOR LARGE SEED ===
set_time_limit(0);
@ini_set('max_execution_time', 0);
@ini_set('memory_limit', '512M');
// Prevent browser output buffering from killing us on long runs
@ini_set('output_buffering', 'off');

$DB_HOST = 'localhost';
$DB_USER = 'root';
$DB_PASS = '';
$DB_NAME = 'trustfund_db';

$RESET_FIRST = isset($_GET['reset']) && $_GET['reset'] == '1';   // ?reset=1 to clear old data first
$VERBOSE     = true;

function log_msg($msg, $force_flush = false) {
    global $VERBOSE;
    if ($VERBOSE) {
        echo htmlspecialchars($msg) . "<br>\n";
        if (php_sapi_name() === 'cli') echo $msg . "\n";
        if ($force_flush || (mt_rand(1, 8) === 1)) {
            @flush();
        }
    }
}

function connect() {
    global $DB_HOST, $DB_USER, $DB_PASS, $DB_NAME;
    $conn = mysqli_connect($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
    if (!$conn) {
        die("Connection failed: " . mysqli_connect_error());
    }
    mysqli_set_charset($conn, "utf8mb4");
    return $conn;
}

function ensure_support_tables($conn) {
    // group_history (used extensively by app for activity feed)
    mysqli_query($conn, "
        CREATE TABLE IF NOT EXISTS `group_history` (
          `history_id` int(11) NOT NULL AUTO_INCREMENT,
          `group_id` int(11) NOT NULL,
          `event_type` varchar(50) NOT NULL,
          `actor_user_id` int(11) DEFAULT NULL,
          `target_user_id` int(11) DEFAULT NULL,
          `cycle_number` int(11) DEFAULT NULL,
          `amount` decimal(10,2) DEFAULT NULL,
          `description` text,
          `created_at` datetime NOT NULL DEFAULT current_timestamp(),
          PRIMARY KEY (`history_id`),
          KEY `group_id` (`group_id`),
          KEY `event_type` (`event_type`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
    ");

    // wallet_requests (core for balances + payouts)
    mysqli_query($conn, "
        CREATE TABLE IF NOT EXISTS `wallet_requests` (
          `request_id` int(11) NOT NULL AUTO_INCREMENT,
          `user_id` int(11) NOT NULL,
          `type` enum('deposit','withdraw') NOT NULL,
          `amount` decimal(10,2) NOT NULL,
          `payment_method` varchar(60) DEFAULT NULL,
          `account_details` text DEFAULT NULL,
          `attachment` varchar(255) DEFAULT NULL,
          `status` enum('pending','approved','declined') NOT NULL DEFAULT 'pending',
          `created_at` datetime NOT NULL DEFAULT current_timestamp(),
          `reviewed_by` int(11) DEFAULT NULL,
          `reviewed_at` datetime DEFAULT NULL,
          PRIMARY KEY (`request_id`),
          KEY `user_id` (`user_id`),
          KEY `status` (`status`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
    ");
}

function reset_demo_data($conn) {
    log_msg("Resetting previous demo data (respecting FKs)...");

    // Order matters due to FKs
    $tables = [
        'transactions',
        'payouts',
        'contributions',
        'cycles',
        'group_members',
        'group_history',
        'wallet_requests',
        'user_verifications',
        'groups',
        'users',
    ];

    foreach ($tables as $t) {
        // Use TRUNCATE when possible, fallback to DELETE
        if (@mysqli_query($conn, "TRUNCATE TABLE `$t`")) {
            log_msg("  Truncated $t");
        } else {
            mysqli_query($conn, "DELETE FROM `$t`");
            mysqli_query($conn, "ALTER TABLE `$t` AUTO_INCREMENT = 1");
            log_msg("  Deleted from $t");
        }
    }
    log_msg("Reset complete.\n");
}

// ============== DATA GENERATORS ==============

$FIRST_NAMES = [
    "Juan","Maria","Jose","Ana","Pedro","Sofia","Miguel","Isabella","Carlos","Luna",
    "Andres","Camila","Luis","Valentina","Diego","Gabriela","Fernando","Elena","Ricardo","Natalia",
    "Paolo","Bianca","Enrique","Carmen","Ramon","Patricia","Angelo","Monica","Francis","Angela",
    "Emilio","Julia","Rafael","Beatriz","Victor","Clara","Oscar","Diana","Manuel","Rosa",
    "Alfonso","Teresa","Ignacio","Lourdes","Sergio","Pilar","Raul","Mercedes","Javier","Consuelo",
    "Elijah","Sophia","Liam","Olivia","Noah","Emma","Lucas","Ava","Mason","Isla",
    "Ethan","Mia","James","Amelia","Benjamin","Harper","Daniel","Evelyn","Matthew","Abigail"
];

$LAST_NAMES = [
    "Santos","Reyes","Cruz","Garcia","Mendoza","Aquino","Ramos","Dela Cruz","Tan","Lim",
    "Santiago","Bautista","Villanueva","Fernandez","Lopez","Gonzales","Perez","Castillo","Rivera","Flores",
    "Morales","Gutierrez","Chavez","Domingo","Navarro","Mercado","Salazar","Castro","Vega","Ortiz",
    "Del Rosario","Aguilar","Roxas","Valdez","Miranda","Cortez","Manalo","Pascual","Suarez","Estrada",
    "Velasco","Francisco","Soriano","Diaz","Marquez","Herrera","Medina","Aguirre","Reyes","Bautista"
];

$OCCUPATIONS = [
    "Teacher", "Nurse", "Software Engineer", "OFW - Healthcare", "Grab Driver", "Small Business Owner",
    "Call Center Agent", "Accountant", "Civil Engineer", "Seafarer", "Retail Supervisor", "Bank Teller",
    "Freelance Graphic Designer", "Barangay Health Worker", "Sales Manager", "University Professor",
    "Logistics Coordinator", "Virtual Assistant", "Chef / Cook", "Real Estate Agent", "Electrician",
    "Medical Technologist", "Government Employee", "Tricycle Operator", "Online Seller (Shopee/Lazada)"
];

$CITIES = [
    "Quezon City", "Manila", "Makati City", "Pasig City", "Taguig City", "Cebu City", "Davao City",
    "Cagayan de Oro", "Bacolod City", "Iloilo City", "Baguio City", "San Fernando, Pampanga",
    "Antipolo City", "Calamba, Laguna", "Batangas City", "Tacloban City", "General Santos City"
];

$GROUP_NAME_TEMPLATES = [
    "Sunrise Savings Circle", "Barkada Paluwagan {n}", "Office Unity Fund - {dept}",
    "Family First Trust {year}", "Barangay {brgy} Monthly Hulog", "Teachers' Mutual Aid {n}",
    "OFW Dream Fund {year}", "Market Vendors Paluwagan", "Engineers Circle {n}",
    "Nurses' Trust Group", "BPO Night Shift Savings", "Kapit-Bisig Fund {n}",
    "Young Professionals Paluwagan", "Tita's Investment Club", "Father & Son Savings",
    "Community Kitchen Fund", "School Staff Paluwagan {year}", "Tricycle Operators Coop",
    "Online Sellers Trust {n}", "Healthcare Heroes Fund"
];

function rand_date($start_year, $end_year, $month_bias = null) {
    $y = rand($start_year, $end_year);
    $m = $month_bias ?: rand(1,12);
    $d = rand(1, 28);
    return sprintf("%04d-%02d-%02d %02d:%02d:%02d", $y, $m, $d, rand(8,18), rand(0,59), rand(0,59));
}

function rand_date_between($start, $end) {
    $start_ts = strtotime($start);
    $end_ts   = strtotime($end);
    $ts = rand($start_ts, $end_ts);
    return date('Y-m-d H:i:s', $ts);
}

function make_username($first, $last, $id) {
    $base = strtolower(preg_replace('/[^a-z]/i', '', $first . '.' . $last));
    return $base . ($id > 50 ? $id : '');
}

function make_email($username) {
    $domains = ['gmail.com', 'yahoo.com', 'outlook.com', 'proton.me'];
    return $username . '@' . $domains[array_rand($domains)];
}

function demo_password_hash() {
    // All demo accounts use this fixed password hash
    return '$2y$10$GKfh18Ysah5fH9O7w2o0puPVdUo3E7hREQAR52DgPuPpLP8tD891u';
}

// ============== MAIN SEED ==============

$conn = connect();
ensure_support_tables($conn);

if ($RESET_FIRST) {
    reset_demo_data($conn);
}

log_msg("=== Starting Group-ECHO 3-Year Demo Seed ===");

$password_hash = demo_password_hash();
$now = date('Y-m-d H:i:s');

// ---- 1. USERS (120 total: 3 admins + 117 members) ----
log_msg("Creating 120 users (spread across 2023-2026)...");

$user_ids = [];
$admin_ids = [];

$insert_user = mysqli_prepare($conn, "
    INSERT INTO users 
    (username, first_name, last_name, email, phone, occupation, address, password_hash, role, created_at, status)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");

$insert_verif = mysqli_prepare($conn, "
    INSERT INTO user_verifications (user_id, document, verified_at, status) VALUES (?, ?, ?, 'approved')
");

// Create 3 admins first (very early adopters)
$admin_data = [
    [1, 'admin.juan', 'Juan', 'Dela Cruz', 'Quezon City', 'Teacher'],
    [2, 'maria.admin', 'Maria', 'Santos', 'Makati City', 'Nurse'],
    [3, 'carlos.sys', 'Carlos', 'Reyes', 'Cebu City', 'Software Engineer'],
];

foreach ($admin_data as $idx => $a) {
    $created = rand_date_between('2023-04-10', '2023-06-15');
    $username = $a[1];
    $email = make_email($username);
    $phone = '+63 917 ' . str_pad(rand(1000000,9999999), 7, '0', STR_PAD_LEFT);
    $addr = $a[3] . ', Metro Manila / Visayas';
    $occ = $a[4];

    $role_admin = 'admin';
    $status_activated = 'activated';
    $first_name = $a[2];
    $last_name = $a[3];
    mysqli_stmt_bind_param($insert_user, "sssssssssss", 
        $username, $first_name, $last_name, $email, $phone, $occ, $addr, $password_hash, $role_admin, $created, $status_activated);
    mysqli_stmt_execute($insert_user);
    $uid = mysqli_insert_id($conn);
    $admin_ids[] = $uid;
    $user_ids[] = $uid;

    // verification doc
    $doc = "verify_{$uid}_" . time() . ".png";
    $vdate = date('Y-m-d H:i:s', strtotime($created . ' + 4 days'));
    mysqli_stmt_bind_param($insert_verif, "iss", $uid, $doc, $vdate);
    mysqli_stmt_execute($insert_verif);
}

log_msg("  - 3 admins created (early 2023)");

// 117 regular members
$member_count = 117;
$created_dates = [];
for ($i = 0; $i < $member_count; $i++) {
    $first  = $FIRST_NAMES[array_rand($FIRST_NAMES)];
    $last   = $LAST_NAMES[array_rand($LAST_NAMES)];
    $id     = 10 + $i;
    $username = make_username($first, $last, $id);
    // ensure some uniqueness
    if (in_array($username, ['juan.delacruz','maria.santos'])) $username .= $id;

    $email = make_email($username);
    $phone = '+63 9' . rand(17,99) . ' ' . rand(100,999) . ' ' . rand(1000,9999);
    $occ   = $OCCUPATIONS[array_rand($OCCUPATIONS)];
    $city  = $CITIES[array_rand($CITIES)];
    $addr  = rand(10,987) . " " . ["St.","Ave.","Road","Blvd."][rand(0,3)] . ", " . $city;

    // Spread creation: heavy in 2023-2024, some 2025-2026
    if ($i < 40) {
        $created = rand_date_between('2023-04-20', '2023-12-28');
    } elseif ($i < 80) {
        $created = rand_date_between('2024-01-05', '2024-11-30');
    } elseif ($i < 105) {
        $created = rand_date_between('2025-01-10', '2025-10-15');
    } else {
        $created = rand_date_between('2025-11-01', '2026-05-20');
    }

    $status = 'activated';
    if ($i % 23 === 0 && $i > 10) $status = 'pending';      // a few pending for admin demo
    if ($i === 55) $status = 'suspended';                   // one dramatic case

    $role_member = 'member';
    mysqli_stmt_bind_param($insert_user, "sssssssssss",
        $username, $first, $last, $email, $phone, $occ, $addr, $password_hash, $role_member, $created, $status);
    mysqli_stmt_execute($insert_user);
    $uid = mysqli_insert_id($conn);
    $user_ids[] = $uid;

    // Most activated users have approved verification docs
    if ($status === 'activated' && ($i % 3 !== 0)) {
        $doc = "verify_{$uid}_" . (1780000000 + $i) . ".jpg";
        $vdate = date('Y-m-d H:i:s', strtotime($created . ' + ' . rand(1,9) . ' days'));
        mysqli_stmt_bind_param($insert_verif, "iss", $uid, $doc, $vdate);
        mysqli_stmt_execute($insert_verif);
    }

    if (($i + 1) % 30 === 0) log_msg("  - Created " . ($i+1) . " members so far...");
}

mysqli_stmt_close($insert_user);
mysqli_stmt_close($insert_verif);

log_msg("Users complete: " . count($user_ids) . " total (" . count($admin_ids) . " admins).");

// ---- 2. GROUPS + MEMBERS + CYCLES + HISTORICAL DATA ----
log_msg("\nCreating 28 groups with full historical activity (2023-2026)...");
log_msg("(using batched multi-row INSERTs + per-group transactions — should be fast)");

$groups_created = 0;
$contrib_count = 0;
$payout_count = 0;
$tx_count = 0;
$wallet_count = 0;
$history_count = 0;

$insert_group = mysqli_prepare($conn, "
    INSERT INTO groups 
    (group_name, description, privacy, contribution_amount, max_members, frequency, 
     cycle_length, current_cycle, invite_code, is_active, status, created_by, created_at)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?, ?, ?)
");

$insert_member = mysqli_prepare($conn, "
    INSERT INTO group_members (user_id, group_id, status, position, joined_at)
    VALUES (?, ?, 'active', ?, ?)
");

$insert_cycle = mysqli_prepare($conn, "
    INSERT INTO cycles (group_id, cycle_number, start_date, end_date, status, payout_status, payout_member_id)
    VALUES (?, ?, ?, ?, ?, ?, ?)
");

$insert_contrib = mysqli_prepare($conn, "
    INSERT INTO contributions (cycle_id, member_id, amount, due_date, paid_at, status)
    VALUES (?, ?, ?, ?, ?, ?)
");

$insert_payout = mysqli_prepare($conn, "
    INSERT INTO payouts (cycle_id, member_id, amount, payout_date, status)
    VALUES (?, ?, ?, ?, 'released')
");

$insert_tx = mysqli_prepare($conn, "
    INSERT INTO transactions 
    (group_id, cycle_id, member_id, user_id, transaction_type, amount, transaction_date, status, recorded_by, created_at)
    VALUES (?, ?, ?, ?, ?, ?, ?, 'completed', ?, ?)
");

$insert_wallet = mysqli_prepare($conn, "
    INSERT INTO wallet_requests 
    (user_id, type, amount, payment_method, account_details, status, created_at, reviewed_at, reviewed_by)
    VALUES (?, ?, ?, ?, ?, 'approved', ?, ?, ?)
");

$insert_hist = mysqli_prepare($conn, "
    INSERT INTO group_history 
    (group_id, event_type, actor_user_id, target_user_id, cycle_number, amount, description, created_at)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
");

// Pre-select a pool of "founders" (early users tend to create groups)
$founder_pool = array_slice($user_ids, 0, 55);

// Create 28 groups
$group_templates = $GROUP_NAME_TEMPLATES;
$group_idx = 0;

$group_meta = []; // store useful info per group for later live data

for ($g = 0; $g < 28; $g++) {
    $creator = $founder_pool[array_rand($founder_pool)];

    // Group creation date spread over 3 years
    if ($g < 6) {
        $g_created = rand_date_between('2023-05-01', '2023-11-20');
        $is_completed = true;
    } elseif ($g < 13) {
        $g_created = rand_date_between('2024-01-15', '2024-09-30');
        $is_completed = ($g % 2 === 0);
    } elseif ($g < 20) {
        $g_created = rand_date_between('2024-10-01', '2025-07-15');
        $is_completed = false;
    } else {
        $g_created = rand_date_between('2025-08-01', '2026-04-10');
        $is_completed = false;
    }

    $privacy = ($g % 7 === 0 && $g > 3) ? 'private' : 'public';
    $invite = null;
    if ($privacy === 'private') {
        $invite = strtoupper(substr(md5('grp' . $g . microtime()), 0, 6));
    }

    $amount = [500, 750, 1000, 1000, 1500, 1500, 2000, 2000, 2500, 3000][array_rand([0,1,2,3,4,5,6,7,8,9])];
    $max_m  = rand(5, 10);
    $freq   = (rand(1,10) <= 7) ? 'monthly' : (rand(1,2) === 1 ? 'biweekly' : 'weekly');

    $tmpl = $group_templates[$group_idx % count($group_templates)];
    $group_idx++;
    $name = str_replace(
        ['{n}', '{year}', '{dept}', '{brgy}'],
        [($g+1), date('Y', strtotime($g_created)), ['Accounting','IT','Sales','HR','Operations'][rand(0,4)], ['San Roque','Poblacion','Rizal','Mabini'][rand(0,3)] ],
        $tmpl
    );

    $desc = "Community savings group. " . ($privacy === 'public' ? "Open to new members (subject to slots)." : "Private - invite only.") . " Running strong since " . date('M Y', strtotime($g_created)) . ".";

    $cycle_len = $max_m;
    $current_c = $is_completed ? $cycle_len : rand(2, max(2, (int)($cycle_len * 0.7)));

    $status = $is_completed ? 'completed' : (rand(0,8) > 1 ? 'active' : 'pending');
    $invite_code = $invite; // can be null

    mysqli_stmt_bind_param($insert_group, "sssdiiisssis",
        $name, $desc, $privacy, $amount, $max_m, $freq,
        $cycle_len, $current_c, $invite_code, $status, $creator, $g_created
    );
    mysqli_stmt_execute($insert_group);
    $gid = mysqli_insert_id($conn);
    $groups_created++;

    // Batch collectors - huge speedup vs thousands of single prepared statements
    $batch_contribs = [];
    $batch_txs      = [];
    $batch_wallets  = [];
    $batch_hists    = [];

    // One transaction per group = much less overhead
    mysqli_begin_transaction($conn);

    // Pick members for this group (founder + others). Overlap users across groups for realism.
    $members = [];
    $members[] = $creator; // position 1 = creator

    $available = array_values(array_diff($user_ids, [$creator]));
    shuffle($available);
    for ($m = 1; $m < $max_m; $m++) {
        if (!empty($available[$m-1])) $members[] = $available[$m-1];
    }
    // If not enough unique, allow some repeats (rare)
    while (count($members) < $max_m) {
        $members[] = $available[array_rand($available)];
    }

    $member_rows = [];
    $pos = 1;
    foreach ($members as $uid) {
        $joined = date('Y-m-d H:i:s', strtotime($g_created . ' + ' . rand(0, 45) . ' days'));
        if (strtotime($joined) > time()) $joined = $g_created;

        $current_pos = $pos;
        mysqli_stmt_bind_param($insert_member, "iiis", $uid, $gid, $current_pos, $joined);
        mysqli_stmt_execute($insert_member);
        $mid = mysqli_insert_id($conn);
        $member_rows[$current_pos] = ['member_id' => $mid, 'user_id' => $uid, 'position' => $current_pos];
        $pos++;

        // history (batched)
        $desc_joined = mysqli_real_escape_string($conn, "Joined as position #{$current_pos}");
        $batch_hists[] = "($gid, 'member_joined', $uid, $uid, NULL, NULL, '$desc_joined', '$joined')";
        $history_count++;
    }

    // Pre-create cycles 1..cycle_len (matching app logic)
    $cycle_start = $g_created;
    $cycle_id_map = []; // cycle_number => cycle_id

    for ($c = 1; $c <= $cycle_len; $c++) {
        $start = date('Y-m-d', strtotime($cycle_start . ($freq === 'monthly' ? " + " . ($c-1) . " months" : 
                      ($freq === 'biweekly' ? " + " . (14*($c-1)) . " days" : " + " . (7*($c-1)) . " days")) ));

        $end   = date('Y-m-d', strtotime($start . ' + ' . ($freq === 'monthly' ? '28' : ($freq === 'biweekly' ? '13' : '6')) . ' days'));

        $c_status = ($c < $current_c || $is_completed) ? 'completed' : 'ongoing';
        $p_status = ($c < $current_c || $is_completed) ? 'released' : 'pending';
        $payout_mid = null;
        $payout_uid = null;

        if ($p_status === 'released' && isset($member_rows[$c])) {
            $payout_mid = $member_rows[$c]['member_id'];
            $payout_uid = $member_rows[$c]['user_id'];
        } elseif ($p_status === 'released') {
            // fallback (shouldn't happen)
            $payout_mid = $member_rows[1]['member_id'];
            $payout_uid = $member_rows[1]['user_id'];
        }

        $payout_member_id = $payout_mid; // may be null
        mysqli_stmt_bind_param($insert_cycle, "iissssi", $gid, $c, $start, $end, $c_status, $p_status, $payout_member_id);
        mysqli_stmt_execute($insert_cycle);
        $cid = mysqli_insert_id($conn);
        $cycle_id_map[$c] = $cid;

        // If released, create the payout + tx + wallet credit (historical)
        if ($p_status === 'released' && $payout_mid && $payout_uid) {
            $pot = $amount * count($members);
            $pdate = date('Y-m-d', strtotime($start . ' + 3 days'));

            mysqli_stmt_bind_param($insert_payout, "iids", $cid, $payout_mid, $pot, $pdate);
            mysqli_stmt_execute($insert_payout);
            $payout_count++;

            // transactions row (payout) - batched
            $tx_date = $pdate;
            $batch_txs[] = "($gid, $cid, $payout_mid, $payout_uid, 'payout', $pot, '$tx_date', 'completed', $payout_uid, '$tx_date')";
            $tx_count++;

            // wallet credit (payout received) - batched
            $note = mysqli_real_escape_string($conn, "Payout • Group #{$gid} • Cycle #{$c}");
            $admin_reviewer = $admin_ids[0] ?? 1;
            $batch_wallets[] = "($payout_uid, 'deposit', $pot, 'Payout', '$note', 'approved', '$tx_date', '$tx_date', $admin_reviewer)";
            $wallet_count++;

            // history (batched)
            $hist_desc_p = mysqli_real_escape_string($conn, "Payout of ₱" . number_format($pot, 2) . " released to position #{$c}");
            $batch_hists[] = "($gid, 'payout', $creator, $payout_uid, $c, $pot, '$hist_desc_p', '$tx_date')";
            $history_count++;
        }
    }

    // Now insert contributions for every cycle.
    // Rule: member does NOT contribute on the cycle where their position == cycle_number
    foreach ($cycle_id_map as $cnum => $cid) {
        $is_released = ($cnum < $current_c || $is_completed);

        foreach ($member_rows as $pos => $mrow) {
            if ($pos === $cnum) continue; // receiver does not pay this cycle (app rule)

            $due = date('Y-m-d', strtotime($g_created . " + " . (($cnum-1)*30 + rand(0,5)) . " days"));
            $paid = null;
            $cstatus = 'pending';

            if ($is_released || $cnum < $current_c) {
                $cstatus = (rand(1,12) === 1) ? 'late' : 'paid';
                $paid = date('Y-m-d', strtotime($due . ' + ' . rand(0,6) . ' days'));
            }

            $amt = $amount;
            $this_member_id = $mrow['member_id'];
            $this_user_id   = $mrow['user_id'];

            // Batch contribution (much faster)
            $paid_sql = $paid ? "'$paid'" : "NULL";
            $batch_contribs[] = "($cid, $this_member_id, $amt, '$due', $paid_sql, '$cstatus')";
            $contrib_count++;

            if ($cstatus === 'paid' || $cstatus === 'late') {
                // contribution transaction (batched)
                $txd = $paid ?: $due;
                $batch_txs[] = "($gid, $cid, $this_member_id, $this_user_id, 'contribution', $amt, '$txd', 'completed', $this_user_id, '$txd')";
                $tx_count++;

                // wallet withdraw (batched)
                $wnote = mysqli_real_escape_string($conn, "Group contribution - Group #{$gid} Cycle #{$cnum}");
                $batch_wallets[] = "($this_user_id, 'withdraw', $amt, 'Internal Wallet', '$wnote', 'approved', '$txd', '$txd', $this_user_id)";
                $wallet_count++;

                // occasional history entry (batched)
                if (rand(0,2) === 0) {
                    $hist_desc = mysqli_real_escape_string($conn, "Paid ₱" . number_format($amt, 2) . " for cycle #{$cnum}");
                    $batch_hists[] = "($gid, 'payment', $this_user_id, $this_user_id, $cnum, $amt, '$hist_desc', '$txd')";
                    $history_count++;
                }
            }
        }
    }

    // Record some extra wallet deposits (users funding their wallets over time)
    if (!$is_completed && rand(0,1) === 1) {
        foreach (array_slice($members, 0, 3) as $uid) {
            $dep_amt = $amount * rand(2,5);
            $dep_date = rand_date_between($g_created, date('Y-m-d', strtotime($g_created . ' + 4 months')));
            $admin_reviewer = $admin_ids[0] ?? 1;
            $wdesc = mysqli_real_escape_string($conn, 'Initial / Top-up deposit for group activities');
            $batch_wallets[] = "($uid, 'deposit', $dep_amt, 'Bank Transfer / GCash', '$wdesc', 'approved', '$dep_date', '$dep_date', $admin_reviewer)";
            $wallet_count++;
        }
    }

    // Group activation history for active ones (batched)
    if ($status === 'active') {
        $desc_act = mysqli_real_escape_string($conn, "Group activated with " . count($members) . " members - rotation started");
        $batch_hists[] = "($gid, 'group_activated', $creator, NULL, 1, NULL, '$desc_act', '$g_created')";
        $history_count++;
    }

    // Also ensure a couple of "current cycle" pending contributions exist for active non-completed groups
    // so the UI shows live "to pay" items for demo. (This runs after the historical data.)
    if (!$is_completed && $status === 'active' && isset($cycle_id_map[$current_c])) {
        $cur_cid = $cycle_id_map[$current_c];
        $pending_count = 0;
        foreach ($member_rows as $pos => $mrow) {
            if ($pos === $current_c) continue;
            $chk = mysqli_query($conn, "SELECT 1 FROM contributions WHERE cycle_id = {$cur_cid} AND member_id = {$mrow['member_id']} LIMIT 1");
            if ($chk && mysqli_num_rows($chk) === 0) {
                $due = date('Y-m-d', strtotime($g_created . " + " . (($current_c-1)*28) . " days"));
                $member_id_var = $mrow['member_id'];
                $ins_pending = mysqli_prepare($conn, "INSERT INTO contributions (cycle_id, member_id, amount, due_date, status) VALUES (?, ?, ?, ?, 'pending')");
                mysqli_stmt_bind_param($ins_pending, "iids", $cur_cid, $member_id_var, $amount, $due);
                mysqli_stmt_execute($ins_pending);
                mysqli_stmt_close($ins_pending);
                $pending_count++;
            }
        }
        if ($pending_count > 0) {
            log_msg("    + Added {$pending_count} pending invoices for live cycle #{$current_c} in group #{$gid}");
        }
    }

    // === EXECUTE BATCHES for this group (the key to not timing out) ===
    if (!empty($batch_contribs)) {
        $sql = "INSERT INTO contributions (cycle_id, member_id, amount, due_date, paid_at, status) VALUES " . implode(',', $batch_contribs);
        @mysqli_query($conn, $sql);
    }
    if (!empty($batch_txs)) {
        $sql = "INSERT INTO transactions (group_id, cycle_id, member_id, user_id, transaction_type, amount, transaction_date, status, recorded_by, created_at) VALUES " . implode(',', $batch_txs);
        @mysqli_query($conn, $sql);
    }
    if (!empty($batch_wallets)) {
        $sql = "INSERT INTO wallet_requests (user_id, type, amount, payment_method, account_details, status, created_at, reviewed_at, reviewed_by) VALUES " . implode(',', $batch_wallets);
        @mysqli_query($conn, $sql);
    }
    if (!empty($batch_hists)) {
        $sql = "INSERT INTO group_history (group_id, event_type, actor_user_id, target_user_id, cycle_number, amount, description, created_at) VALUES " . implode(',', $batch_hists);
        @mysqli_query($conn, $sql);
    }

    mysqli_commit($conn);

    $group_meta[$gid] = [
        'name' => $name,
        'amount' => $amount,
        'members' => $member_rows,
        'current_cycle' => $current_c,
        'cycle_len' => $cycle_len,
        'cycle_ids' => $cycle_id_map,
        'is_completed' => $is_completed,
        'status' => $status,
        'created' => $g_created
    ];

    if (($g + 1) % 7 === 0) {
        log_msg("  - Created group " . ($g+1) . "/28: {$name} ({$status}, {$freq}, ₱{$amount})");
    }
}

mysqli_stmt_close($insert_group);
mysqli_stmt_close($insert_member);
mysqli_stmt_close($insert_cycle);
mysqli_stmt_close($insert_contrib);
mysqli_stmt_close($insert_payout);
mysqli_stmt_close($insert_tx);
mysqli_stmt_close($insert_wallet);
mysqli_stmt_close($insert_hist);

log_msg("\n=== SEED SUMMARY ===");
log_msg("Users:              " . count($user_ids) . " (3 admins)");
log_msg("Groups:             $groups_created (many public, spread 2023-2026)");
log_msg("Contributions:      $contrib_count");
log_msg("Payouts (released): $payout_count");
log_msg("Transactions:       $tx_count");
log_msg("Wallet activity:    $wallet_count rows (deposits + withdraws)");
log_msg("Group history logs: $history_count");
log_msg("");

// Final: ensure at least the first admin can be used immediately
log_msg("\n=== READY FOR PRESENTATION ===");
log_msg("All demo users password hash: \$2y\$10\$GKfh18Ysah5fH9O7w2o0puPVdUo3E7hREQAR52DgPuPpLP8tD891u");
log_msg("Recommended first login:");
log_msg("  Username: admin.juan   (or maria.admin / carlos.sys)");
log_msg("  Password hash: \$2y\$10\$GKfh18Ysah5fH9O7w2o0puPVdUo3E7hREQAR52DgPuPpLP8tD891u");
log_msg("");
log_msg("After login:");
log_msg("  1. Visit Admin panel (users, groups, transactions, analytics) — you will see 100+ people and years of activity.");
log_msg("  2. IMPORTANT: In Admin > Analytics, run the ETL / OLAP sync (buttons or olap_sync.php) so the charts and fact tables light up with 3 years of contributions/payouts.");
log_msg("  3. Browse public groups (many!), join one as a regular member, view rich Group Details history, Payments tab, and Wallet (lots of past payouts + deposits).");
log_msg("  4. The Simulation panel (in some views) can be used for live 'next cycle' demos on top of the historical data.");
log_msg("");
log_msg("To re-seed fresh data: append ?reset=1 to this script's URL or set \$RESET_FIRST = true.");
log_msg("");
log_msg("--- Quick phpMyAdmin password fix (if needed) ---");
log_msg("UPDATE users SET password_hash = '" . addslashes($password_hash) . "' WHERE username IN ('admin.juan','maria.admin','carlos.sys') OR username LIKE '%.%';");
log_msg("");

mysqli_close($conn);

echo "<hr><p><strong>Done.</strong> You can now browse the site with rich 3-year demo data.</p>";
if (php_sapi_name() !== 'cli') {
    echo '<p><a href="../../Paluwagan/">Go to the Paluwagan app →</a></p>';
}
?>