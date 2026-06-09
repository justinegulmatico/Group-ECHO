<?php
// pop dim_time (php way, no delimiter pain)

require_once __DIR__ . '/../olap_db.php';

$olap = OlapDatabase::getInstance()->getPdo();

$start = $argv[1] ?? '2024-01-01';
$end   = $argv[2] ?? '2027-12-31';

echo "Populating dim_time from $start to $end...\n";

try {
    $stmt = $olap->prepare("
        INSERT IGNORE INTO dim_time (
            full_date, year, quarter, month, month_name, 
            day, day_of_week, week_of_year, is_weekend,
            fiscal_year, fiscal_quarter
        ) VALUES (
            :d, YEAR(:d), QUARTER(:d), MONTH(:d), MONTHNAME(:d),
            DAY(:d), DAYOFWEEK(:d), WEEK(:d, 1), (DAYOFWEEK(:d) IN (1,7)),
            YEAR(:d), QUARTER(:d)
        )
    ");

    $current = new DateTime($start);
    $endDate = new DateTime($end);
    $count = 0;

    while ($current <= $endDate) {
        $dateStr = $current->format('Y-m-d');
        $stmt->execute([':d' => $dateStr]);
        $count++;
        $current->modify('+1 day');

        // Progress every 100 days
        if ($count % 100 === 0) {
            echo "  Inserted $count dates...\n";
        }
    }

    echo "✅ Done! Inserted/verified $count dates into dim_time.\n";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
?>