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

// Now start HTML output after all PHP processing is done
include_once 'Header.php';
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <style>
    /* Styles for the new 3-column layout */
    .main-layout {
      display: grid;
      grid-template-columns: 1fr 1.2fr 1fr;
      gap: 20px;
      width: 100%;
      max-width: 1800px;
      margin: 0 auto;
    }

    .deck-column, .game-action-column, .news-column {
      display: flex;
      flex-direction: column;
      border-radius: 10px;
      overflow-y: auto;
    }

    /* Tabbed interface styles */
    .tabs {
      display: flex;
      margin-bottom: 0;
      border-bottom: 2px solid rgba(150, 130, 90, 0.7);
      position: relative;
      z-index: 10; /* Higher z-index to ensure it's above other elements */
    }

    .tab-button {
      background: rgba(70, 50, 20, 0.5);
      border: none;
      padding: 10px 20px;
      cursor: pointer;
      font-weight: 600;
      border-radius: 8px 8px 0 0;
      margin-right: 5px;
      transition: background-color 0.3s;
      position: relative;
      z-index: 10; /* Same z-index as tabs for consistency */
      pointer-events: auto; /* Explicitly enable pointer events */
    }

    .tab-button.active {
      background: rgba(120, 100, 60, 0.8);
      color: white;
    }

    .tab-content {
      display: none;
      padding: 20px;
      background: rgba(90, 70, 30, 0.7);
      border-radius: 0 0 10px 10px;
      position: relative;
      z-index: 9; /* Slightly lower than buttons but still above most content */
    }

    .tab-content.active {
      display: block !important;
    }

    /* Game list styling */
    .game-list {
      margin-top: 15px;
      max-height: 400px;
      overflow-y: auto;
    }

    .game-item {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 10px;
      border-bottom: 1px solid rgba(255,255,255,0.1);
    }

    .game-item:last-child {
      border-bottom: none;
    }

    .format-section {
      margin-bottom: 15px;
    }

    /* Deck selection feedback */
    .deck-feedback {
      margin-top: 10px;
      padding: 10px;
      border-radius: 5px;
      font-weight: bold;
      text-align: center;
      display: none;
      position: relative; /* Position relative for absolute positioning of children */
      overflow: visible !important; /* Force tooltip to overflow without scrollbars */
    }

    .deck-valid {
      background: rgba(0, 120, 0, 0.3);
      color: #aaffaa;
    }

    .deck-invalid {
      background: rgba(120, 0, 0, 0.3);
      color: #ffaaaa;
    }

    /* Disable buttons when no deck is selected */
    button:disabled {
      opacity: 0.6;
      cursor: not-allowed;
    }

    button:disabled:hover {
      transform: none;
    }

    .news-toggle {
      text-align: right;
      cursor: pointer;
      padding: 5px;
      font-size: 18px;
    }

    /* Collapsible sections */
    .collapsible-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      cursor: pointer;
      padding: 5px 10px;
    }

    .collapsible-content {
      max-height: 0;
      overflow: hidden;
      transition: max-height 0.3s ease-out;
    }

    .collapsible-content.expanded {
      max-height: 1000px;
    }

    .rotate-icon {
      transition: transform 0.3s;
      display: inline-block;
    }

    .rotate-icon.rotated {
      transform: rotate(180deg);
    }

    /* Fix for the home-header blocking tab interactions */
    .home-header {
      pointer-events: none; /* This makes the header ignore mouse events */
      z-index: -1; /* This ensures it stays below interactive elements */
    }

    /* Make specific elements within home-header clickable again if needed */
    .home-header a, 
    .home-header button,
    .nav-bar,
    .nav-bar * {
      pointer-events: auto; /* Re-enable click events for navigation elements */
    }

    /* Help icon tooltip styles */
    .help-icon {
      display: inline-block;
      width: 18px;
      height: 18px;
      background-color: rgba(150, 130, 90, 0.7);
      color: white;
      border-radius: 50%;
      text-align: center;
      line-height: 18px;
      font-size: 14px;
      margin-left: 6px;
      cursor: help;
      position: relative;
    }

    /* Move all tooltips to a fixed position relative to the viewport */
    #global-tooltip {
      display: none;
      position: fixed;
      z-index: 9999;
      width: 280px;
      background-color: rgba(40, 30, 20, 0.95);
      color: #fff;
      text-align: left;
      border-radius: 6px;
      padding: 10px;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.6);
    }

    .tooltip-title {
      font-weight: bold;
      margin-bottom: 12px;
      font-size: 14px;
      display: block;
      border-bottom: 1px solid rgba(255, 255, 255, 0.3);
      padding-bottom: 8px;
    }

    .tooltip-link {
      display: block;
      margin: 8px 0;
      padding: 5px 0;
      color: #bddbff;
      text-decoration: underline;
      border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }

    .tooltip-link:last-child {
      border-bottom: none;
      margin-bottom: 0;
    }
    </style>
