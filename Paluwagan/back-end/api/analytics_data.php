<?php
/**
 * api/analytics_data.php
 * 
 * Dedicated API endpoint for the OLAP Analytics Dashboard.
 * 
 * This file is intentionally kept simple and heavily commented for a BS IT student.
 * 
 * It accepts filter parameters via GET and returns clean JSON data.
 * All heavy OLAP logic (Slice, Dice, Roll-up, Drill-down) lives here.
 * 
 * Called via AJAX (fetch) from admin/analytics.php
 */

// ============================================
// 1. SECURITY & HEADERS
// ============================================
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

// ============================================
// 2. GET FILTER PARAMETERS (from the browser)
// ============================================
$year       = isset($_GET['year']) ? (int)$_GET['year'] : 0;           // 0 = All years
$quarter    = isset($_GET['quarter']) ? (int)$_GET['quarter'] : 0;     // 0 = All quarters
$group_key  = isset($_GET['group_key']) ? (int)$_GET['group_key'] : 0; // 0 = All groups
$time_level = $_GET['time_level'] ?? 'month';                          // 'year', 'quarter', 'month'
$trans_type = $_GET['trans_type'] ?? 'all';                            // 'all', 'contribution', 'payout'

// ============================================
// 3. CONNECT TO OLAP DATA WAREHOUSE
// ============================================
require_once __DIR__ . '/../olap_db.php';
$olap = OlapDatabase::getInstance()->getPdo();

// ============================================
// 4. BUILD DYNAMIC WHERE CLAUSE
// This is where SLICE and DICE happen.
// We safely build conditions using prepared statements.
// ============================================
$where = "1=1";
$params = [];

if ($year > 0) {
    // SLICE on time dimension (one specific year).
    // We also check YEAR(ft.created_at) so that the year filter stays functional
    // even if some facts use a fallback time_key (common during early ETL or date population issues).
    // This makes the dropdown options (populated from actual OLAP fact data) actually work.
    $where .= " AND (dt.year = ? OR YEAR(ft.created_at) = ?)";
    $params[] = $year;
    $params[] = $year;
}

if ($quarter > 0) {
    // DICE - adding another dimension
    $where .= " AND dt.quarter = ?";
    $params[] = $quarter;
}

if ($group_key > 0) {
    // Classic SLICE on the Group dimension
    $where .= " AND ft.group_key = ?";
    $params[] = $group_key;
}

if ($trans_type !== 'all') {
    // DICE on transaction type
    $where .= " AND ft.transaction_type = ?";
    $params[] = $trans_type;
}

// ============================================
// 5. TIME GRANULARITY (ROLL-UP / DRILL-DOWN)
// The GROUP BY changes based on what the user selects.
// This is the core of OLAP aggregation.
// ============================================
function getTimeGrouping($level) {
    if ($level === 'year') {
        // ROLL-UP to the highest level
        return [
            'select' => "dt.year as period_label",
            'group'  => "dt.year",
            'order'  => "dt.year"
        ];
    } elseif ($level === 'quarter') {
        return [
            'select' => "CONCAT(dt.year, '-Q', dt.quarter) as period_label",
            'group'  => "dt.year, dt.quarter",
            'order'  => "dt.year, dt.quarter"
        ];
    } else {
        // Default = most detailed (DRILL-DOWN)
        return [
            'select' => "CONCAT(dt.year, '-', LPAD(dt.month, 2, '0')) as period_label",
            'group'  => "dt.year, dt.month",
            'order'  => "dt.year, dt.month"
        ];
    }
}

$timeGroup = getTimeGrouping($time_level);

// ============================================
// 6. RUN THE OLAP QUERIES
// ============================================
try {
    // --- SUMMARY CARDS (KPI) ---
    $summary_sql = "
        SELECT 
            COALESCE(SUM(ft.amount_contribution), 0) AS total_contributions,
            COALESCE(SUM(ft.amount_payout), 0)       AS total_payouts,
            COUNT(*)                                 AS total_transactions,
            COUNT(DISTINCT ft.group_key)             AS active_groups
        FROM fact_transactions ft
        JOIN dim_time dt ON ft.time_key = dt.time_key
        WHERE $where
    ";
    $stmt = $olap->prepare($summary_sql);
    $stmt->execute($params);
    $summary = $stmt->fetch(PDO::FETCH_ASSOC);

    // --- DATA FOR BAR CHART + PIE CHART ---
    // Grouped by Group (good for both contribution bars and payout pie)
    $group_sql = "
        SELECT 
            dg.group_name,
            SUM(ft.amount_contribution) AS total_contributions,
            SUM(ft.amount_payout)       AS total_payouts
        FROM fact_transactions ft
        JOIN dim_time dt   ON ft.time_key = dt.time_key
        JOIN dim_group dg  ON ft.group_key = dg.group_key
        WHERE $where
        GROUP BY dg.group_key, dg.group_name
        ORDER BY total_contributions DESC
        LIMIT 12
    ";
    $stmt = $olap->prepare($group_sql);
    $stmt->execute($params);
    $by_group = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // --- DATA FOR LINE CHART (Trends) ---
    // The GROUP BY is controlled by $timeGroup (Roll-up / Drill-down magic)
    $time_sql = "
        SELECT 
            {$timeGroup['select']},
            SUM(ft.amount_contribution) AS contributions,
            SUM(ft.amount_payout)       AS payouts
        FROM fact_transactions ft
        JOIN dim_time dt   ON ft.time_key = dt.time_key
        JOIN dim_group dg  ON ft.group_key = dg.group_key
        WHERE $where
        GROUP BY {$timeGroup['group']}
        ORDER BY {$timeGroup['order']}
    ";
    $stmt = $olap->prepare($time_sql);
    $stmt->execute($params);
    $time_series = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // ============================================
    // 7. RETURN JSON TO THE FRONTEND
    // ============================================
    echo json_encode([
        'success' => true,
        'summary' => $summary,
        'by_group' => $by_group,
        'time_series' => $time_series,
        'meta' => [
            'time_level' => $time_level,
            'filters' => [
                'year' => $year,
                'quarter' => $quarter,
                'group_key' => $group_key,
                'trans_type' => $trans_type
            ]
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Query failed: ' . $e->getMessage()
    ]);
}
?>