<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
<script src="./jsInclude250308.js"></script>
<?php
require "MenuBar.php";
include_once './AccountFiles/AccountDatabaseAPI.php';
include "Libraries/PlayerSettings.php";
include_once './Database/ConnectionManager.php';

if (!isset($_SESSION['userid'])) {
    header('Location: ./MainMenu.php');
    die();
}

// Check if the user is banned
if (isset($_SESSION["userid"]) && IsBanned($_SESSION["userid"])) {
  header("Location: ./PlayerBanned.php");
  exit;
}

include_once "CardDictionary.php";
include_once "./Libraries/UILibraries2.php";
include_once "./APIKeys/APIKeys.php";

// Calculate default dates
$startDate = date('Y-m-d', strtotime('-1 month'));
$endDate = date('Y-m-d');

?>

<script>
$(document).on('click', '#filterButton', function() {
    console.log("Filter button clicked!"); // Debugging line
    $.ajax({
        url: 'zzGameStats.php',
        type: 'GET',
        data: $('#filterForm').serialize(),
        success: function(data) {
            console.log("AJAX Response:", data); // Log the response data
            $('#statsContainer').html(data);
        },
        error: function(xhr, status, error) {
            console.error('AJAX Error: ' + status + ' ' + error);
        }
    });
});
</script>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - Petranaki</title>
    <style>
        /* Common styles for adequate header spacing */
        .core-wrapper {
            margin-top: 0px !important; /* Increased top margin for header space */
            min-height: calc(100vh - 200px) !important; /* Adjust to account for header and footer */
            position: relative;
            z-index: 1; /* Keep content above background, below header */
            width: 100%;
            max-width: 100%;
            box-sizing: border-box;
            overflow: visible !important;
        }

        /* Ensure profile content is properly laid out */
        .profile-flex-wrapper {
            display: flex;
            flex-direction: row;
            gap: 32px;
            width: 100%;
            padding: 0 20px;
            box-sizing: border-box;
        }

        @media screen and (max-width: 768px) {
            .core-wrapper {
                margin-top: 0px !important; /* More space for mobile header */
                height: auto !important;
                min-height: calc(100vh - 220px) !important;
            }

            .profile-flex-wrapper {
                flex-direction: column;
                gap: 20px;
                padding: 0 10px;
            }

            .fav-decks, .profile-set-settings-wrapper {
                width: 100% !important;
                max-width: 100% !important;
                margin: 0 0 15px 0 !important;
            }

            .fav-decks.container, .profile-set-settings.container {
                width: 100%;
                margin: 0 0 15px 0 !important;
                padding: 15px;
            }

            .disclaimer {
                left: 0;
                right: 0;
                border-radius: 0;
                width: 100%;
                z-index: 100;
            }
        }
    </style>
</head>
<body>

<?php
include_once 'Header.php';
?>

<div id="cardDetail" style="z-index:100000; display:none; position:fixed;"></div>

