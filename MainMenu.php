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
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Petranaki</title>
    
    <style>
    html, body {
      margin: 0;
      padding: 0;
      font-family: 'Barlow', sans-serif;
      overflow-x: hidden;
      width: 100vw;
      max-width: 100vw;
    }
    
    /* Site container */
    .site-container {
      display: flex;
      flex-direction: column;
      min-height: 100vh;
    }
    
    /* Ensure header and menubar work together in mobile view */
    .header-wrapper {
      position: relative;
      z-index: 100;
      width: 60%;
      margin: 0;
      display: block;
    }
    @media (max-width: 991px) {
      .header-wrapper {
        width: 100%;
        margin: 0 auto;
        display: flex;
        justify-content: center;
      }
    }
    
    .nav-bar {
      /* Desktop: compact, top-right, auto width */
      position: absolute;
      right: 0;
      top: 10px;
      width: auto;
      margin-bottom: 0;
      z-index: 100;
      background: none;
      backdrop-filter: none;
    }
    
    /* Main content area */
    .content-wrapper {
      flex: 1;
      padding: 10px;
    }
    
    /* Styles for the 3-column layout */
    .main-layout {
      display: grid;
      grid-template-columns: 1fr 1.2fr 1fr;
      gap: 20px;
      width: 100vw;
      max-width: 100vw;
      margin: 0 auto;
      height: calc(100vh - 220px);
      overflow-x: hidden;
    }

    .deck-column, .game-action-column, .news-column {
      display: flex;
      flex-direction: column;
      border-radius: 10px;
      overflow-y: auto; /* Enable scrolling within columns on desktop */
      overflow-x: hidden;
      max-height: calc(100vh - 180px); /* Ensure columns don't exceed viewport, accounting for footer */
      margin-right: 10px;
      
      /* Custom scrollbar styling */
      scrollbar-width: thin; /* For Firefox */
      scrollbar-color: rgba(120, 100, 60, 0.8) rgba(70, 50, 20, 0.5); /* Thumb and track colors */

      /* For Webkit-based browsers (Chrome, Edge, Safari) */
      &::-webkit-scrollbar {
      width: 8px;
      }
      &::-webkit-scrollbar-track {
      background: rgba(70, 50, 20, 0.5);
      border-radius: 10px;
      }
      &::-webkit-scrollbar-thumb {
      background: rgba(120, 100, 60, 0.8);
      border-radius: 10px;
      }
      &::-webkit-scrollbar-thumb:hover {
      background: rgba(140, 120, 80, 0.9);
      }
      &::-webkit-scrollbar-button {
      display: none; /* Remove scrollbar arrows */
      }
    }
    
    /* Fix deck-column to fit content */
    .deck-column {
      min-height: auto !important;
      height: auto !important;
      align-self: flex-start;
    }
    
    /* Keep scrolling for other columns */
    .game-action-column, .news-column {
      overflow-y: auto;
    }

    /* Fix news-column height and scrolling */
    .news-column {
      height: auto;
      max-height: calc(100vh - 230px); /* Adjusted to account for header and padding */
    }
    
    /* Ensure container inside news column doesn't have its own scrollbar */
    .news-column .container {
      overflow: visible;
      height: auto;
      max-height: none;
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
      height: auto; /* Allow content to expand naturally */
      overflow: visible; /* Remove scrolling from tab content container */
    }

    .tab-content.active {
      display: block !important;
    }

    /* Game list styling */
    .game-list {
      margin-top: 15px;
      max-height: none; /* Remove fixed height to avoid nested scrollbars */
      overflow-y: visible; /* Remove scrollbars from inner elements */
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
    
    .card-display {
      display: flex;
      align-items: center;
      gap: 8px;
      margin-right: 10px;
    }
    
    .card-image {
      width: 56px;
      height: 40px;
      object-fit: cover;
      border-radius: 4px;
      box-shadow: 0 2px 4px rgba(0,0,0,0.3);
    }
    
    .game-info {
      display: flex;
      align-items: center;
      flex-grow: 1;
    }
    
    .join-btn {
      background-color: rgba(120, 100, 60, 0.8);
      color: white;
      border: none;
      border-radius: 5px;
      padding: 6px 12px;
      cursor: pointer;
      font-weight: bold;
      white-space: nowrap;
    }
    
    .join-btn:hover {
      background-color: rgba(140, 120, 80, 0.9);
    }

    .format-section {
      margin-bottom: 15px;
    }
    
    /* Format filter styling */
    .game-list-filters {
      display: flex;
      align-items: center;
      gap: 10px;
      margin-bottom: 15px;
    }
    
    /* Styled dropdown with filter icon */
    .filter-dropdown-wrapper {
      position: relative;
      flex-grow: 1;
    }
    
    .filter-icon {
      position: absolute;
      left: 10px;
      top: 50%;
      transform: translateY(-50%);
      color: rgba(255, 255, 255, 0.8);
      pointer-events: none;
      z-index: 1;
      margin-top:-4px;
    }
    
    /* Deck feedback styling */
    .deck-feedback {
      margin-top: 10px;
      padding: 8px 12px;
      border-radius: 5px;
      font-weight: 500;
      display: none;
    }
    
    .deck-valid {
      background-color: rgba(40, 150, 50, 0.3);
      color: #c0ffc0;
      border: 1px solid rgba(40, 150, 50, 0.5);
    }
    
    .deck-invalid {
      background-color: rgba(180, 60, 60, 0.3);
      color: #ffc0c0;
      border: 1px solid rgba(180, 60, 60, 0.5);
    }
    
    .styled-dropdown {
      appearance: none;
      -webkit-appearance: none;
      -moz-appearance: none;
      background-color: rgba(70, 50, 20, 0.6);
      color: white;
      padding: 10px 30px 10px 35px; /* Increased left padding for icon */
      border-radius: 5px;
      border: 1px solid rgba(150, 130, 90, 0.5);
      width: 100%;
      font-size: 0.9rem;
      cursor: pointer;
      background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='rgba(255, 255, 255, 0.8)' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E");
      background-repeat: no-repeat;
      background-position: right 10px center;
    }
    
    .styled-dropdown:focus {
      outline: none;
      border-color: rgba(180, 160, 120, 0.8);
      box-shadow: 0 0 0 2px rgba(180, 160, 120, 0.3);
    }
    
    .styled-dropdown:hover {
      background-color: rgba(90, 70, 40, 0.7);
    }
    
    /* Refresh button styling */
    .refresh-btn {
      background-color: rgba(120, 100, 60, 0.8);
      color: white;
      border: none;
      border-radius: 5px;
      width: 36px;
      height: 36px;
      padding: 8px;
      display: flex;
      align-items: center;
      margin-top: -9px;
    }

    /* Disable buttons when no deck is selected */
    button:disabled {
      opacity: 0.6;
      cursor: not-allowed;
      background-color: rgba(100, 90, 80, 0.5) !important;
    }

    button:disabled:hover {
      transform: none;
      background-color: rgba(100, 90, 80, 0.5) !important;
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

    /* Modal styles */
    .modal-overlay {
      display: none;
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background-color: rgba(0, 0, 0, 0.7);
      z-index: 1000;
      backdrop-filter: blur(2px);
    }

    .modal-content {
      position: fixed;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
      width: 90%;
      max-width: 600px;
      background: rgba(70, 50, 20, 0.95);
      border-radius: 10px;
      padding: 20px;
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.5);
      z-index: 1001;
    }

    .modal-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      border-bottom: 1px solid rgba(255, 255, 255, 0.2);
      padding-bottom: 10px;
      margin-bottom: 15px;
    }

    .modal-title {
      font-size: 1.5rem;
      margin: 0;
    }

    .modal-close {
      background: transparent;
      border: none;
      color: #fff;
      font-size: 1.5rem;
      cursor: pointer;
      transition: transform 0.2s;
    }

    .modal-close:hover {
      transform: scale(1.2);
    }

    .modal-footer {
      border-top: 1px solid rgba(255, 255, 255, 0.2);
      padding-top: 15px;
      margin-top: 15px;
      display: flex;
      justify-content: flex-end;
      gap: 10px;
    }

    .modal-btn {
      padding: 8px 16px;
      border-radius: 5px;
      border: none;
      cursor: pointer;
      font-weight: bold;
    }

    .modal-btn-primary {
      background: rgba(120, 100, 60, 0.8);
      color: white;
    }

    .modal-btn-secondary {
      background: rgba(70, 70, 70, 0.8);
      color: white;
    }

    /* Create game summary bar */
    .create-game-summary {
      display: flex;
      justify-content: space-between;
      align-items: center;
      background: rgba(70, 50, 20, 0.5);
      border-radius: 5px;
      padding: 10px 15px;
      margin-bottom: 15px;
    }

    .summary-text {
      flex-grow: 1;
    }

    .edit-icon {
      background: transparent;
      border: none;
      color: #fff;
      cursor: pointer;
      display: flex;
      align-items: center;
      gap: 5px;
      padding: 5px 10px;
      border-radius: 4px;
      transition: background-color 0.2s;
    }

    .edit-icon:hover {
      background: rgba(255, 255, 255, 0.1);
    }

    .edit-icon svg {
      width: 16px;
      height: 16px;
    }

    .create-btn {
      background: rgba(120, 100, 60, 0.8);
      color: white;
      border: none;
      border-radius: 5px;
      padding: 8px 16px;
      cursor: pointer;
      font-weight: bold;
      margin-right: 10px;
    }

    .create-btn:hover {
      background: rgba(140, 120, 80, 0.9);
    }

    .create-btn:disabled {
      opacity: 0.6;
      cursor: not-allowed;
    }

    .summary-actions {
      display: flex;
      align-items: center;
    }
    
    /* Mobile-responsive layout */
    @media (max-width: 991px) {
      html, body {
        overflow-x: hidden;
        width: 100vw;
        max-width: 100vw;
        background-attachment: fixed;
      }
      .main-layout {
        grid-template-columns: 1fr;
        grid-template-rows: auto auto auto;
        height: auto;
        overflow: visible;
        padding: 0 10px;
        width: 100%;
        max-width: 100%;
        box-sizing: border-box;
      }
      .deck-column, .game-action-column, .news-column {
        width: 100%;
        max-width: 100%;
        margin-bottom: 20px;
        overflow: visible !important;
        max-height: none !important;
        height: auto !important;
      }
      .container {
        width: 100%;
        max-width: 100%;
        box-sizing: border-box;
        padding-left: 8px;
        padding-right: 8px;
      }
      .header-wrapper {
        position: relative;
        width: auto;
        margin: 0 auto;
        display: flex;
        justify-content: center;
      }
      
      .nav-bar {
        position: relative !important;
        top: auto;
        width: 100% !important;
        margin-bottom: 15px;
        z-index: 20;
        background-color: rgba(20, 20, 20, 0.8);
        backdrop-filter: blur(5px);
      }
      
      /* Improve mobile menu appearance */
      .menu-toggle {
        position: absolute;
        top: 5px;
        right: 10px;
        z-index: 40;
      }
      
      /* Better spacing for mobile content */
      .content-wrapper {
        padding: 10px;
      }
      
      /* Ensure smooth scrolling on the entire page */
      html, body {
        height: auto;
        overflow: visible;
        overflow-x: hidden;
      }
      
      /* Adjust core wrapper for full page scroll */
      .core-wrapper {
        height: auto;
        overflow: visible;
      }
    }
    
    @media (max-width: 575px) {
      .game-item {
        flex-direction: column;
        align-items: flex-start;
      }
      
      .game-info {
        margin-bottom: 10px;
        width: 100%;
      }
      
      .join-btn, .spectate-btn {
        width: 100%;
      }
      
      .create-game-summary {
        flex-direction: column;
      }
      
      .summary-text {
        margin-bottom: 10px;
      }
      
      .summary-actions {
        width: 100%;
        justify-content: space-between;
      }
      
      .title p {
        font-size: 0.7rem;
        margin-top: 0;
      }
      
      .home-title {
        font-size: 1.5rem !important;
        margin-bottom: 0;
      }
      
      .content-wrapper {
        padding: 5px;
      }
    }
    </style>
