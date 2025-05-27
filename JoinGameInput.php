<?php

include "Libraries/HTTPLibraries.php";
include "Libraries/SHMOPLibraries.php";
include "Libraries/GameFormats.php";
include "Libraries/NetworkingLibraries.php";
include "APIKeys/APIKeys.php";
include_once 'includes/functions.inc.php';
include_once 'includes/dbh.inc.php';
include_once 'CoreLogic.php';
include_once 'Libraries/CoreLibraries.php';
include_once "WriteLog.php";
include_once 'CardOverrides.php';

include_once 'LZCompressor/LZContext.php';
include_once 'LZCompressor/LZData.php';
include_once 'LZCompressor/LZReverseDictionary.php';
include_once 'LZCompressor/LZString.php';
include_once 'LZCompressor/LZUtil.php';
include_once 'LZCompressor/LZUtil16.php';

session_start();
if (!isset($_SESSION["userid"])) {
  if (isset($_COOKIE["rememberMeToken"])) {
    include_once './Assets/patreon-php-master/src/PatreonLibraries.php';
    include_once './Assets/patreon-php-master/src/API.php';
    include_once './Assets/patreon-php-master/src/PatreonDictionary.php';
    loginFromCookie();
  }
}

$gameName = $_GET["gameName"];
if (!IsGameNameValid($gameName)) {
  echo ("Invalid game name.");
  exit;
}
$playerID = intval($_GET["playerID"]);
$deck = TryGet("deck");
$decklink = $_GET["fabdb"];
$decklinkP2 = TryGet("fabdbP2", "");
$decksToTry = TryGet("decksToTry");
$favoriteDeck = TryGet("favoriteDeck", "0");
$favoriteDeckLink = TryGet("favoriteDecks", "0");
$set = TryGet("set");
$matchup = TryGet("matchup", "");
$starterDeck = false;

if ($matchup == "" && GetCachePiece($gameName, $playerID + 6) != "") {
  $_SESSION['error'] = '⚠️ Another player has already joined the game.';
  header("Location: MainMenu.php");
  die();
}

include "HostFiles/Redirector.php";
include "CardDictionary.php";
include "MenuFiles/ParseGamefile.php";
include "MenuFiles/WriteGamefile.php";
if($playerID == 2 && isset($_SESSION["userid"])) {
  $iBlockedThem = false;
  $theyBlockedMe = false;

  $myBlockedPlayers = LoadBlockedPlayers($_SESSION["userid"]);
  for($i=0; $i<count($myBlockedPlayers); $i+=2) {
    if($myBlockedPlayers[$i] == $p1id) {
      $iBlockedThem = true;
      break;
    }
  }
  $theirBlockedPlayers = LoadBlockedPlayers($p1id);
  for($i=0; $i<count($theirBlockedPlayers); $i+=2) {
    if($theirBlockedPlayers[$i] == $_SESSION["userid"]) {
      $theyBlockedMe = true;
      break;
    }
  }

  if ($iBlockedThem || $theyBlockedMe) {
    $_SESSION['error'] = '⚠️ Another player has already joined the game.';
    header("Location: MainMenu.php");
    die();
  }

  if ($matchup == "" && GetCachePiece($gameName, $playerID + 6) != "") {
    $_SESSION['error'] = '⚠️ Another player has already joined the game.';
    header("Location: MainMenu.php");
    die();
  }
}

if ($decklink == "" && $deck == "" && $favoriteDeckLink == "0") {
  $starterDeck = true;
  switch($decksToTry) {

    default:
        $deck = "./test.txt";
      break;
  }
}

if ($favoriteDeckLink != "0" && $decklink == "") $decklink = $favoriteDeckLink;

if ($deck == "" && !IsDeckLinkValid($decklink)) {
  echo '<b>' . "⚠️ Deck URL is not valid: " . $decklink . '</b>';
  exit;
}

