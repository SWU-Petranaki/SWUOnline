<?php
include_once 'MenuBar.php';
include "HostFiles/Redirector.php";
include_once "Libraries/PlayerSettings.php";
include_once 'Assets/patreon-php-master/src/PatreonDictionary.php';
include_once './AccountFiles/AccountDatabaseAPI.php';
include_once './Database/ConnectionManager.php';

// Check if the user is banned
if (isset($_SESSION["userid"]) && IsBanned($_SESSION["userid"])) {
  header("Location: ./PlayerBanned.php");
  exit;
}

$gameName = $_GET["gameName"];
if (!IsGameNameValid($gameName)) {
  echo ("Invalid game name.");
  exit;
}
$playerID = $_GET["playerID"];
if ($playerID == "1") {
  echo ("Player 1 should not use JoinGame.php");
  exit;
}

$settingArray = [];
$userData = isset($_SESSION["useruid"]) ? LoadUserData($_SESSION["useruid"]) : NULL;
$swuStatsLinked = isset($userData) && $userData["swustatsAccessToken"] != null;

if (isset($_SESSION["userid"])) {
  $savedSettings = LoadSavedSettings($_SESSION["userid"]);
  for ($i = 0; $i < count($savedSettings); $i += 2) {
    $settingArray[$savedSettings[intval($i)]] = $settingArray[$savedSettings[intval($i) + 1]] = $savedSettings[intval($i) + 1];
  }
}

?>

<?php
include_once 'Header.php';
?>

<div class="core-wrapper">
<div class="flex-padder"></div>
<div class="flex-wrapper">
  <div class='game-invite container bg-yellow'>
    <h2>Join Game</h2>
    <?php
    echo ("<form action='" . $redirectPath . "/JoinGameInput.php'>");
    echo ("<input type='hidden' id='gameName' name='gameName' value='$gameName'>");
    echo ("<input type='hidden' id='playerID' name='playerID' value='$playerID'>");
    ?>

    <?php
    echo ("<form style='display:inline-block;' action='" . $redirectPath . "/CreateGame.php'>");

    $favoriteDecks = [];
    if (isset($_SESSION["userid"])) {
      if ($swuStatsLinked) {
        echo "<div id='deckLoadingContainer' class='swustats-connected'>";
        echo "<select id='swuDecksLoading' name='swuDecksLoading' disabled>";
        echo "<option>Loading decks...</option>";
        echo "</select>";
        echo "</div>";
        echo "<div id='deckDropdownContainer' style='display: none;'>
          <select name='swuDecks' id='swuDecks' style='margin-top: 15px; margin-bottom: 10px;'>
          <option value=''>-- Select a deck --</option>
          </select>
          </div>";
        $selIndex = -1;
        if (isset($settingArray[$SET_FavoriteDeckIndex])) $selIndex = $settingArray[$SET_FavoriteDeckIndex];
        echo "<script>var savedFavoriteDeckIndex = " . $selIndex . ";</script>";
      } else {
        $favoriteDecks = LoadFavoriteDecks($_SESSION["userid"]);
        if (count($favoriteDecks) > 0) {
          $selIndex = -1;
          if (isset($settingArray[$SET_FavoriteDeckIndex])) $selIndex = $settingArray[$SET_FavoriteDeckIndex];
          echo ("<label for='favoriteDecks'>Favorite Decks");
          echo ("<select name='favoriteDecks' id='favoriteDecks'>");
          for ($i = 0; $i < count($favoriteDecks); $i += 4) {
            echo ("<option value='" . $favoriteDecks[$i] . "'" . ($i == $selIndex ? " selected " : "") . ">" . $favoriteDecks[$i + 1] . "</option>");
          }
          echo ("</select></label>");
        }
      }
    }
    /*
    if (count($favoriteDecks) == 0) {
      echo ("<div><label class='SelectDeckInput'>Starter Decks: </label>");
      echo ("<select name='decksToTry' id='decksToTry'>");

      echo ("</select></div>");
    }
    */

    ?>
    <label for="fabdb"><u><a style='color:darksalmon;' href='https://www.swudb.com/' target='_blank'>SWUDB</a></u> or <u><a style='color:darksalmon;' href='https://www.sw-unlimited-db.com/' target='_blank'>SW-Unlimited-DB</a></u> Deck Link <span class="secondary">(use the url or 'Deck Link' button)</span></label>
    <input type="text" id="deckLink" name="fabdb">
    <?php
    if (isset($_SESSION["userid"])) {
      echo ("<span class='save-deck'>");
      echo ("<label for='favoriteDeck'><input title='Save deck to Favorites' class='inputFavoriteDeck' type='checkbox' id='favoriteDeck' name='favoriteDeck' />");
      echo ("Save Deck to Favorites</label>");
      echo ("</span>");
    }
    ?>
    <div style='text-align:center;'><input class="JoinGame_Button" type="submit" value="Join Game"></div>
    </form>
  </div>
  <div class="container bg-yellow">
      <h3>Instructions</h3>
      <p>Choose a deck, then click 'Join Game' to be taken to the game lobby.</p>
      <p>Once in the game lobby, the player who win the dice roll choose if the go first. Then the host can start the game.</p>
      <p>Have Fun!</p>
  </div>
</div>
<div class="flex-padder"></div>
</div>

<?php if ($swuStatsLinked) { ?>
<script>
// SWU Stats deck loading logic using plain JavaScript and AJAX
(function() {
  document.addEventListener('DOMContentLoaded', function() {
    var deckLoadingContainer = document.getElementById('deckLoadingContainer');
    var deckDropdownContainer = document.getElementById('deckDropdownContainer');
    var swuDecksDropdown = document.getElementById('swuDecks');
    var deckLinkInput = document.getElementById('deckLink');
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
      // Set the input field
      deckLinkInput.value = selectedValue;

      // Save the selected index as a user preference
      var selectedIndex = swuDecksDropdown.selectedIndex - 1; // -1 to account for the placeholder
      if (selectedValue && selectedIndex >= 0) {
        // Update the savedFavoriteDeckIndex variable to keep track of the current selection
        savedFavoriteDeckIndex = selectedIndex;

        // Save the selection to user settings via AJAX
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
</script>
<?php } ?>

<?php
include_once 'Disclaimer.php'
?>
