<meta name="viewport" content="width=device-width, initial-scale=0.67">
<?php

include_once 'MenuBar.php';

include_once './includes/functions.inc.php';

if (!isset($_SESSION["useruid"])) {
  echo ("Please login to view this page.");
  exit;
}
$useruid = $_SESSION["useruid"];
if ($useruid != "OotTheMonk" && $useruid != "love" && $useruid != "ninin" && $useruid != "Brubraz" && $useruid != "Mobyus1") {
  echo ("You must log in to use this page.");
  exit;
}

$banfileHandler = fopen("./HostFiles/bannedPlayers.txt", "r");
?>
<script>
function BannedPlayersExpandCollapse() {
  var x = document.getElementsByClassName("banned-players-list")[0];
  if (x.style.display === "none") {
    x.style.display = "block";
  } else {
    x.style.display = "none";
  }
}
</script>
<div style='padding:10px; width:80vw; max-width: 640px; height: 70vh; margin: 20vh auto;
  background-color:rgba(74, 74, 74, 0.9); border: 2px solid #1a1a1a; border-radius: 5px; overflow-y: scroll;'>
<h2>Banned players:</h2>
<button onclick="BannedPlayersExpandCollapse()" style="font-size: 1rem; padding: 8px; margin: 8px;">Expand/Collapse</button>
<div class='banned-players-list' style='display:none;'>
<?php
while (!feof($banfileHandler)) {
  $bannedPlayer = fgets($banfileHandler);
  echo ($bannedPlayer . "<BR>");
}
fclose($banfileHandler);
?>
</div>
<form  action='./BanPlayer.php'>
<label for="playerToBan" style='font-weight:bolder; margin-left:10px;'>Player to ban:</label>
<input type="text" id="playerToBan" name="playerToBan" value="">
<input type="submit" value="Ban">
</form>

<form action='./BanPlayer.php'>
<label for="playerToUnban" style='font-weight:bolder; margin-left:10px;'>Player to unban:</label>
<input type="text" id="playerToUnban" name="playerToUnban" value="">
<input type="submit" value="Unban">
</form>
<hr>
<?php
$conn = GetDBConnection();
$sql = "SELECT usersUid FROM users ORDER BY usersId DESC LIMIT 20";
$stmt = mysqli_stmt_init($conn);
if (!mysqli_stmt_prepare($stmt, $sql)) {
  //header("location: ../Signup.php?error=stmtfailed");
  echo ("ERROR");
  exit();
}
//mysqli_stmt_bind_param($stmt, "ss", $username, $email);
mysqli_stmt_execute($stmt);
// "Get result" returns the results from a prepared statement
?>
<h2>Most recently created accounts:</h2>
<?php
$userData = mysqli_stmt_get_result($stmt);
while ($row = mysqli_fetch_array($userData, MYSQLI_NUM)) {
  echo ($row[0] . "<BR>");
}
mysqli_close($conn);
?>
<hr><h2>Banned IPs:</h2>
<?php
$banfileHandler = fopen("./HostFiles/bannedIPs.txt", "r");
while (!feof($banfileHandler)) {
  $bannedIP = fgets($banfileHandler);
  echo ($bannedIP . "<BR>");
}
fclose($banfileHandler);
?>
<form action='./BanPlayer.php'>
  <label for="ipToBan" style='font-weight:bolder; margin-left:10px;'>Game to IP ban from:</label>
  <input type="text" id="ipToBan" name="ipToBan" value=""><br>
  <label for="playerNumberToBan" style='font-weight:bolder; margin-left:10px;'>Player to ban? (1 or 2):</label>
  <input type="text" id="playerNumberToBan" name="playerNumberToBan" value="">
  <br>
  <input type="submit" value="Ban IP">
</form>
<hr>
<h2>Shut Down Game:</h2>
<form action='./CloseGame.php'>
  <label for="gameToClose" style='font-weight:bolder; margin-left:10px;'>Game to close:</label>
  <input type="text" id="gameToClose" name="gameToClose" value="">
  <input type="submit" value="Close Game">
</form>
<h2>Boot Players:</h2>
<form action='./BootPlayer.php'>
  <label for="gameToClose" style='font-weight:bolder; margin-left:10px;'>Game:</label>
  <input type="text" id="gameToClose" name="gameToClose" value="">
  <br>
  <label for="playerToBoot" style='font-weight:bolder; margin-left:10px;'>Player to boot:</label>
  <input type="text" id="playerToBoot" name="playerToBoot" value="">
  <input type="submit" value="Boot Player">
</form>

</div>