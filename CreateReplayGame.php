<?php
session_start();

ob_start();
include "HostFiles/Redirector.php";
include "Libraries/HTTPLibraries.php";
include "Libraries/SHMOPLibraries.php";
include_once "WriteLog.php";
include_once "Libraries/PlayerSettings.php";
include_once 'Assets/patreon-php-master/src/PatreonDictionary.php';
ob_end_clean();

$userId = "";
if(isset($_SESSION["userid"])) $userId = $_SESSION["userid"];
if($userId == "")
{
  echo("You must be logged in to use this feature.");
  exit;
}
$replayNumber = TryGet("replayNumber", "");

if(!file_exists("./Replays/" . $userId . "/" . $replayNumber . "/"))
{
  echo("That replay file does not exist.");
  exit;
}

$gameName = GetGameCounter();

if (file_exists("Games/$gameName") || !mkdir("Games/$gameName", 0700, true)) {
  print_r("Encountered a problem creating a game. Please return to the main menu and try again");
}

$p1Data = [1];
$p2Data = [2];

$gameStatus = 5; //Game started

$firstPlayerChooser = "";
$firstPlayer = 1;
$p1Key = hash("sha256", rand() . rand());
$p2Key = hash("sha256", rand() . rand() . rand());
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
$isReplay = "1";
WriteCache($gameName, 1 . "!" . $currentTime . "!" . $currentTime . "!0!-1!" . $currentTime . "!!!0!" . $isReplay . "!0!0!0!" . $gameStatus . "!0!0!$currentTime!0!0!!!!!1!0!0" . "!0!0!0!0"); //Initialize SHMOP cache for this game

copy("./Replays/" . $userId . "/" . $replayNumber . "/origGamestate.txt", "./Games/" . $gameName . "/gamestate.txt");
copy("./Replays/" . $userId . "/" . $replayNumber . "/replayCommands.txt", "./Games/" . $gameName . "/replayCommands.txt");

header("Location: NextTurn4.php?gameName=$gameName&playerID=3");
