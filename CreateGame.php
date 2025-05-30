<?php

ob_start();
include "HostFiles/Redirector.php";
include "Libraries/HTTPLibraries.php";
include "Libraries/SHMOPLibraries.php";
include "Libraries/NetworkingLibraries.php";
include_once "WriteLog.php";
include_once "Libraries/PlayerSettings.php";
include_once 'Assets/patreon-php-master/src/PatreonDictionary.php';
include_once "./AccountFiles/AccountDatabaseAPI.php";
include_once './includes/functions.inc.php';
include_once './includes/dbh.inc.php';
include_once './Database/ConnectionManager.php';
ob_end_clean();

$deck = TryGET("deck");
$decklink = TryGET("fabdb");
$decklinkP2 = TryGET("fabdbP2", "");
$deckTestMode = TryGET("deckTestMode", "");
$format = TryGET("format");
$visibility = TryGET("visibility");
$set = TryGET("set");
$decksToTry = TryGet("decksToTry");
$favoriteDeck = TryGet("favoriteDeck", "0");
$favoriteDeckLink = TryGet("favoriteDecks", "0");
$gameDescription = htmlentities(TryGet("gameDescription", "Game #"), ENT_QUOTES);
$deckbuilderID = TryGet("user", "");
$roguelikeGameID = TryGet("roguelikeGameID", "");
$startingHealth = TryGet("startingHealth", "");

if($favoriteDeckLink != 0)
{
  $favDeckArr = explode("<fav>", $favoriteDeckLink);
  if(count($favDeckArr) == 1) $favoriteDeckLink = $favDeckArr[0];
  else {
    $favoriteDeckIndex = $favDeckArr[0];
    $favoriteDeckLink = $favDeckArr[1];
  }
}

session_start();

if (!isset($_SESSION["userid"])) {
  if (isset($_COOKIE["rememberMeToken"])) {
    include_once './Assets/patreon-php-master/src/PatreonLibraries.php';
    include_once './Assets/patreon-php-master/src/API.php';
    include_once './Assets/patreon-php-master/src/PatreonDictionary.php';
    loginFromCookie();
  }
}

$isUserBanned = isset($_SESSION["userid"]) ? IsBanned($_SESSION["userid"]) : false;
if ($isUserBanned) {
  header("Location: PlayerBanned.php");
  exit;
}

if($visibility == "public" && $deckTestMode != "" && !isset($_SESSION["userid"])) {
  //Must be logged in to use matchmaking
  header("Location: MainMenu.php");
  exit;
}

if(isset($_SESSION["userid"]) && !IsOnePlayerMode())
{
  //Save game creation settings
  if(isset($favoriteDeckIndex))
  {
    ChangeSetting("", $SET_FavoriteDeckIndex, $favoriteDeckIndex, $_SESSION["userid"]);
  }
  ChangeSetting("", $SET_Format, FormatCode($format), $_SESSION["userid"]);
  ChangeSetting("", $SET_GameVisibility, ($visibility == "public" ? 1 : 0), $_SESSION["userid"]);
  if($deckbuilderID != "")
  {
    // if(str_contains($decklink, "fabrary")) storeFabraryId($_SESSION["userid"], $deckbuilderID);
    // else if(str_contains($decklink, "fabdb")) storeFabDBId($_SESSION["userid"], $deckbuilderID);
  }
}

session_write_close();

$gameName = GetGameCounter();

if (file_exists("Games/$gameName") || !mkdir("Games/$gameName", 0700, true)) {
  print_r("Encountered a problem creating a game. Please return to the main menu and try again");
}

$p1Data = [1];
$p2Data = [2];
$p1SideboardSubmitted = "0";
$p2SideboardSubmitted = "0";
if ($deckTestMode != "") {
  $gameStatus = 4; //ReadyToStart
  $p2SideboardSubmitted = "1";
  $opponentDeck = "./Assets/Dummy.txt";
  $fileName = "./Roguelike/Encounters/".$deckTestMode.".txt";
  if(file_exists($fileName)) $opponentDeck = $fileName;
  copy($opponentDeck, "./Games/" . $gameName . "/p2Deck.txt");
} else {
  $gameStatus = 0; //Initial
}
$firstPlayerChooser = "";
$firstPlayer = 1;
$p1Key = hash("sha256", rand() . rand());
$p2Key = IsOnePlayerMode() ? $p1Key : hash("sha256", rand() . rand() . rand());
$p1uid = "-";
$p2uid = "-";
$p1id = "-";
$p2id = "-";
$p1SWUStatsToken = "-";
$p2SWUStatsToken = "-";
$hostIP = $_SERVER['REMOTE_ADDR'];
$p1StartingHealth = $startingHealth;

$filename = "./Games/" . $gameName . "/GameFile.txt";
$gameFileHandler = fopen($filename, "w");
include "MenuFiles/WriteGamefile.php";
WriteGameFile();

CreateLog($gameName);

$currentTime = round(microtime(true) * 1000);
$cacheVisibility = ($visibility == "public" ? "1" : "0");
WriteCache($gameName, 1 . "!" . $currentTime . "!" . $currentTime . "!0!-1!" . $currentTime . "!!!" . $cacheVisibility . "!0!0!0!" . FormatCode($format) . "!" . $gameStatus . "!0!0!$currentTime!0!0!!!!!1!0!0" . "!0!0!0!0"); //Initialize SHMOP cache for this game

// Convert favoriteDeck parameter from "1" to "on" to match what JoinGameInput.php expects
$favoriteDeckParam = ($favoriteDeck == "1") ? "on" : $favoriteDeck;

header("Location:" . $redirectPath . "/JoinGameInput.php?gameName=$gameName&playerID=1&deck=$deck&fabdb=$decklink&format=$format&set=$set&decksToTry=$decksToTry&favoriteDeck=$favoriteDeckParam&favoriteDecks=$favoriteDeckLink&fabdbP2=$decklinkP2");
