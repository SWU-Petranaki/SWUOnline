<?php
/*ob_start();
include "CardDictionary.php";
include "HostFiles/Redirector.php";
include "Libraries/UILibraries2.php";
include "Libraries/SHMOPLibraries.php";
include_once "Libraries/PlayerSettings.php";
include_once "Libraries/HTTPLibraries.php";
include_once "Assets/patreon-php-master/src/PatreonDictionary.php";
include_once "WriteLog.php";
include_once './AccountFiles/AccountDatabaseAPI.php';
include_once './Libraries/GameFormats.php';
include './Libraries/NetworkingLibraries.php';
ob_end_clean();

session_start();

$authKey = "";
$gameName = TryGET("gameName", "");
$playerID = TryGET("playerID", "");
//$rematchID = TryGET("rematchID", "");

if($gameName == "" || $playerID == "") exit;

if ($playerID == 1 && isset($_SESSION["p1AuthKey"]))
  $authKey = $_SESSION["p1AuthKey"];
else if ($playerID == 2 && isset($_SESSION["p2AuthKey"]))
  $authKey = $_SESSION["p2AuthKey"];
else if (isset($_GET["authKey"]))
  $authKey = $_GET["authKey"];

session_write_close();

if (($playerID == 1 || $playerID == 2) && $authKey == "") {
  if (isset($_COOKIE["lastAuthKey"]))
    $authKey = $_COOKIE["lastAuthKey"];
}

if (!file_exists("./Games/" . $gameName . "/GameFile.txt")) {
  header("Location: " . $redirectPath . "/MainMenu.php"); //If the game file happened to get deleted from inactivity, redirect back to the main menu instead of erroring out
  exit;
}

ob_start();
include "MenuFiles/ParseGamefile.php";
ob_end_clean();

$targetAuth = ($playerID == 1 ? $p1Key : $p2Key);
if (!isset($authKey) || $authKey != $targetAuth) {
  echo ("Invalid Auth Key");
  exit;
}

$deckFilePath = "./Games/" . $gameName . "/p" . $playerID . "Deck.txt";
if (!file_exists($deckFilePath)) {
  echo ("Deck file does not exist");
  exit;
}
$deckFileHandler = fopen($deckFilePath, "r+");
if (!$deckFileHandler) {
  echo ("Deck file could not be opened");
  exit;
}
//get first line, split it into 2 parts by space and get first part
$deckFileLine = fgets($deckFileHandler);
$charPieces = explode(" ", $deckFileLine);
include_once "GeneratedCode/GeneratedCardDictionaries.php";
$aspect = CardAspects($charPieces[0]);
$newBase = match($aspect) {
  "Vigilance" => "0119018087",
  "Command" => "0450346170",
  "Aggression" => "zzzzzzz010",
  "Cunning" => "zzzzzzz011",
  default => $charPieces[0]
};

$deckFileLine = str_replace($charPieces[0], $newBase, $deckFileLine);
fseek($deckFileHandler, 0);
fwrite($deckFileHandler, $deckFileLine);
fclose($deckFileHandler);

echo json_encode(array(
  "success" => true,
  "deckFileLine" => $newBase,
));
*/
?>