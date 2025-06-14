<head>

    <style>
      body {
        background-color: #111111;
      }
      @keyframes move {
        from {margin-top: 0;}
        to {margin-top: -50px;}
      }

      @keyframes shuffle {
        0% {
          transform: translateX(0) rotate(0deg);
          z-index: 1;
        }
        30% {
          transform: translateX(35px) rotate(13deg);
          z-index: 10;
        }
        60% {
          transform: translateX(-35px) rotate(-13deg);
        }
        100% {
          transform: translateX(0) rotate(0deg);
          z-index: 20;
        }
      }

      .reveal-card {
        position: relative;
        transform-style: preserve-3d;
        transition: transform 0.5s ease;
      }

      .reveal-card.is-flipped {
        transform: rotateY(-180deg) translateX(200px);
      }

      .reveal-card-front {
        transform: rotateY(180deg);
      }

      .draggable {
      }

      .droppable {
          border: 3px dashed #ffff00 !important;
      }

    </style>

    <?php

    include 'Libraries/HTTPLibraries.php';

    //We should always have a player ID as a URL parameter
    $gameName = TryGet("gameName", "");
    if (!IsGameNameValid($gameName)) {
      echo ("Invalid game name.");
      exit;
    }
    $playerID = TryGet("playerID", 3);
    if (!is_numeric($playerID)) {
      echo ("Invalid player ID.");
      exit;
    }
    ?>

    <style>
      <?php include 'PlayerColors.php' ?>
    </style>

    <?php
    if (!file_exists("./Games/" . $gameName . "/")) {
      echo ("<p style='color:white'>Game does not exist</p>");
      exit;
    }

    session_start();
    if ($playerID == 1 && isset($_SESSION["p1AuthKey"])) $authKey = $_SESSION["p1AuthKey"];
    else if ($playerID == 2 && isset($_SESSION["p2AuthKey"])) $authKey = $_SESSION["p2AuthKey"];
    else $authKey = TryGet("authKey", "");
    session_write_close();

    if(($playerID == 1 || $playerID == 2) && $authKey == "")
    {
      if(isset($_COOKIE["lastAuthKey"])) $authKey = $_COOKIE["lastAuthKey"];
    }

    if ($playerID == 3 && !isset($_SESSION['userid'])) {
      header('Location: ./MainMenu.php');
      die();
    }

    if (isset($_COOKIE['selectedLanguage'])) {
      $selectedLanguage = $_COOKIE['selectedLanguage'];
    }else {
      $selectedLanguage = 'EN';
    }
    //First we need to parse the game state from the file
    include "Libraries/SHMOPLibraries.php";
    include "ParseGamestate.php";
    include "GameTerms.php";
    include "GameLogic.php";
    include "HostFiles/Redirector.php";
    include "Libraries/UILibraries2.php";
    include "Libraries/StatFunctions.php";
    include "Libraries/PlayerSettings.php";
    include "MenuFiles/ParseGamefile.php";
    include_once "WriteLog.php";
    include_once 'includes/functions.inc.php';
    include_once 'includes/dbh.inc.php';

    if ($currentPlayer == $playerID) {
      $icon = "ready.png";
      $readyText = "You are the player with priority.";
    } else {
      $icon = "notReady.png";
      $readyText = "The other player has priority.";
    }
    echo '<link id="icon" rel="shortcut icon" type="image/png" href="./Images/' . $icon . '"/>';

    $darkMode = IsDarkMode($playerID);

    if ($darkMode) $backgroundColor = "rgba(20,20,20,0.70)";
    else $backgroundColor = "rgba(255,255,255,0.70)";

    $borderColor = ($darkMode ? "#DDD" : "#1a1a1a");
    $myChar = &GetPlayerCharacter($playerID);
    $myBase = $myChar[0];
    $theirChar = &GetPlayerCharacter($otherPlayerID);
    $theirBase = $theirChar[0];
    ?>

    <head>
      <meta charset="utf-8">
      <title>Petranaki</title>
      <link rel="stylesheet" href="./css/chat3.css">
      <link rel="stylesheet" href="./css/gamestyle250403.css">
      <link rel="preconnect" href="https://fonts.googleapis.com">
      <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
      <link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100;0,300;0,400;0,500;0,700;0,900;1,100;1,300;1,400;1,500;1,700;1,900&display=swap" rel="stylesheet">
      <link href="https://fonts.googleapis.com/css2?family=Barlow:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&family=Gemunu+Libre:wght@200..800&display=swap" rel="stylesheet">
    </head>

    <script>
      var IsDynamicScalingEnabled = <?php echo (IsDynamicScalingEnabled($playerID) ? "true" : "false"); ?>;
      var cardSize = IsDynamicScalingEnabled == 1 ? window.innerWidth / 13 : 96;
      //Note: 96 = Card Size

      function Hotkeys(event) {
        switch (event.keyCode) {
          case 32: // Space = pass
            if(document.getElementById("passConfirm").innerText == "false" || confirm("Do you want to skip arsenal?"))
              SubmitInput(99, "");
            break;
          case 85: // U = undo
          case 117: // u
            SubmitInput(10000, "");
            break;
          case 77: // M = open "M"enu
          case 109: // m
            TogglePopup("menuPopup");
            break;
          case 87: // W = player "W"indow
          case 119: // w
            SwitchPlayerWindow();
            break;
          case 66: // B = activate "B"ase
          case 98: // b
            ActivateBase();
            break;
          case 76: // L = activate "L"eader
          case 108: // l
            ActivateLeader();
            break;
          case 73: // I = Claim "I"nitiative
          case 105: // i
            ClaimInitiative();
            break;
          case 82: // R = toggle my "R"esources
          case 114: // r
            ToggleMyResources();
            break;
          case 68: // D = toggle my "D"iscard
          case 100: // D
            ToggleMyDiscard();
            break;
          case 69: // E = toggle "E"nemy Discard
          case 101: // e
            ToggleTheirDiscard();
            break;
          default: break;
        }
        //1-9 keys
        if (event.keyCode >= 49 && event.keyCode <= 57) ProcessNumberKey(event.keyCode - 48);
      }

      function ProcessInputLink(player, mode, input, event = 'onmousedown', fullRefresh = false) {
        return " " + event + "='SubmitInput(\"" + mode + "\", \"&buttonInput=" + input + "\", " + fullRefresh + ");'";
      }
      //Rotate is deprecated
      function Card(cardNumber, folder, maxHeight, action = 0, showHover = 0, overlay = 0, borderColor = 0, counters = 0, actionDataOverride = "", id = "", rotate = 0, lifeCounters = 0, forceTokens = "-", atkCounters = 0, controller = 0, restriction = "", isBroken = 0, onChain = 0, isFrozen = 0, gem = 0, landscape = 0, epicActionUsed = 0, isUnimplemented = 0, showCounterControls = 0, counterType = 0, counterLimitReached = 0) {
        if (folder == "crops") {
          cardNumber += "_cropped";
        }
        fileExt = ".png";
        folderPath = folder;
        var selectedLanguage = "<?php echo $selectedLanguage; ?>";
        fileExt = ".webp";
        if (selectedLanguage != "EN" && folder == "concat") {
          folder = + "concat/" + selectedLanguage;
        }else if(selectedLanguage != "EN" && folder == "WebpImages2"){
          folder = "WebpImages2/" + selectedLanguage;
        }
        var actionData = actionDataOverride != "" ? actionDataOverride : cardNumber;
        //Enforce 375x523 aspect ratio as exported (.71)
        margin = "margin:0px;";
        border = "";
        if (borderColor != -1) margin = borderColor > 0 ? "margin:0px;" : "margin:1px;";
        if (folder == "crops/") margin = "0px;";

        var rv = "<a style='" + margin + " position:relative; display:inline-block;" + (action > 0 ? "cursor:pointer;" : "") + "'" + (showHover > 0 ? " onmouseover='ShowCardDetail(event, this)' onmouseout='HideCardDetail()'" : "") + (action > 0 ? " onclick='SubmitInput(\"" + action + "\", \"&cardID=" + actionData + "\");'" : "") + ">";

        // Counters (damage and heal)
        if (showCounterControls != 0) {
          var canIncrease = counterLimitReached != 1;
          var canDecrease = counters > 0;
          rv += "<div class='counters-control-wrapper base-controls'>";
          rv += "<button class='counter-control increase-control' " + (canIncrease ? "" : "disabled") + " onclick='SubmitIncreaseCounters(this, \"" + actionData + "\");' " + (showHover > 0 ? " onmouseenter='OnDamageControlMouseEnter()' onmouseleave='OnDamageControlMouseLeave()'" : "") + ">+</button>";
          rv += "<button class='counter-control decrease-control' " + (canDecrease ? "" : "disabled") + " onclick='SubmitDecreaseCounters(this, \"" + actionData + "\");' " + (showHover > 0 ? " onmouseenter='OnDamageControlMouseEnter()' onmouseleave='OnDamageControlMouseLeave()'" : "") + ">-</button>";
          rv += "</div>";
        }
        if (counterType > 0) {
          rv += "<div class='base-counter " + (showCounterControls > 0 ? "" : "no-controls") + (counterType == 1 ? " dmg-counter" : " heal-counter") + "'>" + counters + "</div>";
        }

        if (borderColor > 0) {
          border = "border-radius:8px; border:2px solid " + BorderColorMap(borderColor) + ";";
        } else if (folder == "concat") {
          border = "border-radius:8px; border:1px solid transparent;";
        } else {
          border = "border: 1px solid transparent;";
        }
        var orientation = landscape == 1 ? "data-orientation='landscape'" : "";
        if(rotate == 1 || landscape == 1) {
          height = (maxHeight);
          width = (maxHeight * 1.29);
        }
        else if (folder.startsWith("crop")) {
          height = maxHeight;
          width = (height * 1.29);
        } else if (folder.startsWith("concat")) {
          height = maxHeight;
          width = maxHeight;
        } else {
          height = maxHeight;
          width = (maxHeight * .71);
        }

        <?php
        if (IsPatron(1)) echo ("if(controller == 1 && CardHasAltArt(cardNumber)) folderPath = 'PatreonImages/' + folderPath;");
        if (IsPatron(2)) echo ("if(controller == 2 && CardHasAltArt(cardNumber)) folderPath = 'PatreonImages/' + folderPath;");
        ?>

        var altText = " alt='" + CardTitle(cardNumber) + "' ";
        rv += "<img " + (id != "" ? "id='" + id + "-img' " : "") + altText + orientation + "style='" + border + " height:" + height + "; width:" + width + "px; position:relative;' src='./" + folderPath + "/" + cardNumber + fileExt + "' />";

        if (isUnimplemented != 0) {
          rv += "<img style='position:absolute; top:50%; left:50%; transform:translate(-50%, -50%); width:40%; height:40%; z-index:2;' src='./Images/restricted.png' />";
        }

        rv += "<div " + orientation + " " + (id != "" ? "id='" + id + "-ovr' " : "") + "style='visibility:" + (overlay == 1 ? "visible" : "hidden") + "; width:calc(100% - 4px); height:calc(100% - 4px); top:2px; left:2px; border-radius:10px; position:absolute; background: rgba(0, 0, 0, 0.5); z-index: 1;'></div>";

        var darkMode = false;
        counterHeight = 28;
        imgCounterHeight = 42;
        // //Attacker Label Style
        // if (counters == "Attacker" || counters == "Arsenal") {
        //   rv += "<div style='margin: 0px; top: 80%; left: 50%; margin-right: -50%; border-radius: 7px; width: fit-content; text-align: center; line-height: 16px; height: 16px; padding: 5px; border: 3px solid " + PopupBorderColor(darkMode) + ";";
        //   rv += "transform: translate(-50%, -50%); -ms-transform: translate(-50%, -50%); position:absolute; z-index: 10; background:" + BackgroundColor(darkMode) + "; font-size:20px; font-weight:800; color:" + PopupBorderColor(darkMode) + ";'>" + counters + "</div>";
        // }
        // //Equipments, Hero and default counters style
        // else if (counters != 0) {
        //   var left = "72%";
        //   if (lifeCounters == 0 && defCounters == 0 && atkCounters == 0) {
        //     left = "50%";
        //   }
        //   rv += "<div style='margin: 0px; top: 50%; left:" + left + "; margin-right: -50%; border-radius: 50%; width:" + counterHeight + "px; height:" + counterHeight + "px; padding: 5px; border: 3px solid " + PopupBorderColor(darkMode) + "; text-align: center; line-height:" + imgCounterHeight / 1.5 + "px;";
        //   rv += "transform: translate(-50%, -50%); -ms-transform: translate(-50%, -50%); position:absolute; z-index: 10; background:" + BackgroundColor(darkMode) + "; font-family: Helvetica; font-size:" + (counterHeight - 2) + "px; font-weight:550; color:" + TextCounterColor(darkMode) + "; text-shadow: 2px 0 0 " + PopupBorderColor(darkMode) + ", 0 -2px 0 " + PopupBorderColor(darkMode) + ", 0 2px 0 " + PopupBorderColor(darkMode) + ", -2px 0 0 " + PopupBorderColor(darkMode) + ";'>" + counters + "</div>";
        // }
        //-1 Defense & Endurance Counters style
        // if (defCounters != 0 && isBroken != 1) {
        //   var left = "-42%";
        //   if (lifeCounters == 0 && counters == 0) {
        //     left = "0px";
        //   }
        //   rv += "<div style=' position:absolute; margin: auto; top: 0; left:" + left + "; right: 0; bottom: 0;width:" + imgCounterHeight + "px; height:" + imgCounterHeight + "px; display: flex;justify-content: center; z-index: 5; text-align: center; vertical-align: middle; line-height:" + imgCounterHeight + "px;";
        //   rv += "font-size:" + (imgCounterHeight - 17) + "px; font-weight: 600;  color: #EEE; text-shadow: 2px 0 0 #000, 0 -2px 0 #000, 0 2px 0 #000, -2px 0 0 #000;'>" + defCounters + "<img style='position:absolute; top: -2px; width:" + imgCounterHeight + "px; height:" + imgCounterHeight + "px; opacity: 0.9; z-index:-1;' src='./Images/Defense.png'></div>";
        // }

        // //Health Counters style
        // if (lifeCounters != 0) {
        //   var left = "45%";
        //   if (defCounters == 0 && atkCounters == 0) {
        //     left = "0px";
        //   }
        //   rv += "<div style=' position:absolute; margin: auto; top: 0; left:" + left + "; right: 0; bottom: 0;width:" + imgCounterHeight + "px; height:" + imgCounterHeight + "px; display: flex; justify-content: center; z-index: 5; text-align: center; vertical-align: middle; line-height:" + imgCounterHeight + "px;";
        //   rv += "font-size:" + (imgCounterHeight - 17) + "+px; font-weight: 600;  color: #EEE; text-shadow: 2px 0 0 #000, 0 -2px 0 #000, 0 2px 0 #000, -2px 0 0 #000;'>" + lifeCounters + "<img style='position:absolute; top: -2px; width:" + imgCounterHeight + "px; height:" + imgCounterHeight + "px; opacity: 0.9; z-index:-1;' src='./Images/Life.png'></div>";
        // }

        // //Attack Counters style
        // if (atkCounters != 0) {
        //   var left = "-45%";
        //   if (lifeCounters == 0 && counters == 0) {
        //     left = "0px";
        //   }
        //   rv += "<div style=' position:absolute; margin: auto; top: 0; left:" + left + "; right: 0; bottom: 0;width:" + imgCounterHeight + "px; height:" + imgCounterHeight + "px; display: flex; justify-content: center; z-index: 5; text-align: center; vertical-align: middle; line-height:" + imgCounterHeight + "px;";
        //   rv += "font-size:" + (imgCounterHeight - 17) + "px; font-weight: 600;  color: #EEE; text-shadow: 2px 0 0 #000, 0 -2px 0 #000, 0 2px 0 #000, -2px 0 0 #000;'>" + atkCounters + "<img style='position:absolute; top: -2px; width:" + imgCounterHeight + "px; height:" + imgCounterHeight + "px; opacity: 0.9; z-index:-1;' src='./Images/AttackIcon.png'></div>";
        // }

        if (restriction != "") {
          //$restrictionName = CardName($restriction);
          rv += "<img title='Restricted by: " + restriction + "' style='position:absolute; z-index:100; top:26px; left:26px;' src='./Images/restricted.png' />";
        }
        if (epicActionUsed == 1) rv += "<img title='Epic Action Used' style='position:absolute; z-index:100; border-radius:5px; top: -3px; right: -2px; height:26px; width:26px; filter:drop-shadow(1px 1px 1px rgba(0, 0, 0, 0.50));' src='./Images/ExhaustToken.png' />";
        var isMyBase = cardNumber == "<?php echo $myBase ?>" && controller == <?php echo $playerID ?>;
        var isTheirBase = cardNumber == "<?php echo $theirBase ?>" && controller == <?php echo $otherPlayerID ?>;
        var forceTokensArr = forceTokens.split(",");
        var myForceToken = forceTokensArr[0];
        var theirForceToken = forceTokensArr[1];
        if(isMyBase) {
          var showToken = myForceToken == "1";
          rv += `<img id='P<?=$playerID?>FORCETOKEN' title='Force Token' style='display: ${showToken ? 'block':'none'}; position:absolute; z-index:100; border-radius:5px; bottom: 3px; right: -2px; height:32px; width:32px; filter:drop-shadow(1px 1px 1px rgba(0, 0, 0, 0.50));' src='./Images/ForceToken.png' />`;
        }
        if(isTheirBase) {
          var showToken = theirForceToken == "1";
          rv += `<img id='P<?=$otherPlayerID?>FORCETOKEN' title='Force Token' style='display: ${showToken ? 'block':'none'}; position:absolute; z-index:100; border-radius:5px; bottom: 3px; right: -2px; height:32px; width:32px; filter:drop-shadow(1px 1px 1px rgba(0, 0, 0, 0.50));' src='./Images/ForceToken.png' />`;
        }
        rv += "</a>";

        // if (gem != 0) {
        //   var playerID = <?php echo ($playerID)  ; ?>;
        //    //Note: 96 = Card Size
        //   var cardWidth = 96;
        //   gemImg = (gem == 1 ? "hexagonRedGem.png" : "hexagonGrayGem.png");
        //   if (gem == 1) rv += "<img " + ProcessInputLink(playerID, 102, actionDataOverride) + " title='Effect Active' style='position:absolute; z-index:1001; bottom:3px; left:" + (cardWidth / 2 - 18) + "px; width:40px; height:40px; cursor:pointer;' src='./Images/" + gemImg + "' />";
        //   else if (gem == 2) rv += "<img " + ProcessInputLink(playerID, 102, actionDataOverride) + " title='Effect Inactive' style='position:absolute; z-index:1001; bottom:3px; left:" + (cardWidth / 2 - 18) + "px; width:40px; height:40px; cursor:pointer;' src='./Images/" + gemImg + "' />";
        // }
        return rv;
      }

      function BackgroundColor(darkMode) {
        if (darkMode) return "rgba(74, 74, 74, 0.9)";
        else return "rgba(235, 235, 235, 0.9)";
      }

      function PopupBorderColor(darkMode) {
        if (darkMode) return "#DDD";
        else return "#1a1a1a";
      }

      function TextCounterColor(darkMode) {
        if (darkMode) return "#1a1a1a";
        else return "#EDEDED";
      }

      // Function to handle drag start event
      function dragStart(e) {
          // Set the drag's data and styling
          var id = e.target.id;
          var element = e.target;
          var tries = 0;
          while(id == "" && tries < 20) {
            element = element.parentNode;
            id = element.id;
            ++tries;
          }
          e.dataTransfer.setData("text/plain", id);
          e.target.style.opacity = "0.4";
          HideCardDetail();
          //Now show the droppable areas
          var dropArea = document.getElementById("groundArena");
          dropArea.classList.add("droppable");
      }

      // Function to handle drag end event
      function dragEnd(e) {
          // Reset the element's opacity after drag
          e.target.style.opacity = "1";
          var dropArea = document.getElementById("groundArena");
          dropArea.classList.remove("droppable");
      }

      // Function to handle drag over event
      function dragOver(e) {
          e.preventDefault(); // Allow drop
      }

      // Function to handle drop event
      function drop(e) {
          e.preventDefault(); // Prevent default action (open as link for some elements)
          var destination = e.target.id;

          // Get the card being dragged
          var draggedCard = e.dataTransfer.getData("text/plain");

          // Send the action input to the server
          SubmitInput("10014", "&cardID=" + draggedCard + "!" + destination);

          //var draggedElement = document.getElementById(draggedCard);
      }

      function CardHasAltArt(cardID) {
        switch (cardID) {
          default:
            return false;
        }
      }

      function TranslationExist(Language, cardID)
      {
        switch (Language) {
          case "JP": //Japanese
            switch (cardID) {
              default:
                return false;
            }
            break;
          default:
            return false;
        }
      }

      function BorderColorMap(code) {
        code = parseInt(code);
        switch (code) {
          case 1:
            return "DeepSkyBlue";
          case 2:
            return "red";
          case 3:
            return "yellow";
          case 4:
            return "Gray";
          case 5:
            return "Tan";
          case 6:
            return "#00FF66";
          case 7:
            return "Orchid";
          default:
            return "Black";
        }
      }

      //Note: 96 = Card Size
      function PopulateZone(zone, size = 96, folder = "concat") {
          var selectedLanguage = "<?php echo $selectedLanguage; ?>";
          if(selectedLanguage != "EN" && folder == "concat")folder = "concat/" + selectedLanguage;
          var zoneEl = document.getElementById(zone);
          var zoneData = zoneEl.innerHTML;
          if (zoneData == "") return;
          var zoneArr = zoneData.split("|");
          var newHTML = "";
          for (var i = 0; i < zoneArr.length; ++i) {
              cardArr = zoneArr[i].split(" ");
              var id = "-";
              var positionStyle = "relative";
              var type = cardArr[10];
              var substype = cardArr[11];
              var className = "";
              if (type != "") {
                  folder = "WebpImages2";
                  if(selectedLanguage != "EN")folder = "WebpImages2" + "/" + selectedLanguage;
                  if (zone == "myChar") {
                      positionStyle = "fixed;";
                      id = type == "W" ? "P<?= $playerID ?>BASE" : "P<?= $playerID ?>LEADER";
                      className = type == "W" ? "my-base" : "my-leader";
                  } else if (zone == "theirChar") {
                      positionStyle = "fixed;";
                      id = type == "W" ? "P<?= $otherPlayerID ?>BASE" : "P<?= $otherPlayerID ?>LEADER";
                      className = type == "W" ? "their-base" : "their-leader";
                  }
              }
              if (zone == "myHand") {
                  id = "MYHAND-" + (i * <?php echo(HandPieces()); ?>);
              } else if (zone == "theirHand") {
                  id = "THEIRHAND-" + (i * <?php echo(HandPieces()); ?>);
              }
              var styles = " style='position:" + positionStyle + "; margin:1px;'";
              var droppable = " class='draggable " + className + "' draggable='true' ondragstart='dragStart(event)' ondragend='dragEnd(event)'";
              if (id != "-") newHTML += "<span id='" + id + "' " + styles + droppable + ">";
              else newHTML += "<span " + styles + droppable + ">";
              if (type == "C") {
                  folder = "WebpImages2";
                  if(selectedLanguage != "EN")folder = "WebpImages2" + "/" + selectedLanguage;
                  <?php
                  echo ("var p1uid = '" . ($p1uid == "-" ? "Player 1" : $p1uid) . "';");
                  echo ("var p2uid = '" . ($p2uid == "-" ? "Player 2" : $p2uid) . "';");
                  ?>

                  // User Tags
                  if (zone == "myChar") {
                      var fontColor = "#DDD";
                      var borderColor = "#1a1a1a";
                      var backgroundColor = "#DDD";

                      <?php $playerVars = $playerID == 1 ? ["p1-label", "p1uid"] : ["p2-label", "p2uid"] ?>
                      <?php if($playerID != 3): ?> // Hide player names for spectators
                        newHTML += "<div class='player-name <?= $playerVars[0] ?>'>" + <?php echo $playerVars[1]; ?> + "</div>";
                      <?php else: ?>
                        newHTML += "<div class='player-name p2-label'>Player 2</div>";
                      <?php endif; ?>
                  } else if (zone == "theirChar") {
                      var fontColor = "#DDD";
                      var borderColor = "#1a1a1a";
                      var backgroundColor = "#DDD";

                      <?php $playerVars = $playerID == 1 ? ["p2-label", "p2uid"] : ["p1-label", "p1uid"] ?>
                      <?php if($playerID != 3): ?> // Hide player names for spectators
                        newHTML += "<div class='player-name <?= $playerVars[0] ?>'>" + <?php echo $playerVars[1]; ?> + "</div>";
                      <?php else: ?>
                        newHTML += "<div class='player-name p1-label'>Player 1</div>";
                      <?php endif; ?>
                  }
              }
              var restriction = cardArr[12];
              if (typeof restriction != "string") restriction = "";
              restriction = restriction.replace(/_/g, ' ');
              folder = zone == "myChar" || zone == "theirChar" ? "WebpImages2" : "concat";
              if(selectedLanguage != "EN") folder = folder + "/" + selectedLanguage;
              newHTML += Card(cardArr[0], folder, size, cardArr[1], 1, cardArr[2], cardArr[3], cardArr[4], cardArr[5], "", cardArr[17], cardArr[6], cardArr[7], cardArr[8], cardArr[9], restriction, cardArr[13], cardArr[14], cardArr[15], cardArr[16], cardArr[18], cardArr[19], cardArr[20], cardArr[21], cardArr[22], cardArr[23]);
              newHTML += "</span>";
          }
          zoneEl.innerHTML = newHTML;
          zoneEl.style.display = "inline";
      }

      function GetCharacterLeft(cardType, cardSubType) {
        switch (cardType) {
          case "C": case "W":
            return "0000";
          default:
            break;
        }
        switch (cardSubType) {
          case "Head":
            return "95px";
          case "Chest":
            return "95px";
          case "Arms":
            return (cardSize + 105) + "px";
          case "Legs":
            return "95px";
          case "Off-Hand": case "Quiver":
            return "calc(50% + " + (cardSize / 2 + 10) + "px)";
        }
      }

      function GetCharacterBottom(cardType, cardSubType) {
        switch (cardType) {
          case "C":
            return "0000";
          case "W":
            return "0000";
          default:
            break;
        }
        switch (cardSubType) {
          case "Head":
            return (cardSize * 2 + 25) + "px";
          case "Chest":
            return (cardSize + 15) + "px";
          case "Arms":
            return (cardSize + 15) + "px";
          case "Legs":
            return "5px";
          case "Off-Hand": case "Quiver":
            return (cardSize * 2 + 25) + "px";
        }
      }

      function GetCharacterTop(cardType, cardSubType) {
        switch (cardType) {
          case "C":
            return "0000";
          case "W":
            return "0000";
          default:
            break;
        }
        switch (cardSubType) {
          case "Head":
            return "5px";
          case "Chest":
            return (cardSize + 15) + "px";
          case "Arms":
            return (cardSize + 15) + "px";
          case "Legs":
            return (cardSize * 2 + 25) + "px";
          case "Off-Hand": case "Quiver":
            return (cardSize * 2 + 25) + "px";
        }
      }

      function copyText() {
        var gameLink = document.getElementById("gameLink");
        gameLink.select();
        gameLink.setSelectionRange(0, 99999);

        // Copy it to clipboard
        document.execCommand("copy");
      }

      var _maxRetryCount = 5;
      var _currentRetryCount = 0;
      var _baseRetryDelay = 1000; // 1 second initial delay
      var _maxRetryDelay = 30000; // Maximum 30 second delay
      var _connectionHealthCheck = null;
      var _lastSuccessfulPoll = Date.now();
      var _requestTimeout = 60000; // 1 minute timeout

      function handlePollingError(errorMessage, forceRetry = false) {
        console.log("Polling error: " + errorMessage);

        // Implement exponential backoff for retries
        if (_currentRetryCount < _maxRetryCount || forceRetry) {
          _currentRetryCount++;

          // Calculate delay with exponential backoff
          var retryDelay = Math.min(_baseRetryDelay * Math.pow(2, _currentRetryCount - 1), _maxRetryDelay);

          // Add jitter to prevent all clients from retrying simultaneously
          retryDelay = Math.floor(retryDelay * (0.8 + Math.random() * 0.4));

          console.log("Retrying in " + retryDelay + "ms (Attempt " + _currentRetryCount + "/" + _maxRetryCount + ")");

          // Retry after delay
          setTimeout(function() {
            var lcpEl = document.getElementById("lastCurrentPlayer");
            var lastUpdate = lcpEl ? lcpEl.innerHTML : "0";
            CheckReloadNeeded(lastUpdate);
          }, retryDelay);
        } else if (!forceRetry) {
          // If we've reached max retries, show a notification to the user
          // and set up a recovery mechanism
          var recoverMessage = document.createElement("div");
          recoverMessage.style.position = "fixed";
          recoverMessage.style.top = "10px";
          recoverMessage.style.left = "50%";
          recoverMessage.style.transform = "translateX(-50%)";
          recoverMessage.style.padding = "10px";
          recoverMessage.style.backgroundColor = "rgba(255, 0, 0, 0.7)";
          recoverMessage.style.color = "white";
          recoverMessage.style.borderRadius = "5px";
          recoverMessage.style.zIndex = "10000";
          recoverMessage.style.cursor = "pointer";
          recoverMessage.innerHTML = "Connection lost. Click here to reconnect.";
          recoverMessage.onclick = function() {
            this.remove();
            _currentRetryCount = 0;
            reload();
          };
          document.body.appendChild(recoverMessage);

          // Still try to recover automatically after a longer delay
          setTimeout(function() {
            if (document.body.contains(recoverMessage)) {
              recoverMessage.remove();
            }
            _currentRetryCount = 0;
            reload();
          }, _maxRetryDelay * 2);
        }
      }

      function ShowBlockOpponentForm() {
        var form = document.getElementById('blockOppForm');
        if (form.style.display === 'none') {
            form.style.display = 'block';
            document.getElementById('blockOppButton').textContent = 'Hide';
        } else {
            form.style.display = 'none';
            document.getElementById('blockOppButton').textContent = 'Block Opponent';
        }
      }
      //hotkeys
      function SwitchPlayerWindow() {//W
        const isOnePlayerMode = <?php echo IsOnePlayerMode() ? 'true' : 'false'; ?> == true;
        if(!isOnePlayerMode) return;
        const redirectUrl = "<?php echo "$redirectPath/NextTurn4.php?gameName=$gameName&playerID=$otherPlayerID";?>";
        if(isOnePlayerMode) {
          window.location.href = redirectUrl;
        }
      }
      function ActivateBase() {//B
        const base = document.getElementById("P<?= $playerID ?>BASE");
        const link = base?.querySelector("a");
        if (link) link.click();
      }
      function ActivateLeader() {//L
        const leader = document.getElementById("P<?= $playerID ?>LEADER");
        const link = leader?.querySelector("a");
        if (link) link.click();
      }
      function ClaimInitiative() {//I
        const claimButton = document.querySelector("button.claimButton");
        if (claimButton) {
          claimButton.click();
        }
      }
      function ToggleMyResources() {//R
        const myResources = document.querySelector("div.my-resources div.resources");
        if (myResources) {
          myResources.click();
        }
      }
      function ToggleMyDiscard() {//D
        const myDiscard = document.querySelector('div.my-discard a img');
        if (myDiscard) {
          myDiscard.click();
        }
      }
      function ToggleTheirDiscard() {//Shift+D
        const theirDiscard = document.querySelector('div.their-discard a img');
        if (theirDiscard) {
          theirDiscard.click();
        }
      }
      function ProcessNumberKey(index) {
        index = parseInt(index);
        if (isNaN(index) || index < 1 || index > 9) return;
        //check for YESNO div
        const yesNoPopup = document.getElementById("YESNO");
        if (yesNoPopup && index == 1) {
          const yesButton = yesNoPopup.querySelector("div button:nth-of-type(1)");
          if (yesButton) {
            yesButton.click();
            return;
          }
        } else if (yesNoPopup && index == 2) {
          const noButton = yesNoPopup.querySelector("div button:nth-of-type(2)");
          if (noButton) {
            noButton.click();
            return;
          }
        } else if (yesNoPopup) {
          return;
        }
        //check for BUTTONINPUT div
        const buttonInput = document.getElementById("BUTTONINPUT");
        if (buttonInput) {
          const button = buttonInput.querySelector(`div button:nth-of-type(${index})`);
          if (button) {
            button.click();
            return;
          }
        }
        //resources
        const resourceElem = document.getElementById("myResourcePopup");
        if (resourceElem && resourceElem.style.display === "inline") {
          const resourceCard = resourceElem.querySelector(`div:nth-of-type(2) a:nth-of-type(${index}) img`);
          if (resourceCard) {
            resourceCard.click();
            return;
          }
        }
        //discard (could be yours or theirs)
        let discardElem = document.getElementById("myDiscardPopup");
        if (!discardElem) discardElem = document.getElementById("theirDiscardPopup");
        if (discardElem && discardElem.style.display === "inline") {
          const discardCard = discardElem.querySelector(`div:nth-of-type(2) a:nth-of-type(${index}) img.cardImage`);
          if (discardCard) {
            discardCard.click();
            return;
          }
        }
        //check hand last
        const handElem = document.getElementById("myHand");
        if(!handElem) return;
        const handCard = handElem.querySelector(`span:nth-of-type(${index}) a`);
        if (handCard) return handCard.click();
      }
    </script>

    <script src="./jsInclude250308.js"></script>
    <script src="./GeneratedCode/GeneratedCardDictionaries.js"></script>

    <?php
    ?>
    <style>
      :root {
        <?php if (IsDarkMode($playerID)) echo ("color-scheme: dark;");
        else echo ("color-scheme: light;");

        ?>
      }
    </style>

  </head>

  <body onkeypress='Hotkeys(event)' onload='OnLoadCallback(<?php echo (filemtime(LogPath($gameName))); ?>)'>

    <?php echo (CreatePopup("inactivityWarningPopup", [], 0, 0, "⚠️ Inactivity Warning ⚠️", 1, "", "", true, true, "Interact with the screen in the next 30 seconds or you could be kicked for inactivity.")); ?>
    <?php echo (CreatePopup("inactivePopup", [], 0, 0, "⚠️ You are Inactive ⚠️", 1, "", "", true, true, "You are inactive. Your opponent is able to claim victory. Interact with the screen to clear this.")); ?>

    <script>
      var IDLE_TIMEOUT = 30; //seconds
      var _idleSecondsCounter = 0;
      var _idleState = 0; //0 = not idle, 1 = idle warning, 2 = idle
      var _lastUpdate = 0;

      var activityFunction = function() {
        var oldIdleState = _idleState;
        _idleSecondsCounter = 0;
        _idleState = 0;
        var inactivityPopup = document.getElementById('inactivityWarningPopup');
        if (inactivityPopup) inactivityPopup.style.display = "none";
        var inactivePopup = document.getElementById('inactivePopup');
        if (inactivePopup) inactivePopup.style.display = "none";
        if (oldIdleState == 2) SubmitInput("100005", "");
      };

      document.onclick = activityFunction;

      document.onmousemove = activityFunction;

      document.onkeydown = activityFunction;

      window.setInterval(CheckIdleTime, 1000);

      function CheckIdleTime() {
        if (document.getElementById("iconHolder") == null || document.getElementById("iconHolder").innerText != "ready.png") return;
        _idleSecondsCounter++;
        if (_idleSecondsCounter >= IDLE_TIMEOUT) {
          if (_idleState == 0) {
            _idleState = 1;
            _idleSecondsCounter = 0;
            var inactivityPopup = document.getElementById('inactivityWarningPopup');
            if (inactivityPopup) inactivityPopup.style.display = "inline";
          } else if (_idleState == 1) {
            _idleState = 2;
            var inactivityPopup = document.getElementById('inactivityWarningPopup');
            if (inactivityPopup) inactivityPopup.style.display = "none";
            var inactivePopup = document.getElementById('inactivePopup');
            if (inactivePopup) inactivePopup.style.display = "inline";
            SubmitInput("100006", "");
          }
        }
      }
    </script>

    <audio id="yourTurnSound" src="./Assets/prioritySound.wav"></audio>

    <script>
      function reload() {
        CheckReloadNeeded(0);
      }

      function CheckReloadNeeded(lastUpdate) {
        var xmlhttp = new XMLHttpRequest();
        xmlhttp.onreadystatechange = function() {
          if (this.readyState == 4 && this.status == 200) {
            if (this.responseText == "NaN") {} //Do nothing, game is invalid
            else if (this.responseText.split("REMATCH")[0] == "1234") {
              location.replace('GameLobby.php?gameName=<?php echo ($gameName); ?>&playerID=<?php echo ($playerID); ?>&authKey=<?php echo ($authKey); ?>');
            } else if (parseInt(this.responseText) != 0) {
              HideCardDetail();
              var responseArr = this.responseText.split("GSDELIM");
              var update = parseInt(responseArr[0]);
              if (update != "NaN") CheckReloadNeeded(update);
              if(update < _lastUpdate) return;
              //An update was received, begin processing it
              _lastUpdate = update;

              //Handle events; they may need a delay in the card rendering
              var events = responseArr[1];
              if(<?php echo(AreAnimationsDisabled($playerID) ? 'false' : 'events != ""'); ?>) {
                var eventsArr = events.split("~");
                var myPlayerID = <?php echo($playerID); ?>;
                if(eventsArr.length > 0) {
                  var popup = document.getElementById("CHOOSEMULTIZONE");
                  if(!popup) popup = document.getElementById("MAYCHOOSEMULTIZONE");
                  if(popup) popup.style.display = "none";
                  var timeoutAmount = 0;
                  var eventsArr = reduceDamageAndRestoreEvents(eventsArr);
                  for(var i=0; i<eventsArr.length; i+=2) {
                    var eventType = eventsArr[i];//DAMAGE
                    if(eventType == "DAMAGE") {
                      var eventArr = eventsArr[i+1].split("!");
                      //Now do the animation
                      if(eventArr[0] == "P1BASE" || eventArr[0] == "P2BASE") var element = document.getElementById(eventArr[0]);
                      else var element = document.getElementById("unique-" + eventArr[0]);
                      if(!!element) {
                        if(timeoutAmount < 500) timeoutAmount = 500;
                        element.innerHTML += "<div class='dmg-animation dmg-animation-a'><div class='dmg-animation-a-inner'></div></div>";
                        element.innerHTML += "<div class='dmg-animation-a-label'><div class='dmg-animation-a-label-inner'>-" + eventArr[1] + "</div></div>";
                      }
                    } else if(eventType == "RESTORE") {
                      var eventArr = eventsArr[i+1].split("!");
                      //Now do the animation
                      if(eventArr[0] == "P1BASE" || eventArr[0] == "P2BASE") var element = document.getElementById(eventArr[0]);
                      else var element = document.getElementById("unique-" + eventArr[0]);
                      if(!!element) {
                        if(timeoutAmount < 500) timeoutAmount = 500;
                        element.innerHTML += "<div class='dmg-animation' style='position:absolute; text-align:center; font-size:36px; top: 0px; left:-2px; width:100%; height: calc(100% - 8px); padding: 0 2px; border-radius:12px; background-color:rgba(95,167,219,0.5); z-index:1000;'><div style='padding: 25px 0; width:100%; height:100%:'></div></div>";
                        element.innerHTML += "<div style='position:absolute; text-align:center; animation-name: move; animation-duration: 0.6s; font-size:34px; font-weight: 600; text-shadow: 1px 1px 0px rgba(0, 0, 0, 0.60); top:0px; left:0px; width:100%; height:100%; background-color:rgba(0,0,0,0); z-index:1000;'><div style='padding: 25px 0; width:100%; height:100%:'>+" + eventArr[1] + "</div></div>";
                      }
                    } else if(eventType == "EXHAUST") {
                      var eventArr = eventsArr[i+1].split("!");
                      //Now do the animation
                      if(eventArr[0] == "P1BASE" || eventArr[0] == "P2BASE") var element = document.getElementById(eventArr[0]);
                      else var element = document.getElementById("unique-" + eventArr[0]);
                      const timing = {
                          duration: 60,
                          iterations: 1,
                        };
                        const exhaustAnimation = [
                        { transform: "rotate(0deg) scale(1)" },
                        { transform: "rotate(5deg) scale(1)" },
                      ];
                      if(!!element) {
                        if(timeoutAmount < 60) timeoutAmount = 60;
                        element.animate(exhaustAnimation,timing);
                        element.innerHTML += "<div style='position:absolute; text-align:center; font-size:36px; top: 0px; left:-2px; width:100%; height: calc(100% - 16px); padding: 0 2px; border-radius:12px; background-color:rgba(0,0,0,0.5);'><div style='width:100%; height:100%:'></div></div>";
                        element.className += "exhausted";
                      }
                    } else if(eventType == "SHUFFLE") {
                        for(var j=1; j<=4; ++j) {
                          var element = document.getElementById(eventsArr[i+1] + j);
                          if(!!element) {
                            if(timeoutAmount < 500) timeoutAmount = 500;
                            element.style.animation = "shuffle 0.5s ease-in-out forwards";
                            element.style.animationDelay = `calc(var(--i) * 0.1s)`;
                          }
                        }
                    } else if(eventType == "REVEAL") {
                      var eventArr = eventsArr[i+1].split("!");
                      var revealPlayer = eventArr[0];
                      var revealLocation = eventArr[1];
                      var revealCardID = eventArr[2];
                      //Reveals besides deck not yet supported
                      if(revealLocation != "DECK") continue;
                      var frontFace = document.getElementById('P' + revealPlayer + revealLocation + 'REVEALFRONT');
                      frontFace.innerHTML = Card(revealCardID, "concat", 96, "", 1);
                      var element = document.getElementById('P' + revealPlayer + revealLocation + 'REVEALCARD');
                      element.classList.toggle('is-flipped');
                      if(timeoutAmount < 1000) timeoutAmount = 1000;
                    } else if(eventType == "FORCETOKEN") {
                      var eventArr = eventsArr[i+1].split("!");
                      var id = "P" + eventArr[0] + "FORCETOKEN";
                      var element = document.getElementById(id);
                      if(!!element) {
                        if(timeoutAmount < 500) timeoutAmount = 500;
                        if(eventArr[1] == "1") {
                          element.style.display = 'block';
                          element.style.opacity = '0';
                          element.animate(
                            [
                              { opacity: 0 },
                              { opacity: 1 }
                            ],
                            {
                              duration: 500,
                              easing: 'ease-in-out',
                              fill: 'forwards'
                            }
                          ).onfinish = function() {
                            element.style.opacity = '1';
                          };
                        } else {
                          element.style.opacity = '1';
                          element.animate(
                            [
                              { opacity: 1 },
                              { opacity: 0 }
                            ],
                            {
                              duration: 500,
                              easing: 'ease-in-out',
                              fill: 'forwards'
                            }
                          ).onfinish = function() {
                            element.style.display = 'none';
                          };
                        }
                      }
                    }
                  }
                  if(timeoutAmount > 0) setTimeout(RenderUpdate, timeoutAmount, responseArr[2]);
                  else RenderUpdate(responseArr[2]);
                }
              }
              else RenderUpdate(responseArr[2]);
            } else {
              CheckReloadNeeded(lastUpdate);
            }
          }
        };
        var dimensions = "&windowWidth=" + window.innerWidth + "&windowHeight=" + window.innerHeight;
        var lcpEl = document.getElementById("lastCurrentPlayer");
        var lastCurrentPlayer = "&lastCurrentPlayer=" + (!lcpEl ? "0" : lcpEl.innerHTML);
        if (lastUpdate == "NaN") window.location.replace("https://www.petranaki.net/game/MainMenu.php");
        else xmlhttp.open("GET", "GetNextTurn2.php?gameName=<?php echo ($gameName); ?>&playerID=<?php echo ($playerID); ?>&lastUpdate=" + lastUpdate + lastCurrentPlayer + "&authKey=<?php echo ($authKey); ?>" + dimensions, true);
        xmlhttp.send();
      }

      function reduceDamageAndRestoreEvents(events) {
        var groupedByTarget = {};
        var newEvents = [];

        for (var i = 0; i < events.length; i += 2) {
          var eventType = events[i];
          var eventData = events[i+1];

          if (eventType == "DAMAGE" || eventType == "RESTORE") {
            var eventArr = eventData.split("!");
            var target = eventArr[0];
            if (!groupedByTarget[target]) {
              groupedByTarget[target] = 0;
            }

            if (eventType == "DAMAGE") {
              groupedByTarget[target] -= parseInt(eventArr[1]);
            } else if (eventType == "RESTORE") {
              groupedByTarget[target] += parseInt(eventArr[1]);
            }
          } else {
            newEvents.push(eventType);
            newEvents.push(eventData);
          }
        }

        // iterate through groupedByTarget and add the events to the newEvents array
        for (var target in groupedByTarget) {
          if (groupedByTarget[target] > 0) {
            newEvents.push("RESTORE");
            newEvents.push(target + "!" + groupedByTarget[target]);
          } else if (groupedByTarget[target] < 0) {
            newEvents.push("DAMAGE");
            newEvents.push(target + "!" + Math.abs(groupedByTarget[target]));
          }
        }

        return newEvents;
      }

      function RenderUpdate(updatedHTML) {
        //Update the main div
        document.getElementById("mainDiv").innerHTML = updatedHTML;

        //Update the icon, game log, and play ready sound if needed
        var readyIcon = document.getElementById("iconHolder").innerText;
        document.getElementById("icon").href = "./Images/" + readyIcon;
        var log = document.getElementById('gamelog');
        if(log !== null) log.scrollTop = log.scrollHeight;
        if(readyIcon == "ready.png") {
          try {
            var audio = document.getElementById('yourTurnSound');
            <?php if (!IsMuted($playerID)) echo ("audio.play();");
             ?>
          } catch (e) {

          }
        }

        //Now begin populating the cards
        PopulateZone("myHand", cardSize);
        PopulateZone("theirHand", cardSize);
        PopulateZone("myChar", cardSize);
        PopulateZone("theirChar", cardSize);
        var sidebarWrapper = document.getElementById("sidebarWrapper");
        if(sidebarWrapper)
        {
          var sidebarWrapperWidth = sidebarWrapper.style.width;
          var chatbox = document.getElementById("chatbox");
          if(chatbox) chatbox.style.width = (parseInt(sidebarWrapperWidth)-10) + "px";
          var chatText = document.getElementById("chatText");
          if(chatText) chatText.style.width = (parseInt(sidebarWrapperWidth)-100) + "px";
        }
      }

      function chkSubmit(mode, count) {
        var input = "";
        input += "&gameName=" + document.getElementById("gameName").value;
        input += "&playerID=" + document.getElementById("playerID").value;
        input += "&chkCount=" + count;
        for (var i = 0; i < count; ++i) {
          var el = document.getElementById("chk" + i);
          if (el.checked) input += "&chk" + i + "=" + el.value;
        }
        SubmitInput(mode, input);
      }

      function chkSubmitBoth(mode, countTheirs, countMine) {
        var input = "";
        input += "&gameName=" + document.getElementById("gameName").value;
        input += "&playerID=" + document.getElementById("playerID").value;
        input += "&chkCountTheirs=" + countTheirs;
        input += "&chkCountMine=" + countMine;
        for (var i = 0; i < countTheirs; ++i) {
          var el = document.getElementById("chkt" + i);
          if (el.checked) input += "&chkt" + i + "=" + el.value;
        }
        for (var i = 0; i < countMine; ++i) {
          var el = document.getElementById("chkm" + i);
          if (el.checked) input += "&chkm" + i + "=" + el.value;
        }
        SubmitInput(mode, input);
      }

      function textSubmit(mode) {
        var input = "";
        input += "&gameName=" + document.getElementById("gameName").value;
        input += "&playerID=" + document.getElementById("playerID").value;
        input += "&inputText=" + document.getElementById("inputText").value;
        SubmitInput(mode, input);
      }

      function suppressEventPropagation(e)
      {
        e.stopPropagation();
      }
    </script>

    <?php
    // Display hidden elements and Chat UI
    ?>
    <div id='popupContainer'></div>
    <div id="cardDetail" style="z-index:100000; display:none; position:fixed;"></div>
    <div id='mainDiv' style='position:fixed; z-index:20; left:0; top:0; width:100%; height:100%;'></div>
    <div id='chatbox' style='z-index:40; position:fixed; bottom:20px; right:18px; display:flex;'>
        <?php if ($playerID != 3 && !IsChatMuted()): ?>
            <?php
            $playerAspects = explode(",", LeaderMainAspect($playerID));
            echo ("<input type='hidden' id='playerAspect' name='playerAspect' value='" . $playerAspects[0] . "'>");
            ?>
            <input id='chatText'
                  style='background: black; color: white; font-size:16px; font-family:barlow; margin-left: 8px; height: 32px; border: 1px solid #454545; border-radius: 5px 0 0 5px;'
                  type='text'
                  name='chatText'
                  value=''
                  autocomplete='off'
                  onkeypress='ChatKey(event)'>
            <button style='border: 1px solid #454545; border-radius: 0 5px 5px 0; width:55px; height:32px; color: white; margin: 0 0 0 -1px; padding: 0 5px; font-size:16px; font-weight:600; box-shadow: none;'
                    onclick='SubmitChat()'>
                    Chat
            </button>
            <button title='Disable Chat'
                    <?= ProcessInputLink($playerID, 26, $SET_MuteChat . "-1", fullRefresh:true); ?>
                    style='border: 1px solid #454545; color: #1a1a1a; padding: 0; box-shadow: none;'>
                <img style='height:16px; width:16px; float:left; margin: 7px;' src='./Images/disable.png' />
            </button>
        <?php elseif ($playerID != 3): ?>
            <button title='Re-enable Chat'
                    <?= ProcessInputLink($playerID, 26, $SET_MuteChat . "-0", fullRefresh:true); ?>
                    style='border: 1px solid #454545; width: 100%; padding: 0 0 4px 0; height: 32px; font: inherit; box-shadow: none;'>
                ⌨️ Re-enable Chat
            </button>
        <?php endif; ?>
    </div>

    <input type='hidden' id='gameName' value='<?= htmlspecialchars($gameName, ENT_QUOTES, 'UTF-8'); ?>'>
    <input type='hidden' id='playerID' value='<?= htmlspecialchars($playerID, ENT_QUOTES, 'UTF-8'); ?>'>
    <input type='hidden' id='authKey' value='<?= htmlspecialchars($authKey, ENT_QUOTES, 'UTF-8'); ?>'>


  </body>
