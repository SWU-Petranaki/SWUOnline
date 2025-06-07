<?php
// Start with PHP - no HTML output before this
include_once 'MenuBar.php';
include "HostFiles/Redirector.php";
include_once "Libraries/PlayerSettings.php";
include_once 'Assets/patreon-php-master/src/PatreonDictionary.php';
include_once "APIKeys/APIKeys.php";
include_once './AccountFiles/AccountDatabaseAPI.php';
include_once './Database/ConnectionManager.php';
include_once 'Libraries/GameFormats.php';

// Check if the user is banned
if (isset($_SESSION["userid"]) && IsBanned($_SESSION["userid"])) {
  header("Location: ./PlayerBanned.php");
  exit;
}

// April Fools Day
if ((date('m') == '04' && date('d') == '01')) {
  // Check if the user has already seen the April Fools page
  $aprilFoolsSeen = false;
  if (isset($_COOKIE['april_fools_seen']) && $_COOKIE['april_fools_seen'] == date('Y')) {
    $aprilFoolsSeen = true;
  }

  // If they haven't seen it yet, set a cookie and redirect
  if (!$aprilFoolsSeen) {
    header("Location: ./Banned.php");
    exit;
  }
}

$userData = isset($_SESSION["useruid"]) ? LoadUserData($_SESSION["useruid"]) : NULL;

$language = TryGet("language", 1);
$settingArray = [];
$defaultFormat = 0;
$defaultVisibility = (isset($_SESSION["useruid"]) ? 1 : 0);
if (isset($_SESSION["userid"])) {
  $savedSettings = LoadSavedSettings($_SESSION["userid"]);
  for ($i = 0; $i < count($savedSettings); $i += 2) {
    $settingArray[$savedSettings[intval($i)]] = $savedSettings[intval($i) + 1];
  }
  if (isset($settingArray[$SET_Format])) $defaultFormat = $settingArray[$SET_Format];
  if (isset($settingArray[$SET_GameVisibility])) $defaultVisibility = $settingArray[$SET_GameVisibility];
}
$_SESSION['language'] = $language;
$isPatron = $_SESSION["isPatron"] ?? false;

$createGameText = ($language == 1 ? "Create Game" : "ゲームを作る");
$languageText = ($language == 1 ? "Language" : "言語");
$createNewGameText = ($language == 1 ? "Create New Game" : "新しいゲームを作成する");
$starterDecksText = ($language == 1 ? "Starter Decks" : "おすすめデッキ");
$deckUrl = TryGet("deckUrl", '');

$canSeeQueue = isset($_SESSION["useruid"]);

// Process error messages after all session operations
$errorScript = "";
if (!empty($_SESSION['error'])) {
  $error = $_SESSION['error'];
  unset($_SESSION['error']);
  $errorScript = "<script>
    document.addEventListener('DOMContentLoaded', function() {
      document.getElementById('mainMenuError').innerHTML = '$error';
      document.getElementById('mainMenuError').classList.remove('error-popup-hidden');
      document.getElementById('mainMenuError').classList.add('error-popup');
    });

    setTimeout(function() {
      document.getElementById('mainMenuError').classList.remove('error-popup');
      document.getElementById('mainMenuError').classList.add('error-popup-hidden');
    }, 10000);

    document.addEventListener('click', function(event) {
      if (!event.target.closest('#mainMenuError')) {
        document.getElementById('mainMenuError').classList.remove('error-popup');
        document.getElementById('mainMenuError').classList.add('error-popup-hidden');
      }
    });
  </script>";
}

//Cantina Brawl Format; Update this to rotate formats
$funFormatBackendName = Formats::$GalacticCivilWar;
$funFormatDisplayName = FormatDisplayName($funFormatBackendName);

// Now start HTML output after all PHP processing is done
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Petranaki</title>
    <style>
        /* Tooltip fade-in animation */
        #global-tooltip {
            opacity: 0;
            transition: opacity 0.2s ease-in-out;
        }
        #global-tooltip.show-tooltip {
            opacity: 1;
        }
    </style>