<?php
if (isset($_SESSION['swustats_linked_success']) && $_SESSION['swustats_linked_success']) {
    echo '<div id="swustats-modal" style="position:fixed;top:0;left:0;width:100vw;height:100vh;background:rgba(0,0,0,0.3);z-index:99999;display:flex;align-items:center;justify-content:center;">
        <div style="background:#d4edda;color:#155724;padding:30px 40px;border-radius:12px;border:1px solid #c3e6cb;box-shadow:0 6px 32px rgba(0,0,0,0.15);font-size:1.2em;text-align:center;min-width:320px;">
            SWUStats account linked successfully!
        </div>
    </div>';
    unset($_SESSION['swustats_linked_success']);
    echo '<script>
        setTimeout(function() {
            var modal = document.getElementById("swustats-modal");
            if(modal) { modal.style.transition = "opacity 0.6s"; modal.style.opacity = 0; setTimeout(function(){ modal.remove(); }, 700); }
        }, 1500);
    </script>';
}
?>
<div class="core-wrapper">
  <div class="profile-flex-wrapper">
    <div class='fav-decks container bg-yellow'>
      <div style="display:flex; gap: 16px; max-width: 100%; flex-wrap: wrap;">
          <h2 style="flex-grow: 1;">Welcome <?php echo $_SESSION['useruid'] ?>!</h2>
          <a href="ChangeUsername.php">
              <button name="change-username" style="height: 40px">Change Username</button>
          </a>
          <a href="ChangePassword.php">
              <button name="change-password" style="height: 40px">Change Password</button>
          </a>
      </div>
      <?php
      DisplayPatreon();

      // SWUStats Login Button
      include_once './APIKeys/APIKeys.php';

      $userData = isset($_SESSION["useruid"]) ? LoadUserData($_SESSION["useruid"]) : NULL;
      $swuStatsLinked = isset($userData) && $userData["swustatsAccessToken"] != null;

      if(!$swuStatsLinked) {
        $swustats_client_id = $swustatsClientID;
        $swustats_redirect_uri = urlencode('https://petranaki.net/Arena/Assets/SWUStats/callback.php');
        $swustats_scopes = urlencode('decks profile stats');
        $swustats_auth_url = "https://swustats.net/TCGEngine/APIs/OAuth/authorize.php?response_type=code&client_id={$swustats_client_id}&redirect_uri={$swustats_redirect_uri}&scope={$swustats_scopes}";
        echo '<a href="' . $swustats_auth_url . '"><button style="background:#3a6ea5;color:#fff;height:40px;margin-bottom:10px;">Login with SWUStats</button></a>';
      } else {
        echo '<div style="margin-bottom: 10px; color: lightblue;">SWUStats account is linked.</div>';
        echo '<a href="./AccountFiles/SWUStatsUnlink.php"><button style="background:#d9534f;color:#fff;height:40px;">Unlink SWUStats</button></a>';
        echo '<br><br>';
      }

      if($swuStatsLinked) {
        echo "<h2>Favorite Decks</h2>";
        echo '<div style="background-color: rgba(255,255,255,0.1); padding: 15px; border-radius: 8px; margin-bottom: 15px;">';
        echo '<p>Looking for your favorite decks? You\'ve linked your SWU Stats account, which means favorite decks are automatically linked to your favorites on SWU Stats.</p>';
        echo '<p>Import your decks on SWU Stats, and click the heart icon. Now your favorite decks are synchronized with any apps that use the SWU Stats API!</p>';
        echo '<p>If you prefer the old behavior, you can unlink your SWU Stats account with the button above. If you do, please leave feedback on our discord!</p>';
        echo '</div>';
      } else {
        echo "<h2>Favorite Decks</h2>";
        $favoriteDecks = LoadFavoriteDecks($_SESSION["userid"]);
        if (count($favoriteDecks) > 0) {
            echo ("<table>");
            echo ("<tr><td>Hero</td><td>Deck Name</td><td>Link</td><td>Delete</td></tr>");
            for ($i = 0; $i < count($favoriteDecks); $i += 4) {
                echo ("<tr>");
                echo ("<td>" . CardLink($favoriteDecks[$i + 2], $favoriteDecks[$i + 2], true) . "</td>");
                echo ("<td>" . $favoriteDecks[$i + 1] . "</td>");
                echo ("<td>" . ParseLink($favoriteDecks[$i]) . "</td>");
                echo ("<td><a style='text-underline-offset:5px;' href='./MenuFiles/DeleteDeck.php?decklink=" . urlencode($favoriteDecks[$i]) . "'>Delete</a></td>");
                echo ("</tr>");
            }
            echo ("</table>");
        }
      }
      ?>
      <h2>Block List</h2>
      <?php
      $myBlockedPlayers = LoadBlockedPlayers($_SESSION["userid"]);
      if (count($myBlockedPlayers) > 0) {
          echo ("<table>");
          echo ("<tr><td>Blocked Player</td></tr>");
          for ($i = 0; $i < count($myBlockedPlayers); $i += 2) {
              echo ("<tr>");
              echo ("<td>" . $myBlockedPlayers[$i+1] . "</td>");
              echo ("</tr>");
          }
          echo ("</table>");
      }
      ?>
      <form class="form-resetpwd" action="includes/BlockUser.php" method="post">
          <input class="block-input" type="text" name="userToBlock" placeholder="User to block">
          <button type="submit" name="block-user-submit">Block</button>
      </form>
    </div>
    <div class="profile-set-settings-wrapper">
      <div class='profile-set-settings container bg-yellow'>
        <h2>Game Settings</h2>
        <script>
          function OnFaveDeckChange(c) {
            const deckIndex = c.split("<fav>")[0];
            var xmlhttp = new XMLHttpRequest();
            xmlhttp.onreadystatechange = function() {
              if (this.readyState == 4 && this.status == 200) { }
            }
            var ajaxLink = "api/UpdateMyPlayerSetting.php?userid=" + <?php echo ($_SESSION["userid"]); ?>;
            ajaxLink += "&piece=" + <?php echo ($SET_FavoriteDeckIndex); ?>;
            ajaxLink += `&value=${deckIndex}`;
            xmlhttp.open("GET", ajaxLink, true);
            xmlhttp.send();
          }

          function OnCardbackChange(c) {
            const cardback = c.split("-")[1];
            var xmlhttp = new XMLHttpRequest();
            xmlhttp.onreadystatechange = function() {
              if (this.readyState == 4 && this.status == 200) { window.location.reload(); }
            }
            var ajaxLink = "api/UpdateMyPlayerSetting.php?userid=" + <?php echo ($_SESSION["userid"]); ?>;
            ajaxLink += "&piece=" + <?php echo ($SET_Cardback); ?>;
            ajaxLink += `&value=${cardback}`;
            xmlhttp.open("GET", ajaxLink, true);
            xmlhttp.send();
          }

          function OnBackgroundChange(c) {
            const background = c.split("-")[1];
            var xmlhttp = new XMLHttpRequest();
            xmlhttp.onreadystatechange = function() {
              if (this.readyState == 4 && this.status == 200) { window.location.reload(); }
            }
            var ajaxLink = "api/UpdateMyPlayerSetting.php?userid=" + <?php echo ($_SESSION["userid"]); ?>;
            ajaxLink += "&piece=" + <?php echo ($SET_Background); ?>;
            ajaxLink += `&value=${background}`;
            xmlhttp.open("GET", ajaxLink, true);
            xmlhttp.send();
          }
        </script>
        <?php
        $savedSettings = LoadSavedSettings($_SESSION["userid"]);
        $settingArray = [];
        for ($i = 0; $i < count($savedSettings); $i += 2) {
          $settingArray[$savedSettings[intval($i)]] = $savedSettings[intval($i) + 1];
        }
        $favoriteDecks = [];
        $favoriteDecks = LoadFavoriteDecks($_SESSION["userid"]);
        if (count($favoriteDecks) > 0) {
          $selIndex = -1;
          if (isset($settingArray[$SET_FavoriteDeckIndex])) $selIndex = $settingArray[$SET_FavoriteDeckIndex];
          echo ("<div class='SelectDeckInput'>Favorite Deck");
          echo ("<select onchange='OnFaveDeckChange(event.target.value)' name='favoriteDecks' id='favoriteDecks'>");
          for ($i = 0; $i < count($favoriteDecks); $i += 4) {
            echo ("<option value='" . $i . "<fav>" . $favoriteDecks[$i] . "'" . ($i == $selIndex ? " selected " : "") . ">" . $favoriteDecks[$i + 1] . "</option>");
          }
          echo ("</select></div>");
        }
        if(!isset($settingArray[$SET_Cardback])) $settingArray[$SET_Cardback] = 0;
        $cbSource = "./concat/" . GetCardBack("", $settingArray[$SET_Cardback]) . ".webp";
        echo ("<div class='SelectDeckInput'>Cardbacks: <span style='margin-left: 25%;'> Preview:");
        echo ("<img src=$cbSource alt='cardback' style='width: 85px; height: 85px; margin-left: 20px;'></span>");
        echo ("<select onchange='OnCardbackChange(event.target.value)' name='cardbacks' id='cardbacks'>");
        echo CardbacksDropdowns($settingArray);
        echo ("</select></div>");
        $stage = getenv('STAGE') ?: 'prod';
        $isDev = $stage === 'dev';
        $patreonCases = $isDev ? [PatreonCampaign::ForceFam] : PatreonCampaign::cases();
        if(count($patreonCases) > 0) {
          echo ("<div class='SelectDeckInput'>Patreon Cardbacks: <span style='margin-left: 25%;'>");
          echo ("<select onchange='OnCardbackChange(event.target.value)' name='cardbacks' id='cardbacks'>");
          echo PatreonCardbacksDropdowns($settingArray, $patreonCases);
          echo ("</select></div>");
        }
        ?>
        <?php
        if(!isset($settingArray[$SET_Background])) $settingArray[$SET_Background] = 0;
        $bgSource = "./Images/" . GetGameBgSrc($settingArray[$SET_Background])[0];
        echo ("<div class='SelectDeckInput'>Backgrounds: <span style='margin-left: 15%;'> Preview:");
        echo ("<img src=$bgSource alt='cardback' style='width: 160px; height: 90px; margin-left: 20px;'></span>");
        echo ("<select onchange='OnBackgroundChange(event.target.value)' name='backgrounds' id='backgrounds'>");
        echo GameBackgroundDropdowns($settingArray);
        echo ("</select></div>");
        ?>
      </div>
    </div>
  </div>
