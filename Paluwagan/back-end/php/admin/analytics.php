<?php
session_start();
require_once "../../olap_db.php";  // OLAP PDO connection from Component 2

// Admin-only access
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: ../../../index.php");
    exit();
}

$admin_id = (int)$_SESSION['user_id'];

// Get filter params (for initial load and to pass to view)
$group_id = isset($_GET['group_id']) ? (int)$_GET['group_id'] : 0;
$year = isset($_GET['year']) ? (int)$_GET['year'] : 0; // 0 = All Years (overall)
$trans_type = $_GET['trans_type'] ?? 'all'; // all, contribution, payout

$olap = OlapDatabase::getInstance()->getPdo();

// Fetch groups for filter dropdown (from OLAP dim)
$groups_stmt = $olap->query("SELECT group_key, group_id, group_name FROM dim_group ORDER BY group_name");
$groups = $groups_stmt->fetchAll(PDO::FETCH_ASSOC);

// Initial data for charts (will be refreshed via AJAX too)
function fetch_olap_data($olap, $group_key = 0, $year = null, $type = 'all') {
    $where = "1=1";
    $params = [];

    if ($group_key > 0) {
        $where .= " AND ft.group_key = ?";
        $params[] = $group_key;
    }
    if ($year) {
        // Use created_at from fact table for year filter to avoid dependency on dim_time columns
        // (in case dim_time schema in your DB is not fully up to date)
        $where .= " AND YEAR(ft.created_at) = ?";
        $params[] = $year;
    }
    if ($type !== 'all') {
        $where .= " AND ft.transaction_type = ?";
        $params[] = $type;
    }

    // Time series data (by month for line/bar chart)
    // Use YEAR on created_at for robustness (avoids missing columns in dim_time)
    $time_sql = "
        SELECT YEAR(ft.created_at) as year, MONTH(ft.created_at) as month, 
               DATE_FORMAT(ft.created_at, '%b') as month_name,
               SUM(ft.amount_contribution) AS contributions,
               SUM(ft.amount_payout) AS payouts
        FROM fact_transactions ft
        JOIN dim_group dg ON ft.group_key = dg.group_key
        WHERE $where
        GROUP BY YEAR(ft.created_at), MONTH(ft.created_at)
        ORDER BY YEAR(ft.created_at), MONTH(ft.created_at)
    ";
    $time_stmt = $olap->prepare($time_sql);
    $time_stmt->execute($params);
    $time_data = $time_stmt->fetchAll();

    // By Group (pie / bar)
    $group_sql = "
        SELECT dg.group_name,
               SUM(ft.amount_contribution) AS contributions,
               SUM(ft.amount_payout) AS payouts,
               COUNT(*) AS tx_count
        FROM fact_transactions ft
        JOIN dim_group dg ON ft.group_key = dg.group_key
        WHERE $where
        GROUP BY dg.group_key, dg.group_name
        ORDER BY contributions DESC
        LIMIT 8
    ";
    $group_stmt = $olap->prepare($group_sql);
    $group_stmt->execute($params);
    $group_data = $group_stmt->fetchAll();

    // Top Members (for table + drill-down potential)
    $member_sql = "
        SELECT du.full_name, dg.group_name,
               SUM(ft.amount_contribution) AS contributed,
               SUM(CASE WHEN ft.transaction_type = 'payout' THEN ft.amount ELSE 0 END) AS received,
               COUNT(*) AS tx_count
        FROM fact_transactions ft
        JOIN dim_user du ON ft.user_key = du.user_key
        JOIN dim_group dg ON ft.group_key = dg.group_key
        WHERE $where
        GROUP BY du.user_key, du.full_name, dg.group_name
        ORDER BY contributed DESC
        LIMIT 10
    ";
    $member_stmt = $olap->prepare($member_sql);
    $member_stmt->execute($params);
    $member_data = $member_stmt->fetchAll();

    // Summary totals - avoid mandatory dim_time join (many rows may use created_at filtering only)
    $summary_sql = "
        SELECT 
            COALESCE(SUM(ft.amount_contribution),0) AS total_contributions,
            COALESCE(SUM(ft.amount_payout),0) AS total_payouts,
            COUNT(*) AS total_transactions
        FROM fact_transactions ft
        JOIN dim_group dg ON ft.group_key = dg.group_key
        WHERE $where
    ";
    $summary_stmt = $olap->prepare($summary_sql);
    $summary_stmt->execute($params);
    $summary = $summary_stmt->fetch();

    return [
        'time_series' => $time_data,
        'by_group' => $group_data,
        'by_member' => $member_data,
        'summary' => $summary ?: ['total_contributions' => 0, 'total_payouts' => 0, 'total_transactions' => 0]
    ];
}

$initial_data = fetch_olap_data($olap, $group_id, $year, $trans_type);

// Available years (for filter) - fallback to recent years if dim_time not populated or missing columns
$years_stmt = $olap->query("SELECT DISTINCT YEAR(created_at) as yr FROM fact_transactions ORDER BY yr DESC LIMIT 5");
$available_years = $years_stmt->fetchAll(PDO::FETCH_COLUMN);
if (empty($available_years)) {
    $available_years = [date('Y'), date('Y')-1, date('Y')-2];
}
$available_years = array_map('intval', $available_years);

include "../../../front-end/views/admin/analytics-view.php";
?>