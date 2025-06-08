<?php
include_once '../MenuBar.php';

include_once '../includes/functions.inc.php';
include_once "../includes/dbh.inc.php";
include_once '../Libraries/CoreLibraries.php';
include_once '../AccountFiles/AccountDatabaseAPI.php';
include_once '../Database/ConnectionManager.php';

if (!isset($_SESSION["useruid"])) {
  echo ("Please login to view this page.");
  exit;
}
$useruid = $_SESSION["useruid"];
?>
<link rel="stylesheet" href="../css/petranaki250608.css">
<style>
  .onep-title {
    font-size: 1.5em;
    color: #fff;
    text-align: center;
    margin: 20px;
  }
</style>
<div style='padding:10px; width:80vw; max-width: 1600px; height: 80vh; margin: 10vh auto;
  background-color:rgba(74, 74, 74, 0.9); border: 2px solid #1a1a1a; border-radius: 5px; overflow-y: scroll;'>
  <h1 class="onep-title">Create One Player Testing Game</h1>
  <form id="createOnePlayerGameForm" action='../CreateGame.php'>
    <input type='hidden' id='onePlayerMode' name='onePlayerMode' value='1'>

    <div style="display: flex; flex-wrap: wrap; gap: 20px; justify-content: center;">
      <!-- PLAYER 1 DECK SELECTION -->
      <div class="container bg-yellow" style="flex: 1; min-width: 300px;">
        <h3>Player 1 Deck</h3>
        <div id="deckFeedbackP1" class="deck-feedback"></div>

        <?php
        $favoriteDecks = [];
        $userData = LoadUserData($_SESSION["useruid"]);
        $swuStatsLinked = isset($userData) && $userData["swustatsAccessToken"] != null;
        $settingArray = [];

        if (isset($_SESSION["userid"])) {
          $savedSettings = LoadSavedSettings($_SESSION["userid"]);
          for ($i = 0; $i < count($savedSettings); $i += 2) {
            $settingArray[$savedSettings[intval($i)]] = $savedSettings[intval($i) + 1];
          }

          if ($swuStatsLinked) {
            echo "<div id='deckLoadingContainerP1' class='swustats-connected'>";
            echo "<select id='swuDecksLoadingP1' name='swuDecksLoadingP1' disabled>";
            echo "<option>Loading decks...</option>";
            echo "</select>";
            echo "</div>";
            echo "<div id='deckDropdownContainerP1' style='display: none;'>
              <select id='swuDecksP1' name='swuDecksP1' style='margin-top: 15px; margin-bottom: 10px;'>
              <option value=''>-- Select a deck --</option>
              </select>
              </div>";
          } else {
            $favoriteDecks = LoadFavoriteDecks($_SESSION["userid"]);
            if (count($favoriteDecks) > 0) {
              echo ("<div class='SelectDeckInput'>");
              echo ("<select name='favoriteDecksP1' id='favoriteDecksP1'>");
              echo ("<option value=''>-- Select a deck --</option>");
              for ($i = 0; $i < count($favoriteDecks); $i += 4) {
                echo ("<option value='" . $i . "<fav>" . $favoriteDecks[$i] . "'>" . $favoriteDecks[$i + 1] . "</option>");
              }
              echo ("</select></div>");
            }
          }
        }
        ?>

        <div class="deck-link-input">
          <label for="deckLinkP1">Deck Link (Player 1):</label>
          <input type="text" id="deckLinkP1" name="fabdb" placeholder="Paste Player 1 deck URL here">
        </div>
      </div>

      <!-- PLAYER 2 DECK SELECTION -->
      <div class="container bg-yellow" style="flex: 1; min-width: 300px;">
        <h3>Player 2 Deck</h3>
        <div id="deckFeedbackP2" class="deck-feedback"></div>

        <?php
        if (isset($_SESSION["userid"])) {
          if ($swuStatsLinked) {
            echo "<div id='deckLoadingContainerP2' class='swustats-connected'>";
            echo "<select id='swuDecksLoadingP2' name='swuDecksLoadingP2' disabled>";
            echo "<option>Loading decks...</option>";
            echo "</select>";
            echo "</div>";
            echo "<div id='deckDropdownContainerP2' style='display: none;'>
              <select id='swuDecksP2' name='swuDecksP2' style='margin-top: 15px; margin-bottom: 10px;'>
              <option value=''>-- Select a deck --</option>
              </select>
              </div>";
          } else {
            if (count($favoriteDecks) > 0) {
              echo ("<div class='SelectDeckInput'>");
              echo ("<select name='favoriteDecksP2' id='favoriteDecksP2'>");
              echo ("<option value=''>-- Select a deck --</option>");
              for ($i = 0; $i < count($favoriteDecks); $i += 4) {
                echo ("<option value='" . $i . "<fav>" . $favoriteDecks[$i] . "'>" . $favoriteDecks[$i + 1] . "</option>");
              }
              echo ("</select></div>");
            }
          }
        }
        ?>

        <div class="deck-link-input">
          <label for="deckLinkP2">Deck Link (Player 2):</label>
          <input type="text" id="deckLinkP2" name="fabdbP2" placeholder="Paste Player 2 deck URL here">
        </div>
      </div>
    </div>

    <!-- GAME SETTINGS -->
    <div class="container bg-yellow" style="margin-top: 20px;">
      <h3>Game Settings</h3>
      <br><label for="format" class='SelectDeckInput'>Format</label>
      <select name="format" id="format">
        <option value="onepprem">Premier</option>
        <option value="onepopen">Open Format</option>
      </select>

      <input type="hidden" name="visibility" value="private">
    </div>

    <div style="text-align: center; margin-top: 20px;">
      <button type="submit" id="createOnePlayerGameButton" class="modal-btn modal-btn-primary" style="padding: 10px 20px; font-size: 1.2em;">Create Testing Game</button>
    </div>
  </form>

  <script>
  document.addEventListener('DOMContentLoaded', function() {
    // Deck validation functions
    function validateDeckLink(url, playerId) {
      const validDomains = ['swustats.net', 'swudb.com', 'sw-unlimited-db.com'];
      const deckFeedback = document.getElementById('deckFeedbackP' + playerId);

      if (!url || url.trim() === '') {
        deckFeedback.innerHTML = 'Please select or enter a deck <span class="help-icon">?</span>';
        deckFeedback.className = 'deck-feedback deck-invalid';
        deckFeedback.style.display = 'block';
        return false;
      }

      // Check for JSON data
      if (url.trim().startsWith('{')) {
        try {
          const deckData = JSON.parse(url.trim());
          if (!deckData.leader || !deckData.base || !deckData.deck) {
            deckFeedback.textContent = 'Invalid JSON deck data';
            deckFeedback.className = 'deck-feedback deck-invalid';
            deckFeedback.style.display = 'block';
            return false;
          }

          deckFeedback.textContent = 'Valid JSON deck data accepted!';
          deckFeedback.className = 'deck-feedback deck-valid';
          deckFeedback.style.display = 'block';
          return true;
        } catch (e) {
          deckFeedback.textContent = 'Invalid JSON format: ' + e.message;
          deckFeedback.className = 'deck-feedback deck-invalid';
          deckFeedback.style.display = 'block';
          return false;
        }
      }

      // Check for valid domains
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
      } else {
        deckFeedback.innerHTML = 'Please enter a valid deck URL or JSON data <span class="help-icon">?</span>';
        deckFeedback.className = 'deck-feedback deck-invalid';
        deckFeedback.style.display = 'block';
      }

      return isValid;
    }

    // Event listeners for deck inputs
    const deckLinkP1 = document.getElementById('deckLinkP1');
    const deckLinkP2 = document.getElementById('deckLinkP2');
    const createBtn = document.getElementById('createOnePlayerGameButton');

    function validateForm() {
      const isP1Valid = validateDeckLink(deckLinkP1.value, 1);
      const isP2Valid = validateDeckLink(deckLinkP2.value, 2);
      createBtn.disabled = !(isP1Valid && isP2Valid);
    }

    // Validate on input
    if (deckLinkP1) deckLinkP1.addEventListener('input', validateForm);
    if (deckLinkP2) deckLinkP2.addEventListener('input', validateForm);

    // Initialize validation
    validateForm();

    // Connect favorite decks dropdowns
    const favoriteDecksP1 = document.getElementById('favoriteDecksP1');
    const favoriteDecksP2 = document.getElementById('favoriteDecksP2');

    if (favoriteDecksP1) {
      favoriteDecksP1.addEventListener('change', function() {
        var selectedValue = favoriteDecksP1.value;
        if (selectedValue && selectedValue.includes('<fav>')) {
          var parts = selectedValue.split('<fav>');
          if (parts.length > 1 && deckLinkP1) {
            deckLinkP1.value = parts[1];
            validateForm();
          }
        }
      });
    }

    if (favoriteDecksP2) {
      favoriteDecksP2.addEventListener('change', function() {
        var selectedValue = favoriteDecksP2.value;
        if (selectedValue && selectedValue.includes('<fav>')) {
          var parts = selectedValue.split('<fav>');
          if (parts.length > 1 && deckLinkP2) {
            deckLinkP2.value = parts[1];
            validateForm();
          }
        }
      });
    }

    // SWU Stats integration if available
    <?php if ($swuStatsLinked) { ?>
    function loadSWUStatsDecks(playerPrefix) {
      var deckLoadingContainer = document.getElementById('deckLoadingContainer' + playerPrefix);
      var deckDropdownContainer = document.getElementById('deckDropdownContainer' + playerPrefix);
      var swuDecksDropdown = document.getElementById('swuDecks' + playerPrefix);
      var deckLinkInput = document.getElementById('deckLink' + playerPrefix);

      function populateDeckDropdown(decks) {
        var validDecks = decks.filter(function(deck) {
          return true;
        });

        validDecks.forEach(function(deck) {
          var option = document.createElement('option');
          var deckId = deck.id || deck.deckId || deck.keyIndicator2 || '';
          var deckLink = '';
          if (deckId) {
            deckLink = 'https://www.swustats.net/TCGEngine/NextTurn.php?gameName=' + encodeURIComponent(deckId) + '&playerID=1&folderPath=SWUDeck';
          }
          option.value = deckLink;
          option.textContent = deck.name || deck.deckName || 'Unnamed Deck';
          swuDecksDropdown.appendChild(option);
        });

        if (validDecks.length === 0) {
          deckLoadingContainer.textContent = 'No valid decks found.';
          deckLoadingContainer.style.display = 'block';
          deckDropdownContainer.style.display = 'none';
        }
      }

      swuDecksDropdown.addEventListener('change', function() {
        var selectedValue = swuDecksDropdown.value;
        deckLinkInput.value = selectedValue;
        validateForm();
      });

      var xhr = new XMLHttpRequest();
      xhr.open('GET', '../Assets/SWUStats/get_user_decks.php', true);
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
    }

    loadSWUStatsDecks('P1');
    loadSWUStatsDecks('P2');
    <?php } ?>
  });
  </script>
</div>