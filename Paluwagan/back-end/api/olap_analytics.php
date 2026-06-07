<?php
/**
 * api/olap_analytics.php
 * 
 * Simple REST-like API for OLAP analytics.
 * Returns JSON that the dashboard (or any frontend) can consume.
 * 
 * Usage examples:
 *   /back-end/api/olap_analytics.php?action=monthly_summary&year=2025
 *   /back-end/api/olap_analytics.php?action=group_performance
 *   /back-end/api/olap_analytics.php?action=slice&group=Paluwagan%20Alpha
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../olap_db.php';

$action = $_GET['action'] ?? 'summary';
$year   = (int)($_GET['year'] ?? date('Y'));
$group  = $_GET['group'] ?? null;
$limit  = min((int)($_GET['limit'] ?? 50), 200);

$olap = OlapDatabase::getInstance()->getPdo();

$response = ['success' => false, 'data' => [], 'meta' => ['action' => $action]];

try {
    switch ($action) {

        // 1. Monthly roll-up summary (great for charts)
        case 'monthly_summary':
            $stmt = $olap->prepare("
                SELECT 
                    dt.year, dt.month, dt.month_name,
                    dg.group_name,
                    SUM(ft.amount_contribution) AS contributions,
                    SUM(ft.amount_payout)       AS payouts,
                    COUNT(*)                    AS tx_count
                FROM fact_transactions ft
                JOIN dim_time dt   ON ft.time_key = dt.time_key
                JOIN dim_group dg  ON ft.group_key = dg.group_key
                WHERE dt.year = ?
                GROUP BY dt.year, dt.month, dt.month_name, dg.group_name
                ORDER BY dt.month, contributions DESC
                LIMIT ?
            ");
            $stmt->execute([$year, $limit]);
            $response['data'] = $stmt->fetchAll();
            break;

        // 2. Group performance with ROLLUP simulation
        case 'group_performance':
            $stmt = $olap->prepare("
                SELECT 
                    COALESCE(dg.group_name, 'ALL GROUPS') as group_name,
                    dt.year,
                    dt.quarter,
                    SUM(ft.amount_contribution) as total_contributions,
                    SUM(ft.amount_payout) as total_payouts
                FROM fact_transactions ft
                JOIN dim_time dt  ON ft.time_key = dt.time_key
                JOIN dim_group dg ON ft.group_key = dg.group_key
                WHERE dt.year = ?
                GROUP BY dg.group_name, dt.year, dt.quarter WITH ROLLUP
                ORDER BY dt.year DESC, dt.quarter, group_name
            ");
            $stmt->execute([$year]);
            $response['data'] = $stmt->fetchAll();
            break;

        // 3. Slice - specific group
        case 'slice':
            $stmt = $olap->prepare("
                SELECT 
                    dt.year, dt.month_name,
                    ft.transaction_type,
                    SUM(ft.amount) as total_amount,
                    COUNT(*) as tx_count
                FROM fact_transactions ft
                JOIN dim_time dt ON ft.time_key = dt.time_key
                JOIN dim_group dg ON ft.group_key = dg.group_key
                WHERE dg.group_name = ?
                  AND dt.year = ?
                GROUP BY dt.year, dt.month_name, ft.transaction_type
                ORDER BY dt.month
            ");
            $stmt->execute([$group ?: 'Paluwagan Alpha', $year]);
            $response['data'] = $stmt->fetchAll();
            break;

        // 4. Dice - multi filter
        case 'dice':
            $stmt = $olap->prepare("
                SELECT 
                    dg.group_name,
                    dt.quarter,
                    ft.transaction_type,
                    SUM(ft.amount) as total,
                    COUNT(DISTINCT ft.user_key) as unique_users
                FROM fact_transactions ft
                JOIN dim_time dt ON ft.time_key = dt.time_key
                JOIN dim_group dg ON ft.group_key = dg.group_key
                WHERE dg.group_name IN ('Paluwagan Alpha', 'Savings Circle B')
                  AND dt.year = ?
                  AND ft.transaction_type = 'contribution'
                GROUP BY dg.group_name, dt.quarter, ft.transaction_type
                ORDER BY total DESC
            ");
            $stmt->execute([$year]);
            $response['data'] = $stmt->fetchAll();
            break;

        // 5. Top contributors (window function style via PHP aggregation)
        case 'top_contributors':
            $stmt = $olap->prepare("
                SELECT 
                    du.full_name,
                    dg.group_name,
                    SUM(ft.amount_contribution) as total_contributed
                FROM fact_transactions ft
                JOIN dim_user du ON ft.user_key = du.user_key
                JOIN dim_group dg ON ft.group_key = dg.group_key
                WHERE ft.transaction_type = 'contribution'
                GROUP BY du.full_name, dg.group_name
                ORDER BY total_contributed DESC
                LIMIT 20
            ");
            $stmt->execute();
            $response['data'] = $stmt->fetchAll();
            break;

        default:
            $response['data'] = ['message' => 'Available actions: monthly_summary, group_performance, slice, dice, top_contributors'];
    }

    $response['success'] = true;
    $response['meta']['executed_at'] = date('c');
    $response['meta']['row_count'] = count($response['data']);

} catch (Exception $e) {
    $response['success'] = false;
    $response['error'] = $e->getMessage();
}

echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
?>