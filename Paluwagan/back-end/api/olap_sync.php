<?php
/**
 * API endpoint to trigger OLAP ETL sync from the web UI.
 * Only accessible to admins.
 */

ob_start(); // Catch any early output
error_reporting(E_ALL);
ini_set('display_errors', 0); // Prevent PHP errors from outputting HTML

session_start();
require_once "../olap_db.php";   // for consistency, though ETL loads its own

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

$fullReload = !empty($_GET['full']);

$etlScript = __DIR__ . '/../etl/etl_sync.php';

if (!file_exists($etlScript)) {
    $earlyOutput = ob_get_clean();
    echo json_encode(['success' => false, 'error' => 'ETL script not found', 'debug' => $earlyOutput]);
    exit();
}

try {
    // Pass the full flag to the ETL script
    $_GET['full'] = $fullReload ? 1 : 0;

    // Include the ETL script (it will detect web call and use $_GET['full'])
    include $etlScript;

    $output = ob_get_clean();

    echo json_encode([
        'success' => true,
        'full' => $fullReload,
        'output' => $output,
        'message' => 'ETL sync completed. You may need to refresh the analytics data.'
    ]);

} catch (Exception $e) {
    $output = ob_get_clean();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'full' => $fullReload,
        'output' => $output,
        'error' => $e->getMessage()
    ]);
} catch (Throwable $t) {  // Catch fatal errors like parse issues if possible
    $output = ob_get_clean();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'full' => $fullReload,
        'output' => $output,
        'error' => 'Fatal error: ' . $t->getMessage()
    ]);
}
?>