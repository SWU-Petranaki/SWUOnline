<?php

include "Libraries/SHMOPLibraries.php";
include "HostFiles/Redirector.php";
include "CardDictionary.php";
include_once 'MenuBar.php';
include_once "./AccountFiles/AccountDatabaseAPI.php";
include_once './includes/functions.inc.php';
include_once './includes/dbh.inc.php';

define('ROOTPATH', __DIR__);

$path = ROOTPATH . "/Games";

$currentlyActiveGames = "";
$spectateLinks = "";
$premierLinks = "";
$premierStrictLinks = "";
$previewLinks = "";
$otherFormatsLinks = "";
$previewStrictLinks = "";
// TODO: Have as a global variable.
$reactFE = "https://fe.talishar.net/game/play";

$isUserBanned = isset($_SESSION["userid"]) ? IsBanned($_SESSION["userid"]) : false;
$canSeeQueue = isset($_SESSION["useruid"]);

echo ("<div class='SpectatorContainer'>");
echo ("<h2>Public Games</h2>");
$gameInProgressCount = 0;
if ($handle = opendir($path)) {
  while (false !== ($folder = readdir($handle))) {
    if ('.' === $folder)
      continue;
    if ('..' === $folder)
      continue;
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
        //$p1SecondHero = GetCachePiece($gameToken, 22);
        //$p2SecondHero = GetCachePiece($gameToken, 23);
        //if($p2Hero != "") $gameInProgressCount += 1;
        $gameInProgressCount += 1;
        $visibility = GetCachePiece($gameToken, 9);


        if ($p2Hero != "" && $visibility == "1") {
          $spectateLinks .= <<<HTML
            <style>

            .hero-container {
              display: flex;
              align-items: center;
              column-gap: 10px
            }

            .hero-image {
              height: 50px;
              max-width: inherit;
            }

            </style>

            <form class='spectate-form' action='https://petranaki.net/Arena/NextTurn4.php?gameName=$gameToken&playerID=3'>
                <div class='spectate-container'>
                    <div class='hero-container'>
          HTML;

          if ($p1Hero == "") {
            $spectateLinks .= "<label for='joinGame' class='last-update-label'>Last Update " . intval(($currentTime - $lastGamestateUpdate) / 1000) . " seconds ago </label>";
          } else {
            $spectateLinks .= <<<HTML
              <img class='hero-image' src='./WebpImages2/$p1Hero.webp' alt='Player 1 Hero Image' />
              <img class='hero-image' src='./WebpImages2/$p1Base.webp' alt='Player 1 Base Image' />
              <span class='versus'>vs</span>
              <img class='hero-image' src='./WebpImages2/$p2Hero.webp' alt='Player 2 Hero Image' />
              <img class='hero-image' src='./WebpImages2/$p2Base.webp' alt='Player 2 Base Image' />
            HTML;
          }

          $spectateLinks .= <<<HTML
            </div>
          HTML;

          if (isset($_SESSION['userid'])) {
            $spectateLinks .= <<<HTML
              <input class='spectate-button' type='submit' id='joinGame' value='Spectate' />
              <input type='hidden' name='gameName' value='$gameToken' />
              <input type='hidden' name='playerID' value='3' />
            HTML;
          }

          $spectateLinks .= <<<HTML
            </div>
          </form>
          HTML;
        }
      } else if ($currentTime - $lastGamestateUpdate > 900_000) //15 minutes
      {
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
      if ($lastRefresh != "" && $currentTime - $lastRefresh < 500) {
        include 'MenuFiles/ParseGamefile.php';
        $status = $gameStatus;
        UnlockGamefile();
      } else if ($lastRefresh == "" || $currentTime - $lastRefresh > 900_000) //15 minutes
      {
        if ($autoDeleteGames) {
          deleteDirectory($folder);
          DeleteCache($gameToken);
        }
      }
    }

    if ($status == 0 && $visibility == "public" && intval(GetCachePiece($gameName, 11)) < 3) {
      $p1Hero = GetCachePiece($gameName, 7);
      $p1Base = GetCachePiece($gameName, 20);
      if ($p1Hero != "" && $p1Base != "") {
        $formatName = FormatDisplayName($format);

        $link = "<form style='text-align:center;' action='" . $redirectPath . "/JoinGame.php'>";
        $link .= "<table class='game-item' cellspacing='0'><tr>";
        $link .= "<td class='game-name'>";
        if ($formatName != "" && $formatName != "Premier (Best of 3)" && $formatName != "Premier Casual") {
            $link .= "<p class='format-title'>" . $formatName . "</p>";
        }
        $description = ($gameDescription == "" ? "Game #" . $gameName : $gameDescription);
        $link .= "<p style='word-break: break-word;'>" . $description . "</p></td>";
        $link .= "<td>" . <<<HTML
          <style>
            .hero-container {
              display: flex;
              align-items: center;
              column-gap: 10px
            }

            .hero-image {
              height: 50px;
              max-width: inherit;
            }
          </style>
          <div class='hero-container'>
            <img class='hero-image' src='./WebpImages2/$p1Hero.webp' alt='Player 1 Hero Image' />
            <img class='hero-image' src='./WebpImages2/$p1Base.webp' alt='Player 1 Base Image' />
          </div>
        HTML
        . "</td>";
        $link .= "<td><input style='margin-left: 24px;' class='ServerChecker_Button' type='submit' id='joinGame' value='Join Game' /></td></tr>";
        $link .= "</table>";
        $link .= "<input type='hidden' name='gameName' value='$gameToken' />";
        $link .= "<input type='hidden' name='playerID' value='2' />";
        $link .= "</form>";

        if (!$isUserBanned) {
          switch ($format) {
            case "premierf":
              $premierLinks .= $link;
              break;
            case "prstrict":
              $premierStrictLinks .= $link;
              break;
            case "previewf":
              $previewLinks .= $link;
              break;
            case "pwstrict":
              $previewStrictLinks .= $link;
              break;
            default:
              if ($format != "shadowblitz" && $format != "shadowcc")
                $otherFormatsLinks .= $link;
              break;
          }
        } else {
          if ($format == "shadowblitz")
            $blitzLinks .= $link;
          else if ($format == "shadowcc")
            $ccLinks .= $link;
        }
      }
    }
  }
  closedir($handle);
}
if ($canSeeQueue) {
  echo ("<h3 style='text-align: right;'>Premier Casual</h3>");
  echo ("<hr/>");
  echo ($premierLinks);
  echo ("<h3 style='text-align: right;'>Premier (Best of 3)</h3>");
  echo ("<hr/>");
  echo ($premierStrictLinks);
  // echo ("<h3>Preview</h3>");
  // echo ("<hr/>");
  // echo ($previewLinks);
  // echo ("<h3>Open Format</h3>");
  // echo ("<hr/>");
  // echo ($openFormatLinks);
  echo ("<h3 style='text-align: right;'>Other Formats</h3>");
  echo ("<hr/>");
  echo ($otherFormatsLinks);
}
if (!$canSeeQueue) {
  echo ("<p class='login-notice'>&#10071;<a href='./LoginPage.php'>Log In</a> to use matchmaking and see open matches</p>");
}
echo ("<div class='progress-title-wrapper'>");
echo ("<h3 class='progress-header'>Games In Progress</h3>");
echo ("<h3 class='progress-count'>$gameInProgressCount</h3>");
echo ("</div>");
echo ("<hr/>");
//if (!IsMobile()) {
echo ($spectateLinks);
//}
echo ("</div>");

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