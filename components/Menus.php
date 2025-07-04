<?php
function EndGameRematchButtons($playerID, $endBo3, $myWins, $theirWins, $gameName, $isPremierStrict, $isPremierQuick): string {
  $content = "";
  if($playerID != 3)
    $content .= ($endBo3 ? "Final" : "Current" ) . " Score is " . $myWins . " - " . $theirWins . "<br/><br/>";
  else
    $content .= ($endBo3 ? "Final" : "Current") . " Score is " . GetCachePiece($gameName, 25) . " - " . GetCachePiece($gameName, 26) . "<br/><br/>";
  if($playerID != 1 && $isPremierStrict && !$endBo3)
    $content .= "Waiting for Host to Begin Next Game in this Round.";
  if(!$isPremierStrict || $endBo3)
    $content .= CreateButton($playerID, "Main Menu", 100001, 0, "24px", "", "", false, true);
  if ($playerID == 1 && !$isPremierStrict && !$isPremierQuick) $content .= "&nbsp;" . CreateButton($playerID, "Rematch", 100004, 0, "24px");
  if ($playerID == 1 && $isPremierStrict && !$endBo3) $content .= "&nbsp;" . CreateButton($playerID, "Next Game", 100004, 0, "24px");
  if ($playerID == 1 && !$isPremierStrict && !$isPremierQuick) $content .= "&nbsp;" . CreateButton($playerID, "Quick Rematch", 100000, 0, "24px");

  return $content;
}

function BlockOpponentButtons($playerID): string {
  global $p1uid, $p2uid, $gameName;
  $content = "";
  include_once __DIR__ . "/../MenuFiles/ParseGamefile.php";
  $theirUsername = $playerID == 1 ? $p2uid : $p1uid;
  //ShowBlockOpponentForm() defined in main UI (NextTurn4.php)
  $content .= "<br/><br/><button id='blockOppButton' onclick='ShowBlockOpponentForm();'>Block Opponent</button>";
  $content .= "<br/><br/><div id='blockOppForm' style='display: none;'>";
  $content .= "<form class='form-resetpwd' action='includes/BlockUser.php' method='post'>
    <input class='block-input' type='text' name='userToBlock' value='$theirUsername' readonly>
    <button type='submit' name='block-user-submit'>Block Opponent</button>
    </form>";
  $content .= "</div>";

  return $content;
}
?>