<?php
ob_start();
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
include_once "./includes/functions.inc.php";
ob_end_clean();

session_start();

// Check if the user is banned
if (isset($_SESSION["userid"]) && IsBanned($_SESSION["userid"])) {
  header("Location: ./PlayerBanned.php");
  exit;
}

logUserIP();

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

if ($gameStatus == $MGS_GameStarted) {
  $authKey = ($playerID == 1 ? $p1Key : $p2Key);
  if (isset($gameUIPath))
    header("Location: " . $gameUIPath . "?gameName=$gameName&playerID=$playerID");
  else
    header("Location: " . $redirectPath . "/NextTurn4.php?gameName=$gameName&playerID=$playerID");
  exit;
}

$icon = "ready.png";

if ($gameStatus == $MGS_ChooseFirstPlayer)
  $icon = $playerID == $firstPlayerChooser ? "ready.png" : "notReady.png";
else if ($playerID == 1 && $gameStatus < $MGS_ReadyToStart)
  $icon = "notReady.png";
else if ($playerID == 2 && $gameStatus >= $MGS_ReadyToStart)
  $icon = "notReady.png";

$isMobile = IsMobile();
$parsedFormat = GetCurrentFormat();
$currentRoundGame = intval(GetCachePiece($gameName, 24));
$canSideboard = (
  (Formats::$PremierStrict != $parsedFormat && Formats::$PreviewStrict != $parsedFormat)
  || $currentRoundGame !== 1
) && $parsedFormat != Formats::$PremierQuick;
$canLeaveLobby = (Formats::$PremierStrict != $parsedFormat && Formats::$PreviewStrict != $parsedFormat) || $currentRoundGame === 1;