</head>
<body>
<div class="site-container">
  <!-- Header & MenuBar wrapper to coordinate them better -->
  <?php include_once 'Header.php'; ?>
  <!-- MenuBar is already included at the top of this file -->

  <div class="content-wrapper">
    <div id="mainMenuError" class="error-popup-hidden"></div>

    <!-- Add a single global tooltip that will be positioned dynamically -->
    <div id="global-tooltip"></div>

    <div class="main-layout">
      <!-- COLUMN 1: DECK SELECTION -->
      <div class="deck-column">
        <div class="container bg-yellow">
          <div id="deckFeedback" class="deck-feedback"></div>
          <!-- SWU Stats integration -->
          <?php
          $favoriteDecks = [];
          $swuStatsLinked = isset($userData) && $userData["swustatsAccessToken"] != null;
          $selIndex = -1;
          if (isset($settingArray[$SET_FavoriteDeckIndex])) $selIndex = $settingArray[$SET_FavoriteDeckIndex];

          if (isset($_SESSION["userid"])) {
            if ($swuStatsLinked) {
              echo "<div id='deckLoadingContainer' class='swustats-connected'>";
              echo "<select id='swuDecksLoading' name='swuDecksLoading' disabled>";
              echo "<option>Loading decks...</option>";
              echo "</select>";
              echo "</div>";
              echo "<div id='deckDropdownContainer' style='display: none;'>
                <select id='swuDecks' name='swuDecks' style='margin-top: 15px; margin-bottom: 10px;'>
                <option value=''>-- Select a deck --</option>
                </select>
                </div>";
              echo "<script>var savedFavoriteDeckIndex = " . $selIndex . ";</script>";
            } else {
              $favoriteDecks = LoadFavoriteDecks($_SESSION["userid"]);
              if (count($favoriteDecks) > 0) {
                $selIndex = -1;
                if (isset($settingArray[$SET_FavoriteDeckIndex])) $selIndex = $settingArray[$SET_FavoriteDeckIndex];
                echo ("<div class='SelectDeckInput'>");
                echo ("<select name='favoriteDecks' id='favoriteDecks'>");
                echo ("<option value=''>-- Select a deck --</option>");
                for ($i = 0; $i < count($favoriteDecks); $i += 4) {
            echo ("<option value='" . $i . "<fav>" . $favoriteDecks[$i] . "'" . ($i == $selIndex ? " selected " : "") . ">" . $favoriteDecks[$i + 1] . "</option>");
                }
                echo ("</select></div>");
              }
              echo "<p>Link your <a href='https://swustats.net/' target='_blank'>SWU Stats</a> account to import favorites! <span class='help-icon'>?</span></p>";
            }
          }
          ?>

          <div class="deck-link-input">
            <label for="deckLink">Deck Link:</label>
            <input type="text" id="deckLink" name="deckLink" value='<?= $deckUrl ?>' placeholder="Paste your deck URL here">

            <?php
              if (isset($_SESSION["userid"]) && !$swuStatsLinked) {
              echo ("<span class='save-deck'>");
              echo ("<label for='saveFavoriteDeck'><input class='inputFavoriteDeck' type='checkbox' id='saveFavoriteDeck' name='saveFavoriteDeck' />");
              echo ("Save to Favorite Decks</label>");
              echo ("</span>");
              }
            ?>
          </div>
        </div>
      </div>

      <!-- COLUMN 2: GAME ACTIONS -->
      <div class="game-action-column">
        <div class="tabs">
          <button class="tab-button active" id="gamesTabBtn" onclick="switchTabDirect('gamesTab')">Play</button>
          <button class="tab-button" id="spectateTabBtn" onclick="switchTabDirect('spectateTab')">Spectate</button>
        </div>

        <!-- GAMES TAB (COMBINED JOIN & CREATE) -->
        <div id="gamesTab" class="tab-content active">
          <div class="create-game-summary">
            <div class="summary-text">
              <span id="gameSettingsSummary">Create a new game: Premier Casual, <?php echo ($defaultVisibility == 1 ? "Public" : "Private"); ?></span>
            </div>
            <div class="summary-actions">
              <button class="create-btn" id="quickCreateGame">Create Game</button>
              <button class="edit-icon" id="openCreateGameModal">
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-gear-fill" viewBox="0 0 16 16">
                <path d="M9.405 1.05c-.413-1.4-2.397-1.4-2.81 0l-.1.34a1.464 1.464 0 0 1-2.105.872l-.31-.17c-1.283-.698-2.686.705-1.987 1.987l.169.311c.446.82.023 1.841-.872 2.105l-.34.1c-1.4.413-1.4 2.397 0 2.81l.34.1a1.464 1.464 0 0 1 .872 2.105l-.17.31c-.698 1.283.705 2.686 1.987 1.987l.311-.169a1.464 1.464 0 0 1 2.105.872l.1.34c.413 1.4 2.397 1.4 2.81 0l.1-.34a1.464 1.464 0 0 1 2.105-.872l.31.17c1.283.698 2.686-.705 1.987-1.987l-.169-.311a1.464 1.464 0 0 1 .872-2.105l.34-.1c1.4-.413 1.4-2.397 0-2.81l-.34-.1a1.464 1.464 0 0 1-.872-2.105l.17-.31c.698-1.283-.705-2.686-1.987-1.987l-.311.169a1.464 1.464 0 0 1-2.105-.872zM8 10.93a2.929 2.929 0 1 1 0-5.86 2.929 2.929 0 0 1 0 5.858z"/>
              </svg>
                Edit
              </button>
            </div>
          </div>
          <div class="create-game-summary">
              <div class="quick-match-container">
                <div class="summary-actions" style="display: flex; align-items: center; gap: 10px; width: 100%;">
                  <span style="white-space: nowrap; margin-right: 5px;">Quick Match:</span>
                  <div class="filter-dropdown-wrapper" style="flex: 1; margin-top: 10px;">
                    <select id="quickMatchFormat" class="styled-dropdown">
                      <?php if($canSeeQueue) { ?>
                        <option value="premierf" <?php echo (FormatCode('premierf') == $defaultFormat ? "selected" : ""); ?>>Premier Casual</option>
                        <option value="prstrict" <?php echo (FormatCode('prstrict') == $defaultFormat ? "selected" : ""); ?>>Premier (Best of 3)</option>
                        <option value="<?php echo $funFormatBackendName?>" <?php echo (FormatCode($funFormatBackendName) == $defaultFormat ? "selected" : ""); ?>>Cantina Brawl</option>
                        <option value="previewf" <?php echo (FormatCode('previewf') == $defaultFormat ? "selected" : ""); ?>>Preview (Set 5)</option>
                      <?php } ?>
                      <option value="openform" <?php echo (FormatCode('openform') == $defaultFormat ? "selected" : ""); ?>>Open Format</option>
                    </select>
                  </div>
                  <button id="findQuickMatch" class="create-btn">Find Game</button>
                </div>
                <div id="quickMatchStatus" class="quick-match-status"></div>
              </div>

              <script>
              document.addEventListener('DOMContentLoaded', function() {
                const findQuickMatchBtn = document.getElementById('findQuickMatch');
                const quickMatchFormat = document.getElementById('quickMatchFormat');
                const quickMatchStatus = document.getElementById('quickMatchStatus');

                if (findQuickMatchBtn) {
                  findQuickMatchBtn.addEventListener('click', function() {
                    // Check if a valid deck is selected
                    const deckLink = document.getElementById('deckLink').value;
                    if (!deckLink || deckLink.trim() === '' || !validateDeckLink(deckLink)) {
                      quickMatchStatus.textContent = 'Please select a valid deck first';
                      quickMatchStatus.className = 'quick-match-status error';
                      return;
                    }

                    // Show loading status
                    quickMatchStatus.textContent = 'Searching for games...';
                    quickMatchStatus.className = 'quick-match-status loading';

                    // Get selected format
                    const selectedFormat = quickMatchFormat.value;

                    // Fetch open games for user
                    fetch('APIs/GetOpenGames.php?forCurrentPlayer=true')
                      .then(response => response.json())
                      .then(data => {
                        // Find first game matching the selected format and also match on not blocked by user and user not blocked by them
                        const matchingGame = data.openGames?.find(game => game.format === selectedFormat);

                        if (matchingGame) {
                          // Game found
                          quickMatchStatus.textContent = `Game found! Joining ${matchingGame.description || 'Game #' + matchingGame.gameName}...`;
                          quickMatchStatus.className = 'quick-match-status success';

                          // Check if the game is still available before joining
                          fetch('APIs/GetOpenGames.php?forCurrentPlayer=true')
                            .then(response => response.json())
                            .then(latestData => {
                              // Check if the game still exists in the open games list
                              const isStillAvailable = latestData.openGames?.some(game => game.gameName === matchingGame.gameName);

                              if (!isStillAvailable) {
                                // Game is no longer available, create a new game instead
                                quickMatchStatus.textContent = 'Game no longer available. Creating a new game...';
                                quickMatchStatus.className = 'quick-match-status warning';

                                // Create form to create a new game
                                const form = document.createElement('form');
                                form.method = 'GET';
                                form.action = `${window.location.origin}/Arena/CreateGame.php`;

                                // Add format
                                const formatInput = document.createElement('input');
                                formatInput.type = 'hidden';
                                formatInput.name = 'format';
                                formatInput.value = selectedFormat;
                                form.appendChild(formatInput);

                                // Add deck
                                const fabdbInput = document.createElement('input');
                                fabdbInput.type = 'hidden';
                                fabdbInput.name = 'fabdb';
                                fabdbInput.value = document.getElementById('deckLink').value;
                                form.appendChild(fabdbInput);

                                // Add visibility (public for quick match)
                                const visibilityInput = document.createElement('input');
                                visibilityInput.type = 'hidden';
                                visibilityInput.name = 'visibility';
                                visibilityInput.value = 'public';
                                form.appendChild(visibilityInput);

                                // Add game description
                                const descriptionInput = document.createElement('input');
                                descriptionInput.type = 'hidden';
                                descriptionInput.name = 'gameDescription';
                                descriptionInput.value = 'Quick Match';
                                form.appendChild(descriptionInput);

                                // Add save preference if checked
                                const saveDeck = document.getElementById('saveFavoriteDeck');
                                if (saveDeck && saveDeck.checked) {
                                  const favDeckInput = document.createElement('input');
                                  favDeckInput.type = 'hidden';
                                  favDeckInput.name = 'favoriteDeck';
                                  favDeckInput.value = 'on';
                                  form.appendChild(favDeckInput);
                                }

                                // Submit form
                                document.body.appendChild(form);
                                form.submit();
                                return;
                              } else {
                                // Create form to join game
                                const form = document.createElement('form');
                                form.method = 'GET';
                                form.action = `${window.location.origin}/Arena/JoinGameInput.php`;

                                // Add game name
                                const gameNameInput = document.createElement('input');
                                gameNameInput.type = 'hidden';
                                gameNameInput.name = 'gameName';
                                gameNameInput.value = matchingGame.gameName;
                                form.appendChild(gameNameInput);

                                // Add player ID
                                const playerIDInput = document.createElement('input');
                                playerIDInput.type = 'hidden';
                                playerIDInput.name = 'playerID';
                                playerIDInput.value = '2';
                                form.appendChild(playerIDInput);

                                // Add deck
                                const fabdbInput = document.createElement('input');
                                fabdbInput.type = 'hidden';
                                fabdbInput.name = 'fabdb';
                                fabdbInput.value = document.getElementById('deckLink').value;
                                form.appendChild(fabdbInput);

                                // Add save preference if checked
                                const saveDeck = document.getElementById('saveFavoriteDeck');
                                if (saveDeck && saveDeck.checked) {
                                  const favDeckInput = document.createElement('input');
                                  favDeckInput.type = 'hidden';
                                  favDeckInput.name = 'favoriteDeck';
                                  favDeckInput.value = 'on';
                                  form.appendChild(favDeckInput);
                                }

                                // Submit form
                                document.body.appendChild(form);
                                form.submit();
                              }
                            });
                        } else {
                          // No matching games found - create a new game
                          quickMatchStatus.textContent = 'No games found. Creating a new game...';
                          quickMatchStatus.className = 'quick-match-status loading';

                          // Create form to create a new game
                          const form = document.createElement('form');
                          form.method = 'GET';
                          form.action = `${window.location.origin}/Arena/CreateGame.php`;

                          // Add format
                          const formatInput = document.createElement('input');
                          formatInput.type = 'hidden';
                          formatInput.name = 'format';
                          formatInput.value = selectedFormat;
                          form.appendChild(formatInput);

                          // Add deck
                          const fabdbInput = document.createElement('input');
                          fabdbInput.type = 'hidden';
                          fabdbInput.name = 'fabdb';
                          fabdbInput.value = document.getElementById('deckLink').value;
                          form.appendChild(fabdbInput);

                          // Add visibility (public for quick match)
                          const visibilityInput = document.createElement('input');
                          visibilityInput.type = 'hidden';
                          visibilityInput.name = 'visibility';
                          visibilityInput.value = 'public';
                          form.appendChild(visibilityInput);

                          // Add game description
                          const descriptionInput = document.createElement('input');
                          descriptionInput.type = 'hidden';
                          descriptionInput.name = 'gameDescription';
                          descriptionInput.value = 'Quick Match';
                          form.appendChild(descriptionInput);

                          // Add save preference if checked
                          const saveDeck = document.getElementById('saveFavoriteDeck');
                          if (saveDeck && saveDeck.checked) {
                            const favDeckInput = document.createElement('input');
                            favDeckInput.type = 'hidden';
                            favDeckInput.name = 'favoriteDeck';
                            favDeckInput.value = 'on';
                            form.appendChild(favDeckInput);
                          }

                          // Submit form
                          document.body.appendChild(form);
                          form.submit();
                        }
                      });
                    });
                }
              });
              </script>
          </div>
          <!-- Filters and refresh button are now part of the Games tab only -->
          <div class="game-list-filters">
            <div class="filter-dropdown-wrapper">
              <span class="filter-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon>
                </svg>
              </span>
              <select id="formatFilter" class="styled-dropdown">
                <?php if($canSeeQueue) { ?>
                  <option value="all">All Formats</option>
                  <option value="premierf">Premier Casual</option>
                  <option value="prstrict">Premier (Best of 3)</option>
                  <option value="<?php echo $funFormatBackendName?>">Cantina Brawl</option>
                  <option value="previewf">Preview (Set 5)</option>
                <?php } ?>
                  <option value="openform">Open Format</option>
              </select>
            </div>
            <button id="refreshGameList" class="refresh-btn" title="Refresh game list">
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M21.5 2v6h-6M2.5 22v-6h6M2 11.5a10 10 0 0 1 18.8-4.3M22 12.5a10 10 0 0 1-18.8 4.2"/>
              </svg>
            </button>
          </div>
          <div id="gameList" class="game-list">
            <p id="gameListLoading">Loading games...</p>
            <div id="gameListContent"></div>
          </div>
        </div>

        <!-- SPECTATE TAB -->
        <div id="spectateTab" class="tab-content">
          <div class="game-list-filters">
            <div class="filter-dropdown-wrapper">
              <span class="filter-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon>
                </svg>
              </span>
              <select id="spectateFormatFilter" class="styled-dropdown">
                <?php if($canSeeQueue) { ?>
                  <option value="all">All Formats</option>
                  <option value="premierf">Premier Casual</option>
                  <option value="prstrict">Premier (Best of 3)</option>
                  <option value="<?php $funFormatBackendName?>">Cantina Brawl</option>
                  <option value="previewf">Preview (Set 5)</option>
                <?php } ?>
                <option value="openform">Open Format</option>
              </select>
            </div>
            <button id="refreshSpectateList" class="refresh-btn" title="Refresh spectate list">
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M21.5 2v6h-6M2.5 22v-6h6M2 11.5a10 10 0 0 1 18.8-4.3M22 12.5a10 10 0 0 1-18.8 4.2"/>
              </svg>
            </button>
          </div>

          <div id="spectateList" class="game-list">
            <p id="spectateListLoading">Loading games...</p>
            <div id="spectateListContent"></div>
          </div>
        </div>

        <!-- CREATE GAME MODAL -->
        <div id="createGameModal" class="modal-overlay">
          <div class="modal-content">
            <div class="modal-header">
              <h3 class="modal-title"><?php echo ($createNewGameText); ?></h3>
              <button class="modal-close" id="closeCreateGameModal">&times;</button>
            </div>

            <form id="createGameForm" action='<?= $redirectPath ?>/CreateGame.php'>
              <input type='hidden' id='fabdb' name='fabdb' value=''>
              <input type='hidden' id='favoriteDeck' name='favoriteDeck' value='0'>

              <label for="gameDescription" class='game-name-label'>Game Name</label>
              <input type="text" id="gameDescription" name="gameDescription" placeholder="Game #">

              <?php
              $standardFormatCasual = Formats::$PremierFormat;
              $standardFormat = Formats::$PremierStrict;
              $previewFormat = Formats::$PreviewFormat;
              $openFormat = Formats::$OpenFormat;
              echo ("<br><label for='format' class='SelectDeckInput'>Format</label>");
              echo ("<select name='format' id='format'>");
              if($canSeeQueue) {
                echo ("<option value='$standardFormatCasual' " . ($defaultFormat == FormatCode($standardFormatCasual) ? " selected" : "") . ">Premier Casual</option>");
                echo ("<option value='$standardFormat' " . ($defaultFormat == FormatCode($standardFormat) ? " selected" : "") . ">Premier (Best of 3)</option>");
                echo ("<option value='$funFormatBackendName'" . ($defaultFormat == FormatCode($funFormatBackendName) ? " selected" : "") . ">Cantina Brawl ($funFormatDisplayName)</option>");
                echo ("<option value='$previewFormat' " . ($defaultFormat == FormatCode($previewFormat) ? " selected" : "") . ">Preview (Set 5)</option>");
              }
              echo ("<option value='$openFormat'" . ($defaultFormat == FormatCode($openFormat) ? " selected" : "") . ">" . FormatDisplayName($openFormat) . "</option>");
              echo ("</select>");
              ?>

              <?php
              echo ("<label for='visibility' class='SelectDeckInput'>Game Visibility</label>");
              echo ("<select name='visibility' id='visibility'>");

              if ($canSeeQueue) {
                echo ("<option value='public'" . ($defaultVisibility == 1 ? " selected" : "") . ">Public</option>");
              } else {
                echo '<p class="login-notice">&#10071;<a href="./LoginPage.php">Log In</a> to be able to create public games.</p>';
              }

              echo ("<option value='private'" . ($defaultVisibility == 0 ? " selected" : "") . ">Private</option>");
              echo ("</select>");
              ?>

              <div class="modal-footer">
                <button type="button" class="modal-btn modal-btn-secondary" id="cancelCreateGame">Cancel</button>
                <button type="submit" id="createGameButton" class="modal-btn modal-btn-primary" disabled><?php echo ($createGameText); ?></button>
              </div>
            </form>
          </div>
        </div>
      </div>

      <!-- COLUMN 3: NEWS & INFO -->
      <div class="news-column">
        <div class="container bg-yellow" style="margin-top: 20px;">
          <h2>News</h2>
          <div>
            <div style="position: relative;">
              <div style='vertical-align:middle; text-align: start;'>
                <img src="./Images/SWUKeyArt/SWH05_KeyArt.jpg" style="width: 100%; height: 200px; object-fit: cover; border-radius: 10px;">
                <h3 style="margin: 15px 0; display: block;">All Set 5 Cards Now Available</h3>
                <p>The full Set 5 roster has landed in Petranaki! Every card is now implemented and ready for battle — no need to wait for hyperspace delivery.</p>
                <p>With so many new cards added back-to-back, a few bugs or card implementation errors may have snuck in. If you notice anything off, please report it so we can address it promptly. We're also always open to feedback on how to improve the platform and your overall experience.</p>
                <p>Join our <a href="https://discord.gg/ep9fj8Vj3F" target="_blank" rel="noopener noreferrer">Discord server</a> to report issues, suggest improvements, and stay updated on future developments. May the Force be with you!</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <?php
  // Output any error messages
  echo $errorScript;
  ?>

  <?php include_once 'Disclaimer.php'; ?>
