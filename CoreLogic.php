<?php

  include "CardSetters.php";
  include "CardGetters.php";

function EvaluateCombatChain(&$totalAttack, &$totalDefense, &$attackModifiers=[])
{
  global $combatChain, $mainPlayer, $currentTurnEffects, $defCharacter, $playerID, $combatChainState, $CCS_LinkBaseAttack;
  global $CCS_WeaponIndex, $mainCharacter, $mainAuras;
    UpdateGameState($playerID);
    BuildMainPlayerGameState();
    $attackType = CardType($combatChain[0]);
    $canGainAttack = CanGainAttack();
    $snagActive = SearchCurrentTurnEffects("CRU182", $mainPlayer) && $attackType == "AA";
    for($i=1; $i<count($combatChain); $i+=CombatChainPieces())
    {
      $from = $combatChain[$i+1];
      $resourcesPaid = $combatChain[$i+2];

      if($combatChain[$i] == $mainPlayer)
      {
        if($i == 1) $attack = $combatChainState[$CCS_LinkBaseAttack];
        else $attack = SpecificCardPower(AttackerMZID(), $mainPlayer);
        if($canGainAttack || $i == 1 || $attack < 0)
        {
          array_push($attackModifiers, $combatChain[$i-1], $attack);
          if($i == 1) $totalAttack += $attack;
          else AddAttack($totalAttack, $attack);
        }
      }
      else
      {
        $totalDefense += BlockingCardDefense($i-1, $combatChain[$i+1], $combatChain[$i+2]);
      }
    }

    if($combatChainState[$CCS_WeaponIndex] != -1)
    {
      $attack = 0;
      if($attackType == "W") $attack = $mainCharacter[$combatChainState[$CCS_WeaponIndex]+3];
      else if(DelimStringContains(CardSubtype($combatChain[0]), "Aura")) $attack = $mainAuras[$combatChainState[$CCS_WeaponIndex]+3];
      else if(IsAlly($combatChain[0]))
      {
        $allies = &GetAllies($mainPlayer);
        if(count($allies) > $combatChainState[$CCS_WeaponIndex]+7) $attack = $allies[$combatChainState[$CCS_WeaponIndex]+7];
      }
      if($canGainAttack || $attack < 0)
      {
        array_push($attackModifiers, "+1 Attack Counters", $attack);
        AddAttack($totalAttack, $attack);
      }
    }
}

// function CharacterLevel($player)//FAB
// {
//   global $CS_CachedCharacterLevel;
//   return GetClassState($player, $CS_CachedCharacterLevel);
// }

function AddAttack(&$totalAttack, $amount)
{
  global $combatChain;
  if($amount > 0 && $combatChain[0] == "OUT100") $amount += 1;
  if($amount > 0 && ($combatChain[0] == "OUT065" || $combatChain[0] == "OUT066" || $combatChain[0] == "OUT067") && ComboActive()) $amount += 1;
  if($amount > 0) $amount += PermanentAddAttackAbilities();
  $totalAttack += $amount;
}

function BlockingCardDefense($index, $from="", $resourcesPaid=-1)
{
  global $combatChain, $defPlayer, $mainPlayer, $currentTurnEffects;
  $from = $combatChain[$index+2];
  $resourcesPaid = $combatChain[$index+3];
  $defense = BlockValue($combatChain[$index]) + BlockModifier($combatChain[$index], $from, $resourcesPaid) + $combatChain[$index + 6];
  if(CardType($combatChain[$index]) == "E")
  {
    $defCharacter = &GetPlayerCharacter($defPlayer);
    $charIndex = FindDefCharacter($combatChain[$index]);
    $defense += $defCharacter[$charIndex+4];
  }
  for ($i = 0; $i < count($currentTurnEffects); $i += CurrentTurnPieces()) {
    if (IsCombatEffectActive($currentTurnEffects[$i]) && !IsCombatEffectLimited($i)) {
      if ($currentTurnEffects[$i + 1] == $defPlayer) {
        $defense += EffectBlockModifier($currentTurnEffects[$i], index:$index);
      }
    }
  }
  if($defense < 0) $defense = 0;
  return $defense;
}

function AddCombatChain($cardID, $player, $from, $resourcesPaid, $upgradesWithMetadata)
{
  global $combatChain, $turn;
  if($upgradesWithMetadata == "") $upgradesWithMetadata = "-";
  $index = count($combatChain);
  $combatChain[] = $cardID;
  $combatChain[] = $player;
  $combatChain[] = $from;
  $combatChain[] = $resourcesPaid;
  $combatChain[] = RepriseActive();
  $combatChain[] = 0;//Attack modifier
  $combatChain[] = 0;//Defense modifier
  $combatChain[] = $upgradesWithMetadata;
  //if($turn[0] == "B" || CardType($cardID) == "DR") OnBlockEffects($index, $from);//FAB
  CurrentEffectAttackAbility();
  return $index;
}

//FAB
// function CombatChainPowerModifier($index, $amount)
// {
//   global $combatChain;
//   $combatChain[$index+5] += $amount;
//   ProcessPhantasmOnBlock($index);
// }

function CacheCombatResult()
{
  global $combatChain, $combatChainState, $CCS_CachedTotalAttack, $CCS_CachedTotalBlock, $CCS_CachedDominateActive, $CCS_CachedNumBlockedFromHand, $CCS_CachedOverpowerActive;
  global $CSS_CachedNumActionBlocked, $CCS_CachedNumDefendedFromHand;
  if(count($combatChain) > 0)
  {
    $combatChainState[$CCS_CachedTotalAttack] = 0;
    $combatChainState[$CCS_CachedTotalBlock] = 0;
    EvaluateCombatChain($combatChainState[$CCS_CachedTotalAttack], $combatChainState[$CCS_CachedTotalBlock]);
    $combatChainState[$CCS_CachedDominateActive] = (IsDominateActive() ? "1" : "0");
    if ($combatChainState[$CCS_CachedNumBlockedFromHand] == 0) $combatChainState[$CCS_CachedNumBlockedFromHand] = NumBlockedFromHand();
    $combatChainState[$CCS_CachedOverpowerActive] = (IsOverpowerActive() ? "1" : "0");
    $combatChainState[$CSS_CachedNumActionBlocked] = NumActionBlocked();
    $combatChainState[$CCS_CachedNumDefendedFromHand] = NumDefendedFromHand(); //Reprise
  }
}

function CachedTotalAttack()
{
  global $combatChainState, $CCS_CachedTotalAttack;
  return $combatChainState[$CCS_CachedTotalAttack];
}

function CachedTotalBlock()
{
  global $combatChainState, $CCS_CachedTotalBlock;
  return $combatChainState[$CCS_CachedTotalBlock];
}

function CachedDominateActive()
{
  global $combatChainState, $CCS_CachedDominateActive;
  return $combatChainState[$CCS_CachedDominateActive] == "1";
}

function CachedOverpowerActive()
{
  global $combatChainState, $CCS_CachedOverpowerActive;
  return $combatChainState[$CCS_CachedOverpowerActive] == "1";
}

function CachedNumBlockedFromHand() //Dominate
{
  global $combatChainState, $CCS_CachedNumBlockedFromHand;
  return $combatChainState[$CCS_CachedNumBlockedFromHand];
}

function CachedNumDefendedFromHand() //Reprise
{
  global $combatChainState, $CCS_CachedNumDefendedFromHand;
  return $combatChainState[$CCS_CachedNumDefendedFromHand];
}

function CachedNumActionBlocked()
{
  global $combatChainState, $CSS_CachedNumActionBlocked;
  return $combatChainState[$CSS_CachedNumActionBlocked];
}

function StartTurnAbilities()
{
  global $initiativePlayer;
  //AuraStartTurnAbilities();//FAB
  ItemStartTurnAbilities();
}

function ArsenalStartTurnAbilities()
{
  global $mainPlayer;
  $arsenal = &GetArsenal($mainPlayer);
  for($i=0; $i<count($arsenal); $i+=ArsenalPieces())
  {
    switch($arsenal[$i])
    {
      case "MON404": case "MON405": case "MON406": case "MON407": case "DVR007": case "RVD007":
        if($arsenal[$i+1] == "DOWN")
        {
          AddDecisionQueue("YESNO", $mainPlayer, "if_you_want_to_turn_your_mentor_card_face_up");
          AddDecisionQueue("NOPASS", $mainPlayer, "-");
          AddDecisionQueue("PASSPARAMETER", $mainPlayer, $i, 1);
          AddDecisionQueue("TURNARSENALFACEUP", $mainPlayer, $i, 1);
        }
        break;
      default: break;
    }
  }
}

function DamageTrigger($player, $damage, $type, $source="NA", $canPass=false)
{
  AddDecisionQueue("DEALDAMAGE", $player, $damage . "-" . $source . "-" . $type, ($canPass ? 1 : "0"));
  return $damage;
}

//FAB
// function CanDamageBePrevented($player, $damage, $type, $source="-")
// {
//   global $mainPlayer;
//   if($source == "aebjvwbciz" && IsClassBonusActive($mainPlayer, "GUARDIAN") && CharacterLevel($mainPlayer) >= 2) return false;
//   return true;
// }

function DealDamageAsync($player, $damage, $type="DAMAGE", $source="NA", $sourcePlayer = "")
{
  global $CS_DamagePrevention, $combatChain;
  global $CS_ArcaneDamagePrevention, $dqVars, $dqState;

  $classState = &GetPlayerClassState($player);
  if($type == "COMBAT" && $damage > 0 && EffectPreventsHit()) HitEffectsPreventedThisLink();
  if($type == "COMBAT" || $type == "ATTACKHIT") $source = $combatChain[0];
  $damage = max($damage, 0);
  $damageThreatened = $damage;
  $damage = CurrentEffectPreventDamagePrevention($player, $type, $damage, $source);
  if($type == "COMBAT" || $type == "OVERWHELM") $dqState[6] = $damage;
  FinalizeDamage($player, $damage, $damageThreatened, $type, $source);
  if ($damage > 0) {
    $fromCombat = $type == "COMBAT" || $type == "OVERWHELM";
    CheckBobaFettJTL($player, $sourcePlayer != $player, $fromCombat);
    //check for base effects
    $char = &GetPlayerCharacter($sourcePlayer);
    for($i=0; $i<count($char); $i+=CharacterPieces()) {
      switch($char[$i]) {
        case "9453163990"://Temple of Destruction
          if($fromCombat && $damage >= 3)
            TheForceIsWithYou($sourcePlayer);
          break;
        default: break;
      }
    }
    //check for ally "When damage is done on your base" effects
    $allies = &GetAllies($player);
    for($i=0; $i<count($allies); $i+=AllyPieces()) {
      switch($allies[$i]) {
        //Legends of the Force
        case "0662915879"://The Daughter
          if(HasTheForce($player)) {
            DQAskToUseTheForce($player);
            AddDecisionQueue("PASSPARAMETER", $player, "MYCHAR-0", 1);
            AddDecisionQueue("MZOP", $player, "RESTORE,2", 1);
          }
          break;
        default: break;
      }
    }
  }
  return $damage;
}

// function AddDamagePreventionSelection($player, $damage, $preventable)
// {
//   PrependDecisionQueue("PROCESSDAMAGEPREVENTION", $player, $damage . "-" . $preventable, 1);
//   PrependDecisionQueue("CHOOSEMULTIZONE", $player, "<-", 1);
//   PrependDecisionQueue("SETDQCONTEXT", $player, "Choose a card to prevent damage", 1);
//   PrependDecisionQueue("FINDINDICES", $player, "DAMAGEPREVENTION");
// }

function FinalizeDamage($player, $damage, $damageThreatened, $type, $source)
{
  global $otherPlayer, $CS_DamageTaken, $combatChainState, $CCS_AttackTotalDamage, $defPlayer, $mainPlayer;
  global $CCS_AttackFused;
  $classState = &GetPlayerClassState($player);
  $otherPlayer = $player == 1 ? 2 : 1;
  if($damage > 0)
  {
    if($source != "NA")
    {
      $damage += CurrentEffectDamageModifiers($player, $source, $type);
    }

    AllyDealDamageAbilities($otherPlayer, $damage, $type);
    $classState[$CS_DamageTaken] += $damage;
    if($player == $defPlayer && $type == "COMBAT" || $type == "ATTACKHIT") $combatChainState[$CCS_AttackTotalDamage] += $damage;
    // if($type == "ARCANE") $classState[$CS_ArcaneDamageTaken] += $damage;//FAB
    CurrentEffectDamageEffects($player, $source, $type, $damage);
  }
  PlayerLoseHealth($player, $damage);
  LogDamageStats($player, $damageThreatened, $damage);
  return $damage;
}

function ProcessDealDamageEffect($cardID)
{

}

function CurrentEffectDamageModifiers($player, $source, $type)
{
  global $currentTurnEffects;
  $modifier = 0;
  for($i=count($currentTurnEffects)-CurrentTurnPieces(); $i >= 0; $i-=CurrentTurnPieces())
  {
    $remove = 0;
    switch($currentTurnEffects[$i])
    {
      default: break;
    }
    if($remove == 1) RemoveCurrentTurnEffect($i);
  }
  return $modifier;
}

function CurrentEffectDamageEffects($target, $source, $type, $damage)
{
  global $currentTurnEffects;
  if(CardType($source) == "AA" && (SearchAuras("CRU028", 1) || SearchAuras("CRU028", 2))) return;
  for($i=count($currentTurnEffects)-CurrentTurnPieces(); $i >= 0; $i-=CurrentTurnPieces())
  {
    if($currentTurnEffects[$i+1] == $target) continue;
    if($type == "COMBAT" && HitEffectsArePrevented()) continue;
    $remove = 0;
    switch($currentTurnEffects[$i])
    {

      default: break;
    }
    if($remove == 1) RemoveCurrentTurnEffect($i);
  }
}

// function AttackDamageAbilities($damageDone)//FAB
// {
//   global $combatChain, $defPlayer;
//   $attackID = $combatChain[0];
//   switch($attackID)
//   {
//     default: break;
//   }
// }

function LoseHealth($amount, $player)
{
  PlayerLoseHealth($player, $amount);
}

function Restore($amount, $player)
{
  if(SearchCurrentTurnEffects("7533529264", $player)) {
    WriteLog("<span style='color:red;'>Wolffe prevents the healing</span>");
    return false;
  }
  if(SearchAlliesForCard(1, "6277739341") != "" || SearchAlliesForCard(2, "6277739341") != "") {
    WriteLog("<span style='color:red;'>Confederate Tri-Fighter prevents the healing</span>");
    return false;
  }

  $baseDmg = &GetBaseDamage($player);
  WriteLog("Player " . $player . " gained " . $amount . " health.");
  if($amount > $baseDmg) $amount = $baseDmg;
  $baseDmg -= $amount;
  AddEvent("RESTORE", "P" . $player . "BASE!" . $amount);
  return true;
}

function PlayerLoseHealth($player, $amount)
{
  $baseDmg = &GetBaseDamage($player);
  //$amount = AuraLoseHealthAbilities($player, $amount);//FAB
  $char = &GetPlayerCharacter($player);
  if(count($char) == 0) return;
  $baseDmg += $amount;
  AddEvent("DAMAGE", "P" . $player . "BASE!" . $amount);
  if(PlayerRemainingHealth($player) <= 0)
  {
    PlayerWon(($player == 1 ? 2 : 1));
  }
}

function PlayerRemainingHealth($player) {
  $baseDmg = &GetBaseDamage($player);
  $char = &GetPlayerCharacter($player);
  if($char[0] == "DUMMY") return 1000 - $baseDmg;
  return CardHP($char[0]) - $baseDmg;
}

function IsGameOver()
{
  global $inGameStatus, $GameStatus_Over;
  return $inGameStatus == $GameStatus_Over;
}

function PlayerWon($playerID, $concededMatch = false)
{
  global $winner, $turn, $gameName, $p1id, $p2id, $p1uid, $p2uid, $conceded, $currentRound;
  global $p1DeckLink, $p2DeckLink, $inGameStatus, $GameStatus_Over, $firstPlayer, $p1deckbuilderID, $p2deckbuilderID;
  global $p1SWUStatsToken, $p2SWUStatsToken;

  if($turn[0] == "OVER" && !$concededMatch) return;
  include_once "./MenuFiles/ParseGamefile.php";

  $winner = $playerID;
  $machGameNumber = GetCachePiece($gameName, 24);
  $p1Wins = GetCachePiece($gameName, 25);
  $p2Wins = GetCachePiece($gameName, 26);
  if(!$concededMatch) {
    $winsEqualsGamesWon = $p1Wins + $p2Wins == ($machGameNumber - 1);
    if(!$winsEqualsGamesWon) return;
  }
  if ($playerID == 1 && $p1uid != "") WriteLog($p1uid . " wins!", $playerID);
  elseif ($playerID == 2 && $p2uid != "") WriteLog($p2uid . " wins!", $playerID);
  else WriteLog("Player " . $winner . " wins!");

  $inGameStatus = $GameStatus_Over;
  $turn[0] = "OVER";
  IncrementCachePiece($gameName, $playerID + 24);//25 = P1 Game Wins, 26 = P2 Game Wins
  SetCachePiece($gameName, 14, 6);//$MGS_GameOverStatsLogged
  if(GetCachePiece($gameName, 14) == 7) return;//$MGS_StatsLoggedIrreversible

  try {
    if (!AreStatsDisabled(1) && !AreStatsDisabled(2) && !IsDevEnvironment()) {
      SendSWUStatsResults();
    }
  } catch (Exception $e) {

  }

  if(!$conceded || $currentRound>= 3) {
    //If this happens, they left a game in progress -- add disconnect logging?
  }
}

function SendSWUStatsResults() {
  global $gameName, $firstPlayer, $winner, $currentRound, $p1id, $p2id, $p1DeckLink, $p2DeckLink, $SWUStatsAPIKey;
  global $p1SWUStatsToken, $p2SWUStatsToken;
  include_once "./APIKeys/APIKeys.php";

  $url = 'https://swustats.net/TCGEngine/APIs/SubmitGameResult.php';
	$loser = ($winner == 1 ? 2 : 1);
  $source = "Petranaki";
  $apiKey = $SWUStatsAPIKey;
  $winHero = GetCachePiece($gameName, ($winner == 1 ? 7 : 8));
	$loseHero = GetCachePiece($gameName, ($winner == 1 ? 8 : 7));
  $winnerHealth = GetBaseDamage($winner);
  $p1Char = &GetPlayerCharacter(1);
  $p1Hero = FindLeaderInPlay(1);
  $p1Base = DeduplicateBase($p1Char[0]);
  $p1BaseColor = AspectToColor(CardAspects($p1Base));
  $p2Char = &GetPlayerCharacter(2);
  $p2Hero = FindLeaderInPlay(2);
  $p2Base = DeduplicateBase($p2Char[0]);
  $p2BaseColor = AspectToColor(CardAspects($p2Base));
	$winnerDeck = file_get_contents("./Games/" . $gameName . "/p" . $winner . "Deck.txt");
	$loserDeck = file_get_contents("./Games/" . $gameName . "/p" . $loser . "Deck.txt");
  $data_json = json_encode([
    'source' => $source,
    'apiKey' => $apiKey,
    'gameName' => $gameName,
    'round' => $currentRound,
    'winner' => $winner,
    'winHero' => $winHero,
    'loseHero' => $loseHero,
    'firstPlayer' => $firstPlayer,
    'p1id' => $p1id,
    'p2id' => $p2id,
    'p1DeckLink' => $p1DeckLink,
    'p2DeckLink' => $p2DeckLink,
    'winnerHealth' => $winnerHealth,
    'winnerDeck' => $winnerDeck,
    'loserDeck' => $loserDeck,
    'p1SWUStatsToken' => $p1SWUStatsToken,
    'p2SWUStatsToken' => $p2SWUStatsToken,
    'player1' => SerializeGameResult(1, "", file_get_contents("./Games/" . $gameName . "/p1Deck.txt"), $gameName, $p2Hero, "", "", $p2BaseColor, $p1Hero, $p1Base),
    'player2' => SerializeGameResult(2, "", file_get_contents("./Games/" . $gameName . "/p2Deck.txt"), $gameName, $p1Hero, "", "", $p1BaseColor, $p2Hero, $p2Base)
  ]);

  // Initialize cURL session
  $ch = curl_init($url);

  // Set cURL options
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
  curl_setopt($ch, CURLOPT_POST, true);
  curl_setopt($ch, CURLOPT_POSTFIELDS, $data_json);

  // Execute cURL session and get the response
  $response = curl_exec($ch);

  // Check for errors
  if ($response === false) {
      $error = curl_error($ch);
      curl_close($ch);
      die('Curl error: ' . $error);
  }

  // Close cURL session
  curl_close($ch);
}

function DeduplicateBase($base)
{
  if(CardHP($base) != 30) return $base;//TODO: Add rarity check too?
  $baseAspect = CardAspects($base);
  switch($baseAspect) {
    case "Command": return "2055904747";
    case "Vigilance": return "7303722102";
    case "Aggression": return "8659924257";
    case "Cunning": return "4313706014";
    default: return $base;
  }
}

function AspectToColor($aspect)
{
  switch($aspect) {
    case "Command": return "Green";
    case "Vigilance": return "Blue";
    case "Aggression": return "Red";
    case "Cunning": return "Yellow";
    case "Heroism": return "White";
    case "Villainy": return "Black";
  }
}

function UnsetBanishModifier($player, $modifier, $newMod="DECK")
{
  $banish = &GetBanish($player);
  for($i=0; $i<count($banish); $i+=BanishPieces())
  {
    $cardModifier = explode("-", $banish[$i+1])[0];
    if($cardModifier == $modifier) $banish[$i+1] = $newMod;
  }
}

function UnsetDiscardModifier($player, $modifier, $newMod="-")
{
  $discard = &GetDiscard($player);
  for($i=0; $i<count($discard); $i+=DiscardPieces())
  {
    $cardModifier = explode("-", $discard[$i+1])[0];
    if($cardModifier == $modifier) $discard[$i+1] = $newMod;
  }
}

function UnsetChainLinkBanish()
{
  UnsetBanishModifier(1, "TCL");
  UnsetBanishModifier(2, "TCL");
}

function UnsetCombatChainBanish()
{
  UnsetBanishModifier(1, "TCC");
  UnsetBanishModifier(2, "TCC");
  UnsetBanishModifier(1, "TCL");
  UnsetBanishModifier(2, "TCL");
}

function ReplaceBanishModifier($player, $oldMod, $newMod)
{
  UnsetBanishModifier($player, $oldMod, $newMod);
}

function UnsetTurnModifiers()
{
  UnsetDiscardModifier(1, "TT");
  UnsetDiscardModifier(1, "TTOP"); // TTOP is the same as TT, but for the opponent
  UnsetDiscardModifier(1, "TTFREE");
  UnsetDiscardModifier(1, "TTOPFREE"); // TTOPFREE is the same as TTFREE, but for the opponent
  UnsetDiscardModifier(2, "TT");
  UnsetDiscardModifier(2, "TTOP"); // TTOP is the same as TT, but for the opponent
  UnsetDiscardModifier(2, "TTFREE");
  UnsetDiscardModifier(2, "TTOPFREE"); // TTOPFREE is the same as TTFREE, but for the opponent
}

function UnsetTurnBanish()
{
  global $defPlayer;
  UnsetBanishModifier(1, "TT");
  UnsetBanishModifier(1, "INST");
  UnsetBanishModifier(2, "TT");
  UnsetBanishModifier(2, "INST");
  UnsetBanishModifier(1, "ARC119");
  UnsetBanishModifier(2, "ARC119");
  UnsetCombatChainBanish();
  ReplaceBanishModifier($defPlayer, "NT", "TT");
}

function GetChainLinkCards($playerID="", $cardType="", $exclCardTypes="")
{
  global $combatChain;
  $pieces = "";
  $exclArray=explode(",", $exclCardTypes);
  for($i=0; $i<count($combatChain); $i+=CombatChainPieces())
  {
    $thisType = CardType($combatChain[$i]);
    if(($playerID == "" || $combatChain[$i+1] == $playerID) && ($cardType == "" || $thisType == $cardType))
    {
      $excluded = false;
      for($j=0; $j<count($exclArray); ++$j)
      {
        if($thisType == $exclArray[$j]) $excluded = true;
      }
      if($excluded) continue;
      if($pieces != "") $pieces .= ",";
      $pieces .= $i;
    }
  }
  return $pieces;
}

function GetTheirEquipmentChoices()
{
  global $currentPlayer;
  return GetEquipmentIndices(($currentPlayer == 1 ? 2 : 1));
}

function FindMyCharacter($cardID)
{
  global $currentPlayer;
  return FindCharacterIndex($currentPlayer, $cardID);
}

function FindDefCharacter($cardID)
{
  global $defPlayer;
  return FindCharacterIndex($defPlayer, $cardID);
}

//FAB
// function ChainLinkResolvedEffects()
// {
//   global $combatChain, $mainPlayer, $currentTurnEffects;
//   if($combatChain[0] == "MON245" && !ExudeConfidenceReactionsPlayable())
//   {
//     AddCurrentTurnEffect($combatChain[0], $mainPlayer, "CC");
//   }
//   switch($combatChain[0])
//   {
//     case "CRU051": case "CRU052":
//       EvaluateCombatChain($totalAttack, $totalBlock);
//       for ($i = CombatChainPieces(); $i < count($combatChain); $i += CombatChainPieces()) {
//         if (!($totalBlock > 0 && (intval(BlockValue($combatChain[$i])) + BlockModifier($combatChain[$i], "CC", 0) + $combatChain[$i + 6]) > $totalAttack)) {
//           UndestroyCurrentWeapon();
//         }
//       }
//       break;
//       default: break;
//   }
// }

function CombatChainClosedMainCharacterEffects()
{
  global $chainLinks, $chainLinkSummary, $combatChain, $mainPlayer;
  $character = &GetPlayerCharacter($mainPlayer);
  for($i=0; $i<count($chainLinks); ++$i)
  {
    for($j=0; $j<count($chainLinks[$i]); $j += ChainLinksPieces())
    {
      if($chainLinks[$i][$j+1] != $mainPlayer) continue;
      $charIndex = FindCharacterIndex($mainPlayer, $chainLinks[$i][$j]);
      if($charIndex == -1) continue;
      switch($chainLinks[$i][$j])
      {
        case "CRU051": case "CRU052":
          if($character[$charIndex+7] == "1") DestroyCharacter($mainPlayer, $charIndex);
          break;
        default: break;
      }
    }
  }
}

function CombatChainClosedCharacterEffects()
{
  global $chainLinks, $defPlayer, $chainLinkSummary, $combatChain;
  $character = &GetPlayerCharacter($defPlayer);
  for($i=0; $i<count($chainLinks); ++$i)
  {
    $nervesOfSteelActive = $chainLinkSummary[$i*ChainLinkSummaryPieces()+1] <= 2 && SearchAuras("EVR023", $defPlayer);
    for($j=0; $j<count($chainLinks[$i]); $j += ChainLinksPieces())
    {
      if($chainLinks[$i][$j+1] != $defPlayer) continue;
      $charIndex = FindCharacterIndex($defPlayer, $chainLinks[$i][$j]);
      if($charIndex == -1) continue;
      if(!$nervesOfSteelActive)
      {
        if(HasTemper($chainLinks[$i][$j]))
        {
          $character[$charIndex+4] -= 1;//Add -1 block counter
          if((BlockValue($character[$charIndex]) + $character[$charIndex + 4] + BlockModifier($character[$charIndex], "CC", 0) + $chainLinks[$i][$j + 5]) <= 0)
          {
            DestroyCharacter($defPlayer, $charIndex);
          }
        }
        if(HasBattleworn($chainLinks[$i][$j]))
        {
          $character[$charIndex+4] -= 1;//Add -1 block counter
        }
        else if(HasBladeBreak($chainLinks[$i][$j]))
        {
          DestroyCharacter($defPlayer, $charIndex);
        }
      }
      switch($chainLinks[$i][$j])
      {
        default: break;
      }
    }
  }
}

// CR 2.1 - 5.3.4c A card with the type defense reaction becomes a defending card and is moved onto the current chain link instead of being moved to the graveyard.
function NumDefendedFromHand() //Reprise
{
  global $combatChain, $defPlayer;
  $num = 0;
  for($i=0; $i<count($combatChain); $i += CombatChainPieces())
  {
    if($combatChain[$i+1] == $defPlayer)
    {
      $type = CardType($combatChain[$i]);
      if($type != "I" && $combatChain[$i+2] == "HAND") ++$num;
    }
  }
  return $num;
}

function NumBlockedFromHand() //Dominate
{
  global $combatChain, $defPlayer, $layers;
  $num = 0;
  for ($i = 0; $i < count($combatChain); $i += CombatChainPieces()) {
    if ($combatChain[$i + 1] == $defPlayer) {
      $type = CardType($combatChain[$i]);
      if ($type != "I" && $combatChain[$i + 2] == "HAND") ++$num;
    }
  }
  for ($i = 0; $i < count($layers); $i += LayerPieces()) {
    $params = explode("|", $layers[$i + 2]);
    if ($params[0] == "HAND" && CardType($layers[$i]) == "DR") ++$num;
  }
  return $num;
}

function NumActionBlocked()
{
  global $combatChain, $defPlayer;
  $num = 0;
  for ($i = 0; $i < count($combatChain); $i += CombatChainPieces()) {
    if ($combatChain[$i + 1] == $defPlayer) {
      $type = CardType($combatChain[$i]);
      if ($type == "A" || $type == "AA") ++$num;
    }
  }
  return $num;
}

function NumAttacksBlocking()
{
  global $combatChain, $defPlayer;
  $num = 0;
  for($i=0; $i<count($combatChain); $i += CombatChainPieces())
  {
    if($combatChain[$i+1] == $defPlayer)
    {
      if(CardType($combatChain[$i]) == "AA") ++$num;
    }
  }
  return $num;
}

function IHaveLessDamageOnBase()
{
  global $currentPlayer;
  return PlayerHasLessDamageOnBase($currentPlayer);
}

function DefHasLessDamageOnBase()
{
  global $defPlayer;
  return PlayerHasLessDamageOnBase($defPlayer);
}

function PlayerHasLessDamageOnBase($player)
{
  $otherPlayer = ($player == 1 ? 2 : 1);
  return GetBaseDamage($player) < GetBaseDamage($otherPlayer);
}

function PlayerHasFewerEquipment($player)
{
  $otherPlayer = ($player == 1 ? 2 : 1);
  $thisChar = &GetPlayerCharacter($player);
  $thatChar = &GetPlayerCharacter($otherPlayer);
  $thisEquip = 0;
  $thatEquip = 0;
  for($i=0; $i<count($thisChar); $i+=CharacterPieces())
  {
    if($thisChar[$i+1] != 0 && CardType($thisChar[$i]) == "E") ++$thisEquip;
  }
  for($i=0; $i<count($thatChar); $i+=CharacterPieces())
  {
    if($thatChar[$i+1] != 0 && CardType($thatChar[$i]) == "E") ++$thatEquip;
  }
  return $thisEquip < $thatEquip;
}

function GetIndices($count, $add=0, $pieces=1)
{
  $indices = "";
  for($i=0; $i<$count; $i+=$pieces)
  {
    if($indices != "") $indices .= ",";
    $indices .= ($i + $add);
  }
  return $indices;
}

function GetMyHandIndices()
{
  global $currentPlayer;
  return GetIndices(count(GetHand($currentPlayer)));
}

function GetDefHandIndices()
{
  global $defPlayer;
  return GetIndices(count(GetHand($defPlayer)));
}

function CurrentAttack()
{
  global $combatChain;
  if(count($combatChain) == 0) return "";
  return $combatChain[0];
}

function RollDie($player, $fromDQ=false, $subsequent=false)
{
  global $CS_DieRoll;
  $numRolls = 1 + CountCurrentTurnEffects("EVR003", $player);
  $highRoll = 0;
  for($i=0; $i<$numRolls; ++$i)
  {
    $roll = GetRandom(1, 6);
    WriteLog($roll . " was rolled.");
    if($roll > $highRoll) $highRoll = $roll;
  }
  AddEvent("ROLL", $highRoll);
  SetClassState($player, $CS_DieRoll, $highRoll);
  $GGActive = HasGamblersGloves(1) || HasGamblersGloves(2);
  if($GGActive)
  {
    if($fromDQ && !$subsequent) PrependDecisionQueue("AFTERDIEROLL", $player, "-");
    GamblersGloves($player, $player, $fromDQ);
    GamblersGloves(($player == 1 ? 2 : 1), $player, $fromDQ);
    if(!$fromDQ && !$subsequent) AddDecisionQueue("AFTERDIEROLL", $player, "-");
  }
  else
  {
    if(!$subsequent) AfterDieRoll($player);
  }
}

function AfterDieRoll($player)
{
  global $CS_DieRoll, $CS_HighestRoll;
  $roll = GetClassState($player, $CS_DieRoll);
  $skullCrusherIndex = FindCharacterIndex($player, "EVR001");
  if($skullCrusherIndex > -1 && IsCharacterAbilityActive($player, $skullCrusherIndex))
  {
    if($roll == 1) { WriteLog("Skull Crushers was destroyed."); DestroyCharacter($player, $skullCrusherIndex); }
    if($roll == 5 || $roll == 6) { WriteLog("Skull Crushers gives +1 this turn."); AddCurrentTurnEffect("EVR001", $player); }
  }
  if($roll > GetClassState($player, $CS_HighestRoll)) SetClassState($player, $CS_HighestRoll, $roll);
}

function HasGamblersGloves($player)
{
  $gamblersGlovesIndex = FindCharacterIndex($player, "CRU179");
  return $gamblersGlovesIndex != -1 && IsCharacterAbilityActive($player, $gamblersGlovesIndex);
}

function GamblersGloves($player, $origPlayer, $fromDQ)
{
  $gamblersGlovesIndex = FindCharacterIndex($player, "CRU179");
  if(HasGamblersGloves($player))
  {
    if($fromDQ)
    {
      PrependDecisionQueue("ROLLDIE", $origPlayer, "1", 1);
      PrependDecisionQueue("DESTROYCHARACTER", $player, "-", 1);
      PrependDecisionQueue("PASSPARAMETER", $player, $gamblersGlovesIndex, 1);
      PrependDecisionQueue("NOPASS", $player, "-");
      PrependDecisionQueue("YESNO", $player, "if_you_want_to_destroy_Gambler's_Gloves_to_reroll_the_result");
    }
    else
    {
      AddDecisionQueue("YESNO", $player, "if_you_want_to_destroy_Gambler's_Gloves_to_reroll_the_result");
      AddDecisionQueue("NOPASS", $player, "-");
      AddDecisionQueue("PASSPARAMETER", $player, $gamblersGlovesIndex, 1);
      AddDecisionQueue("DESTROYCHARACTER", $player, "-", 1);
      AddDecisionQueue("ROLLDIE", $origPlayer, "1", 1);
    }
  }
}

function IsCharacterAbilityActive($player, $index, $checkGem=false)
{
  $character = &GetPlayerCharacter($player);
  if($checkGem && $character[$index+9] == 0) return false;
  return $character[$index+1] == 2;
}

function GetMultizoneIndicesForTitle($player, $title, $onlyReady=false) {
  $indices=[];
  $char = &GetPlayerCharacter($player);
  $leaderIndex = CharacterPieces();
  if(count($char) > $leaderIndex && CardTitle($char[$leaderIndex]) == $title && (!$onlyReady || $char[$leaderIndex+1] == 2))
    array_push($indices, "MYCHAR-$leaderIndex");
  $allies = SearchAlliesForTitle($player, $title);
  if($allies != "") {
    $allies = explode(",", $allies);
    for($i=0; $i<count($allies); ++$i) {
      $ally = new Ally("MYALLY-$allies[$i]", $player);
      if(!$onlyReady || !$ally->IsExhausted()) array_push($indices, "MYALLY-$allies[$i]");
    }
  }
  return implode(",", $indices);
}

function GetDieRoll($player)
{
  global $CS_DieRoll;
  return GetClassState($player, $CS_DieRoll);
}

function ClearDieRoll($player)
{
  global $CS_DieRoll;
  return SetClassState($player, $CS_DieRoll, 0);
}

//FAB
// function CanPlayAsInstant($cardID, $index=-1, $from="")
// {
//   global $currentPlayer, $CS_NextWizardNAAInstant, $CS_NextNAAInstant, $CS_CharacterIndex, $CS_ArcaneDamageTaken, $CS_NumWizardNonAttack;
//   global $mainPlayer, $CS_PlayedAsInstant;
//   $otherPlayer = $currentPlayer == 1 ? 2 : 1;
//   $cardType = CardType($cardID);
//   $otherCharacter = &GetPlayerCharacter($otherPlayer);
//   if($cardID == "MON034" && SearchItemsForCard("DYN066", $currentPlayer) != "") return true;
//   if(GetClassState($currentPlayer, $CS_NextWizardNAAInstant))
//   {
//     if(ClassContains($cardID, "WIZARD", $currentPlayer) && $cardType == "A") return true;
//   }
//   if(GetClassState($currentPlayer, $CS_NumWizardNonAttack) && ($cardID == "CRU174" || $cardID == "CRU175" || $cardID == "CRU176")) return true;
//   if($currentPlayer != $mainPlayer && ($cardID == "CRU165" || $cardID == "CRU166" || $cardID == "CRU167")) return true;
//   if(GetClassState($currentPlayer, $CS_NextNAAInstant))
//   {
//     if($cardType == "A") return true;
//   }
//   if($cardType == "C" || $cardType == "E" || $cardType == "W")
//   {
//     if($index == -1) $index = GetClassState($currentPlayer, $CS_CharacterIndex);
//     if(SearchCharacterEffects($currentPlayer, $index, "INSTANT")) return true;
//   }
//   if($from == "BANISH")
//   {
//     $banish = GetBanish($currentPlayer);
//     if($index < count($banish))
//     {
//       $mod = explode("-", $banish[$index+1])[0];
//       if(($cardType == "I" && ($mod == "TCL" || $mod == "TT" || $mod == "TCC" || $mod == "NT" || $mod == "MON212")) || $mod == "INST" || $mod == "ARC119") return true;
//     }
//   }
//   if(GetClassState($currentPlayer, $CS_PlayedAsInstant) == "1") return true;
//   if($cardID == "ELE106" || $cardID == "ELE107" || $cardID == "ELE108") { return PlayerHasFused($currentPlayer); }
//   if($cardID == "CRU143") { return GetClassState($otherPlayer, $CS_ArcaneDamageTaken) > 0; }
//   if($from == "ARS" && $cardType == "A" && $currentPlayer != $mainPlayer && PitchValue($cardID) == 3 && (SearchCharacterActive($currentPlayer, "EVR120") || SearchCharacterActive($currentPlayer, "UPR102") || SearchCharacterActive($currentPlayer, "UPR103") || (SearchCharacterActive($currentPlayer, "CRU097") && SearchCurrentTurnEffects($otherCharacter[0] . "-SHIYANA", $currentPlayer) && IsIyslander($otherCharacter[0])))) return true;
//   $isStaticType = IsStaticType($cardType, $from, $cardID);
//   $abilityType = "-";
//   if($isStaticType) $abilityType = GetAbilityType($cardID, $index, $from);
//   if(($cardType == "AR" || ($abilityType == "AR" && $isStaticType)) && IsReactionPhase() && $currentPlayer == $mainPlayer) return true;
//   if(($cardType == "DR" || ($abilityType == "DR" && $isStaticType)) && IsReactionPhase() && $currentPlayer != $mainPlayer && IsDefenseReactionPlayable($cardID, $from)) return true;
//   return false;
// }

function HasLostClass($player)
{
  if(SearchCurrentTurnEffects("UPR187", $player)) return true;//Erase Face
  return false;
}

//FAB
// function ClassOverride($cardID, $player="")
// {
//   global $currentTurnEffects;
//   $cardClass = CardClass($cardID);
//   if ($cardClass == "NONE") $cardClass = "";
//   $otherPlayer = ($player == 1 ? 2 : 1);
//   $otherCharacter = &GetPlayerCharacter($otherPlayer);

//   if(SearchCurrentTurnEffects("UPR187", $player)) return "NONE";//Erase Face
//   if(count($otherCharacter) > 0 && SearchCurrentTurnEffects($otherCharacter[0] . "-SHIYANA", $player)) {
//     if($cardClass != "") $cardClass .= ",";
//     $cardClass .= CardClass($otherCharacter[0]) . ",SHAPESHIFTER";
//   }

//   for($i=0; $i<count($currentTurnEffects); $i+=CurrentTurnEffectPieces())
//   {
//     if($currentTurnEffects[$i+1] != $player) continue;
//     $toAdd = "";
//     switch($currentTurnEffects[$i])
//     {
//       case "MON095": case "MON096": case "MON097":
//       case "EVR150": case "EVR151": case "EVR152":
//       case "UPR155": case "UPR156": case "UPR157": $toAdd = "ILLUSIONIST"; break;
//       default: break;
//     }
//     if($toAdd != "")
//     {
//       if($cardClass != "") $cardClass .= ",";
//       $cardClass .= $toAdd;
//     }
//   }
//   if($cardClass == "") return "NONE";
//   return $cardClass;
// }

function NameOverride($cardID, $player="")
{
  $name = CardName($cardID);
  if(SearchCurrentTurnEffects("OUT183", $player)) $name = "";
  return $name;
}

function DefinedTypesContains($cardID, $type, $player="")
{
  if(!$cardID || $cardID == "" || strlen($cardID) < 3) return "";
  $cardTypes = DefinedCardType($cardID);
  $cardTypes2 = DefinedCardType2Wrapper($cardID);
  return DelimStringContains($cardTypes, $type) || DelimStringContains($cardTypes2, $type);
}

//FAB
// function CardTypeContains($cardID, $type, $player="")
// {
//   $cardTypes = CardTypes($cardID);
//   return DelimStringContains($cardTypes, $type);
// }

// function ClassContains($cardID, $class, $player="")
// {
//   $cardClass = ClassOverride($cardID, $player);
//   return DelimStringContains($cardClass, $class);
// }

function AspectContains($cardID, $aspect, $player="")
{
  $cardAspect = CardAspects($cardID);
  return DelimStringContains($cardAspect, $aspect);
}

function TraitContainsAny($cardID, $traits, $player="", $index=-1) {
  $traitsArr = explode(",", $traits);
  for ($i = 0; $i < count($traitsArr); $i++) {
    if (TraitContains($cardID, $traitsArr[$i], $player, $index)) return true;
  }
  return false;
}

function TraitContainsAll($cardID, $traits, $player="", $index=-1) {
  $traitsArr = explode(",", $traits);
  for ($i = 0; $i < count($traitsArr); $i++) {
    if (!TraitContains($cardID, $traits[$i], $player, $index)) return false;
  }
  return true;
}

function TraitContains($cardID, $trait, $player, $index=-1) {
  $trait = str_replace("_", " ", $trait); //"MZALLCARDTRAITORPASS" and possibly other decision queue options call this function with $trait having been underscoreified, so I undo that here.
  $isBase = CardIDIsBase($cardID);
  $isLeaderSide = CardIDIsLeader($cardID) && LeaderUndeployed($cardID) == "";
  if($index != -1 && !$isLeaderSide && !$isBase) {
    $ally = new Ally("MYALLY-" . $index, $player);

    // // Check for upgrades
    $upgrades = $ally->GetUpgrades();
    for($i=0; $i<count($upgrades); ++$i) {
      switch ($upgrades[$i]) {
        case "7687006104"://Foundling
          if($trait == "Mandalorian") return true;
          break;
        case "0545149763"://Jedi Trials
          if($trait == "Jedi" && count($upgrades) >= 4) return true;
          break;
        default: break;
      }
    }

    if ($ally->IsCloned() && $trait == "Clone") return true;
  }
  $cardTrait = CardTraits($cardID);
  if($trait == "Force" && SearchCurrentTurnEffects("9702812601", $player)){
     WriteLog("Nameless Terror prevented Force Trait");
     return false;
  }
  if($player != 0 && PlayerHasMythosaurActive($player) && CardIDIsLeader($cardID) && $trait == "Mandalorian") {
    return true;
  }
  return DelimStringContains($cardTrait, $trait);
}

function AllyTraitContainsOrUpgradeTraitContains($allyUniqueID, $trait) {
  $ally = new Ally($allyUniqueID);
  $upgrades = $ally->GetUpgrades();
  for($i=0; $i<count($upgrades); ++$i) {
    if (TraitContains($upgrades[$i], $trait, $ally->Controller())) return true;
  }

  return TraitContains($ally->CardID(), $trait,$ally->Controller());
}

function HasKeyword($cardID, $keyword, $player="", $index=-1){
  switch($keyword){
    case "Smuggle": return SmuggleCost($cardID, $player, $index) > -1;
    case "Raid": return RaidAmount($cardID, $player, $index, true) > 0;
    case "Grit": return HasGrit($cardID, $player, $index);
    case "Restore": return RestoreAmount($cardID, $player, $index) > 0;
    case "Bounty": return CollectBounty($player, $cardID, $cardID, false, $player, true) > 0; // Since we don't have information about "exhausted" and "owner," this data may be imprecise in very rare cases.
    case "Overwhelm": return HasOverwhelm($cardID, $player, $index);
    case "Saboteur": return HasSaboteur($cardID, $player, $index);
    case "Shielded": return HasShielded($cardID, $player, $index);
    case "Sentinel": return HasSentinel($cardID, $player, $index);
    case "Ambush": return HasAmbush($cardID, $player, $index,"");
    case "Coordinate": return HasCoordinate($cardID, $player, $index);
    case "Exploit": return ExploitAmount($cardID, $player, true) > 0;
    case "Piloting": return PilotingCost($cardID) > -1;
    case "Hidden": return HasHidden($cardID, $player, $index);
    case "Any":
      return SmuggleCost($cardID, $player, $index) > -1 ||
        RaidAmount($cardID, $player, $index, true) > 0 ||
        HasGrit($cardID, $player, $index) ||
        RestoreAmount($cardID, $player, $index) > 0 ||
        CollectBounty($player, $cardID, $cardID, false, $player, true) > 0 || // Since we don't have information about "exhausted" and "owner," this data may be imprecise in very rare cases.
        HasOverwhelm($cardID, $player, $index) ||
        HasSaboteur($cardID, $player, $index) ||
        HasShielded($cardID, $player, $index) ||
        HasSentinel($cardID, $player, $index) ||
        HasAmbush($cardID, $player, $index, "") ||
        HasCoordinate($cardID, $player, $index) ||
        ExploitAmount($cardID, $player, true) > 0 ||
        PilotingCost($cardID) > -1 ||
        HasHidden($cardID, $player, $index);
    default: return false;
  }
}

function HasKeywordWhenPlayed($cardID) {
  switch($cardID) {
    case "6059510270"://Obi-Wan Kenobi (Protective Padawan)
      return true;
    default:
      return false;
  }
}

function ArenaContains($cardID, $arena, Ally $ally = null)
{
  $cardArena = CardArenas($cardID);
  if ($ally != null) {
    return $ally->CurrentArena() == $arena;
  }
  return DelimStringContains($cardArena, $arena);
}

function SubtypeContains($cardID, $subtype, $player="")
{
  $cardSubtype = CardSubtype($cardID);
  return DelimStringContains($cardSubtype, $subtype);
}

//FAB
// function ElementContains($cardID, $element, $player="")
// {
//   $cardElement = CardElement($cardID);
//   return DelimStringContains($cardElement, $element);
// }

function CardNameContains($cardID, $name, $player="")
{
  $cardName = NameOverride($cardID, $player);
  return DelimStringContains($cardName, $name);
}

//FAB
// function TalentOverride($cardID, $player="")
// {
//   global $currentTurnEffects;
//   $cardTalent = CardTalent($cardID);
//   //CR 2.2.1 - 6.3.6. Continuous effects that remove a property, or part of a property, from an object do not remove properties, or parts of properties, that were added by another effect.
//   if(SearchCurrentTurnEffects("UPR187", $player)) $cardTalent = "NONE";
//   for($i=0; $i<count($currentTurnEffects); $i+=CurrentTurnEffectPieces())
//   {
//     $toAdd = "";
//     if($currentTurnEffects[$i+1] != $player) continue;
//     switch($currentTurnEffects[$i])
//     {
//       case "UPR060": case "UPR061": case "UPR062": $toAdd = "DRACONIC";
//       default: break;
//     }
//     if($toAdd != "")
//     {
//       if($cardTalent == "NONE") $cardTalent = "";
//       if($cardTalent != "") $cardTalent .= ",";
//       $cardTalent .= $toAdd;
//     }
//   }
//   return $cardTalent;
// }

// function TalentContains($cardID, $talent, $player="")
// {
//   $cardTalent = TalentOverride($cardID, $player);
//   return DelimStringContains($cardTalent, $talent);
// }

//parameters: (comma delimited list of card ids, , )
function RevealCards($cards, $player="", $from="HAND")
{
  global $currentPlayer;
  if($player == "") $player = $currentPlayer;
  if(!CanRevealCards($player)) return false;
  $cardArray = explode(",", $cards);
  $string = "";
  for($i=count($cardArray)-1; $i>=0; --$i)
  {
    if($string != "") $string .= ", ";
    $string .= CardLink($cardArray[$i], $cardArray[$i]);
    //AddEvent("REVEAL", $cardArray[$i]);
    OnRevealEffect($player, $cardArray[$i], $from, $i);
  }
  $string .= (count($cardArray) == 1 ? " is" : " are");
  $string .= " revealed.";
  WriteLog($string);
  return true;
}

function OnRevealEffect($player, $cardID, $from, $index)
{
  switch($cardID)
  {
    default: break;
  }
}

function IsEquipUsable($player, $index)
{
  $character = &GetPlayerCharacter($player);
  if($index >= count($character) || $index < 0) return false;
  return $character[$index + 1] == 2;
}


function UndestroyCurrentWeapon()
{
  global $combatChainState, $CCS_WeaponIndex, $mainPlayer;
  $index = $combatChainState[$CCS_WeaponIndex];
  $char = &GetPlayerCharacter($mainPlayer);
  $char[$index+7] = "0";
}

function DestroyCurrentWeapon()
{
  global $combatChainState, $CCS_WeaponIndex, $mainPlayer;
  $index = $combatChainState[$CCS_WeaponIndex];
  $char = &GetPlayerCharacter($mainPlayer);
  $char[$index+7] = "1";
}

function AttackDestroyed($attackID)
{
  global $mainPlayer, $combatChainState, $CCS_GoesWhereAfterLinkResolves;
  $type = CardType($attackID);
  $character = &GetPlayerCharacter($mainPlayer);
  switch($attackID)
  {

    default: break;
  }
  AttackDestroyedEffects($attackID);
}

function AttackDestroyedEffects($attackID)
{
  global $currentTurnEffects, $mainPlayer;
  for($i=0; $i<count($currentTurnEffects); $i+=CurrentTurnEffectPieces())
  {
    switch($currentTurnEffects[$i])
    {
      default: break;
    }
  }
}

function CloseCombatChain($chainClosed="true")
{
  global $turn, $currentPlayer, $mainPlayer, $combatChainState, $CCS_AttackTarget;
  AddLayer("FINALIZECHAINLINK", $mainPlayer, $chainClosed);
  $turn[0] = "M";
  $currentPlayer = $mainPlayer;
  $combatChainState[$CCS_AttackTarget] = "NA";
}

function UndestroyCharacter($player, $index)
{
  $char = &GetPlayerCharacter($player);
  $char[$index+1] = 2;
  $char[$index+4] = 0;
}

function DestroyCharacter($player, $index)
{
  $char = &GetPlayerCharacter($player);
  $char[$index+1] = 0;
  $char[$index+4] = 0;
  $cardID = $char[$index];
  if($char[$index+6] == 1) RemoveCombatChain(GetCombatChainIndex($cardID, $player));
  $char[$index+6] = 0;
  AddGraveyard($cardID, $player, "CHAR");
  CharacterDestroyEffect($cardID, $player);
  return $cardID;
}

function RemoveCharacter($player, $index)
{
  $char = &GetPlayerCharacter($player);
  $cardID = $char[$index];
  for($i=$index+CharacterPieces()-1; $i>=$index; --$i)
  {
    unset($char[$i]);
  }
  $char = array_values($char);
  return $cardID;
}

function AddDurabilityCounters($player, $amount=1)
{
  AddDecisionQueue("PASSPARAMETER", $player, $amount);
  AddDecisionQueue("SETDQVAR", $player, "0");
  AddDecisionQueue("MULTIZONEINDICES", $player, "MYCHAR:type=WEAPON");
  AddDecisionQueue("SETDQCONTEXT", $player, "Choose a weapon to add durability counter" . ($amount > 1 ? "s" : ""), 1);
  AddDecisionQueue("CHOOSEMULTIZONE", $player, "<-", 1);
  AddDecisionQueue("MZOP", $player, "ADDDURABILITY", 1);
}

function RemoveCombatChain($index)
{
  global $combatChain;
  if($index < 0) return;
  for($i = CombatChainPieces() - 1; $i >= 0; --$i) {
    unset($combatChain[$index + $i]);
  }
  $combatChain = array_values($combatChain);
}

function GainActionPoints($amount=1, $player=0)
{
  global $actionPoints, $mainPlayer, $currentPlayer;
  if($player == 0) $player = $currentPlayer;
  if($player == $mainPlayer) $actionPoints += $amount;
}

function AddCharacterUses($player, $index, $numToAdd)
{
  $character = &GetPlayerCharacter($player);
  if($character[$index+1] == 0) return;
  $character[$index+1] = 2;
  $character[$index+5] += $numToAdd;
}

function HaveUnblockedEquip($player)
{
  $char = &GetPlayerCharacter($player);
  for($i=CharacterPieces(); $i<count($char); $i+=CharacterPieces())
  {
    if($char[$i+1] == 0) continue;//If broken
    if($char[$i+6] == 1) continue;//On combat chain
    if(CardType($char[$i]) != "E") continue;
    if(BlockValue($char[$i]) == -1) continue;
    return true;
  }
  return false;
}

function NumEquipBlock()
{
  global $combatChain, $defPlayer;
  $numEquipBlock = 0;
  for($i=CombatChainPieces(); $i<count($combatChain); $i+=CombatChainPieces())
  {
    if(CardType($combatChain[$i]) == "E" && $combatChain[$i + 1] == $defPlayer) ++$numEquipBlock;
  }
  return $numEquipBlock;
}

function CanConfirmPhase($phase) {
  global $turn;
  switch ($phase) {
    case "PARTIALMULTIDAMAGEMULTIZONE":
    case "MAYMULTIDAMAGEMULTIZONE":
    case "MULTIDAMAGEMULTIZONE":
    case "INDIRECTDAMAGEMULTIZONE":
    case "PARTIALMULTIHEALMULTIZONE":
    case "MAYMULTIHEALMULTIZONE":
    case "MULTIHEALMULTIZONE":
      $parsedParams = ParseDQParameter($turn[0], $turn[1], $turn[2]);
      $counterLimit = $parsedParams["counterLimit"];
      $allies = $parsedParams["allies"];
      $characters = $parsedParams["characters"];
      $totalCounters = 0;

      foreach ($allies as $ally) {
        $ally = new Ally($ally);
        $totalCounters += $ally->Counters();
      }

      foreach ($characters as $character) {
        $character = new Character($character);
        $totalCounters += $character->Counters();
      }

      if ($totalCounters > 0 && str_starts_with($phase, "PARTIAL")) {
        return 1;
      }

      return $totalCounters == $counterLimit;
    default:
      return 0;
  }
}

function CanPassPhase($phase)
{
  global $combatChainState, $CCS_RequiredEquipmentBlock, $currentPlayer, $turn;
  global $CS_CantSkipPhase;
  if(GetClassState($currentPlayer, $CS_CantSkipPhase) == 1) return 0;
  if($phase == "B" && HaveUnblockedEquip($currentPlayer) && NumEquipBlock() < $combatChainState[$CCS_RequiredEquipmentBlock]) return false;
  switch($phase)
  {
    case "P": return 0;
    case "CHOOSEDECK": return 0;
    case "HANDTOPBOTTOM": return 0;
    case "CHOOSECOMBATCHAIN": return 0;
    case "CHOOSECHARACTER": return 0;
    case "CHOOSEHAND": return 0;
    case "CHOOSEHANDCANCEL": return 0;
    case "MULTICHOOSESEARCHTARGETS": return 0;
    case "MULTICHOOSEDISCARD": return 0;
    case "MULTICHOOSETHEIRDISCARD": return 0;
    case "CHOOSEDISCARDCANCEL": return 0;
    case "CHOOSEARCANE": return 0;
    case "CHOOSEARSENAL": return 0;
    case "CHOOSEDISCARD": return 0;
    case "MULTICHOOSEHAND": return 0;
    case "MULTICHOOSEUNIT": return 0;
    case "MULTICHOOSETHEIRUNIT": return 0;
    case "MULTICHOOSEOURUNITS": return 0;
    case "MULTICHOOSEMULTIZONE": return 0;
    case "MULTICHOOSEMYUNITSANDBASE": return 0;
    case "MULTICHOOSETHEIRUNITSANDBASE": return 0;
    case "MULTICHOOSEOURUNITSANDBASE": return 0;
    case "CHOOSEMULTIZONE": return 0;
    case "CHOOSEBANISH": return 0;
    case "BUTTONINPUTNOPASS": return 0;
    case "CHOOSEFIRSTPLAYER": return 0;
    case "MULTICHOOSEDECK": return 0;
    case "CHOOSEPERMANENT": return 0;
    case "MULTICHOOSETEXT": return 0; // Deprecated, use CHOOSEOPTION instead
    case "CHOOSEOPTION": return 0;
    case "CHOOSEMYSOUL": return 0;
    case "OVER": return 0;
    case "YESNO": return 0;
    case "INDIRECTDAMAGEMULTIZONE": return 0;
    case "MULTIDAMAGEMULTIZONE": return 0;
    case "MULTIHEALMULTIZONE": return 0;
    case "PARTIALMULTIDAMAGEMULTIZONE":
    case "MAYMULTIDAMAGEMULTIZONE":
    case "PARTIALMULTIHEALMULTIZONE":
    case "MAYMULTIHEALMULTIZONE":
      $parsedParams = ParseDQParameter($turn[0], $turn[1], $turn[2]);
      $allies = $parsedParams["allies"];
      $characters = $parsedParams["characters"];

      foreach ($allies as $ally) {
        $ally = new Ally($ally);
        if ($ally->Counters() > 0) {
          return 0;
        }
      }

      foreach ($characters as $character) {
        $character = new Character($character);
        if ($character->Counters() > 0) {
          return 0;
        }
      }

      return 1;
    default: return 1;
  }
}

function ResolveGoAgain($cardID, $player, $from)
{
  global $actionPoints;
  ++$actionPoints;
}

function PitchDeck($player, $index)
{
  $deck = &GetDeck($player);
  $cardID = RemovePitch($player, $index);
  $deck[] = $cardID;
}

function GetUniqueId()
{
  global $permanentUniqueIDCounter;
  ++$permanentUniqueIDCounter;
  return $permanentUniqueIDCounter;
}

function IsHeroAttackTarget()
{
  $target = explode("-", GetAttackTarget());
  return $target[0] == "THEIRCHAR";
}

function IsAllyAttackTarget()
{
  $target = GetAttackTarget();
  if($target == "NA") return false;
  $targetArr = explode("-", $target);
  return $targetArr[0] == "THEIRALLY";
}

function AttackIndex()
{
  global $combatChainState, $CCS_WeaponIndex;
  return $combatChainState[$CCS_WeaponIndex];
}

function IsAttackTargetRested()
{
  global $defPlayer;
  $target = GetAttackTarget();
  $mzArr = explode("-", $target);
  if($mzArr[0] == "ALLY" || $mzArr[0] == "MYALLY" || $mzArr[0] == "THEIRALLY")
  {
    $allies = &GetAllies($defPlayer);
    return $allies[$mzArr[1]+1] == 1;
  }
  else
  {
    $char = &GetPlayerCharacter($defPlayer);
    return $char[1] == 1;
  }
}

function IsSpecificAllyAttackTarget($player, $index)
{
  $mzTarget = GetAttackTarget();
  $mzArr = explode("-", $mzTarget);
  if($mzArr[0] == "ALLY" || $mzArr[0] == "MYALLY" || $mzArr[0] == "THEIRALLY")
  {
    return $index == intval($mzArr[1]);
  }
  return false;
}

function IsAllyAttacking()
{
  global $combatChain;
  if(count($combatChain) == 0) return false;
  return IsAlly($combatChain[0]);
}

function IsSpecificAllyAttacking($player, $index)
{
  global $combatChain, $combatChainState, $CCS_WeaponIndex, $mainPlayer;
  if(count($combatChain) == 0) return false;
  if($mainPlayer != $player) return false;
  $weaponIndex = intval($combatChainState[$CCS_WeaponIndex]);
  if($weaponIndex == -1) return false;
  if($weaponIndex != $index) return false;
  if(!IsAlly($combatChain[0])) return false;
  return true;
}

function TargetAlly() {
  global $mainPlayer, $CS_LayerTarget;
  $target = GetClassState($mainPlayer, $CS_LayerTarget);
  $ally = new Ally($target);
  return $ally;
}

function AttackerAlly() {
  global $mainPlayer;
  $attackerMZ = AttackerMZID($mainPlayer);
  $ally = new Ally($attackerMZ, $mainPlayer);
  return $ally;
}

function AttackerMZID($player)
{
  global $combatChainState, $CCS_WeaponIndex, $mainPlayer;
  if($player == $mainPlayer) return "MYALLY-" . $combatChainState[$CCS_WeaponIndex];
  else return "THEIRALLY-" . $combatChainState[$CCS_WeaponIndex];
}

function ClearAttacker() {
  global $combatChainState, $CCS_WeaponIndex;
  $combatChainState[$CCS_WeaponIndex] = -1;
}

function DefendingPlayerHasUnits($arena="") {
  global $defPlayer;
  return SearchCount(SearchAllies($defPlayer, arena:$arena)) > 0;
}

function IsSpecificAuraAttacking($player, $index)
{
  global $combatChain, $combatChainState, $CCS_WeaponIndex, $mainPlayer;
  if (count($combatChain) == 0) return false;
  if ($mainPlayer != $player) return false;
  $weaponIndex = intval($combatChainState[$CCS_WeaponIndex]);
  if ($weaponIndex == -1) return false;
  if ($weaponIndex != $index) return false;
  if (!DelimStringContains(CardSubtype($combatChain[0]), "Aura")) return false;
  return true;
}

function RevealMemory($player)
{
  $memory = &GetMemory($player);
  $toReveal = "";
  for($i=0; $i<count($memory); $i += MemoryPieces())
  {
    if($toReveal != "") $toReveal .= ",";
    $toReveal .= $memory[$i];
  }
  return RevealCards($toReveal, $player, "MEMORY");
}

  function CanRevealCards($player)
  {
    return true;
  }

  function BaseAttackModifiers($attackValue)
  {
    global $combatChainState, $CCS_LinkBaseAttack, $currentTurnEffects, $mainPlayer;
    for($i=0; $i<count($currentTurnEffects); $i+=CurrentTurnEffectPieces())
    {
      if($currentTurnEffects[$i+1] != $mainPlayer) continue;
      if(!IsCombatEffectActive($currentTurnEffects[$i])) continue;
      switch($currentTurnEffects[$i])
      {
        case "EVR094": case "EVR095": case "EVR096": $attackValue = ceil($attackValue/2); break;
        default: break;
      }
    }
    return $attackValue;
  }

  function GetDefaultLayerTarget()
  {
    global $layers, $combatChain, $currentPlayer;
    if(count($combatChain) > 0) return $combatChain[0];
    if(count($layers) > 0)
    {
      for($i=count($layers)-LayerPieces(); $i>=0; $i-=LayerPieces())
      {
        if($layers[$i+1] != $currentPlayer) return $layers[$i];
      }
    }
    return "-";
  }

//FAB
// function GetDamagePreventionIndices($player)
// {
//   $rv = "";
//   $auras = &GetAuras($player);
//   $indices = "";
//   for($i=0; $i<count($auras); $i+=AuraPieces())
//   {
//     if(AuraDamagePreventionAmount($player, $i) > 0)
//     {
//       if($indices != "") $indices .= ",";
//       $indices .= $i;
//     }
//   }
//   $mzIndices = SearchMultiZoneFormat($indices, "MYAURAS");

//   $char = &GetPlayerCharacter($player);
//   $indices = "";
//   for($i=0; $i<count($char); $i+=CharacterPieces())
//   {
//     if($char[$i+1] != 0 && WardAmount($char[$i]) > 0)
//     {
//       if($indices != "") $indices .= ",";
//       $indices .= $i;
//     }
//   }
//   $indices = SearchMultiZoneFormat($indices, "MYCHAR");
//   $mzIndices = CombineSearches($mzIndices, $indices);

//   $ally = &GetAllies($player);
//   $indices = "";
//   for($i=0; $i<count($ally); $i+=AllyPieces())
//   {
//     if($ally[$i+1] != 0 && WardAmount($ally[$i]) > 0)
//     {
//       if($indices != "") $indices .= ",";
//       $indices .= $i;
//     }
//   }
//   $indices = SearchMultiZoneFormat($indices, "MYALLY");
//   $mzIndices = CombineSearches($mzIndices, $indices);
//   $rv = $mzIndices;
//   return $rv;
// }

function GetDamagePreventionTargetIndices()
{
  global $combatChain, $currentPlayer;
  $otherPlayer = $currentPlayer == 1 ? 2 : 1;
  $rv = "";

  $rv = SearchMultizone($otherPlayer, "LAYER");
  if (count($combatChain) > 0) {
    if ($rv != "") $rv .= ",";
    $rv .= "CC-0";
  }
  if (SearchLayer($otherPlayer, "W") == "" && (count($combatChain) == 0 || CardType($combatChain[0]) != "W")) {
    $theirWeapon = SearchMultiZoneFormat(SearchCharacter($otherPlayer, type: "W"), "THEIRCHAR");
    $rv = CombineSearches($rv, $theirWeapon);
  }
  $theirAllies = SearchMultiZoneFormat(SearchAllies($otherPlayer), "THEIRALLY");
  $rv = CombineSearches($rv, $theirAllies);
  // $theirAuras = SearchMultiZoneFormat(SearchAura($otherPlayer), "THEIRAURAS");//FAB
  // $rv = CombineSearches($rv, $theirAuras);
  $theirHero = SearchMultiZoneFormat(SearchCharacter($otherPlayer, type: "C"), "THEIRCHAR");
  $rv = CombineSearches($rv, $theirHero);
  return $rv;
}

function SameWeaponEquippedTwice()
{
  global $mainPlayer;
  $char = &GetPlayerCharacter($mainPlayer);
  $weaponIndex = explode(",", SearchCharacter($mainPlayer, "W"));
  if (count($weaponIndex) > 1 && $char[$weaponIndex[0]] == $char[$weaponIndex[1]]) return true;
  return false;
}

function IgnoreAspectPenalty($cardID, $player, $reportMode) {
  global $myClassState, $CS_NumClonesPlayed, $CS_LayerTarget, $currentTurnEffects;
  $ignore = false;
  if(TraitContains($cardID, "Spectre", $player)) {
    $ignore = !LeaderAbilitiesIgnored() && (HeroCard($player) == "7440067052" || SearchAlliesForCard($player, "80df3928eb") != ""); //Hera Syndulla (Spectre Two)
  }
  if($ignore) return true;
  if (TraitContains($cardID, "Clone", $player)) {
    $ignore = (SearchAlliesForCard($player, "1386874723") != "" && GetClassState($player, $CS_NumClonesPlayed) < 1) //Omega (Part of the Squad)
      || (!LeaderAbilitiesIgnored() && (HeroCard($player) == "2742665601" || SearchAlliesForCard($player, "f05184bd91") != "")); //Nala Se (Kaminoan Prime Minister)
  }
  if($ignore) return true;
  if(TraitContains($cardID, "Lightsaber", $player)) {
    $findGrievous = SearchAlliesForCard($player, "4776553531");//General Grievous  (Trophy Collector)
    $ignore = $findGrievous != "" && ($reportMode || $myClassState[$CS_LayerTarget] == "MYALLY-$findGrievous");
  }
  if($ignore) return true;
  for($i=0;$i<count($currentTurnEffects);$i+=CurrentTurnEffectPieces()) {
    if($currentTurnEffects[$i+1] != $player) continue;
    switch($currentTurnEffects[$i]) {
      case "7895170711"://A Fine Addition
        RemoveCurrentTurnEffect($i);
        return true;
      case "8536024453"://Anakin Skywalker leader
      case "7d9f8bcb9b"://Anakin Skywalker leader unit
        RemoveCurrentTurnEffect($i);
        return true;
      default: break;
    }
  }

  return false;
}

function SelfCostModifier($cardID, $from, $reportMode=false)
{
  global $currentPlayer, $layers;
  global $CS_LastAttack, $CS_LayerTarget, $CS_NumClonesPlayed, $CS_PlayedAsUpgrade, $CS_NumWhenDefeatedPlayed, $CS_NumCreaturesPlayed;
  global $CS_NumUnitsPlayed;

  $modifier = 0;
  //Aspect Penalty
  $playerAspects = PlayerAspects($currentPlayer);
  $penalty = 0;
  if(!IgnoreAspectPenalty($cardID, $currentPlayer, $reportMode)) {
    $cardAspects = CardAspects($cardID);
    //Manually changing the aspects of cards played with smuggle that have different aspect requirements for smuggle.
    //Not a great solution; ideally we could define a whole smuggle ability in one place.
    if ($from == "RESOURCES") {
      $tech = SearchAlliesForCard($currentPlayer, "3881257511");
      if($tech != "") {
        $ally = new Ally("MYALLY-" . $tech, $currentPlayer);
        $techOnBoard = !$ally->LostAbilities();
      } else {
        $techOnBoard = false;
      }
      switch($cardID) {
        case "5169472456"://Chewbacca (Pykesbane)
          if(!$techOnBoard || $playerAspects["Aggression"] != 0) {
            //if tech is here and player is not aggression, tech will always be cheaper than aggression cost
            $cardAspects = "Heroism,Aggression";
          }
          break;
        case "9871430123"://Sugi
          //vigilance is always cheaper than vigilance,vigilance, do not use tech passive
          $cardAspects = "Vigilance";
          break;
        case "5874342508"://Hotshot DL-44 Blaster
          if(!$techOnBoard || ($playerAspects["Cunning"] != 0 && $playerAspects["Aggression"] == 0)) {
            //if tech is here, cunning smuggle is better only if player is cunning and not aggression
            $cardAspects = "Cunning";
          }
          break;
        case "4002861992"://DJ (Blatant Thief)
          if(!$techOnBoard) {
            //cunning will always be cheaper than cunning+cunning, do not add a cunning if tech is here
            $cardAspects = "Cunning,Cunning";
          }
          break;
        case "3010720738"://Tobias Beckett
          if(!$techOnBoard || $playerAspects["Vigilance"] != 0) {
            //if tech is here and player is not vigilance, tech will always be cheaper than vigilance cost
            $cardAspects = "Vigilance";
          }
          break;
        default: break;
      }
    }
    if($from == "HAND" && PilotingCost($cardID) > -1) {
      switch($cardID) {
        case "6421006753"://The Mandalorian
          if($reportMode) $cardAspects = "Cunning";
          else $cardAspects = GetClassState($currentPlayer, $CS_PlayedAsUpgrade) == "1" ? "Cunning" : "Cunning,Cunning";
          break;
      }
    }
    if($cardAspects != "") {
      $aspectArr = explode(",", $cardAspects);
      for($i=0; $i<count($aspectArr); ++$i)
      {
        --$playerAspects[$aspectArr[$i]];
        if($playerAspects[$aspectArr[$i]] < 0) {
          //We have determined that the player is liable for an aspect penalty
          //Now we need to determine if they are exempt
          switch($cardID) {
            case "6263178121"://Kylo Ren (Killing the Past)
              if($aspectArr[$i] != "Villainy" || !ControlsNamedCard($currentPlayer, "Rey")) ++$penalty;
              break;
            case "0196346374"://Rey (Keeping the Past)
              if($aspectArr[$i] != "Heroism" || !ControlsNamedCard($currentPlayer, "Kylo Ren")) ++$penalty;
              break;
            default:
              ++$penalty;
              break;
          }
        }
      }
      $modifier += $penalty * 2;
    }
  }
  //Self Cost Modifier
  switch($cardID) {
    case "2585318816"://Resolute
      $modifier -= floor(GetBaseDamage($currentPlayer)/5);
      break;
    case "1446471743"://Force Choke
      if(SearchCount(SearchAllies($currentPlayer, trait:"Force")) > 0) $modifier -= 1;
      break;
    case "7884488904"://For The Republic
      if(SearchCount(SearchAllies($currentPlayer, trait:"Republic")) >= 3) $modifier -= 2;
      break;
    case "4111616117"://Volunteer Soldier
      if(SearchCount(SearchAllies($currentPlayer, trait:"Trooper")) > 0) $modifier -= 1;
      break;
    case "6905327595"://Reputable Hunter
      $otherPlayer = $currentPlayer == 1 ? 2 : 1;
      $theirAllies = &GetAllies($otherPlayer);
      $hasBounty = false;
      for($i=0; $i<count($theirAllies) && !$hasBounty; $i+=AllyPieces())
      {
        $theirAlly = new Ally("MYALLY-" . $i, $otherPlayer);
        if($theirAlly->HasBounty()) { $hasBounty = true; $modifier -= 1; }
      }
      break;
    case "7212445649"://Bravado
      global $CS_NumAlliesDestroyed;
      $otherPlayer = $currentPlayer == 1 ? 2 : 1;
      if(GetClassState($otherPlayer, $CS_NumAlliesDestroyed) > 0) $modifier -= 2;
      break;
    case "1087522061"://AT-DP Occupier
      $modifier -= SearchCount(SearchAllies(1, arena: "Ground", damagedOnly: true));
      $modifier -= SearchCount(SearchAllies(2, arena: "Ground", damagedOnly: true));
      break;
    case "8380936981"://Jabba's Rancor
      if(ControlsNamedCard($currentPlayer, "Jabba the Hutt")) $modifier -= 1;
      break;
    case "6238512843"://Republic Defense Carrier
      $otherPlayer = $currentPlayer == 1 ? 2 : 1;
      $modifier -= SearchCount(SearchAllies($otherPlayer));
      break;
    case "2443835595"://Republic Attack Pod
      if(SearchCount(SearchAllies($currentPlayer)) >= 3) $modifier -= 1;
      break;
    //Jump to Lightspeed
    case "1356826899"://Home One
      $otherPlayer = $currentPlayer == 1 ? 2 : 1;
      if(SearchCount(SearchAllies($otherPlayer, arena:"Space")) >= 3) $modifier -= 3;
      break;
    case "3711891756"://Red Leader
      $modifier -= CountPilotUnitsAndPilotUpgrades($currentPlayer);
      break;
    case "9958088138"://Invincible
      $controlsSeparatist = CountUniqueAlliesOfTrait($currentPlayer, "Separatist") > 0;
      $controlsSeparatist = $controlsSeparatist || SearchCount(SearchCharacter($currentPlayer, trait:"Separatist")) > 0;
      $controlsSeparatist = $controlsSeparatist || SearchUpgrades($currentPlayer, trait:"Separatist", uniqueOnly:true) > 0;
      $modifier -= $controlsSeparatist ? 1 : 0;
      break;
    case "6576881465"://Decimator of Dissidents
      global $CS_NumIndirectDamageGiven;
      if(GetClassState($currentPlayer, $CS_NumIndirectDamageGiven) > 0) $modifier -= 1;
      break;
    //Legends of the Force
    case "6980075962"://Size Matters Not
      if (SearchCount(SearchAllies($currentPlayer, trait:"Force")) > 0) $modifier -= 1;
      break;
    default: break;
  }
  //Target cost modifier
  if(count($layers) > 0) {
    $mzIndex = GetClassState($currentPlayer, $CS_LayerTarget);
    $targetID = GetMZCard($currentPlayer, $mzIndex);
  } else {
    if(SearchAlliesForCard($currentPlayer, "4166047484") != "") $targetID = "4166047484";
    else if(SearchAlliesForCard($currentPlayer, "fb7af4616c") != "") $targetID = "fb7af4616c";
    else if(SearchAlliesForCard($currentPlayer, "4776553531") != "") $targetID = "4776553531";
    else if($cardID == "3141660491") $targetID = "4088c46c4d";
    else $targetID = "";
  }
  if(DefinedTypesContains($cardID, "Upgrade", $currentPlayer)) {
    if($targetID == "4166047484") $modifier -= 1;//Guardian of the Whills
    if($cardID == "0875550518" && ($targetID == "fb7af4616c" || $targetID == "4776553531")) $modifier -= 2;//Grievous's Wheel Bike
    if($cardID == "3141660491" && $targetID != "" && $penalty > 0) {//The Darksaber
      $isMando = TraitContains($targetID, "Mandalorian", $currentPlayer, isset($mzIndex) && $mzIndex != "-" ? explode("-", $mzIndex)[1] : -1);
      if($isMando) {
        $modifier -= $penalty * 2;
      }
    }
  }
  //Opponent ally cost modifier
  $otherPlayer = $currentPlayer == 1 ? 2 : 1;
  $allies = &GetAllies($otherPlayer);
  for($i=0; $i<count($allies); $i+=AllyPieces())
  {
    if($allies[$i+1] == 0) continue;
    $allyUniqueID = $allies[$i+5];
    switch($allies[$i]) {
      case "9412277544"://Del Meeko
        if(DefinedTypesContains($cardID, "Event", $currentPlayer)) $modifier += 1;
        break;
      case "3503494534"://Regional Governor
        $turnEffect = GetCurrentTurnEffects("3503494534", $currentPlayer, uniqueID:$allyUniqueID);
        if ($turnEffect != null) {
          $cardTitle = GamestateUnsanitize(explode("_", $turnEffect[0])[1]);

          if (CardTitle($cardID) == $cardTitle) {
            $modifier += 999;
          }
        }
        break;
      case "7964782056"://Qi'Ra unit
        $turnEffect = GetCurrentTurnEffects("7964782056", $currentPlayer, uniqueID:$allyUniqueID);
        if ($turnEffect != null) {
          $cardTitle = GamestateUnsanitize(explode("_", $turnEffect[0])[1]);

          if (CardTitle($cardID) == $cardTitle) {
            $modifier += 3;
          }
        }
        break;
      default: break;
    }
  }
  //Death Star Plans
  if(GetClassState($currentPlayer, $CS_NumUnitsPlayed) == 0
      && SearchUpgradesForCard($currentPlayer, "7501988286") != ""
      && DefinedCardType($cardID) == "Unit"
      && GetClassState($currentPlayer, $CS_PlayedAsUpgrade) == 0)
    $modifier -= SearchCount(SearchUpgradesForCard($currentPlayer, "7501988286"));
  //My ally cost modifier
  $allies = &GetAllies($currentPlayer);
  for($i=0; $i<count($allies); $i+=AllyPieces())
  {
    $ally = new Ally("MYALLY-" . $i, $currentPlayer);
    if($ally->LostAbilities()) continue;
    if($allies[$i+1] == 0) continue;
    switch($allies[$i]) {
      //Shadows of the Galaxy
      case "5035052619"://Jabba the Hutt
        if(DefinedTypesContains($cardID, "Event", $currentPlayer) && TraitContains($cardID, "Trick", $currentPlayer)) $modifier -= 1;
        break;
      //Jump to Lightspeed
      case "649c6a9dbd"://Admiral Piett
        if(TraitContains($cardID, "Capital Ship", $currentPlayer)) $modifier -= 2;
        break;
      case "6311662442"://Director Krennic
        if(GetClassState($currentPlayer, $CS_NumWhenDefeatedPlayed) == 0 && HasWhenDestroyed($cardID)) $modifier -= 1;
        break;
      case "0728753133"://The Starhawk
        if($reportMode) $modifier -= (CardCost($cardID) + $modifier);//TODO: find a better way to check potential costs
        break;
      case "4945479132"://Malakili
        if(GetClassState($currentPlayer, $CS_NumCreaturesPlayed) == 0 && TraitContains($cardID, "Creature", $currentPlayer)) $modifier -= 1;
        break;
      default: break;
    }
  }
  return $modifier;
}

function PlayerAspects($player)
{
  $char = &GetPlayerCharacter($player);
  $aspects = [];
  $aspects["Vigilance"] = 0;
  $aspects["Command"] = 0;
  $aspects["Aggression"] = 0;
  $aspects["Cunning"] = 0;
  $aspects["Heroism"] = 0;
  $aspects["Villainy"] = 0;
  for($i=0; $i<count($char); $i+=CharacterPieces())
  {
    $cardAspects = explode(",", CardAspects($char[$i]));
    if($cardAspects[0] == "") continue;
    for($j=0; $j<count($cardAspects); ++$j) {
      ++$aspects[$cardAspects[$j]];
    }

    // Special case //TODO: look into Twin Suns rules around Flipatine leader
    if ($char[$i] == '0026166404') { //Chancellor Palpatine Leader
      $aspects["Villainy"] = 0;
    } else if ($char[$i] == 'ad86d54e97') { //Darth Sidious Leader
      $aspects["Heroism"] = 0;
    }
  }

  $allies = &GetAllies($player);
  $leaderIndices = explode(",", SearchAllies($player, definedType:"Leader"));
  if($leaderIndices[0] != "") {
    for($i=0; $i<count($leaderIndices); ++$i) {
      $cardAspects = explode(",", CardAspects($allies[$leaderIndices[$i]]));
      for($j=0; $j<count($cardAspects); ++$j) {
        ++$aspects[$cardAspects[$j]];
      }
    }
  }
  //check Leader upgrades for aspects
  for($i=0; $i<count($allies); $i+=AllyPieces())
  {
    $ally = new Ally("MYALLY-" . $i, $player);
    $allyUpgrades = $ally->GetUpgrades();
    for($j=0; $j<count($allyUpgrades); ++$j)
    {
      if(CardIDIsLeader($allyUpgrades[$j])) {
        $cardAspects = explode(",", CardAspects($allyUpgrades[$j]));
        for($k=0; $k<count($cardAspects); ++$k) {
          ++$aspects[$cardAspects[$k]];
        }
      }
    }
  }
  //check they have a leader upgrade for your aspects
  $otherPlayer = $player == 1 ? 2 : 1;
  $theirAllies = &GetAllies($otherPlayer);
  for($i=0; $i<count($theirAllies); $i+=AllyPieces())
  {
    $ally = new Ally("MYALLY-" . $i, $otherPlayer);
    if($ally->IsUpgraded()) {
      $upgrades = $ally->GetUpgrades(withMetadata:true);
      for($j=0; $j<count($upgrades); $j+=SubcardPieces()) {
        if(CardIDIsLeader($upgrades[$j]) && $upgrades[$j+1] == $player) {
          $cardAspects = explode(",", CardAspects($upgrades[$j]));
          for($k=0; $k<count($cardAspects); ++$k) {
            ++$aspects[$cardAspects[$k]];
          }
        }
      }
    }
  }

  return $aspects;
}

function LeaderMainAspect($player) {
  $character = &GetPlayerCharacter($player);
  $baseAspects = explode(",", CardAspects($character[0]));
  $aspects = PlayerAspects($player);
  foreach($aspects as $aspect => $count) {
    if ($count == 0 || in_array($aspect, $baseAspects)) {
      continue;
    }
    if ($aspect != "Heroism" && $aspect != "Villainy") {
      return $aspect;
    }
  }
  return $baseAspects[0];
}

function IsAlternativeCostPaid($cardID, $from)
{
  global $currentTurnEffects, $currentPlayer;
  $isAlternativeCostPaid = false;
  for($i = count($currentTurnEffects) - CurrentTurnPieces(); $i >= 0; $i -= CurrentTurnPieces()) {
    $remove = false;
    if($currentTurnEffects[$i + 1] == $currentPlayer) {
      switch($currentTurnEffects[$i]) {
        case "9644107128"://Bamboozle
          $isAlternativeCostPaid = true;
          $remove = true;
          break;
        default:
          break;
      }
      if($remove) RemoveCurrentTurnEffect($i);
    }
  }
  return $isAlternativeCostPaid;
}

function BanishCostModifier($from, $index)
{
  global $currentPlayer;
  if($from != "BANISH") return 0;
  $banish = GetBanish($currentPlayer);
  $mod = explode("-", $banish[$index + 1]);
  switch($mod[0]) {
    case "ARC119": return -1 * intval($mod[1]);
    default: return 0;
  }
}

function IsCurrentAttackName($name)
{
  $names = GetCurrentAttackNames();
  for($i=0; $i<count($names); ++$i)
  {
    if($name == $names[$i]) return true;
  }
  return false;
}

function IsCardNamed($player, $cardID, $name)
{
  global $currentTurnEffects;
  if(CardName($cardID) == $name) return true;
  for($i=0; $i<count($currentTurnEffects); $i+=CurrentTurnEffectPieces())
  {
    $effectArr = explode("-", $currentTurnEffects[$i]);
    $name = CurrentEffectNameModifier($effectArr[0], (count($effectArr) > 1 ? GamestateUnsanitize($effectArr[1]) : "N/A"));
    //You have to do this at the end, or you might have a recursive loop -- e.g. with OUT052
    if($name != "" && $currentTurnEffects[$i+1] == $player) return true;
  }
  return false;
}

function GetCurrentAttackNames()
{
  global $combatChain, $currentTurnEffects, $mainPlayer;
  $names = [];
  if(count($combatChain) == 0) return $names;
  $names[] = CardName($combatChain[0]);
  for($i=0; $i<count($currentTurnEffects); $i+=CurrentTurnEffectPieces())
  {
    $effectArr = explode("-", $currentTurnEffects[$i]);
    $name = CurrentEffectNameModifier($effectArr[0], (count($effectArr) > 1 ? GamestateUnsanitize($effectArr[1]) : "N/A"));
    //You have to do this at the end, or you might have a recursive loop -- e.g. with OUT052
    if($name != "" && $currentTurnEffects[$i+1] == $mainPlayer && IsCombatEffectActive($effectArr[0]) && !IsCombatEffectLimited($i)) $names[] = $name;
  }
  return $names;
}

function SerializeCurrentAttackNames()
{
  $names = GetCurrentAttackNames();
  $serializedNames = "";
  for($i=0; $i<count($names); ++$i)
  {
    if($serializedNames != "") $serializedNames .= ",";
    $serializedNames .= GamestateSanitize($names[$i]);
  }
  return $serializedNames;
}

function HasAttackName($name)
{
  global $chainLinkSummary;
  for($i=0; $i<count($chainLinkSummary); $i+=ChainLinkSummaryPieces())
  {
    $names = explode(",", $chainLinkSummary[$i+4]);
    for($j=0; $j<count($names); ++$j)
    {
      if($name == GamestateUnsanitize($names[$j])) return true;
    }
  }
  return false;
}

function HasPlayedAttackReaction()
{
  global $combatChain, $mainPlayer;
  for($i=CombatChainPieces(); $i<count($combatChain); $i+=CombatChainPieces())
  {
    if($combatChain[$i+1] != $mainPlayer) continue;
    if(CardType($combatChain[$i]) == "AR" || GetResolvedAbilityType($combatChain[$i])) return true;
  }
  return false;
}

function HitEffectsArePrevented()
{
  global $combatChainState, $CCS_ChainLinkHitEffectsPrevented;
  return $combatChainState[$CCS_ChainLinkHitEffectsPrevented];
}

function HitEffectsPreventedThisLink()
{
  global $combatChainState, $CCS_ChainLinkHitEffectsPrevented;
  $combatChainState[$CCS_ChainLinkHitEffectsPrevented] = 1;
}

function EffectPreventsHit()
{
  global $currentTurnEffects, $mainPlayer, $combatChain;
  $preventsHit = false;
  for($i=count($currentTurnEffects)-CurrentTurnPieces(); $i >= 0; $i-=CurrentTurnPieces())
  {
    if($currentTurnEffects[$i+1] != $mainPlayer) continue;
    $remove = 0;
    switch($currentTurnEffects[$i])
    {
      case "OUT108": if(CardType($combatChain[0]) == "AA") { $preventsHit = true; $remove = 1; } break;
      default: break;
    }
    if($remove == 1) RemoveCurrentTurnEffect($i);
  }
  return $preventsHit;
}

function HitsInRow()
{
  global $chainLinkSummary;
  $numHits = 0;
  for($i=count($chainLinkSummary)-ChainLinkSummaryPieces(); $i>=0 && intval($chainLinkSummary[$i+5]) > 0; $i-=ChainLinkSummaryPieces())
  {
    ++$numHits;
  }
  return $numHits;
}

function HitsInCombatChain()
{
  global $chainLinkSummary, $combatChainState, $CCS_HitThisLink;
  $numHits = intval($combatChainState[$CCS_HitThisLink]);
  for($i=count($chainLinkSummary)-ChainLinkSummaryPieces(); $i>=0; $i-=ChainLinkSummaryPieces())
  {
    $numHits += intval($chainLinkSummary[$i+5]);
  }
  return $numHits;
}

function NumAttacksHit()
{
    global $chainLinkSummary;
    $numHits = 0;
    for($i=count($chainLinkSummary)-ChainLinkSummaryPieces(); $i>=0; $i-=ChainLinkSummaryPieces())
    {
      if($chainLinkSummary[$i] > 0) ++$numHits;
    }
    return $numHits;
}

function NumChainLinks()
{
  global $chainLinkSummary, $combatChain;
  $numLinks = count($chainLinkSummary)/ChainLinkSummaryPieces();
  if(count($combatChain) > 0) ++$numLinks;
  return $numLinks;
}

function ClearGameFiles($gameName)
{
  if(file_exists("./Games/" . $gameName . "/gamestateBackup.txt")) unlink("./Games/" . $gameName . "/gamestateBackup.txt");
  if(file_exists("./Games/" . $gameName . "/beginTurnGamestate.txt")) unlink("./Games/" . $gameName . "/beginTurnGamestate.txt");
  if(file_exists("./Games/" . $gameName . "/lastTurnGamestate.txt")) unlink("./Games/" . $gameName . "/lastTurnGamestate.txt");
}

//FAB
// function IsClassBonusActive($player, $class)
// {
//   $char = &GetPlayerCharacter($player);
//   if(count($char) == 0) return false;
//   if(ClassContains($char[0], $class, $player)) return true;
//   return false;
// }

function PlayAbility($cardID, $from, $resourcesPaid, $target = "-", $additionalCosts = "-", $theirCard = false, $uniqueId = "")
{
  global $currentPlayer, $layers, $CS_PlayIndex, $CS_OppIndex, $initiativePlayer, $CCS_CantAttackBase, $CS_NumAlliesDestroyed;
  global $CS_NumFighterAttacks, $CS_NumNonTokenVehicleAttacks, $CS_NumFirstOrderPlayed, $CS_NumForcePlayed;
  global $CS_NumUsesLeaderUpgrade1, $CS_NumUsesLeaderUpgrade2;
  global $CS_CachedLeader1EpicAction, $CS_CachedLeader2EpicAction;
  $index = GetClassState($currentPlayer, $CS_PlayIndex);
  $otherPlayer = $currentPlayer == 1 ? 2 : 1;
  if($from == "PLAY" && IsAlly($cardID, $currentPlayer)) {
    $playAlly = new Ally("MYALLY-" . $index);
    $abilityName = GetResolvedAbilityName($cardID, $from);
    if($abilityName == "Heroic Resolve") {
      $ally = new Ally("MYALLY-" . $index, $currentPlayer);
      $ownerId = $ally->DefeatUpgrade("4085341914");
      AddGraveyard("4085341914", $ownerId, "PLAY");
      AddCurrentTurnEffect("4085341914", $currentPlayer, "PLAY", $ally->UniqueID());
      AddDecisionQueue("PASSPARAMETER", $currentPlayer, "MYALLY-" . $index);
      AddDecisionQueue("MZOP", $currentPlayer, "ATTACK");
      return "";
    } else if($abilityName == "Strategic Acumen") {
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a card to play");
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYHAND:definedType=Unit");
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("ADDCURRENTEFFECT", $currentPlayer, "2397845395", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "PLAYCARD", 1);
      return "";
    } else if ($abilityName == "Mill") { //Satine Kryze
      $ally = new Ally("MYALLY-" . $index, $currentPlayer);
      Mill($otherPlayer, ceil($ally->Health()/2));
      return "";
    } else if ($abilityName == "Poe Pilot") {
      global $CS_OppCardActive;
      if(GetClassState($currentPlayer, $CS_OppCardActive) == "1") {
        AddDecisionQueue("PASSPARAMETER", $currentPlayer, -1, 1);
        AddDecisionQueue("SETCLASSSTATE", $currentPlayer, $CS_OppCardActive, 1);
      }
      DecrementClassState($currentPlayer, $CS_NumUsesLeaderUpgrade1, 1);
      AddDecisionQueue("PASSPARAMETER", $currentPlayer, ($theirCard ? "THEIRALLY-" : "MYALLY-") . $index, 1);
      AddDecisionQueue("SETDQVAR", $currentPlayer, "0", 1);
      AddDecisionQueue("PASSPARAMETER", $currentPlayer, "3eb545eb4b", 1);
      AddDecisionQueue("SETDQVAR", $currentPlayer, "1", 1);
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY:trait=Vehicle", 1);
      AddDecisionQueue("MZFILTER", $currentPlayer, "hasPilot=1", 1);
      AddDecisionQueue("PASSREVERT", $currentPlayer, "-");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to move <1> to.", 1);
      AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "MOVEUPGRADE", 1);
    }
  }
  if($target != "-")
  {
    $targetArr = explode("-", $target);
    if($targetArr[0] == "LAYERUID") { $targetArr[0] = "LAYER"; $targetArr[1] = SearchLayersForUniqueID($targetArr[1]); }
    $target = count($targetArr) > 1 ? $targetArr[0] . "-" . $targetArr[1] : "-";
  }
  if ($from != "PLAY" && IsAlly($cardID, $currentPlayer)) {
    //LastAllyIndex does not work well when you play multiple unit on same times (Vader, U-Wing, Endless Legion ...)
     if ($uniqueId != "") {
       $playAlly = new Ally($uniqueId, $currentPlayer);
     } else {
       $playAlly = new Ally("MYALLY-" . LastAllyIndex($currentPlayer));
     }
  }

  if($from == "EQUIP" && DefinedTypesContains($cardID, "Leader", $currentPlayer)) {
    global $dqVars;
    $abilityName = GetResolvedAbilityName($cardID, $from);
    if (count($dqVars) > 0 && $dqVars[0] == "Deploy" && $dqVars[1] == "Pilot") {
      $abilityName = "Pilot";
    }

    if($abilityName == "Deploy" || $abilityName == "Pilot" || $abilityName == "") {
      $notEnoughResources = NumResources($currentPlayer) < CardCost($cardID);
      if($cardID == "8520821318") {//Poe Dameron JTL leader
        $notEnoughResources = $abilityName == "Deploy" && NumResources($currentPlayer) < 5;
      }
      if($cardID == "0092239541") {//Avar Kriss LOF leader
        global $CS_NumTimesUsedTheForce;
        $notEnoughResources = $abilityName == "Deploy" && NumResources($currentPlayer) + intval(GetClassState($currentPlayer, $CS_NumTimesUsedTheForce)) < 9;
      }
      if($notEnoughResources) {
        WriteLog("You don't control enough resources to deploy that leader; reverting the game state.");
        RevertGamestate();
        return "";
      }
      if($abilityName == "Deploy" || $abilityName == "") {
        $epicAction = $cardID != "3905028200" ? 1 : 0;//Admiral Trench leader (so far the only one)
        if($epicAction) $from = "EPICACTION";
        $playUniqueID = PlayAlly(LeaderUnit($cardID), $currentPlayer, from:$from);
        if (HasShielded(LeaderUnit($cardID), $currentPlayer, Ally::FromUniqueId($playUniqueID)->Index())) {
          AddLayer("TRIGGER", $currentPlayer, "SHIELDED", "-", "-", $playUniqueID);
        }
        PlayAbility(LeaderUnit($cardID), "CHAR", 0, "-", "-", false, $uniqueId);
      }
      else if($cardID == "8520821318" && $abilityName == "Pilot") {//Poe Dameron JTL leader
        AddDecisionQueue("PASSPARAMETER", $currentPlayer, $cardID);
        AddDecisionQueue("SETDQVAR", $currentPlayer, "0");
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY:trait=Vehicle");
        AddDecisionQueue("MZFILTER", $currentPlayer, "hasPilot=1");
        AddDecisionQueue("PASSREVERT", $currentPlayer, "-");
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to attach <0>");
        AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("SHOWSELECTEDTARGET", $currentPlayer, "-", 1);
        AddDecisionQueue("DEPLOYLEADERASUPGRADE", $currentPlayer, $cardID, 1);
      }
      else if($abilityName == "Pilot") {
        AddDecisionQueue("PASSPARAMETER", $currentPlayer, $cardID);
        AddDecisionQueue("SETDQVAR", $currentPlayer, "0");
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY:trait=Vehicle;canAddPilot=1");
        AddDecisionQueue("PASSREVERT", $currentPlayer, "-");
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to attach <0>");
        AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("SHOWSELECTEDTARGET", $currentPlayer, "-", 1);
        AddDecisionQueue("DEPLOYLEADERASUPGRADE", $currentPlayer, $cardID, 1);
      }
      //On Deploy ability / When Deployed ability
      if(!LeaderAbilitiesIgnored()) {
        switch($cardID) {
          case "5784497124"://Emperor Palpatine Leader flip
            AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "THEIRALLY:damagedOnly=true");
            AddDecisionQueue("MZFILTER", $currentPlayer, "leader=1");
            AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a damaged unit to take control of", 1);
            AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
            AddDecisionQueue("MZOP", $currentPlayer, "GETUNIQUEID", 1);
            AddDecisionQueue("MZOP", $currentPlayer, "TAKECONTROL", 1);
            break;
          case "2432897157"://Qi'Ra Leader flip
            $myAllies = &GetAllies($currentPlayer);
            for($i=0; $i<count($myAllies); $i+=AllyPieces())
            {
              $ally = new Ally("MYALLY-" . $i, $currentPlayer);
              $ally->Heal(9999);
              $ally->DealDamage(floor($ally->MaxHealth()/2));
            }
            $theirAllies = &GetAllies($otherPlayer);
            for($i=0; $i<count($theirAllies); $i+=AllyPieces())
            {
              $ally = new Ally("MYALLY-" . $i, $otherPlayer);
              $ally->Heal(9999);
              $ally->DealDamage(floor($ally->MaxHealth()/2));
            }
            break;
          case "0254929700"://Doctor Aphra Leader flip
            AddDecisionQueue("FINDINDICES", $currentPlayer, "GY");
            AddDecisionQueue("PREPENDLASTRESULT", $currentPlayer, "3-", 1);
            AddDecisionQueue("APPENDLASTRESULT", $currentPlayer, "-3", 1);
            AddDecisionQueue("MULTICHOOSEDISCARD", $currentPlayer, "<-", 1);
            AddDecisionQueue("SPECIFICCARD", $currentPlayer, "DOCTORAPHRA", 1);
            break;
          case "0622803599"://Jabba the Hutt Leader flip
            $jabbaMzIndex = explode(",", "MYALLY-" . SearchAlliesForCard($currentPlayer, "f928681d36"));//Jabba the Hutt leader unit
            $allyMzIndices = explode(",", SearchMultizone($currentPlayer, "MYALLY"));
            $alliesNotJabba = implode(",", array_diff($allyMzIndices, $jabbaMzIndex));
            if($alliesNotJabba != "") {
              AddDecisionQueue("PASSPARAMETER", $currentPlayer, $alliesNotJabba);
              AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to capture another unit");
              AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
              AddDecisionQueue("MZOP", $currentPlayer, "GETUNIQUEID", 1);
              AddDecisionQueue("SETDQVAR", $currentPlayer, "0", 1);
              AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "THEIRALLY", 1);
              AddDecisionQueue("MZFILTER", $currentPlayer, "leader=1", 1);
              AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to capture", 1);
              AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
              AddDecisionQueue("MZOP", $currentPlayer, "CAPTURE,{0}", 1);
            }
            break;
          case "4628885755"://Mace Windu Leader flip
            $theirAllies = &GetAllies($otherPlayer);
            for($i=count($theirAllies)-AllyPieces(); $i>=0; $i-=AllyPieces())
            {
              $ally = new Ally("MYALLY-" . $i, $otherPlayer);
              if($ally->IsDamaged()) {
                $ally->DealDamage(2);
              }
            }
            break;
          case "7734824762"://Captain Rex Leader flip
            CreateCloneTrooper($currentPlayer);
            break;
          case "2847868671"://Yoda Leader flip
          $deck = &GetDeck($currentPlayer);
          if(count($deck) > 0) {
            AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose if you want to discard a card to Yoda");
            AddDecisionQueue("YESNO", $currentPlayer, "-");
            AddDecisionQueue("NOPASS", $currentPlayer, "-");
            AddDecisionQueue("PASSPARAMETER", $currentPlayer, "1", 1);
            AddDecisionQueue("OP", $currentPlayer, "MILL", 1);
            AddDecisionQueue("MZOP", $currentPlayer, "GETCARDCOST", 1);
            AddDecisionQueue("SETDQVAR", $currentPlayer, "0", 1);
            AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "THEIRALLY:maxCost={0}", 1);
            AddDecisionQueue("MZFILTER", $currentPlayer, "leader=1");
            AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to destroy");
            AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
            AddDecisionQueue("MZOP", $currentPlayer, "DESTROY,$currentPlayer", 1);
          }
          break;
          case "3905028200"://Admiral Trench flip
            $deck = new Deck($currentPlayer);
            $cardsToReveal = min($deck->RemainingCards(), 4);
            $deck->Reveal($cardsToReveal);
            $cards = $deck->Top(remove:true, amount:$cardsToReveal);
            $cardArr = explode(",", $cards);
            if($cards != "") {
              AddDecisionQueue("PASSPARAMETER", $currentPlayer, $cards, 1);
              AddDecisionQueue("SETDQVAR", $currentPlayer, "0", 1);
              AddDecisionQueue("SETDQCONTEXT", $otherPlayer, "Discard 2 cards.", 1);
              AddDecisionQueue("CHOOSECARD", $otherPlayer, "{0}", 1);//if there's one card, forced to choose
              AddDecisionQueue("SPECIFICCARD", $currentPlayer, "TRENCH_JTL_OPP", 1);
              AddDecisionQueue("MAYCHOOSECARD", $otherPlayer, "{0}", 1);//this is a may to account for edge cases where <4 cards left
              AddDecisionQueue("SPECIFICCARD", $currentPlayer, "TRENCH_JTL_OPP", 1);
              AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose card to draw.", 1);
              AddDecisionQueue("MAYCHOOSECARD", $currentPlayer, "{0}", 1);
              AddDecisionQueue("SPECIFICCARD", $currentPlayer, "TRENCH_JTL", 1);
            }
            break;
          //Legend of the Force
          case "2762251208"://Rey Leader flip
            AddDecisionQueue("YESNO", $currentPlayer, "if you want to discard to draw 2 cards");
            AddDecisionQueue("NOPASS", $currentPlayer, "-", 1);
            AddDecisionQueue("OP", $currentPlayer, "DISCARDHAND", 1);
            AddDecisionQueue("DRAW", $currentPlayer, "-", 1);
            AddDecisionQueue("DRAW", $currentPlayer, "-", 1);
            break;
          case "5174764156"://Kylo Ren Leader flip
            AddDecisionQueue("SPECIFICCARD", $currentPlayer, "KYLOREN_LOF", 1);
            break;
          default: break;
        }
      }
      RemoveCharacter($currentPlayer, CharacterPieces());
      if(isset($epicAction) && $epicAction == 1) SetClassState($currentPlayer, $CS_CachedLeader1EpicAction, $epicAction);
      //Base deploy ability
      $char = &GetPlayerCharacter($currentPlayer);
      $baseID = $char[0];
      switch($baseID) {
        case "8589863038"://Droid Manufactory
          CreateBattleDroid($currentPlayer);
          CreateBattleDroid($currentPlayer);
          WriteLog("Droid Manufactory deployed two Battle Droids.");
          break;
        case "6854189262"://Shadow Collective Camp
          Draw($currentPlayer);
          WriteLog("Shadow Collective Camp drew a card.");
          break;
        default: break;
      }
      //Ally when leader deployed effects
      $allies = &GetAllies($currentPlayer);
      for($i=0; $i<count($allies); $i+=AllyPieces())
      {
        $ally = new Ally("MYALLY-" . $i, $currentPlayer);
        switch($ally->CardID()) {
          //Jump to Lightspeed
          case "9958088138"://Invincible
            AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY:maxCost=3&THEIRALLY:maxCost=3");
            AddDecisionQueue("MZFILTER", $currentPlayer, "leader=1");
            AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a non-leader unit that costs 3 or less to bounce");
            AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
            AddDecisionQueue("MZOP", $currentPlayer, "BOUNCE", 1);
            break;
          default: break;
        }
      }
      return CardLink($cardID, $cardID) . " was deployed.";
    }
  }
  switch($cardID)
  {
    case "8839068683"://Freelance Assassin
      if(GetResources($currentPlayer) >= 2) {
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Pay 2 resources to deal 2 damage to a unit?", 1);
        AddDecisionQueue("YESNO", $currentPlayer, "-", 1);
        AddDecisionQueue("NOPASS", $currentPlayer, "-", 1);
        AddDecisionQueue("PAYRESOURCES", $currentPlayer, "2", 1);
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY&THEIRALLY", 1);
        AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "DEALDAMAGE,2,$currentPlayer,1", 1);
      }
      break;
    case "4569767827"://Execute Order 66
      for ($p = 1; $p <= 2; $p++) {
        $jediUniqueIDs = explode(",", SearchAlliesUniqueIDForTrait($p, "Jedi"));

        foreach ($jediUniqueIDs as $jediUniqueID) {
          $ally = new Ally($jediUniqueID, $p);
          $enemyDamage = $p != $currentPlayer;
          $destroyed = $ally->DealDamage(6, enemyDamage:$enemyDamage);

          if ($destroyed) {
            CreateCloneTrooper($p);
          }
        }
      }
      break;
    case "5013139687"://Caught In The Crossfire
      if ($target != "-") {
        $ally = new Ally($target);
        AddDecisionQueue("PASSPARAMETER", $currentPlayer, $target);
        AddDecisionQueue("SETDQVAR", $currentPlayer, "0");
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "THEIRALLY:arena=" . CardArenas($ally->CardID()));
        AddDecisionQueue("MZFILTER", $currentPlayer, "index=" . $target);
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose another unit");
        AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-");
        AddDecisionQueue("APPENDDQVAR", $currentPlayer, "0", 1);
        AddDecisionQueue("SPECIFICCARD", $currentPlayer, "CAUGHTINTHECROSSFIRE", 1);
      }
      break;
    case "7895170711"://A Fine Addition
      if(GetClassState($otherPlayer, $CS_NumAlliesDestroyed) > 0) {
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose where to play an upgrade from");
        AddDecisionQueue("BUTTONINPUT", $currentPlayer, "My Hand,My Discard,Opponent Discard", 1);
        AddDecisionQueue("SPECIFICCARD", $currentPlayer, "AFINEADDITION", 1);
      }
      break;
    case "0345124206"://Clone
      $mzIndex = "MYALLY-" . $playAlly->Index();
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY&THEIRALLY");
      AddDecisionQueue("MZFILTER", $currentPlayer, "trait=Vehicle");
      AddDecisionQueue("MZFILTER", $currentPlayer, "leader=1");
      AddDecisionQueue("MZFILTER", $currentPlayer, "index=" . $mzIndex);
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose which unit you want to clone", 1);
      AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "GETCARDID", 1);
      $playAbility = $from != "CAPTIVE" ? "true" : "false";
      AddDecisionQueue("PLAYALLY", $currentPlayer, "cloned=true;from=" . $from . ";playAbility=" . $playAbility, 1);
      AddDecisionQueue("PASSPARAMETER", $currentPlayer, $mzIndex, 1);
      AddDecisionQueue("MZREMOVE", $currentPlayer, "-", 1);
      break;
    case "4721628683": case "6534973905"://Patrolling V-Wing
      if($from != "PLAY") Draw($currentPlayer);
      break;
    case "2050990622"://Spark of Rebellion card
      AddDecisionQueue("REVEALHANDCARDS", $otherPlayer, "-");
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "THEIRHAND");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose which card you want your opponent to discard", 1);
      AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZDISCARD", $currentPlayer, "HAND," . $currentPlayer, 1);
      AddDecisionQueue("MZREMOVE", $currentPlayer, "-", 1);
      break;
    case "3377409249"://Rogue Squadron Skirmisher
      if($from != "PLAY") MZMoveCard($currentPlayer, "MYDISCARD:maxCost=2;definedType=Unit", "MYHAND", may:true);
      break;
    case "5335160564"://Guerilla Attack Pod
      if($from != "PLAY" && (GetBaseDamage(1) >= 15 || GetBaseDamage(2) >= 15)) {
        $playAlly->Ready();
      }
      break;
    case "7262314209"://Mission Briefing
      $player = $additionalCosts == "Yourself" ? $currentPlayer : $otherPlayer;
      Draw($player);
      Draw($player);
      break;
    case "6253392993"://Bright Hope
      if($from != "PLAY") {
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY:arena=Ground");
        AddDecisionQueue("MZFILTER", $currentPlayer, "leader=1");
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a card to bounce");
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "BOUNCE", 1);
        AddDecisionQueue("DRAW", $currentPlayer, "-", 1);
      }
      break;
    case "6702266551"://Smoke and Cinders
      $hand = &GetHand(1);
      for($i=0; $i<(count($hand)/HandPieces())-2; ++$i) PummelHit(1);
      $hand = &GetHand(2);
      for($i=0; $i<(count($hand)/HandPieces())-2; ++$i) PummelHit(2);
      break;
    case "8148673131"://Open Fire
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY&THEIRALLY");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to deal 4 damage to");
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "DEALDAMAGE,4,$currentPlayer", 1);
      break;
    case "8429598559"://Black One
      if($from != "PLAY") BlackOne($currentPlayer);
      break;
    case "8986035098"://Viper Probe Droid
      if($from != "PLAY") {
        AddDecisionQueue("REVEALHANDCARDS", $otherPlayer, "-");
        AddDecisionQueue("LOOKHAND", $currentPlayer, "-");
      }
      break;
    case "9266336818"://Grand Moff Tarkin
      if($from != "PLAY") {
        AddDecisionQueue("SEARCHDECKTOPX", $currentPlayer, "5;2;include-trait-Imperial");
        AddDecisionQueue("MULTIADDHAND", $currentPlayer, "-", 1);
        AddDecisionQueue("REVEALCARDS", $currentPlayer, "DECK", 1);
      }
      break;
    case "9459170449"://Cargo Juggernaut
      if($from != "PLAY" && SearchCount(SearchAllies($currentPlayer, aspect:"Vigilance")) > 1) {
        Restore(4, $currentPlayer);
      }
      break;
    case "7257556541"://Bodhi Rook
      if($from != "PLAY") {
        AddDecisionQueue("LOOKHAND", $currentPlayer, "-");
        AddDecisionQueue("REVEALHANDCARDS", $otherPlayer, "-");
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "THEIRHAND");
        AddDecisionQueue("MZFILTER", $currentPlayer, "definedType=Unit");
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a card to discard");
        AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZDESTROY", $currentPlayer, "-", 1);
      }
      break;
    case "6028207223"://Pirated Starfighter
      if($from != "PLAY") {
        if(SearchCount(SearchAllies($currentPlayer)) == 1) {
          WriteLog(CardLink($cardID, $cardID) . " was played, but no other units were present, so it bounced itself.");
        }
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY");
        AddDecisionQueue("MZFILTER", $currentPlayer, "leader=1");
        AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "BOUNCE", 1);
      }
      break;
    case "8981523525"://Moment of Peace
      if($target != "-") {
        $ally = new Ally($target);
        $ally->Attach("8752877738", $currentPlayer);//Shield
      }
      break;
    case "8679831560"://Repair
      $mzArr = explode("-", $target);
      if($mzArr[0] == "MYCHAR") Restore(3, $currentPlayer);
      else if($mzArr[0] == "MYALLY") {
        $ally = new Ally($target);
        $ally->Heal(3);
      }
      break;
    case "7533529264"://Wolffe
      if($from != "PLAY") WolffeSOR($currentPlayer);
      break;
    case "7596515127"://Academy Walker
      if($from != "PLAY") {
        $allies = &GetAllies($currentPlayer);
        for($i=0; $i<count($allies); $i+=AllyPieces()) {
          $ally = new Ally("MYALLY-" . $i);
          if($ally->IsDamaged()) $ally->Attach("2007868442");//Experience token
        }
      }
      break;
    case "7235023816"://Guerilla Insurgency
      MZChooseAndDestroy($currentPlayer, "MYRESOURCES", context:"Choose a resource to destroy");
      MZChooseAndDestroy($otherPlayer, "MYRESOURCES", context:"Choose a resource to destroy");
      PummelHit($currentPlayer);
      PummelHit($currentPlayer);
      PummelHit($otherPlayer);
      PummelHit($otherPlayer);
      AddDecisionQueue("SPECIFICCARD", $currentPlayer, "GUERILLAINSURGENCY");
      break;
    case "7202133736"://Waylay
      DQWaylay($currentPlayer);
      break;
    case "5283722046"://Spare the Target
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "THEIRALLY");
      AddDecisionQueue("MZFILTER", $currentPlayer, "leader=1");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to return to hand");
      AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("SETDQVAR", $currentPlayer, "1", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "COLLECTBOUNTIES", 1);
      AddDecisionQueue("PASSPARAMETER", $currentPlayer, "{1}", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "BOUNCE", 1);
      break;
    case "7485151088"://Search your feelings
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYDECK");
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZREMOVE", $currentPlayer, "-", 1);
      AddDecisionQueue("MULTIADDHAND", $currentPlayer, "-", 1);
      AddDecisionQueue("SHUFFLEDECK", $currentPlayer, "-");
      break;
    case "0176921487"://Power of the Dark Side
      MZChooseAndDestroy($otherPlayer, "MYALLY");
      break;
    case "0827076106"://Admiral Ackbar
      if($from != "PLAY") {
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY&THEIRALLY");
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to damage with&nbsp;" . CardLink($cardID, $cardID));
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("SPECIFICCARD", $currentPlayer, "ADMIRALACKBAR", 1);
      }
      break;
    case "0867878280"://It Binds All Things
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY&THEIRALLY");
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("PREPENDLASTRESULT", $currentPlayer, "3-", 1);
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Heal up to 3 damage", 1);
        AddDecisionQueue("PARTIALMULTIHEALMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "MULTIHEAL", 1);
        AddDecisionQueue("PREPENDLASTRESULT", $currentPlayer, "-", 1);
        if(SearchCount(SearchAllies($currentPlayer, trait:"Force")) > 0) {
          AddDecisionQueue("SPECIFICCARD", $currentPlayer, "DEALRESTOREDAMAGE,MAY", 1);
        }
      break;
    case "1021495802"://Cantina Bouncer
      if($from != "PLAY") {
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "THEIRALLY&MYALLY");
        AddDecisionQueue("MZFILTER", $currentPlayer, "leader=1");
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "BOUNCE", 1);
      }
      break;
    case "1353201082"://Superlaser Blast
      DestroyAllAllies();
      break;
    case "1705806419"://Force Throw
      if($additionalCosts == "Yourself") PummelHit($currentPlayer);
      else PummelHit($otherPlayer);
      if(SearchCount(SearchAllies($currentPlayer, trait:"Force")) > 0) {
        AddDecisionQueue("SPECIFICCARD", $currentPlayer, "FORCETHROW", 1);
      }
      break;
    case "2587711125"://Disarm
      $ally = new Ally($target);
      AddDecisionQueue("PASSPARAMETER", $currentPlayer, $ally->UniqueID());
      AddDecisionQueue("ADDLIMITEDCURRENTEFFECT", $ally->PlayerID(), "2587711125,HAND");
      break;
    case "6472095064"://Vanquish
    case "6707315263"://It's Worse
      MZChooseAndDestroy($currentPlayer, "MYALLY&THEIRALLY", filter:"leader=1", context:"Choose a non-leader unit to defeat");
      break;
    case "6663619377"://AT-AT Suppressor
      if($from != "PLAY"){
        ExhaustAllAllies("Ground", 1, $currentPlayer);
        ExhaustAllAllies("Ground", 2, $currentPlayer);
      }
      break;
    case "6931439330"://The Ghost
      if($from != "PLAY") TheGhostSOR($currentPlayer, $playAlly->Index());
      break;
    case "8691800148"://Reinforcement Walker
      if ($from != "PLAY") ReinforcementWalkerSOR($currentPlayer);
      break;
    case "9002021213"://Imperial Interceptor
      if($from != "PLAY") {
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a Space unit to deal 3 damage to");
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY:arena=Space&THEIRALLY:arena=Space");
        AddDecisionQueue("MZFILTER", $currentPlayer, "index=MYALLY-" . $playAlly->Index());
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "DEALDAMAGE,3,$currentPlayer,1", 1);
      }
      break;
    case "9133080458"://Inferno Four
      if($from != "PLAY") PlayerOpt($currentPlayer, 2);
      break;
    case "9568000754"://R2-D2
      if ($from != "PLAY") PlayerOpt($currentPlayer, 1);
      break;
    case "9624333142"://Count Dooku (Darth Tyranus)
      if($from != "PLAY") {
        MZChooseAndDestroy($currentPlayer, "MYALLY:maxHealth=4&THEIRALLY:maxHealth=4", may:true, filter:"index=MYALLY-" . $playAlly->Index());
      }
      break;
    case "9097316363"://Emperor Palpatine (Master of the Dark Side)
      if($from != "PLAY") {
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "THEIRALLY");
        AddDecisionQueue("PREPENDLASTRESULT", $currentPlayer, "6-", 1);
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Deal 6 damage divided as you choose", 1);
        AddDecisionQueue("MULTIDAMAGEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $currentPlayer, DealMultiDamageBuilder($currentPlayer, isUnitEffect:1), 1);
      }
      break;
    case "1208707254"://Rallying Cry
      $allies = &GetAllies($currentPlayer);
      for ($i = 0; $i < count($allies); $i += AllyPieces()) {
        AddCurrentTurnEffect($cardID, $currentPlayer, "PLAY", $allies[$i+5]);
      }
      break;
    case "1446471743"://Force Choke
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY&THEIRALLY");
      AddDecisionQueue("MZFILTER", $currentPlayer, "trait=Vehicle");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to deal 5 damage to");
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("SETDQVAR", $currentPlayer, "1", 1);
      AddDecisionQueue("SPECIFICCARD", $currentPlayer, "FORCECHOKE", 1);
      AddDecisionQueue("PASSPARAMETER", $currentPlayer, "{1}", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "DEALDAMAGE,5,$currentPlayer", 1);
      break;
    case "1047592361"://Ruthless Raider
      if($from != "PLAY") {
        DealDamageAsync($otherPlayer, 2, "DAMAGE", "1047592361", sourcePlayer:$currentPlayer);
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "THEIRALLY");
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to deal 2 damage to");
        AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "DEALDAMAGE,2,$currentPlayer,1", 1);
      }
      break;
    case "2554951775"://Bail Organa
      if($from == "PLAY" && GetResolvedAbilityType($cardID) == "A") {
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY");
        AddDecisionQueue("MZFILTER", $currentPlayer, "index=MYALLY-" . $index);
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to add an experience");
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "ADDEXPERIENCE", 1);
      }
      break;
    case "3058784025"://Keep Fighting
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY:maxAttack=3&THEIRALLY:maxAttack=3");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to ready");
      AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "READY", 1);
      break;
    case "3684950815"://Bounty Hunter Crew
      if($from != "PLAY") MZMoveCard($currentPlayer, "MYDISCARD:definedType=Event", "MYHAND", may:true, context:"Choose an event to return with " . CardLink("3684950815", "3684950815"));
      break;
    case "4092697474"://TIE Advanced
      if($from != "PLAY") {
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY:trait=Imperial");
        AddDecisionQueue("MZFILTER", $currentPlayer, "index=MYALLY-" . $playAlly->Index());
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a card to give experience");
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "ADDEXPERIENCE", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "ADDEXPERIENCE", 1);
      }
      break;
    case "4536594859"://Medal Ceremony
      DQMultiUnitSelect($currentPlayer, 3, "MYALLY:trait=Rebel&THEIRALLY:trait=Rebel", "to give an experience to", "numAttacks=0");
      AddDecisionQueue("MZOP", $currentPlayer, GiveExperienceBuilder($currentPlayer, isUnitEffect:1), 1);
      break;
    case "6515891401"://Karabast
      $ally = new Ally($target);
      $damage = $ally->Damage() + 1;
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to deal " . $damage . " damage to");
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "THEIRALLY");
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "DEALDAMAGE,$damage,$currentPlayer", 1);
      break;
    case "2359136621"://Guarding The Way
      $hasInitiative = $initiativePlayer == $currentPlayer;
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a card to give Sentinel");
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY&THEIRALLY");
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      if ($hasInitiative) {
        AddDecisionQueue("MZOP", $currentPlayer, "ADDHEALTH,2", 1);
      }
      AddDecisionQueue("MZOP", $currentPlayer, "WRITECHOICE", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "GETUNIQUEID", 1);
      AddDecisionQueue("ADDLIMITEDCURRENTEFFECT", $currentPlayer, "2359136621_" . ($hasInitiative ? "2" : "0") . ",PLAY", 1);
      break;
    case "8022262805"://Bold Resistance
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY");
      AddDecisionQueue("OP", $currentPlayer, "MZTONORMALINDICES");
      AddDecisionQueue("PREPENDLASTRESULT", $currentPlayer, "3-", 1);
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose up to 3 units that share the same trait", 1);
      AddDecisionQueue("MULTICHOOSEUNIT", $currentPlayer, "<-", 1);
      AddDecisionQueue("SPECIFICCARD", $currentPlayer, "BOLDRESISTANCE", 1);
      break;
    case "7929181061"://General Tagge
      if($from != "PLAY") {
        DQMultiUnitSelect($currentPlayer, 3, "MYALLY:trait=Trooper", "to give an experience to");
        AddDecisionQueue("MZOP", $currentPlayer, GiveExperienceBuilder($currentPlayer, isUnitEffect:1), 1);
      }
      break;
    case "8240629990"://Avenger
      if($from != "PLAY") {
        MZChooseAndDestroy($otherPlayer, "MYALLY", filter: "leader=1", context: "Choose a unit to defeat.");
      }
      break;
    case "8294130780"://Gladiator Star Destroyer
      if($from != "PLAY") {
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a card to give Sentinel");
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY&THEIRALLY");
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "WRITECHOICE", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "GETUNIQUEID", 1);
        AddDecisionQueue("ADDLIMITEDCURRENTEFFECT", $currentPlayer, "8294130780,HAND", 1);
      }
      break;
    case "4919000710"://Home One
      if($from != "PLAY") {
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a card to play");
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYDISCARD:definedType=Unit;aspect=Heroism");
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1); //Technically as written the trigger is not optional, but coding to get around the case where the only options are too expensive to play(which makes Home One unplayable because trying to play off the ability reverts the gamestate) doesn't seem worth it to cover the vanishingly rare case where a player should be forced to play something despite preferring not to.
        AddDecisionQueue("ADDCURRENTEFFECT", $currentPlayer, $cardID, 1);
        AddDecisionQueue("MZOP", $currentPlayer, "PLAYCARD", 1);
      }
      break;
    case "4849184191"://Takedown
      MZChooseAndDestroy($currentPlayer, "MYALLY:maxHealth=5&THEIRALLY:maxHealth=5");
      break;
    case "4631297392"://Devastator
      if($from != "PLAY") {
        $resourceCards = &GetResourceCards($currentPlayer);
        $numResources = count($resourceCards)/ResourcePieces();
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY&THEIRALLY");
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to deal " . $numResources . " damage");
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "DEALDAMAGE,$numResources,$currentPlayer,1", 1);
      }
      break;
    case "3802299538"://Cartel Spacer
      if($from != "PLAY" && SearchCount(SearchAllies($currentPlayer, aspect:"Cunning")) > 1) {
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "THEIRALLY:maxCost=4");
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a card to exhaust");
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "REST", 1);
      }
      break;
    case "3443737404"://Wing Leader
      if($from != "PLAY") {
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY:trait=Rebel");
        AddDecisionQueue("MZFILTER", $currentPlayer, "index=MYALLY-" . $playAlly->Index());
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to add experience");
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "ADDEXPERIENCE", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "ADDEXPERIENCE", 1);
      }
      break;
    case "2756312994"://Alliance Dispatcher
      if($from == "PLAY" && GetResolvedAbilityType($cardID) == "A") {
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a card to play");
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYHAND:definedType=Unit");
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("ADDCURRENTEFFECT", $currentPlayer, $cardID, 1);
        AddDecisionQueue("MZOP", $currentPlayer, "PLAYCARD", 1);
      }
      break;
    case "2569134232"://Jedha City
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY&THEIRALLY");
      AddDecisionQueue("MZFILTER", $currentPlayer, "leader=1");
      AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "GETUNIQUEID", 1);
      AddDecisionQueue("ADDLIMITEDCURRENTEFFECT", $currentPlayer, "2569134232,HAND");
      break;
    case "1349057156"://Strike True
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to deal damage equal to its power");
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "POWER", 1);
      AddDecisionQueue("PREPENDLASTRESULT", $currentPlayer, "DEALDAMAGE,", 1);
      AddDecisionQueue("SETDQVAR", $currentPlayer, "0", 1);
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "THEIRALLY");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to damage");
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "{0},$currentPlayer,1", 1);
      break;
    case "1393827469"://Tarkintown
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to deal 3 damage to");
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY:damagedOnly=true&THEIRALLY:damagedOnly=true");
      AddDecisionQueue("MZFILTER", $currentPlayer, "leader=1");
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "DEALDAMAGE,3,$currentPlayer", 1);
      break;
    case "1880931426"://Lothal Insurgent
      global $CS_NumCardsPlayed;
      if($from != "PLAY" && GetClassState($currentPlayer, $CS_NumCardsPlayed) > 1) {
        Draw($otherPlayer);
        DiscardRandom($otherPlayer, $cardID);
      }
      break;
    case "2429341052"://Security Complex
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY");
      AddDecisionQueue("MZFILTER", $currentPlayer, "leader=1");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to give a shield");
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "ADDSHIELD", 1);
      break;
    case "3018017739"://Vanguard Ace
      global $CS_NumCardsPlayed;
      if($from != "PLAY") {
        for($i=0; $i<(GetClassState($currentPlayer, $CS_NumCardsPlayed)-1); ++$i) {
          $playAlly->Attach("2007868442");//Experience token
        }
      }
      break;
    case "3401690666"://Relentless
      if($from != "PLAY") {
        global $CS_NumEventsPlayed;
        if(GetClassState($otherPlayer, $CS_NumEventsPlayed) == 0) {
          AddCurrentTurnEffect("3401690666", $otherPlayer, from:"PLAY");
        }
      }
      break;
    case "3407775126"://Recruit
      AddDecisionQueue("SEARCHDECKTOPX", $currentPlayer, "5;1;include-definedType-Unit");
      AddDecisionQueue("ADDHAND", $currentPlayer, "-", 1);
      AddDecisionQueue("REVEALCARDS", $currentPlayer, "DECK", 1);
      break;
    case "3498814896"://Mon Mothma
      if($from != "PLAY") {
        AddDecisionQueue("SEARCHDECKTOPX", $currentPlayer, "5;1;include-trait-Rebel");
        AddDecisionQueue("ADDHAND", $currentPlayer, "-", 1);
        AddDecisionQueue("REVEALCARDS", $currentPlayer, "DECK", 1);
      }
      break;
    case "3509161777"://You're My Only Hope
      $deck = new Deck($currentPlayer);
      AddDecisionQueue("PASSPARAMETER", $currentPlayer, $deck->Top());
      AddDecisionQueue("SETDQVAR", $currentPlayer, 0);
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Do you want to play <0>?");
      AddDecisionQueue("YESNO", $currentPlayer, "-");
      AddDecisionQueue("NOPASS", $currentPlayer, "-");
      AddDecisionQueue("ADDCURRENTEFFECT", $currentPlayer, "3509161777", 1);
      AddDecisionQueue("PASSPARAMETER", $currentPlayer, "MYDECK-0", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "PLAYCARD", 1);
      break;
    case "3572356139"://Chewbacca (Walking Carpet)
      $abilityName = GetResolvedAbilityName($cardID, $from);
      if($abilityName == "Play Taunt") {
        global $CS_AfterPlayedBy;
        SetClassState($currentPlayer, $CS_AfterPlayedBy, $cardID);
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYHAND:definedType=Unit;maxCost=3");
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a card to put into play");
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "PLAYCARD", 1);
      }
      break;
    case "2579145458"://Luke Skywalker
      $abilityName = GetResolvedAbilityName($cardID, $from);
      if($abilityName == "Give Shield") {
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY:aspect=Heroism");
        AddDecisionQueue("MZFILTER", $currentPlayer, "turns=>0");
        AddDecisionQueue("MZFILTER", $currentPlayer, "leader=1");
        AddDecisionQueue("MZFILTER", $currentPlayer, "token=1");
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1, 1);
        AddDecisionQueue("MZOP", $currentPlayer, "ADDSHIELD", 1);
      }
      break;
    case "2912358777"://Grand Moff Tarkin
      $abilityName = GetResolvedAbilityName($cardID, $from);
      if($abilityName == "Give Experience") {
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY:trait=Imperial");
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "ADDEXPERIENCE", 1);
      }
      break;
    case "3187874229"://Cassian Andor
      $abilityName = GetResolvedAbilityName($cardID, $from);
      if($abilityName == "Draw Card") {
        global $CS_DamageTaken;
        $otherPlayer = $currentPlayer == 1 ? 2 : 1;
        if(GetClassState($otherPlayer, $CS_DamageTaken) >= 3) Draw($currentPlayer);
      }
      break;
    case "4841169874"://Sabine Wren
      $abilityName = GetResolvedAbilityName($cardID, $from);
      if($abilityName == "Deal Damage") {
        DealDamageAsync(1, 1, "DAMAGE", $cardID, sourcePlayer:$currentPlayer);
        DealDamageAsync(2, 1, "DAMAGE", $cardID, sourcePlayer:$currentPlayer);
      }
      break;
    case "5871074103"://Forced Surrender
      Draw($currentPlayer);
      Draw($currentPlayer);
      global $CS_DamageTaken;
      if(GetClassState($otherPlayer, $CS_DamageTaken) > 0) {
        PummelHit($otherPlayer);
        PummelHit($otherPlayer);
      }
      break;
    case "9250443409"://Lando Calrissian
      if($from != "PLAY") {
        for($i=0; $i<2; ++$i) {
          AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose up to two resource cards to return to your hand");
          AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYRESOURCES");
          AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
          AddDecisionQueue("MZOP", $currentPlayer, "BOUNCE", 1);
        }
      }
      break;
    case "9070397522"://SpecForce Soldier
      if($from != "PLAY") {
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY&THEIRALLY");
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a card to lose sentinel");
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "GETUNIQUEID", 1);
        AddDecisionQueue("ADDLIMITEDCURRENTEFFECT", $currentPlayer, "9070397522,HAND", 1);
      }
      break;
    case "6458912354"://Death Trooper
      if($from != "PLAY") {
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY:arena=Ground");
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to deal 2 damage to");
        AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "DEALDAMAGE,2,$currentPlayer,1", 1);
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "THEIRALLY:arena=Ground");
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to deal 2 damage to");
        AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "DEALDAMAGE,2,$currentPlayer,1", 1);
      }
      break;
    case "7109944284"://Luke Skywalker unit
      global $CS_NumAlliesDestroyed;
      if($from != "PLAY") {
        $amount = GetClassState($currentPlayer, $CS_NumAlliesDestroyed) > 0 ? 6 : 3;
        DQDebuffUnit($currentPlayer, $otherPlayer, "$cardID-$amount", $amount, may:false, mzSearch:"THEIRALLY");
      }
      break;
    case "7366340487"://Outmaneuver
      $options = "Space;Ground";
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose an arena");
      AddDecisionQueue("CHOOSEOPTION", $currentPlayer, "$cardID&$options");
      AddDecisionQueue("SHOWOPTIONS", $currentPlayer, "$cardID&$options");
      AddDecisionQueue("MODAL", $currentPlayer, "OUTMANEUVER");
      break;
    case "6901817734"://Asteroid Sanctuary
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to exhaust");
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "THEIRALLY");
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "REST", 1);
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to give a shield token");
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY:maxCost=3");
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "ADDSHIELD", 1);
      break;
    case "0705773109"://Vader's Lightsaber
      if(CardTitle(GetMZCard($currentPlayer, $target)) == "Darth Vader") {
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to deal 4 damage to");
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "THEIRALLY:arena=Ground");
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "DEALDAMAGE,4,$currentPlayer", 1);
      }
      break;
    case "2048866729"://Iden Versio
      $abilityName = GetResolvedAbilityName($cardID, $from);
      if($abilityName == "Heal") {
        global $CS_NumAlliesDestroyed;
        if(GetClassState($otherPlayer, $CS_NumAlliesDestroyed) > 0) {
          Restore(1, $currentPlayer);
        }
      }
      break;
    case "9680213078"://Leia Organa
      if($from != "PLAY") {
        $options = "Ready a resource;Exhaust a unit";
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose one");
        AddDecisionQueue("CHOOSEOPTION", $currentPlayer, "$cardID&$options");
        AddDecisionQueue("SHOWOPTIONS", $currentPlayer, "$cardID&$options");
        AddDecisionQueue("MODAL", $currentPlayer, "LEIAORGANA");
      }
      break;
    case "7916724925"://Bombing Run
      $options = "Space;Ground";
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose an arena to deal 3 damage to each unit");
      AddDecisionQueue("CHOOSEOPTION", $currentPlayer, "$cardID&$options");
      AddDecisionQueue("SHOWOPTIONS", $currentPlayer, "$cardID&$options");
      AddDecisionQueue("MODAL", $currentPlayer, "BOMBINGRUN");
      break;
    case "6088773439"://Darth Vader
      global $CS_NumVillainyPlayed;
      $abilityName = GetResolvedAbilityName($cardID, $from);
      if($abilityName == "Deal Damage" && GetClassState($currentPlayer, $CS_NumVillainyPlayed) > 0) {
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY&THEIRALLY", 1);
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to deal 1 damage to", 1);
        AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "DEALDAMAGE,1,$currentPlayer", 1);
        DealDamageAsync($otherPlayer, 1, "DAMAGE", "6088773439", sourcePlayer:$currentPlayer);
      }
      break;
    case "3503494534"://Regional Governor
      if($from != "PLAY") {
        AddDecisionQueue("INPUTCARDNAME", $currentPlayer, "<-");
        AddDecisionQueue("SETDQVAR", $currentPlayer, "0", 1);
        AddDecisionQueue("PASSPARAMETER", $currentPlayer, $uniqueId, 1);
        AddDecisionQueue("ADDLIMITEDPERMANENTEFFECT", $otherPlayer, "3503494534_{0},HAND," . $otherPlayer, 1);
      }
      break;
    case "0523973552"://I Am Your Father
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "THEIRALLY", 1);
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to deal 7 damage to", 1);
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("SETDQVAR", $currentPlayer, "0", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "GETCARDID", 1);
      AddDecisionQueue("SETDQVAR", $currentPlayer, "1", 1);
      AddDecisionQueue("SETDQCONTEXT", $otherPlayer, "Do you want your opponent to deal 7 damage to <1>?");
      AddDecisionQueue("YESNO", $otherPlayer, "-");
      AddDecisionQueue("NOPASS", $otherPlayer, "-", 1);
      AddDecisionQueue("PASSPARAMETER", $currentPlayer, "{0}", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "DEALDAMAGE,7,$currentPlayer", 1);
      AddDecisionQueue("ELSE", $otherPlayer, "-");
      AddDecisionQueue("DRAW", $currentPlayer, "-", 1);
      AddDecisionQueue("DRAW", $currentPlayer, "-", 1);
      AddDecisionQueue("DRAW", $currentPlayer, "-", 1);
      break;
    case "6903722220"://Luke's Lightsaber
      if(CardTitle(GetMZCard($currentPlayer, $target)) == "Luke Skywalker") {
        $ally = new Ally($target, $currentPlayer);
        $ally->Heal($ally->MaxHealth()-$ally->Health());
        $ally->Attach("8752877738");//Shield Token
      }
      break;
    case "5494760041"://Galactic Ambition
      global $CS_AfterPlayedBy;
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to play");
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYHAND");
      AddDecisionQueue("MZFILTER", $currentPlayer, "definedType!=Unit", 1);
      AddDecisionQueue("MZFILTER", $currentPlayer, "aspect=Heroism", 1);
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("SETDQVAR", $currentPlayer, 0, 1);
      AddDecisionQueue("ADDCURRENTEFFECT", $currentPlayer, "5494760041", 1);
      AddDecisionQueue("PASSPARAMETER", $currentPlayer, "{0}", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "GETCARDID", 1);
      AddDecisionQueue("SETDQVAR", $currentPlayer, 1, 1);
      AddDecisionQueue("PASSPARAMETER", $currentPlayer, $cardID, 1);
      AddDecisionQueue("SETCLASSSTATE", $currentPlayer, $CS_AfterPlayedBy, 1);
      AddDecisionQueue("PASSPARAMETER", $currentPlayer, "{0}", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "PLAYCARD", 1);
      break;
    case "5049217986"://Overpower
      $ally = new Ally($target);
      $ally->AddRoundHealthModifier(3);
      AddCurrentTurnEffect($cardID, $currentPlayer, "PLAY", $ally->UniqueID());
      break;
      break;
    case "2651321164"://Tactical Advantage
      $ally = new Ally($target);
      $ally->AddRoundHealthModifier(2);
      AddCurrentTurnEffect($cardID, $currentPlayer, "PLAY", $ally->UniqueID());
      break;
    case "1701265931"://Moment of Glory
      $ally = new Ally($target);
      $ally->AddRoundHealthModifier(4);
      AddCurrentTurnEffect($cardID, $currentPlayer, "PLAY", $ally->UniqueID());
      break;
    case "1900571801"://Overwhelming Barrage
      if ($target != "-") {
        $ally = new Ally($target);
        $ally->AddRoundHealthModifier(2);
        AddCurrentTurnEffect($cardID, $currentPlayer, "PLAY", $ally->UniqueID());
        $amount = $ally->CurrentPower();
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY&THEIRALLY");
        AddDecisionQueue("MZFILTER", $currentPlayer, "index=" . $ally->MZIndex());
        AddDecisionQueue("PREPENDLASTRESULT", $currentPlayer, $amount . "-", 1);
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Deal " . $amount . " damage divided as you choose", 1);
        AddDecisionQueue("MAYMULTIDAMAGEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $currentPlayer, DealMultiDamageBuilder($currentPlayer, isUnitEffect:1, unitCardID:$ally->CardID()), 1);
      }
      break;
    case "3974134277"://Prepare for Takeoff
      AddDecisionQueue("SEARCHDECKTOPX", $currentPlayer, "8;2;include-trait-Vehicle&include-definedType-Unit");
      AddDecisionQueue("MULTIADDHAND", $currentPlayer, "-", 1);
      AddDecisionQueue("REVEALCARDS", $currentPlayer, "DECK", 1);
      break;
    case "3896582249"://Redemption
      if($from != "PLAY") {
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY&THEIRALLY");
        AddDecisionQueue("MZFILTER", $currentPlayer, "index=" . $playAlly->MZIndex(), 1);
        AddDecisionQueue("PREPENDLASTRESULT", $currentPlayer, "8-MYCHAR-0,THEIRCHAR-0,");
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Heal up to 8 total damage from any number of units and/or bases", 1);
        AddDecisionQueue("PARTIALMULTIHEALMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "MULTIHEAL", 1);
        AddDecisionQueue("SPECIFICCARD", $currentPlayer, "REDEMPTION," . $playAlly->UniqueID(), 1);
      }
      break;
    case "7861932582"://The Force is With Me
      $ally = new Ally($target, $currentPlayer);
      $ally->Attach("2007868442");//Experience token
      $ally->Attach("2007868442");//Experience token
      if(SearchCount(SearchAllies($currentPlayer, trait:"Force")) > 0) {
        $ally->Attach("8752877738");//Shield Token
      }
      if(!$ally->IsExhausted()) {
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Do you want to attack with the unit?");
        AddDecisionQueue("YESNO", $currentPlayer, "-");
        AddDecisionQueue("NOPASS", $currentPlayer, "-");
        AddDecisionQueue("PASSPARAMETER", $currentPlayer, $target, 1);
        AddDecisionQueue("MZOP", $currentPlayer, "ATTACK", 1);
      }
      break;
    case "9985638644"://Snapshot Reflexes
      $mzArr = explode("-", $target);
      if($mzArr[0] == "MYALLY") {
        $ally = new Ally($target);
        if(!$ally->IsExhausted()) {
          AddDecisionQueue("YESNO", $currentPlayer, "if you want to attack with " . CardLink($ally->CardID(), $ally->CardID()));
          AddDecisionQueue("NOPASS", $currentPlayer, "-");
          AddDecisionQueue("PASSPARAMETER", $currentPlayer, $target, 1);
          AddDecisionQueue("MZOP", $currentPlayer, "ATTACK", 1);
        }
      }
      break;
    case "3809048641"://Surprise Strike
      DQAttackWithEffect($currentPlayer, $cardID, $from);
      break;
    case "3038238423"://Fleet Lieutenant
      if($from != "PLAY") {
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY");
        AddDecisionQueue("MZFILTER", $currentPlayer, "status=1");
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a card to attack with");
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("SPECIFICCARD", $currentPlayer, "FLEETLIEUTENANT", 1);
      }
      break;
    case "7660822254"://Barrel Roll
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY:arena=Space");
      AddDecisionQueue("MZFILTER", $currentPlayer, "status=1");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a space unit to attack with");
      AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("ADDCURRENTEFFECT", $currentPlayer, $cardID, 1);
      AddDecisionQueue("MZOP", $currentPlayer, "ATTACK", 1);
      break;
    case "1996597848"://Cloaked StarViper
      if($from != "PLAY") {
        $playAlly->Attach("8752877738");//Shield Token
        $playAlly->Attach("8752877738");//Shield Token
      }
      break;
    case "3208391441"://Make an Opening
      Restore(2, $currentPlayer);
      DQDebuffUnit($currentPlayer,  $otherPlayer, $cardID, 2);
      break;
    case "4036958275"://Hello There
      DQDebuffUnit($currentPlayer, $otherPlayer, $cardID, 4, may:false, mzFilter:"turns=>0");
      break;
    case "5013214638"://Equalize
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY&THEIRALLY");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to give -2/-2", 1);
      AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("SETDQVAR", $currentPlayer, 0, 1);
      AddDecisionQueue("MZOP", $currentPlayer, "GETUNIQUEID", 1);
      AddDecisionQueue("SETDQVAR", $currentPlayer, 1, 1);
      AddDecisionQueue("ADDLIMITEDCURRENTEFFECT", $currentPlayer, "5013214638,PLAY", 1);
      AddDecisionQueue("PASSPARAMETER", $currentPlayer, "{0}", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "REDUCEHEALTH,2", 1);
      AddDecisionQueue("PASSPARAMETER", $currentPlayer, "{1}", 1);
      AddDecisionQueue("SPECIFICCARD", $currentPlayer, "EQUALIZE", 1);
      break;
    case "5329736697"://Jump to Lightspeed card
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY:arena=Space");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a space unit to bounce");
      AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("SPECIFICCARD", $currentPlayer, "JUMPTOLIGHTSPEED", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "BOUNCE", 1);
      break;
    case "6588309727"://All Wings Report In
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY:arena=Space");
      AddDecisionQueue("MZFILTER", $currentPlayer, "status=1", 1);
      AddDecisionQueue("OP", $currentPlayer, "MZTONORMALINDICES", 1);
      AddDecisionQueue("PREPENDLASTRESULT", $currentPlayer, "2-", 1);
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose up to two friendly space units to exhaust", 1);
      AddDecisionQueue("MULTICHOOSEUNIT", $currentPlayer, "<-", 1);
      AddDecisionQueue("SPECIFICCARD", $currentPlayer, "ALLWINGSREPORTIN", 1);
      break;
    case "3278986026"://Rafa Martez
      if($from != "PLAY") RafaMartezJTL($currentPlayer);
      break;
    case "3148212344"://Admiral Yularen
      if($from != "PLAY") {
        $options = "Grit;Restore 1;Sentinel;Shielded";
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose one");
        AddDecisionQueue("CHOOSEOPTION", $currentPlayer, "$cardID&$options");
        AddDecisionQueue("SHOWOPTIONS", $currentPlayer, "$cardID&$options");
        AddDecisionQueue("MODAL", $currentPlayer, "YULAREN_JTL,$uniqueId");
      }
      break;
    case "7039711282"://Sweep the Area
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY&THEIRALLY");
      AddDecisionQueue("MZFILTER", $currentPlayer, "leader=1", 1);
      AddDecisionQueue("PREPENDLASTRESULT", $currentPlayer, "2-", 1);
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose up to two units", 1);
      AddDecisionQueue("MULTICHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("SPECIFICCARD", $currentPlayer, "SWEEPTHEAREA", 1);
      break;
    case "2579248092"://Covering the Wing
      $xWingUniqueId = CreateXWing($currentPlayer);
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY&THEIRALLY");
      AddDecisionQueue("MZFILTER", $currentPlayer, "uniqueID=" . $xWingUniqueId, 1);
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to give a shield", 1);
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "ADDSHIELD", 1);
      break;
    case "8582806124"://The Annihilator
      if($from != "PLAY") TheAnnihilatorJTL($currentPlayer);
      break;
    case "8736422150"://Close the Shield Gate
      AddCurrentTurnEffect($cardID, $currentPlayer);
      break;
    case "3622750563"://Dornean Gunship
      if($from != "PLAY") {
        $vehicleCount = SearchCount(SearchAllies($currentPlayer, trait:"Vehicle"));
        IndirectDamage($cardID, $currentPlayer, $vehicleCount, true, $playAlly->UniqueID());
      }
      break;
    case "8606123385"://Lightspeed Assault
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY:arena=Space");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a friendly space unit to defeat");
      AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("SPECIFICCARD", $currentPlayer, "LIGHTSPEEDASSAULT", 1);
      break;
    case "2062827036"://Do or Do Not
      if(HasTheForce($currentPlayer)) {
        AddDecisionQueue("YESNO", $currentPlayer, "if you want to use the Force");
        AddDecisionQueue("SPECIFICCARD", $currentPlayer, "DO_OR_DO_NOT", 1);
      } else {
        Draw($currentPlayer, true);
      }
      break;
    case "7730475388"://Shoot Down
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY:arena=Space&THEIRALLY:arena=Space");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a space unit to deal 3 damage to");
      AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("SPECIFICCARD", $currentPlayer, "SHOOTDOWN", 1);
      break;
    case "7456670756"://Torpedo Barrage
      $otherPlayer = $currentPlayer == 1 ? 2 : 1;
      IndirectDamage($cardID, $currentPlayer, 5, false);
      break;
    case "6938023363"://Piercing Shot
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY&THEIRALLY");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to deal 3 damage to");
      AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("SPECIFICCARD", $currentPlayer, "PIERCINGSHOT", 1);
      break;
    case "5540797366"://Rebellious Hammerhand
      if($from != "PLAY") {
        $hand = &GetHand($currentPlayer);
        $numCards = count($hand)/HandPieces();
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY&THEIRALLY");
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to deal " . $numCards . " damage to");
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "DEALDAMAGE," . $numCards . ",$currentPlayer,1", 1);
      }
      break;
    case "5941636047"://Resistance Blue Squadron
      if($from != "PLAY") {
        $spaceUnits = SearchCount(SearchAllies($currentPlayer, arena: "Space"));
        DQPingUnit($currentPlayer, $spaceUnits, isUnitEffect:true, may:true);
      }
      break;
    case "2758597010"://Maximum Firepower
      AddDecisionQueue("PASSPARAMETER", $currentPlayer, "-", 1);
      AddDecisionQueue("SETDQVAR", $currentPlayer, 0, 1);
      for($i=0; $i<2; ++$i) {
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY:trait=Imperial");
        AddDecisionQueue("MZFILTER", $currentPlayer, "dqVar=0", 1);
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a card to deal damage", 1);
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("APPENDDQVAR", $currentPlayer, 0, 1);
        AddDecisionQueue("MZOP", $currentPlayer, "POWER", 1);
        AddDecisionQueue("SETDQVAR", $currentPlayer, 1, 1);
        AddDecisionQueue("PASSPARAMETER", $currentPlayer, $target, 1);
        AddDecisionQueue("MZOP", $currentPlayer, "DEALDAMAGE,{1},$currentPlayer,1", 1);
      }
      break;
    case "4263394087"://Chirrut Imwe
      $abilityName = GetResolvedAbilityName($cardID, $from);
      if($abilityName == "Buff HP") {
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a card to give +2 hp");
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY&THEIRALLY");
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "ADDHEALTH,2", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "GETUNIQUEID", 1);
        AddDecisionQueue("ADDLIMITEDCURRENTEFFECT", $currentPlayer, "4263394087,HAND", 1);
      }
      break;
    case "5154172446"://ISB Agent
      if($from != "PLAY") {
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a card to reveal");
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYHAND:definedType=Event");
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "GETCARDID", 1);
        AddDecisionQueue("REVEALCARDS", $currentPlayer, "HAND", 1);
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to deal 1 damage", 1);
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY&THEIRALLY", 1);
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "DEALDAMAGE,1,$currentPlayer,1", 1);
      }
      break;
    case "7144880397"://Ahsoka Tano TWI
      $abilityName = GetResolvedAbilityName($cardID, $from);
      if ($from == "PLAY" && $abilityName == "Return" && $playAlly->Exists()) {
        $upgrades = $playAlly->GetUpgrades(true);
        for($i=0; $i<count($upgrades); $i+=SubcardPieces()) {
          $playAlly->RemoveSubcard($upgrades[$i], skipDestroy:true);
          if (!IsToken($upgrades[$i]) && !CardIDIsLeader($upgrades[$i])) {
            AddHand($upgrades[$i+1], $upgrades[$i]);
          }
        }
        MZBounce($currentPlayer, $playAlly->MZIndex());
      }
      break;
    case "4300219753"://Fett's Firespray
      if($from != "PLAY") {
        $ready = false;
        if(ControlsNamedCard($currentPlayer, "Boba Fett") || ControlsNamedCard($currentPlayer, "Jango Fett")) $ready = true;
        if($ready) {
          $playAlly->Ready();
        }
      } else {
        $abilityName = GetResolvedAbilityName($cardID, $from);
        if($abilityName == "Exhaust") {
          AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY&THEIRALLY");
          AddDecisionQueue("MZFILTER", $currentPlayer, "unique=1");
          AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to exhaust");
          AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
          AddDecisionQueue("MZOP", $currentPlayer, "REST", 1);
        }
      }
      break;
    case "0595607848"://Disaffected Senator
      $abilityName = GetResolvedAbilityName($cardID, $from);
      if ($abilityName == "Deal Damage") {
        AddDecisionQueue("PASSPARAMETER", $currentPlayer, "MYCHAR-0,THEIRCHAR-0");
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a base to deal 2 damage");
        AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "DEALDAMAGE,2,$currentPlayer,1", 1);
      }
      break;
    case "8009713136"://C-3PO
      C3POSOR($currentPlayer);
      break;
    case "7911083239"://Grand Inquisitor
      $abilityName = GetResolvedAbilityName($cardID, $from);
      if($abilityName == "Deal Damage") {
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to deal 2 damage and ready");
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY:maxAttack=3");
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "DEALDAMAGE,2,$currentPlayer", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "READY", 1);
      }
      break;
    case "5954056864"://Han Solo
      $abilityName = GetResolvedAbilityName($cardID, $from);
      if($abilityName == "Play Resource") {
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a card to resource");
        MZMoveCard($currentPlayer, "MYHAND", "MYRESOURCES", may:false, silent:true);
        AddNextTurnEffect($cardID, $currentPlayer);
      }
      break;
    case "5630404651"://MagnaGuard Wing Leader
      $ally = new Ally("MYALLY-" . $index);
      $abilityName = GetResolvedAbilityName($cardID, $from);
      if ($abilityName == "Droid Attack") {
        if ($ally->NumUses() > 0) {
          AddDecisionQueue("PASSPARAMETER", $currentPlayer, "MYALLY-" . $index);
          AddDecisionQueue("ADDMZUSES", $currentPlayer, "-1");
          AddCurrentTurnEffect($cardID . "-1", $currentPlayer);
          AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY:trait=Droid");
          AddDecisionQueue("MZFILTER", $currentPlayer, "status=1", 1);
          AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a droid to attack with", 1);
          AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
          AddDecisionQueue("MZOP", $currentPlayer, "ATTACK", 1);
        } else {
          WriteLog("<span style='color: red;'>You can use this ability only once each round. Reverting gamestate.</span>");
          RevertGamestate();
        }
      }
      break;
    case "6514927936"://Leia Organa Leader
      $abilityName = GetResolvedAbilityName($cardID, $from);
      if($abilityName == "Attack") {
        AddCurrentTurnEffect($cardID . "-1", $currentPlayer);
        AddDecisionQueue("PASSPARAMETER", $currentPlayer, "{0}", 1);
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY:trait=Rebel");
        AddDecisionQueue("MZFILTER", $currentPlayer, "status=1", 1);
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to attack with");
        AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "ATTACK", 1);
      }
      break;
    case "8055390529"://Traitorous
      $mzArr = explode("-", $target);
      $ally = new Ally($target);
      if(CardCost($ally->CardID()) <= 3 && $mzArr[0] == "THEIRALLY" && !$ally->IsLeader()) {
        AddDecisionQueue("PASSPARAMETER", $currentPlayer, $target);
        AddDecisionQueue("MZOP", $currentPlayer, "GETUNIQUEID");
        AddDecisionQueue("MZOP", $currentPlayer, "TAKECONTROL");
      }
      break;
    case "8244682354"://Jyn Erso
      $abilityName = GetResolvedAbilityName($cardID, $from);
      if($abilityName == "Attack") {
        AddCurrentTurnEffect($cardID, $otherPlayer);
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY");
        AddDecisionQueue("MZFILTER", $currentPlayer, "status=1");
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to attack with");
        AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "ATTACK", 1);
      }
      break;
    case "8327910265"://Energy Conversion Lab (ECL)
      AddCurrentTurnEffect($cardID, $currentPlayer, "PLAY");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a card to put into play");
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYHAND:definedType=Unit;maxCost=6");
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "PLAYCARD", 1);
      break;
    case "8600121285"://IG-88
      $abilityName = GetResolvedAbilityName($cardID, $from);
      if($abilityName == "Attack") {
        if(HasMoreUnits($currentPlayer)) AddCurrentTurnEffect($cardID, $currentPlayer);
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY");
        AddDecisionQueue("MZFILTER", $currentPlayer, "status=1");
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to attack with");
        AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "ATTACK", 1);
      }
      break;
    case "6954704048"://Heroic Sacrifice
      Draw($currentPlayer);
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY");
      AddDecisionQueue("MZFILTER", $currentPlayer, "status=1");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to attack with");
      AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("ADDCURRENTEFFECT", $currentPlayer, "6954704048", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "ATTACK", 1);
      break;
    case "4113123883"://Unnatural Life
      global $CS_AfterPlayedBy;
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYDISCARD:definedType=Unit;defeatedThisPhase=true");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a card to put into play");
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("SETDQVAR", $currentPlayer, "0", 1);
      AddDecisionQueue("PASSPARAMETER", $currentPlayer, $cardID, 1);
      AddDecisionQueue("SETCLASSSTATE", $currentPlayer, $CS_AfterPlayedBy, 1);
      AddDecisionQueue("ADDCURRENTEFFECT", $currentPlayer, $cardID, 1);
      AddDecisionQueue("PASSPARAMETER", $currentPlayer, "{0}", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "PLAYCARD", 1);
      break;
    case "3426168686"://Sneak Attack
      global $CS_AfterPlayedBy;
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a card to put into play");
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYHAND:definedType=Unit");
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("SETDQVAR", $currentPlayer, "0", 1);
      AddDecisionQueue("PASSPARAMETER", $currentPlayer, $cardID, 1);
      AddDecisionQueue("SETCLASSSTATE", $currentPlayer, $CS_AfterPlayedBy, 1);
      AddDecisionQueue("ADDCURRENTEFFECT", $currentPlayer, $cardID, 1);
      AddDecisionQueue("PASSPARAMETER", $currentPlayer, "{0}", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "PLAYCARD", 1);
      break;
    case "8800836530"://No Good To Me Dead
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "THEIRALLY&MYALLY");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a card to exhaust");
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "REST", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "GETUNIQUEID", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "WRITECHOICE", 1);
      AddDecisionQueue("ADDLIMITEDROUNDEFFECT", $otherPlayer, "8800836530", 1);
      break;
    case "9097690846"://Snowtrooper Lieutenant
      if($from != "PLAY") {
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY");
        AddDecisionQueue("MZFILTER", $currentPlayer, "status=1");
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a card to attack with");
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("SETDQVAR", $currentPlayer, "0");
        AddDecisionQueue("MZALLCARDTRAITORPASS", $currentPlayer, "Imperial", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "GETUNIQUEID", 1);
        AddDecisionQueue("ADDLIMITEDCURRENTEFFECT", $currentPlayer, "9097690846", 1);
        AddDecisionQueue("PASSPARAMETER", $currentPlayer, "{0}");
        AddDecisionQueue("MZOP", $currentPlayer, "ATTACK", 1);
      }
      break;
    case "9210902604"://Precision Fire
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY");
      AddDecisionQueue("MZFILTER", $currentPlayer, "status=1");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a card to attack with");
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("SETDQVAR", $currentPlayer, "0", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "GETUNIQUEID", 1);
      AddDecisionQueue("ADDLIMITEDCURRENTEFFECT", $currentPlayer, "9210902604", 1);
      AddDecisionQueue("PASSPARAMETER", $currentPlayer, "{0}", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "ATTACK", 1);
      break;
    case "6476609909"://Corner The Prey
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY");
      AddDecisionQueue("MZFILTER", $currentPlayer, "status=1");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a card to attack with");
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("SETDQVAR", $currentPlayer, "0", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "GETUNIQUEID", 1);
      AddDecisionQueue("ADDLIMITEDCURRENTEFFECT", $currentPlayer, "6476609909", 1);
      AddDecisionQueue("PASSPARAMETER", $currentPlayer, "{0}", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "ATTACK", 1);
      break;
    case "7870435409"://Bib Fortuna
      $abilityName = GetResolvedAbilityName($cardID, $from);
      if($abilityName == "Play Event") {
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose an event to play");
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYHAND:definedType=Event");
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("ADDCURRENTEFFECT", $currentPlayer, $cardID, 1);
        AddDecisionQueue("MZOP", $currentPlayer, "PLAYCARD", 1);
      }
      break;
    case "8297630396"://Shoot First
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY");
      AddDecisionQueue("MZFILTER", $currentPlayer, "status=1");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a card to attack with");
      AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("SETDQVAR", $currentPlayer, "0", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "GETUNIQUEID", 1);
      AddDecisionQueue("ADDLIMITEDCURRENTEFFECT", $currentPlayer, "8297630396", 1);
      AddDecisionQueue("PASSPARAMETER", $currentPlayer, "{0}", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "ATTACK", 1);
      break;
    case "5767546527"://For a Cause I Believe In
      $deck = new Deck($currentPlayer);
      $deck->Reveal(4);
      $cards = $deck->Top(remove:true, amount:4);
      $cardArr = explode(",", $cards);
      $damage = 0;
      for($i=0; $i<count($cardArr); ++$i) {
        if(AspectContains($cardArr[$i], "Heroism", $currentPlayer)) {
          ++$damage;
        }
      }
      WriteLog(CardLink($cardID, $cardID) . " is dealing " . $damage . " damage.");
      DealDamageAsync($otherPlayer, $damage, "DAMAGE", "5767546527", sourcePlayer:$currentPlayer);
      if($cards != "") {
        AddDecisionQueue("PASSPARAMETER", $currentPlayer, $cards);
        AddDecisionQueue("SETDQVAR", $currentPlayer, 0);
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Pass to discard the rest of the cards");
        AddDecisionQueue("MAYCHOOSETOPREVEALED", $currentPlayer, $cards);
        AddDecisionQueue("SPECIFICCARD", $currentPlayer, "FORACAUSEIBELIEVEIN");
      }
      break;
    case "5784497124"://Emperor Palpatine
      $abilityName = GetResolvedAbilityName($cardID, $from);
      if($abilityName == "Deal Damage") {
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose an ally to destroy");
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY");
        AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "DESTROY,$currentPlayer", 1);
        AddDecisionQueue("DRAW", $currentPlayer, "-", 1);
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose an ally to deal 1 damage");
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY&THEIRALLY");
        AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "DEALDAMAGE,1,$currentPlayer", 1);
      }
      break;
    case "8117080217"://Admiral Ozzel
      $abilityName = GetResolvedAbilityName($cardID, $from);
      if($abilityName == "Play Imperial Unit") {
        global $CS_AfterPlayedBy;
        SetClassState($currentPlayer, $CS_AfterPlayedBy, $cardID);
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a card to put into play");
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYHAND:definedType=Unit;trait=Imperial");
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "PLAYCARD", 1);
      }
      break;
    case "1626462639"://Change of Heart
      DQTakeControlOfANonLeaderUnit($currentPlayer);
      AddDecisionQueue("ADDLIMITEDROUNDEFFECT", $currentPlayer, "1626462639,PLAY", 1);
      break;
    case "2855740390"://Lieutenant Childsen
      if($from != "PLAY" && $playAlly->Exists()) {
        AddDecisionQueue("FINDINDICES", $currentPlayer, "HANDASPECT,Vigilance");
        AddDecisionQueue("PREPENDLASTRESULT", $currentPlayer, "4-", 1);
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose up to 4 cards to reveal", 1);
        AddDecisionQueue("MULTICHOOSEHAND", $currentPlayer, "<-", 1);
        AddDecisionQueue("SPECIFICCARD", $currentPlayer, "LTCHILDSEN", 1);
      }
      break;
    case "8506660490"://Darth Vader (Commanding the First Legion)
      if($from != "PLAY") {
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose any number of units with combined cost 3 or less.");
        AddDecisionQueue("SEARCHDECKTOPX", $currentPlayer, "10;99;include-definedType-Unit&include-maxCost-3&include-aspect-Villainy");
        AddDecisionQueue("SPECIFICCARD", $currentPlayer, "DARTHVADER", 1);
      }
      break;
    case "3789633661"://Cunning
      $options = "Return a non-leader unit with 4 or less power to its owner's hand;Give a unit +4/+0 for this phase;Exhaust up to 2 units;An opponent discards a random card from their hand";
      AddDecisionQueue("PASSPARAMETER", $currentPlayer, "-");
      AddDecisionQueue("SETDQVAR", $currentPlayer, "0");
      for ($i = 0; $i < 2; ++$i) {
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose " . ($i == 0 ? "First" : "Second") . " Cunning Ability");
        AddDecisionQueue("CHOOSEOPTION", $currentPlayer, "$cardID&$options&{0}");
        AddDecisionQueue("APPENDDQVAR", $currentPlayer, "0");
        AddDecisionQueue("SHOWOPTIONS", $currentPlayer, "$cardID&$options", 1);
        AddDecisionQueue("MODAL", $currentPlayer, "CUNNING", 1);
      }
      break;
    case "8615772965"://Vigilance
      $options = "Discard 6 cards from an opponent's deck;Heal 5 damage from a base;Defeat a unit with 3 or less remaining HP;Give a Shield token to a unit";
      AddDecisionQueue("PASSPARAMETER", $currentPlayer, "-");
      AddDecisionQueue("SETDQVAR", $currentPlayer, "0");
      for ($i = 0; $i < 2; ++$i) {
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose " . ($i == 0 ? "First" : "Second") . " Vigilance Ability");
        AddDecisionQueue("CHOOSEOPTION", $currentPlayer, "$cardID&$options&{0}");
        AddDecisionQueue("APPENDDQVAR", $currentPlayer, "0");
        AddDecisionQueue("SHOWOPTIONS", $currentPlayer, "$cardID&$options", 1);
        AddDecisionQueue("MODAL", $currentPlayer, "VIGILANCE", 1);
      }
      break;
    case "0073206444"://Command
      $options = "Give 2 Experience tokens to a unit;A friendly unit deals damage equal to its power to a non-unique enemy unit;Put this event into play as a resource;Return a unit from your discard pile to your hand";
      AddDecisionQueue("PASSPARAMETER", $currentPlayer, "-");
      AddDecisionQueue("SETDQVAR", $currentPlayer, "0");
      for ($i = 0; $i < 2; ++$i) {
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose " . ($i == 0 ? "First" : "Second") . " Command Ability");
        AddDecisionQueue("CHOOSEOPTION", $currentPlayer, "$cardID&$options&{0}");
        AddDecisionQueue("APPENDDQVAR", $currentPlayer, "0");
        AddDecisionQueue("SHOWOPTIONS", $currentPlayer, "$cardID&$options", 1);
        AddDecisionQueue("MODAL", $currentPlayer, "COMMAND", 1);
      }
      break;
    case "3736081333"://Aggression
      $options = "Draw a card;Defeat up to 2 upgrades;Ready a unit with 3 or less power;Deal 4 damage to a unit";
      AddDecisionQueue("PASSPARAMETER", $currentPlayer, "-");
      AddDecisionQueue("SETDQVAR", $currentPlayer, "0");
      for ($i = 0; $i < 2; ++$i) {
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose " . ($i == 0 ? "First" : "Second") . " Aggression Ability");
        AddDecisionQueue("CHOOSEOPTION", $currentPlayer, "$cardID&$options&{0}");
        AddDecisionQueue("APPENDDQVAR", $currentPlayer, "0");
        AddDecisionQueue("SHOWOPTIONS", $currentPlayer, "$cardID&$options", 1);
        AddDecisionQueue("MODAL", $currentPlayer, "AGGRESSION", 1);
      }
      break;
    case "2471223947"://Frontline Shuttle
      $abilityName = GetResolvedAbilityName($cardID, $from);
      if($abilityName == "Shuttle") {
        $ally = new Ally("MYALLY-" . $index);
        $ally->Destroy();
        AttackWithMyUnitEvenIfExhaustedNoBases($currentPlayer);
      }
      break;
    case "8968669390"://U-Wing Reinforcement
      AddDecisionQueue("SEARCHDECKTOPX", $currentPlayer, "10;3;include-definedType-Unit&include-maxCost-7");
      AddDecisionQueue("SPECIFICCARD", $currentPlayer, "UWINGREINFORCEMENT", 1);
      break;
    case "7510418786"://Aid From The Innocent
      AddDecisionQueue("SEARCHDECKTOPX", $currentPlayer, "10;2;exclude-definedType-Unit&include-aspect-Heroism");
      AddDecisionQueue("MULTIADDDISCARD", $currentPlayer, "HAND,TT-2", 1);
      AddDecisionQueue("REVEALCARDS", $currentPlayer, "DECK", 1);
      break;
    case "5950125325"://Confiscate
      DefeatUpgrade($currentPlayer);
      break;
    case "2668056720"://Disabling Fang Fighter
      if($from != "PLAY") DefeatUpgrade($currentPlayer, true);
      break;
    case "4323691274"://Power Failure
      DefeatUpgrade($currentPlayer);
      AddDecisionQueue("SPECIFICCARD", $currentPlayer, "POWERFAILURE", 1);
      break;
    case "6087834273"://Restock
        $options = "My Discard;Their Discard";
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a discard to Restock");
        AddDecisionQueue("CHOOSEOPTION", $currentPlayer, "$cardID&$options");
        AddDecisionQueue("SHOWOPTIONS", $currentPlayer, "$cardID&$options");
        AddDecisionQueue("MODAL", $currentPlayer, "RESTOCK");
      break;
    case "5035052619"://Jabba the Hutt
      if($from != "PLAY") {
        AddDecisionQueue("SEARCHDECKTOPX", $currentPlayer, "8;1;include-trait-Trick&include-definedType-Event");
        AddDecisionQueue("ADDHAND", $currentPlayer, "-", 1);
        AddDecisionQueue("REVEALCARDS", $currentPlayer, "DECK", 1);
      }
      break;
    case "9644107128"://Bamboozle
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY&THEIRALLY");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to exhaust");
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "REST", 1);
      AddDecisionQueue("SPECIFICCARD", $currentPlayer, "BAMBOOZLE", 1);
      break;
    case "2639435822"://Force Lightning
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY&THEIRALLY");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to lose abilities");
      AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("SETDQVAR", $currentPlayer, "0", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "GETUNIQUEID", 1);
      AddDecisionQueue("ADDLIMITEDCURRENTEFFECT", $otherPlayer, "2639435822,PLAY", 1);
      if(HasUnitWithTraitInPlay($currentPlayer, "Force")) {
        AddDecisionQueue("PASSPARAMETER", $currentPlayer, "{0}", 1);
        AddDecisionQueue("SPECIFICCARD", $currentPlayer, "FORCE_LIGHTNING", 1);
      }
      break;
    case "1951911851"://Grand Admiral Thrawn
      $abilityName = GetResolvedAbilityName($cardID, $from);
      if($abilityName == "Exhaust") {
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose player to reveal top of deck");
        AddDecisionQueue("BUTTONINPUT", $currentPlayer, "Yourself,Opponent");
        AddDecisionQueue("SPECIFICCARD", $currentPlayer, "GRANDADMIRALTHRAWN", 1);
      }
      break;
    case "9785616387"://The Emperor's Legion
      AddDecisionQueue("SPECIFICCARD", $currentPlayer, "THEEMPERORSLEGION");
      break;
    case "1939951561"://Attack Pattern Delta
      AddDecisionQueue("PASSPARAMETER", $currentPlayer, "-", 1);
      AddDecisionQueue("SETDQVAR", $currentPlayer, 0, 1);
      for($i=3; $i>0; --$i) {
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY");
        AddDecisionQueue("MZFILTER", $currentPlayer, "dqVar=0");
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to give +" . $i . "/+" . $i, 1);
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("APPENDDQVAR", $currentPlayer, 0, 1);
        AddDecisionQueue("MZOP", $currentPlayer, "ADDHEALTH," . $i, 1);
        AddDecisionQueue("MZOP", $currentPlayer, "GETUNIQUEID", 1);
        AddDecisionQueue("ADDLIMITEDCURRENTEFFECT", $currentPlayer, "1939951561_" . $i . ",PLAY", 1);
      }
      break;
    case "2202839291"://Don't Get Cocky
      AddDecisionQueue("PASSPARAMETER", $currentPlayer, $target);
      AddDecisionQueue("SETDQVAR", $currentPlayer, 0);
      AddDecisionQueue("PASSPARAMETER", $currentPlayer, 0);
      AddDecisionQueue("SETDQVAR", $currentPlayer, 1);
      AddDecisionQueue("SPECIFICCARD", $currentPlayer, "DONTGETCOCKY");
      break;
    case "2715652707"://I Had No Choice
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY&THEIRALLY");
      AddDecisionQueue("MZFILTER", $currentPlayer, "leader=1");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit");
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("OP", $otherPlayer, "SWAPDQPERSPECTIVE", 1);
      AddDecisionQueue("SETDQVAR", $currentPlayer, 0, 1);
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY&THEIRALLY", 1);
      AddDecisionQueue("MZFILTER", $currentPlayer, "leader=1", 1);
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit", 1);
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("OP", $otherPlayer, "SWAPDQPERSPECTIVE", 1);
      AddDecisionQueue("PREPENDLASTRESULT", $currentPlayer, "{0},", 1);
      AddDecisionQueue("SETDQVAR", $currentPlayer, 0, 1);
      AddDecisionQueue("SETDQCONTEXT", $otherPlayer, "Choose a unit to return to hand");
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $otherPlayer, "<-", 1);
      AddDecisionQueue("SPECIFICCARD", $otherPlayer, "IHADNOCHOICE", 1);
      break;
    case "8988732248"://Rebel Assault
      AddCurrentTurnEffect($cardID . "-1", $currentPlayer);
      AddDecisionQueue("PASSPARAMETER", $currentPlayer, "{0}", 1);
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY:trait=Rebel");
      AddDecisionQueue("MZFILTER", $currentPlayer, "status=1");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to attack with");
      AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "GETUNIQUEID", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "ATTACK", 1);
      break;
    case "0802973415"://Outflank
      AddCurrentTurnEffect($cardID, $currentPlayer);
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY");
      AddDecisionQueue("MZFILTER", $currentPlayer, "status=1");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to attack with");
      AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "ATTACK", 1);
      break;
    case "5896817672"://Headhunting
      AddCurrentTurnEffect($cardID . "-1", $currentPlayer);
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY");
      AddDecisionQueue("MZFILTER", $currentPlayer, "status=1");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to attack with");
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("SETDQVAR", $currentPlayer, "0");
      AddDecisionQueue("PASSPARAMETER", $currentPlayer, 1, 1);
      AddDecisionQueue("SETCOMBATCHAINSTATE", $currentPlayer, $CCS_CantAttackBase, 1);
      AddDecisionQueue("PASSPARAMETER", $currentPlayer, "{0}", 1);
      AddDecisionQueue("MZALLCARDTRAITORPASS", $currentPlayer, "Bounty Hunter", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "GETUNIQUEID", 1);
      AddDecisionQueue("ADDLIMITEDCURRENTEFFECT", $currentPlayer, "5896817672", 1);
      AddDecisionQueue("PASSPARAMETER", $currentPlayer, "{0}");
      AddDecisionQueue("MZOP", $currentPlayer, "ATTACK", 1);
      AddDecisionQueue("REMOVECURRENTEFFECT", $currentPlayer, $cardID . "-1");
      break;
    case "8142386948"://Razor Crest
      MZMoveCard($currentPlayer, "MYDISCARD:definedType=Upgrade", "MYHAND", may:true);
      break;
    case "3228620062"://Cripple Authority
      Draw($currentPlayer);
      if(NumResources($otherPlayer) > NumResources($currentPlayer)) {
        PummelHit($otherPlayer);
      }
      break;
    case "6722700037"://Doctor Pershing
      $abilityName = GetResolvedAbilityName($cardID, $from);
      if($abilityName == "Draw") {
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY");
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to deal 1 damage");
        AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "DEALDAMAGE,1,$currentPlayer,1", 1);
        AddDecisionQueue("DRAW", $currentPlayer, "-", 1);
      }
      break;
    case "3433996932"://Heavy Missile Gunship
      $abilityName = GetResolvedAbilityName($cardID, $from);
      if($abilityName == "Damage") {
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "THEIRALLY:arena=Ground&MYALLY:arena=Ground");
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a ground unit to damage");
        AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "DEALDAMAGE,2", 1);
      }
      break;
    case "3848295601"://Craving Power
      $ally = new Ally($target, $currentPlayer);
      $damage = $ally->CurrentPower();
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "THEIRALLY");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to deal damage to equal to the attached unit's power");
      AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "DEALDAMAGE,$damage,$currentPlayer,1", 1);
      break;
    case "0398004943"://Vanee
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY:hasUpgradeOnly=true");
      AddDecisionQueue("MZFILTER", $currentPlayer, "upgrade=2007868442", 1); // Experience token
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "You may defeat an experience token on a friendly unit");
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("SETDQVAR", $currentPlayer, "0", 1);
      AddDecisionQueue("PASSPARAMETER", $currentPlayer, "2007868442", 1);
      AddDecisionQueue("OP", $currentPlayer, "DEFEATUPGRADE", 1);
      AddDecisionQueue("PASSPARAMETER", $currentPlayer, "-", 1);
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY", 1);
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a friendly unit to give an experience token to", 1);
      AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "ADDEXPERIENCE", 1);
      break;
    case "0466077140"://A Time of Crisis
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit you control to protect");
      AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("DAMAGEALLOTHERUNITS", $currentPlayer, "3", 1);
      AddDecisionQueue("MULTIZONEINDICES", $otherPlayer, "MYALLY");
      AddDecisionQueue("SETDQCONTEXT", $otherPlayer, "Choose a unit you control to protect");
      AddDecisionQueue("CHOOSEMULTIZONE", $otherPlayer, "<-", 1);
      AddDecisionQueue("DAMAGEALLOTHERUNITS", $otherPlayer, "3", 1);
      break;
    case "3893171959"://Kaadu
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a friendly unit to give Overwhelm");
      AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("ADDCURRENTEFFECT", $currentPlayer, $cardID, 1);
      break;
    case "4024881604"://Adept of Anger
      $abilityName = GetResolvedAbilityName($cardID, $from);
      if($abilityName == "Exhaust" && HasTheForce($currentPlayer)) {
        UseTheForce($currentPlayer);
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "THEIRALLY");
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to exhaust");
        AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "REST", 1);
      }
      break;
    case "6536128825"://Grogu
      $abilityName = GetResolvedAbilityName($cardID, $from);
      if($abilityName == "Exhaust") {
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "THEIRALLY");
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to exhaust");
        AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "REST", 1);
      }
      break;
      case "3258646001"://Steadfast Senator
      $abilityName = GetResolvedAbilityName($cardID, $from);
      if($abilityName == "Buff") {
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY");
        AddDecisionQueue("MZFILTER", $currentPlayer, "status=1");
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a card to attack and give +2");
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("SETDQVAR", $currentPlayer, "0", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "GETUNIQUEID", 1);
        AddDecisionQueue("ADDLIMITEDCURRENTEFFECT", $currentPlayer, "3258646001,HAND", 1);
        AddDecisionQueue("PASSPARAMETER", $currentPlayer, "{0}", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "ATTACK", 1);
      }
      break;
    case "9262288850"://Independent Senator
      $abilityName = GetResolvedAbilityName($cardID, $from);
      if($abilityName == "Exhaust") {
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY:maxAttack=4&THEIRALLY:maxAttack=4");
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to exhaust");
        AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "REST", 1);
      }
      break;
    case "6585115122"://The Mandalorian unit
      if($from != "PLAY") {
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY:maxCost=2&THEIRALLY:maxCost=2");
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to heal and shield");
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "RESTORE,999", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "ADDSHIELD", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "ADDSHIELD", 1);
      }
      break;
    case "3329959260"://Fell the Dragon
      MZChooseAndDestroy($currentPlayer, "MYALLY:minAttack=5&THEIRALLY:minAttack=5", filter:"leader=1");
      break;
    case "0282219568"://Clan Wren Rescuer
      if($from != "PLAY") {
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY&THEIRALLY");
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to add experience");
        AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "ADDEXPERIENCE", 1);
      }
      break;
    case "1081897816"://Mandalorian Warrior
      if($from != "PLAY") {
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY:trait=Mandalorian&THEIRALLY:trait=Mandalorian");
        AddDecisionQueue("MZFILTER", $currentPlayer, "index=MYALLY-" . $playAlly->Index());
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to add experience");
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "ADDEXPERIENCE", 1);
      }
      break;
    case "0866321455"://Smuggler's Aid
      Restore(3, $currentPlayer);
      break;
    case "1090660242"://The Client
      $abilityName = GetResolvedAbilityName($cardID, $from);
      if($abilityName == "Bounty") {
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY&THEIRALLY");
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to give the bounty");
        AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "GETUNIQUEID", 1);
        AddDecisionQueue("ADDLIMITEDCURRENTEFFECT", $currentPlayer, "1090660242-2,PLAY", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "WRITECHOICEFROMUNIQUE", 1);
      }
      break;
    case "1565760222"://Remnant Reserves
      AddDecisionQueue("SEARCHDECKTOPX", $currentPlayer, "5;3;include-definedType-Unit");
      AddDecisionQueue("MULTIADDHAND", $currentPlayer, "-", 1);
      AddDecisionQueue("REVEALCARDS", $currentPlayer, "DECK", 1);
      break;
    case "2288926269"://Privateer Crew
      if($from == "RESOURCES") {
        for($i=0; $i<3; ++$i) $playAlly->Attach("2007868442");//Experience token
      }
      break;
    case "2470093702"://Wrecker
      MZChooseAndDestroy($currentPlayer, "MYRESOURCES", may:true, context:"Choose a resource to destroy");
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY:arena=Ground&THEIRALLY:arena=Ground", 1);
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a ground unit to deal 5 damage to", 1);
      AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "DEALDAMAGE,5,$currentPlayer,1", 1);
      break;
    case "1885628519"://Crosshair
      if($from != "PLAY") break;
      $ally = new Ally("MYALLY-" . $index);
      $abilityName = GetResolvedAbilityName($cardID, $from);
      if($abilityName == "Buff") {
        AddCurrentTurnEffect("1885628519", $currentPlayer, $from, $ally->UniqueID());
      } else if($abilityName == "Snipe") {
        $currentPower = $ally->CurrentPower();
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "THEIRALLY:arena=Ground", 1);
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a ground unit to deal " . $currentPower . " damage to", 1);
        AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "DEALDAMAGE,$currentPower,$currentPlayer,1", 1);
      }
      break;
    case "3514010297"://Mandalorian Armor
      $ally = new Ally($target);
      if(TraitContains(GetMZCard($ally->PlayerID(), $target), "Mandalorian", $ally->PlayerID(), $ally->Index())) {
        $ally->Attach("8752877738");//Shield Token
      }
      break;
    case "1480894253"://Kylo Ren
      PummelHit($currentPlayer);
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY&THEIRALLY");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to give +2 power", 1);
      AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "GETUNIQUEID", 1);
      AddDecisionQueue("ADDLIMITEDCURRENTEFFECT", $currentPlayer, "1480894253,PLAY", 1);
      break;
    case "2995807621"://Trench Run
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY:trait=Fighter");
      AddDecisionQueue("MZFILTER", $currentPlayer, "status=1", 1);
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to attack with", 1);
      AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "GETUNIQUEID", 1);
      AddDecisionQueue("ADDLIMITEDCURRENTEFFECT", $currentPlayer, "2995807621,PLAY", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "ATTACK", 1);
      break;
    case "5834478243"://You're All Clear Kid
      MZChooseAndDestroy($currentPlayer, "THEIRALLY:arena=Space;maxHealth=3", context:"Choose a space unit with 3 or less HP to defeat");
      AddDecisionQueue("SPECIFICCARD", $currentPlayer, "YOUREALLCLEARKID", 1);
      break;
    case "5667308555"://I Have You Now
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY:trait=Vehicle");
      AddDecisionQueue("MZFILTER", $currentPlayer, "status=1", 1);
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a vehicle to attack with", 1);
      AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "GETUNIQUEID", 1);
      AddDecisionQueue("ADDLIMITEDCURRENTEFFECT", $currentPlayer, "5667308555,PLAY", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "ATTACK", 1);
      break;
    case "8734471238"://Stay On Target
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY:trait=Vehicle");
      AddDecisionQueue("MZFILTER", $currentPlayer, "status=1", 1);
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a vehicle to attack with", 1);
      AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "GETUNIQUEID", 1);
      AddDecisionQueue("ADDLIMITEDCURRENTEFFECT", $currentPlayer, "8734471238,PLAY", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "ATTACK", 1);
      break;
    case "7461173274"://They Hate That Ship
      global $CS_AfterPlayedBy;
      for ($i = 0; $i < 2; $i++) {
        $tieFighterUniqueId = CreateTieFighter($otherPlayer);
        $tieFighterAlly = new Ally($tieFighterUniqueId, $otherPlayer);
        $tieFighterAlly->Ready();
      }

      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a vehicle unit to play (costs 3 less)");
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYHAND:definedType=Unit&trait=Vehicle");
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("ADDCURRENTEFFECT", $currentPlayer, $cardID, 1);
      AddDecisionQueue("MZOP", $currentPlayer, "PLAYCARD", 1);
      break;
    case "4942377291"://Face Off
      global $initiativeTaken;
      if (!$initiativeTaken) {
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "THEIRALLY");
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose an enemy unit to ready");
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "READY", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "GETARENA", 1);
        AddDecisionQueue("SETDQVAR", $currentPlayer, "0", 1);
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY:arena={0}", 1);
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a friendly unit in the same arena to ready", 1);
        AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "READY", 1);
      }
      break;
    case "2301911685"://Timely Reinforcements
      $numResources = floor(NumResources($otherPlayer) / 2);
      for ($i = 0; $i < $numResources; $i++) {
        $xwingUniqueId = CreateXWing($currentPlayer);
        AddCurrentTurnEffect($cardID, $currentPlayer, uniqueID: $xwingUniqueId);
      }
      break;
    case "8323555870"://Commence Patrol
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a discard pile to put a card on the bottom of its owner's deck");
      AddDecisionQueue("BUTTONINPUT", $currentPlayer, "Yours,Opoonentʼs"); // Some weird bug with the normal apostrophe (')
      AddDecisionQueue("SPECIFICCARD", $currentPlayer, "COMMENCEPATROL", 1);
      break;
    case "3858069945"://Power From Pain
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY&THEIRALLY");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to give +1/+0 for each damage on it");
      AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "GETUNIQUEID", 1);
      AddDecisionQueue("SETDQVAR", $currentPlayer, "0", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "GETDAMAGE", 1);
      AddDecisionQueue("SETDQVAR", $currentPlayer, "1", 1);
      AddDecisionQueue("PASSPARAMETER", $currentPlayer, "{0}", 1);
      AddDecisionQueue("ADDLIMITEDCURRENTEFFECT", $currentPlayer, "3858069945-{1}" , 1);
      break;
    case "0931441928"://Ma Klounkee
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY:trait=Underworld");
      AddDecisionQueue("MZFILTER", $currentPlayer, "leader=1");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a card to bounce");
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "BOUNCE", 1);
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY&THEIRALLY", 1);
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to deal 3 damage to", 1);
      AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "DEALDAMAGE,3,$currentPlayer", 1);
      break;
    case "6544277158"://Hotshot Maneuver
      if ($target != "-") {
        $ally = new Ally($target);
        $totalOnAttackAbilities = WhileAttackingAbilities($ally->UniqueID(), reportMode:true);
        $totalOnAttackAbilities += RestoreAmount($ally->CardID(), $ally->Controller(), $ally->Index()) > 0 ? 1 : 0;
        $totalOnAttackAbilities += HasSaboteur($ally->CardID(), $ally->Controller(), $ally->Index()) ? 1 : 0;

        AddDecisionQueue("PASSPARAMETER", $currentPlayer, "-");
        AddDecisionQueue("SETDQVAR", $currentPlayer, 0);
        for ($i = 0; $i < $totalOnAttackAbilities; $i++) {
          AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "THEIRALLY", 1);
          AddDecisionQueue("MZFILTER", $currentPlayer, "dqVar=0", 1);
          AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to deal 2 damage to", 1);
          AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
          AddDecisionQueue("SETDQVAR", $currentPlayer, "1", 1);
          AddDecisionQueue("MZOP", $currentPlayer, "GETUNIQUEID", 1);
          AddDecisionQueue("APPENDDQVAR", $currentPlayer, "0", 1);
          AddDecisionQueue("PASSPARAMETER", $currentPlayer, "{1}", 1);
          AddDecisionQueue("MZOP", $currentPlayer, DealDamageBuilder(2, $currentPlayer), 1);
        }

        if (!$ally->IsExhausted()) {
          AddDecisionQueue("PASSPARAMETER", $currentPlayer, $target);
          AddDecisionQueue("MZOP", $currentPlayer, "ATTACK");
        }
      }
      break;
    case "0302968596"://Calculated Lethality
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY:maxCost=3&THEIRALLY:maxCost=3");
      AddDecisionQueue("MZFILTER", $currentPlayer, "leader=1");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a card to defeat");
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("SPECIFICCARD", $currentPlayer, "CALCULATEDLETHALITY", 1);
      break;
    case "2503039837"://Moff Gideon Leader
      $abilityName = GetResolvedAbilityName($cardID, $from);
      if($abilityName == "Attack") {
        AddCurrentTurnEffect($cardID, $currentPlayer);
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY:maxCost=3");
        AddDecisionQueue("MZFILTER", $currentPlayer, "status=1", 1);
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to attack with");
        AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "ATTACK", 1);
      }
      break;
    case "9690731982"://Reckless Gunslinger
      if($from != "PLAY") {
        DealDamageAsync(1, 1, "DAMAGE", $cardID, sourcePlayer:$currentPlayer);
        DealDamageAsync(2, 1, "DAMAGE", $cardID, sourcePlayer:$currentPlayer);
      }
      break;
    case "8712779685"://Outland TIE Vanguard
      if($from != "PLAY") {
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY:maxCost=3&THEIRALLY:maxCost=3");
        AddDecisionQueue("MZFILTER", $currentPlayer, "index=MYALLY-" . $playAlly->Index());
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a card to give experience");
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "ADDEXPERIENCE", 1);
      }
      break;
    case "5874342508"://Hotshot DL-44 Blaster
      if($from == "RESOURCES") {
        $ally = new Ally($target);
        if(!$ally->IsExhausted() && $ally->PlayerID() == $currentPlayer) {
          AddDecisionQueue("PASSPARAMETER", $currentPlayer, $target);
          AddDecisionQueue("MZOP", $currentPlayer, "ATTACK", 1);
        }
      }
      break;
    case "6884078296"://Greef Karga
      if($from != "PLAY") {
        AddDecisionQueue("SEARCHDECKTOPX", $currentPlayer, "5;1;include-definedType-Upgrade");
        AddDecisionQueue("ADDHAND", $currentPlayer, "-", 1);
        AddDecisionQueue("REVEALCARDS", $currentPlayer, "DECK", 1);
      }
      break;
    case "1304452249"://Covetous Rivals
      if($from != "PLAY") CovetousRivalsSHD($currentPlayer);
      break;
    case "2526288781"://Bossk
      $abilityName = GetResolvedAbilityName($cardID, $from);
      if($abilityName == "Deal Damage/Buff") {
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY:hasBountyOnly=true&THEIRALLY:hasBountyOnly=true");
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit with bounty to deal 1 damage to");
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "DEALDAMAGE,1,$currentPlayer", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "GETUNIQUEID", 1);
        AddDecisionQueue("SETDQVAR", $currentPlayer, "0", 1);
        AddDecisionQueue("YESNO", $currentPlayer, "if you want to give the unit +1 power", 1);
        AddDecisionQueue("NOPASS", $currentPlayer, "-", 1);
        AddDecisionQueue("PASSPARAMETER", $currentPlayer, "{0}", 1);
        AddDecisionQueue("ADDLIMITEDCURRENTEFFECT", $currentPlayer, "2526288781", 1);
      }
      break;
    case "7424360283"://Bo-Katan Kryze
      global $CS_NumMandalorianAttacks;
      $abilityName = GetResolvedAbilityName($cardID, $from);
      if($abilityName == "Deal Damage" && GetClassState($currentPlayer, $CS_NumMandalorianAttacks)) {
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY&THEIRALLY");
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to deal 1 damage to");
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "DEALDAMAGE,1,$currentPlayer", 1);
      }
      break;
    case "0505904136"://Scanning Officer
      $resources = &GetResourceCards($otherPlayer);
      if(count($resources) == 0) break;
      $numDestroyed = 0;
      $cards = "";
      $indices = explode(",", GetIndices(count($resources), pieces:ResourcePieces()));
      $randomIndices = array_rand($indices, count($indices) >= 3 ? 3 : count($indices));
      rsort($randomIndices);
      foreach ($randomIndices as $randomIndex) {
        $index = $indices[$randomIndex];
        if ($cards != "") $cards .= ",";
        $cards .= $resources[$index];
        if (SmuggleCost($resources[$index], $otherPlayer, $index) >= 0) {
          AddGraveyard($resources[$index], $otherPlayer, 'ARS');
          for ($j = $index; $j < $index + ResourcePieces(); ++$j) unset($resources[$j]);
          $resources = array_values($resources);
          ++$numDestroyed;
        }
      }
      for($i=0; $i<$numDestroyed; ++$i) {
        AddTopDeckAsResource($otherPlayer);
      }
      RevealCards($cards);
      break;
    case "2560835268"://The Armorer
      if($from != "PLAY") {
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY:trait=Mandalorian");
        AddDecisionQueue("OP", $currentPlayer, "MZTONORMALINDICES");
        AddDecisionQueue("PREPENDLASTRESULT", $currentPlayer, "3-", 1);
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose up to 3 mandalorians to give a shield");
        AddDecisionQueue("MULTICHOOSEUNIT", $currentPlayer, "<-", 1);
        AddDecisionQueue("SPECIFICCARD", $currentPlayer, "MULTIGIVESHIELD", 1);
      }
      break;
    case "3622749641"://Krrsantan
      $numBounty = SearchCount(SearchAllies($otherPlayer, hasBountyOnly:true));
      if($numBounty > 0) {
        $playAlly->Ready();
      }
      break;
    case "9765804063"://Discerning Veteran
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "THEIRALLY:arena=Ground");
      AddDecisionQueue("MZFILTER", $currentPlayer, "leader=1");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to capture");
      AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "CAPTURE," . $playAlly->UniqueID(), 1);
      break;
    case "3765912000"://Take Captive
      $targetAlly = new Ally($target, $currentPlayer);
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "THEIRALLY:arena=" . CardArenas($targetAlly->CardID()));
      AddDecisionQueue("MZFILTER", $currentPlayer, "leader=1");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to capture");
      AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "CAPTURE," . $targetAlly->UniqueID(), 1);
      break;
    case "8877249477"://Legal Authority
      $targetAlly = new Ally($target, $currentPlayer);
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "THEIRALLY:maxAttack=" . ($targetAlly->CurrentPower()-1));
      AddDecisionQueue("MZFILTER", $currentPlayer, "leader=1");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to capture");
      AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "CAPTURE," . $targetAlly->UniqueID(), 1);
      break;
    case "5303936245"://Rival's Fall
      MZChooseAndDestroy($currentPlayer, "MYALLY&THEIRALLY");
      break;
    case "8818201543"://Midnight Repairs
      for($i=0; $i<8; ++$i) {
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY&THEIRALLY", $i == 0 ? 0 : 1);
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to restore 1 (Remaining: " . (8-$i) . ")", $i == 0 ? 0 : 1);
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "RESTORE,1", 1);
      }
      break;
    case "3012322434"://Give In To Your Hate
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "THEIRALLY");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to deal 1 damage to");
      AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "DEALDAMAGE,1,$currentPlayer", 1);
      AddDecisionQueue("WRITELOG", $currentPlayer, "This is a partially manual card. Make sure you attack a unit with this unit for your next action.", 1);
      break;
    case "2090698177"://Street Gang Recruiter
      MZMoveCard($currentPlayer, "MYDISCARD:trait=Underworld", "MYHAND", may:true, context:"Choose an underworld card to return with " . CardLink("2090698177", "2090698177"));
      break;
    case "7964782056"://Qi'Ra unit
      AddDecisionQueue("REVEALHANDCARDS", $otherPlayer, "-");
      AddDecisionQueue("LOOKHAND", $currentPlayer, "-");
      AddDecisionQueue("INPUTCARDNAME", $currentPlayer, "<-");
      AddDecisionQueue("SETDQVAR", $currentPlayer, "0", 1);
      AddDecisionQueue("PASSPARAMETER", $currentPlayer, $uniqueId, 1);
      AddDecisionQueue("ADDLIMITEDPERMANENTEFFECT", $otherPlayer, "7964782056_{0},HAND," . $otherPlayer, 1);
      break;
    case "8096748603"://Steela Gerrera
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Do you want to deal 2 damage to your base?");
      AddDecisionQueue("YESNO", $currentPlayer, "-");
      AddDecisionQueue("NOPASS", $currentPlayer, "-");
      AddDecisionQueue("PASSPARAMETER", $currentPlayer, "MYCHAR-0", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "DEALDAMAGE,2,$currentPlayer,1", 1);
      AddDecisionQueue("SEARCHDECKTOPX", $currentPlayer, "8;1;include-trait-Tactic", 1);
      AddDecisionQueue("ADDHAND", $currentPlayer, "-", 1);
      AddDecisionQueue("REVEALCARDS", $currentPlayer, "DECK", 1);
      break;
    case "5157630261"://Compassionate Senator
      $abilityName = GetResolvedAbilityName($cardID, $from);
      if($abilityName == "Heal") {
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY&THEIRALLY");
        AddDecisionQueue("PREPENDLASTRESULT", $currentPlayer, "MYCHAR-0,THEIRCHAR-0,");
        AddDecisionQueue("MZFILTER", $currentPlayer, "damaged=0");
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to heal", 1);
        AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "RESTORE,2", 1);
      }
      break;
    case "6570091935"://Tranquility
      MZMoveCard($currentPlayer, "MYDISCARD:trait=Republic;definedType=Unit", "MYHAND", may:true, context:"Choose a Republic unit to return to your hand");
      break;
    case "3388566378"://Ahsoka Tano JTL
      PummelHit($otherPlayer);
      AddDecisionQueue("SPECIFICCARD", $currentPlayer, "AHSOKATANOJTL", 1);
      break;
    case "5751831621"://Red Squadron X-Wing
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Do you want to deal 2 damage to Red Squadron X-Wing to draw a card?");
      AddDecisionQueue("YESNO", $currentPlayer, "-");
      AddDecisionQueue("NOPASS", $currentPlayer, "-");
      AddDecisionQueue("PASSPARAMETER", $currentPlayer, "MYALLY-" . $playAlly->Index(), 1);
      AddDecisionQueue("MZOP", $currentPlayer, DealDamageBuilder(2, $currentPlayer, isUnitEffect:1, unitCardID:$cardID), 1);
      AddDecisionQueue("DRAW", $currentPlayer, "-", 1);
      break;
    case "7157369742"://TIE Dagger Vanguard
      DQPingUnit($currentPlayer, 2, isUnitEffect:true, may:true, mzSearch:"MYALLY:damagedOnly=true&THEIRALLY:damagedOnly=true",unitCardID:$cardID);
      break;
    case "5830140660"://Bazine Netal
      AddDecisionQueue("REVEALHANDCARDS", $otherPlayer, "-");
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "THEIRHAND");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a card to discard");
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZDESTROY", $currentPlayer, "-", 1);
      AddDecisionQueue("DRAW", $otherPlayer, "-", 1);
      break;
    case "8645125292"://Covert Strength
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY&THEIRALLY");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to restore 2 and give a experience token to");
      AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "RESTORE,2", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "ADDEXPERIENCE", 1);
      break;
    case "4783554451"://First Light
      if($from == "RESOURCES") {
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY");
        AddDecisionQueue("MZFILTER", $currentPlayer, "index=MYALLY-" . $playAlly->Index());
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to deal 4 damage to");
        AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "DEALDAMAGE,4,$currentPlayer", 1);
      }
      break;
    case "5351496853"://Gideon's Light Cruiser
      if(ControlsNamedCard($currentPlayer, "Moff Gideon")) {
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYDISCARD:definedType=Unit;aspect=Villainy;maxCost=3&MYHAND:definedType=Unit;aspect=Villainy;maxCost=3");
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a card to play");
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("ADDCURRENTEFFECT", $currentPlayer, $cardID, 1);
        AddDecisionQueue("MZOP", $currentPlayer, "PLAYCARD", 1);
      }
      break;
    case "5440730550"://Lando Calrissian
      global $CS_AfterPlayedBy;
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYRESOURCES:keyword=Smuggle");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a card to play");
      AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("SETDQVAR", $currentPlayer, "0", 1);
      AddDecisionQueue("PASSPARAMETER", $currentPlayer, $cardID, 1);
      AddDecisionQueue("SETCLASSSTATE", $currentPlayer, $CS_AfterPlayedBy, 1);
      AddDecisionQueue("ADDCURRENTEFFECT", $currentPlayer, $cardID, 1);
      AddDecisionQueue("PASSPARAMETER", $currentPlayer, "{0}", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "PLAYCARD", 1);
      AddDecisionQueue("MZREMOVE", $currentPlayer, "-", 1);
      break;
    case "040a3e81f3"://Lando Calrissian Leader Unit
      $abilityName = GetResolvedAbilityName($cardID, $from);
      if($abilityName == "Smuggle") {
        $mzIndex = "MYALLY-" . GetAllyIndex($cardID, $currentPlayer);
        $ally = new Ally($mzIndex, $currentPlayer);
        if($ally->NumUses() <= 0) {
          WriteLog("Smuggle ability was already used this turn. Game state reverted");
          RevertGamestate();
        } else {
          global $CS_AfterPlayedBy;
          AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYRESOURCES:keyword=Smuggle");
          AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a card to play");
          AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
          AddDecisionQueue("SETDQVAR", $currentPlayer, "0", 1);
          AddDecisionQueue("PASSPARAMETER", $currentPlayer, $mzIndex, 1);
          AddDecisionQueue("ADDMZUSES", $currentPlayer, -1, 1);
          AddDecisionQueue("PASSPARAMETER", $currentPlayer, $cardID, 1);
          AddDecisionQueue("SETCLASSSTATE", $currentPlayer, $CS_AfterPlayedBy, 1);
          AddDecisionQueue("ADDCURRENTEFFECT", $currentPlayer, $cardID, 1);
          AddDecisionQueue("PASSPARAMETER", $currentPlayer, "{0}", 1);
          AddDecisionQueue("MZOP", $currentPlayer, "PLAYCARD", 1);
          AddDecisionQueue("MZREMOVE", $currentPlayer, "-", 1);
        }
      }
      break;
    case "0754286363"://The Mandalorian's Rifle
      $ally = new Ally($target, $currentPlayer);
      if(CardTitle($ally->CardID()) == "The Mandalorian") {
        AddLayer("TRIGGER", $currentPlayer, $cardID, uniqueID: $ally->UniqueID());
      }
      break;
    case "4643489029"://Palpatine's Return
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYDISCARD:definedType=Unit");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a card to play");
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("ADDCURRENTEFFECT", $currentPlayer, $cardID, 1);
      AddDecisionQueue("MZOP", $currentPlayer, "PLAYCARD", 1);
      break;
    case "4717189843"://A New Adventure
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY:maxCost=6&THEIRALLY:maxCost=6");
      AddDecisionQueue("MZFILTER", $currentPlayer, "leader=1");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to return to hand");
      AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "BOUNCE", 1);
      AddDecisionQueue("SPECIFICCARD", $currentPlayer, "ANEWADVENTURE", 1);
      break;
    case "9757839764"://Adelphi Patrol Wing
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY");
      AddDecisionQueue("MZFILTER", $currentPlayer, "status=1");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a card to attack and give +2");
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      if($initiativePlayer == $currentPlayer) {
        AddDecisionQueue("SETDQVAR", $currentPlayer, "0", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "GETUNIQUEID", 1);
        AddDecisionQueue("ADDLIMITEDCURRENTEFFECT", $currentPlayer, "9757839764,HAND", 1);
        AddDecisionQueue("PASSPARAMETER", $currentPlayer, "{0}", 1);
      }
      AddDecisionQueue("MZOP", $currentPlayer, "ATTACK", 1);
      break;
    case "7212445649"://Bravado
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY&THEIRALLY");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to ready");
      AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "READY", 1);
      break;
    case "2432897157"://Qi'Ra
      $abilityName = GetResolvedAbilityName($cardID, $from);
      if($abilityName == "Shield") {
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY");
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to deal 2 damage and give a shield");
        AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "DEALDAMAGE,2,$currentPlayer", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "ADDSHIELD", 1);
      }
      break;
    case "4352150438"://Rey
      $abilityName = GetResolvedAbilityName($cardID, $from);
      if($abilityName == "Experience") {
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY:maxAttack=2&THEIRALLY:maxAttack=2");
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to give an experience");
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "ADDEXPERIENCE", 1);
      }
      break;
    case "5778949819"://Relentless Pursuit
      $ally = new Ally($target, $currentPlayer);
      if(TraitContains($ally->CardID(), "Bounty Hunter", $currentPlayer)) $ally->Attach("8752877738");//Shield Token
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "THEIRALLY:maxCost=" . (CardCost($ally->CardID())));
      AddDecisionQueue("MZFILTER", $currentPlayer, "leader=1");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to capture");
      AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "CAPTURE," . $ally->UniqueID(), 1);
      break;
    case "6847268098"://Timely Intervention
      AddCurrentTurnEffect($cardID, $currentPlayer, "PLAY");
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYHAND:definedType=Unit");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a card to put into play");
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "PLAYCARD", 1);
      break;
    case "1973545191"://Unexpected Escape
      $owner = MZPlayerID($currentPlayer, $target);
      $ally = new Ally($target, $owner);
      $ally->Exhaust(enemyEffects:$currentPlayer != $ally->Controller());
      RescueUnit($currentPlayer, $target);
      break;
    case "9552605383"://L3-37
      $captors = SearchCaptors();
      if (SearchCount($captors) > 0) {
        AddDecisionQueue("PASSPARAMETER", $currentPlayer, $captors);
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "You may choose a unit to rescue from (or pass for shield)");
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      } else {
        AddDecisionQueue("PASSPARAMETER", $currentPlayer, "PASS");
      }
      AddDecisionQueue("SPECIFICCARD", $currentPlayer, "L337," . $uniqueId);
      break;
    case "5818136044"://Xanadu Blood
      if ($from != "PLAY") XanaduBloodSHD($currentPlayer, $playAlly->Index());
      break;
    case "1312599620"://Smuggler's Starfighter
      if(SearchCount(SearchAllies($currentPlayer, trait:"Underworld")) > 1) {
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "THEIRALLY");
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to give -3 power");
        AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "GETUNIQUEID", 1);
        AddDecisionQueue("ADDLIMITEDCURRENTEFFECT", $currentPlayer, "1312599620,PLAY", 1);
      }
      break;
    case "6853970496"://Slaver's Freighter
      $theirAllies = &GetAllies($otherPlayer);
      $numUpgrades = 0;
      for($i=0; $i<count($theirAllies); $i+=AllyPieces()) {
        $ally = new Ally("MYALLY-" . $i, $otherPlayer);
        $numUpgrades += $ally->NumUpgrades();
      }
      if($numUpgrades > 0) {
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY:maxAttack=" . $numUpgrades . "&THEIRALLY:maxAttack=" . $numUpgrades);
        if($index > -1) AddDecisionQueue("MZFILTER", $currentPlayer, "index=MYALLY-" . $playAlly->Index());
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to ready");
        AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "READY", 1);
      }
      break;
    case "2143627819"://The Marauder
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYDISCARD");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a card in your discard to resource");
      AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("SPECIFICCARD", $currentPlayer, "THEMARAUDER", 1);
      break;
    case "7642980906"://Stolen Landspeeder
      if ($from == "HAND" && $playAlly->Exists()) {
        AddDecisionQueue("PASSPARAMETER", $otherPlayer, $playAlly->UniqueID());
        AddDecisionQueue("MZOP", $otherPlayer, "TAKECONTROL");
      }
      break;
    case "2346145249"://Choose Sides
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY");
      AddDecisionQueue("MZFILTER", $currentPlayer, "leader=1");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a friendly unit to swap");
      AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "GETUNIQUEID", 1);
      AddDecisionQueue("MZOP", $otherPlayer, "TAKECONTROL", 1);
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "THEIRALLY", 1);
      AddDecisionQueue("MZFILTER", $currentPlayer, "leader=1", 1);
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose an enemy unit to swap", 1);
      AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "GETUNIQUEID", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "TAKECONTROL", 1);
      break;
    case "0598830553"://Dryden Vos
      PlayCaptive($currentPlayer, $target);
      break;
    case "1477806735"://Wookiee Warrior
      if(SearchCount(SearchAllies($currentPlayer, trait:"Wookiee")) > 1) {
        Draw($currentPlayer);
      }
      break;
    case "5696041568"://Triple Dark Raid
      global $CS_AfterPlayedBy;
      AddDecisionQueue("SEARCHDECKTOPX", $currentPlayer, "7;1;include-trait-Vehicle");
      AddDecisionQueue("SETDQVAR", $currentPlayer, "0", 1);
      AddDecisionQueue("ADDCURRENTEFFECT", $currentPlayer, $cardID, 1);
      AddDecisionQueue("PASSPARAMETER", $currentPlayer, $cardID, 1);
      AddDecisionQueue("SETCLASSSTATE", $currentPlayer, $CS_AfterPlayedBy, 1);
      AddDecisionQueue("PASSPARAMETER", $currentPlayer, "{0}", 1);
      AddDecisionQueue("OP", $currentPlayer, "PLAYCARD,DECK", 1);
      break;
    case "0911874487"://Fennec Shand
      $abilityName = GetResolvedAbilityName($cardID, $from);
      if($abilityName == "Ambush") {
        AddCurrentTurnEffect($cardID, $currentPlayer, "PLAY");
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYHAND:definedType=Unit;maxCost=4");
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a card to put into play");
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "PLAYCARD", 1);
      }
      break;
    case "2b13cefced"://Fennec Shand Leader Unit
      $abilityName = GetResolvedAbilityName($cardID, $from);
      if($abilityName == "Ambush") {
        AddCurrentTurnEffect($cardID, $currentPlayer, "PLAY");
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYHAND:definedType=Unit;maxCost=4");
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a card to put into play");
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "PLAYCARD", 1);
      }
      break;
    case "9828896088"://Spark of Hope
      MZMoveCard($currentPlayer, "MYDISCARD:definedType=Unit;defeatedThisPhase=true", "MYRESOURCES", may:true);
      AddDecisionQueue("PAYRESOURCES", $currentPlayer, "1,1", 1);
      break;
    case "9845101935"://This is the Way
      AddDecisionQueue("SEARCHDECKTOPX", $currentPlayer, "8;2;include-trait-Mandalorian|include-definedType-Upgrade");
      AddDecisionQueue("MULTIADDHAND", $currentPlayer, "-", 1);
      AddDecisionQueue("REVEALCARDS", $currentPlayer, "DECK", 1);
      break;
    case "8261033110"://Evacuate
      $p1Allies = &GetAllies(1);
      $p1Captives = [];
      for($i=count($p1Allies)-AllyPieces(); $i>=0; $i-=AllyPieces()) {
        $ally = new Ally("MYALLY-" . $i, 1);
        if(!$ally->IsLeader()) {
          $p1Captives = array_merge($p1Captives, $ally->GetCaptives());
          MZBounce(1, "MYALLY-" . $i);
        }
      }
      $p2Allies = &GetAllies(2);
      for($i=count($p2Allies)-AllyPieces(); $i>=0; $i-=AllyPieces()) {
        $ally = new Ally("MYALLY-" . $i, 2);
        if(in_array($ally->CardID(), $p1Captives)) {
          $index = array_search($ally->CardID(),$p1Captives);
          unset($p1Captives[$index]);
        } else if (!$ally->IsLeader()) {
          MZBounce(2, "MYALLY-" . $i);
        }
      }
      break;
    case "1910812527"://Final Showdown
      AddRoundEffect("1910812527", $currentPlayer);
      $myAllies = &GetAllies($currentPlayer);
      for($i=0; $i<count($myAllies); $i+=AllyPieces())
      {
        $ally = new Ally("MYALLY-" . $i, $currentPlayer);
        $ally->Ready();
      }
      break;
    case "a742dea1f1"://Han Solo Red Unit
    case "9226435975"://Han Solo Red
      $abilityName = GetResolvedAbilityName($cardID, $from);
      $choosePhase = $cardID == "9226435975" ? "MAYCHOOSEMULTIZONE" : "CHOOSEMULTIZONE";
      if($abilityName == "Play") {
        global $CS_AfterPlayedBy;
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYHAND:definedType=Unit");
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to play");
        AddDecisionQueue($choosePhase, $currentPlayer, "<-", 1);
        AddDecisionQueue("SETDQVAR", $currentPlayer, "0", 1);
        AddDecisionQueue("ADDCURRENTEFFECT", $currentPlayer, "9226435975", 1);
        AddDecisionQueue("PASSPARAMETER", $currentPlayer, $cardID, 1);
        AddDecisionQueue("SETCLASSSTATE", $currentPlayer, $CS_AfterPlayedBy, 1);
        AddDecisionQueue("PASSPARAMETER", $currentPlayer, "{0}", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "PLAYCARD", 1);
      }
      break;
    case "7354795397"://No Bargain
      PummelHit($otherPlayer);
      Draw($currentPlayer);
      break;
    case "9270539174"://Wild Rancor
      DamageAllAllies(2, "9270539174", arena: "Ground", except: "MYALLY-" . $playAlly->Index());
      break;
    case "2744523125"://Salacious Crumb
      $abilityName = GetResolvedAbilityName($cardID, $from);
      if($abilityName == "Bounce") {
        $salaciousCrumbIndex = SearchAlliesForCard($currentPlayer, $cardID);
        MZBounce($currentPlayer, "MYALLY-" . $salaciousCrumbIndex);
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY:arena=Ground&THEIRALLY:arena=Ground");
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to deal 1 damage to");
        AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $currentPlayer, DealDamageBuilder(1, $currentPlayer, isUnitEffect:true, unitCardID:$cardID), 1);
      } else if($from != "PLAY") {
        Restore(1, $currentPlayer);
      }
      break;
    case "0622803599"://Jabba the Hutt Leader
      $abilityName = GetResolvedAbilityName($cardID, $from);
      if($abilityName == "Bounty") {
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "THEIRALLY");
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to give bounty");
        AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "GETUNIQUEID", 1);
        AddDecisionQueue("ADDLIMITEDCURRENTEFFECT", $currentPlayer, "0622803599-2,PLAY", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "WRITECHOICEFROMUNIQUE", 1);
      }
      break;
    case "f928681d36"://Jabba the Hutt Leader Unit
      $abilityName = GetResolvedAbilityName($cardID, $from);
      if($abilityName == "Bounty") {
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "THEIRALLY");
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to give bounty");
        AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "GETUNIQUEID", 1);
        AddDecisionQueue("ADDLIMITEDCURRENTEFFECT", $currentPlayer, "f928681d36-2,PLAY", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "WRITECHOICEFROMUNIQUE", 1);
      }
      break;
    case "8090818642"://The Chaos of War
      $p1Hand = &GetHand(1);
      DamageTrigger(1, count($p1Hand)/HandPieces(), "DAMAGE", "8090818642");
      $p2Hand = &GetHand(2);
      DamageTrigger(2, count($p2Hand)/HandPieces(), "DAMAGE", "8090818642");
      break;
    case "7826408293"://Daring Raid
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY&THEIRALLY");
      AddDecisionQueue("PREPENDLASTRESULT", $currentPlayer, "MYCHAR-0,THEIRCHAR-0,");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose something to deal 2 damage to");
      AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "DEALDAMAGE,2,$currentPlayer", 1);
      break;
    case "0753707056"://Unity of Purpose
      $allies = &GetAllies($currentPlayer);
      $uniqueCards = [];
      for($i=0; $i<count($allies); $i+=AllyPieces()) {
        $cardID = CardTitle($allies[$i]);
        if (!in_array($cardID, $uniqueCards)){
          array_push($uniqueCards, $cardID);
        }
      }
      $buffAmount = count($uniqueCards);
      for($i=0; $i<count($allies); $i+=AllyPieces()) {
        $ally = new Ally("MYALLY-" . $i, $currentPlayer);
        $ally->AddRoundHealthModifier($buffAmount);
        AddCurrentTurnEffect("0753707056-" . $buffAmount, $currentPlayer, uniqueID:$allies[$i+5]);
      }
      break;
    case "4772866341"://Pillage
      $player = $additionalCosts == "Yourself" ? $currentPlayer : $otherPlayer;
      PummelHit($player);
      PummelHit($player);
      break;
    case "5984647454"://Enforced Loyalty
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose something to sacrifice");
      AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "DESTROY,$currentPlayer", 1);
      AddDecisionQueue("DRAW", $currentPlayer, "-", 1);
      AddDecisionQueue("DRAW", $currentPlayer, "-", 1);
      break;
    case "6234506067"://Cassian Andor
      if($from == "RESOURCES") $playAlly->Ready();
      break;
    case "5169472456"://Chewbacca Pykesbane
      if($from != "PLAY") {
        MZChooseAndDestroy($currentPlayer, "MYALLY:maxHealth=5&THEIRALLY:maxHealth=5", may:true, filter:"index=MYALLY-" . $playAlly->Index());
      }
      break;
    case "6962053552"://Desperate Attack
      if($from != "PLAY") {
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY");
        AddDecisionQueue("MZFILTER", $currentPlayer, "status=1");
        AddDecisionQueue("MZFILTER", $currentPlayer, "damaged=0");
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a card to attack and give +2");
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("SETDQVAR", $currentPlayer, "0", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "GETUNIQUEID", 1);
        AddDecisionQueue("ADDLIMITEDCURRENTEFFECT", $currentPlayer, "6962053552,HAND", 1);
        AddDecisionQueue("PASSPARAMETER", $currentPlayer, "{0}");
        AddDecisionQueue("MZOP", $currentPlayer, "ATTACK", 1);
      }
      break;
    case "3803148745"://Ruthless Assassin
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to deal 2 damage to");
      AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "DEALDAMAGE,2,$currentPlayer,1", 1);
      break;
    case "4057912610"://Bounty Guild Initiate
      if($from != "PLAY" && SearchCount(SearchAllies($currentPlayer, trait:"Bounty Hunter")) > 1) {
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "THEIRALLY:arena=Ground");
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to deal 2 damage to");
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "DEALDAMAGE,2,$currentPlayer,1", 1);
      }
      break;
    case "6475868209"://Criminal Muscle
      if($from != "PLAY") {
        DefeatUpgrade($currentPlayer, may:true, upgradeFilter: "unique=1", to:"HAND");
      }
      break;
    case "1743599390"://Trandoshan Hunters
      if(SearchCount(SearchAllies($otherPlayer, hasBountyOnly:true)) > 0) $playAlly->Attach("2007868442");//Experience token
      break;
    case "1141018768"://Commission
      AddDecisionQueue("SEARCHDECKTOPX", $currentPlayer, "10;1;include-trait-Bounty Hunter|include-trait-Item|include-trait-Transport");
      AddDecisionQueue("ADDHAND", $currentPlayer, "-", 1);
      AddDecisionQueue("REVEALCARDS", $currentPlayer, "DECK", 1);
      break;
    case "9596662994"://Finn
      $abilityName = GetResolvedAbilityName($cardID, $from);
      if($abilityName == "Shield") {
        DefeatUpgrade($currentPlayer, search:"MYALLY");
        AddDecisionQueue("PASSPARAMETER", $currentPlayer, "{0}", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "ADDSHIELD", 1);
      }
      break;
    case "7578472075"://Let the Wookiee Win
      $options = "They ready up to 6 resources;They ready a friendly unit. If it's a Wookiee unit, attack with it. It gets +2/+0 for this attack";
      AddDecisionQueue("SETDQCONTEXT", $otherPlayer, "Choose one for your opponent");
      AddDecisionQueue("CHOOSEOPTION", $otherPlayer, "$cardID&$options");
      AddDecisionQueue("SHOWOPTIONS", $otherPlayer, "$cardID&$options");
      AddDecisionQueue("MODAL", $currentPlayer, "LETTHEWOOKIEEWIN");
      break;
    case "8380936981"://Jabba's Rancor
      if($from != "PLAY") JabbasRancorSHD($currentPlayer, $playAlly->Index());
      break;
    case "2750823386"://Look the Other Way
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "THEIRALLY");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to exhaust");
      AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("SETDQVAR", $currentPlayer, "0", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "GETCARDID", 1);
      AddDecisionQueue("SETDQVAR", $currentPlayer, "1", 1);
      AddDecisionQueue("YESNO", $otherPlayer, "if you want to pay 2 to prevent <1> from being exhausted", 1);//Should have a CardLink, but doing SETDQVAR and adding <1> to the string for YESNO breaks the UI. Something to do with YESNO being processed outside normal DecisionQueue stuff I suspect.
      AddDecisionQueue("NOPASS", $otherPlayer, "-", 1);
      AddDecisionQueue("PAYRESOURCES", $otherPlayer, "2", 1);
      AddDecisionQueue("ELSE", $currentPlayer, "-");
      AddDecisionQueue("PASSPARAMETER", $currentPlayer, "{0}", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "REST", 1);
      break;
    case "4002861992"://DJ (Blatant Thief)
      if($from == "RESOURCES") {
        $djAlly = new Ally("MYALLY-" . LastAllyIndex($currentPlayer), $currentPlayer);
        // Try to get ready resources first
        $theirResourceIndices = GetArsenalFaceDownIndices($otherPlayer, 0);
        if ($theirResourceIndices == "") {
          // If no ready resources, get all resources
          $theirResourceIndices = GetArsenalFaceDownIndices($otherPlayer);
        }
        $theirResourceIndicesArr = explode(",", $theirResourceIndices);
        $theirResourceIndex = $theirResourceIndicesArr[GetRandom(0, count($theirResourceIndicesArr) - 1)]; // Pick a random resource. Important: remove this randomization if it breaks the game.
        $theirResources = &GetArsenal($otherPlayer);
        $isExhausted = $theirResources[$theirResourceIndex + 4];

        // Steal the resource
        $resourceCard = RemoveResource($otherPlayer, $theirResourceIndex);
        AddResources($resourceCard, $currentPlayer, "PLAY", "DOWN", isExhausted:$isExhausted, stealSource:$djAlly->UniqueID());

        // The new rules (v3) allow you to change the state of your resources immediately after smuggling the DJ, provided the total number of "ready" and "exhausted" resources remains the same.
        // So, we will exhaust the stolen resource and ready another.
        if (!$isExhausted) {
          $myResourceIndices = GetArsenalFaceDownIndices($currentPlayer, 1);
          if ($myResourceIndices != "") {
            $myResourceIndicesArr = explode(",", $myResourceIndices);
            $myResourceIndex = $myResourceIndicesArr[GetRandom(0, count($myResourceIndicesArr) - 1)]; // Pick a random resource. Important: remove this randomization if it breaks the game.
            $myResources = &GetArsenal($currentPlayer);
            $myResources[$myResourceIndex + 4] = "0"; // Ready a random resource
            $myResources[count($myResources) - ArsenalPieces() + 4] = "1"; // Exhaust the stolen resource
          }
        }
      }
      break;
    case "7718080954"://Frozen in Carbonite
      AddDecisionQueue("PASSPARAMETER", $currentPlayer, $target);
      AddDecisionQueue("MZOP", $currentPlayer, "REST", 1);
      break;
    case "6117103324"://Jetpack
      $ally = new Ally($target, $currentPlayer);
      if ($ally->Exists()) {
        $upgradeUniqueID = $ally->Attach("8752877738");//Shield Token
        AddRoundEffect("6117103324", $currentPlayer, uniqueID:$upgradeUniqueID);
      }
      break;
    case "1386874723"://Omega (Part of the Squad)
      if($from != "PLAY") {
        AddDecisionQueue("SEARCHDECKTOPX", $currentPlayer, "5;1;include-trait-Clone");
        AddDecisionQueue("ADDHAND", $currentPlayer, "-", 1);
        AddDecisionQueue("REVEALCARDS", $currentPlayer, "DECK", 1);
      }
      break;
    case "6151970296"://Bounty Posting
      MZMoveCard($currentPlayer, "MYDECK:trait=Bounty", "MYHAND", isReveal:true, may:true, context:"Choose a bounty to add to your hand");
      AddDecisionQueue("SHUFFLEDECK", $currentPlayer, "-");
      AddDecisionQueue("YESNO", $currentPlayer, "if you want to play the upgrade", 1);
      AddDecisionQueue("NOPASS", $currentPlayer, "-", 1);
      AddDecisionQueue("FINDINDICES", $currentPlayer, "MZLASTHAND", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "PLAYCARD", 1);
      break;
    case "8576088385"://Detention Block Rescue
      $owner = MZPlayerID($currentPlayer, $target);
      $ally = new Ally($target, $owner);
      $damage = count($ally->GetCaptives()) > 0 ? 6 : 3;
      $ally->DealDamage($damage);
      break;
    case "9999079491"://Mystic Reflection
      $healthDebuffAmount = HasUnitWithTraitInPlay($currentPlayer, "Force") ? 2 : 0;
      DQDebuffUnit($currentPlayer, $otherPlayer, $cardID, 2, $healthDebuffAmount, false, "THEIRALLY");
      break;
    case "5576996578"://Endless Legions
      AddDecisionQueue("SPECIFICCARD", $currentPlayer, "ENDLESSLEGIONS");
      break;
    case "8095362491"://Frontier Trader
      if($from != "PLAY") {
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYRESOURCES");
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a resource to return to hand");
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "BOUNCE", 1);
        AddDecisionQueue("YESNO", $currentPlayer, "if you want to add a resource from the top of your deck", 1);
        AddDecisionQueue("NOPASS", $currentPlayer, "-", 1);
        AddDecisionQueue("OP", $currentPlayer, "ADDTOPDECKASRESOURCE", 1);
      }
      break;
    case "8709191884"://Hunter (Outcast Sergeant)
      $abilityName = GetResolvedAbilityName($cardID, $from);
      if($abilityName == "Replace Resource") {
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYRESOURCES");
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a resource to reveal", 1);
        AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("SPECIFICCARD", $currentPlayer, "HUNTEROUTCASTSERGEANT", 1);
      }
      break;
    case "4663781580"://Swoop Down
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY:arena=Space");
      AddDecisionQueue("MZFILTER", $currentPlayer, "status=1", 1);
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to attack with", 1);
      AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("SETDQVAR", $currentPlayer, "0", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "GETUNIQUEID", 1);
      AddDecisionQueue("ADDLIMITEDCURRENTEFFECT", $currentPlayer, "4663781580,HAND", 1);
      AddDecisionQueue("ADDCURRENTEFFECT", $otherPlayer, "4663781580", 1);
      AddDecisionQueue("PASSPARAMETER", $currentPlayer, "{0}", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "ATTACK", 1);
      break;
    case "4895747419"://Consolidation Of Power
      $allies = &GetAllies($currentPlayer);
      $totalAllies = count($allies) / AllyPieces();
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY");
      AddDecisionQueue("OP", $currentPlayer, "MZTONORMALINDICES");
      AddDecisionQueue("PREPENDLASTRESULT", $currentPlayer, "$totalAllies-", 1);
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose any number of friendly units", 1);
      AddDecisionQueue("MULTICHOOSEUNIT", $currentPlayer, "<-", 1 );
      AddDecisionQueue("SPECIFICCARD", $currentPlayer, "CONSOLIDATIONOFPOWER", 1);
      break;
    case "9752523457"://Finalizer
      $allies = &GetAllies($currentPlayer);
      for($i=0; $i<count($allies); $i+=AllyPieces()) {
        $ally = new Ally("MYALLY-" . $i, $currentPlayer);
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "THEIRALLY:arena=".$ally->CurrentArena());
        AddDecisionQueue("MZFILTER", $currentPlayer, "leader=1", 1);
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a ". $ally->CurrentArena() . " unit for  " . CardLink($ally->CardID(), $ally->CardID()) . " to capture (must be in same arena)", 1);
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "CAPTURE," . $ally->UniqueID(), 1);
      }
      break;
    case "6425029011"://Altering the Deal
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY");
      AddDecisionQueue("MZFILTER", $currentPlayer, "hasCaptives=0", 1);
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a friendly unit to discard a captive from.", 1);
      AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("SETDQVAR", $currentPlayer, "0", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "GETCAPTIVES", 1);
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a captive to discard", 1);
      AddDecisionQueue("CHOOSECARD", $currentPlayer, "<-", 1);
      AddDecisionQueue("OP", $currentPlayer, "DISCARDCAPTIVE", 1);
      break;
    case "6452159858"://Evidence of the Crime
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY:hasUpgradeOnly=true&THEIRALLY:hasUpgradeOnly=true");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to take a 3-cost or less upgrade from.", 1);
      AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("SETDQVAR", $currentPlayer, "0", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "GETUPGRADES", 1);
      AddDecisionQueue("FILTER", $currentPlayer, "LastResult-include-maxCost-3", 1);
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose an upgrade to take.", 1);
      AddDecisionQueue("CHOOSECARD", $currentPlayer, "<-", 1);
      AddDecisionQueue("SETDQVAR", $currentPlayer, "1", 1);
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY&THEIRALLY");
      AddDecisionQueue("MZFILTER", $currentPlayer, "filterUpgradeEligible={1}", 1);
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to move <1> to.", 1);
      AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "MOVEUPGRADE,$cardID", 1);
      break;
    case "3399023235"://Fenn Rau
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYHAND:definedType=Upgrade");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose an upgrade to play");
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("ADDCURRENTEFFECT", $currentPlayer, $cardID, 1);
      AddDecisionQueue("MZOP", $currentPlayer, "PLAYCARD", 1);

      break;
    case "1503633301"://Survivors' Gauntlet
      if ($from != "PLAY") SurvivorsGauntletSHD($currentPlayer);
      break;
    case "3086868510"://Pre Vizsla
      if($from != "PLAY") PreVizslaSHD($currentPlayer);
      break;
    case "3671559022"://Echo
      if($from != "PLAY") {
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYHAND");
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "You may discard a card to Echo's ability", 1);
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZDISCARD", $currentPlayer, "HAND," . $currentPlayer, 1);
        AddDecisionQueue("MZREMOVE", $currentPlayer, "-", 1);
        AddDecisionQueue("SETDQVAR", $currentPlayer, "0", 1);
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY:sameTitle={0}&THEIRALLY:sameTitle={0}", 1);
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to give 2 experience tokens to.", 1);
        AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "ADDEXPERIENCE", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "ADDEXPERIENCE", 1);
      }
      break;
    case "8080818347"://Rule with Respect
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a friendly unit to capture all enemy units that attacked your base this phase", 1);
      AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "GETUNIQUEID", 1);
      AddDecisionQueue("SPECIFICCARD", $currentPlayer, "RULEWITHRESPECT", 1);
    break;
    case "3468546373"://General Rieekan
      if($from != "PLAY") GeneralRieekanSHD($currentPlayer);
      break;
    case "3577961001"://Mercenary Gunship
      $abilityName = GetResolvedAbilityName($cardID, $from);
      if($abilityName == "Take Control") {
        global $CS_OppCardActive;
        $oppIndex = GetClassState($currentPlayer, $CS_OppIndex);
        $ally = new Ally("THEIRALLY-" . $oppIndex, $otherPlayer);
        AddDecisionQueue("PASSPARAMETER", $currentPlayer, $ally->UniqueID(), 1);
        AddDecisionQueue("MZOP", $currentPlayer, "TAKECONTROL", 1);
        AddDecisionQueue("PASSPARAMETER", $currentPlayer, -1, 1);
        AddDecisionQueue("SETCLASSSTATE", $currentPlayer, $CS_OppCardActive, 1);
      }
      break;
    case "8552292852"://Kashyyyk Defender
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY&THEIRALLY");
      AddDecisionQueue("MZFILTER", $currentPlayer, "index=MYALLY-" . $playAlly->Index(), 1);
      AddDecisionQueue("MZFILTER", $currentPlayer, "damaged=0", 1);
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to heal up to 2 damage", 1);
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("PREPENDLASTRESULT", $currentPlayer, "2-", 1);
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Heal up to 2 damage", 1);
      AddDecisionQueue("PARTIALMULTIHEALMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "MULTIHEAL", 1);
      AddDecisionQueue("PREPENDLASTRESULT", $currentPlayer, $uniqueId . "-", 1);
      AddDecisionQueue("SPECIFICCARD", $currentPlayer, "KASHYYYKDEFENDER", 1);
      break;
    case "7439418148"://Twice the Pride
      $ally = new Ally($target);
      $ally->DealDamage(2);
      break;
    case "7252148824"://501st Liberator
      if (SearchCount(SearchAllies($currentPlayer, trait:"Republic")) > 1) {
        Restore(3, $currentPlayer);
      }
      break;
    case "7280804443"://Hold-Out Blaster
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY:arena=Ground&THEIRALLY:arena=Ground");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a card to deal 1 damage");
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "DEALDAMAGE,1,$currentPlayer,1", 1);
      break;
    case "6969421569"://Batch Brothers
      CreateCloneTrooper($currentPlayer);
      break;
    case "6826668370"://Droid Deployment
      CreateBattleDroid($currentPlayer);
      CreateBattleDroid($currentPlayer);
      break;
    case "6401761275"://In Pursuit
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY");
      AddDecisionQueue("MZFILTER", $currentPlayer, "status=1");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to exhaust");
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "REST", 1);
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "THEIRALLY", 1);
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to exhaust", 1);
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "REST", 1);
      break;
    case "5936350569"://Jesse
      CreateBattleDroid($otherPlayer);
      CreateBattleDroid($otherPlayer);
      break;
    case "5584601885"://Battle Droid Escort
      CreateBattleDroid($currentPlayer);
      break;
    case "5074877594"://Drop In
      CreateCloneTrooper($currentPlayer);
      CreateCloneTrooper($currentPlayer);
      break;
    case "4412828936"://Merciless Contest
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY");
      AddDecisionQueue("MZFILTER", $currentPlayer, "leader=1");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to destroy");
      AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "DESTROY,$currentPlayer", 1);
      AddDecisionQueue("MULTIZONEINDICES", $otherPlayer, "MYALLY");
      AddDecisionQueue("MZFILTER", $otherPlayer, "leader=1");
      AddDecisionQueue("SETDQCONTEXT", $otherPlayer, "Choose a unit to destroy");
      AddDecisionQueue("CHOOSEMULTIZONE", $otherPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $otherPlayer, "DESTROY,$currentPlayer", 1);
      break;
    case "3840495762"://Old Access Codes
      if(TheyControlMoreUnits($currentPlayer)) {
        Draw($currentPlayer);
      }
      break;
    case "3357486161"://Political Pressure
      $options = "Discard a random card from your hand;Opponent creates 2 Battle Droid tokens";
      if (CountHand($otherPlayer) > 0) {
        AddDecisionQueue("SETDQCONTEXT", $otherPlayer, "Choose one");
        AddDecisionQueue("CHOOSEOPTION", $otherPlayer, "$cardID&$options");
        AddDecisionQueue("SHOWOPTIONS", $otherPlayer, "$cardID&$options");
      } else {
        AddDecisionQueue("PASSPARAMETER", $otherPlayer, 1); // Create 2 Battle Droid tokens
      }
      AddDecisionQueue("MODAL", $otherPlayer, "POLITICALPRESSURE");
      break;
    case "0511508627"://Captain Rex
      CreateCloneTrooper($currentPlayer);
      CreateCloneTrooper($currentPlayer);
      break;
    case "0598115741"://Royal Guard Attache
      $playAlly->DealDamage(2);
      break;
    case "0968965258"://Death By Droids
      MZChooseAndDestroy($currentPlayer, "MYALLY:maxCost=3&THEIRALLY:maxCost=3");
      CreateBattleDroid($currentPlayer);
      CreateBattleDroid($currentPlayer);
      break;
    case "0036920495"://Elite P-38 Starfighter
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY&THEIRALLY");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to deal 1 damage to");
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "DEALDAMAGE,1,$currentPlayer,1", 1);
      break;
    case "2585318816"://Resolute
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "THEIRALLY");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to deal 2 damage to");
      AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("SPECIFICCARD", $currentPlayer, "RESOLUTE", 1);
      break;
    case "0328412140"://Creative Thinking
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY&THEIRALLY");
      AddDecisionQueue("MZFILTER", $currentPlayer, "unique=1");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a card to exhaust");
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "REST", 1);
      CreateCloneTrooper($currentPlayer);
      break;
    case "0959549331"://Unmasking the Conspiracy
      $hand = &GetHand($currentPlayer);
      if(count($hand) > 0) {
        PummelHit($currentPlayer);
        AddDecisionQueue("REVEALHANDCARDS", $otherPlayer, "-");
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "THEIRHAND");
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose which card you want your opponent to discard", 1);
        AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZDISCARD", $currentPlayer, "HAND," . $currentPlayer, 1);
        AddDecisionQueue("MZREMOVE", $currentPlayer, "-", 1);
      }
      break;
    case "1192349217"://Manufactured Soldiers
      $options = "Create 2 Clone Trooper tokens;Create 3 Battle Droid tokens";
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose one");
      AddDecisionQueue("CHOOSEOPTION", $currentPlayer, "$cardID&$options");
      AddDecisionQueue("SHOWOPTIONS", $currentPlayer, "$cardID&$options");
      AddDecisionQueue("MODAL", $currentPlayer, "MANUFACTUREDSOLDIERS");
      break;
    case "1417180295"://Strategic Analysis
      Draw($currentPlayer);
      Draw($currentPlayer);
      Draw($currentPlayer);
      break;
    case "2103133661"://Blood Sport
      DamageAllAllies(2, "2103133661", arena: "Ground");
      break;
    case "2483302291"://On the Doorstep
      CreateBattleDroid($currentPlayer);
      CreateBattleDroid($currentPlayer);
      CreateBattleDroid($currentPlayer);
      $allies = &GetAllies($currentPlayer);
      for($i=0; $i<3; ++$i) {
        $ally = new Ally("MYALLY-" . (count($allies) - ($i+1)*AllyPieces()), $currentPlayer);
        $ally->Ready();
      }
      break;
    case "2761325938"://Devastating Gunship
      MZChooseAndDestroy($currentPlayer, "THEIRALLY:maxHealth=2");
      break;
    case "4824842849"://Subjugating Starfighter
      if($initiativePlayer == $currentPlayer) {
        CreateBattleDroid($currentPlayer);
      }
      break;
    case "6732988831"://Grievous Reassembly
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY&THEIRALLY");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to restore 3");
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "RESTORE,3", 1);
      CreateBattleDroid($currentPlayer);
      break;
    case "6700679522"://Tri-Droid Suppressor
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "THEIRALLY");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to exhaust");
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "REST", 1);
      break;
    case "9479767991"://Favorable Deligate
      Draw($currentPlayer);
      break;
    case "3348783048"://Geonosis Patrol Fighter
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY:maxCost=3&THEIRALLY:maxCost=3");
      AddDecisionQueue("MZFILTER", $currentPlayer, "leader=1");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a card to bounce");
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "BOUNCE", 1);
      break;
    case "8777351722"://Anakin Skywalker
      DealDamageAsync($currentPlayer, 2, "DAMAGE", "8777351722", sourcePlayer:$currentPlayer);
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY");
      AddDecisionQueue("MZFILTER", $currentPlayer, "status=1", 1);
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to attack with", 1);
      AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("ADDCURRENTEFFECT", $currentPlayer, $cardID, 1);
      AddDecisionQueue("MZOP", $currentPlayer, "ATTACK", 1);
      break;
    case "6410481716"://Mace Windu's Lightsaber
      if(CardTitle(GetMZCard($currentPlayer, $target)) == "Mace Windu") {
        Draw($currentPlayer);
        Draw($currentPlayer);
      }
      break;
    case "5616678900"://R2-D2
      PummelHit($currentPlayer, may:true);
      AddDecisionQueue("SEARCHDECKTOPX", $currentPlayer, "3;1;", 1);
      AddDecisionQueue("ADDHAND", $currentPlayer, "-", 1);
      break;
    case "4910017138"://Breaking In
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY");
      AddDecisionQueue("MZFILTER", $currentPlayer, "status=1");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a card to attack and give +2");
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("SETDQVAR", $currentPlayer, "0", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "GETUNIQUEID", 1);
      AddDecisionQueue("ADDLIMITEDCURRENTEFFECT", $currentPlayer, "4910017138,HAND", 1);
      AddDecisionQueue("PASSPARAMETER", $currentPlayer, "{0}", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "ATTACK", 1);
      break;
    case "3799780905"://Prisoner of War
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to capture another unit");
      AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "GETUNIQUEID", 1);
      AddDecisionQueue("SETDQVAR", $currentPlayer, "0", 1);
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "THEIRALLY", 1);
      AddDecisionQueue("MZFILTER", $currentPlayer, "leader=1", 1);
      AddDecisionQueue("MZFILTER", $currentPlayer, "trait=Vehicle");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to capture", 1);
      AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "CAPTURE,{0}", 1);
      AddDecisionQueue("SPECIFICCARD", $currentPlayer, "PRISONEROFWAR", 1);
      break;
    case "3500129784"://Petition the Senate
      if(SearchCount(SearchAllies($currentPlayer, trait:"Official")) >= 3) {
        Draw($currentPlayer);
        Draw($currentPlayer);
        Draw($currentPlayer);
      }
      break;
    case "3476041913"://Low Altitude Gunship
      $damage = SearchCount(SearchAllies($currentPlayer, trait:"Republic"));
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "THEIRALLY");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to deal " . $damage . " damage to");
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "DEALDAMAGE,$damage,$currentPlayer,1", 1);
      break;
    case "2784756758"://Obi-wan Kenobi
      $abilityName = GetResolvedAbilityName($cardID, $from);
      if($abilityName == "Heal") {
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY:damagedOnly=true&THEIRALLY:damagedOnly=true");
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to heal");
        AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "RESTORE,1", 1);
      }
      break;
    case "8929774056"://Asajj Ventress
      global $CS_NumEventsPlayed;
      if(GetClassState($currentPlayer, $CS_NumEventsPlayed) > 0) AddCurrentTurnEffect("8929774056", $currentPlayer, "PLAY");
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY");
      AddDecisionQueue("MZFILTER", $currentPlayer, "status=1", 1);
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to attack with");
      AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "ATTACK", 1);
      break;
    case "9966134941"://Pelta Supply Frigate
      if(IsCoordinateActive($currentPlayer)) CreateCloneTrooper($currentPlayer);
      break;
    case "6461101372"://Maul
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY");
      AddDecisionQueue("MZFILTER", $currentPlayer, "status=1", 1);
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to attack with");
      AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("ADDCURRENTEFFECT", $currentPlayer, "6461101372", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "ATTACK", 1);
      break;
    case "2155351882"://Ahsoka Tano
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY");
      AddDecisionQueue("MZFILTER", $currentPlayer, "status=1", 1);
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to attack with");
      AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("ADDCURRENTEFFECT", $currentPlayer, "2155351882", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "ATTACK", 1);
      break;
    case "5081383630"://Pre Viszla
      global $CS_CardsDrawn;
      $cardsDrawn = GetClassState($currentPlayer, $CS_CardsDrawn);
      if($cardsDrawn > 0) {
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY&THEIRALLY");
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to deal " . $cardsDrawn . " damage to");
        AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "DEALDAMAGE,$cardsDrawn,$currentPlayer", 1);
      }
      break;
    case "8061497086"://Perilous Position
      $ally = new Ally($target, MZPlayerID($currentPlayer, $target));
      $ally->Exhaust(enemyEffects:$currentPlayer != $ally->Controller());
      $ally->DefeatIfNoRemainingHP();
      break;
    case "8345985976"://Trade Federation Shuttle
      if(SearchCount(SearchAllies($currentPlayer, damagedOnly:true))) CreateBattleDroid($currentPlayer);
      break;
    case "8060312086"://Self Destruct
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to sacrifice");
      AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "DESTROY,$currentPlayer", 1);
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY&THEIRALLY", 1);
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to deal 4 damage to", 1);
      AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "DEALDAMAGE,4,$currentPlayer", 1);
      break;
    case "8540765053"://Savage Opress
      if(HasMoreUnits($otherPlayer)) $playAlly->Ready();
      break;
    case "9620454519"://Clear the Field
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY:maxCost=3&THEIRALLY:maxCost=3");
      AddDecisionQueue("MZFILTER", $currentPlayer, "leader=1");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to return to hand");
      AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("SPECIFICCARD", $currentPlayer, "CLEARTHEFIELD", 1);
      break;
    case "9832122703"://Luminara Unduli
      $healAmount = SearchCount(SearchAllies($currentPlayer));
      Restore($healAmount, $currentPlayer);
      break;
    case "1882027961"://Wolf Pack Escort
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY");
      AddDecisionQueue("MZFILTER", $currentPlayer, "leader=1");
      AddDecisionQueue("MZFILTER", $currentPlayer, "trait=Vehicle");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to return to hand");
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "BOUNCE", 1);
      break;
    case "1389085256"://Lethal Crackdown
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY&THEIRALLY");
      AddDecisionQueue("MZFILTER", $currentPlayer, "leader=1");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to destroy");
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("SPECIFICCARD", $currentPlayer, "LETHALCRACKDOWN", 1);
      break;
    case "5683908835"://Count Dooku
      AddCurrentTurnEffect("5683908835", $currentPlayer);
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYHAND:trait=Separatist");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a card to play");
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "PLAYCARD", 1);
      break;
    case "4628885755"://Mace Windu
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "THEIRALLY:damagedOnly=true");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to deal damage");
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("SETDQVAR", $currentPlayer, "0", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "GETDAMAGE", 1);
      AddDecisionQueue("LESSTHANPASS", $currentPlayer, "4"); // Check if the unit has at least 4 damage to take 2 damage
      AddDecisionQueue("PASSPARAMETER", $currentPlayer, "{0}", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "DEALDAMAGE,2,$currentPlayer", 1);
      AddDecisionQueue("ELSE", $currentPlayer, "-");
      AddDecisionQueue("PASSPARAMETER", $currentPlayer, "{0}", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "DEALDAMAGE,1,$currentPlayer", 1);
      break;
    case "0026166404"://Chancellor Palpatine Leader
      if (SearchCount(SearchAlliesDestroyed($currentPlayer, aspect:"Heroism")) > 0) {
        Draw($currentPlayer);
        Restore(2, $currentPlayer);
        $char = &GetPlayerCharacter($currentPlayer);
        $char[CharacterPieces()] = "ad86d54e97";
        $char[CharacterPieces() + 1] = 1; // Ehxaust the flipped Leader. It's necessary to manually exhaust the Leader only if the Leader was flipped.
      }
      break;
    case "ad86d54e97"://Darth Sidious Leader
      global $CS_NumVillainyPlayed;
      if (GetClassState($currentPlayer, $CS_NumVillainyPlayed) > 0) {
        CreateCloneTrooper($currentPlayer);
        DealDamageAsync(($currentPlayer == 1 ? 2 : 1), 2, "DAMAGE", "ad86d54e97", sourcePlayer:$currentPlayer);
        $char = &GetPlayerCharacter($currentPlayer);
        $char[CharacterPieces()] = "0026166404"; // Chancellor Palpatine Leader
        $char[CharacterPieces() + 1] = 1; // Ehxaust the flipped Leader. It's necessary to manually exhaust the Leader only if the Leader was flipped.
      }
      break;
    case "7734824762"://Captain Rex
      global $CS_NumAttacks;
      if(GetClassState($currentPlayer, $CS_NumAttacks) > 0) {
        CreateCloneTrooper($currentPlayer);
      }
      break;
    case "3410014206"://Vanguard Droid Bomber
      if(SearchCount(SearchAllies($currentPlayer, trait:"Separatist")) > 1) {
        DealDamageAsync($currentPlayer == 1 ? 2 : 1, 2, "DAMAGE", "3410014206", sourcePlayer:$currentPlayer);
      }
      break;
    case "4210027426"://Heavy Persuader Tank
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY:arena=Ground&THEIRALLY:arena=Ground", 1);
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to deal 2 damage to", 1);
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "DEALDAMAGE,2,$currentPlayer,1", 1);
      break;
    case "4512764429"://Sanctioner's Shuttle
      if($from != "PLAY" && IsCoordinateActive($currentPlayer)) {
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "THEIRALLY:maxCost=3");
        AddDecisionQueue("MZFILTER", $currentPlayer, "leader=1");
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to capture");
        AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "CAPTURE," . $playAlly->UniqueID(), 1);
      }
      break;
    case "6849037019"://Now There Are Two of Them
      $allies = &GetAllies($currentPlayer);
      if(count($allies) == AllyPieces()) {
        $ally = new Ally("MYALLY-0");
        $traits = array_map('trim', explode(',', CardTraits($ally->CardID())));
        if ($ally->HasUpgrade("7687006104") && !in_array("Mandalorian", $traits)) $traits[] = "Mandalorian";
        $traits = implode(',', $traits);
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a card to put into play");
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYHAND:definedType=Unit");
        AddDecisionQueue("MZFILTER", $currentPlayer, "trait=Vehicle");
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("NOTSHARETRAITPASS", $currentPlayer, $traits, 1);
        AddDecisionQueue("ADDCURRENTEFFECT", $currentPlayer, $cardID, 1);
        AddDecisionQueue("MZOP", $currentPlayer, "PLAYCARD", 1);
      }
      break;
    case "7013591351"://Admiral Trench
      MZMoveCard($currentPlayer, "MYDISCARD:definedType=Unit;defeatedThisPhase=true", "MYHAND", may:true, context:"Return up to 3 units that were defeated this phase");
      MZMoveCard($currentPlayer, "MYDISCARD:definedType=Unit;defeatedThisPhase=true", "MYHAND", may:true, context:"Return up to 2 units that were defeated this phase", isSubsequent:1);
      MZMoveCard($currentPlayer, "MYDISCARD:definedType=Unit;defeatedThisPhase=true", "MYHAND", may:true, context:"Return 1 unit that was defeated this phase", isSubsequent:1);
      break;
    case "6648824001":
      ObiWansAethersprite($currentPlayer, $playAlly->Index());
      break;
    case "6669050232"://Grim Resolve
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY");
      AddDecisionQueue("MZFILTER", $currentPlayer, "status=1");
      AddDecisionQueue("MZFILTER", $currentPlayer, "leader=1");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a card to attack and give Grit");
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("SETDQVAR", $currentPlayer, "0", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "GETUNIQUEID", 1);
      AddDecisionQueue("ADDLIMITEDCURRENTEFFECT", $currentPlayer, "6669050232,HAND", 1);
      AddDecisionQueue("PASSPARAMETER", $currentPlayer, "{0}", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "ATTACK", 1);
      break;
    case "2565830105"://Invasion of Christophsis
      DestroyAllAllies($otherPlayer);
      break;
    case "2535372432"://Aggrieved Parliamentarian
      $theirDiscard = &GetDiscard($otherPlayer);
      $deck = new Deck($otherPlayer);
      for($i=count($theirDiscard) - DiscardPieces(); $i>=0; $i-=DiscardPieces()) {
        $deck->Add(RemoveDiscard($otherPlayer, $i));
      }
      break;
    case "5184505570"://Chimaera JTL
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY:hasWhenDefeatedOnly=true");
      AddDecisionQueue("MZFILTER", $currentPlayer, "index=MYALLY-" . $playAlly->Index());
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to use a When Defeated ability");
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("USEWHENDEFEATED", $currentPlayer, "-", 1);
      break;
    case "0398102006"://The Invisible Hand
      CreateBattleDroid($currentPlayer);
      CreateBattleDroid($currentPlayer);
      CreateBattleDroid($currentPlayer);
      CreateBattleDroid($currentPlayer);
      break;
    case "1686059165"://Wat Tambor
      global $CS_NumAlliesDestroyed;
      if(GetClassState($currentPlayer, $CS_NumAlliesDestroyed) > 0) {
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY");
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to give +2/+2");
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "ADDHEALTH,2", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "GETUNIQUEID", 1);
        AddDecisionQueue("ADDLIMITEDCURRENTEFFECT", $currentPlayer, "1686059165,PLAY", 1);
      }
      break;
    case "2041344712"://Osi Sobeck
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "THEIRALLY:arena=Ground;maxCost=" . $resourcesPaid);
      AddDecisionQueue("MZFILTER", $currentPlayer, "leader=1");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to capture");
      AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "CAPTURE," . $playAlly->UniqueID(), 1);
      break;
    case "2298508689"://Reckless Torrent
      if(IsCoordinateActive($currentPlayer)) {
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY");
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "You may choose a friendly unit to deal 2 damage");
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "DEALDAMAGE,2,$currentPlayer,1", 1);
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "THEIRALLY", 1);
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to deal 2 damage to (make sure it's same arena)", 1);
        AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "DEALDAMAGE,2,$currentPlayer,1", 1);
      }
      break;
    case "2395430106"://Republic Tactical Officer
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY:trait=Republic");
      AddDecisionQueue("MZFILTER", $currentPlayer, "status=1");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a card to attack and give +2");
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("SETDQVAR", $currentPlayer, "0", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "GETUNIQUEID", 1);
      AddDecisionQueue("ADDLIMITEDCURRENTEFFECT", $currentPlayer, "2395430106,HAND", 1);
      AddDecisionQueue("PASSPARAMETER", $currentPlayer, "{0}");
      AddDecisionQueue("MZOP", $currentPlayer, "ATTACK", 1);
      break;
    case "2267524398"://The Clone Wars
      for($i=0; $i<$resourcesPaid-2; ++$i) {
        CreateCloneTrooper($currentPlayer);
        CreateBattleDroid($otherPlayer);
      }
      break;
    case "1302133998"://Impropriety Among Thieves
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "THEIRALLY");
      AddDecisionQueue("MZFILTER", $currentPlayer, "leader=1");
      AddDecisionQueue("MZFILTER", $currentPlayer, "status=1");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose their unit to take control of", 1);
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "GETUNIQUEID", 1);
      AddDecisionQueue("SETDQVAR", $currentPlayer, "0", 1);
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY", 1);
      AddDecisionQueue("MZFILTER", $currentPlayer, "leader=1", 1);
      AddDecisionQueue("MZFILTER", $currentPlayer, "status=1", 1);
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to give control of", 1);
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "GETUNIQUEID", 1);
      AddDecisionQueue("MZOP", $otherPlayer, "TAKECONTROL", 1);
      AddDecisionQueue("ADDLIMITEDROUNDEFFECT", $otherPlayer, "1302133998,PLAY", 1);
      AddDecisionQueue("PASSPARAMETER", $currentPlayer, "{0}", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "TAKECONTROL", 1);
      AddDecisionQueue("ADDLIMITEDROUNDEFFECT", $currentPlayer, "1302133998,PLAY", 1);
      break;
    case "2847868671"://Yoda Leader
      global $CS_NumLeftPlay;
      if(GetClassState($currentPlayer, $CS_NumLeftPlay) > 0 || GetClassState($otherPlayer, $CS_NumLeftPlay) > 0) {
        $drawnCardID = Draw($currentPlayer, specialCase: true);
        switch($drawnCardID) {
          case "6172986745"://Rey, With Palpatine's Power
            AddDecisionQueue("SPECIFICCARD", $currentPlayer, "REY_LOF_LEADERDRAW,$cardID", 1);
            break;
          default:
            AddDecisionQueue("HANDTOPBOTTOM", $currentPlayer, "-");
            break;
        }
      }
      break;
    case "3381931079"://Malevolence
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "THEIRALLY");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a card to give -4/-0", 1);
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "GETUNIQUEID", 1);
      AddDecisionQueue("ADDLIMITEDCURRENTEFFECT", $otherPlayer, "3381931079,HAND", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "WRITECHOICEFROMUNIQUE", 1);
      break;
    case "5333016146"://Rune Haako
      global $CS_NumAlliesDestroyed;
      if(GetClassState($currentPlayer, $CS_NumAlliesDestroyed) > 0)
        DQDebuffUnit($currentPlayer, $otherPlayer, $cardID, 1);
      break;
    case "6064906790"://Nute Gunray
      WriteLog(DefinedCardType($cardID));
      global $CS_NumAlliesDestroyed;
      if(GetClassState($currentPlayer, $CS_NumAlliesDestroyed) >= 2) {
        CreateBattleDroid($currentPlayer);
      }
      break;
    case "2872203891"://General Grievous
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a card to give Sentinel");
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY:trait=Droid&THEIRALLY:trait=Droid");
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "WRITECHOICE", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "GETUNIQUEID", 1);
      AddDecisionQueue("ADDLIMITEDCURRENTEFFECT", $currentPlayer, "2872203891,HAND", 1);
      break;
    case "0693815329"://Cad Bane (Hostage Taker)
      if($from != "PLAY") {
        AddDecisionQueue("PASSPARAMETER", $currentPlayer, "8");
        AddDecisionQueue("SETDQVAR", $currentPlayer, "0");
        for($i=0; $i<3; ++$i) {
          AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "THEIRALLY:maxHealth={0}", 1);
          AddDecisionQueue("MZFILTER", $currentPlayer, "leader=1", 1);
          AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to capture (Max HP: {0})", 1);
          AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
          AddDecisionQueue("SETDQVAR", $currentPlayer, "1", 1);
          AddDecisionQueue("MZOP", $currentPlayer, "GETHEALTH", 1);
          AddDecisionQueue("DECDQVAR", $currentPlayer, "0", 1);
          AddDecisionQueue("PASSPARAMETER", $currentPlayer, "{1}", 1);
          AddDecisionQueue("MZOP", $currentPlayer, "CAPTURE," . $playAlly->UniqueID(), 1);
          AddDecisionQueue("PASSPARAMETER", $currentPlayer, "{0}", 1);
          AddDecisionQueue("LESSTHANPASS", $currentPlayer, 1, 1);
        }
      }
      break;
    case "8418001763"://Huyang
      DQChooseAUnitToGiveEffect($currentPlayer, $cardID, $from,
        may:false, mzSearch:"MYALLY", mzFilter: "index=" . $playAlly->MZIndex(), context:"a unit to give +2/+2",lastingType:"Permanent");
      break;
    case "0216922902"://The Zillo Beast
      $theirAllies = &GetTheirAllies($currentPlayer);
      for ($i = 0; $i < count($theirAllies); $i += AllyPieces()) {
        if (CardArenas($theirAllies[$i]) == "Ground") {
          AddCurrentTurnEffect("0216922902", $otherPlayer, "PLAY", $theirAllies[$i+5]);
        }
      }
      break;
    case "4916334670"://Encouraging Leadership
      $allies = &GetAllies($currentPlayer);
      for ($i = 0; $i < count($allies); $i += AllyPieces()) {
        AddDecisionQueue("PASSPARAMETER", $currentPlayer, "MYALLY-$i", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "ADDHEALTH,1", 1);
        AddDecisionQueue("PASSPARAMETER", $currentPlayer, $allies[$i+5], 1);
        AddDecisionQueue("ADDLIMITEDCURRENTEFFECT", $currentPlayer, "4916334670,PLAY", 1);
      }
      break;
    case "3596811933"://Disruptive Burst
      $otherPlayer = $currentPlayer == 1 ? 2 : 1;
      $theirAllies = &GetAllies($otherPlayer);
      for ($i = count($theirAllies) - AllyPieces(); $i >= 0; $i -= AllyPieces()) {
        $theirAlly = new Ally("MYALLY-" . $i, $otherPlayer);
        $theirAlly->AddEffect("3596811933", "PLAY");
        $theirAlly->AddRoundHealthModifier(-1);
      }
      break;
    case "2870878795"://Padme Amidala
      if(IsCoordinateActive($currentPlayer)) {
        AddDecisionQueue("SEARCHDECKTOPX", $currentPlayer, "3;1;include-trait-Republic");
        AddDecisionQueue("ADDHAND", $currentPlayer, "-", 1);
        AddDecisionQueue("REVEALCARDS", $currentPlayer, "DECK", 1);
      }
      break;
    case "4042866439"://Grenade Strike
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY&THEIRALLY");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to deal 2 damage to", 1);
      AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("SETDQVAR", $currentPlayer, "0", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "GETUNIQUEID", 1);
      AddDecisionQueue("SETDQVAR", $currentPlayer, "1", 1);
      AddDecisionQueue("PASSPARAMETER", $currentPlayer, "{0}", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "GETARENA", 1);
      AddDecisionQueue("SETDQVAR", $currentPlayer, "2", 1);
      AddDecisionQueue("PASSPARAMETER", $currentPlayer, "{0}", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "DEALDAMAGE,2,$currentPlayer", 1);
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY:arena={2}&THEIRALLY:arena={2}", 1);
      AddDecisionQueue("MZFILTER", $currentPlayer, "uniqueID={1}", 1);
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to deal 1 damage to", 1);
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "DEALDAMAGE,1,$currentPlayer", 1);
      break;
    case "2483520485"://Private Manufacturing
      Draw($currentPlayer);
      Draw($currentPlayer);
      if(SearchCount(SearchAllies($currentPlayer, tokenOnly:true)) == 0) {
        MZMoveCard($currentPlayer, "MYHAND", "MYBOTDECK", context:"Choose a card to put on the bottom of your deck");
        MZMoveCard($currentPlayer, "MYHAND", "MYBOTDECK", context:"Choose a card to put on the bottom of your deck");
      }
      break;
    case "0633620454"://Synchronized Strike
      $damage = SearchCount(SearchAllies($currentPlayer, arena:$additionalCosts));
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "THEIRALLY:arena=" . $additionalCosts);
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to deal " . $damage . " damage to", 1);
      AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "DEALDAMAGE,$damage,$currentPlayer", 1);
      break;
    case "1039828081"://Calculating MagnaGuard
      AddCurrentTurnEffect("1039828081", $currentPlayer, "PLAY");
      break;
    case "0056489820"://Unlimited Power
      AddDecisionQueue("PASSPARAMETER", $currentPlayer, "-");
      AddDecisionQueue("SETDQVAR", $currentPlayer, 0);
      for($i=4; $i>=1; --$i) {
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY&THEIRALLY");
        AddDecisionQueue("MZFILTER", $currentPlayer, "dqVar=0");
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to deal " . $i . " damage to", 1);
        AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("SETDQVAR", $currentPlayer, 1, 1);
        AddDecisionQueue("MZOP", $currentPlayer, "GETUNIQUEID", 1);
        AddDecisionQueue("APPENDDQVAR", $currentPlayer, 0, 1);
        AddDecisionQueue("PASSPARAMETER", $currentPlayer, "{1}", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "DEALDAMAGE,$i,$currentPlayer", 1);
      }
      break;
    case "0741296536"://Ahsoka's Padawan Lightsaber
      if(CardTitle(GetMZCard($currentPlayer, $target)) == "Ahsoka Tano") {
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY");
        AddDecisionQueue("MZFILTER", $currentPlayer, "status=1");
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a card to attack");
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "ATTACK", 1);
      }
      break;
    case "3033790509"://Captain Typho
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY&THEIRALLY");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a card to give Sentinel");
      AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "GETUNIQUEID", 1);
      AddDecisionQueue("ADDLIMITEDCURRENTEFFECT", $currentPlayer, "3033790509,PLAY", 1);
      break;
    case "4489623180"://Ziro the Hutt
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "THEIRALLY");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a card to exhaust");
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "REST", 1);
      break;
    case "7579458834"://Reprocess
      //Choose up to 4 units in your discard pile
      AddDecisionQueue("FINDINDICES", $currentPlayer, "GYUNITS");
      AddDecisionQueue("PREPENDLASTRESULT", $currentPlayer, "4-");
      AddDecisionQueue("MULTICHOOSEDISCARD", $currentPlayer, "<-");
      //specific card "Reprocess"
      AddDecisionQueue("SPECIFICCARD", $currentPlayer, "REPROCESS", 1);
      break;
    case "8414572243"://Enfys Nest (Champion of Justice)
      $enfyAlly = new Ally($uniqueId);
      $enfyBouncePower = $enfyAlly->CurrentPower() - 1;
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "THEIRALLY:maxAttack=$enfyBouncePower");
      AddDecisionQueue("MZFILTER", $currentPlayer, "leader=1");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a card to bounce");
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "BOUNCE", 1);
      break;
    case "7979348081"://Kraken
      CreateBattleDroid($currentPlayer);
      CreateBattleDroid($currentPlayer);
      break;
    case "1272825113"://In Defense of Kamino
      $allies = &GetAllies($currentPlayer);
      for ($i = 0; $i < count($allies); $i += AllyPieces()) {
        if (TraitContains($allies[$i], "Republic", $currentPlayer)) {
          AddCurrentTurnEffect($cardID, $currentPlayer, "PLAY", $allies[$i+5]);
        }
      }
      break;
    case "9415708584"://Pyrrhic Assault
      $allies = &GetAllies($currentPlayer);
      for ($i = 0; $i < count($allies); $i += AllyPieces()) {
        AddCurrentTurnEffect($cardID, $currentPlayer, "PLAY", $allies[$i+5]);
      }
      break;
    case "9399634203"://I Have the High Ground
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit");
      AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "GETUNIQUEID", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "WRITECHOICE", 1);
      AddDecisionQueue("ADDLIMITEDCURRENTEFFECT", $currentPlayer, "9399634203,HAND", 1);
      break;
    case "1167572655"://Planetary Invasion
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY");
      AddDecisionQueue("OP", $currentPlayer, "MZTONORMALINDICES");
      AddDecisionQueue("PREPENDLASTRESULT", $currentPlayer, "3-", 1);
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose up to 3 units to ready", 1);
      AddDecisionQueue("MULTICHOOSEUNIT", $currentPlayer, "<-", 1, 1);
      AddDecisionQueue("SPECIFICCARD", $currentPlayer, "PLANETARYINVASION", 1);
      break;
    case "4033634907"://No Disintegrations
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY&THEIRALLY");
      AddDecisionQueue("MZFILTER", $currentPlayer, "leader=1");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to deal all but one damage to", 1);
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("SPECIFICCARD", $currentPlayer, "NODISINTEGRATIONS", 1);
      break;
    case "2012334456"://On Top of Things
      $ally = new Ally($target, $currentPlayer);
      $ally->AddEffect("2012334456", "PLAY");
      break;
    case "5610901450"://Heroes on Both Sides
      //Republic
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY:trait=Republic&THEIRALLY:trait=Republic");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a card to give +2/+2", 1);
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "ADDHEALTH,2", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "GETUNIQUEID", 1);
      AddDecisionQueue("ADDLIMITEDCURRENTEFFECT", $currentPlayer, "5610901450,PLAY", 1);
      //Separatist
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY:trait=Separatist&THEIRALLY:trait=Separatist");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a card to give +2/+2", 1);
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "ADDHEALTH,2", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "GETUNIQUEID", 1);
      AddDecisionQueue("ADDLIMITEDCURRENTEFFECT", $currentPlayer, "5610901450,PLAY", 1);
      break;
    case "7732981122"://Sly Moore
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "THEIRALLY");
      AddDecisionQueue("MZFILTER", $currentPlayer, "token=0", 1);
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to take control of", 1);
      AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "READY", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "GETUNIQUEID", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "TAKECONTROL", 1);
      AddDecisionQueue("ADDLIMITEDROUNDEFFECT", $currentPlayer, "7732981122,PLAY", 1);
      break;
    case "8719468890"://Sword and Shield Maneuver
      AddCurrentTurnEffect("8719468890", $currentPlayer, "PLAY");
      break;
    case "3459567689"://Wartime Profiteering
      global $CS_NumAlliesDestroyed;
      $numDefeated = GetClassState(1, $CS_NumAlliesDestroyed) + GetClassState(2, $CS_NumAlliesDestroyed);
      AddDecisionQueue("SEARCHDECKTOPX", $currentPlayer, $numDefeated . ";1;");
      AddDecisionQueue("MULTIADDHAND", $currentPlayer, "-", 1);
      AddDecisionQueue("REVEALCARDS", $currentPlayer, "DECK", 1);
      break;
    //Jump to Lightspeed
    case "0425156332"://Planetary Bombardment
      $hasCapitalShip = SearchCount(SearchAllies($currentPlayer, trait:"Capital Ship")) > 0;
      $indirectAmount = $hasCapitalShip ? 12 : 8;
      IndirectDamage($cardID, $currentPlayer, $indirectAmount);
      break;
    case "2778554011"://General Draven
      CreateXWing($currentPlayer);
      break;
    case "1303370295"://Death Space Skirmisher
      if($from != "PLAY") {
        if (SearchCount(SearchAllies($currentPlayer, arena: "Space")) > 1) {
          AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY&THEIRALLY");
          AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to exhaust");
          AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
          AddDecisionQueue("MZOP", $currentPlayer, "REST", 1);
        }
      }
      break;
    case "1330473789"://Devastator
      if($from != "PLAY") {
        IndirectDamage($cardID, $currentPlayer, 4, true, $playAlly->UniqueID(), targetPlayer: $otherPlayer);
      }
      break;
    case "2388374331"://Blue Leader
      if($from != "PLAY" && NumResourcesAvailable($currentPlayer) >= 2) {
        AddDecisionQueue("YESNO", $currentPlayer, "if you want to pay 2 to gain 2 experience tokens", 1);
        AddDecisionQueue("NOPASS", $currentPlayer, "-", 1);
        AddDecisionQueue("PAYRESOURCES", $currentPlayer, "2", 1);
        AddDecisionQueue("PASSPARAMETER", $currentPlayer, "MYALLY-" . $playAlly->Index(), 1);
        AddDecisionQueue("MZOP", $currentPlayer, "ADDEXPERIENCE", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "ADDEXPERIENCE", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "MOVEARENA,Ground", 1);
      }
      break;
    case "4179470615"://Asajj Ventress Leader
      $abilityName = GetResolvedAbilityName($cardID, $from);
      if($abilityName == "Damage") {
        AsajjVentressIWorkAlone($currentPlayer);
      }
      break;
    case "0926549684"://Resupply Carrier
      if($from != "PLAY" && count(GetDeck($currentPlayer)) > 0) {
        AddDecisionQueue("YESNO", $currentPlayer, "if you want to add a resource from the top of your deck");
        AddDecisionQueue("NOPASS", $currentPlayer, "-", 1);
        AddDecisionQueue("OP", $currentPlayer, "ADDTOPDECKASRESOURCE", 1);
      }
      break;
    case "8833191722"://Never Tell Me the Odds
      $damageAmount = 0;
      $cards = Mill(1, 3);
      if($cards != "") {
        $cards = explode(",", $cards);
        for($i=0; $i<count($cards); ++$i) {
          if(CardCostIsOdd($cards[$i])) ++$damageAmount;
        }
      }
      $cards = Mill(2, 3);
      if($cards != "") {
        $cards = explode(",", $cards);
        for($i=0; $i<count($cards); ++$i) {
          if(CardCostIsOdd($cards[$i])) ++$damageAmount;
        }
      }
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY&THEIRALLY");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to deal $damageAmount damage to");
      AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "DEALDAMAGE,$damageAmount,$currentPlayer,1", 1);
      break;
    case "4030832630"://Admiral Piett Leader
      if(GetResolvedAbilityName($cardID) == "Play") {
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a card to play");
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYHAND:trait=Capital_Ship");
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("ADDCURRENTEFFECT", $currentPlayer, $cardID, 1);
        AddDecisionQueue("MZOP", $currentPlayer, "PLAYCARD", 1);
      }
      break;
    case "0011262813"://Wedge Antilles Leader
      $vehiclesAvailableToPilot = SearchCount(SearchAllies($currentPlayer, trait:"Vehicle"));
      if(GetResolvedAbilityName($cardID) == "Play" && $vehiclesAvailableToPilot > 0) {
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a pilot to play", 1);
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYHAND:keyword=Piloting", 1);
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("ADDCURRENTEFFECT", $currentPlayer, $cardID, 1);
        AddDecisionQueue("MZOP", $currentPlayer, "PLAYCARD", 1);
      }
      break;
    case "3933322003"://Rose Tico Leader
      if(GetResolvedAbilityName($cardID) == "Heal") {
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY:trait=Vehicle&THEIRALLY:trait=Vehicle");
        AddDecisionQueue("MZFILTER", $currentPlayer, "numAttacks=0");
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a vehicle unit to heal");
        AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "RESTORE,2", 1);
      }
      break;
    case "0616724418"://Han Solo Leader
      if(GetResolvedAbilityName($cardID) == "Odds") {
        $deck = new Deck($currentPlayer);
        if($deck->Reveal()) {
          $cardCost = CardCost($deck->Top());
        } else {
          $cardCost = 2; // If the deck is empty, we'll set the card cost to 2 only to say that the card cost is not odd
        }
        AddDecisionQueue("PASSPARAMETER", $currentPlayer, $cardCost);
        AddDecisionQueue("SETDQVAR", $currentPlayer, "0");
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY");
        AddDecisionQueue("MZFILTER", $currentPlayer, "status=1");
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a card to attack with");
        AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("SPECIFICCARD", $currentPlayer, "HAN_SOLO_LEADER_JTL", 1);
      }
      break;
    case "3658069276"://Lando Calrissian Leader
      if(GetResolvedAbilityName($cardID) == "Play") {
        global $CS_AfterPlayedBy;
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to play");
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYHAND:definedType=Unit");
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("SETDQVAR", $currentPlayer, "0", 1);
        AddDecisionQueue("PASSPARAMETER", $currentPlayer, $cardID, 1);
        AddDecisionQueue("SETCLASSSTATE", $currentPlayer, $CS_AfterPlayedBy, 1);
        AddDecisionQueue("PASSPARAMETER", $currentPlayer, "{0}", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "PLAYCARD", 1);
      }
      break;
    case "9763190770"://Major Vonreg Leader
      if(GetResolvedAbilityName($cardID) == "Play") {
        global $CS_AfterPlayedBy;
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to play");
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYHAND:definedType=Unit&trait=Vehicle");
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("SETDQVAR", $currentPlayer, "0", 1);
        AddDecisionQueue("PASSPARAMETER", $currentPlayer, $cardID, 1);
        AddDecisionQueue("SETCLASSSTATE", $currentPlayer, $CS_AfterPlayedBy, 1);
        AddDecisionQueue("PASSPARAMETER", $currentPlayer, "{0}", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "PLAYCARD", 1);
      }
    case "7514405173"://Admiral Ackbar Leader
      if(GetResolvedAbilityName($cardID) == "Exhaust") {
        AdmiralAckbarItsATrap($currentPlayer, flipped:false);
      }
      break;
    case "1519837763"://Shuttle ST-149
      if($from != "PLAY") {
        ShuttleST149($currentPlayer);
      }
      break;
    case "6648978613"://Fett's Firespray (Feared Silhouettte)
      if($from != "PLAY") {
        $damage = ControlsNamedCard($currentPlayer, "Boba Fett") ? 2 : 1;
        IndirectDamage($cardID, $currentPlayer, $damage, true, $playAlly->UniqueID());
      }
      break;
    case "4819196588"://Electromagnetic Pulse
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY:trait=Vehicle&THEIRALLY:trait=Vehicle&MYALLY:trait=Droid&THEIRALLY:trait=Droid");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a DROID or VEHICLE unit to deal 2 damage and exhaust");
      AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "DEALDAMAGE,2,$currentPlayer", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "REST", 1);
      break;
    case "3722493191"://IG-2000
       if($from != "PLAY" && SearchCount(SearchAllies($otherPlayer)) > 0) {
        DQMultiUnitSelect($currentPlayer, 3, "MYALLY&THEIRALLY", "to damage");
        AddDecisionQueue("MZOP", $currentPlayer, DealMultiDamageBuilder($currentPlayer, isUnitEffect:1), 1);
      }
      break;
    case "0964312065"://It's A Trap!
      $spaceAllies = SearchAllies($currentPlayer, arena:"Space");
      $spaceEnemiesCount = SearchCount(SearchAllies($otherPlayer, arena:"Space"));
      if(SearchCount($spaceAllies) < $spaceEnemiesCount) {
        $spaceAllies = explode(",", $spaceAllies);
        for($i=0;$i<count($spaceAllies);++$i) {
          $ally = new Ally("MYALLY-" . $spaceAllies[$i]);
          $ally->Ready();
        }
      }
    case "6421006753"://The Mandalorian
      if($from != "PLAY" && SearchCount(SearchAlliesForCard($currentPlayer, "6421006753")) > 0) {
        for ($i = 0; $i < 2; $i++) {
          AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY:arena=Ground&THEIRALLY:arena=Ground");
          AddDecisionQueue("MZFILTER", $currentPlayer, "status=1");
          AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to exhaust");
          AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
          AddDecisionQueue("MZOP", $currentPlayer, "REST", 1);
        }
      }
      break;
    case "7924461681"://Leia Organa
      if($from != "PLAY") {
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY:trait=Pilot&MYALLY:hasPilotOnly=1");
        AddDecisionQueue("MZFILTER", $currentPlayer, "status=1");
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a card to attack with");
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("SPECIFICCARD", $currentPlayer, "LEIA_JTL", 1);
      }
      break;
    case "8105698374"://Commandeer
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY:trait=Vehicle;maxCost=6&THEIRALLY:trait=Vehicle;maxCost=6");
      AddDecisionQueue("MZFILTER", $currentPlayer, "leader=1");
      AddDecisionQueue("MZFILTER", $currentPlayer, "hasPilot=1");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to take control of", 1);
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "GETUNIQUEID", 1);
      AddDecisionQueue("SPECIFICCARD", $currentPlayer, "COMMANDEER", 1);
      break;
    case "4334684518"://Tandem Assault
      AddCurrentTurnEffect($cardID . "-1", $currentPlayer);
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY:arena=Space");
      AddDecisionQueue("MZFILTER", $currentPlayer, "status=1");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a space unit to attack with");
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "ATTACK", 1);
      break;
    case "5093056978"://Direct Hit
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "THEIRALLY:trait=Vehicle&MYALLY:trait=Vehicle");
      AddDecisionQueue("MZFILTER", $currentPlayer, "leader=1");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a vehicle unit to defeat");
      AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "DESTROY,$currentPlayer", 1);
      break;
    case "5345999887"://Kijimi patrollers
      if($from != "PLAY") {
        CreateTieFighter($currentPlayer);
      }
      break;
    case "7072861308"://Profundity
      if($from != "PLAY") {
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose player to discard 1 card");
        AddDecisionQueue("BUTTONINPUT", $currentPlayer, "Yourself,Opponent");
        AddDecisionQueue("SPECIFICCARD", $currentPlayer, "PROFUNDITY", 1);
      }
      break;
    case "8656409691"://Rio Durant
      if(GetResolvedAbilityName($cardID) == "Attack") {
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY:arena=Space");
        AddDecisionQueue("MZFILTER", $currentPlayer, "status=1", 1);
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to attack with");
        AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("ADDCURRENTEFFECT", $currentPlayer, "8656409691", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "ATTACK", 1);
      }
      break;
    case "8943696478";//Admiral Holdo
      if(GetResolvedAbilityName($cardID) == "Buff") {
        AdmiralHoldoWereNotAlone($currentPlayer, flipped:false);
      }
      break;
    case "9695562265"://Koiogran Turn
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY:trait=Fighter;maxAttack=6&MYALLY:trait=Transport;maxAttack=6&THEIRALLY:trait=Fighter;maxAttack=6&THEIRALLY:trait=Transport;maxAttack=6");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a Fighter or Transport to ready");
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "READY", 1);
      break;
    case "1965647391"://Blade Squadron B-Wing
      if($from != "PLAY") {
        $theirAllies = &GetAllies($otherPlayer);
        $numExhausted = 0;
        for($i=0; $i<count($theirAllies); $i+=AllyPieces()) {
        if($theirAllies[$i+1] == 1) ++$numExhausted;
        }
        if($numExhausted >= 3) {
          AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY&THEIRALLY");
          AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to give a shield to");
          AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
          AddDecisionQueue("MZOP", $currentPlayer, "ADDSHIELD", 1);
        }
      }
      break;
    case "0766281795"://Luke Skywalker
      if(GetResolvedAbilityName($cardID) == "Deal Damage" && GetClassState($currentPlayer, $CS_NumFighterAttacks) > 0) {
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY&THEIRALLY");
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to deal 1 damage to");
        AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "DEALDAMAGE,1,$currentPlayer", 1);
      }
      break;
    case "7661383869"://Darth Vader
      if(GetResolvedAbilityName($cardID) == "TIE Fighter" && GetClassState($currentPlayer, $CS_NumNonTokenVehicleAttacks) > 0) {
        CreateTieFighter($currentPlayer);
      }
      break;
    case "3132453342"://Captain Phasma
      if(GetResolvedAbilityName($cardID) == "Deal Damage" && GetClassState($currentPlayer, $CS_NumFirstOrderPlayed) > 0) {
        DealDamageAsync($otherPlayer, 1, "DAMAGE", "3132453342", sourcePlayer:$currentPlayer);
      }
      break;
    case "4531112134"://Kazuda Xiono
      if (GetResolvedAbilityName($cardID) == "Lose Abilities") {
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY");
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to lose abilities");
        AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("SHOWSELECTEDTARGET", $currentPlayer, "-", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "GETUNIQUEID", 1);
        AddDecisionQueue("ADDLIMITEDROUNDEFFECT", $currentPlayer, "4531112134,PLAY", 1);
        AddDecisionQueue("SWAPTURN", $currentPlayer, "-");
      }
      break;
    case "8174214418"://Turbolaser Salvo
      if(SearchCount(SearchAllies($currentPlayer, arena:"Space")) < 1) break;
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose an arena to blast. ");
      AddDecisionQueue("BUTTONINPUT", $currentPlayer, "Space,Ground");
      AddDecisionQueue("SETDQVAR", $currentPlayer, "0");
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY:arena=Space");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose an attacking unit");
      AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-");
      AddDecisionQueue("MZOP", $currentPlayer, "POWER");
      AddDecisionQueue("SETDQVAR", $currentPlayer, "1");
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "THEIRALLY:arena={0}");
      AddDecisionQueue("SPECIFICCARD", $currentPlayer, "TURBOLASERSALVO", 1);
      break;
    case "9595057518"://Special Forces TIE Fighter
      if($from != "PLAY") {
        $theirSpaceCount = SearchCount(SearchAllies($otherPlayer, arena:"Space"));
        $mySpaceCount = SearchCount(SearchAllies($currentPlayer, arena:"Space"));
        if($theirSpaceCount > $mySpaceCount) {
          $playAlly->Ready();
        }
      }
      break;
    case "6854247423"://Tantive IV
      if($from != "PLAY") {
        CreateXWing($currentPlayer);
      }
      break;
    case "3427170256"://Captain Phasma Unit
      if($from != "PLAY") {
        CaptainPhasmaUnit($currentPlayer, $playAlly->Index());
      }
      break;
    case "3885807284"://Fight Fire With Fire
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a friendly unit");
      AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("SETDQVAR", $currentPlayer, "0", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "GETARENA", 1);
      AddDecisionQueue("SETDQVAR", $currentPlayer, "1", 1);
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "THEIRALLY:arena={1}", 1);
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose an enemy unit in the same arena", 1);
      AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("SETDQVAR", $currentPlayer, "2", 1);
      AddDecisionQueue("PASSPARAMETER", $currentPlayer, "{0}", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "DEALDAMAGE,3," . $currentPlayer . ",0,1,0" , 1);
      AddDecisionQueue("PASSPARAMETER", $currentPlayer, "{2}", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "DEALDAMAGE,3," . $currentPlayer . ",0,1,0" , 1);
      break;
    case "0524529055"://Snap Wexley
      if($from != "PLAY" && $target == "-") AddCurrentTurnEffect("0524529055-P", $currentPlayer, from:$from);
      break;
    case "3567283316"://Radiant VII
      if($from != "PLAY") {
        IndirectDamage($cardID, $currentPlayer, 5, true, $playAlly->UniqueID());
      }
      break;
    case "0753794638"://Corvus
      if($from != "PLAY" && CountPilotUnitsAndPilotUpgrades($currentPlayer) > 0) {
        $options = "Move Pilot unit;Move Pilot upgrade;Pass";
        AddDecisionQueue("PASSPARAMETER", $currentPlayer, $uniqueId, 1);
        AddDecisionQueue("SETDQVAR", $currentPlayer, "0", 1);
        AddDecisionQueue("CHOOSEOPTION", $currentPlayer, "$cardID&$options", 1);
        AddDecisionQueue("SHOWOPTIONS", $currentPlayer, "$cardID&$options", 1);
        AddDecisionQueue("MODAL", $currentPlayer, "CORVUS", 1);
      }
      break;
    case "8993849612"://Eject
      Draw($currentPlayer);
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to eject a pilot from.");
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY:hasPilotOnly=1&THEIRALLY:hasPilotOnly=1");
      AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "GETUNIQUEID", 1);
      AddDecisionQueue("SETDQVAR", $currentPlayer, "0", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "GETUPGRADES", 1);
      AddDecisionQueue("FILTER", $currentPlayer, "LastResult-include-trait-Pilot", 1);
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a pilot to eject.", 1);
      AddDecisionQueue("CHOOSECARD", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "MOVEPILOTUPGRADE", 1);
      break;
    case "0097256640"://TIE Ambush Squadron
      if($from != "PLAY") CreateTieFighter($currentPlayer);
      break;
    case "9810057689"://No Glory, Only Results
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "THEIRALLY&MYALLY");
      AddDecisionQueue("MZFILTER", $currentPlayer, "leader=1");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to take control of");
      AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "GETUNIQUEID", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "TAKECONTROL", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "DESTROY,$currentPlayer", 1);
      break;
    case "2870117979"://Executor
      if($from != "PLAY") {
        CreateTieFighter($currentPlayer);
        CreateTieFighter($currentPlayer);
        CreateTieFighter($currentPlayer);
      }
      break;
    case "2711104544"://Guerilla Soldier
      if($from != "PLAY") {
        AddCurrentTurnEffect("2711104544", $currentPlayer, $from, $uniqueId);
        IndirectDamage($cardID, $currentPlayer, 3, true, $playAlly->UniqueID());
        AddDecisionQueue("REMOVECURRENTEFFECT", $currentPlayer, "2711104544", 1);
      }
      break;
    case "7138400365"://The Invisible Hand JTL
      if($from != "PLAY") InvisibleHandJTL($currentPlayer);
      break;
    case "6600603122"://Massassi Tactical Officer
      if(GetResolvedAbilityName($cardID, $from) == "Fighter Attack") {
        AddCurrentTurnEffect($cardID, $currentPlayer);
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY:trait=Fighter");
        AddDecisionQueue("MZFILTER", $currentPlayer, "status=1", 1);
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a Fighter to attack with", 1);
        AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "ATTACK", 1);
      }
      break;
    case "9921128444"://General Hux
      if(GetResolvedAbilityName($cardID, $from) == "Draw" && GetClassState($currentPlayer, $CS_NumFirstOrderPlayed) > 0) {
        Draw($currentPlayer);
      }
      break;
    case "3436482269"://Dogfight
      AttackWithMyUnitEvenIfExhaustedNoBases($currentPlayer);
      break;
    case "8757741946"://Poe Dameron (One Hell of a Pilot)
      if($from != "PLAY" && $target == "-") {
        CreateXWing($currentPlayer);
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Attach Poe to a Vehicle?");
        AddDecisionQueue("YESNO", $currentPlayer, "-", 1);
        AddDecisionQueue("NOPASS", $currentPlayer, "-", 1);
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY:trait=Vehicle", 1);
        AddDecisionQueue("MZFILTER", $currentPlayer, "hasPilot=1", 1);
        AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "GETUNIQUEID", 1);
        AddDecisionQueue("SETDQVAR", $currentPlayer, "0", 1);
        AddDecisionQueue("PASSPARAMETER", $currentPlayer, $uniqueId, 1);
        AddDecisionQueue("MZOP", $currentPlayer, "MOVEPILOTUNIT", 1);
      }
      break;
    case "0979322247"://Sidon Ithano
      if($from != "PLAY" && $playAlly->Exists()) {
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Attach Sidon to an enemy Vehicle?");
        AddDecisionQueue("YESNO", $currentPlayer, "-", 1);
        AddDecisionQueue("NOPASS", $currentPlayer, "-", 1);
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "THEIRALLY:trait=Vehicle", 1);
        AddDecisionQueue("MZFILTER", $currentPlayer, "hasPilot=1", 1);
        AddDecisionQueue("PASSREVERT", $currentPlayer, "-", 1);
        AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "GETUNIQUEID", 1);
        AddDecisionQueue("SETDQVAR", $currentPlayer, "0", 1);
        AddDecisionQueue("PASSPARAMETER", $currentPlayer, $uniqueId, 1);
        AddDecisionQueue("MZOP", $currentPlayer, "MOVEPILOTUNIT", 1);
      }
      break;
    case "3905028200"://Admiral Trench
      if(GetResolvedAbilityName($cardID, $from) == "Rummage" && SearchCount(SearchHand($currentPlayer, minCost:3)) > 0) {
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYHAND:minCost=3");
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a card costing 3 or more to discard");
        AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZDESTROY", $currentPlayer, "-", 1);
        AddDecisionQueue("DRAW", $currentPlayer, "-", 1);
      }
      break;
    case "4019449999"://Cham Syndulla
      if($from != "PLAY") {
        $myResourcesCount = NumResources($currentPlayer);
        $theirResourcesCount = NumResources($otherPlayer);
        if($myResourcesCount < $theirResourcesCount && count(GetDeck($currentPlayer)) > 0) {
          AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Put top deck into play as a resource?");
          AddDecisionQueue("YESNO", $currentPlayer, "if you want to add a resource from the top of your deck", 1);
          AddDecisionQueue("NOPASS", $currentPlayer, "-", 1);
          AddDecisionQueue("OP", $currentPlayer, "ADDTOPDECKASRESOURCE", 1);
        }
      }
      break;
    case "2614693321"://Salvage
      global $CS_AfterPlayedBy;
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYDISCARD:definedType=Unit;trait=Vehicle");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a card to play");
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("SETDQVAR", $currentPlayer, "0", 1);
      AddDecisionQueue("PASSPARAMETER", $currentPlayer, $cardID, 1);
      AddDecisionQueue("SETCLASSSTATE", $currentPlayer, $CS_AfterPlayedBy, 1);
      AddDecisionQueue("PASSPARAMETER", $currentPlayer, "{0}", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "PLAYCARD", 1);
      break;
    case "4203363893"://War Juggernaut
      if($from != "PLAY") {
        AddDecisionQueue("FINDINDICES", $currentPlayer, "ALLOURUNITSMULTI");
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose units to damage", 1);
        AddDecisionQueue("MULTICHOOSEOURUNITS", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "COMBINEMYANDTHEIRINDICIES", 1);
        AddDecisionQueue("MULTIDAMAGE", $currentPlayer, DealDamageBuilder(1, $currentPlayer, isUnitEffect:1, unitCardID:$cardID), 1);
      }
      break;
    case "6410144226"://Air Superiority
      $otherPlayer = $currentPlayer == 1 ? 2 : 1;
      if(SearchCount(SearchAllies($currentPlayer, arena:"Space")) > SearchCount(SearchAllies($otherPlayer, arena:"Space"))){
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "THEIRALLY:arena=Ground");
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to deal 4 damage to");
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "DEALDAMAGE,4,$currentPlayer", 1);
      }
      break;
    case "6196035152"://Nebula Ignition
      DestroyAllAllies(spareFilter:"upgraded");
      break;
    case "0391050270"://Jam Communications
      AddDecisionQueue("LOOKHAND", $currentPlayer, "-");
      AddDecisionQueue("REVEALHANDCARDS", $otherPlayer, "-");
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "THEIRHAND:definedType=Event");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose an event to discard");
      AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZDESTROY", $currentPlayer, "-", 1);
      break;
    case "7508489374"://Wing Guard Security Team
      if ($from != "PLAY") {
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY:trait=Fringe");
        AddDecisionQueue("OP", $currentPlayer, "MZTONORMALINDICES", 1);
        AddDecisionQueue("PREPENDLASTRESULT", $currentPlayer, "2-", 1);
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose up to 2 Fringe units to give a shield", 1);
        AddDecisionQueue("MULTICHOOSEUNIT", $currentPlayer, "<-", 1);
        AddDecisionQueue("SPECIFICCARD", $currentPlayer, "MULTIGIVESHIELD", 1);
      }
      break;
    case "5038195777"://Evasive Maneuver
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "THEIRALLY&MYALLY");
      AddDecisionQueue("MZFILTER", $currentPlayer, "status=1");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a card to exhaust");
      AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "REST", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "GETUNIQUEID", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "WRITECHOICE", 1);
      break;
    case "8382691367"://Dedicated Wingmen
      CreateXWing($currentPlayer);
      CreateXWing($currentPlayer);
      break;
    case "6413979593"://Punch It
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY:trait=Vehicle");
      AddDecisionQueue("MZFILTER", $currentPlayer, "status=1");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a vehicle to attack and give +2");
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("SETDQVAR", $currentPlayer, "0", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "GETUNIQUEID", 1);
      AddDecisionQueue("ADDLIMITEDCURRENTEFFECT", $currentPlayer, "6413979593,HAND", 1);
      AddDecisionQueue("PASSPARAMETER", $currentPlayer, "{0}", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "ATTACK", 1);
      break;
    case "9283378702"://Apology Accepted
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a friendly unit to defeat", 1);
      AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "DESTROY,$currentPlayer", 1);
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY&THEIRALLY");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to give 2 experience tokens to", 1);
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "ADDEXPERIENCE", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "ADDEXPERIENCE", 1);
      break;
    case "5012301077"://Dilapidated Ski Speeder
      if($from != "PLAY") $playAlly->DealDamage(3);
      break;
    case "7214707216"://Diversion
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY&THEIRALLY");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to give Sentinel for this phase");
      AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "WRITECHOICE", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "GETUNIQUEID", 1);
      AddDecisionQueue("ADDLIMITEDCURRENTEFFECT", $currentPlayer, "7214707216,HAND", 1);
      break;
      case "8905858173"://Focus Fire
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY&THEIRALLY");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to deal damage to");
      AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("SPECIFICCARD", $currentPlayer, "FOCUS_FIRE", 1);
      break;
    case "9347873117"://Veteran Fleet Officer
      if($from != "PLAY") CreateXWing($currentPlayer);
      break;
    case "3272995563"://In the Heat of Battle
      foreach ([1, 2] as $p) {
        $allies = &GetAllies($p);
        for ($i = 0; $i < count($allies); $i += AllyPieces()) {
          AddCurrentTurnEffect($cardID, $p, "PLAY", $allies[$i+5]);
        }
      }
      break;
    case "1355075014"://Attack Run
      AddCurrentTurnEffect($cardID, $currentPlayer);
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY:arena=Space");
      AddDecisionQueue("MZFILTER", $currentPlayer, "status=1");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to attack with");
      AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "ATTACK", 1);
      break;
    case "3658858659"://Cat and Mouse
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "THEIRALLY");
      AddDecisionQueue("MZFILTER", $currentPlayer, "status=1");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a card to exhaust");
      AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("SPECIFICCARD", $currentPlayer, "CAT_AND_MOUSE", 1);
      break;
    case "3782661648"://Out the Airlock
        DQDebuffUnit($currentPlayer, $otherPlayer, $cardID, 5, may:false);
      break;
    case "4159101997"://Crackshot V-Wing
      if($from != "PLAY" && SearchCount(SearchAllies($currentPlayer, trait:"Fighter")) <= 1) {
        $playAlly->DealDamage(1);
      }
      break;
    case "9595202461"://Coordinated Front
      //Ground unit
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY:arena=Ground&THEIRALLY:arena=Ground");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a ground unit to give +2/+2");
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "ADDHEALTH,2", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "GETUNIQUEID", 1);
      AddDecisionQueue("ADDLIMITEDCURRENTEFFECT", $currentPlayer, "9595202461,HAND", 1);
      //Space unit
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY:arena=Space&THEIRALLY:arena=Space");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a space unit to give +2/+2", 1);
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "ADDHEALTH,2", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "GETUNIQUEID", 1);
      AddDecisionQueue("ADDLIMITEDCURRENTEFFECT", $currentPlayer, "9595202461,HAND", 1);
      break;
    case "3660641793"://Echo Base Engineer
      if($from != "PLAY") {
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY:trait=Vehicle&THEIRALLY:trait=Vehicle");
        AddDecisionQueue("MZFILTER", $currentPlayer, "damaged=0");
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a damaged vehicle to give a shield token to");
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "ADDSHIELD", 1);
      }
      break;
    case "2948553808"://Fly Casual
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY:trait=Vehicle&THEIRALLY:trait=Vehicle");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to ready");
      AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "READY", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "GETUNIQUEID", 1);
      AddDecisionQueue("ADDLIMITEDCURRENTEFFECT", $currentPlayer, "2948553808,PLAY", 1);
      break;
    case "5841647666"://Scramble Fighters
      for($i=0;$i<8;++$i) {
        $allyUid = CreateTieFighter($currentPlayer);
        Ally::FromUniqueId($allyUid)->Ready();
        AddCurrentTurnEffect($cardID, $currentPlayer, "PLAY", $allyUid);
      }
      break;
    case "6515230001"://Pantoran Starship Thief
      if($from != "PLAY" && $playAlly->Exists() && NumResourcesAvailable($currentPlayer) >= 3) {
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Pay 3 resources to take control of a Fighter or Transport?");
        AddDecisionQueue("YESNO", $currentPlayer, "-", 1);
        AddDecisionQueue("NOPASS", $currentPlayer, "-", 1);
        AddDecisionQueue("PAYRESOURCES", $currentPlayer, "3", 1);
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY:trait=Fighter&MYALLY:trait=Transport&THEIRALLY:trait=Fighter&THEIRALLY:trait=Transport", 1);
        AddDecisionQueue("MZFILTER", $currentPlayer, "hasPilot=1", 1);
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a Fighter or Transport to take control of", 1);
        AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "GETUNIQUEID", 1);
        AddDecisionQueue("SETDQVAR", $currentPlayer, "0", 1);
        AddDecisionQueue("PASSPARAMETER", $currentPlayer, $uniqueId, 1);
        AddDecisionQueue("MZOP", $currentPlayer, "MOVEPILOTUNIT", 1);
        AddDecisionQueue("PASSPARAMETER", $currentPlayer, "{0}", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "TAKECONTROL", 1);
      }
      break;
    case "6757031085"://Kimogila Heavy Fighter
      if($from != "PLAY") {
        IndirectDamage($cardID, $currentPlayer, 3, fromUnitEffect:true, uniqueID:$playAlly->UniqueID());
        AddDecisionQueue("SPECIFICCARD", $currentPlayer, "KIMOGILAHEAVYFIGHTER", 1);
      }
      break;
    case "2384695376"://Heartless Tactics
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY&THEIRALLY");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to exhaust and debuff");
      AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "REST", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "GETUNIQUEID", 1);
      AddDecisionQueue("ADDLIMITEDCURRENTEFFECT", $currentPlayer, "2384695376,HAND", 1);
      AddDecisionQueue("SPECIFICCARD", $currentPlayer, "HEARTLESSTACTICS", 1);
      break;
    case "2454329668"://System Shock
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY:hasUpgradeOnly=true&THEIRALLY:hasUpgradeOnly=true");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to defeat an upgrade from");
      AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "GETUNIQUEID", 1);
      AddDecisionQueue("SPECIFICCARD", $currentPlayer, "SYSTEMSHOCK", 1);
      break;
    case "9184947464"://There Is No Escape
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY&THEIRALLY");
      AddDecisionQueue("PREPENDLASTRESULT", $currentPlayer, "3-", 1);
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose up to 3 units to lose abilities this round.", 1);
      AddDecisionQueue("MULTICHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("SPECIFICCARD", $currentPlayer, "THEREISNOESCAPE", 1);
      break;
    case "8544209291"://U-Wing Lander
      if($from != "PLAY") {
        $ally = Ally::FromUniqueId($uniqueId);
        for($i=0; $i<3; ++$i) {
          $ally->Attach("2007868442");//Experience token
        }
      }
      break;
    case "5306772000"://Phantom II
      if(GetResolvedAbilityName($cardID, "PLAY") == "Dock") {
        $ghostIndices = explode(",", SearchAllies($currentPlayer, cardTitle:"The Ghost"));
        if($ghostIndices[0] == "") break;
        $ghostUnits = implode(",",array_map(function ($x) { return "MYALLY-" . $x; }, $ghostIndices));
        AddDecisionQueue("PASSPARAMETER", $currentPlayer, $ghostUnits);
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose which of The Ghost you would like to attach to.", 1);
        AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "GETUNIQUEID", 1);
        AddDecisionQueue("SETDQVAR", $currentPlayer, "0", 1);
        AddDecisionQueue("PASSPARAMETER", $currentPlayer, $uniqueId, 1);
        AddDecisionQueue("MZOP", $currentPlayer, "MOVEPILOTUNIT", 1);
      }
      break;
    //Legends of the Force
    //LOF leaders
    case "0024560758"://Darth Maul Leader
      $abilityName = GetResolvedAbilityName($cardID, $from);
      if($abilityName == "Deal Damage") {
        if(!HasTheForce($currentPlayer)) {
          WriteLog(NoForceSpan());
          RevertGamestate();
        } else {
          UseTheForce($currentPlayer);
          DQMultiUnitSelect($currentPlayer, 2, "MYALLY&THEIRALLY", "to deal 1 damage to", cantSkip:true);
          AddDecisionQueue("MZOP", $currentPlayer, DealMultiDamageBuilder($currentPlayer), 1);
        }
      }
      break;
    case "2580909557"://Qui-Gon Jinn Leader
      $abilityName = GetResolvedAbilityName($cardID, $from);
      if($abilityName == "Bounce/Play") {
        if(!HasTheForce($currentPlayer)) {
          WriteLog(NoForceSpan());
          RevertGamestate();
        } else {
          UseTheForce($currentPlayer);
          QuiGonJinnLOF($currentPlayer, false);
        }
      }
      break;
    case "2693401411"://Obi-Wan Kenobi Leader
      $abilityName = GetResolvedAbilityName($cardID, $from);
      if($abilityName == "Experience") {
        if(!HasTheForce($currentPlayer)) {
          WriteLog(NoForceSpan());
          RevertGamestate();
        } else {
          UseTheForce($currentPlayer);
          ObiWanKenobiLOF($currentPlayer, false);
        }
      }
      break;
    case "3357344238"://Third Sister Leader
      global $CS_AfterPlayedBy;
      $abilityName = GetResolvedAbilityName($cardID, $from);
      if($abilityName == "Play") {
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYHAND:definedType=Unit");
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to play");
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("SETDQVAR", $currentPlayer, "0", 1);
        AddDecisionQueue("PASSPARAMETER", $currentPlayer, $cardID, 1);
        AddDecisionQueue("SETCLASSSTATE", $currentPlayer, $CS_AfterPlayedBy, 1);
        AddDecisionQueue("PASSPARAMETER", $currentPlayer, "{0}", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "PLAYCARD", 1);
      }
      break;
    case "8304104587"://Kanan Jarrus Leader
      $abilityName = GetResolvedAbilityName($cardID, $from);
      if($abilityName == "Shield") {
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY:trait=Creature&MYALLY:trait=Spectre&THEIRALLY:trait=Creature&THEIRALLY:trait=Spectre");
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to give a shield token to");
        AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "ADDSHIELD", 1);
      }
      break;
    case "2520636620"://Mother Talzin Leader
      $abilityName = GetResolvedAbilityName($cardID, $from);
      if($abilityName == "Debuff") {
        if(!HasTheForce($currentPlayer)) {
          WriteLog(NoForceSpan());
          RevertGamestate();
        } else {
          UseTheForce($currentPlayer);
          MotherTalzinLOF($currentPlayer, false);
        }
      }
      break;
    case "3822427538"://Kit Fisto Leader
      global $CS_NumJediAttacks;
      $abilityName = GetResolvedAbilityName($cardID, $from);
      if($abilityName == "Deal Damage" && GetClassState($currentPlayer, $CS_NumJediAttacks) > 0) {
        DQPingUnit($currentPlayer, 2, isUnitEffect:false, may:false);
      }
      break;
    case "5917432593"://Grand Inquisitor Leader
      $abilityName = GetResolvedAbilityName($cardID, $from);
      if($abilityName == "Attack") {
        if(!HasTheForce($currentPlayer)) {
          WriteLog(NoForceSpan());
          RevertGamestate();
        } else {
          UseTheForce($currentPlayer);
          AddCurrentTurnEffect($cardID, $otherPlayer);
          AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY");
          AddDecisionQueue("MZFILTER", $currentPlayer, "status=1");
          AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to attack with");
          AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
          AddDecisionQueue("MZOP", $currentPlayer, "ATTACK", 1);
        }
      }
      break;
    case "0092239541"://Avar Kriss Leader
      $abilityName = GetResolvedAbilityName($cardID, $from);
      if($abilityName == "Meditate") TheForceIsWithYou($currentPlayer);
      break;
    case "5045607736"://Morgan Elsbeth Leader
      $abilityName = GetResolvedAbilityName($cardID, $from);
      if($abilityName == "Play") {
        $keywordsInPlay = GetAllAlliesKeywords($currentPlayer);
        $mzSearch = "";
        foreach ($keywordsInPlay as $keyword) {
          $mzSearch .= "MYHAND:keyword=$keyword&";
        }
        if($mzSearch != "") {
          $mzSearch = rtrim($mzSearch, "&");
          AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, $mzSearch);
          AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a card to play");
          AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
          AddDecisionQueue("SETDQVAR", $currentPlayer, "0", 1);
          AddDecisionQueue("PASSPARAMETER", $currentPlayer, $cardID, 1);
          AddDecisionQueue("ADDCURRENTEFFECT", $currentPlayer, $cardID, 1);
          AddDecisionQueue("PASSPARAMETER", $currentPlayer, "{0}", 1);
          AddDecisionQueue("MZOP", $currentPlayer, "PLAYCARD", 1);
        }
      }
      break;
    case "7077983867"://Ahsoka Tano Leader
      $abilityName = GetResolvedAbilityName($cardID, $from);
      if($abilityName == "Sentinel") {
        if(!HasTheForce($currentPlayer)) {
          WriteLog(NoForceSpan());
          RevertGamestate();
        } else {
          UseTheForce($currentPlayer);
          DQChooseAUnitToGiveEffect($currentPlayer, $cardID, $from, mzSearch:"MYALLY", context:"a unit to give Sentinel to");
        }
      }
      break;
    case "9919167831"://Supreme Leader Snoke Leader
      $abilityName = GetResolvedAbilityName($cardID, $from);
      if($abilityName == "Experience" && SearchCount(SearchAllies($currentPlayer, aspect: "Villainy")) > 0) {
        $highestPower = GetHighestPowerFromFriendlyUnits($currentPlayer, "Villainy");
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY:aspect=Villainy;minAttack=$highestPower;maxAttack=$highestPower");
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a Villainy unit to give experience to");
        AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "ADDEXPERIENCE", 1);
      }
      break;
    case "6677799440"://Cal Kestis Leader
      $abilityName = GetResolvedAbilityName($cardID, $from);
      if($abilityName == "Exhaust") {
        if(!HasTheForce($currentPlayer)) {
          WriteLog(NoForceSpan());
          RevertGamestate();
        } else {
          UseTheForce($currentPlayer);
          AddDecisionQueue("MULTIZONEINDICES", $otherPlayer, "MYALLY");
          AddDecisionQueue("MZFILTER", $otherPlayer, "status=1");
          AddDecisionQueue("SETDQCONTEXT", $otherPlayer, "Choose a unit to exhaust");
          AddDecisionQueue("CHOOSEMULTIZONE", $otherPlayer, "<-", 1);
          AddDecisionQueue("MZOP", $otherPlayer, "REST,$currentPlayer", 1);
        }
      }
      break;
    case "1184397926"://Barriss Offee Leader
    case "20f7c21d8b"://Barriss Offee Leader unit
      $abilityName = GetResolvedAbilityName($cardID, $from);
      if($abilityName == "Play") {
        if(!HasTheForce($currentPlayer)) {
          WriteLog(NoForceSpan());
          RevertGamestate();
        } else {
          UseTheForce($currentPlayer);
          AddCurrentTurnEffect($cardID, $currentPlayer, $from);
          AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYHAND:definedType=Event");
          AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose an event to play");
          AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
          AddDecisionQueue("SETDQVAR", $currentPlayer, "0", 1);
          AddDecisionQueue("MZOP", $currentPlayer, "PLAYCARD", 1);
        }
      }
      break;
    case "8536024453"://Anakin Skywalker Leader
    case "7d9f8bcb9b"://Anakin Skywalker Leader unit
      $abilityName = GetResolvedAbilityName($cardID, $from);
      if($abilityName == "Play") {
        if(!HasTheForce($currentPlayer)) {
          WriteLog(NoForceSpan());
          RevertGamestate();
        } else {
          UseTheForce($currentPlayer);
          AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYHAND:aspect=Villainy");
          AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a non-unit card to play");
          AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
          AddDecisionQueue("SETDQVAR", $currentPlayer, "0", 1);
          AddDecisionQueue("ADDCURRENTEFFECT", $currentPlayer, $cardID, 1);
          AddDecisionQueue("PASSPARAMETER", $currentPlayer, "{0}", 1);
          AddDecisionQueue("MZOP", $currentPlayer, "GETCARDID", 1);
          AddDecisionQueue("SPECIFICCARD", $currentPlayer, "ANAKINSKYWALKER_LOF", 1);
        }
      }
      break;
    case "5174764156"://Kylo Ren Leader
      $abilityName = GetResolvedAbilityName($cardID, $from);
      if($abilityName == "Rummage") {
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYHAND");
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a card to discard");
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZDESTROY", $currentPlayer, "-", 1);
        AddDecisionQueue("PASSIFNOTDEFINEDTYPE", $currentPlayer, "Upgrade", 1);
        AddDecisionQueue("DRAW", $currentPlayer, "-", 1);
      }
      break;
    case "2762251208"://Rey Leader
      $abilityName = GetResolvedAbilityName($cardID, $from);
      if($abilityName == "Deal Damage") {
        global $CS_NumForcePlayedNonUnit;
        if(GetClassState($currentPlayer, $CS_NumForcePlayedNonUnit) > 0) {
          DQPingUnit($currentPlayer,1, isUnitEffect:false, may:false);
        }
      }
      break;
    //end LOF leaders
    case "5083905745"://Drain Essence
      TheForceIsWithYou($currentPlayer);
      DQPingUnit($currentPlayer, 2, isUnitEffect:false, may:false);
      break;
    case "6797297267"://Darth Sidious
      if($from != "PLAY") {
        if(HasTheForce($currentPlayer)) {
          DQAskToUseTheForce($currentPlayer);
          AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY:maxHealth=3&THEIRALLY:maxHealth=3", 1);
          AddDecisionQueue("MZFILTER", $currentPlayer, "trait=Sith", 1);
          AddDecisionQueue("MZDESTROY", $currentPlayer, "-", 1);
        }
      }
      break;
    case "1636013021"://Savage Opress
      if($from != "PLAY") {
        SavageOpressLOF($currentPlayer);
      }
      break;
    case "1545515980"://Stinger Mantis
      //When Played
      if($from != "PLAY") {
        //You may deal 2 damage to an exhausted unit.
        DQPingUnit($currentPlayer, 2, isUnitEffect:true, may:true, mzFilter:"status=0", context:"an exhausted unit", unitCardID:$cardID);
      }
      break;
    case "0102737248"://Refugee of the Path
      //When Played
      if($from != "PLAY") {
        //You may give a Shield token to a unit with Sentinel.
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY&THEIRALLY");
        AddDecisionQueue("MZFILTER", $currentPlayer, "hasSentinel=0");
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "ADDSHIELD", 1);
      }
      break;
    case "2167393423"://Darth Maul's Lightsaber
      if(CardTitle(GetMZCard($currentPlayer, $target)) == "Darth Maul") {
        $ally = new Ally($target, $currentPlayer);
        AddCurrentTurnEffect($cardID, $currentPlayer, "HAND", $ally->UniqueID());
        AddDecisionQueue("YESNO", $currentPlayer, "if you want to attack with Darth Maul", 1);
        AddDecisionQueue("NOPASS", $currentPlayer, "-", 1);
        AddDecisionQueue("PASSPARAMETER", $currentPlayer, 1, 1);
        AddDecisionQueue("SETCOMBATCHAINSTATE", $currentPlayer, $CCS_CantAttackBase, 1);
        AddDecisionQueue("PASSPARAMETER", $currentPlayer, $ally->MZIndex(), 1);
        AddDecisionQueue("MZOP", $currentPlayer, "ATTACK", 1);
      }
      break;
    case "4389144613"://Grogu
      $abilityName = GetResolvedAbilityName($cardID, $from);
      $theirUnitCount = SearchCount(SearchAllies($otherPlayer));
      $ourUnitCount = SearchCount(SearchAllies($currentPlayer));
      if ($theirUnitCount == 0 && $ourUnitCount == 1) {
        break;
      }
      if($abilityName == "Move Damage") {
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY:damagedOnly=1&THEIRALLY:damagedOnly=1");
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to heal up to 2 damage", 1);
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("PREPENDLASTRESULT", $currentPlayer, "2-", 1);
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Heal up to 2 damage", 1);
        AddDecisionQueue("PARTIALMULTIHEALMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "MULTIHEAL", 1);
        AddDecisionQueue("PREPENDLASTRESULT", $currentPlayer, $uniqueId . "-", 1);
        AddDecisionQueue("SPECIFICCARD", $currentPlayer, "DEALRESTOREDAMAGE", 1);
      }
      break;
    case "8569501777"://As I Have Foreseen
      AddDecisionQueue("FINDINDICES", $currentPlayer, "TOPDECK");
      AddDecisionQueue("DECKCARDS", $currentPlayer, "<-", 1);
      AddDecisionQueue("SETDQVAR", $currentPlayer, "0", 1);
      if(HasTheForce($currentPlayer)) {
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose if you want to use the Force to play <0>", 1);
        AddDecisionQueue("YESNO", $currentPlayer, "-", 1);
        AddDecisionQueue("NOPASS", $currentPlayer, "-", 1);
        AddDecisionQueue("USETHEFORCE", $currentPlayer, "-", 1);
        AddDecisionQueue("ADDCURRENTEFFECT", $currentPlayer, $cardID, 1);
        AddDecisionQueue("PASSPARAMETER", $currentPlayer, "MYDECK-0", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "PLAYCARD", 1);
      } else {
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "The top card of your deck is <0>");
        AddDecisionQueue("OK", $currentPlayer, "-");
      }
      break;
    case "2285555274"://Darth Malak
      if($from != "PLAY") {
        if(HasLeaderUnitWithTraitInPlay($currentPlayer, "Sith"))
          Ally::FromUniqueId($uniqueId)->Ready();
      }
      break;
    case "3853063436"://Cure Wounds
      if(HasTheForce($currentPlayer)) {
        UseTheForce($currentPlayer);
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY:damagedOnly=true&THEIRALLY:damagedOnly=true");
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to heal 6 damage from");
        AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "RESTORE,6", 1);
      }
      break;
    case "4092125792"://Death Field
      if(HasUnitWithTraitInPlay($currentPlayer, "Force")) Draw($currentPlayer);
      $theirAllies = &GetAllies($otherPlayer);
      for($i=count($theirAllies)-AllyPieces(); $i>=0; $i-=AllyPieces())
      {
        $ally = new Ally("MYALLY-" . $i, $otherPlayer);
        if(!TraitContains($theirAllies[$i], "Vehicle", $currentPlayer)) $ally->DealDamage(2, enemyDamage:true);
      }
      break;
    case "5737712611"://Jedi Knight
      //When Played: if you have the initiative,
      if($from != "PLAY" && $initiativePlayer == $currentPlayer) {
        //Deal 2 damage to an enemy ground unit.
        DQPingUnit($currentPlayer, 2, isUnitEffect:true, may:false, mzSearch:"THEIRALLY:arena=Ground", context:"an enemy ground unit", unitCardID:$cardID);
      }
      break;
    case "7691597101"://Liberated By Darkness
      if(HasTheForce($currentPlayer)) {
        UseTheForce($currentPlayer);
        DQTakeControlOfANonLeaderUnit($currentPlayer);
        AddDecisionQueue("ADDLIMITEDROUNDEFFECT", $currentPlayer, "7691597101,PLAY", 1);
      }
      break;
    case "5482818255"://Jedi Consular
      $abilityName = GetResolvedAbilityName($cardID, $from);
      if($abilityName == "Play Unit") {
        if(!HasTheForce($currentPlayer)) {
          WriteLog(NoForceSpan());
          RevertGamestate();
        } else {
          UseTheForce($currentPlayer);
          AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a card to play");
          AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYHAND:definedType=Unit");
          AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
          AddDecisionQueue("ADDCURRENTEFFECT", $currentPlayer, $cardID, 1);
          AddDecisionQueue("MZOP", $currentPlayer, "PLAYCARD", 1);
        }
      }
      break;
    case "4236013558"://Anakin Adult LOF
      if($from != "PLAY") {
        if(SearchCount(SearchDiscard($currentPlayer, aspect:"Villainy")) > 0) {
          DQDebuffUnit($currentPlayer, $otherPlayer, $cardID, 3, context: "a unit (for having a Villainy card in your discard pile)", may:true);
        }
        if(SearchCount(SearchDiscard($currentPlayer, aspect:"Heroism")) > 0) {
          DQDebuffUnit($currentPlayer, $otherPlayer, $cardID, 3, context: "a unit (for having a Heroism card in your discard pile)", may:true);
        }
      }
      break;
    case "4974236883"://Curious Flock
      if($from != "PLAY") {
        $resourcesAvailable = NumResourcesAvailable($currentPlayer);
        $porgsAvailable = min($resourcesAvailable, 6);
        $indices = "";
        for($i=0; $i<=$porgsAvailable; ++$i) {
          if($i > 0) $indices .= ",";
          $indices .= $i;
        }
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose how many extra Porgs are in this flock:");
        AddDecisionQueue("BUTTONINPUTNOPASS", $currentPlayer, $indices, 1);
        AddDecisionQueue("SPECIFICCARD", $currentPlayer, "CURIOUS_FLOCK,$uniqueId", 1);
      }
      break;
    case "1146162009"://Mind Trick
      if($from != "PLAY") {
        $totalUnits = intval(SearchCount(SearchAllies($currentPlayer)) + intval(SearchCount(SearchAllies($otherPlayer))));
        DQMultiUnitSelect($currentPlayer, $totalUnits, "MYALLY&THEIRALLY", "to Exhaust");
        AddDecisionQueue("SPECIFICCARD", $currentPlayer, "MIND_TRICK", 1);
      }
      break;
    case "6736342819"://Ataru Onslaught
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY:maxAttack=4;trait=Force&THEIRALLY:maxAttack=4;trait=Force");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to ready");
      AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "READY", 1);
      break;
    case "2410965424"://Talzin's Assassin
      if($from != "PLAY") {
        if(HasTheForce($currentPlayer)) {
          DQAskToUseTheForce($currentPlayer);
          AddDecisionQueue("NOPASS", $currentPlayer, "-", 1);
          DQDebuffUnit($currentPlayer, $otherPlayer, $cardID, 3, subsequent:true);
        }
      }
      break;
    case "8032269906"://Soresu Stance
      global $CS_AfterPlayedBy;
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYHAND:definedType=Unit;trait=Force");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a Force unit to play");
      AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("SETDQVAR", $currentPlayer, "0", 1);
      AddDecisionQueue("PASSPARAMETER", $currentPlayer, $cardID, 1);
      AddDecisionQueue("SETCLASSSTATE", $currentPlayer, $CS_AfterPlayedBy, 1);
      AddDecisionQueue("PASSPARAMETER", $currentPlayer, "{0}", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "PLAYCARD", 1);
      break;
    case "0564229530"://Old Daka
      if($from != "PLAY") {
        $cardTitle = CardTitle($cardID);
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY:definedType=Unit;trait=Night");
        AddDecisionQueue("MZFILTER", $currentPlayer, "cardTitle=$cardTitle");
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to sacrifice");
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("SPECIFICCARD", $currentPlayer, "OLDDAKA_LOF", 1);
      }
      break;
    case "0612354523"://Youngling Padawan
      if($from != "PLAY") TheForceIsWithYou($currentPlayer);
      break;
    case "1553569317"://Kelleran Beq
      if($from != "PLAY") {
        AddDecisionQueue("SEARCHDECKTOPX", $currentPlayer, "7;1;include-definedType-Unit");
        AddDecisionQueue("SETDQVAR", $currentPlayer, "0", 1);
        AddDecisionQueue("ADDCURRENTEFFECT", $currentPlayer, $cardID, 1);
        AddDecisionQueue("PASSPARAMETER", $currentPlayer, "{0}", 1);
        AddDecisionQueue("OP", $currentPlayer, "PLAYCARD,DECK", 1);
      }
      break;
    case "3445044882"://Qui-Gon Jinn's Lightsaber
      if(CardTitle(GetMZCard($currentPlayer, $target)) == "Qui-Gon Jinn") {
        $totalUnits = intval(SearchCount(SearchAllies($currentPlayer)) + intval(SearchCount(SearchAllies($otherPlayer))));
        DQMultiUnitSelect($currentPlayer, $totalUnits, "MYALLY&THEIRALLY", "to exhaust (combined 6 power or less)");
        AddDecisionQueue("SPECIFICCARD", $currentPlayer, "QGJSABER_LOF", 1);
      }
      break;
    case "8834515285"://Maz Kanata
      if($from != "PLAY") {
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY:trait=Force");
        AddDecisionQueue("MZFILTER", $currentPlayer, "status=1");
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a Force unit to attack with");
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("SPECIFICCARD", $currentPlayer, "MAZKANATA_LOF", 1);
      }
      break;
    case "6801641285"://Luminous Beings
      AddDecisionQueue("FINDINDICES", $currentPlayer, "GYUNITSTRAIT,Force");
      AddDecisionQueue("PREPENDLASTRESULT", $currentPlayer, "3-");
      AddDecisionQueue("MULTICHOOSEDISCARD", $currentPlayer, "<-");
      AddDecisionQueue("SPECIFICCARD", $currentPlayer, "LUMINOUSBEINGS", 1);
      break;
    case "7012130030"://Paladin Training Corvette
      if($from != "PLAY") {
        DQMultiUnitSelect($currentPlayer, 3, "MYALLY:trait=Force", "to give an experience to");
        AddDecisionQueue("MZOP", $currentPlayer, GiveExperienceBuilder($currentPlayer, isUnitEffect:1), 1);
      }
      break;
    case "7074896971"://J-Type Nubian Starship
      if($from != "PLAY") Draw($currentPlayer);
      break;
    case "5390030381"://Infused Brawler
      if($from != "PLAY") {
        if(HasTheForce($currentPlayer)) {
          DQAskToUseTheForce($currentPlayer);
          AddDecisionQueue("NOPASS", $currentPlayer, "-");
          AddDecisionQueue("PASSPARAMETER", $currentPlayer, "MYALLY-" . $playAlly->Index(), 1);
          AddDecisionQueue("SPECIFICCARD", $currentPlayer, "INFUSEDBRAWLER", 1);
        }
      }
      break;
    case "9021149512"://The Will of the Force
      DQWaylay($currentPlayer);
      if(HasTheForce($currentPlayer)) {
        AddDecisionQueue("SETDQVAR", $currentPlayer, "0", 1);
        DQAskToUseTheForce($currentPlayer);
        AddDecisionQueue("SPECIFICCARD", $currentPlayer, "WILLOFTHEFORCE", 1);
      }
      break;
    case "9069308523"://Impossible Escape
        if(HasTheForce($currentPlayer)) {
          $options = "Exhaust a friendly unit;Use the Force";
          AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose one");
          AddDecisionQueue("CHOOSEOPTION", $currentPlayer, "$cardID&$options");
          AddDecisionQueue("SHOWOPTIONS", $currentPlayer, "$cardID&$options");
          AddDecisionQueue("MODAL", $currentPlayer, "9069308523");
        } else {
          AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY");
          AddDecisionQueue("MZFILTER", $currentPlayer, "status=1");
          AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a friendly unit to exhaust");
          AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
          AddDecisionQueue("MZOP", $currentPlayer, "REST", 1);
          AddDecisionQueue("DRAW", $currentPlayer, "-", 1);
          AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "THEIRALLY", 1);
          AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose an enemy unit to exhaust");
          AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
          AddDecisionQueue("MZOP", $currentPlayer, "REST", 1);
        }
      break;
    case "0033766648"://Shatterpoint
      $options = "Defeat 3 or less HP;Use the Force to defeat";
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose one");
      AddDecisionQueue("CHOOSEOPTION", $currentPlayer, "$cardID&$options");
      AddDecisionQueue("SHOWOPTIONS", $currentPlayer, "$cardID&$options");
      AddDecisionQueue("MODAL", $currentPlayer, "0033766648");
      break;
    case "7078597376"://Directed by the Force
      TheForceIsWithYou($currentPlayer);
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a card to play");
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYHAND:definedType=Unit");
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("ADDCURRENTEFFECT", $currentPlayer, $cardID, 1);
      AddDecisionQueue("MZOP", $currentPlayer, "PLAYCARD", 1);
      break;
    case "1028870559"://Kit Fisto's Aethersprite
      if($from != "PLAY") {
        DefeatUpgrade($currentPlayer, may: true);
        AddDecisionQueue("SPECIFICCARD", $currentPlayer, "KITFISTOAETHERSPRITE", 1);
      }
      break;
    case "7981459508"://Shien Flurry
      global $CS_AfterPlayedBy;
      SetClassState($currentPlayer, $CS_AfterPlayedBy, $cardID);
      AddCurrentTurnEffect($cardID, $currentPlayer, "PLAY");
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYHAND:definedType=Unit;trait=Force");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a Force unit to play");
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "PLAYCARD", 1);
      break;
    case "5960134941"://Niman Strike
      AttackWithMyUnitEvenIfExhaustedNoBases($currentPlayer, "Force", "5960134941");
      break;
    case "9434212852"://Mystic Monastery
      TheForceIsWithYou($currentPlayer);
      break;
    case "2699176260"://Tomb of Eilram
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY");
      AddDecisionQueue("MZFILTER", $currentPlayer, "status=1");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to exhaust");
      AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "REST", 1);
      AddDecisionQueue("THEFORCEISWITHYOU", $currentPlayer, "-", 1);
      break;
    case "4218264341"://Crushing Blow
      MZChooseAndDestroy($currentPlayer, "MYALLY:maxCost=2&THEIRALLY:maxCost=2", filter:"leader=1");
      break;
    case "3595375406"://Purge Trooper
        DQPingUnit($currentPlayer, 2, isUnitEffect:true, may:true, mzSearch:"MYALLY:trait=Force&THEIRALLY:trait=Force", context:"a Force unit", unitCardID:$cardID);
      break;
    case "9702812601"://Nameless Terror
      if($from != "PLAY") {
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "THEIRALLY:trait=Force&MYALLY:trait=Force");
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a card to exhaust");
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "REST", 1);
      }
      break;
    case "2940037100"://Vernestra Rwoh
      if($from != "PLAY") {
        if(HasTheForce($currentPlayer)) {
          DQAskToUseTheForce($currentPlayer);
          AddDecisionQueue("PASSPARAMETER", $currentPlayer, $uniqueId, 1);
          AddDecisionQueue("MZOP", $currentPlayer, "READY", 1);
        }
      }
      break;
    case "6001143439"://Sorcerous Blast
      if(HasTheForce($currentPlayer)) {
        UseTheForce($currentPlayer);
        DQPingUnit($currentPlayer, 3, isUnitEffect:false, may:false);
      }
      break;
    case "7012013186"://Priestesses of the Force
      if(HasTheForce($currentPlayer)) {
        DQAskToUseTheForce($currentPlayer);
        DQMultiUnitSelect($currentPlayer, 5, "MYALLY&THEIRALLY", "to give a shield token to");
        AddDecisionQueue("MZOP", $currentPlayer, "MULTIADDSHIELD", 1);
      }
      break;
    case "9757688123"://Mace Windu
      if($from != "PLAY" && HasTheForce($currentPlayer)) {
        DQAskToUseTheForce($currentPlayer);
        DQPingUnit($currentPlayer, 4, isUnitEffect:true, may:false, subsequent:true);
      }
      break;
    case "2277278592"://Darth Vader
      if($from != "PLAY") {
        // Give a shield to a friendly unit
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY");
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a friendly unit to give a shield to");
        AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "ADDSHIELD", 1);
        // Give a shield to an enemy unit
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "THEIRALLY", 1);
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose an enemy unit to give a shield to", 1);
        AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "ADDSHIELD", 1);
      }
      break;
    case "4729355863"://Baylan Skoll
      if($from != "PLAY" && HasTheForce($currentPlayer)) {
        DQAskToUseTheForce($currentPlayer);
        AddDecisionQueue("NOPASS", $currentPlayer, "-", 1);
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY:maxCost=4&THEIRALLY:maxCost=4", 1);
        AddDecisionQueue("MZFILTER", $currentPlayer, "leader=1");
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a non-leader unit to bounce and replay");
        AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("SETDQVAR", $currentPlayer, "0", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "GETCARDID", 1);
        AddDecisionQueue("SETDQVAR", $currentPlayer, "1", 1);
        AddDecisionQueue("PASSPARAMETER", $currentPlayer, "{0}", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "BOUNCE", 1);
        AddDecisionQueue("SPECIFICCARD", $currentPlayer, "BAYLANSKOLL", 1);
      }
      break;
    case "7323186775"://Itinerant Warrior
      if($from != "PLAY") {
        if(HasTheForce($currentPlayer)) {
          DQAskToUseTheForce($currentPlayer);
          AddDecisionQueue("PASSPARAMETER", $currentPlayer, "MYCHAR-0,THEIRCHAR-0", 1);
          AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a base to heal", 1);
          AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
          AddDecisionQueue("MZOP", $currentPlayer, "RESTORE,3", 1);
        }
      }
      break;
    case "0531276830"://Ki-Adi Mundi
      //When Played:
      if($from != "PLAY") {
        //You may use the force (lose your Force token).
        if(HasTheForce($currentPlayer)) {
          DQAskToUseTheForce($currentPlayer);
          //If you do, draw 2 cards.
          AddDecisionQueue("DRAW", $currentPlayer, "-", 1);
          AddDecisionQueue("DRAW", $currentPlayer, "-", 1);
        }
      }
      break;
    case "3052907071"://Dooku
      //When Played:
      if($from != "PLAY") {
        $hiddenUnits = SearchAllies($currentPlayer, keyword:"Hidden");
        if(SearchCount($hiddenUnits) > 0) {
          $hiddenAllies = explode(",", $hiddenUnits);
          for($i=0; $i<count($hiddenAllies); ++$i) {
            $ally = new Ally("MYALLY-" . $hiddenAllies[$i], $currentPlayer);
            if($ally->UniqueID() != $uniqueId)
              $ally->AddEffect($cardID, $from);
          }
        }
      }
      break;
    case "1723823172"://Protect the Pod
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY");
      AddDecisionQueue("MZFILTER", $currentPlayer, "trait=Vehicle");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to deal damage equal to its remaining HP");
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "GETHEALTH", 1);
      AddDecisionQueue("PREPENDLASTRESULT", $currentPlayer, "DEALDAMAGE,", 1);
      AddDecisionQueue("SETDQVAR", $currentPlayer, "0", 1);
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "THEIRALLY");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to damage");
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "{0},$currentPlayer,1", 1);
      break;
    case "0721742014"://Lightsaber Throw
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYHAND:trait=Lightsaber");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a Lightsaber card to discard");
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZDISCARD", $currentPlayer, "HAND," . $currentPlayer, 1);
      AddDecisionQueue("MZREMOVE", $currentPlayer, "-", 1);
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "THEIRALLY:arena=Ground", 1);
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a ground unit to deal 4 damage to", 1);
      AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "DEALDAMAGE,4,$currentPlayer", 1);
      AddDecisionQueue("DRAW", $currentPlayer, "-", 1);
      break;
    case "3591040205"://Pounce
      DQAttackWithEffect($currentPlayer, $cardID, $from, mzSearch:"MYALLY:trait=Creature", context:"Choose a Creature unit to attack with");
      break;
    case "2404240951"://Grappling Guardian
      //When Played:
      if($from != "PLAY") {
        //You may defeat a space unit with 6 or less remaining HP.
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY:maxHealth=6;arena=Space&THEIRALLY:maxHealth=6;arena=Space");
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a Space unit to defeat");
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZDESTROY", $currentPlayer, "-", 1);
      }
      break;
    case "6800160263"://Caretaker Matron
      $abilityName = GetResolvedAbilityName($cardID, $from);
      if($abilityName == "Draw" && GetClassState($currentPlayer, $CS_NumForcePlayed)) {
        Draw($currentPlayer);
      }
      break;
    case "6491675327"://Tip the Scale
      AddDecisionQueue("REVEALHANDCARDS", $otherPlayer, "-");
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "THEIRHAND");
      AddDecisionQueue("MZFILTER", $currentPlayer, "definedType=Unit");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a non-unit card to discard");
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZDESTROY", $currentPlayer, "-", 1);
      break;
    case "4371455331"://Last Words
      global $CS_NumAlliesDestroyed;
      if(GetClassState($currentPlayer, $CS_NumAlliesDestroyed) > 0) {
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY");
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a friendly unit to give 2 experience tokens to");
        AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "ADDEXPERIENCE", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "ADDEXPERIENCE", 1);
      }
      break;
    case "1022691467"://Hyena Bomber
      if ($from != "PLAY") {
        if (SearchCount(SearchAllies($currentPlayer, aspect: "Aggression")) > 1) {
          AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "THEIRALLY:arena=Ground");
          AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "You may choose a ground unit to deal 2 damage to");
          AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
          AddDecisionQueue("MZOP", $currentPlayer, "DEALDAMAGE,2,$currentPlayer", 1);
        }
      }
      break;
    case "1548886844"://Tusken Tracker
      AddCurrentTurnEffect($cardID, $otherPlayer, "PLAY");
      break;
    case "0463147975"://Always Two
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY:trait=Sith");
      AddDecisionQueue("MZFILTER", $currentPlayer, "unique=0");
      AddDecisionQueue("OP", $currentPlayer, "MZTONORMALINDICES");
      AddDecisionQueue("PREPENDLASTRESULT", $currentPlayer, "2-", 1);
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose two friendly Sith units", 1);
      AddDecisionQueue("MULTICHOOSEUNIT", $currentPlayer, "<-", 1);
      AddDecisionQueue("SPECIFICCARD", $currentPlayer, "ALWAYS_TWO", 1);
      break;
    case "4387584779"://Following the Path
      AddDecisionQueue("SEARCHDECKTOPX", $currentPlayer, "8;2;include-trait-Force&include-definedType-Unit");
      AddDecisionQueue("REVEALCARDS", $currentPlayer, "-", 1);
      AddDecisionQueue("MULTIADDTOPDECK", $currentPlayer, "DECK", 1);
      break;
    case "0978531185"://Psychometry
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYDISCARD");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a card in your discard pile");
      AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("SPECIFICCARD", $currentPlayer, "PSYCHOMETRY", 1);
      break;
    case "5562351003"://A Precarious Predicament
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "THEIRALLY", 1);
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to return to their hand", 1);
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("SETDQVAR", $currentPlayer, "0", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "GETCARDID", 1);
      AddDecisionQueue("SETDQVAR", $currentPlayer, "1", 1);
      AddDecisionQueue("SETDQCONTEXT", $otherPlayer, "Does <1> say it could be worse?");
      AddDecisionQueue("YESNO", $otherPlayer, "-");
      AddDecisionQueue("NOPASS", $otherPlayer, "-", 1);//Play It's Worse for free
      AddDecisionQueue("SPECIFICCARD", $currentPlayer, "A_Precarious_Predicament", 1);
      AddDecisionQueue("ELSE", $otherPlayer, "-");//Accept the bounce
      AddDecisionQueue("PASSPARAMETER", $currentPlayer, "{0}", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "BOUNCE", 1);
      break;
    case "8365703627"://The Burden of Masters
      global $CS_AfterPlayedBy;
      SetClassState($currentPlayer, $CS_AfterPlayedBy, $cardID);
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYDISCARD:trait=Force;definedType=Unit");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a Force unit in your discard to put on the bottom of your deck");
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "BOTTOMDECK", 1);
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYHAND:definedType=Unit", 1);
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit from your hand to play", 1);
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "PLAYCARD", 1);
      break;
    case "2720873461"://Disturbance in the Force
      global $CS_NumLeftPlay;
      if (GetClassState($currentPlayer, $CS_NumLeftPlay) > 0) {
        TheForceIsWithYou($currentPlayer);
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY");
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "You may choose a friendly unit to give a shield token to");
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "ADDSHIELD", 1);
      } else {
        WriteLog("<span style='color:green'>Luminous beings are we, not this crude matter.</span>");
      }
      break;
    case "5800386133"://Yoda's Lightsaber
      if (HasTheForce($currentPlayer)) {
        DQAskToUseTheForce($currentPlayer);
        AddDecisionQueue("PASSPARAMETER", $currentPlayer, "MYCHAR-0", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "RESTORE,3", 1);
      }
      break;
    case "2755329102"://Loth Cat
      if ($from != "PLAY") {
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY:arena=Ground&THEIRALLY:arena=Ground");
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "You may choose a ground unit to exhaust");
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "REST", 1);
      }
      break;
    case "1876907238"://Trust Your Instincts
      if (HasTheForce($currentPlayer)) {
        DQAskToUseTheForce($currentPlayer);
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY", 1);
        AddDecisionQueue("MZFILTER", $currentPlayer, "status=1", 1);
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a card to attack with", 1);
        AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("SETDQVAR", $currentPlayer, "0", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "GETUNIQUEID", 1);
        AddDecisionQueue("ADDLIMITEDCURRENTEFFECT", $currentPlayer, "1876907238", 1);
        AddDecisionQueue("PASSPARAMETER", $currentPlayer, "{0}", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "ATTACK", 1);
      } else {
        WriteLog("<span style='color:red'>Your thoughts betray you.</span>");
      }
      break;
    case "5098263349"://Yoda LOF
      if (HasTheForce($currentPlayer)) {
        DQAskToUseTheForce($currentPlayer);
        AddDecisionQueue("PASSPARAMETER", $currentPlayer, "MYCHAR-0", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "RESTORE,5", 1);
      }
      break;
    case "5074877387"://Three Lessons
      global $CS_AfterPlayedBy;
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYHAND:definedType=Unit");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to play");
      AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("SETDQVAR", $currentPlayer, "0", 1);
      AddDecisionQueue("PASSPARAMETER", $currentPlayer, $cardID, 1);
      AddDecisionQueue("SETCLASSSTATE", $currentPlayer, $CS_AfterPlayedBy, 1);
      AddDecisionQueue("PASSPARAMETER", $currentPlayer, "{0}", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "PLAYCARD", 1);
      break;
    case "1759165041"://Heavy Blaster Cannon
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "THEIRALLY:arena=Ground&MYALLY:arena=Ground");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a ground unit to deal 1 damage to");
      AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("SETDQVAR", $currentPlayer, "0", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "GETUNIQUEID", 1);
      for ($i = 0; $i < 3; ++$i) {
        AddDecisionQueue("PASSPARAMETER", $currentPlayer, "{0}", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "DEALDAMAGE,1,$currentPlayer", 1);
      }
      break;
    case "1093502388"://DRK-1 Probe Droid
      if($from != "PLAY") {
        DefeatUpgrade($currentPlayer, may:true, upgradeFilter: "unique=1");
      }
      break;
    case "1393713161"://Flight of the Inquisitor
      MZMoveCard($currentPlayer, "MYDISCARD:trait=Force;definedType=Unit", "MYHAND", may:true, context:"Choose a Force unit to return to your hand");
      MZMoveCard($currentPlayer, "MYDISCARD:trait=Lightsaber;definedType=Upgrade", "MYHAND", may:true, context:"Choose a Lightsaber upgrade to return to your hand", isSubsequent:1);
      break;
    case "1906860379"://Force Illusion
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "THEIRALLY");
      AddDecisionQueue("MZFILTER", $currentPlayer, "status=1");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose an enemy unit to exhaust");
      AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "REST", 1);
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a friendly unit to give Sentinel to");
      AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "GETUNIQUEID", 1);
      AddDecisionQueue("ADDLIMITEDCURRENTEFFECT", $currentPlayer, "$cardID,HAND", 1);
      break;
    case "5227991792"://Asajj Ventress
      if($from != "PLAY") {
        DQChooseAUnitToGiveEffect($currentPlayer, $cardID, $from,
          may: false, mzSearch: "MYALLY:trait=Force", mzFilter: "index=" . $playAlly->MZIndex(),
          context: "a Force unit to give +2/+0 for this phase");
      }
      break;
    case "0024409893"://BD-1
      if($from != "PLAY") {
        DQChooseAUnitToGiveEffect($currentPlayer, $cardID, $from,
          may: false, mzSearch: "MYALLY", mzFilter: "index=" . $playAlly->MZIndex(),
          context: "a friendly unit to give +1/0 and Saboteur", lastingType:"Permanent");
      }
      break;
    case "8743459187"://Focus Determines Reality
      AddCurrentTurnEffect("8743459187", $currentPlayer, "PLAY");
      break;
    case "2968188569"://The Purggil King
      if($from != "PLAY") {
        $allies = &GetAllies($currentPlayer);
        for ($i = 0; $i < count($allies); $i += AllyPieces()) {
         $ally = new Ally("MYALLY-" . $i, $currentPlayer);
         if ($ally->Health() >= 7) {
          Draw($currentPlayer);
         }
        }
      }
      break;
    case "6553590382"://In the Shadows
      DQMultiUnitSelect($currentPlayer, 3, "MYALLY:keyword=Hidden", "to give Experience to");
      AddDecisionQueue("MZOP", $currentPlayer, GiveExperienceBuilder($currentPlayer), 1);
      break;
    case "6918152447"://Ravening Gundark
      //When Played:
      if($from != "PLAY") {
        //Deal 1 damage to a ground unit.
        DQPingUnit($currentPlayer, 1, isUnitEffect:true, may:false, mzSearch:"MYALLY:arena=Ground&THEIRALLY:arena=Ground", context:"a ground unit", unitCardID:$cardID);
      }
      break;
    case "7030628730"://Force Slow
      DQChooseAUnitToGiveEffect($currentPlayer, $cardID, $from,
        may: false, mzSearch: "MYALLY&THEIRALLY", mzFilter: "status!=1",
        context: "an exhausted unit to give -8/-0 for this phase", lastingType:"Phase");
      break;
    case "7137948532"://Saesee Tiin
      //When Played:
      if($from != "PLAY") {
        //if you have the initiative, deal 1 damage to each of up to 3 units.
        if($currentPlayer == $initiativePlayer) {
          DQMultiUnitSelect($currentPlayer, 3, "MYALLY&THEIRALLY", "to deal 1 damage to");
          AddDecisionQueue("MZOP", $currentPlayer, DealMultiDamageBuilder($currentPlayer, isUnitEffect:1), 1);
        }
      }
      break;
    case "8241022502"://Rampage
      $allies = &GetAllies($currentPlayer);
      for ($i = 0; $i < count($allies); $i += AllyPieces()) {
        if(TraitContains($allies[$i], "Creature", $currentPlayer)) {
          Ally::FromUniqueId($allies[$i+5])->AddRoundHealthModifier(2);
          AddCurrentTurnEffect($cardID, $currentPlayer, "PLAY", $allies[$i+5]);
        }
      }
      break;
    case "8421586325"://Unleash Rage
      if(HasTheForce($currentPlayer)) {
        //Use the Force. If you do, give a friendly unit +3/+0 for this phase.
        UseTheForce($currentPlayer);
        DQBuffUnit($currentPlayer, $cardID, 3, 0, may:false, mzSearch:"MYALLY", context:"a friendly unit to give +3/+0 for this phase");
      }
      break;
    case "8496220683"://Point Rain Reclaimer
      if($from != "PLAY" && SearchCount(SearchAllies($currentPlayer, trait:"Jedi")) > 0) {
        //When played: If you control a Jedi unit, you may give an experience token to this unit.
        AddDecisionQueue("YESNO", $currentPlayer, "if you want to give an experience token to " . CardLink($cardID, $cardID));
        AddDecisionQueue("NOPASS", $currentPlayer, "-");
        AddDecisionQueue("PASSPARAMETER", $currentPlayer, $playAlly->UniqueID(), 1);
        AddDecisionQueue("MZOP", $currentPlayer, "ADDEXPERIENCE", 1);
      }
      break;
    case "8580514429"://Pillio Star Compass
      //When Played: Search the top 3 cards of your deck for a unit, reveal it, and draw it.
      AddDecisionQueue("SEARCHDECKTOPX", $currentPlayer, "3;1;include-definedType-Unit");
      AddDecisionQueue("ADDHAND", $currentPlayer, "-", 1);
      AddDecisionQueue("REVEALCARDS", $currentPlayer, "DECK", 1);
      break;
    case "8621390428"://Consumed by the Dark Side
      //Give 2 Experience tokens to a unit, then deal 2 damage to it.
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY&THEIRALLY");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to give 2 experience tokens to");
      AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "ADDEXPERIENCE", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "ADDEXPERIENCE", 1);
      AddDecisionQueue("MZOP", $currentPlayer, DealDamageBuilder(2, $currentPlayer), 1);
      break;
    case "8635969563"://Jocasta Nu
      //When Played: You may attach a friendly upgrade on a friendly unit to a different eligible unit.
      if($from != "PLAY") {
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY:hasUpgradeOnly=true");
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a friendly unit to move an upgrade from");
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("SETDQVAR", $currentPlayer, "0", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "GETUPGRADES", 1);
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose an upgrade to take.", 1);
        AddDecisionQueue("CHOOSECARD", $currentPlayer, "<-", 1);
        AddDecisionQueue("SETDQVAR", $currentPlayer, "1", 1);
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY",1);
        AddDecisionQueue("MZFILTER", $currentPlayer, "filterUpgradeEligible={1}", 1);
        AddDecisionQueue("MZFILTER", $currentPlayer, "index={1}", 1);
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to move <1> to.", 1);
        AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "MOVEUPGRADE", 1);
      }
      break;
    case "9129337737"://Premonition of Doom
      AddCurrentTurnEffect($cardID, $currentPlayer, $from);
      break;
    case "9242267986"://The Tragedy of Darth Plagueis
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a friendly unit to influence midichlorians");
      AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "GETUNIQUEID", 1);
      AddDecisionQueue("ADDLIMITEDCURRENTEFFECT", $currentPlayer, "$cardID,$f rom  ", 1);
      MZChooseAndDestroy($otherPlayer, "MYALLY", context: "Choose a unit to defeat.");
      break;
    case "9854991700"://Calm in the Storm
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY");
      AddDecisionQueue("MZFILTER", $currentPlayer, "status=1");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a frindly unit to exhaust.");
      AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "REST", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "ADDEXPERIENCE", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "ADDEXPERIENCE", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "ADDSHIELD", 1);
      break;
    case "5787840677"://Go into Hiding
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY&THEIRALLY");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to go into hiding this phase.");
      AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "GETUNIQUEID", 1);
      AddDecisionQueue("ADDLIMITEDCURRENTEFFECT", $currentPlayer, "$cardID,$from", 1);
      break;
    case "1708605474"://Dagoyan Master
      //When played:
      if($from != "PLAY") {
        //You may use the Force. If you do, search the top 5 cards of your deck for a Force unit, reveal it, and draw it.
        if(HasTheForce($currentPlayer)) {
          DQAskToUseTheForce($currentPlayer);
          AddDecisionQueue("SEARCHDECKTOPX", $currentPlayer, "5;1;include-trait-Force&include-definedType-Unit", 1);
          AddDecisionQueue("ADDHAND", $currentPlayer, "-", 1);
          AddDecisionQueue("REVEALCARDS", $currentPlayer, "DECK", 1);
        }
      }
      break;
    case "7787879864"://Cin Drallig
      //When played:
      if($from != "PLAY") {
        //You may play a {lightsaber} upgrade from your hand for free on this unit. If you do, ready him.
        if(SearchCount(SearchHand($currentPlayer, trait:"Lightsaber")) > 0) {
          global $CS_AfterPlayedBy;
          AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYHAND:trait=Lightsaber;definedType=Upgrade");
          AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a Lightsaber upgrade to play for free on this unit");
          AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
          AddDecisionQueue("SETDQVAR", $currentPlayer, "0", 1);
          AddDecisionQueue("ADDCURRENTEFFECT", $currentPlayer, $cardID, 1);
          AddDecisionQueue("PASSPARAMETER", $currentPlayer, $cardID, 1);
          AddDecisionQueue("SETCLASSSTATE", $currentPlayer, $CS_AfterPlayedBy, 1);
          AddDecisionQueue("PASSPARAMETER", $currentPlayer, "{0}", 1);
          AddDecisionQueue("MZOP", $currentPlayer, "PLAYCARD", 1);
        }
      }
      break;
    case "2236831712"://Leia Organa (Extraordinary)
      $abilityName = GetResolvedAbilityName($cardID, $from);
      if($abilityName == "Fly Through Space") {
        if(!HasTheForce($currentPlayer)) {
          WriteLog(NoForceSpan());
          RevertGamestate();
        } else {
          UseTheForce($currentPlayer);
          AddDecisionQueue("PASSPARAMETER", $currentPlayer, $playAlly->MZIndex(), 1);
          AddDecisionQueue("MZOP", $currentPlayer, "MOVEARENA,Ground", 1);
          AddDecisionQueue("SPECIFICCARD", $currentPlayer, "LEIAORGANA_LOF", 1);
        }
      }
      break;
    case "1655929166"://Whirlwind of Power
      $debuff = 2;
      if(HasUnitWithTraitInPlay($currentPlayer, "Force")) $debuff = 3;
      DQDebuffUnit($currentPlayer, $otherPlayer, "$cardID-$debuff", $debuff, $debuff, false);
      break;
    case "6501780064"://Babu Frik
      $abilityName = GetResolvedAbilityName($cardID, $from);
      if($abilityName == "Droid Attack") {
        if(SearchCount(SearchAllies($currentPlayer, trait:"Droid")) > 0) {
          DQAttackWithEffect($currentPlayer, $cardID, $from, mzSearch:"MYALLY:trait=Droid",
            context:"a Droid unit to attack with");
        }
      }
      break;
    case "6551214763"://Force Speed
      DQAttackWithEffect($currentPlayer, $cardID, $from);
      break;
    //Intro Battle: Hoth
    case "9389694773"://Darth Vader leader
      if($abilityName == "Deal Damage") {
        //Deal 1 damage to their base.
        DealDamageAsync($otherPlayer, 1, "DAMAGE", "6088773439", sourcePlayer:$currentPlayer);
      }
      break;
    case "9970912404"://Leia Organa
      if($abilityName == "Heal") {
        //Heal 1 damage from a friendly unit.
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY");
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a friendly unit to heal 1 damage from");
        AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "RESTORE,1", 1);
      }
      break;
    case "0375794695"://Recovery
      //Heal 5 damage from a unit.
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY&THEIRALLY");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to heal 5 damage from");
      AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "RESTORE,5", 1);
      break;
    case "1174196426"://Luke Skywalker
      if($from != "PLAY") {
        //You may deal 3 damage to a ground unit.
        DQPingUnit($currentPlayer, 3, isUnitEffect:true, may:true, mzSearch:"MYALLY:arena=Ground&THEIRALLY:arena=Ground", context:"a ground unit", unitCardID:$cardID);
      }
      break;
    case "2404973143"://C-3PO
      //When played: if you control a Cunning_aspect unit, draw a card.
      if($from != "PLAY" && SearchCount(SearchAllies($currentPlayer, aspect:"Cunning")) > 0) {
        Draw($currentPlayer);
      }
      break;
    case "2859074789"://Go For The Legs
      //Exhaust an enemy ground unit.
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "THEIRALLY:arena=Ground");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose an enemy ground unit to exhaust");
      AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "REST", 1);
      break;
    case "6776733024"://General Veers
      //When played: if you control a Vigilance_aspect unit, deal 2 damage to an enemy base and heal 2 damage from your base.
      if($from != "PLAY" && SearchCount(SearchAllies($currentPlayer, aspect:"Vigilance")) > 0) {
        //Deal 2 damage to an enemy base.
        DealDamageAsync($otherPlayer, 2, "DAMAGE", $cardID, sourcePlayer:$currentPlayer);
        //Heal 2 damage from your base.
        Restore(2, $currentPlayer);
      }
      break;
    case "4184803715"://Avenger
      //When played: deal 1 damage to each other unit (including friendly units).
      if($from != "PLAY") {
        DamageAllAllies(1, $cardID, except: $playAlly->MZIndex());
      }
      break;
    case "7524197668"://We're In Trouble
      //Deal 3 damage to a unit.
      DQPingUnit($currentPlayer, 3, isUnitEffect:false, may:false);
      break;
    case "8290455967"://Target The Main Generator
      //Deal 2 damage to a base.
      DealDamageAsync($otherPlayer, 2, "DAMAGE", $cardID, sourcePlayer:$currentPlayer);
      break;
    case "7014669428"://Too Strong For Blasters
      //Heal 2 damage from a unit.
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY&THEIRALLY");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to heal 2 damage from");
      AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "RESTORE,2", 1);
      break;
    case "1758639231"://Improvised Detonation
      DQAttackWithEffect($currentPlayer, $cardID, $from);
      break;
    case "1941072965"://I'll Cover For You
      DQMultiUnitSelect($currentPlayer, 2, "THEIRALLY", "to deal 1 damage to", cantSkip:true);
      AddDecisionQueue("MZOP", $currentPlayer, DealMultiDamageBuilder($currentPlayer), 1);
      break;
    case "5480486728"://Blizzard One
      //When played:
      if($from != "PLAY") {
        //You may defeat a non-leader ground unit with 3 or less remaining HP.
        MZChooseAndDestroy($currentPlayer, "MYALLY:arena=Ground;maxHealth=3&THEIRALLY:arena=Ground;maxHealth=3", may: true, filter: "leader=1");
      }
      break;
    case "4512477799"://Hoth Lieutenant
      //When played:
      if($from != "PLAY") {
        DQAttackWithEffect($currentPlayer, $cardID, $from, mzOtherThan: $playAlly->MZIndex(), may: true);
      }
      break;
    case "2796502553"://I Want Proof, Not Leads
      Draw($currentPlayer, true);
      Draw($currentPlayer, true);
      PummelHit($currentPlayer);
      break;
    case "4087028261"://The Desolation of Hoth
      DQMultiUnitSelect($currentPlayer, 2, "THEIRALLY:maxCost=3", "to defeat");
      AddDecisionQueue("SPECIFICCARD", $currentPlayer, "THE_DESOLATION_OF_HOTH", 1);
      break;
    case "4187779775"://You Have Failed Me
      //Defeat a friendly unit. If you do, ready a friendly unit with 5 or less power.
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a friendly unit to defeat");
      AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZDESTROY", $currentPlayer, "-", 1);
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY:maxAttack=5", 1);
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a friendly unit with 5 or less power to ready", 1);
      AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "READY", 1);
      break;
    case "6270777752"://Millennium Falcon
      //When played: if your base has more damage on it than an enemy base, ready this unit.
      if($from != "PLAY" && PlayerHasLessDamageOnBase($otherPlayer)) {
        AddDecisionQueue("PASSPARAMETER", $currentPlayer, $playAlly->UniqueID(), 1);
        AddDecisionQueue("MZOP", $currentPlayer, "READY", 1);
      }
      break;
    case "9508246309"://Imperial Deck Officer
      $abilityName = GetResolvedAbilityName($cardID, $from);
      if($abilityName == "Heal") {
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY:aspect=Villainy&THEIRALLY:aspect=Villainy");
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a villainous unit to heal damage from");
        AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "RESTORE,2", 1);
      }
      break;
    case "9782761594"://Ion Cannon
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a Space unit to deal 3 damage to");
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY:arena=Space&THEIRALLY:arena=Space");
      AddDecisionQueue("MZFILTER", $currentPlayer, "index=MYALLY-" . $playAlly->Index());
      AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "DEALDAMAGE,3,$currentPlayer,1", 1);
      break;
    //PlayAbility End
    default: break;
  }

  if($from != "PLAY" && $from != "EQUIP" && $from != "CHAR"
    && HasWhenPlayed($cardID)
    && SearchCurrentTurnEffects("0661066339", $currentPlayer, remove:true)) {//Qui-Gon Jinn's Aethersprite
    $data = "$cardID,$resourcesPaid,$target,$additionalCosts,$uniqueId";
    AddLayer("TRIGGER", $currentPlayer, "0661066339", $data);
  }
}

function AttackWithMyUnitEvenIfExhaustedNoBases($player, $traitOnly="", $withCombatEffect="") {
  global $CCS_CantAttackBase;
  $search = $traitOnly == "" ? "MYALLY" : "MYALLY:trait=$traitOnly";
  $context = $traitOnly == "" ? "unit" : "$traitOnly unit";
  AddDecisionQueue("MULTIZONEINDICES", $player, $search, 1);
  AddDecisionQueue("SETDQCONTEXT", $player, "Choose a $context to attack with");
  AddDecisionQueue("CHOOSEMULTIZONE", $player, "<-", 1);
  AddDecisionQueue("SETDQVAR", $player, "0", 1);
  AddDecisionQueue("PASSPARAMETER", $player, 1, 1);
  AddDecisionQueue("SETCOMBATCHAINSTATE", $player, $CCS_CantAttackBase, 1);
  AddDecisionQueue("PASSPARAMETER", $player, "{0}", 1);
  if($withCombatEffect != "") {
    AddDecisionQueue("MZOP", $player, "GETUNIQUEID", 1);
    AddDecisionQueue("ADDLIMITEDCURRENTEFFECT", $player, "$withCombatEffect,PLAY", 1);
    AddDecisionQueue("PASSPARAMETER", $player, "{0}", 1);
  }
  AddDecisionQueue("MZOP", $player, "ATTACK", 1);
}

function ResetResources($player) {
  $resourceCards = &GetResourceCards($player);
  for($i=0; $i<count($resourceCards); $i+=ResourcePieces()) {
    $resourceCards[$i + 4] = 0;
  }
}

function ReadyResource($player, $amount=1) {
  $resourceCards = &GetResourceCards($player);
  $numReadied = 0;
  for($i=0; $i<count($resourceCards) && $numReadied < $amount; $i+=ResourcePieces()) {
    if($resourceCards[$i + 4] == 1) {
      ++$numReadied;
      $resourceCards[$i + 4] = 0;
    }
  }
}

function ExhaustResource($player, $amount=1) {
  $resourceCards = &GetResourceCards($player);
  $numExhausted = 0;
  for($i=0; $i<count($resourceCards) && $numExhausted < $amount; $i+=ResourcePieces()) {
    if($resourceCards[$i + 4] == 0) {
      ++$numExhausted;
      $resourceCards[$i + 4] = 1;
    }
  }
}

function AfterPlayedByAbility($cardID) {
  global $currentPlayer, $CS_AfterPlayedBy;
  SetClassState($currentPlayer, $CS_AfterPlayedBy, "-");
  $index = LastAllyIndex($currentPlayer);
  $ally = new Ally("MYALLY-" . $index, $currentPlayer);
  switch($cardID) {
    case "PLAYCAPTIVE":
      $ally->SetOwner($ally->Owner() == 1 ? 2 : 1);
      break;
    case "040a3e81f3"://Lando Calrissian Leader Unit
    case "5440730550"://Lando Calrissian
      AddDecisionQueue("OP", $currentPlayer, "ADDTOPDECKASRESOURCE");
      MZChooseAndDestroy($currentPlayer, "MYRESOURCES", context:"Choose a resource to destroy");
      break;
    case "9226435975"://Han Solo Red
      AddDecisionQueue("OP", $currentPlayer, "GETLASTALLYMZ");
      AddDecisionQueue("MZOP", $currentPlayer, "DEALDAMAGE,2,$currentPlayer", 1);
      break;
    case "a742dea1f1"://Han Solo Red Unit
        AddDecisionQueue("OP", $currentPlayer, "GETLASTALLYMZ");
        AddDecisionQueue("MZOP", $currentPlayer, "DEALDAMAGE,2,$currentPlayer,1", 1);
        break;
    case "3572356139"://Chewbacca (Walking Carpet)
      AddDecisionQueue("OP", $currentPlayer, "GETLASTALLYMZ");
      AddDecisionQueue("MZOP", $currentPlayer, "GETUNIQUEID", 1);
      AddDecisionQueue("ADDLIMITEDCURRENTEFFECT", $currentPlayer, "3572356139,PLAY", 1);
      break;
    case "5494760041"://Galactic Ambition
      AddDecisionQueue("PASSPARAMETER", $currentPlayer, "{1}", 1);
      AddDecisionQueue("SPECIFICCARD", $currentPlayer, "GALACTICAMBITION", 1);
      break;
    case "4113123883"://Unnatural Life
    case "7270736993"://Unrefusable Offer
    case "3426168686"://Sneak Attack
      AddDecisionQueue("OP", $currentPlayer, "GETLASTALLYMZ");
      AddDecisionQueue("MZOP", $currentPlayer, "READY,1", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "GETUNIQUEID", 1);
      AddDecisionQueue("ADDLIMITEDROUNDEFFECT", $currentPlayer, $cardID . "-2,PLAY", 1);
      break;
    case "8117080217"://Admiral Ozzel
      $ally->Ready();
      $otherPlayer = $currentPlayer == 1 ? 2 : 1;
      AddDecisionQueue("SETDQCONTEXT", $otherPlayer, "Choose a unit to ready");
      AddDecisionQueue("MULTIZONEINDICES", $otherPlayer, "MYALLY");
      AddDecisionQueue("MZFILTER", $otherPlayer, "status=0", 1);
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $otherPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $otherPlayer, "READY", 1);
      break;
    case "5696041568"://Triple Dark Raid
      AddDecisionQueue("OP", $currentPlayer, "GETLASTALLYMZ", 1);//TODO: this is breaking for Grievous Wheel Bike
      AddDecisionQueue("MZOP", $currentPlayer, "READY", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "GETUNIQUEID", 1);
      AddDecisionQueue("ADDLIMITEDCURRENTEFFECT", $currentPlayer, "5696041568-2,HAND", 1);
      break;
    //Jump to Lightspeed
    case "3658069276"://Lando Calrissian Leader
      if(SearchCount(SearchAllies($currentPlayer, arena:"Space")) > 0 && SearchCount(SearchAllies($currentPlayer, arena:"Ground")) > 0) {
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY&THEIRALLY");
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to give a shield", 1);
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "ADDSHIELD", 1);
      }
      break;
    case "2614693321"://Salvage
      AddDecisionQueue("OP", $currentPlayer, "GETLASTALLYMZ", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "DEALDAMAGE,1,$currentPlayer", 1);
      break;
    case "9763190770"://Major Vonreg Leader
      AddDecisionQueue("OP", $currentPlayer, "GETLASTALLYMZ");
      AddDecisionQueue("MZOP", $currentPlayer, "GETUNIQUEID");
      AddDecisionQueue("SETDQVAR", $currentPlayer, 0);
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY&THEIRALLY");
      AddDecisionQueue("MZFILTER", $currentPlayer, "uniqueID={0}");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to give +1/+0");
      AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "GETUNIQUEID", 1);
      AddDecisionQueue("ADDLIMITEDCURRENTEFFECT", $currentPlayer, "9763190770,PLAY", 1);
      break;
    case "5576996578"://Endless Legions
      AddDecisionQueue("SPECIFICCARD", $currentPlayer, "ENDLESSLEGIONS");
      break;
    //Legends of the Force
    case "3357344238"://Third Sister Leader
      AddDecisionQueue("OP", $currentPlayer, "GETLASTALLYMZ");
      AddDecisionQueue("MZOP", $currentPlayer, "GETUNIQUEID");
      AddDecisionQueue("ADDLIMITEDCURRENTEFFECT", $currentPlayer, "3357344238,HAND", 1);
      break;
    case "8032269906"://Soresu Stance
      AddDecisionQueue("OP", $currentPlayer, "GETLASTALLYMZ");
      AddDecisionQueue("MZOP", $currentPlayer, "GETUNIQUEID");
      AddDecisionQueue("MZOP", $currentPlayer, "ADDSHIELD", 1);
      break;
    case "5074877387"://Three Lessons
      AddDecisionQueue("OP", $currentPlayer, "GETLASTALLYMZ");
      AddDecisionQueue("MZOP", $currentPlayer, "GETUNIQUEID");
      AddDecisionQueue("MZOP", $currentPlayer, "ADDSHIELD", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "ADDEXPERIENCE", 1);
      AddDecisionQueue("ADDLIMITEDCURRENTEFFECT", $currentPlayer, "5074877387,PLAY", 1);
      break;
    case "7981459508"://Shien Flurry
      AddDecisionQueue("OP", $currentPlayer, "GETLASTALLYMZ");
      AddDecisionQueue("MZOP", $currentPlayer, "GETUNIQUEID");
      AddDecisionQueue("ADDLIMITEDCURRENTEFFECT", $currentPlayer, "7981459508,HAND", 1);
      break;
    case "8365703627"://The Burden of Masters
      AddDecisionQueue("OP", $currentPlayer, "GETLASTALLYMZ");
      AddDecisionQueue("MZOP", $currentPlayer, "ADDEXPERIENCE");
      AddDecisionQueue("MZOP", $currentPlayer, "ADDEXPERIENCE");
      break;
    case "d911b778e4"://Kylo Ren Leader unit
      SearchCurrentTurnEffects("d911b778e4", $currentPlayer, remove:true);
      AddDecisionQueue("SPECIFICCARD", $currentPlayer, "KYLOREN_LOF", 1);
      break;
    case "7787879864"://Cin Drallig
      AddDecisionQueue("OP", $currentPlayer, "GETLASTALLYMZ");
      AddDecisionQueue("MZOP", $currentPlayer, "READY", 1);
      break;
    default: break;
  }
}

function MemoryCount($player) {
  $memory = &GetMemory($player);
  return count($memory)/MemoryPieces();
}

function MemoryRevealRandom($player, $returnIndex=false)
{
  $memory = &GetMemory($player);
  $rand = GetRandom()%(count($memory)/MemoryPieces());
  $index = $rand*MemoryPieces();
  $toReveal = $memory[$index];
  $wasRevealed = RevealCards($toReveal);
  return $wasRevealed ? ($returnIndex ? $toReveal : $index) : ($returnIndex ? -1 : "");
}

function ExhaustAllAllies($arena, $player, $sourcePlayer)
{
  $allies = &GetAllies($player);
  for($i=0; $i<count($allies); $i+=AllyPieces())
  {
    $ally = new Ally("MYALLY-" . $i, $player);
    if($ally->CurrentArena() == $arena) {
      $ally->Exhaust(enemyEffects:$sourcePlayer != $ally->Controller());
    }
  }
}

function DestroyAllAllies($player="", $spareFilter="")
{
  $spareUpgraded = $spareFilter == "upgraded";
  //To avoid problems to do with allies entering play in the middle of things(i.e. captives), we first note the uniqueID of every ally in play and then destroy only those noted.
  global $currentPlayer;
  //Get all uniqueIDs of allies that are on board right now.
  $currentPlayerAllies = &GetAllies($currentPlayer);
  $currentPlayerAlliesUniqueIDs = [];
  if($player == "" || $player == $currentPlayer) {
    for($i = 0; $i < count($currentPlayerAllies); $i += AllyPieces()) {
      $currentPlayerAlliesUniqueIDs[] = $currentPlayerAllies[$i+5];
    }
  }
  $otherPlayer = $currentPlayer == 1 ? 2 : 1;
  $otherPlayerAllies = &GetAllies($otherPlayer);
  $otherPlayerAlliesUniqueIDs = [];
  if($player == "" || $player != $currentPlayer) {
    for($i  = 0; $i < count($otherPlayerAllies); $i += AllyPieces()) {
      $otherPlayerAlliesUniqueIDs[] = $otherPlayerAllies[$i+5];
    }
  }
  //cache any when theirs defeated triggers
  $cacheTriggers = [];

  foreach ($currentPlayerAlliesUniqueIDs as $UID) {
    $ally = new Ally($UID, $currentPlayer);
    if($spareUpgraded && $ally->IsUpgraded()) continue;
    $triggers = GetAllyWhenDestroyTheirsEffects($player, $otherPlayer, $ally->UniqueID(), $ally->IsUnique(), $ally->IsUpgraded(), $ally->GetUpgrades(withMetadata:true));
    if(count($triggers) > 0) {
      $cacheTriggers[] = $triggers;
    }
  }
  $defaultSpecialData = [
    "saveNalaSeForLast" => false,
    "nalaSeId" => -1,
    "saveKrellForLast" => false,
    "krellId" => -1
  ];
  //now destroy their allies
  $specialData = $defaultSpecialData;
  foreach ($otherPlayerAlliesUniqueIDs as $UID) {
    $ally = new Ally($UID, $otherPlayer);
    if($spareUpgraded && $ally->IsUpgraded()) continue;
    if(CheckForFriendlyDefeatedExceptions($ally, $specialData)) continue;
    $ally->Destroy();
  }
  if($specialData['saveNalaSeForLast']) Ally::FromUniqueId($specialData['nalaSeId'])->Destroy();
  if($specialData['saveKrellForLast']) Ally::FromUniqueId($specialData['krellId'])->Destroy();

  //now desroy my allies
  $specialData = $defaultSpecialData;
  foreach ($currentPlayerAlliesUniqueIDs as $UID) {
    $ally = new Ally($UID, $currentPlayer);
    if($spareUpgraded && $ally->IsUpgraded()) continue;
    if(CheckForFriendlyDefeatedExceptions($ally, $specialData)) continue;
    $ally->Destroy(enemyEffects:false);
  }
  if($specialData['saveNalaSeForLast']) Ally::FromUniqueId($specialData['nalaSeId'])->Destroy();
  if($specialData['saveKrellForLast']) Ally::FromUniqueId($specialData['krellId'])->Destroy();

  if(count($cacheTriggers) > 0) {
    foreach ($cacheTriggers as $triggers) {
      LayerTheirsDestroyedTriggers($otherPlayer, $triggers);
    }
  }
}

function CheckForFriendlyDefeatedExceptions(Ally $ally, &$specialData) {
  switch($ally->CardID()) {
    case "f05184bd91"://Nala Se
      $specialData['saveNalaSeForLast'] = true;
      $specialData['nalaSeId'] = $ally->UniqueID();
      return true;
    case "9353672706"://General Krell
      $specialData['saveKrellForLast'] = true;
      $specialData['krellId'] = $ally->UniqueID();
      return true;
  }

  return false;
}

function DamagePlayerAllies($player, $damage, $source, $type="-", $arena="")
{
  $enemyDamage = false;
  $fromUnitEffect = false;
  switch($source) {
    case "0160548661"://Fallen Lightsaber
    case "0683052393"://Hevy
    case "0354710662"://Saw Gerrera (Resistance Is Not Terrorism)
      $enemyDamage = true;
      $fromUnitEffect = true;
      break;
    default: break;
  }

  $allies = &GetAllies($player);
  for($i=count($allies)-AllyPieces(); $i>=0; $i-=AllyPieces())
  {
    $ally = Ally::FromUniqueId($allies[$i+5]);
    if($arena != "" && !ArenaContains($allies[$i], $arena, $ally)) continue;
    $ally->DealDamage($damage, enemyDamage: $enemyDamage, fromUnitEffect: $fromUnitEffect);
  }
}

function DamageAllAllies($amount, $source, $alsoRest=false, $alsoFreeze=false, $arena="", $except="", $player="")
{
  global $currentPlayer;
  $otherPlayer = $currentPlayer == 1 ? 2 : 1;
  if($player == "" || $player == $otherPlayer) {
    $theirAllies = &GetAllies($otherPlayer);
    for($i=count($theirAllies) - AllyPieces(); $i>=0; $i-=AllyPieces())
    {
      $ally = Ally::FromUniqueId($theirAllies[$i+5]);
      if($arena != "" && !ArenaContains($theirAllies[$i], $arena, $ally)) continue;
      if($alsoRest) $theirAllies[$i+1] = 1;
      if($alsoFreeze) $theirAllies[$i+3] = 1;
      $ally->DealDamage($amount, enemyDamage:true);
    }
  }
  if(PlayerHasMalakaliLOF($currentPlayer) && TraitContains($source, "Creature", $currentPlayer)) {
    return;
  }
  if($player == "" || $player == $currentPlayer) {
    $allies = &GetAllies($currentPlayer);
    for($i=count($allies) - AllyPieces(); $i>=0; $i-=AllyPieces())
    {
      $ally = Ally::FromUniqueId($allies[$i+5]);
      if($arena != "" && !ArenaContains($allies[$i], $arena, $ally)) continue;
      if($except != "" && $except == ("MYALLY-" . $i)) continue;
      if($alsoRest) $allies[$i+1] = 1;
      if($alsoFreeze) $allies[$i+3] = 1;
      $ally->DealDamage($amount);
    }
  }
}



// function IsHarmonizeActive($player)//FAB
// {
//   global $CS_NumMelodyPlayed;
//   return GetClassState($player, $CS_NumMelodyPlayed) > 0;
// }

function IsMultiTargetAttackActive() {
  global $combatChainState, $CCS_MultiAttackTargets;
  //TODO: look into why SubmitSideboard.php is not initializing this
  return isset($combatChainState[$CCS_MultiAttackTargets]) && $combatChainState[$CCS_MultiAttackTargets]!=="-";
}

// function AddPreparationCounters($player, $amount=1)//FAB
// {
//   global $CS_PreparationCounters;
//   IncrementClassState($player, $CS_PreparationCounters, $amount);
// }

function Mill($player, $amount)
{
  $cards = "";
  $deck = &GetDeck($player);
  if($amount > count($deck)) $amount = count($deck);
  for($i=0; $i<$amount; ++$i)
  {
    $card = array_shift($deck);
    if($cards != "") $cards .= ",";
    $cards .= $card;
    AddGraveyard($card, $player, "DECK");
  }
  return $cards;
}

function AddTopDeckAsResource($player, $isExhausted=true)
{
  $deck = &GetDeck($player);
  if(count($deck) > 0) {
    $card = array_shift($deck);
    AddResources($card, $player, "DECK", "DOWN", isExhausted:($isExhausted ? 1 : 0));
    return true;
  }

  return false;
}

function HasTheForce($player) {
  $char = &GetPlayerCharacter($player);
  return $char[4] == "1";
}

function TheForceIsWithYou($player) {
  $char = &GetPlayerCharacter($player);
  if($char[4] == "1") return;
  $char[4] = "1";
  AddEvent("FORCETOKEN", "$player!1");
  WriteLog("The Force is with Player " . $player . ".");
}

function UseTheForce($player) {
  global $CS_NumTimesUsedTheForce;
  $char = &GetPlayerCharacter($player);
  if($char[4] == "0") return;
  $char[4] = "0";
  AddEvent("FORCETOKEN", "$player!0");
  $numTimes = IncrementClassState($player, $CS_NumTimesUsedTheForce);
  WriteLog("Player " . $player . " used the Force ($numTimes time" .  ($numTimes > 1 ? "s" : "") . " this phase).");
  //Unit "When you use the Force" effects
  $units = &GetAllies($player);
  for($i=0; $i<count($units); $i+=AllyPieces()) {
    switch($units[$i]) {
      case "1554637578"://The Father
        AddDecisionQueue("SETDQCONTEXT", $player, "You may deal 1 damage to this unit to gain the Force.", 1);
        AddDecisionQueue("YESNO", $player, "-", 1);
        AddDecisionQueue("NOPASS", $player, "-", 1);
        AddDecisionQueue("PASSPARAMETER", $player, "MYALLY-" . $i, 1);
        AddDecisionQueue("MZOP", $player, "DEALDAMAGE,1,$player", 1);
        AddDecisionQueue("THEFORCEISWITHYOU", $player, "-", 1);
        break;
      case "5098263349"://Yoda LOF
        $numUnits = SearchCount(SearchAllies($player));
        if ($numUnits > 0) {
          DQPingUnit($player, $numUnits * 2, isUnitEffect:true, may:true);
        }
        break;
      default:
        break;
    }
  }
}

function DQAskToUseTheForce($player, $withNoPass=true) {
  AddDecisionQueue("YESNO", $player, "if you want to use the Force");
  if($withNoPass) AddDecisionQueue("NOPASS", $player, "-", 1);
  AddDecisionQueue("USETHEFORCE", $player, "-", 1);
}

function DQTakeControlOfANonLeaderUnit($player) {
  AddDecisionQueue("MULTIZONEINDICES", $player, "THEIRALLY");
  AddDecisionQueue("MZFILTER", $player, "leader=1", 1);
  AddDecisionQueue("SETDQCONTEXT", $player, "Choose a unit to take control of", 1);
  AddDecisionQueue("MAYCHOOSEMULTIZONE", $player, "<-", 1);
  AddDecisionQueue("MZOP", $player, "GETUNIQUEID", 1);
  AddDecisionQueue("MZOP", $player, "TAKECONTROL", 1);
}

function DQWaylay($player) {
  AddDecisionQueue("MULTIZONEINDICES", $player, "MYALLY&THEIRALLY");
  AddDecisionQueue("MZFILTER", $player, "leader=1");
  AddDecisionQueue("SETDQCONTEXT", $player, "Choose a unit to return to hand");
  AddDecisionQueue("CHOOSEMULTIZONE", $player, "<-", 1);
  AddDecisionQueue("MZOP", $player, "BOUNCE", 1);
}

function DQPingUnit($player, $amount, $isUnitEffect, $may, $mzSearch = "MYALLY&THEIRALLY", $mzFilter="", $context="a unit", $sourcePlayer="", $preventable=1, $unitCardID="", $subsequent=false) {
  if($sourcePlayer == "") $sourcePlayer = $player;
  $subsequent = $subsequent ? 1 : 0;
  $isUnitEffect = $isUnitEffect ? 1 : 0;
  $preventable = $preventable ? 1 : 0;
  AddDecisionQueue("MULTIZONEINDICES", $player, $mzSearch, $subsequent);
  if($mzFilter != "") AddDecisionQueue("MZFILTER", $player, $mzFilter, $subsequent);
  AddDecisionQueue("SETDQCONTEXT", $player, "Choose $context to deal $amount damage to", $subsequent);
  AddDecisionQueue(($may ? "MAY" : "") . "CHOOSEMULTIZONE", $player, "<-", 1);
  AddDecisionQueue("MZOP", $player, DealDamageBuilder($amount, $sourcePlayer, $isUnitEffect, $preventable, $unitCardID), 1);
}

function ShuffleToBottomDeck($cards, $player) {
  $arr = [];
  for($i = count($cards); $i >= 0; --$i) {
    if($cards[$i] != "") $arr[] = RemoveGraveyard($player, $cards[$i]);
  }
  RevealCards(implode(",", $arr), $player);
  if(count($arr) > 0) {
    RandomizeArray($arr);
    $deck = new Deck($player);
    for($i=0; $i<count($arr); ++$i) {
      $deck->Add($arr[$i]);
    }
  }
}

//target type return values
//-1: no target
// 0: My Hero + Their Hero
// 1: Their Hero only
// 2: Any Target
// 3: Their Hero + Their Allies
// 4: My Hero only (For afflictions)
// 6: Any unit
// 7: Friendly unit
// 8: Any Non-Leader + Non-Vehicle unit
function PlayRequiresTarget($cardID)
{
  global $currentPlayer;
  switch($cardID)
  {
    case "8679831560": return 2;//Repair
    case "8981523525": return 6;//Moment of Peace
    case "2587711125": return 6;//Disarm
    case "6515891401": return 7;//Karabast
    case "5049217986": return 6;//Overpower
    case "2651321164": return 6;//Tactical Advantage
    case "1900571801": return 7;//Overwhelming Barrage
    case "6544277158": return 7;//Hotshot Maneuver
    case "5013139687": return 3;//Caught In The Crossfire
    case "7861932582": return 6;//The Force is With Me
    case "2758597010": return 6;//Maximum Firepower
    case "2202839291": return 6;//Don't Get Cocky
    case "1701265931": return 6;//Moment of Glory
    case "3765912000": return 7;//Take Captive
    case "5778949819": return 7;//Relentless Pursuit
    case "1973545191": return 6;//Unexpected Escape
    case "0598830553": return 7;//Dryden Vos
    case "8576088385": return 6;//Detention Block Rescue
    default: return -1;
  }
}

function DQMultiUnitSelect($player, $numUnits, $unitSelector, $title, $mzFilter="", $cantSkip=false, $customIndices="") {
  global $CS_CantSkipPhase, $dqVars;
  if($cantSkip) SetClassState($player, $CS_CantSkipPhase, 1);
  AddDecisionQueue("PASSPARAMETER", $player, "-");
  AddDecisionQueue("SETDQVAR", $player, "0");
  AddDecisionQueue("SETDQVAR", $player, "1");
  for ($i = $numUnits; $i > 0; $i--) {
    if($customIndices == "") {
      AddDecisionQueue("MULTIZONEINDICES", $player, $unitSelector, 1);
      if($mzFilter != "") AddDecisionQueue("MZFILTER", $player, $mzFilter, 1);
    } else {
      AddDecisionQueue("PASSPARAMETER", $player, $customIndices, 1);
    }
    AddDecisionQueue("MZFILTER", $player, "dqVar=0", 1);
    AddDecisionQueue("SETDQCONTEXT", $player, "Choose" . ($cantSkip ? "" : " up to") . " $i unit" . ($i > 1 ? "s " : " ") . $title, 1);
    AddDecisionQueue("MAYCHOOSEMULTIZONE", $player, "<-", 1);
    AddDecisionQueue("APPENDDQVAR", $player, "0", 1);
    AddDecisionQueue("MZOP", $player, "GETUNIQUEID", 1);
    AddDecisionQueue("SETDQVAR", $player, "2", 1);
    //TODO: add param for target amount if we ever need to deal more than one damage
    AddDecisionQueue("PASSPARAMETER", $player, "1-{2}", 1);
    AddDecisionQueue("APPENDDQVAR", $player, "1", 1);
  }
  AddDecisionQueue("PASSPARAMETER", $player, "{1}");
  AddDecisionQueue("EQUALPASS", $player, "-");
  AddDecisionQueue("PASSPARAMETER", $player, "0");
  AddDecisionQueue("SETCLASSSTATE", $player, $CS_CantSkipPhase);
  AddDecisionQueue("PASSPARAMETER", $player, "{1}");
}

function DQDebuffUnit($currentPlayer, $otherPlayer, $effectID, $attackDebuff,
    $healthDebuff="-", $may=true, $mzSearch="MYALLY&THEIRALLY", $mzFilter="", $context="a unit", $from="HAND", $subsequent=false) {
  $healthDebuff = $healthDebuff == "-" ? $attackDebuff : $healthDebuff;
  $subsequent = $subsequent ? 1 : 0;
  AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, $mzSearch, $subsequent);
  if($mzFilter != "") AddDecisionQueue("MZFILTER", $currentPlayer, $mzFilter, $subsequent);
  AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose $context to give -$attackDebuff/-$healthDebuff", 1);
  AddDecisionQueue(($may ? "MAY" : "") . "CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
  AddDecisionQueue("MZOP", $currentPlayer, "GETUNIQUEID", 1);
  AddDecisionQueue("ADDLIMITEDCURRENTEFFECT", $otherPlayer, "$effectID,$from", 1);
  if($healthDebuff > 0) {
    AddDecisionQueue("MZOP", $currentPlayer, "REDUCEHEALTH,$healthDebuff", 1);
  }
}

function DQBuffUnit($player, $effectID, $attackBuff,
    $healthBuff="-", $may=true, $mzSearch="MYALLY&THEIRALLY", $mzFilter="", $context="a unit", $from="HAND", $subsequent=false) {
  $healthBuff = $healthBuff == "-" ? $attackBuff : $healthBuff;
  $subsequent = $subsequent ? 1 : 0;
  AddDecisionQueue("MULTIZONEINDICES", $player, $mzSearch, $subsequent);
  if($mzFilter != "") AddDecisionQueue("MZFILTER", $player, $mzFilter, $subsequent);
  AddDecisionQueue("SETDQCONTEXT", $player, "Choose $context to give +$attackBuff/+$healthBuff", 1);
  AddDecisionQueue(($may ? "MAY" : "") . "CHOOSEMULTIZONE", $player, "<-", 1);
  AddDecisionQueue("MZOP", $player, "ADDHEALTH,$healthBuff", 1);
  AddDecisionQueue("MZOP", $player, "GETUNIQUEID", 1);
  AddDecisionQueue("ADDLIMITEDCURRENTEFFECT", $player, "$effectID,$from", 1);
}

function DQChooseAUnitToGiveEffect($player, $effectID, $from, $may=true,
  $mzSearch="MYALLY&THEIRALLY", $mzFilter="", $context="a unit", $lastingType="Phase",
  $subsequent=false)
{
  $subsequent = $subsequent ? 1 : 0;
  AddDecisionQueue("MULTIZONEINDICES", $player, $mzSearch, $subsequent);
  if($mzFilter != "") AddDecisionQueue("MZFILTER", $player, $mzFilter, $subsequent);
  AddDecisionQueue("SETDQCONTEXT", $player, "Choose $context", 1);
  AddDecisionQueue("CHOOSEMULTIZONE", $player, "<-", 1);
  AddDecisionQueue("MZOP", $player, "WRITECHOICE", 1);
  AddDecisionQueue("MZOP", $player, "GETUNIQUEID", 1);
  switch ($lastingType) {
    case "Permanent":
      AddDecisionQueue("ADDLIMITEDPERMANENTEFFECT", $player, "$effectID,$from", 1);
      break;
    case "Round":
      AddDecisionQueue("ADDLIMITEDROUNDEFFECT", $player, "$effectID,$from", 1);
      break;
    case "Phase":
      AddDecisionQueue("ADDLIMITEDCURRENTEFFECT", $player, "$effectID,$from", 1);
      break;
  }
}

function DQAttackWithEffect($currentPlayer, $effectID, $from, $mzSearch = "MYALLY", $context = "Choose a unit to attack with", $mzOtherThan = "", $may = false, $subsequent = false) {
  $subsequent = $subsequent ? 1 : 0;
  AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, $mzSearch, $subsequent);
  AddDecisionQueue("MZFILTER", $currentPlayer, "status=1", $subsequent);
  if($mzOtherThan != "") {
    AddDecisionQueue("MZFILTER", $currentPlayer, "index=$mzOtherThan", $subsequent);
  }
  AddDecisionQueue("SETDQCONTEXT", $currentPlayer, $context, $subsequent);
  AddDecisionQueue(($may ? "MAY" : "") . "CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
  AddDecisionQueue("SETDQVAR", $currentPlayer, "0", 1);
  AddDecisionQueue("MZOP", $currentPlayer, "GETUNIQUEID", 1);
  AddDecisionQueue("ADDLIMITEDCURRENTEFFECT", $currentPlayer, "$effectID,$from", 1);
  AddDecisionQueue("PASSPARAMETER", $currentPlayer, "{0}", 1);
  AddDecisionQueue("MZOP", $currentPlayer, "ATTACK", 1);
}

//target type return values
//-1: no target
// 0: My Hero + Their Hero
// 1: Their Hero only
// 2: Any Target
// 3: Their Units
// 4: My Hero only (For afflictions)
// 6: Any unit
// 7: Friendly unit
// 8: Any Non-Leader + Non-Vehicle unit
function GetArcaneTargetIndices($player, $target)
{
  global $CS_ArcaneTargetsSelected;
  $otherPlayer = ($player == 1 ? 2 : 1);

  if ($target == 8) {
    $rvArr = [];
    $theirAllies = &GetAllies($otherPlayer);
    for($i=0; $i<count($theirAllies); $i+=AllyPieces()) {
      $cardID = $theirAllies[$i];
      if (CardIDIsLeader($cardID) || TraitContains($cardID, "Vehicle", $otherPlayer)) {
        continue;
      }

      $rvArr[] = "THEIRALLY-" . $i;
    }

    $myAllies = &GetAllies($player);
    for($i=0; $i<count($myAllies); $i+=AllyPieces()) {
      $cardID = $myAllies[$i];
      if (CardIDIsLeader($cardID) || TraitContains($cardID, "Vehicle", $player)) {
        continue;
      }
      $rvArr[] = "MYALLY-" . $i;
    }

    return implode(",", $rvArr);
  } else if ($target == 4) return "MYCHAR-0";

  if($target != 3 && $target != 6 && $target != 7) $rv = "THEIRCHAR-0";
  else $rv = "";

  if(($target == 0 && !ShouldAutotargetOpponent($player)) || $target == 2)
  {
    $rv .= ",MYCHAR-0";
  }
  if($target == 2 || $target == 6)
  {
    $theirAllies = &GetAllies($otherPlayer);
    for($i=0; $i<count($theirAllies); $i+=AllyPieces())
    {
      if($rv != "") $rv .= ",";
      $rv .= "THEIRALLY-" . $i;
    }
    $myAllies = &GetAllies($player);
    for($i=0; $i<count($myAllies); $i+=AllyPieces())
    {
      if($rv != "") $rv .= ",";
      $rv .= "MYALLY-" . $i;
    }
  }
  elseif($target == 3 || $target == 5)
  {
    $theirAllies = &GetAllies($otherPlayer);
    for($i=0; $i<count($theirAllies); $i+=AllyPieces())
    {
      if($rv != "") $rv .= ",";
      $rv .= "THEIRALLY-" . $i;
    }
  } else if($target == 7) {
    $myAllies = &GetAllies($player);
    for($i=0; $i<count($myAllies); $i+=AllyPieces())
    {
      if($rv != "") $rv .= ",";
      $rv .= "MYALLY-" . $i;
    }
  }
  $targets = explode(",", $rv);
  $targetsSelected = GetClassState($player, $CS_ArcaneTargetsSelected);
  for($i=count($targets)-1; $i>=0; --$i)
  {
    if(DelimStringContains($targetsSelected, $targets[$i])) unset($targets[$i]);
  }
  return implode(",", $targets);
}

function CountPitch(&$pitch, $min = 0, $max = 9999)
{
  $pitchCount = 0;
  for($i = 0; $i < count($pitch); ++$i) {
    $cost = CardCost($pitch[$i]);
    if($cost >= $min && $cost <= $max) ++$pitchCount;
  }
  return $pitchCount;
}

function HandIntoMemory($player)
{
  AddDecisionQueue("MULTIZONEINDICES", $player, "MYHAND");
  AddDecisionQueue("SETDQCONTEXT", $player, "Choose a card to put into memory", 1);
  AddDecisionQueue("CHOOSEMULTIZONE", $player, "<-", 1);
  AddDecisionQueue("MZADDZONE", $player, "MYMEMORY,HAND,DOWN", 1);
  AddDecisionQueue("MZREMOVE", $player, "-", 1);
}

function Draw($player, $mainPhase = true, $specialCase = false)
{
  global $EffectContext, $mainPlayer, $CS_CardsDrawn;
  $otherPlayer = ($player == 1 ? 2 : 1);
  $deck = &GetDeck($player);
  $hand = &GetHand($player);
  if(count($deck) == 0) {
    $char = &GetPlayerCharacter($player);
    if(count($char) > CharacterPieces() && $char[CharacterPieces()] != "DUMMY") WriteLog("Player " . $player . " took 3 damage for having no cards left in their deck.");
    DealDamageAsync($player, 3, "DAMAGE", "DRAW");
    return -1;
  }
  if(CurrentEffectPreventsDraw($player, $mainPhase)) return -1;
  $hand[] = array_shift($deck);
  PermanentDrawCardAbilities($player);
  $hand = array_values($hand);
  $drawnCardID = $hand[count($hand) - 1];
  if($mainPhase) {
    IncrementClassState($player, $CS_CardsDrawn);
    OpponentUnitDrawEffects($otherPlayer);
    switch($drawnCardID) {
      case "6172986745"://Rey, With Palpatine's Power
        if(!$specialCase) ReyPalpatineLOF($player);
        break;
      default: break;
    }
  }
  LogPlayCardStats($player, $drawnCardID, "DECK", type:"DRAWN");
  return $drawnCardID;
}

function WakeUpChampion($player)
{
  $char = &GetPlayerCharacter($player);
  $char[1] = 2;
}

