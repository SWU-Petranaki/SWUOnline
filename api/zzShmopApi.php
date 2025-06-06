<style>
  body {
    font-family: Arial, sans-serif;
    background-color: black;
    color: ghostwhite;
    padding: 20px;
  }
  h1,h3 {
    color: ghostwhite;
  }
  pre {
    background-color: dimgrey;
    padding: 10px;
    border-radius: 5px;
    color: greenyellow;
    font-weight: lighter;
  }
  .api-param {
    color: lightblue;
    font-weight: normal;
  }
</style>
<?php
if(!IsDevEnvironment()) {
  echo "Forbidden";
  exit;
}

include_once "../Libraries/SHMOPLibraries.php";

$list = $_GET["list"] ?? '';
$gameName = $_GET["gameName"] ?? '';
$cachePiece = $_GET["cachePiece"] ?? '';
$value = $_GET["value"] ?? '';

if($list == "true") {
  echo "<h1>Cache Pieces</h1>
    <h3>Call this api with params <pre>?gameName={gameName}&cachePiece={cachePiece}&value={value}</pre></h3>
    <ul>
      <li>1 - Update Number</li>
      <ul><li>api param: 1 or <span class='api-param'>update_number</span></li></ul>
      <li>2 - P1 Last Connection Time</li>
      <ul><li>api param: 2 or <span class='api-param'>p1_last_connection_time</span></li></ul>
      <li>3 - P2 Last Connection Time</li>
      <ul><li>api param: 3 or <span class='api-param'>p2_last_connection_time</span></li></ul>
      <li>4 - Player 1 status</li>
      <ul><li>api param: 4 or <span class='api-param'>p1_status</span></li></ul>
      <li>5 - Player 2 status</li>
      <ul><li>api param: 5 or <span class='api-param'>p2_status</span></li></ul>
      <li>6 - Last gamestate update (time)</li>
      <ul><li>api param: 6 or <span class='api-param'>last_gamestate_update</span></li></ul>
      <li>7 - P1 Hero</li>
      <ul><li>api param: 7 or <span class='api-param'>p1_hero</span></li></ul>
      <li>8 - P2 Hero</li>
      <ul><li>api param: 8 or <span class='api-param'>p2_hero</span></li></ul>
      <li>9 - Game visibility (1 = public, 0 = private)</li>
      <ul><li>api param: 9 or <span class='api-param'>game_visibility</span></li></ul>
      <li>10 - Is Replay</li>
      <ul><li>api param: 10 or <span class='api-param'>is_replay</span></li></ul>
      <li>11 - Number P2 disconnects</li>
      <ul><li>api param: 11 or <span class='api-param'>p2_disconnects</span></li></ul>
      <li>12 - Current player status (0 = active, 1 = inactive)</li>
      <ul><li>api param: 12 or <span class='api-param'>current_player_status</span></li></ul>
      <li>13 - Format (see function FormatCode)</li>
      <ul><li>api param: 13 or <span class='api-param'>format</span></li></ul>
      <li>14 - Game status (see \$GS_ constants)</li>
      <ul><li>api param: 14 or <span class='api-param'>game_status</span></li></ul>
      <li>15 - P1 Disconnect Status (0 = connected, 1 = first warning, 2 = second warning, 3 = disconnected; opponent can claim victory)</li>
      <ul><li>api param: 15 or <span class='api-param'>p1_disconnect_status</span></li></ul>
      <li>16 - P2 Disconnect Status</li>
      <ul><li>api param: 16 or <span class='api-param'>p2_disconnect_status</span></li></ul>
      <li>17 - Last Action Time</li>
      <ul><li>api param: 17 or <span class='api-param'>last_action_time</span></li></ul>
      <li>18 - Last Action Warning (0 = no warning, 1 = player 1, 2 = player 2)</li>
      <ul><li>api param: 18 or <span class='api-param'>last_action_warning</span></li></ul>
      <li>19 - Player Autopassed Last Turn (0 = none, 1 = player 1, 2 = player 2)</li>
      <ul><li>api param: 19 or <span class='api-param'>final_warning</span></li></ul>
      <li>20 - P1 Base</li>
      <ul><li>api param: 20 or <span class='api-param'>p1_base</span></li></ul>
      <li>21 - P2 Base</li>
      <ul><li>api param: 21 or <span class='api-param'>p2_base</span></li></ul>
      <li>22 - P1 Second Hero</li>
      <ul><li>api param: 22 or <span class='api-param'>p1_second_hero</span></li></ul>
      <li>23 - P2 Second Hero</li>
      <ul><li>api param: 23 or <span class='api-param'>p2_second_hero</span></li></ul>
      <li>24 - Round Game Number (eg. for a Bo3)</li>
      <ul><li>api param: 24 or <span class='api-param'>round_game_number</span></li></ul>
      <li>25 - P1 Game Wins</li>
      <ul><li>api param: 25 or <span class='api-param'>p1_game_wins</span></li></ul>
      <li>26 - P2 Game Wins</li>
      <ul><li>api param: 26 or <span class='api-param'>p2_game_wins</span></li></ul>
      <li>27 - P1 Undo Count per Round</li>
      <ul><li>api param: 27 or <span class='api-param'>p1_undo_count</span></li></ul>
      <li>28 - P2 Undo Count per Round</li>
      <ul><li>api param: 28 or <span class='api-param'>p2_undo_count</span></li></ul>
      <li>29 - P1 Revert Count Per Round</li>
      <ul><li>api param: 29 or <span class='api-param'>p1_revert_count</span></li></ul>
      <li>30 - P2 Revert Count Per Round</li>
      <ul><li>api param: 30 or <span class='api-param'>p2_revert_count</span></li></ul>
    </ul>
  ";
  exit;
}

