<?php
session_start();
include "../db.php"; // Adjust connection paths to match your layout

// Kick users back out to registration if session context is missing
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../index.php"); 
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch accurate application status details safely
$query = mysqli_prepare($conn, "SELECT username, first_name, last_name, email, phone, occupation, address, role, status FROM users WHERE user_id = ?");
mysqli_stmt_bind_param($query, "i", $user_id);
mysqli_stmt_execute($query);
$result = mysqli_stmt_get_result($query);
$user = mysqli_fetch_assoc($result);

if (!$user) {
    session_destroy();
    header("Location: ../../index.php");
    exit();
}

// Route users away instantly if an admin verified them mid-session
if ($user['status'] === 'verified') {
    header("Location: dashboard.php");
    exit();
}

// ================= DESTRUCTION AND ARCHIVE SECTOR =================
if (isset($_POST['delete_account']) && $user['status'] === 'denied') {
    
    // 1. Move registration details safely into historical logs 
    $archive_stmt = mysqli_prepare($conn, "INSERT INTO user_creation_history 
        (original_user_id, username, first_name, last_name, email, phone, occupation, address, role, verification_status) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'denied')");
    
    mysqli_stmt_bind_param($archive_stmt, "issssssss", 
        $user_id, 
        $user['username'], 
        $user['first_name'], 
        $user['last_name'], 
        $user['email'], 
        $user['phone'], 
        $user['occupation'], 
        $user['address'], 
        $user['role']
    );
    mysqli_stmt_execute($archive_stmt);

    // 2. Clear out documents attached to registration to save storage
    $delete_docs = mysqli_prepare($conn, "DELETE FROM user_verifications WHERE user_id = ?");
    mysqli_stmt_bind_param($delete_docs, "i", $user_id);
    mysqli_stmt_execute($delete_docs);

    // 3. Wipe account profile row to free up emails & usernames for re-use
    $delete_user = mysqli_prepare($conn, "DELETE FROM users WHERE user_id = ?");
    mysqli_stmt_bind_param($delete_user, "i", $user_id);
    mysqli_stmt_execute($delete_user);

    // 4. Terminate access tokens completely
    session_destroy();
    header("Location: ../../index.php?message=account_purged");
    exit();
}

// Bring in presentation layer template 
include "../../front-end/views/pending_review-view.php";
?>