</head>
<body>
<div class="site-container">
  <!-- Header & MenuBar wrapper to coordinate them better -->
  <div class="header-wrapper">
    <?php include_once 'Header.php'; ?>
    <!-- MenuBar is already included at the top of this file -->
  </div>
  
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
        </div>
      </div>
      
      <!-- COLUMN 2: GAME ACTIONS -->
      <div class="game-action-column">
        <div class="tabs">
          <button class="tab-button active" id="gamesTabBtn" onclick="switchTabDirect('gamesTab')">Games</button>
          <button class="tab-button" id="spectateTabBtn" onclick="switchTabDirect('spectateTab')">Spectate</button>
        </div>
        
        <!-- GAMES TAB (COMBINED JOIN & CREATE) -->
        <div id="gamesTab" class="tab-content active">
          <!-- Filters and refresh button are now part of the Games tab only -->
          <div class="game-list-filters">
            <div class="filter-dropdown-wrapper">
              <span class="filter-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon>
                </svg>
              </span>
              <select id="formatFilter" class="styled-dropdown">
                <option value="all">All Formats</option>
                <option value="premier">Premier Casual</option>
                <option value="premier-bo3">Premier (Best of 3)</option>
                <option value="cantina">Cantina Brawl</option>
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
          <div class="create-game-summary">
            <div class="summary-text">
              <span id="gameSettingsSummary">Create a new game: Premier Casual, <?php echo ($defaultVisibility == 1 ? "Public" : "Private"); ?></span>
            </div>
            <div class="summary-actions">
              <button class="create-btn" id="quickCreateGame">Create Game</button>
              <button class="edit-icon" id="openCreateGameModal">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2-2h14a2 2 0 0 0 2-2v-7"></path>
                  <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                </svg>
                Edit
              </button>
            </div>
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
                <option value="all">All Formats</option>
                <option value="premier">Premier Casual</option>
                <option value="premier-bo3">Premier (Best of 3)</option>
                <option value="cantina">Cantina Brawl</option>
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
  
  <?php include_once 'Disclaimer.php'; ?>