</div>

<script>
// Tab switching functionality (without alerts)
function switchTabDirect(tabId) {
    // Remove active class from all tabs
    document.querySelectorAll('.tab-content').forEach(tab => {
        tab.classList.remove('active');
    });

    // Add active class to the selected tab
    document.getElementById(tabId).classList.add('active');

    // Update button styles - only use the buttons that exist
    document.getElementById('gamesTabBtn').classList.remove('active');
    document.getElementById('spectateTabBtn').classList.remove('active');
    document.getElementById(tabId + 'Btn').classList.add('active');
}

// Show total games in the Games tab button
function updateGamesTabTotal(totalGames) {
  var gamesTabBtn = document.getElementById('spectateTabBtn');
  if (gamesTabBtn) {
    // Remove any previous count
    gamesTabBtn.innerHTML = 'Spectate' + (typeof totalGames === 'number' ? ' <span style="font-size:0.95em; color:#ffd24d;">(' + totalGames + ')</span>' : '');
  }
}

// Load other functionality on page ready
document.addEventListener('DOMContentLoaded', function() {
    // Deck validation
    const deckLinkInput = document.getElementById('deckLink');
    const fabdbHidden = document.getElementById('fabdb');
    const favoriteDecksSelect = document.getElementById('favoriteDecks');
    const favoriteDeckHidden = document.getElementById('favoriteDeck');
    const createGameButton = document.getElementById('createGameButton');
    const deckFeedback = document.getElementById('deckFeedback');
    const globalTooltip = document.getElementById('global-tooltip');
    const userLoggedIn = <?php echo isset($_SESSION["userid"]) ? 'true' : 'false'; ?>;

    // Clear deck link input when clicked
    if(deckLinkInput) {
        deckLinkInput.addEventListener('click', function() {
            this.value = '';
            // Update the hidden field
            if(fabdbHidden) fabdbHidden.value = '';
            // Update validation state
            validateDeckLink('');
        });
    }

    // Initialize with current value
    if(deckLinkInput && fabdbHidden) {
        fabdbHidden.value = deckLinkInput.value;
        // Run validation when the page loads to set initial button states
        validateDeckLink(deckLinkInput.value);

        // Update on change
        deckLinkInput.addEventListener('input', function() {
            fabdbHidden.value = deckLinkInput.value;
            validateDeckLink(deckLinkInput.value);
        });
    } else {
        // If deckLinkInput doesn't exist, disable all join buttons by default
        validateDeckLink('');
    }

    // Connect favorite decks dropdown to the form
    if(userLoggedIn && favoriteDecksSelect && favoriteDeckHidden) {
        favoriteDecksSelect.addEventListener('change', function() {
            var selectedValue = favoriteDecksSelect.value;
            favoriteDeckHidden.value = selectedValue;

            // If a favorite deck is selected, update the deck link input with the deck URL
            if(selectedValue && selectedValue.includes('<fav>')) {
                var parts = selectedValue.split('<fav>');
                if(parts.length > 1 && deckLinkInput) {
                    deckLinkInput.value = parts[1];
                    fabdbHidden.value = parts[1];
                    validateDeckLink(parts[1]);
                }

                // Save the selected index to user settings (similar to SWU Stats)
                var selectedIndex = selectedValue.split('<fav>')[0];
                if(selectedIndex) {
                    // Update user setting via AJAX
                    var xhr = new XMLHttpRequest();
                    xhr.open('GET', 'api/UpdateMyPlayerSetting.php?piece=<?= $SET_FavoriteDeckIndex ?>&value=' + selectedIndex + '&userid=<?= $_SESSION["userid"] ?? ''?>', true);
                    xhr.send();
                }
            }
        });

        // Auto-select the favorite deck if one is set in user settings
        if (favoriteDecksSelect.selectedIndex > 0) {
            // Trigger the change event to populate the deck link
            var event = new Event('change');
            favoriteDecksSelect.dispatchEvent(event);
        }
    }

    // Set up event handlers for help icons
    document.addEventListener('click', function(e) {
        if (!e.target.closest('#global-tooltip') && !e.target.closest('.help-icon')) {
            globalTooltip.style.display = 'none';
        }
    });

    // Track if we're hovering over either the help icon or the tooltip
    let isHoveringHelp = false;
    let tooltipHideTimeout = null;
    let tooltipShowTimeout = null; // Add timeout for showing tooltip

    document.addEventListener('mouseover', function(e) {
        const helpIcon = e.target.closest('.help-icon');
        const tooltip = e.target.closest('#global-tooltip');

        if (helpIcon || tooltip) {
            // Clear any pending hide timeout
            if (tooltipHideTimeout) {
                clearTimeout(tooltipHideTimeout);
                tooltipHideTimeout = null;
            }

            isHoveringHelp = true;

            if (helpIcon) {
                // Clear any existing show timeout
                if (tooltipShowTimeout) {
                    clearTimeout(tooltipShowTimeout);
                }

                // Set a short delay before showing the tooltip
                tooltipShowTimeout = setTimeout(() => {
                    // Get appropriate tooltip content based on context
                    let tooltipContent = '';

                    // Check if this is the SWU Stats link tooltip
                    if (helpIcon.parentElement && helpIcon.parentElement.textContent.includes('Link your SWU Stats account')) {
                        tooltipContent = `
                          <span class="tooltip-title">SWU Stats Integration</span>
                          <p>Connect your SWU Stats account to save and manage all your decks in one place!</p>
                          <ol style="padding-left: 20px; margin: 5px 0;">
                            <li style="display: list-item; list-style-type: decimal;">Log in to SWU Stats in another tab</li>
                            <li style="display: list-item; list-style-type: decimal;">Go to your Petranaki profile and click the Link SWU Stats button</li>
                            <li style="display: list-item; list-style-type: decimal;">Import your decks into SWU Stats with the cloud icon and click the heart icon</li>
                          </ol>
                        `;
                    } else {
                        // Default tooltip about deck links
                        tooltipContent = `
                            <span class="tooltip-title">Copy deck links or JSON from:</span>
                            <a href="https://swustats.net/" target="_blank" class="tooltip-link">SWU Stats</a>
                            <a href="https://melee.gg/" target="_blank" class="tooltip-link">Melee.gg</a>
                            <a href="https://www.swudb.com/" target="_blank" class="tooltip-link">SWUDB</a>
                            <a href="https://sw-unlimited-db.com/" target="_blank" class="tooltip-link">SW-Unlimited-DB</a>
                        `;
                    }

                    // Position tooltip at fixed position relative to the icon
                    const rect = helpIcon.getBoundingClientRect();
                    globalTooltip.innerHTML = tooltipContent;
                    // Make the tooltip visible with fade-in animation
                    globalTooltip.style.display = 'block';
                    globalTooltip.classList.remove('show-tooltip');
                    // Add the show-tooltip class after a brief delay to trigger the fade-in animation
                    setTimeout(() => {
                      globalTooltip.classList.add('show-tooltip');
                    }, 10);

                    // Mobile: show below icon, Desktop: show above
                    if (window.innerWidth <= 768) {
                        globalTooltip.style.left = rect.left + 'px';
                        globalTooltip.style.top = (rect.bottom + 12) + 'px'; // 12px below icon
                    } else {
                        globalTooltip.style.left = (rect.left - 120) + 'px'; // Center tooltip over icon
                        globalTooltip.style.top = (rect.top - 150) + 'px'; // Position higher above the icon
                    }

                    // Prevent tooltips from going off-screen
                    const tooltipRect = globalTooltip.getBoundingClientRect();
                    if (tooltipRect.left < 10) {
                        globalTooltip.style.left = '10px';
                    }
                    if (tooltipRect.right > window.innerWidth - 10) {
                        globalTooltip.style.left = (window.innerWidth - tooltipRect.width - 10) + 'px';
                    }
                    if (tooltipRect.top < 10) {
                        globalTooltip.style.top = (rect.bottom + 10) + 'px'; // Position below instead
                    }
                }, 200); // 200ms delay before showing tooltip
            }
        }
    });

    document.addEventListener('mouseout', function(e) {
        const leavingHelpIcon = e.target.closest('.help-icon');
        const leavingTooltip = e.target.closest('#global-tooltip');
        const enteringHelpIcon = e.relatedTarget && e.relatedTarget.closest('.help-icon');
        const enteringTooltip = e.relatedTarget && e.relatedTarget.closest('#global-tooltip');

        // If we're leaving either the help icon or the tooltip and not entering the other
        if ((leavingHelpIcon || leavingTooltip) && !enteringHelpIcon && !enteringTooltip) {
            // Set a short timeout before hiding the tooltip to allow for small gaps in mouse movement
            tooltipHideTimeout = setTimeout(() => {
                globalTooltip.style.display = 'none';
                isHoveringHelp = false;
            }, 100);
        }
    });

    // Function to validate deck link
    function validateDeckLink(url) {
        const validDomains = ['swustats.net', 'swudb.com', 'sw-unlimited-db.com', 'melee.gg'];
        const joinButtons = document.querySelectorAll('.join-btn');

        if (!url || url.trim() === '') {
            // Add help icon with tooltip containing deck site links
            deckFeedback.innerHTML = 'Please select or enter a deck <span class="help-icon">?</span>';
            deckFeedback.className = 'deck-feedback deck-invalid';
            deckFeedback.style.display = 'block';
            createGameButton.disabled = true;

            // Disable all join buttons
            joinButtons.forEach(btn => {
                btn.disabled = true;
                btn.classList.add('disabled');
            });

            return false;
        }

        // Check if the input is JSON (starts with '{')
        if (url.trim().startsWith('{')) {
            try {
                // Try to parse the JSON to ensure it's valid
                const deckData = JSON.parse(url.trim());

                // Check if the deck has the required properties
                if (!deckData.leader || !deckData.base || !deckData.deck) {
                    deckFeedback.textContent = 'Invalid JSON deck data. Required properties: leader, base, and deck.';
                    deckFeedback.className = 'deck-feedback deck-invalid';
                    deckFeedback.style.display = 'block';
                    createGameButton.disabled = true;

                    // Disable all join buttons
                    joinButtons.forEach(btn => {
                        btn.disabled = true;
                        btn.classList.add('disabled');
                    });

                    return false;
                }

                // Additional validation for required structure
                if (!deckData.leader.id || !deckData.base.id || !Array.isArray(deckData.deck) || deckData.deck.length === 0) {
                    deckFeedback.textContent = 'Invalid JSON deck structure. Check leader, base, and deck properties.';
                    deckFeedback.className = 'deck-feedback deck-invalid';
                    deckFeedback.style.display = 'block';
                    createGameButton.disabled = true;

                    // Disable all join buttons
                    joinButtons.forEach(btn => {
                        btn.disabled = true;
                        btn.classList.add('disabled');
                    });

                    return false;
                }

                // Verify deck is an array with at least some cards
                let validDeck = true;
                for (const card of deckData.deck) {
                    if (!card.id || !card.count) {
                        validDeck = false;
                        break;
                    }
                }

                if (!validDeck) {
                    deckFeedback.textContent = 'Invalid cards in deck. Each card needs id and count properties.';
                    deckFeedback.className = 'deck-feedback deck-invalid';
                    deckFeedback.style.display = 'block';
                    createGameButton.disabled = true;

                    // Disable all join buttons
                    joinButtons.forEach(btn => {
                        btn.disabled = true;
                        btn.classList.add('disabled');
                    });

                    return false;
                }

                // If we made it here, the JSON is valid
                deckFeedback.textContent = 'Valid JSON deck data accepted!';
                deckFeedback.className = 'deck-feedback deck-valid';
                deckFeedback.style.display = 'block';
                createGameButton.disabled = false;

                // Enable all join buttons
                joinButtons.forEach(btn => {
                    btn.disabled = false;
                    btn.classList.remove('disabled');
                });

                return true;
            } catch (e) {
                // JSON parsing failed
                deckFeedback.textContent = 'Invalid JSON format. ' + e.message;
                deckFeedback.className = 'deck-feedback deck-invalid';
                deckFeedback.style.display = 'block';
                createGameButton.disabled = true;

                // Disable all join buttons
                joinButtons.forEach(btn => {
                    btn.disabled = true;
                    btn.classList.add('disabled');
                });

                return false;
            }
        }

        // If not JSON, check if it's a valid deck URL
        let isValid = false;
        for (const domain of validDomains) {
            if (url.includes(domain)) {
                isValid = true;
                break;
            }
        }

        if (isValid) {
            deckFeedback.textContent = 'Valid deck selected!';
            deckFeedback.className = 'deck-feedback deck-valid';
            deckFeedback.style.display = 'block';
            createGameButton.disabled = false;

            // Enable all join buttons
            joinButtons.forEach(btn => {
                btn.disabled = false;
                btn.classList.remove('disabled');
            });
        } else {
            // Also add help icon to the error message (without embedded tooltip)
            deckFeedback.innerHTML = 'Please enter a valid deck URL or JSON data <span class="help-icon">?</span>';
            deckFeedback.className = 'deck-feedback deck-invalid';
            deckFeedback.style.display = 'block';
            createGameButton.disabled = true;

            // Disable all join buttons
            joinButtons.forEach(btn => {
                btn.disabled = true;
                btn.classList.add('disabled');
            });
        }

        return isValid;
    }

    // Collapsible sections
    const collapsibleHeaders = document.querySelectorAll('.collapsible-header');
    collapsibleHeaders.forEach(header => {
        header.addEventListener('click', () => {
            const content = header.nextElementSibling;
            const icon = header.querySelector('.rotate-icon');

            content.classList.toggle('expanded');
            icon.classList.toggle('rotated');
        });
    });

    // Modal functionality for create game
    const modal = document.getElementById('createGameModal');
    const openModalBtn = document.getElementById('openCreateGameModal');
    const closeModalBtn = document.getElementById('closeCreateGameModal');
    const cancelBtn = document.getElementById('cancelCreateGame');
    const formatSelect = document.getElementById('format');
    const visibilitySelect = document.getElementById('visibility');
    const summaryElement = document.getElementById('gameSettingsSummary');

    // Function to update the game settings summary
    function updateGameSettingsSummary() {
        const formatText = formatSelect.options[formatSelect.selectedIndex].text;
        const visibilityText = visibilitySelect.options[visibilitySelect.selectedIndex].text;
        summaryElement.textContent = `Create: ${formatText}, ${visibilityText}`;
    }

    // Open modal
    openModalBtn.addEventListener('click', () => {
        modal.style.display = 'block';
    });

    // Close modal with X button
    closeModalBtn.addEventListener('click', () => {
        modal.style.display = 'none';
    });

    // Close modal with Cancel button
    cancelBtn.addEventListener('click', () => {
        modal.style.display = 'none';
    });

    // Close modal with Escape key
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && modal.style.display === 'block') {
            modal.style.display = 'none';
        }
    });

    // Update summary when format or visibility changes
    formatSelect.addEventListener('change', updateGameSettingsSummary);
    visibilitySelect.addEventListener('change', updateGameSettingsSummary);

    // Initialize the summary with default values
    updateGameSettingsSummary();

    // Handle form submission (existing validation will work normally)
    document.getElementById('createGameForm').addEventListener('submit', () => {
        modal.style.display = 'none';
    });

    // Close modal if clicked outside content
    modal.addEventListener('click', (e) => {
        if (e.target === modal) {
            modal.style.display = 'none';
        }
    });

    // Quick create game button functionality
    const quickCreateGameBtn = document.getElementById('quickCreateGame');
    if (quickCreateGameBtn) {
        quickCreateGameBtn.addEventListener('click', () => {
            // Check if the deck is valid before submitting
            if (!createGameButton.disabled) {
                // Submit the form directly
                document.getElementById('createGameForm').submit();
            } else {
                // If no valid deck is selected, show a visual indication
                deckFeedback.innerHTML = 'Please select a valid deck before creating a game <span class="help-icon">?</span>';
                deckFeedback.className = 'deck-feedback deck-invalid';
                deckFeedback.style.display = 'block';

                // Scroll to the deck feedback if it's not in view
                deckFeedback.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        });
    }

    // Initialize the quickCreateGame button with same disabled state as modal button
    if (quickCreateGameBtn) {
        quickCreateGameBtn.disabled = createGameButton.disabled;

        // Update quick create button disabled state when deck validation changes
        const originalValidateDeckLink = validateDeckLink;
        validateDeckLink = function(url) {
            const result = originalValidateDeckLink(url);
            if (quickCreateGameBtn) {
                quickCreateGameBtn.disabled = createGameButton.disabled;
            }
            return result;
        };
    }

    // Connect saveFavoriteDeck checkbox to the hidden favoriteDeck input
    const saveFavoriteDeckCheckbox = document.getElementById('saveFavoriteDeck');
    if (saveFavoriteDeckCheckbox && favoriteDeckHidden) {
        saveFavoriteDeckCheckbox.addEventListener('change', function() {
            favoriteDeckHidden.value = this.checked ? '1' : '0';
        });
    }

    // Make validateDeckLink globally available
    window.validateDeckLink = validateDeckLink;
});

