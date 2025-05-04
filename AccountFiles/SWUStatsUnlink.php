<?php
// This script unlinks a user's SWUStats account by clearing the access token

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include required files for database connection and functions
include_once '../Database/ConnectionManager.php';
include_once './AccountDatabaseAPI.php';

// Check if user is logged in
if (!isset($_SESSION['userid'])) {
    // Redirect to login page if not logged in
    header('Location: ../LoginPage.php');
    exit();
}

// Get user ID from session
$userId = $_SESSION['userid'];

// Connect to database
$conn = GetLocalMySQLConnection();

// Prepare SQL statement to clear SWUStats tokens
$sql = "UPDATE users SET swustatsAccessToken = NULL, swustatsRefreshToken = NULL, swustatsTokenExpiry = NULL WHERE usersId = ?";
$stmt = mysqli_stmt_init($conn);

if (!mysqli_stmt_prepare($stmt, $sql)) {
    // Error in SQL statement preparation
    $_SESSION['error'] = "Database error occurred. Please try again later.";
    header('Location: ../ProfilePage.php');
    exit();
}

// Bind parameters and execute statement
mysqli_stmt_bind_param($stmt, "i", $userId);
mysqli_stmt_execute($stmt);

// Check if update was successful
if (mysqli_stmt_affected_rows($stmt) > 0) {
    // Success message
    $_SESSION['success_message'] = "SWUStats account has been unlinked successfully.";
} else {
    // No rows affected - user may not have had a linked account
    $_SESSION['info_message'] = "No SWUStats account was linked to your profile.";
}

// Close statement and connection
mysqli_stmt_close($stmt);
mysqli_close($conn);

// Redirect back to profile page
header('Location: ../ProfilePage.php');
exit();
?>