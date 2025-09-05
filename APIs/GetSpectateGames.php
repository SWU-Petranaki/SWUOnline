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

$path = "../Games";

session_start();
SetHeaders();

// Create response object
$response = new stdClass();
$response->gamesInProgress = [];
$gameInProgressCount = 0;

if ($handle = opendir($path)) {
  while (false !== ($folder = readdir($handle))) {
    if ('.' === $folder) continue;
    if ('..' === $folder) continue;

    $gameToken = $folder;
    $folder = $path . "/" . $folder . "/";
    $gs = $folder . "gamestate.txt";
    $currentTime = round(microtime(true) * 1000);

    if (file_exists($gs)) {
      $lastGamestateUpdate = intval(GetCachePiece($gameToken, 6));
      if ($currentTime - $lastGamestateUpdate < 30000) {
        $p1Hero = GetCachePiece($gameToken, 7);
        $p2Hero = GetCachePiece($gameToken, 8);
        $p1Base = GetCachePiece($gameToken, 20);
        $p2Base = GetCachePiece($gameToken, 21);
        $gameInProgressCount += 1;
        $visibility = GetCachePiece($gameToken, 9);

        // Only include games with 2 players and that are public
        if ($p2Hero != "" && $visibility == "1") {
          $gameInProgress = new stdClass();
          $gameInProgress->gameName = $gameToken;
          $gameInProgress->p1Hero = $p1Hero;
          $gameInProgress->p2Hero = $p2Hero;
          $gameInProgress->p1Base = $p1Base;
          $gameInProgress->p2Base = $p2Base;
          $gameInProgress->secondsSinceLastUpdate = intval(($currentTime - $lastGamestateUpdate) / 1000);
          $format = GetCachePiece($gameToken, 13);
          $gameInProgress->format = $format;
          $gameInProgress->formatName = FormatDisplayName($format);

          $response->gamesInProgress[] = $gameInProgress;
        }
      } else if ($currentTime - $lastGamestateUpdate > 900_000) { //15 minutes
        if ($autoDeleteGames) {
          deleteDirectory($folder);
          DeleteCache($gameToken);
        }
      }
    }
  }
  closedir($handle);
}

$response->gameInProgressCount = $gameInProgressCount;

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