// Function to load games from API
function loadOpenGames() {
    document.getElementById('gameListLoading').style.display = 'block';
    document.getElementById('gameListContent').innerHTML = '';
    fetch('APIs/GetOpenGames.php?forCurrentPlayer=true')
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            updateGamesTabTotal(data.totalGames); // <-- update tab label
            displayOpenGames(data);
        })
        .catch(error => {
            console.error('Error fetching games:', error);
            document.getElementById('gameListContent').innerHTML =
                '<p class="error-message">Error loading games. Please try again later.</p>';
            document.getElementById('gameListLoading').style.display = 'none';
        });
}

// Function to display games by format
function displayOpenGames(data) {
    const gameListContent = document.getElementById('gameListContent');
    const formatFilter = document.getElementById('formatFilter').value;
    document.getElementById('gameListLoading').style.display = 'none';

    // Check if there's a valid deck to determine if buttons should be disabled
    const deckLinkInput = document.getElementById('deckLink');
    const isValidDeck = deckLinkInput && deckLinkInput.value && validateDeckLink(deckLinkInput.value);

    // Group games by format for better organization
    const gamesByFormat = {};

    if (!data.openGames || data.openGames.length === 0) {
        gameListContent.innerHTML = '<p>No open games available. Create a new game to get started!</p><BR>';
        return;
    }

    // Group games by format
    data.openGames.forEach(game => {
        if (!gamesByFormat[game.format]) {
            gamesByFormat[game.format] = [];
        }
        gamesByFormat[game.format].push(game);
    });

    // Clear existing content
    gameListContent.innerHTML = '';

    // Display games grouped by format
    Object.keys(gamesByFormat).forEach(format => {
        // Skip if format doesn't match filter (unless "all" is selected)
        if (formatFilter !== 'all' && format !== formatFilter) {
            return;
        }

        const formatGames = gamesByFormat[format];
        const formatSection = document.createElement('div');
        formatSection.className = 'format-section';

        // Only show format header when viewing all formats
        if (formatFilter === 'all') {
            const formatHeader = document.createElement('h4');
            formatHeader.textContent = formatGames[0].formatName || format;
            formatSection.appendChild(formatHeader);
        }

        // Create games list for this format
        formatGames.forEach(game => {
            const gameItem = document.createElement('div');
            gameItem.className = 'game-item';

            // Game info with images
            const gameInfo = document.createElement('div');
            gameInfo.className = 'game-info';

            // Create card display div
            const cardDisplay = document.createElement('div');
            cardDisplay.className = 'card-display';

            // Leader card
            if (game.p1Hero) {
                const leaderImg = document.createElement('img');
                leaderImg.src = `./WebpImages2/${game.p1Hero}.webp`;
                leaderImg.alt = 'Leader';
                leaderImg.className = 'card-image';

                // Add error handler for image loading
                leaderImg.onerror = function() {
                    leaderImg.src = './Images/card-back.webp'; // Fallback image if leader image not found
                    leaderImg.alt = 'Leader Card';
                };

                cardDisplay.appendChild(leaderImg);
            }

            // Base card
            if (game.p1Base) {
                const baseImg = document.createElement('img');
                baseImg.src = `./WebpImages2/${game.p1Base}.webp`;
                baseImg.alt = 'Base';
                baseImg.className = 'card-image';

                // Add error handler for image loading
                baseImg.onerror = function() {
                    baseImg.src = './Images/card-back.webp'; // Fallback image if base image not found
                    baseImg.alt = 'Base Card';
                };

                cardDisplay.appendChild(baseImg);
            }

            // Game description/name
            const gameName = document.createElement('span');
            gameName.className = 'game-name';
            // Use a friendly name if available, otherwise use Game #ID
            gameName.textContent = game.description && game.description !== game.gameName ?
                                  game.description :
                                  `Game #${game.gameName}`;

            gameInfo.appendChild(cardDisplay);
            gameInfo.appendChild(gameName);

            // Join button
            const joinButton = document.createElement('button');
            joinButton.textContent = 'Join';
            joinButton.className = 'join-btn';

            // Set initial disabled state based on deck validity
            if (!isValidDeck) {
                joinButton.disabled = true;
                joinButton.classList.add('disabled');
            }

            joinButton.onclick = function() {
                // Check if deck is selected first
                const deckLink = document.getElementById('deckLink').value;
                if (!deckLink || deckLink.trim() === '') {
                    const deckFeedback = document.getElementById('deckFeedback');
                    deckFeedback.textContent = 'Please select a deck before joining a game';
                    deckFeedback.className = 'deck-feedback deck-invalid';
                    deckFeedback.style.display = 'block';
                    return;
                }

                // Join the game directly via JoinGameInput.php instead of going to JoinGame.php
                const fabdb = document.getElementById('fabdb');
                const saveDeck = document.getElementById('saveFavoriteDeck');

                // Create a form element to submit the data
                const form = document.createElement('form');
                form.method = 'GET';
                form.action = `${window.location.origin}/Arena/JoinGameInput.php`;

                // Add game name
                const gameNameInput = document.createElement('input');
                gameNameInput.type = 'hidden';
                gameNameInput.name = 'gameName';
                gameNameInput.value = game.gameName;
                form.appendChild(gameNameInput);

                // Add player ID (hardcoded to 2 for now, as that's typically the joiner)
                const playerIDInput = document.createElement('input');
                playerIDInput.type = 'hidden';
                playerIDInput.name = 'playerID';
                playerIDInput.value = '2';
                form.appendChild(playerIDInput);

                // Add the deck link
                const fabdbInput = document.createElement('input');
                fabdbInput.type = 'hidden';
                fabdbInput.name = 'fabdb';
                fabdbInput.value = fabdb.value;
                form.appendChild(fabdbInput);

                // Add save to favorites checkbox if it exists
                if (saveDeck && saveDeck.checked) {
                    const favoriteDeckInput = document.createElement('input');
                    favoriteDeckInput.type = 'hidden';
                    favoriteDeckInput.name = 'favoriteDeck';
                    favoriteDeckInput.value = 'on';
                    form.appendChild(favoriteDeckInput);
                }

                // Add form to the document, submit it, and remove it
                document.body.appendChild(form);
                form.submit();
                document.body.removeChild(form);
            };

            gameItem.appendChild(gameInfo);
            gameItem.appendChild(joinButton);
            formatSection.appendChild(gameItem);
        });

        gameListContent.appendChild(formatSection);
    });

    // Show message if no games match the filter
    if (gameListContent.children.length === 0) {
        gameListContent.innerHTML = '<p>No games found matching the selected format.</p>';
    }
}

