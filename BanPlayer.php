<?php

include "./Libraries/HTTPLibraries.php";
include_once './includes/functions.inc.php';
include_once "./includes/dbh.inc.php";

session_start();

if (!isset($_SESSION["useruid"])) {
  echo ("Please login to view this page.");
  exit;
}
$useruid = $_SESSION["useruid"];
if ($useruid != "OotTheMonk" && $useruid != "love" && $useruid != "ninin" && $useruid != "Brubraz" && $useruid != "Mobyus1") {
  echo ("You must log in to use this page.");
  exit;
}

$playerToBan = TryGET("playerToBan", "");
$ipToBan = TryGET("ipToBan", "");
$playerNumberToBan = TryGET("playerNumberToBan", "");
$playerToUnban = TryGET("playerToUnban", "");

if ($playerToBan != "") {
  file_put_contents('./HostFiles/bannedPlayers.txt', $playerToBan . "\r\n", FILE_APPEND | LOCK_EX);
  BanPlayer($playerToBan);
}
if ($playerToUnban != "") {
  $bannedPlayers = file_get_contents('./HostFiles/bannedPlayers.txt');
  $bannedPlayers = str_replace($playerToUnban . "\r\n", "", $bannedPlayers);
  file_put_contents('./HostFiles/bannedPlayers.txt', $bannedPlayers, LOCK_EX);
  UnbanPlayer($playerToUnban);
}
if ($ipToBan != "") {
  $gameName = $ipToBan;
  include './MenuFiles/ParseGamefile.php';
  $ipToBan = ($playerNumberToBan == "1" ? $hostIP : $joinerIP);
  file_put_contents('./HostFiles/bannedIPs.txt', $ipToBan . "\r\n", FILE_APPEND | LOCK_EX);
}



header("Location: ./zzModPage.php");
