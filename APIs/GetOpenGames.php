<?php

include "../Libraries/SHMOPLibraries.php";
include "../Libraries/IOLibraries.php";
include "../Libraries/HTTPLibraries.php";
include "../Libraries/GameFormats.php";
include "../HostFiles/Redirector.php";
include "../CardDictionary.php";
include "../AccountFiles/AccountSessionAPI.php";
include_once "../AccountFiles/AccountDatabaseAPI.php";
include_once '../includes/functions.inc.php';
include_once '../includes/dbh.inc.php';

$path = "../Games";

session_start();
SetHeaders();

// Create response object
$response = new stdClass();
$response->openGames = [];
$response->canSeeQueue = IsUserLoggedIn();
$response->totalGames = 0; // Will only count actual active games
$isUserBanned = isset($_SESSION["userid"]) ? IsBanned($_SESSION["userid"]) : false;

if ($handle = opendir($path)) {
  while (false !== ($folder = readdir($handle))) {
    if ('.' === $folder) continue;
    if ('..' === $folder) continue;

    $gameToken = $folder;
    $folder = $path . "/" . $folder . "/";
    $gs = $folder . "gamestate.txt";
    $currentTime = round(microtime(true) * 1000);

    // Skip if game is already in progress
    if (file_exists($gs)) {
      $lastGamestateUpdate = intval(GetCachePiece($gameToken, 6));
      // Only count in-progress games with recent updates (last 30 seconds)
      if ($lastGamestateUpdate != "" && $currentTime - $lastGamestateUpdate < 30000) {
        $response->totalGames++;
      } else if ($currentTime - $lastGamestateUpdate > 900_000) { //15 minutes
        // Delete old game files
        if ($autoDeleteGames) {
          deleteDirectory($folder);
          DeleteCache($gameToken);
        }
      }
      continue;
    }

    $gf = $folder . "GameFile.txt";
    $gameName = $gameToken;
    $lineCount = 0;
    $status = -1;

    if (file_exists($gf)) {
      $lastRefresh = intval(GetCachePiece($gameName, 2)); //Player 1 last connection time
      // Use 500ms timeout like in ServerChecker instead of 15 minutes
      if ($lastRefresh != "" && $currentTime - $lastRefresh < 500) {
        $gameFileHandler = fopen($gf, "r+");
        if ($gameFileHandler) {
          if (flock($gameFileHandler, LOCK_EX)) {
            $p1Data = GetArray($gameFileHandler);
            $p2Data = GetArray($gameFileHandler);
            $gameStatus = trim(fgets($gameFileHandler));
            $format = trim(fgets($gameFileHandler));
            $visibility = trim(fgets($gameFileHandler));
            $gameDescription = trim(fgets($gameFileHandler));

            flock($gameFileHandler, LOCK_UN);
            fclose($gameFileHandler);

            $status = $gameStatus;
          }
        }
      } else if ($lastRefresh == "" || $currentTime - $lastRefresh > 900_000) { //15 minutes
        if ($autoDeleteGames) {
          deleteDirectory($folder);
          DeleteCache($gameToken);
        }
        continue;
      }
    }

    if ($status == 0 && $visibility == "public" && intval(GetCachePiece($gameName, 11)) < 3) {
      $p1Hero = GetCachePiece($gameName, 7);
      $p1Base = GetCachePiece($gameName, 20);

      if ($p1Hero != "" && $p1Base != "") {
        $openGame = new stdClass();
        $formatName = FormatDisplayName($format);

        $description = ($gameDescription == "" ? "Game #" . $gameName : $gameDescription);

        $openGame->gameName = $gameToken;
        $openGame->format = $format;
        $openGame->formatName = $formatName;
        $openGame->description = $description;
        $openGame->p1Hero = $p1Hero;
        $openGame->p1Base = $p1Base;

        // Only include if user is not banned or if the format is shadowblitz/shadowcc for banned users
        $shouldInclude = !$isUserBanned || ($format == "shadowblitz" || $format == "shadowcc");

        if ($shouldInclude) {
          $response->openGames[] = $openGame;
          // Only count games we actually return as open
          $response->totalGames++;
        }
      }
    }
  }
  closedir($handle);
}

// Set content type to JSON
header('Content-Type: application/json');
echo json_encode($response);

function deleteDirectory($dir)
{
  if (!file_exists($dir)) {
    return true;
  }

  if (!is_dir($dir)) {
    $handler = fopen($dir, "w");
    if($handler) {
      fwrite($handler, "");
      fclose($handler);
      return unlink($dir);
    }
    return true;
  }

  foreach (scandir($dir) as $item) {
    if ($item == '.' || $item == '..') {
      continue;
    }
    if (!deleteDirectory($dir . "/" . $item)) {
      return false;
    }
  }
  return rmdir($dir);
}