if ($matchup == "" && $playerID == 2 && $gameStatus >= $MGS_Player2Joined) {
  if ($gameStatus >= $MGS_GameStarted) {
    header("Location: " . $redirectPath . "/NextTurn4.php?gameName=$gameName&playerID=3");
  } else {
    header("Location: " . $redirectPath . "/MainMenu.php");
  }
  WriteGameFile();
  exit;
}

$usesUuid = false;

if ($decklink != "") {
  if ($playerID == 1) $p1DeckLink = $decklink;
  else if ($playerID == 2) $p2DeckLink = $decklink;
  LoadPlayerDeck($decklink, $redirectPath, $format, $gameName, $playerID, $favoriteDeck, $usesUuid);
  if($playerID == 1 && IsOnePlayerMode() && $decklinkP2 != "") {
    $p2DeckLink = $decklinkP2;
    LoadPlayerDeck($decklinkP2, $redirectPath, $format, $gameName, 2, $favoriteDeck, $usesUuid);
  }
} else {
  $_SESSION['error'] = '⚠️ Deck link is empty. Did you maybe copy your deck link into the Game Name field?';
  header("Location: " . $redirectPath . "/MainMenu.php");
  WriteGameFile();
  exit;
  // copy($deckFile, "./Games/" . $gameName . "/p" . $playerID . "Deck.txt");
  // copy($deckFile, "./Games/" . $gameName . "/p" . $playerID . "DeckOrig.txt");
}

if ($playerID == 1) {
  $p1uid = ($_SESSION["useruid"] ?? "Player 1");
  $p1id = ($_SESSION["userid"] ?? "");
  $p1SWUStatsToken = ($_SESSION["swustatsAccessToken"] ?? "");
  $p1IsPatron = (isset($_SESSION["isPatron"]) ? "1" : "");
  $p1ContentCreatorID = ($_SESSION["patreonEnum"] ?? "");
  $playerNames[1] = $p1uid;
} else if ($playerID == 2) {
  $p2uid = ($_SESSION["useruid"] ?? "Player 2");
  $p2id = ($_SESSION["userid"] ?? "");
  $p2SWUStatsToken = ($_SESSION["swustatsAccessToken"] ?? "");
  $p2IsPatron = (isset($_SESSION["isPatron"]) ? "1" : "");
  $p2ContentCreatorID = ($_SESSION["patreonEnum"] ?? "");
  $playerNames[2] = $p2uid;
}

if ($matchup == "") {
  if ($playerID == 2) {

    $gameStatus = $MGS_Player2Joined;
    if (file_exists("./Games/" . $gameName . "/gamestate.txt")) unlink("./Games/" . $gameName . "/gamestate.txt");

    $firstPlayerChooser = 1;
    $p1roll = 0;
    $p2roll = 0;
    $tries = 10;
    while ($p1roll == $p2roll && $tries > 0) {
      $p1roll = rand(1, 6) + rand(1, 6);
      $p2roll = rand(1, 6) + rand(1, 6);
      WriteLog("$p1uid rolled $p1roll and $p2uid rolled $p2roll.");
      --$tries;
    }
    $firstPlayerChooser = ($p1roll > $p2roll ? 1 : 2);
    $playerName = $playerNames[$firstPlayerChooser];
    WriteLog("$playerName chooses who goes first.");
    $gameStatus = $MGS_ChooseFirstPlayer;
    $joinerIP = $_SERVER['REMOTE_ADDR'];
  }

  if ($playerID == 2 && !IsOnePlayerMode()) $p2Key = hash("sha256", rand() . rand() . rand());

  WriteGameFile();
  SetCachePiece($gameName, $playerID + 1, strval(round(microtime(true) * 1000)));
  SetCachePiece($gameName, $playerID + 3, "0");
  SetCachePiece($gameName, $playerID + 6, $leader ?? "-");
  SetCachePiece($gameName, $playerID + 19, $base ?? "-");
  SetCachePiece($gameName, 14, $gameStatus);
  GamestateUpdated($gameName);

  //$authKey = ($playerID == 1 ? $p1Key : $p2Key);
  //$_SESSION["authKey"] = $authKey;
  $domain = (!empty(getenv("DOMAIN")) ? getenv("DOMAIN") : "petranaki.net");
  if ($playerID == 1) {
    $_SESSION["p1AuthKey"] = $p1Key;
    setcookie("lastAuthKey", $p1Key, time() + 86400, "/", $domain);
    if(IsOnePlayerMode() && $decklinkP2 != "") {
      $_SESSION["p2AuthKey"] = $p2Key;
    }
  } else if ($playerID == 2) {
    $_SESSION["p2AuthKey"] = $p2Key;
    setcookie("lastAuthKey", $p2Key, time() + 86400, "/", $domain);
  }
}