</head>
<body>
<div class="core-wrapper">
  <div id="mainMenuError" class="error-popup-hidden"></div>
  
  <!-- Add a single global tooltip that will be positioned dynamically -->
  <div id="global-tooltip"></div>
  
  <div class="main-layout">
    <!-- COLUMN 1: DECK SELECTION -->
    <div class="deck-column">
      <div class="container bg-yellow">
        <h2>Select Your Deck</h2>
        
        <!-- SWU Stats integration -->
        <?php
        $favoriteDecks = [];
        $swuStatsLinked = isset($userData) && $userData["swustatsAccessToken"] != null;

        if (isset($_SESSION["userid"])) {
          if ($swuStatsLinked) {
            echo "<div class='swustats-connected'>SWU Stats account connected</div>";
            // Future: Add deck management via SWU Stats API here
            echo "<div class='deck-list'>
              <p>Your decks will appear here.</p>
              <!-- Future: Dynamic deck list will load here -->
            </div>";
          } else {
            $favoriteDecks = LoadFavoriteDecks($_SESSION["userid"]);
            if (count($favoriteDecks) > 0) {
              $selIndex = -1;
              if (isset($settingArray[$SET_FavoriteDeckIndex])) $selIndex = $settingArray[$SET_FavoriteDeckIndex];
              echo ("<div class='SelectDeckInput'><label for='favoriteDecks'>Favorite Decks</label>");
              echo ("<select name='favoriteDecks' id='favoriteDecks'>");
              echo ("<option value=''>-- Select a deck --</option>");
              for ($i = 0; $i < count($favoriteDecks); $i += 4) {
          echo ("<option value='" . $i . "<fav>" . $favoriteDecks[$i] . "'" . ($i == $selIndex ? " selected " : "") . ">" . $favoriteDecks[$i + 1] . "</option>");
              }
              echo ("</select></div>");
            }
            echo "<p>Link your <a href='https://swustats.net/' target='_blank'>SWU Stats</a> account in your <a href='./ProfilePage.php' target='_blank'>profile</a> to manage your decks in one place!</p>";
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
        <div id="deckFeedback" class="deck-feedback"></div>
      </div>
    </div>
    
    <!-- COLUMN 2: GAME ACTIONS -->
    <div class="game-action-column">
      <div class="tabs">
        <button class="tab-button active" id="createTabBtn" onclick="switchTabDirect('createTab')">Create Game</button>
        <button class="tab-button" id="joinTabBtn" onclick="switchTabDirect('joinTab')">Join Game</button>
        <button class="tab-button" id="spectateTabBtn" onclick="switchTabDirect('spectateTab')">Spectate</button>
      </div>
      
      <!-- CREATE GAME TAB -->
      <div id="createTab" class="tab-content active">
        <h3><?php echo ($createNewGameText); ?></h3>
        
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
          echo ("<label for='format' class='SelectDeckInput'>Format</label>");
          echo ("<select name='format' id='format'>");
          echo ("<option value='$standardFormatCasual' " . ($defaultFormat == FormatCode($standardFormatCasual) ? " selected" : "") . ">Premier Casual</option>");
          if($canSeeQueue) {
            echo ("<option value='$standardFormat' " . ($defaultFormat == FormatCode($standardFormat) ? " selected" : "") . ">Premier (Best of 3)</option>");
            //Cantina Brawl Format; Update this to rotate formats
            $funFormatBackendName = Formats::$PadawanFormat;
            $funFormatDisplayName = FormatDisplayName($funFormatBackendName);
            echo ("<option value='$funFormatBackendName'" . ($defaultFormat == FormatCode($funFormatBackendName) ? " selected" : "") . ">Cantina Brawl ($funFormatDisplayName)</option>");
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
          
          <div style='text-align:center;'>
            <input type="submit" id="createGameButton" class="create-game-button" value="<?php echo ($createGameText); ?>" disabled>
          </div>
        </form>
      </div>
      
      <!-- JOIN GAME TAB -->
      <div id="joinTab" class="tab-content">
        <h3>Join a Game</h3>
        
        <div class="game-list-filters">
          <label for="formatFilter">Filter by format:</label>
          <select id="formatFilter">
            <option value="all">All Formats</option>
            <option value="premier">Premier Casual</option>
            <option value="premier-bo3">Premier (Best of 3)</option>
            <option value="cantina">Cantina Brawl</option>
            <option value="open">Open Format</option>
          </select>
        </div>
        
        <div id="gameList" class="game-list">
          <p>Game listing functionality will be added via REST API.</p>
          <p class="api-note"><i>Note: This section is being restructured to use a more efficient REST-based approach.</i></p>
        </div>
      </div>
      
      <!-- SPECTATE TAB -->
      <div id="spectateTab" class="tab-content">
        <h3>Spectate Games</h3>
        <p>Watch ongoing games without participating.</p>
        
        <div id="spectateList" class="game-list">
          <p>Spectate functionality will be added via REST API.</p>
          <p class="api-note"><i>Note: This section is being restructured to use a more efficient REST-based approach.</i></p>
        </div>
      </div>
    </div>
    
    <!-- COLUMN 3: NEWS & INFO -->
    <div class="news-column">
      <div class="container bg-yellow">
        <div class="collapsible-header">
          <h2>What is Petranaki?</h2>
          <span class="rotate-icon">▼</span>
        </div>
        <div class="collapsible-content expanded">
          <p><b>Petranaki is an Open-Source, Fan-Made Platform</b></p>
          <p>This is a free educational tool for researching decks and strategies for in-person play. It does not include automated tournaments or rankings. All features are accessible without payment and are not intended for commercial use.</p>
        </div>
      </div>

      <div class="container bg-yellow" style="margin-top: 20px;">
        <div class="collapsible-header">
          <h2>News</h2>
          <span class="rotate-icon">▼</span>
        </div>
        <div class="collapsible-content expanded">
          <div style="position: relative;">
            <div style='vertical-align:middle; text-align: start;'>
              <img src="./Images/jtl-han-solo.webp" style="width: 100%; height: 200px; object-fit: cover; border-radius: 10px;">
              <h3 style="margin: 15px 0; display: block;">The Classic Force Awakens</h3>
              <p>All Set 1-4 cards are now implemeneted.</p>
              <p>Petranaki is the original version of Karabast, and it will continue to be available for those who prefer to stick with the classic experience. The project will keep evolving with updates designed to enhance gameplay, offering a fast and easy way to enjoy and sharpen your skills with your favorite decks.</p>
              <p>Join our <a href="https://discord.gg/ep9fj8Vj3F" target="_blank" rel="noopener noreferrer">new Discord server</a> to stay up-to-date, get the latest news, and share your feedback. May the Force guide your cards!</p>
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

<script>
// Tab switching functionality (without alerts)
function switchTabDirect(tabId) {
    console.log('Switching to tab: ' + tabId);
    
    // Remove active class from all tabs
    document.querySelectorAll('.tab-content').forEach(tab => {
        tab.classList.remove('active');
    });
    
    // Add active class to the selected tab
    document.getElementById(tabId).classList.add('active');
    
    // Update button styles
    document.getElementById('createTabBtn').classList.remove('active');
    document.getElementById('joinTabBtn').classList.remove('active');
    document.getElementById('spectateTabBtn').classList.remove('active');
    document.getElementById(tabId + 'Btn').classList.add('active');
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
    
    // Initialize with current value
    if(deckLinkInput && fabdbHidden) {
        fabdbHidden.value = deckLinkInput.value;
        validateDeckLink(deckLinkInput.value);
        
        // Update on change
        deckLinkInput.addEventListener('input', function() {
            fabdbHidden.value = deckLinkInput.value;
            validateDeckLink(deckLinkInput.value);
        });
    }
    
    // Connect favorite decks dropdown to the form
    if(favoriteDecksSelect && favoriteDeckHidden) {
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
            }
        });
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
                // Get tooltip content
                const tooltipContent = `
                    <span class="tooltip-title">Copy deck links or JSON from:</span>
                    <a href="https://swustats.net/" target="_blank" class="tooltip-link">SWU Stats</a>
                    <a href="https://www.swudb.com/" target="_blank" class="tooltip-link">SWUDB</a>
                    <a href="https://sw-unlimited-db.com/" target="_blank" class="tooltip-link">SW-Unlimited-DB</a>
                `;
                
                // Position tooltip at fixed position relative to the icon
                const rect = helpIcon.getBoundingClientRect();
                globalTooltip.innerHTML = tooltipContent;
                globalTooltip.style.left = (rect.left - 120) + 'px'; // Center tooltip over icon
                globalTooltip.style.top = (rect.top - 130) + 'px'; // Position above the icon
                globalTooltip.style.display = 'block';
                
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
            }
        }
    });
    
    document.addEventListener('mouseout', function(e) {
        const leavingHelpIcon = e.target.closest('.help-icon');
        const leavingTooltip = e.target.closest('#global-tooltip');
        const enteringHelpIcon = e.relatedTarget && e.relatedTarget.closest('.help-icon');
        const enteringTooltip = e.relatedTarget && e.relatedTarget.closest('#global-tooltip');
        
        // If we're leaving either the help icon or tooltip and not entering the other
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
        const validDomains = ['swustats.net', 'swudb.com', 'sw-unlimited-db.com'];
        
        if (!url || url.trim() === '') {
            // Add help icon with tooltip containing deck site links
            deckFeedback.innerHTML = 'Please select or enter a deck <span class="help-icon">?</span>';
            deckFeedback.className = 'deck-feedback deck-invalid';
            deckFeedback.style.display = 'block';
            createGameButton.disabled = true;
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
                    return false;
                }
                
                // Additional validation for required structure
                if (!deckData.leader.id || !deckData.base.id || !Array.isArray(deckData.deck) || deckData.deck.length === 0) {
                    deckFeedback.textContent = 'Invalid JSON deck structure. Check leader, base, and deck properties.';
                    deckFeedback.className = 'deck-feedback deck-invalid';
                    deckFeedback.style.display = 'block';
                    createGameButton.disabled = true;
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
                    return false;
                }
                
                // If we made it here, the JSON is valid
                deckFeedback.textContent = 'Valid JSON deck data accepted!';
                deckFeedback.className = 'deck-feedback deck-valid';
                deckFeedback.style.display = 'block';
                createGameButton.disabled = false;
                return true;
            } catch (e) {
                // JSON parsing failed
                deckFeedback.textContent = 'Invalid JSON format. ' + e.message;
                deckFeedback.className = 'deck-feedback deck-invalid';
                deckFeedback.style.display = 'block';
                createGameButton.disabled = true;
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
        } else {
            // Also add help icon to the error message (without embedded tooltip)
            deckFeedback.innerHTML = 'Please enter a valid deck URL or JSON data <span class="help-icon">?</span>';
            deckFeedback.className = 'deck-feedback deck-invalid';
            deckFeedback.style.display = 'block';
            createGameButton.disabled = true;
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
});
</script>

<?php include_once 'Disclaimer.php'; ?>
</body>
</html>
