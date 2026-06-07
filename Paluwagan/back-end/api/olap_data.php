<?php
/**
 * API endpoint for OLAP Analytics (AJAX)
 * Returns JSON for charts and tables based on filters.
 * Admin access assumed (called from admin pages).
 */

session_start();
require_once "../olap_db.php";

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$olap = OlapDatabase::getInstance()->getPdo();

$group_key = isset($_GET['group_id']) ? (int)$_GET['group_id'] : 0;
$year = isset($_GET['year']) ? (int)$_GET['year'] : 0; // 0 = All Years (overall)
$trans_type = $_GET['trans_type'] ?? 'all';

$where = "1=1";
$params = [];

if ($group_key > 0) { $where .= " AND ft.group_key = ?"; $params[] = $group_key; }
if ($year) { $where .= " AND YEAR(ft.created_at) = ?"; $params[] = $year; }
if ($trans_type !== 'all') { $where .= " AND ft.transaction_type = ?"; $params[] = $trans_type; }

try {
    // Time series
    $time_stmt = $olap->prepare("
        SELECT YEAR(ft.created_at) as year, MONTH(ft.created_at) as month, 
               DATE_FORMAT(ft.created_at, '%b') as month_name,
               SUM(ft.amount_contribution) AS contributions,
               SUM(ft.amount_payout) AS payouts
        FROM fact_transactions ft
        JOIN dim_group dg ON ft.group_key = dg.group_key
        WHERE $where
        GROUP BY YEAR(ft.created_at), MONTH(ft.created_at)
        ORDER BY YEAR(ft.created_at), MONTH(ft.created_at)
    ");
    $time_stmt->execute($params);
    $time_data = $time_stmt->fetchAll();

    // By group
    $group_stmt = $olap->prepare("
        SELECT dg.group_name,
               SUM(ft.amount_contribution) AS contributions,
               SUM(ft.amount_payout) AS payouts
        FROM fact_transactions ft
        JOIN dim_group dg ON ft.group_key = dg.group_key
        WHERE $where
        GROUP BY dg.group_key, dg.group_name
        ORDER BY contributions DESC LIMIT 8
    ");
    $group_stmt->execute($params);
    $group_data = $group_stmt->fetchAll();

    // Members
    $member_stmt = $olap->prepare("
        SELECT du.full_name, dg.group_name,
               SUM(ft.amount_contribution) AS contributed,
               SUM(CASE WHEN ft.transaction_type='payout' THEN ft.amount ELSE 0 END) AS received,
               COUNT(*) AS tx_count
        FROM fact_transactions ft
        JOIN dim_user du ON ft.user_key = du.user_key
        JOIN dim_group dg ON ft.group_key = dg.group_key
        WHERE $where
        GROUP BY du.user_key, du.full_name, dg.group_name
        ORDER BY contributed DESC LIMIT 10
    ");
    $member_stmt->execute($params);
    $member_data = $member_stmt->fetchAll();

    // Summary - do not require dim_time join (filters use created_at in many paths)
    $sum_stmt = $olap->prepare("
        SELECT 
            COALESCE(SUM(ft.amount_contribution),0) AS total_contributions,
            COALESCE(SUM(ft.amount_payout),0) AS total_payouts,
            COUNT(*) AS total_transactions
        FROM fact_transactions ft
        JOIN dim_group dg ON ft.group_key = dg.group_key
        WHERE $where
    ");
    $sum_stmt->execute($params);
    $summary = $sum_stmt->fetch();

    echo json_encode([
        'time_series' => $time_data,
        'by_group' => $group_data,
        'by_member' => $member_data,
        'summary' => $summary
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Query failed: ' . $e->getMessage()]);
}
?>