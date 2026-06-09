<?php
session_start();
include "../../db.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../../index.php");
    exit();
}

// Simulation tools have been removed as requested.
header("Location: index.php?success=" . urlencode("Simulation tools have been removed."));
exit();
?>