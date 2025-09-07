<?php

include_once '../AccountFiles/AccountSessionAPI.php';
require_once './functions.inc.php';

if(!IsUserLoggedIn()) {
  header("Location: ../MainMenu.php");
}

if (isset($_POST["submit"])) {
  // First we get the form data from the URL
  $modOverride = (IsUserLoggedInAsSqlMod() && isset($_POST["modoverride"])) ? $_POST["modoverride"] : false;
  $userID = LoggedInUser();
  //$currentPwd = $_POST["currentpwd"];//not used
  $newPwd = $_POST["newpwd"];
  $confirmNewPwd = $_POST["confirmnewpwd"];
  $email = ($modOverride && isset($_POST["email"])) ? $_POST["email"] : "";

  if($modOverride) {
    if(!isset($_POST["username"]) || empty($_POST["username"])) {
      header("location: ../zzModChangePassword.php?error=emptyinput");
      exit();
    }
    if(!isset($_POST["email"]) || empty($_POST["email"])) {
      header("location: ../zzModChangePassword.php?error=emptyinput");
      exit();
    }
    $userName = $_POST["username"];
    // Get userid from username
    $userID = GetUserIDFromUsername($userName);
    if($userID === null) {
      header("location: ../zzModChangePassword.php?error=usernotfound");
      exit();
    }
    if($modOverride && $email != "") {
      $savedEmail = GetUserEmailFromID($userID);
      if ($savedEmail != $email) {
        header("location: ../zzModChangePassword.php?error=usernotfound");
        exit();
      }
    }
  }

  // Then we run a bunch of error handlers to catch any user mistakes we can (you can add more than I did)
  // These functions can be found in functions.inc.php
  $conn = GetDBConnection();

  // Do the two passwords match?
  if ($newPwd != $confirmNewPwd) {
    header("location: ../ChangePassword.php?error=passwordsdontmatch");
    exit();
  }

  // If we get to here, it means there are no user errors

  // Now we change the password in the database
  changePassword($conn, $userID, $newPwd);
  mysqli_close($conn);
} else {
  header("location: ../ChangePassword.php");
  exit();
}