$arenaBotPremierStrictMessage = "By joining this lobby, you are agreeing to a Best of 3 Game. Any premature exit in the middle of a game will be considered unsportsmanlike behavior and could result in a ban. If you joined by mistake, please leave this lobby and join a casual lobby instead.";
if($currentRoundGame == 1 && $gameStatus == $MGS_ChooseFirstPlayer && ($parsedFormat == Formats::$PremierStrict || $parsedFormat == Formats::$PreviewStrict)) {
  $chatLog = file("./Games/" . $gameName . "/gamelog.txt");
  $found = false;
  foreach($chatLog as $line) {
    if(strpos($line, $arenaBotPremierStrictMessage) !== false) {
      $found = true;
      break;
    }
  }
  if(!$found) {
    WriteLog(ArenabotSpan() . $arenaBotPremierStrictMessage);
  }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="content-type" content="text/html; charset=utf-8" >
  <title>Game Lobby</title>
  <link id="icon" rel="shortcut icon" type="image/png" href="./Images/<?= $icon ?>"/>
  <link rel="stylesheet" href="./css/chat3.css">
  <link rel="stylesheet" href="./css/petranaki251019.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Barlow:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Teko:wght@700&display=swap" rel="stylesheet">
  <style>
    <?php include 'PlayerColors.php' ?>

    /* Fixed layout for the lobby panes */
    .lobby-wrapper {
      display: grid;
      grid-template-columns: minmax(0, 1.0fr) minmax(150px, 0.5fr) minmax(0, 1.5fr);
      gap: 10px;
      height: calc(100vh - 80px); /* Adjust height to fit viewport minus header/footer */
    }

    /* Make all container panes scrollable */
    .game-lobby, .player-info, .deck-info {
      display: flex;
      flex-direction: column;
      max-height: 100%;
      overflow: hidden;
    }

    /* Setup panel and chat panel in first column */
    .game-set-up {
      flex: 0 0 auto; /* Don't grow, don't shrink, auto height */
      margin-bottom: 10px;
      overflow-y: auto;
      max-height: 40vh;
    }

    .chat-log {
      flex: 1 1 auto; /* Grow, shrink, auto height */
      display: flex;
      flex-direction: column;
      overflow: hidden;
    }

    .gamelog {
      flex: 1;
      overflow-y: auto;
    }

    .chatbox {
      flex: 0 0 auto;
    }

    /* Ensure the player pane doesn't get too narrow but not too wide either */
    .player-info {
      min-width: 150px;
      max-width: 240px;
      width: 100%;
      overflow-y: auto;
    }

    /* Make deck info scrollable */
    .deck-info {
      overflow-y: auto;
    }

    /* Adjust the card display in the player pane */
    .player-info img {
      max-width: 100%;
      height: auto;
    }

    /* Ensure the deck display doesn't overlap */
    .deck-display {
      display: flex;
      flex-wrap: wrap;
      gap: 5px;
      justify-content: center;
      overflow-y: auto;
      max-height: calc(100vh - 220px);
    }

    /* Consistent card spacing */
    .deck-display > span {
      margin: 0;
      padding: 0 0 5px 0 !important;
      display: inline-block;
      box-sizing: border-box;
    }

    /* For mobile views, stack all panes vertically with full-page scrolling */
    @media (max-width: 1024px) {
      body {
        overflow-y: auto;
      }

      .lobby-wrapper {
        grid-template-columns: 1fr;
        height: auto;
        overflow-y: visible;
      }

      .game-lobby, .player-info, .deck-info {
        max-height: none;
        overflow: visible;
      }

      .game-set-up, .chat-log, .gamelog, .deck-display {
        max-height: none;
        overflow: visible;
      }

      .player-info {
        max-width: 100%;
      }
    }
  </style>
</head>

<body onload='GameLobbyOnLoad(<?php echo (filemtime(LogPath($gameName))); ?>)'>
  <div class="lobby-container">
    <div id="cardDetail" style="display:none; position:absolute;"></div>
    <!-- <div class="lobby-header">
      <h2 class="lobby-title bg-yellow">Game Lobby</h2>
      <p class="leave-lobby"><a href='MainMenu.php'>Leave Lobby</a></p>
    </div> -->
    <div class="lobby-wrapper">
      <div class="game-lobby">
        <div id='mainPanel' style='text-align:center;'>
          <div class='game-set-up container bg-yellow'>
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
              <h2 style="margin: 0;">Set Up</h2>
            </div>
            <div id="setup-content"></div>
            <div id='submitForm' style='width:100%; text-align: center; display: none;'>
              <form action='./SubmitSideboard.php'>
                <input type='hidden' id='gameName' name='gameName' value='<?= $gameName ?>'>
                <input type='hidden' id='playerID' name='playerID' value='<?= $playerID ?>'>
                <input type='hidden' id='playerCharacter' name='playerCharacter' value=''>
                <input type='hidden' id='playerDeck' name='playerDeck' value=''>
                <input type='hidden' id='authKey' name='authKey' value='<?= $authKey ?>'>
                <input class='GameLobby_Button' type='submit' value='<?= $playerID == 1 ? "Start" : "Ready" ?>'>
              </form>
            </div>
          </div>
        </div>
        <div class='chat-log container bg-yellow'>
          <h2>Chat</h2>
          <div id='gamelog' class="gamelog"></div>
          <?php if(!IsChatDisabledForAnyPlayer()): ?>
            <div id="chatbox" class="chatbox">
              <div class="lobby-chat-input">
                <input class="GameLobby_Input" type="text" id="chatText" name="chatText" value="" autocomplete="off" onkeypress="ChatKey(event)">
                <button class="GameLobby_Button" style="cursor:pointer;" onclick="SubmitChat()">Chat</button>
              </div>
            </div>
          <?php else: ?>
            <div id="chatbox" class="chatbox">
              <div class="lobby-chat-input">
                <div style="margin: 0 auto; color:white; font-weight: bold;">One or more players has disabled chat.</div>
              </div>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <div class="player-info container bg-yellow">
        <h2>Players</h2>
        <div id="my-info">
          <?php
          $contentCreator = ContentCreators::tryFrom(($playerID == 1 ? $p1ContentCreatorID : $p2ContentCreatorID));
          $nameColor = ($contentCreator != null ? $contentCreator->NameColor() : "");
          $displayName = "<span style='color:$nameColor'>$playerName</span>";
          $deckFile = "./Games/" . $gameName . "/p" . $playerID . "Deck.txt";

          // If the deck file doesn't exist, redirect to the main menu
          if (!file_exists($deckFile)) {
            // Retry 10 times before redirecting to the main menu
            $attempts = 10;
            while ($attempts > 0 && !file_exists($deckFile)) {
              $deckFile = "./Games/" . $gameName . "/p" . $playerID . "Deck.txt";
              $attempts--;
            }
            if (!file_exists($deckFile)) {
              echo "<script>alert('Could not find deck file'); window.location.href = '" . $redirectPath . "/MainMenu.php';</script>";
              exit;
            }
          }

          $handler = fopen($deckFile, "r");

          echo ("<h3>$displayName</h3>");
          if ($handler) {
            $material = GetArray($handler);
            $playerAspects = explode(",", CardAspects($material[1]));
            $base = $material[0];
            echo ("<input type='hidden' id='playerAspect' name='playerAspect' value='" . $playerAspects[0] . "'>");
            echo ("<div style='position:relative; display: inline-block;'>");
            $overlayURL = ($contentCreator != null ? $contentCreator->HeroOverlayURL($material[1]) : "");
            $isUnimplemented = IsUnimplemented($material[1]);
            echo (Card($material[1], "CardImages", ($isMobile ? 100 : 250), 0, 1, 0, 0, 0, "", "", true, isUnimplemented:$isUnimplemented));

            if ($overlayURL != "")
              echo ("<img title='Portrait' style='position:absolute; z-index:1001; top: 27px; left: 0px; cursor:pointer; height:" . ($isMobile ? 100 : 250) . "; width:100%;' src='" . $overlayURL . "' />");
            echo ("</div>");

            echo ("<div style='position:relative; display: inline-block;'>");
            $overlayURL = ($contentCreator != null ? $contentCreator->HeroOverlayURL($material[0]) : "");
            $isUnimplemented = IsUnimplemented($material[0]);
            echo (Card($material[0], "CardImages", ($isMobile ? 100 : 250), 0, 1, 0, 0, 0, "", "", true, isUnimplemented:$isUnimplemented));
            if ($overlayURL != "")
              echo ("<img title='Portrait' style='position:absolute; z-index:1001; top: 27px; left: 0px; cursor:pointer; height:" . ($isMobile ? 100 : 250) . "; width:" . ($isMobile ? 100 : 250) . ";' src='" . $overlayURL . "' />");
            echo ("</div>");

            $deck = GetArray($handler);
            $deckSB = GetArray($handler);

            fclose($handler);
          }
          ?>
        </div>

        <div id="their-info">
        </div>
      </div>

      <div class="deck-info container bg-yellow" style="padding-bottom: 0;">
        <div id="deckTab" class="deck-header">
          <?php if (isset($deck)): ?>
            <div style="display: flex; align-items: center; width: 100%; gap: 16px; margin-bottom: 20px;">
            <h2 class='deck-title' style="margin: 0;">Your Deck</h2>
            <h2 class='deck-count' style="flex-grow: 1; margin: 0;">(<span id='mbCount'><?= count($deck) ?></span>/<?= count($deck) + count($deckSB) ?>)</h2>
            <?php
            if ($canLeaveLobby) echo "<a href='MainMenu.php' class='leave-lobby'>
              <svg xmlns='http://www.w3.org/2000/svg' width='24px' height='24px' viewBox='0 0 24 24'>
              <path fill='currentColor' d='m17 7l-1.41 1.41L18.17 11H8v2h10.17l-2.58 2.58L17 17l5-5zM4 5h8V3H4c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h8v-2H4z' />
              </svg>
              Leave Lobby
            </a>";
            ?>
            </div>
          <?php endif; ?>
        </div>

        <h4>Click Cards to Select/Unselect</h4>
        <div class="deck-display">
          <?php
          if (isset($deck)) {
            $cardSize = 110;
            $count = 0;
            sort($deck);
            for ($i = 0; $i < count($deck); ++$i) {
              $id = "DECK-" . $count;
              $isUnimplemented = IsUnimplemented($deck[$i]);
              echo ("<span style='cursor:pointer; padding-bottom:5px; padding-left:3px;' onclick='CardClick(\"" . $id . "\")'>" . Card($deck[$i], "concat", $cardSize, 0, 1, 0, 0, 0, "", $id, isUnimplemented:$isUnimplemented) . "</span>");
              ++$count;
            }
            for ($i = 0; $i < count($deckSB); ++$i) {
              $id = "DECK-" . $count;
              $isUnimplemented = IsUnimplemented($deckSB[$i]);
              echo ("<span style='cursor:pointer; padding-bottom:5px; padding-left:3px;' onclick='CardClick(\"" . $id . "\")'>" . Card($deckSB[$i], "concat", $cardSize, 0, 1, 1, 0, 0, "", $id, isUnimplemented:$isUnimplemented) . "</span>");
              ++$count;
            }
          }
          ?>
        </div>
      </div>

    </div>
    <div class="lobby-footer">
      <?php include_once 'Disclaimer.php'; ?>
    </div>
  </div>
  <audio id="playerJoinedAudio">
    <source src="./Assets/playerJoinedSound.mp3" type="audio/mpeg">
  </audio>
  <script>
    function reload() {
      // This function is called by jsInclude250308.js but should be handled by CheckReloadNeeded
      // For GameLobby, we don't want to reload immediately, so we do nothing here
    }

    function UpdateFormInputs() {
      var playerCharacter = document.getElementById("playerCharacter");
      if (!!playerCharacter) playerCharacter.value = GetCharacterCards();
      var playerDeck = document.getElementById("playerDeck");
      if (!!playerDeck) playerDeck.value = GetDeckCards();
    }

    function GetCharacterCards() {
      var types = ["WEAPONS", "OFFHAND", "QUIVER", "HEAD", "CHEST", "ARMS", "LEGS"];
      var returnValue = "<?php echo (isset($material) ? implode(",", $material) : ""); ?>";
      return returnValue;
    }

    function GetDeckCards() {
      var count = 0;
      var returnValue = "";
      var overlay = document.getElementById("DECK-" + count + "-ovr");
      while (!!overlay) {
          if (overlay.style.visibility == "hidden") {
          var imageSrc = document.getElementById("DECK-" + count + "-img").src;
          if (returnValue != "") returnValue += ",";
          var splitArr = imageSrc.split("/");
          returnValue += splitArr[splitArr.length-1].split(".")[0];
        }
        ++count;
        var overlay = document.getElementById("DECK-" + count + "-ovr");
      }
      return returnValue;
    }

    function CheckReloadNeeded(lastUpdate) {
      var xmlhttp = new XMLHttpRequest();
      xmlhttp.onreadystatechange = function() {
        if (this.readyState == 4 && this.status == 200) {
          if (parseInt(this.responseText) != 0) {
            if (parseInt(this.responseText) == 1) location.reload();
            else {
              response = JSON.parse(this.responseText);
              document.getElementById("setup-content").innerHTML = response["setupContent"];
              document.getElementById("gamelog").innerHTML = response["logContent"];
              document.getElementById("their-info").innerHTML = response["theirInfo"];
              document.getElementById("submitForm").style.display = response["showSubmit"] ? "block" : "none";
              if (response["playerJoinAudio"] === true && !audioPlayed) {
                var audio = document.getElementById('playerJoinedAudio');
                audio.play();
                audioPlayed = true;
              } else if (response["playerJoinAudio"] === false && audioPlayed) {
                //reset audio if player left
                audioPlayed = false;
              }
              // document.getElementById("icon").href = "./Images/" + document.getElementById("iconHolder").innerText;
              var log = document.getElementById('gamelog');
              if (log !== null) log.scrollTop = log.scrollHeight;
              CheckReloadNeeded(parseInt(response["timestamp"]));
            }
          }
        }
      };
      xmlhttp.open("GET", "GetLobbyRefresh.php?gameName=<?php echo ($gameName); ?>&playerID=<?php echo ($playerID); ?>&lastUpdate=" + lastUpdate + "&authKey=<?php echo ($authKey); ?>", true);
      xmlhttp.send();
    }

    var audioPlayed = false;

    function SubmitFirstPlayer(action) {
       if (action == 1) action = "Go First";
      else action = "Go Second";
      var xmlhttp = new XMLHttpRequest();
      xmlhttp.onreadystatechange = function() {
        if (this.readyState == 4 && this.status == 200) {}
      }
      var ajaxLink = "ChooseFirstPlayer.php?gameName=" + <?php echo ($gameName); ?>;
      ajaxLink += "&playerID=" + <?php echo ($playerID); ?>;
      ajaxLink += "&action=" + action;
      ajaxLink += <?php echo ("\"&authKey=" . $authKey . "\""); ?>;
        xmlhttp.open("GET", ajaxLink, true);
      xmlhttp.send();
    }

    function RandomizePlayer() {
      var num = Math.floor(Math.random() * 2) + 1;
      SubmitFirstPlayer(num);
    }

    function CardClick(id) {
      if(<?php echo $canSideboard ? "'true'" : "'false'"?> === 'false') {
        alert('In this format, you cannot sideboard at this time\nIf you wish to sideboard Game 1, leave this lobby and change the format to Premier Casual');
        return;
      }
      var idArr = id.split("-");
      if (idArr[0] == "DECK") {
        var overlay = document.getElementById(id + "-ovr");
        overlay.style.visibility = (overlay.style.visibility == "hidden" ? "visible" : "hidden");
        var mbCount = document.getElementById("mbCount");
        mbCount.innerText = parseInt(mbCount.innerText) + (overlay.style.visibility == "hidden" ? 1 : -1);
      }
      UpdateFormInputs();
    }

    function GameLobbyOnLoad(lastUpdate) {
      <?php
      if ($playerID == "1" && $gameStatus == $MGS_ChooseFirstPlayer) {
        echo ("var audio = document.getElementById('playerJoinedAudio');");
        echo ("audio.play();");
      }
      ?>
      UpdateFormInputs();
      var log = document.getElementById('gamelog');
      if (log !== null) log.scrollTop = log.scrollHeight;
      CheckReloadNeeded(0);
    }

    function copyText() {
      var gameLink = document.getElementById("gameLink");
      gameLink.select();
      gameLink.setSelectionRange(0, 99999);

      // Copy it to clipboard
      document.execCommand("copy");
    }
  </script>
  <script src="./jsInclude250308.js"></script>
  <script>
    // function SwapOutForceBase(base) {
    //   var xmlhttp = new XMLHttpRequest();
    //   xmlhttp.onreadystatechange = function() {
    //     if (this.readyState == 4 && this.status == 200) {
    //       var response = JSON.parse(this.responseText);
    //       if (response.success) {
    //         alert("Force Base swapped out successfully!");
    //         location.reload();
    //       } else {
    //         alert("Failed to swap out Force Base");
    //       }
    //     }
    //   };
    //   xmlhttp.open("GET", "SwapOutForceBase.php?gameName=<?php echo ($gameName); ?>&playerID=<?php echo ($playerID); ?>&authKey=<?php echo ($authKey); ?>&base=" + base, true);
    //   xmlhttp.send();
    // }
  </script>
</body>
</html>