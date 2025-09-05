<meta name="viewport" content="width=device-width, initial-scale=0.67">
<?php

include_once 'MenuBar.php';

include_once './includes/functions.inc.php';
include_once './AccountFiles/AccountSessionAPI.php';
include_once './AccountFiles/AccountDatabaseAPI.php';

if (!isset($_SESSION["useruid"])) {
  echo ("Please login to view this page.");
  exit;
}
$useruid = $_SESSION["useruid"];
if (!IsUserLoggedInAsSqlMod()) {
  echo ("You must log in to use this page.");
  exit;
}

?>
<div style='padding:10px; width:80vw; max-width: 640px; height: 70vh; margin: 20vh auto;
  background-color:rgba(74, 74, 74, 0.9); border: 2px solid #1a1a1a; border-radius: 5px; overflow-y: scroll;'>

<h2>Force Change User Password</h2>

<?php
// Check if a password was changed
if(isset($_GET["error"])) {
  if($_GET["error"] == "emptyinput") {
    echo "<p style='color:red;'>Fill in all fields.</p>";
  } else if($_GET["error"] == "passwordsdontmatch") {
    echo "<p style='color:red;'>Passwords don't match.</p>";
  } else if($_GET["error"] == "usernotfound") {
    echo "<p style='color:red;'>User not found.</p>";
  } else if($_GET["error"] == "stmtfailed") {
    echo "<p style='color:red;'>Something went wrong.</p>";
  } else if($_GET["error"] == "none") {
    echo "<p style='color:green;'>Password successfully changed!</p>";
  }
}
?>

<form action="includes/change-password.inc.php" method="post">
  <label for="username" style='font-weight:bolder; margin-left:10px;'>Username:</label>
  <input type="text" id="username" name="username" value=""><br><br>

  <label for="email" style='font-weight:bolder; margin-left:10px;'>Email:</label>
  <input type="email" id="email" name="email" value=""><br><br>

  <label for="pwd" style='font-weight:bolder; margin-left:10px;'>New Password:</label>
  <input type="password" id="pwd" name="newpwd"><br><br>

  <label for="pwdrepeat" style='font-weight:bolder; margin-left:10px;'>Confirm Password:</label>
  <input type="password" id="pwdrepeat" name="confirmnewpwd"><br><br>

  <input type="hidden" name="modoverride" value="true">
  <input type="submit" name="submit" value="Change Password">
</form>

</div>