</div>


<?php
function DisplayPatreon() {
    global $patreonClientID, $patreonClientSecret;
    $client_id = $patreonClientID;
    $client_secret = $patreonClientSecret;

    $redirect_uri = "https://www.petranaki.net/Arena/PatreonLogin.php";
    $href = 'https://www.patreon.com/oauth2/authorize?response_type=code&client_id=' . $client_id . '&redirect_uri=' . urlencode($redirect_uri);
    $state = array();
    $state['final_page'] = 'https://petranaki.net/Arena/MainMenu.php';
    $state_parameters = '&state=' . urlencode(base64_encode(json_encode($state)));
    $href .= $state_parameters;
    $scope_parameters = '&scope=identity%20identity.memberships';
    $href .= $scope_parameters;

    if (!isset($_SESSION["patreonAuthenticated"])) {
        echo '<a class="containerPatreon" href="' . $href . '">';
        echo ("<img class='imgPatreon' src='./Assets/patreon-php-master/assets/images/login_with_patreon.png' alt='Login via Patreon'>");
        echo '</a>';
    } else {
        include './zzPatreonDebug.php';
    }
}

function ParseLink($link) {
  if(strpos($link, "swustats.net") !== false) {
    return "<a href='https://" . $link . "&playerID=1&folderPath=SWUDeck' target='_blank'>View on SWU Stats</a>";
  } else if(strpos($link, "swudb.com") !== false) {
    return "<a href='https://" . $link . "' target='_blank'>View on SWUDB</a>";
  } else if(strpos($link, "sw-unlimited-db.com" !== false)) {
    return "<a href='https://" . $link . "' target='_blank'>View on sw-unlimited-db.com</a>";
  } else {
    return "error: link not supported";
  }
}
?>
</body>
</html>

<?php
require "Disclaimer.php";
?>