// Function to load spectate games from API
function loadSpectateGames() {
    document.getElementById('spectateListLoading').style.display = 'block';
    document.getElementById('spectateListContent').innerHTML = '';

    fetch('APIs/GetSpectateGames.php')
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            displaySpectateGames(data);
        })
        .catch(error => {
            console.error('Error fetching spectate games:', error);
            document.getElementById('spectateListContent').innerHTML =
                '<p class="error-message">Error loading games. Please try again later.</p>';
            document.getElementById('spectateListLoading').style.display = 'none';
        });
}

// Function to display spectate games by format
function displaySpectateGames(data) {
    const spectateListContent = document.getElementById('spectateListContent');
    const formatFilter = document.getElementById('spectateFormatFilter').value;
    document.getElementById('spectateListLoading').style.display = 'none';

    // Group games by format for better organization
    const gamesByFormat = {};

    if (!data.gamesInProgress || data.gamesInProgress.length === 0) {
        spectateListContent.innerHTML = '<p>No games currently available to spectate.</p>';
        return;
    }

    // Group games by format
    data.gamesInProgress.forEach(game => {
        if (!gamesByFormat[game.format]) {
            gamesByFormat[game.format] = [];
        }
        gamesByFormat[game.format].push(game);
    });

    // Clear existing content
    spectateListContent.innerHTML = '';

    // Display games grouped by format
    Object.keys(gamesByFormat).forEach(format => {
        // Skip if format doesn't match filter (unless "all" is selected)
        if (formatFilter !== 'all' && format !== formatFilter) {
            return;
        }

        const formatGames = gamesByFormat[format];
        const formatSection = document.createElement('div');
        formatSection.className = 'format-section';

        // Only show format header when viewing all formats
        if (formatFilter === 'all') {
            const formatHeader = document.createElement('h4');
            formatHeader.textContent = formatGames[0].formatName || format;
            formatSection.appendChild(formatHeader);
        }

        // Create games list for this format
        formatGames.forEach(game => {
            const gameItem = document.createElement('div');
            gameItem.className = 'game-item';

            // Game info with images
            const gameInfo = document.createElement('div');
            gameInfo.className = 'game-info';

            // Create card display div for Player 1
            const p1CardDisplay = document.createElement('div');
            p1CardDisplay.className = 'card-display';

            // Player 1 Leader card
            if (game.p1Hero) {
                const leaderImg = document.createElement('img');
                leaderImg.src = `./WebpImages2/${game.p1Hero}.webp`;
                leaderImg.alt = 'Leader 1';
                leaderImg.className = 'card-image';

                // Add error handler for image loading
                leaderImg.onerror = function() {
                    leaderImg.src = './Images/card-back.webp'; // Fallback image
                    leaderImg.alt = 'Leader Card';
                };

                p1CardDisplay.appendChild(leaderImg);
            }

            // Player 1 Base card
            if (game.p1Base) {
                const baseImg = document.createElement('img');
                baseImg.src = `./WebpImages2/${game.p1Base}.webp`;
                baseImg.alt = 'Base 1';
                baseImg.className = 'card-image';

                // Add error handler for image loading
                baseImg.onerror = function() {
                    baseImg.src = './Images/card-back.webp'; // Fallback image
                    baseImg.alt = 'Base Card';
                };

                p1CardDisplay.appendChild(baseImg);
            }

            // Create VS text
            const vsText = document.createElement('span');
            vsText.textContent = ' vs ';
            vsText.className = 'vs-text';
            vsText.style.margin = '0 10px';

            // Create card display div for Player 2
            const p2CardDisplay = document.createElement('div');
            p2CardDisplay.className = 'card-display';

            // Player 2 Leader card
            if (game.p2Hero) {
                const leaderImg = document.createElement('img');
                leaderImg.src = `./WebpImages2/${game.p2Hero}.webp`;
                leaderImg.alt = 'Leader 2';
                leaderImg.className = 'card-image';

                // Add error handler for image loading
                leaderImg.onerror = function() {
                    leaderImg.src = './Images/card-back.webp'; // Fallback image
                    leaderImg.alt = 'Leader Card';
                };

                p2CardDisplay.appendChild(leaderImg);
            }

            // Player 2 Base card
            if (game.p2Base) {
                const baseImg = document.createElement('img');
                baseImg.src = `./WebpImages2/${game.p2Base}.webp`;
                baseImg.alt = 'Base 2';
                baseImg.className = 'card-image';

                // Add error handler for image loading
                baseImg.onerror = function() {
                    baseImg.src = './Images/card-back.webp'; // Fallback image
                    baseImg.alt = 'Base Card';
                };

                p2CardDisplay.appendChild(baseImg);
            }

            // Assemble all the info components
            const matchupDiv = document.createElement('div');
            matchupDiv.className = 'matchup-display';
            matchupDiv.style.display = 'flex';
            matchupDiv.style.alignItems = 'center';
            matchupDiv.style.marginTop = '5px';
            matchupDiv.appendChild(p1CardDisplay);
            matchupDiv.appendChild(vsText);
            matchupDiv.appendChild(p2CardDisplay);

            gameInfo.appendChild(matchupDiv);

            // Spectate button
            const spectateButton = document.createElement('button');
            spectateButton.textContent = 'Spectate';
            spectateButton.className = 'spectate-btn';
            spectateButton.onclick = function() {
                window.location.href = `${window.location.origin}/Arena/NextTurn4.php?gameName=${game.gameName}&playerID=3`;
            };

            gameItem.appendChild(gameInfo);
            gameItem.appendChild(spectateButton);
            formatSection.appendChild(gameItem);
        });

        spectateListContent.appendChild(formatSection);
    });

    // Show message if no games match the filter
    if (spectateListContent.children.length === 0) {
        spectateListContent.innerHTML = '<p>No games found matching the selected format.</p>';
    }
}