session_write_close();
header("Location: " . $redirectPath . "/GameLobby.php?gameName=$gameName&playerID=$playerID");

function JsHtmlTitleAndSub($cardID) {
  $forJS = CardTitle($cardID);
  if($forJS == "") return $cardID;
  if(CardSubtitle($cardID) != "") $forJS .= " (" . CardSubtitle($cardID) . ")";
  return str_replace("'", "\'", $forJS);
}

function LoadPlayerDeck($decklink, $redirectPath, $format, $gameName, $playerID, $favoriteDeck, $usesUuid) {
  $originalLink = $decklink;

  if(str_contains($decklink, "swustats.net")) {
    $decklinkArr = explode("gameName=", $decklink);
    if(count($decklinkArr) > 1) {
      $deckLinkArr = explode("&", $decklinkArr[1]);
      $deckID = $deckLinkArr[0];
      $decklink = "https://swustats.net/TCGEngine/APIs/LoadDeck.php?deckID=" . $deckID . "&format=json";
      $curl = curl_init();
      curl_setopt($curl, CURLOPT_URL, $decklink);
      curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
      $apiDeck = curl_exec($curl);
      $apiInfo = curl_getinfo($curl);
      $errorMessage = curl_error($curl);
      curl_close($curl);
      $json = $apiDeck;
      echo($json);
      $usesUuid = true;
    }
  }
  else if(str_contains($decklink, "swudb.com/deck")) {
    $decklinkArr = explode("/", $decklink);
    $decklink = "https://swudb.com/api/getDeckJson/" . trim($decklinkArr[count($decklinkArr) - 1]);
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $decklink);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    $apiDeck = curl_exec($curl);
    $apiInfo = curl_getinfo($curl);
    $errorMessage = curl_error($curl);
    curl_close($curl);
    $json = $apiDeck;
    //echo($json);
  }
  else if(str_contains($decklink, "sw-unlimited-db.com/decks")) {
    $decklinkArr = explode("/", $decklink);
	  $deckId = trim($decklinkArr[count($decklinkArr) - 1]);
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, "https://sw-unlimited-db.com/umbraco/api/deckapi/get?id=" . $deckId);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    $apiDeck = curl_exec($curl);
    $apiInfo = curl_getinfo($curl);
    $errorMessage = curl_error($curl);
    curl_close($curl);
    $json = $apiDeck;
    //echo($json);
  }
  else $json = $decklink;

  if($json == "") {
    echo "Failed to retrieve deck from API. Check to make sure you have a valid deckbuilder link. If it's a SWUDB link, make sure it's not a private deck.<BR>";
    echo "Your link: " . $originalLink . "<BR>";
    echo "API link: " . $decklink . "<BR>";
    echo "Error Message: " . $errorMessage . "<BR>";
    exit;
  }
  $previewSet = "LOF";
  $deckObj = json_decode($json);
  $deckName = $deckObj->metadata->{"name"};
  $setCodeLeader = $deckObj->leader->id;
  $leader = !$usesUuid ? UUIDLookup($setCodeLeader) : $setCodeLeader;
  if($leader == "" && str_starts_with($setCodeLeader, $previewSet . "_")) {
    $_SESSION['error'] = "<div>⚠️ Error: Leader $setCodeLeader is not available yet in Petranaki.<br/>Please tell the devs on our Discord to add it manually.</div>";
    header("Location: " . $redirectPath . "/MainMenu.php");
    WriteGameFile();
    exit;
  }
  else if($leader == "") {
    $_SESSION['error'] = "<div>⚠️ Error: Deck link not supported. <br/>Make sure it is not private and that the deck link is correct.</div>";
    header("Location: " . $redirectPath . "/MainMenu.php");
    WriteGameFile();
    exit;
  }
  $deckFormat = 1;
  $baseSetCode = $deckObj->base->id;
  $base = !$usesUuid ? UUIDLookup($baseSetCode) : $baseSetCode;
  if($base == "" && str_starts_with($baseSetCode, $previewSet . "_")) {
    $_SESSION['error'] = "<div>⚠️ Error: Base $baseSetCode is not available yet in Petranaki.<br/>Please tell the devs on our Discord to add it manually.</div>";
    header("Location: " . $redirectPath . "/MainMenu.php");
    WriteGameFile();
    exit;
  }
  else if($base == "") {
    $_SESSION['error'] = "<div>⚠️ Error: Deck link not supported. <br/>Make sure it is not private and that the deck link is correct.</div>";
    header("Location: " . $redirectPath . "/MainMenu.php");
    WriteGameFile();
    exit;
  }
  $deck = $deckObj->deck;
  $sideboard = $deckObj->sideboard;
  if(!IsAllowed($leader, $format)) {
    $_SESSION['error'] = "<div>⚠️ Your deck contains a leader that is not allowed in this format.</div>";
    header("Location: " . $redirectPath . "/MainMenu.php");
    WriteGameFile();
    exit;
  }
  if(!IsAllowed($base, $format)) {
    $_SESSION['error'] = "<div>⚠️ Your deck contains a base that is not allowed in this format.</div>";
    header("Location: " . $redirectPath . "/MainMenu.php");
    WriteGameFile();
    exit;
  }
  $validation = ValidateDeck($format, $usesUuid, $leader, $base, $deck, $sideboard);
  if (!$validation->IsValid()) {
    $_SESSION['error'] = "<div>" . $validation->Error($format) . "</div>";
    if(count($validation->InvalidCards()) > 0) {
      $rejectionDetail = $validation->RejectionDetail($format);
      $_SESSION['error'] .= "<div><div><h3>" . $rejectionDetail . "</h3><h2>Invalid Cards:</h2></div><ul>"
        . implode("", array_map(function($x) {
          return "<li>" . JsHtmlTitleAndSub($x) . "</li>";
        }, $validation->InvalidCards())) . "</ul></div>";
    } else if(count($validation->UnavailableCards()) > 0) {
      $rejectionDetail = $validation->RejectionDetail($format);
      $_SESSION['error'] .= "<div><div><h3>" . $rejectionDetail . "</h3><h2>Unavailable Cards:</h2></div><ul>"
        . implode("", array_map(function($x) {
          return "<li>" . JsHtmlTitleAndSub($x) . "</li>";
        }, $validation->UnavailableCards())) . "</ul></div>";
    }
    header("Location: " . $redirectPath . "/MainMenu.php");
    WriteGameFile();
    exit;
  }
  $cards = $validation->CardString();
  $sideboardCards = $validation->SideboardString();
  //We have the decklist, now write to file
  $filename = "./Games/" . $gameName . "/p" . $playerID . "Deck.txt";
  $deckFile = fopen($filename, "w");
  fwrite($deckFile, $base . " " . $leader . "\r\n");
  fwrite($deckFile, $cards . "\r\n");
  fwrite($deckFile, $sideboardCards . "\r\n");
  fclose($deckFile);
  copy($filename, "./Games/" . $gameName . "/p" . $playerID . "DeckOrig.txt");

  if ($favoriteDeck == "on" && isset($_SESSION["userid"])) {
    //Save deck
    include_once './includes/functions.inc.php';
    include_once "./includes/dbh.inc.php";
    $saveLink = explode("https://", $originalLink);
    $saveLink = count($saveLink) > 1 ? $saveLink[1] : $originalLink;
    addFavoriteDeck($_SESSION["userid"], $saveLink, $deckName, $leader, $deckFormat);
  }
}
