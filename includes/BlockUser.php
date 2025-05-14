<?php
if (!isset($_POST['block-user-submit'])) {
  exit();
}

session_start();

if (!isset($_SESSION['userid'])) {
  header('Location: ./MainMenu.php');
  exit();
}

$userToBlock = trim($_POST['userToBlock']);
if (empty($userToBlock)) {
  header("Location: ../ProfilePage.php");
  exit();
}

require 'dbh.inc.php';
$conn = GetDBConnection();

$sql = "SELECT usersId FROM users WHERE usersUid=?";
$stmt = mysqli_stmt_init($conn);
if (!mysqli_stmt_prepare($stmt, $sql)) {
  echo "There was an error preparing the blocked user lookup query.";
  mysqli_close($conn);
  exit();
}

mysqli_stmt_bind_param($stmt, "s", $userToBlock);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (!$row = mysqli_fetch_assoc($result)) {
  echo "The user you are trying to block could not be found in the database.";
  mysqli_close($conn);
  exit();
}

$sql = "INSERT INTO blocklist (blockingPlayer, blockedPlayer) VALUES (?, ?)";
$stmt = mysqli_stmt_init($conn);
if (!mysqli_stmt_prepare($stmt, $sql)) {
  echo "There was an error preparing the blocklist insert query.";
  mysqli_close($conn);
  exit();
}

mysqli_stmt_bind_param($stmt, "ss", $_SESSION['userid'], $row['usersId']);
mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);
mysqli_close($conn);

echo "You have successfully blocked " . htmlspecialchars($userToBlock) . ".";
echo "You can now go back.";
echo "<br><button onclick=\"window.history.back()\">Go Back</button>";
exit();