// Set up spectate refresh button and format filter events
document.addEventListener('DOMContentLoaded', function() {
    // Initial load of open games
    loadOpenGames();

    // Set up game refresh button
    const refreshGameButton = document.getElementById('refreshGameList');
    if (refreshGameButton) {
        refreshGameButton.addEventListener('click', loadOpenGames);
    }

    // Set up game format filter
    const formatFilterDropdown = document.getElementById('formatFilter');
    formatFilterDropdown.addEventListener('change', function() {
        // Reload games with new filter
        loadOpenGames();
    });

    // Initial load of spectate games
    loadSpectateGames();

    // Set up spectate refresh button
    const refreshSpectateButton = document.getElementById('refreshSpectateList');
    if (refreshSpectateButton) {
        refreshSpectateButton.addEventListener('click', loadSpectateGames);
    }

    // Set up spectate format filter
    const spectateFormatFilterDropdown = document.getElementById('spectateFormatFilter');
    spectateFormatFilterDropdown.addEventListener('change', function() {
        // Reload spectate games with new filter
        loadSpectateGames();
    });
});

<?php if ($swuStatsLinked) { ?>
// SWU Stats deck loading logic using plain JavaScript and AJAX
(function() {
  document.addEventListener('DOMContentLoaded', function() {
    var deckLoadingContainer = document.getElementById('deckLoadingContainer');
    var deckDropdownContainer = document.getElementById('deckDropdownContainer');
    var swuDecksDropdown = document.getElementById('swuDecks');
    var deckLinkInput = document.getElementById('deckLink');
    var fabdbHidden = document.getElementById('fabdb');
    // Access the saved favorite deck index from the PHP variable
    var savedIndex = typeof savedFavoriteDeckIndex !== 'undefined' ? savedFavoriteDeckIndex : -1;

    function populateDeckDropdown(decks) {
      var validDecks = decks.filter(function(deck) {
        return true;
      });

      validDecks.forEach(function(deck, index) {
        var option = document.createElement('option');
        // Build the deck link using the id from the API
        var deckId = deck.id || deck.deckId || deck.keyIndicator2 || '';
        var deckLink = '';
        if (deckId) {
          deckLink = 'https://www.swustats.net/TCGEngine/NextTurn.php?gameName=' + encodeURIComponent(deckId) + '&playerID=1&folderPath=SWUDeck';
        }
        option.value = deckLink;
        option.textContent = deck.name || deck.deckName || 'Unnamed Deck';
        swuDecksDropdown.appendChild(option);
      });

      // Display message if no valid decks after filtering
      if (validDecks.length === 0) {
        deckLoadingContainer.textContent = 'No valid decks found.';
        deckLoadingContainer.style.display = 'block';
        deckDropdownContainer.style.display = 'none';
      } else {
        // If we have a saved index, select that deck in the dropdown
        if (savedIndex >= 0 && savedIndex < validDecks.length) {
          // +1 because the first option is the "-- Select a deck --" placeholder
          swuDecksDropdown.selectedIndex = savedIndex + 1;

          // Trigger the change event to populate the deck link
          var event = new Event('change');
          swuDecksDropdown.dispatchEvent(event);
        }
      }
    }

    function handleDeckSelection() {
      var selectedValue = swuDecksDropdown.value;
      // Set the input and hidden field
      deckLinkInput.value = selectedValue;
      fabdbHidden.value = selectedValue;
      // Always use the same validation as typing
      if (typeof validateDeckLink === 'function') validateDeckLink(selectedValue);

      // Save the selected index as a user preference
      var selectedIndex = swuDecksDropdown.selectedIndex - 1; // -1 to account for the placeholder
      if (selectedValue && selectedIndex >= 0) {
        // Update the savedFavoriteDeckIndex variable to keep track of the current selection
        savedFavoriteDeckIndex = selectedIndex;

        // Save the selection to user settings via AJAX - use correct parameter names: piece and value
        var xhr = new XMLHttpRequest();
        xhr.open('GET', 'api/UpdateMyPlayerSetting.php?piece=<?= $SET_FavoriteDeckIndex ?>&value=' + selectedIndex + '&userid=<?= $_SESSION["userid"] ?>', true);
        xhr.send();
      }
    }

    var xhr = new XMLHttpRequest();
    xhr.open('GET', './Assets/SWUStats/get_user_decks.php', true);
    xhr.onreadystatechange = function() {
      if (xhr.readyState === 4) {
        if (xhr.status === 200) {
          try {
            var data = JSON.parse(xhr.responseText);
            if (data.decks && data.decks.length > 0) {
              populateDeckDropdown(data.decks);
              deckLoadingContainer.style.display = 'none';
              deckDropdownContainer.style.display = 'block';
            } else {
              deckLoadingContainer.textContent = 'No decks found.';
            }
          } catch (e) {
            deckLoadingContainer.textContent = 'Error loading decks.';
          }
        } else {
          deckLoadingContainer.textContent = 'Error loading decks.';
        }
      }
    };
    xhr.send();

    swuDecksDropdown.addEventListener('change', handleDeckSelection);
  });
})();
<?php } ?>
</script>

</body>
</html>