if($gameName == "") {
  echo "<h3>Missing gameName</h3>";
  exit;
}
$path = "../Games/$gameName";
if(!is_dir($path)) {
  echo "<h3>Game not found</h3>";
  exit;
}

if($cachePiece == "") {
  echo "<h3>Missing cachePiece</h3>";
  exit;
}

$cachePiece = ParseCachePiece($cachePiece);

if($value == "") {
  $value = GetCachePiece($gameName, $cachePiece);
  if($value == "") {
    echo "<h3>Cache piece not found</h3>";
    exit;
  }
  echo "<h1>Cache Piece</h1>";
  echo "<pre>$value</pre>";
  exit;
}

SetCachePiece($gameName, $cachePiece, $value);
echo "<h1>Cache Piece Set</h1>";
echo "<pre>Game Name: $gameName</pre>";
echo "<pre>Cache Piece: $cachePiece</pre>";
$newVal = GetCachePiece($gameName, $cachePiece);
echo "<pre>Value: $newVal</pre>";
exit;

function ParseCachePiece($cachePiece) {
  if(intval($cachePiece)) {
    $cachePiece = intval($cachePiece);
    if($cachePiece < 1 || $cachePiece > 30) {
      echo "<h3>Invalid cache piece</h3>";
      exit();
    }
    return $cachePiece;
  }
  $cachePiece = strtolower($cachePiece);
  switch($cachePiece) {
    case "update_number":
      return 1;
    case "p1_last_connection_time":
      return 2;
    case "p2_last_connection_time":
      return 3;
    case "p1_status":
      return 4;
    case "p2_status":
      return 5;
    case "last_gamestate_update":
      return 6;
    case "p1_hero":
      return 7;
    case "p2_hero":
      return 8;
    case "game_visibility":
      return 9;
    case "is_replay":
      return 10;
    case "p2_disconnects":
      return 11;
    case "current_player_status":
      return 12;
    case "format":
      return 13;
    case "game_status":
      return 14;
    case "p1_disconnect_status":
      return 15;
    case "p2_disconnect_status":
      return 16;
    case "last_action_time":
      return 17;
    case "last_action_warning":
      return 18;
    case "final_warning":
      return 19;
    case "p1_base":
      return 20;
    case "p2_base":
      return 21;
    case "p1_second_hero":
      return 22;
    case "p2_second_hero":
      return 23;
    case "round_game_number":
      return 24;
    case "p1_game_wins":
      return 25;
    case "p2_game_wins":
      return 26;
    case "p1_undo_count":
      return 27;
    case "p2_undo_count":
      return 28;
    case "p1_revert_count":
      return 29;
    case "p2_revert_count":
      return 30;
   }
   echo "<h3>Invalid cache piece</h3>";
   exit();
}
?>