</div>

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

// Show total games in the Games tab button
function updateGamesTabTotal(totalGames) {
  var gamesTabBtn = document.getElementById('gamesTabBtn');
  if (gamesTabBtn) {
    // Remove any previous count
    gamesTabBtn.innerHTML = 'Games' + (typeof totalGames === 'number' ? ' <span style="font-size:0.95em; color:#ffd24d;">(' + totalGames + ')</span>' : '');
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
                // Mobile: show below icon, Desktop: show above
                if (window.innerWidth <= 768) {
                    globalTooltip.style.left = rect.left + 'px';
                    globalTooltip.style.top = (rect.bottom + 12) + 'px'; // 12px below icon
                } else {
                    globalTooltip.style.left = (rect.left - 120) + 'px'; // Center tooltip over icon
                    globalTooltip.style.top = (rect.top - 130) + 'px'; // Position above the icon
                }
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
    
    // Make validateDeckLink globally available
    window.validateDeckLink = validateDeckLink;
});

// Function to load games from API
function loadOpenGames() {
    document.getElementById('gameListLoading').style.display = 'block';
    document.getElementById('gameListContent').innerHTML = '';
    fetch('APIs/GetOpenGames.php')
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
            
            // Last update time
            const timeInfo = document.createElement('div');
            timeInfo.className = 'time-info';
            timeInfo.textContent = `Last update: ${game.secondsSinceLastUpdate}s ago`;
            
            // Add format label
            const formatLabel = document.createElement('div');
            formatLabel.className = 'format-label';
            formatLabel.textContent = game.formatName || game.format;
            
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
            gameInfo.appendChild(timeInfo);
            
            // Only show format label when not filtering by format
            if (formatFilter === 'all') {
                gameInfo.appendChild(formatLabel);
            }
            
            // Spectate button
            const spectateButton = document.createElement('button');
            spectateButton.textContent = 'Spectate';
            spectateButton.className = 'spectate-btn';
            spectateButton.onclick = function() {
                window.location.href = `${window.location.origin}/Arena/GameLobby.php?gameName=${game.gameName}&playerID=3`;
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

    function populateDeckDropdown(decks) {
      decks.forEach(function(deck) {
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
    }

    function handleDeckSelection() {
      var selectedValue = swuDecksDropdown.value;
      // Set the input and hidden field
      deckLinkInput.value = selectedValue;
      fabdbHidden.value = selectedValue;
      // Always use the same validation as typing
      if (typeof validateDeckLink === 'function') validateDeckLink(selectedValue);
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
