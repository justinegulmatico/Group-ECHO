<?php
session_start();
include "db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_SESSION['user_id'];
    $raw_code = strtoupper(trim($_POST['invite_code']));

    // Extract numerical sequence value map based on system invite structure
    // Matches the pattern 70303{ID}L generated on your details dashboard view
    $clean_id = str_replace(["70303", "L"], "", $raw_code);
    $target_group_id = intval($clean_id);

    // Verify if target group actually exists
    $verify_group = "SELECT * FROM groups WHERE group_id = '$target_group_id' LIMIT 1";
    $verify_res = mysqli_query($conn, $verify_group);
    $group_data = mysqli_fetch_assoc($verify_res);

    if (!$group_data) {
        header("Location: my_groups.php?error=" . urlencode("Invalid code. Matching savings group row context wasn't found."));
        exit();
    }

    // Check if user is already a member of this group
    $membership_check = "SELECT member_id FROM group_members WHERE user_id = '$user_id' AND group_id = '$target_group_id' AND status = 'active' LIMIT 1";
    $membership_res = mysqli_query($conn, $membership_check);

    if (mysqli_num_rows($membership_res) > 0) {
        header("Location: my_groups.php?error=" . urlencode("You are already an active participant inside this savings group circle."));
        exit();
    }

    // Check if group has available slots
    $slots_check = "SELECT COUNT(*) as current_slots FROM group_members WHERE group_id = '$target_group_id' AND status = 'active'";
    $slots_res = mysqli_query($conn, $slots_check);
    $slots_data = mysqli_fetch_assoc($slots_res);
    
    if (($slots_data['current_slots'] ?? 0) >= $group_data['cycle_length']) {
        header("Location: my_groups.php?error=" . urlencode("This savings circle is already full. No open slots remain."));
        exit();
    }

    // Safe execution window passed: Insert membership mapping safely
    $join_query = "INSERT INTO group_members (user_id, group_id, status) VALUES ('$user_id', '$target_group_id', 'active')";
    if (mysqli_query($conn, $join_query)) {
        header("Location: group_details.php?id=" . $target_group_id . "&success=" . urlencode("Successfully joined the savings group circle!"));
        exit();
    } else {
        header("Location: my_groups.php?error=" . urlencode("Database mapping exception encountered during insertion routines."));
        exit();
    }
}
?>