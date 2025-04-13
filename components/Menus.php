<?php
function EndGameRematchButtons($playerID, $endBo3, $myWins, $theirWins, $gameName, $isPremierStrict): string {
  $content = "";
  if($playerID != 3)
    $content .= ($endBo3 ? "Final" : "Current" ) . " Score is " . $myWins . " - " . $theirWins . "<br/><br/>";
  else
    $content .= ($endBo3 ? "Final" : "Current") . " Score is " . GetCachePiece($gameName, 25) . " - " . GetCachePiece($gameName, 26) . "<br/><br/>";
  if($playerID != 1 && $isPremierStrict && !$endBo3)
    $content .= "Waiting for Host to Begin Next Game in this Round.";
  if(!$isPremierStrict || $endBo3)
    $content .= CreateButton($playerID, "Main Menu", 100001, 0, "24px", "", "", false, true);
  if ($playerID == 1 && !$isPremierStrict) $content .= "&nbsp;" . CreateButton($playerID, "Rematch", 100004, 0, "24px");
  if ($playerID == 1 && $isPremierStrict && !$endBo3) $content .= "&nbsp;" . CreateButton($playerID, "Next Game", 100004, 0, "24px");
  if ($playerID == 1 && !$isPremierStrict) $content .= "&nbsp;" . CreateButton($playerID, "Quick Rematch", 100000, 0, "24px");

  return $content;
}
?>