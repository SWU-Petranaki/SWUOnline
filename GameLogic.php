<?php

include "Search.php";
include "CardLogic.php";
include "AuraAbilities.php";
include "ItemAbilities.php";
include "LeaderAbilities.php";
include "AllyAbilities.php";
include "PermanentAbilities.php";
include "CharacterAbilities.php";
include "WeaponLogic.php";
include "MZLogic.php";
include "Classes/Deck.php";
include "Classes/Ally.php";
include "DecisionQueue/DecisionQueueEffects.php";
include "CurrentEffectAbilities.php";
include "CombatChain.php";
include_once "WriteLog.php";
include_once "./Libraries/NetworkingLibraries.php";
include_once "./Libraries/GameFormats.php";

function ParseDQParameter($phase, $player, $parameter) {
  switch ($phase) {
    case "PARTIALMULTIDAMAGEMULTIZONE":
    case "MAYMULTIDAMAGEMULTIZONE":
    case "MULTIDAMAGEMULTIZONE":
    case "INDIRECTDAMAGEMULTIZONE":
    case "PARTIALMULTIHEALMULTIZONE":
    case "MAYMULTIHEALMULTIZONE":
    case "MULTIHEALMULTIZONE":
      $params = explode("-", $parameter);
      $counterLimit = $params[0];
      $mzIndexes = explode(",", implode("-", array_slice($params, 1)));
      $allies = [];
      $characters = [];

      // Get the allies and characters from the mzIndexes
      for ($i = 0; $i < count($mzIndexes); $i++) {
        $mzIndex = $mzIndexes[$i];
        if (str_contains($mzIndex, "ALLY")) {
          $ally = new Ally($mzIndex);
          $allies[] = $ally->UniqueId();
        } else if (str_contains($mzIndex, "CHAR")) {
          $character = new Character($mzIndex, $player);
          $characters[] = $character->UniqueId();
        }
      }

      return array(
        "counterLimit" => $counterLimit,
        "allies" => $allies,
        "characters" => $characters
      );
    default:
      break;
  }
  return "";
}


function DecisionQueueStaticEffect($phase, $player, $parameter, $lastResult)
{
  global $redirectPath, $playerID, $gameName;
  global $currentPlayer, $combatChain, $defPlayer;
  global $combatChainState;
  global $defCharacter, $otherPlayer;
  global $CS_NextNAACardGoAgain, $CCS_AttackTarget, $CS_NumLeftPlay, $CS_PlayIndex;
  global $CS_LayerTarget, $decisionQueue, $dqVars, $mainPlayer, $lastPlayed, $dqState, $CS_AbilityIndex, $CS_CharacterIndex;
  global $CS_AdditionalCosts, $CS_AlluvionUsed, $CS_MaxQuellUsed, $CS_DamageDealt, $CS_ArcaneTargetsSelected, $inGameStatus;
  global $CS_ArcaneDamageDealt, $MakeStartTurnBackup, $CCS_AttackTargetUID, $chainLinkSummary, $chainLinks, $MakeStartGameBackup, $CCS_MultiAttackTargets, $CS_NumEventsPlayed;
  $rv = "";

  switch($phase) {
    case "SEARCHDECKTOPX":
      //The parameter for this is: the number of cards to search, the number of cards that may be chosen, then a list of filters(delimited with | or & for OR or AND logic(combinations of both are not supported)) using the syntax for the FILTER DecisionQueue option(excluding the initial source part), with each of these three(number to search, max choosable, filter list) separated by a semicolon.
      //For example, the parameter for Darth Vader unit's search("Search the top 10 cards of your deck for any number of Villainy Aspect units with combined cost 3 or less") would be:
      //"10;99;include-aspect-Villainy&include-definedType-Unit&include-maxCost-3"(the validity of the selection(in this case the combined cost) can be further checked after this step if necessary).
      $paramArray = explode(";", $parameter);
      if(count($paramArray) != 3) {WriteLog("SEARCHDECKTOPX called incorrectly. Please file a bug report."); return "PASS";}
      $numToSearch = $paramArray[0];
      $numToAllowChoosing = $paramArray[1];
      $deckIndicesToShow = DecisionQueueStaticEffect("FINDINDICES", $player, "DECKTOPXINDICES," . $numToSearch, "");
      if($deckIndicesToShow == "PASS") return "PASS";

      if(substr_count($paramArray[2], "|") > 0) { //OR: Any card that matches at least one filter should be selectable.
        $filterArray = explode("|", $paramArray[2]);
        $chooseableDeckIndices = "";
        foreach($filterArray as $filter) {
          $chooseableDeckIndices .= DecisionQueueStaticEffect("FILTER", $player, "Deck-" . $filter, $deckIndicesToShow) . ",";
        }
        $choosableDeckIndicesArray = array_unique(explode(",", $chooseableDeckIndices));
        $choosableDeckIndicesArray = array_filter($choosableDeckIndicesArray, function($a){return $a != "PASS" && $a != "";});
        natsort($choosableDeckIndicesArray);
        $choosableDeckIndices = implode(",", $choosableDeckIndicesArray);
      }

      else { //AND: Only cards that match every filter should be selectable.
        $filterArray = explode("&", $paramArray[2]);
        $choosableDeckIndices = $deckIndicesToShow;
        foreach($filterArray as $filter) {
          $choosableDeckIndices = DecisionQueueStaticEffect("FILTER", $player, "Deck-" . $filter, $choosableDeckIndices);
          if($choosableDeckIndices == "PASS") {$choosableDeckIndices = ""; break;}
        }
      }

      PrependDecisionQueue("PROCESSSEARCH", $player, $numToSearch); //We'll need to know how many cards were searched to figure out how many to shuffle and put on the bottom later.
      PrependDecisionQueue("MULTICHOOSESEARCHTARGETS", $player,
        $numToAllowChoosing . "-" . $deckIndicesToShow . "-" . "0-" . $choosableDeckIndices, //The MULTICHOOSE system(case 19 in ProcessInput()) is set up to use three params(- delimited): $maxSelect, $options(usually choosable indices, but in this case just indices to show), and $minSelect. I want to extend this with choosable indices, so they come after, at index 3. $minSelect should always be 0 for a search as a player can always choose to find nothing from a search(Comp Rules v2.0 section 8.27.1).
        0, 1);
      if($dqState[4] != "-") PrependDecisionQueue("SETDQCONTEXT", $player, $dqState[4]); //Pass on the DQCONTEXT message here so individual card logic can actually set it for the search screen.
      return "";
    case "PROCESSSEARCH":
      $searchLeftoversCount = $parameter - count($lastResult);
      $searchTargets = DecisionQueueStaticEffect("MULTIREMOVEDECK", $player, "-", $lastResult);
      $searchLeftovers = DecisionQueueStaticEffect("FINDINDICES", $player, "DECKTOPXREMOVE," . $searchLeftoversCount, "-");
      DecisionQueueStaticEffect("ALLRANDOMBOTTOM", $player, "-", $searchLeftovers);
      return $searchTargets == "" ? "PASS" : $searchTargets;
    case "FINDINDICES":
      UpdateGameState($currentPlayer);
      BuildMainPlayerGamestate();
      $parameters = explode(",", $parameter);
      $parameter = $parameters[0];
      if(count($parameters) > 1) $subparam = $parameters[1];
      else $subparam = "";
      switch($parameter) {
        case "MYDISCARD":
        case "THEIRDISCARD":
          $p = $parameter == "MYDISCARD" ? $player : ($player == 1 ? 2 : 1);

          if ($subparam == "") {
            $discard = &GetDiscard($p);
            $rv = GetIndices(count($discard), pieces:DiscardPieces());
          } else {
            $rv = SearchDiscardForCard($p, $subparam);
          }
          break;
        case "GETINDICES": $rv = GetIndices($subparam); break;
        case "ARCANETARGET": $rv = GetArcaneTargetIndices($player, $subparam); break;
        // case "DAMAGEPREVENTION"://FAB
        //   $rv = GetDamagePreventionIndices($player);
        //   break;
        case "DAMAGEPREVENTIONTARGET": $rv = GetDamagePreventionTargetIndices(); break;
        case "DECK": $rv = SearchDeck($player); break;
        case "TOPDECK":
          $deck = &GetDeck($player);
          if(count($deck) > 0) $rv = "0";
          break;
        case "DECKTOPXINDICES":
          $deck = &GetDeck($player);
          for($i=0; $i<$subparam && $i<count($deck); ++$i)
          {
            if($rv != "") $rv .= ",";
            $rv .= $i;
          }
          break;
        case "GY":
          $discard = &GetDiscard($player);
          $rv = GetIndices(count($discard), pieces:DiscardPieces());
          break;
        // case "STORMTYRANTSEYE"://FAB
        //   $deck = &GetDeck($player);
        //   $toReveal = "";
        //   $found = false;
        //   for($i=0; $i<count($deck) && !$found; ++$i)
        //   {
        //     if($toReveal != "") $toReveal .= ",";
        //     $toReveal .= $deck[$i];
        //     if($rv != "") $rv .= ",";
        //     $rv .= $i;
        //     if(CardElement($deck[$i]) == "ARCANE") $found = true;
        //   }
        //   RevealCards($toReveal);
        //   LoseHealth(SearchCount($rv), $player);
        //   break;
        case "DECKTOPXREMOVE":
          $deck = new Deck($player);
          $rv = $deck->Top(true, $subparam);
          break;
        // case "PERMSUBTYPE"://FAB
        //   if($subparam == "Aura") $rv = SearchAura($player, "", $subparam);
        //   else $rv = SearchPermanents($player, "", $subparam);
        //   break;
        case "HAND":
          $hand = &GetHand($player);
          $rv = GetIndices(count($hand));
          break;
        case "HANDASPECT":
          $rv = SearchHand($player, aspect:$subparam);
          break;
        case "MATERIAL":
          $material = &GetMaterial($player);
          $rv = GetIndices(count($material));
          break;
        //This one requires CHOOSEMULTIZONECANCEL
        case "HANDPITCH": $rv = SearchHand($player, "", "", -1, -1, "", "", false, false, $subparam); break;
        case "HANDACTIONMAXCOST": $rv = CombineSearches(SearchHand($player, "A", "", $subparam), SearchHand($player, "AA", "", $subparam)); break;
        case "MULTIHAND":
          $hand = &GetHand($player);
          $rv = count($hand) . "-" . GetIndices(count($hand));
          break;
        case "MULTIHANDAA":
          $search = SearchHand($player, "AA");
          $rv = SearchCount($search) . "-" . $search;
          break;
        case "ARSENAL":
          $arsenal = &GetArsenal($player);
          $rv = GetIndices(count($arsenal), 0, ArsenalPieces());
          break;
        //These are needed because MZ search doesn't have facedown parameter
        case "ARSENALDOWN": $rv = GetArsenalFaceDownIndices($player, $subparam); break;
        case "ARSENALUP": $rv = GetArsenalFaceUpIndices($player, $subparam); break;
        //case "ITEMSMAX": $rv = SearchItems($player, "", "", $subparam); break;//FAB
        case "EQUIP": $rv = GetEquipmentIndices($player); break;
        case "EQUIP0": $rv = GetEquipmentIndices($player, 0); break;
        case "EQUIPONCC": $rv = GetEquipmentIndices($player, onCombatChain:true); break;
        case "CCAA": $rv = SearchCombatChainLink($player, "AA"); break;
        case "CCDEFLESSX": $rv = SearchCombatChainLink($player, "", "", -1, -1, "", "", false, false, -1, false, -1, $subparam); break;
        case "HANDAAMAXCOST": $rv = SearchHand($player, "AA", "", $subparam); break;
        case "MYHANDAA": $rv = SearchHand($player, "AA"); break;
        case "MAINHAND":
          $hand = &GetHand($mainPlayer);
          $rv = GetIndices(count($hand)); break;
        //case "BANISHTYPE": $rv = SearchBanish($player, $subparam); break;//FAB
        case "UNITS":
          $allies = &GetAllies($player);
          $rv = GetIndices(count($allies), 0 , AllyPieces());
          break;
        case "UNITSANDBASE":
          $allies = &GetAllies($player);
          $rv = "0;" . (count($allies)/AllyPieces()) . "-" . GetIndices(count($allies), 0 , AllyPieces());
          break;
        case "THEIRUNITSANDBASE":
          $allies = &GetAllies($player == 1 ? 2 : 1);
          $rv = "0;" . (count($allies)/AllyPieces()) . "-" . GetIndices(count($allies), 0 , AllyPieces());
          break;
        case "ALLOURUNITSMULTI":
          $theirAllies = &GetAllies($player == 1 ? 2 : 1);
          $myAllies = &GetAllies($player);
          $rv = (count($theirAllies)/AllyPieces()) . "-" . GetIndices(count($theirAllies), 0 , AllyPieces())
            . "&" . (count($myAllies)/AllyPieces()) . "-" . GetIndices(count($myAllies), 0 , AllyPieces());
          break;
        case "ALLTHEIRUNITSMULTI":
          $allies = &GetAllies($player == 1 ? 2 : 1);
          $rv = (count($allies)/AllyPieces()) . "-" . GetIndices(count($allies), 0 , AllyPieces());
          break;
        case "ALLTHEIRUNITSMULTILIMITED":
          $allies = &GetAllies($player == 1 ? 2 : 1);
          $rv = $subparam . "-" . GetIndices(count($allies), 0 , AllyPieces());
          break;
        case "ALLTHEIRGROUNDUNITSMULTI":
          $allies = &GetAllies($player == 1 ? 2 : 1);
          $groundAllies = "";
          $groundCount = 0;
          for($i = 0; $i < count($allies); $i+=AllyPieces()) {
            $ally = Ally::FromUniqueId($allies[$i+5]);
            if(ArenaContains($allies[$i], "Ground", $ally)) {
              if($groundAllies != "") $groundAllies .= ",";
              $groundAllies .= $i;
              $groundCount++;
            }
          }
          $rv = $groundCount . "-" . $groundAllies;
          break;
        case "ALLTHEIRGROUNDUNITSMULTILIMITED":
          $allies = &GetAllies($player == 1 ? 2 : 1);
          $groundAllies = "";
          for($i = 0; $i < count($allies); $i+=AllyPieces()) {
            $ally = Ally::FromUniqueId($allies[$i+5]);
            if(ArenaContains($allies[$i], "Ground", $ally)) {
              if($groundAllies != "") $groundAllies .= ",";
              $groundAllies .= $i;
            }
          }
          $rv = $subparam . "-" . $groundAllies;
          break;
        case "ALLTHEIRSPACEUNITSMULTI":
          $allies = &GetAllies($player == 1 ? 2 : 1);
          $spaceAllies = "";
          $spaceCount = 0;
          for($i = 0; $i < count($allies); $i+=AllyPieces()) {
            $ally = Ally::FromUniqueId($allies[$i+5]);
            if(ArenaContains($allies[$i], "Space", $ally)) {
              if($spaceAllies != "") $spaceAllies .= ",";
              $spaceAllies .= $i;
              $spaceCount++;
            }
          }
          $rv = $spaceCount . "-" . $spaceAllies;
          break;
        case "ALLTHEIRSPACEUNITSMULTILIMITED":
          $allies = &GetAllies($player == 1 ? 2 : 1);
          $spaceAllies = "";
          for($i = 0; $i < count($allies); $i+=AllyPieces()) {
            $ally = Ally::FromUniqueId($allies[$i+5]);
            if(ArenaContains($allies[$i], "Space", $ally)) {
              if($spaceAllies != "") $spaceAllies .= ",";
              $spaceAllies .= $i;
            }
          }
          $rv = $subparam . "-" . $spaceAllies;
          break;
        case "GYUNITS": $rv = SearchDiscard($player, definedType:"Unit"); break;
        case "GYUNITSTRAIT": $rv = SearchDiscard($player, definedType:"Unit", trait:$subparam); break;
        case "GYTYPE": $rv = SearchDiscard($player, $subparam); break;
        case "GYAA": $rv = SearchDiscard($player, "AA"); break;
        case "GYNAA": $rv = SearchDiscard($player, "A"); break;
        case "GYCLASSAA": $rv = SearchDiscard($player, "AA", "", -1, -1, $subparam); break;
        case "GYCLASSNAA": $rv = SearchDiscard($player, "A", "", -1, -1, $subparam); break;
        case "GYCARD": $rv = SearchDiscardForCard($player, $subparam); break;
        case "WEAPON": $rv = WeaponIndices($player, $player, $subparam); break;
        // case "HEAVE": $rv = HeaveIndices(); break;//FAB
        //case "AURACLASS": $rv = SearchAura($player, "", "", -1, -1, $subparam); break;//FAB
        case "DECKAURAMAXCOST": $rv = SearchDeck($player, "", "Aura", $subparam); break;
        // case "QUELL": $rv = QuellIndices($player); break;//FAB
        case "MZLASTHAND":
          $hand = &GetHand($player);
          if(count($hand) > 0) $rv = "MYHAND-" . count($hand) - HandPieces();
          break;
        default: $rv = ""; break;
      }
      return ($rv == "" ? "PASS" : $rv);
    case "MULTIZONEINDICES":
      $rv = SearchMultizone($player, $parameter);
      return ($rv == "" ? "PASS" : $rv);
    case "MZMYDECKTOPX":
      $deck = &GetDeck($player);
      $rv = "";
      for($i=0; $i<$parameter; ++$i) {
        if($rv != "") $rv .= ",";
        $rv .= "MYDECK-" . $i*DeckPieces();
      }
      return ($rv == "" ? "PASS" : $rv);
    // case "PUTPLAY"://FAB
    //   $subtype = CardSubType($lastResult);
    //   if($subtype == "Item") {
    //     PutItemIntoPlayForPlayer($lastResult, $player, ($parameter != "-" ? $parameter : 0));
    //   }
    //   else if(IsAlly($lastResult))
    //   {
    //     PlayAlly($lastResult, $player);
    //     PlayAbility($lastResult, "-", 0);
    //   }
    //   else {
    //     PlayAura($lastResult, $player);
    //   }
    //   return $lastResult;
    case "CREATECLONETROOPER":
      return CreateCloneTrooper($player, $parameter);
    case "CREATEBATTLEDROID":
      return CreateBattleDroid($player, $parameter);
    case "CREATEXWING":
      return CreateXWing($player, $parameter);
    case "CREATETIEFIGHTER":
      return CreateTieFighter($player, $parameter);
    case "PLAYALLY":
      $params = explode(";", $parameter);
      $subCards = "-";
      $from = "-";
      $owner = null;
      $cloned = false;
      $playAbility = false;
      for ($i = 0; $i < count($params); $i++) {
        $param = explode("=", $params[$i]);
        switch ($param[0]) {
          case "subCards": $subCards = $param[1]; break;
          case "from": $from = $param[1]; break;
          case "owner": $owner = $param[1]; break;
          case "cloned": $cloned = in_array($param[1], [1, "true"]); break;
          case "playAbility": $playAbility = in_array($param[1], [1, "true"]); break;
          default: break;
        }
      }
      PlayAlly($lastResult, $player, $subCards, $from, $owner, $cloned, $playAbility);
      return $lastResult;
    case "DRAW":
      $isMainPhase = $parameter != 0;
      return Draw($player, $isMainPhase);
    case "DISCARD":
      return PummelHit($player);
    case "MAYDISCARD":
      return PummelHit($player, may: true);
    case "EXHAUSTRESOURCES":
      if(NumResourcesAvailable($player) >= $parameter) {
        for($i = 0; $i < $parameter; ++$i) {
          ExhaustResource($player);
        }
        return 1;
      }
      return "PASS";
    case "REMOVECOMBATCHAIN":
      $cardID = $combatChain[$lastResult];
      RemoveCombatChain($lastResult);
      return $cardID;
    // case "COMBATCHAINPOWERMODIFIER"://FAB
    //   CombatChainPowerModifier($lastResult, $parameter);
    //   return $lastResult;
    case "COMBATCHAINDEFENSEMODIFIER":
      if($parameter < 0) {
        $defense = BlockingCardDefense($lastResult);
        if($parameter < $defense * -1) $parameter = $defense * -1;
      }
      $combatChain[$lastResult+6] += $parameter;
      return $lastResult;
    case "COMBATCHAINCHARACTERDEFENSEMODIFIER":
      $character = &GetPlayerCharacter($player);
      $index = FindCharacterIndex($player, $combatChain[$parameter]);
      $character[$index + 4] += $lastResult;
      return $lastResult;
    case "REMOVEMYHAND":
      $hand = &GetHand($player);
      $cardID = $hand[$lastResult];
      unset($hand[$lastResult]);
      $hand = array_values($hand);
      return $cardID;
    case "HANDCARD":
      $hand = &GetHand($player);
      $cardID = $hand[$lastResult];
      return $cardID;
    // case "MULTIBANISHSOUL"://FAB
    //   if(!is_array($lastResult)) $lastResult = explode(",", $lastResult);
    //   for($i = count($lastResult)-1; $i >= 0; --$i) BanishFromSoul($player, $lastResult[$i]);
    //   return $lastResult;
    case "ADDHAND":
      AddPlayerHand($lastResult, $player, "-");
      return $lastResult;
    case "ADDMEMORY":
      AddMemory($lastResult, $player, "HAND", "DOWN");
      return $lastResult;
    case "ADDARSENAL":
      $params = explode("-", $parameter);
      $from = (count($params) > 0 ? $params[0] : "-");
      $facing = (count($params) > 1 ? $params[1] : "DOWN");
      AddArsenal($lastResult, $player, $from, $facing);
      return $lastResult;
    case "TURNARSENALFACEUP":
      $arsenal = &GetArsenal($player);
      $arsenal[$lastResult + 1] = "UP";
      return $lastResult;
    case "REMOVEARSENAL":
      $index = $lastResult;
      $arsenal = &GetArsenal($player);
      $cardToReturn = $arsenal[$index];
      for($i = $index + ArsenalPieces() - 1; $i >= $index; --$i) {
        unset($arsenal[$i]);
      }
      $arsenal = array_values($arsenal);
      return $cardToReturn;
    case "MULTIADDHAND":
      global $CS_CardsDrawn;
      $cards = explode(",", $lastResult);
      $hand = &GetHand($player);
      $log = "";
      for($i = 0; $i < count($cards); ++$i) {
        if($parameter == "1") {
          if($log != "") $log .= ", ";
          if($i != 0 && $i == count($cards) - 1) $log .= "and ";
          $log .= CardLink($cards[$i], $cards[$i]);
        }
        $hand[] = $cards[$i];
        IncrementClassState($player, $CS_CardsDrawn);
        switch($cards[$i]) {
          case "6172986745"://Rey, With Palpatine's Power
            ReyPalpatineLOF($player);
            break;
          default: break;
        }
      }
      if($log != "") WriteLog($log . " added to hand");
      return $lastResult;
    case "MULTIREMOVEHAND":
      $cards = "";
      $hand = &GetHand($player);
      if(!is_array($lastResult)) $lastResult = explode(",", $lastResult);
      for($i = 0; $i < count($lastResult); ++$i) {
        if($cards != "") $cards .= ",";
        $cards .= $hand[$lastResult[$i]];
        unset($hand[$lastResult[$i]]);
      }
      $hand = array_values($hand);
      return $cards;
    case "DESTROYCHARACTER":
      DestroyCharacter($player, $lastResult);
      return $lastResult;
    case "DESTROYEQUIPDEF0":
      $character = &GetPlayerCharacter($defPlayer);
      if(BlockValue($character[$lastResult]) + $character[$lastResult+4] <= 0) {
        WriteLog(CardLink($character[$lastResult], $character[$lastResult]) . " was destroyed");
        DestroyCharacter($defPlayer, $lastResult);
      }
      return "";
    case "CHARFLAGDESTROY":
      $character = &GetPlayerCharacter($player);
      $character[$parameter+7] = 1;
      return $lastResult;
    case "ADDCHARACTEREFFECT":
      $characterEffects = &GetCharacterEffects($player);
      array_push($characterEffects, $lastResult, $parameter);
      return $lastResult;
    case "ADDMZBUFF":
      $lrArr = explode("-", $lastResult);
      $characterEffects = &GetCharacterEffects($player);
      array_push($characterEffects, $lrArr[1], $parameter);
      return $lastResult;
    case "ADDMZUSES":
      $lrArr = explode("-", $lastResult);
      switch($lrArr[0]) {
        case "MYCHAR": case "THEIRCHAR": AddCharacterUses($player, $lrArr[1], $parameter); break;
        case "MYALLY": case "THEIRALLY":
          $ally = new Ally($lastResult, $player);
          $ally->SumNumUses($parameter);
          break;
        default: break;
      }
      return $lastResult;
    case "ATTACKEROP":
      $mzID = AttackerMZID($currentPlayer);
      $type = GetMZType($mzID);
      switch($parameter) {
        case "ADDDURABILITY":
          if($type == "CHAR") {
            $character = &GetPlayerCharacter($currentPlayer);
            ++$character[GetMZIndex($mzID) + 2];
          }
          break;
        default: break;
      }
      return $lastResult;
    case "UIDOP":
      $parameterArr = explode(",", $parameter);
      switch ($parameterArr[0]) {
        case "GETMZINDEX":
          $indexes = explode(",", $lastResult);
          $mzIndexes = [];
          foreach ($indexes as $index) {
            if (is_numeric($index)) {
              $ally = new Ally($index);
              $mzIndexes[] = $ally->MZIndex();
            } else {
              $character = new Character($lastResult);
              $mzIndexes[] = $character->MZIndex();
            }
          }
          return implode(",", $mzIndexes);
        case "REVERTCONTROL": // Revert control of a unit to its owner
          $ally = new Ally($lastResult);
          if (!$ally->Exists() || $ally->Controller() == $ally->Owner()) return "PASS";
          $owner = $ally->Owner();
          AllyTakeControl($owner, $ally->UniqueID());
          WriteLog("Reverted control of " . CardLink($ally->CardID(), $ally->CardID()) . "back to player $owner");
          break;
        case "DEFEATUPGRADE":
          DefeatUpgradeForUniqueID($lastResult, $player);
          break;
        case "BOUNCE":
          $ally = new Ally($lastResult);
          if (!$ally->Exists()) return "PASS";
          MZBounce($ally->Controller(), "MYALLY-" . $ally->Index());
          break;
        case "REST":
          $ally = new Ally($lastResult);
          if (!$ally->Exists()) return "PASS";
          if(!$ally->Exhaust($player != $ally->Controller())) return "PASS";
          break;
      }
      return $lastResult;
    case "MZOP":
      $parameterArr = explode(",", $parameter);
      switch ($parameterArr[0])
      {
        case "CHECKUNIQUECARD":
          $args = explode(",", $lastResult);
          $cardID = $args[0];
          $allyUniqueID = $args[1];
          return CheckUniqueCard($cardID, $allyUniqueID, reportMode:true) ? "YES" : "NO";
        case "FREEZE": MZFreeze($lastResult); break;
        case "GAINCONTROL": MZGainControl($player, $lastResult); break;
        case "GETCARDID": return GetMZCard($player, $lastResult);
        case "GETCARDCOST": return CardCost($lastResult);
        case "GETCARDTITLE":
          $cardTitle = CardTitle($lastResult);
          return GamestateSanitize($cardTitle);
        case "GETCARDINDEX": $mzArr = explode("-", $lastResult); return $mzArr[1];
        case "GETUNIQUEID":
          $mzArr = explode("-", $lastResult);
          if(str_starts_with($mzArr[0], "THEIR")) $zone = &GetMZZone(($player == 1 ? 2 : 1), $mzArr[0]);
          else $zone = &GetMZZone($player, $mzArr[0]);
          switch($mzArr[0]) {
            case "ALLY": case "MYALLY": case "THEIRALLY": return $zone[$mzArr[1] + 5];
            case "BANISH": case "MYBANISH": case "THEIRBANISH": return $zone[$mzArr[1] + 2];
            default: return "-1";
          }
        case "GETHEALTH":
          $ally = new Ally($lastResult);
          return $ally->Health();
        case "GETDAMAGE":
          $ally = new Ally($lastResult);
          return $ally->Damage();
        case "GETARENA": return CardArenas(GetMZCard($player, $lastResult));
        case "BOUNCE": return MZBounce($player, $lastResult);
        case "COLLECTBOUNTIES":
          $mzArr = explode("-", $lastResult);
          $ally = new Ally($lastResult);
          CollectBounties($mzArr[0] == "MYALLY" ? $player : ($player == 1 ? 2 : 1), $ally->CardID(), $ally->UniqueID(), $ally->IsExhausted(), $ally->Owner(), $ally->GetUpgrades());
          return $lastResult;
        case "SINK": MZSink($player, $lastResult); return $lastResult;
        //case "SUPPRESS": MZSuppress($player, $lastResult); return $lastResult;//FAB
        case "REST": MZRest($player, $lastResult, $parameterArr[1] ?? $player); return $lastResult;
        case "HEAL": MZHealAlly($player, $lastResult, $parameterArr[1]); return $lastResult;
        case "READY": {
          $exception = isset($parameterArr[1]) ? $parameterArr[1] == "1" : false;
          MZWakeUp($player, $lastResult, $exception); return $lastResult;
        }
        case "PLAYCARD": return MZPlayCard($player, $lastResult);
        case "ATTACK": return MZAttack($player, $lastResult);
        case "ADDHEALTH": MZAddHealth($player, $lastResult, count($parameterArr) > 1 ? $parameterArr[1] : 1); return $lastResult;
        case "ENDCOMBAT": MZEndCombat($player, $lastResult); return $lastResult;
        case "RESTORE":
          $mzArr = explode("-", $lastResult);
          if($mzArr[0] == "MYCHAR") {
            Restore(count($parameterArr) > 1 ? $parameterArr[1] : 1, $player);
          } else if($mzArr[0] == "THEIRCHAR") {
            Restore(count($parameterArr) > 1 ? $parameterArr[1] : 1, $player == 1 ? 2 : 1);
          } else if($mzArr[0] == "MYALLY") {
            MZHealAlly($player, $lastResult, count($parameterArr) > 1 ? $parameterArr[1] : 1);
          } else if($mzArr[0] == "THEIRALLY") {
            MZHealAlly($player == 1 ? 2 : 1, $lastResult, count($parameterArr) > 1 ? $parameterArr[1] : 1);
          }
          return $lastResult;
        case "CHANGEATTACKTARGET": SetAttackTarget($lastResult); return $lastResult;
        case "MULTIATTACKTARGET":
          if ($lastResult==="Units") {
            $dqVars[0]=str_replace("THEIRCHAR-0,", "", $dqVars[0]);
            DQMultiUnitSelect($player, 2, "THEIRALLY", "to attack", customIndices:$dqVars[0]);
            AddDecisionQueue("MZOP", $player, "PROCESSMULTIATTACKTARGET", 1);
          } else {
            SetAttackTarget("THEIRCHAR-0");
          }
          break;
        case "PROCESSMULTIATTACKTARGET":
          $targets = explode(",", str_replace("THEIRALLY-", "", $dqVars[0]));
          sort($targets, SORT_STRING);
          $targets = implode(",", $targets);
          AddDecisionQueue("PASSPARAMETER", $player, $targets, 1);
          AddDecisionQueue("SETCOMBATCHAINSTATE", $player, $CCS_MultiAttackTargets, 1);
          AddDecisionQueue("PROCESSATTACKTARGET", $player, "MULTI", 1);
          //since we couldn't use the ability names for this, we need to pass 0 to the PlayCard function
          AddDecisionQueue("PASSPARAMETER", $player, 0, 1);
          break;
        case "COMBINEMYANDTHEIRINDICIES"://to be used after "MULTICHOOSEOURUNITS"
          $theirs=$lastResult[0];
          $mine=$lastResult[1];
          $theirs=array_map(function($x) {return "THEIRALLY-$x";}, $theirs);
          $mine=array_map(function($x) {return "MYALLY-$x";}, $mine);
          $lastResult=implode(",", array_merge($theirs, $mine));
          break;
        case "MAPTHEIRINDICES"://to be used after "MULTICHOOSETHEIRUNITS"
          return implode(",", array_map(function($x) {return "THEIRALLY-$x";}, $lastResult));
        case "MAPMYINDICES"://to be used after "MULTICHOOSEUNIT"
          return implode(",", array_map(function($x) {return "MYALLY-$x";}, $lastResult));
        case "MULTIHEAL":
          $targets = explode(",", $lastResult);
          $healedTargets = [];

          foreach ($targets as $target) {
            $targetArr = explode("-", $target);
            $targetHeal = $targetArr[0];
            $targetUniqueID = $targetArr[1];

            $healAmount = 0;
            if (is_numeric($targetUniqueID)) {
              $ally = new Ally($targetUniqueID);
              $currentHealth = $ally->Health();
              $ally->Heal($targetHeal);
              $afterHealth = $ally->Health();
              $healAmount = $afterHealth - $currentHealth;
            } else {
              $targetPlayer = $targetUniqueID[1];
              $currentHealth = GetBaseDamage($targetPlayer);
              Restore($targetHeal, $targetPlayer);
              $afterHealth = GetBaseDamage($targetPlayer);
              $healAmount = $currentHealth - $afterHealth;
            }

            if ($healAmount > 0) {
              $healedTargets[] = $healAmount . "-" . $targetUniqueID;
            }
          }

          if (count($healedTargets) == 0) {
            return "PASS";
          }

          return implode(",", $healedTargets);
        case "DEALMULTIDAMAGE":
          $sourcePlayer = $parameterArr[1];
          $isUnitEffect = $parameterArr[2];
          $isPreventable = $parameterArr[3];
          $unitCardID = $parameterArr[4];
          $targets = explode(",", $lastResult);
          $damagedTargets = [];

          foreach ($targets as $target) {
            $targetArr = explode("-", $target);
            $targetDamage = $targetArr[0];
            $targetUniqueID = $targetArr[1];
            if (is_numeric($targetUniqueID)) {
              $ally = new Ally($targetUniqueID);
              $isEnemeyDamage = $sourcePlayer != $ally->Controller();
              $currentHealth = $ally->Health();
              $destroyed = $ally->DealDamage($targetDamage, enemyDamage:$isEnemeyDamage, fromUnitEffect:$isUnitEffect, preventable:$isPreventable, unitCardID:$unitCardID);
              if ($destroyed || $ally->Health() < $currentHealth) {
                $damagedTargets[] = $targetUniqueID;
              }
            } else {
              DealDamageAsync($targetUniqueID[1], $targetDamage, sourcePlayer:$sourcePlayer);
              $damagedTargets[] = $targetUniqueID;
            }
          }

          if (count($damagedTargets) == 0) {
            return "PASS";
          }

          return implode(",", $damagedTargets);
        case "ASSIGNINDIRECTDAMAGE":
          // Important: use MZOpHelpers.php AssignIndirectDamageBuilder() function for param structure
          $targetPlayer = $lastResult;
          $sourceCardID = $parameterArr[1];
          $sourcePlayer = $parameterArr[2];
          $amount = $parameterArr[3];
          $fromUnitEffect = $parameterArr[4];
          $sourceModifierCardID = $parameterArr[5];

          if ($sourcePlayer != $targetPlayer && $sourceModifierCardID != "") {
            PrependDecisionQueue("MZOP", $sourcePlayer, DealMultiDamageBuilder($sourcePlayer, isUnitEffect:$fromUnitEffect, isPreventable:false));
            PrependDecisionQueue("INDIRECTDAMAGEMULTIZONE", $sourcePlayer, "<-");
            PrependDecisionQueue("SETDQCONTEXT", $sourcePlayer, "Assign " . $amount . " unpreventable damage among their base and units");
            PrependDecisionQueue("PREPENDLASTRESULT", $sourcePlayer, $amount . "-THEIRCHAR-0,");
            PrependDecisionQueue("MULTIZONEINDICES", $sourcePlayer, "THEIRALLY");
            PrependDecisionQueue("OK", $targetPlayer, CardLink($sourceCardID, $sourceCardID) . " deals " . $amount . " indirect damage to you, and your opponent will assign the indirect damage due to the " . CardLink($sourceModifierCardID, $sourceModifierCardID) . ".");
          } else {
            PrependDecisionQueue("MZOP", $targetPlayer, DealMultiDamageBuilder($sourcePlayer, isUnitEffect:$fromUnitEffect, isPreventable:false));
            PrependDecisionQueue("SETDQCONTEXT", $targetPlayer, "Assign " . $amount . " unpreventable damage among your base and units");
            PrependDecisionQueue("INDIRECTDAMAGEMULTIZONE", $targetPlayer, "<-");
            PrependDecisionQueue("PREPENDLASTRESULT", $targetPlayer, $amount . "-MYCHAR-0,");
            PrependDecisionQueue("MULTIZONEINDICES", $targetPlayer, "MYALLY");
            PrependDecisionQueue("OK", $targetPlayer, CardLink($sourceCardID, $sourceCardID) . " deals " . $amount . " indirect damage to you.");
          }
          PrependDecisionQueue("SETDQCONTEXT", $targetPlayer, "Indirect Damage");

          return $lastResult;
        case "DEALDAMAGE":
          // Important: use MZOpHelpers.php DamageStringBuilder() function for param structure
          if($lastResult == "") return "";
          $targetMZIndex = $lastResult;

          // Get mz index from unique ID
          if (is_numeric($lastResult)) {
            $ally = new Ally($lastResult);
            $targetMZIndex = $ally->MZIndex();
          } else if (str_contains("BASE", $lastResult)) {
            $character = new Character($lastResult);
            $targetMZIndex = $character->MZIndex();
          }

          $targetArr = explode("-", $targetMZIndex);
          $targetPlayer = ($targetArr[0] == "MYCHAR" || $targetArr[0] == "MYALLY" ? $player : ($player == 1 ? 2 : 1));
          $sourcePlayer = count($parameterArr) > 2 ? $parameterArr[2] : ($targetPlayer == 1 ? 2 : 1);
          $fromUnitEffect = count($parameterArr) > 3 && (bool)$parameterArr[3];
          $preventable = count($parameterArr) > 4 ? $parameterArr[4] == 1 : 1;
          $unitCardID = count($parameterArr) > 5 ? $parameterArr[5] : "";
          if($targetArr[0] == "MYALLY" || $targetArr[0] == "THEIRALLY") {
            $isAttackTarget = GetAttackTarget() == $targetMZIndex;
            $isAttacker = AttackerMZID($player) == $targetMZIndex;
            $ally = new Ally($targetMZIndex);
            $attackerHasOverwhelm = HasOverwhelm($ally->CardID(), $ally->Controller(), $ally->Index());
            $destroyed = $ally->DealDamage($parameterArr[1],
                enemyDamage:(count($parameterArr) > 2 && $sourcePlayer != $targetPlayer),
                fromUnitEffect: $fromUnitEffect,
                preventable: $preventable,
                unitCardID: $unitCardID);

            if($destroyed) {
              if(($isAttackTarget || $isAttacker) && !$attackerHasOverwhelm) CloseCombatChain();
              return "";
            }
          } else {
            PrependDecisionQueue("TAKEDAMAGE", $targetPlayer, $parameterArr[1]);
            PrependDecisionQueue("PASSPARAMETER", $targetPlayer, "0");
          }
          return $lastResult;
        case "REDUCEHEALTH":
          MZAddHealth($player, $lastResult, count($parameterArr) > 1 ? -1 * $parameterArr[1] : 1); return $lastResult;
        case "DESTROY":
          $ally = new Ally($lastResult);
          $id = $ally->CardID();
          $enemyEffects = count($parameterArr) > 1 ? $parameterArr[1] != $ally->Controller(): true;
          $ally->Destroy($enemyEffects);
          return $id;
        case "DESTROYUNIQUECARD":
          $uniqueCardID = $parameterArr[1];
          $ally = new Ally($lastResult);

          if ($ally->CardID() == $uniqueCardID) {
            $ally->Destroy(false);
          } else {
            $ally->DefeatUpgrade($uniqueCardID);
          }

          return $uniqueCardID;
        case "EXPLOIT":
          global $CS_PlayedWithExploit;
          SetClassState($player, $CS_PlayedWithExploit, 1);
          $exploitedAllies = explode(",", $dqVars[0]);
          if ($exploitedAllies[0] == "-" || $exploitedAllies == [])
          {
            return;
          }
          $numExploits = count($exploitedAllies);
          $exploitingCardID = $parameterArr[1];

          for($i=0; $i<$numExploits; ++$i) {
            AddDecisionQueue("ADDCURRENTEFFECT", $player, "6772128891", 1);//Exploit effect
          }

          for($i=0; $i<$numExploits; ++$i) {
            $ally = new Ally($exploitedAllies[$i]);
            $exploitedUniqueID = $ally->UniqueID();
            AddDecisionQueue("PASSPARAMETER", $player, $exploitedUniqueID, 1);
            AddDecisionQueue("DESTROYALLY", $player, "-", 1);
          }

          if($exploitingCardID == "8655450523") {//Count Dooku (Fallen Jedi)
            $exploitedAlliesPowers = [];
            for($i=0;$i<$numExploits;++$i) {
              $ally = new Ally($exploitedAllies[$i], $player);
              $exploitedAlliesPowers[$i] = $ally->CurrentPower();
            }
            AddLayer("TRIGGER", $player, "8655450523", implode(",", $exploitedAlliesPowers));
          }
          break;
        case "ADDEXPERIENCE":
          $ally = new Ally($lastResult);
          $ally->AttachExperience();
          break;
        case "MULTIADDEXPERIENCE":
          $arr = explode(",", $dqVars[0]);
          for($i = 0; $i < count($arr); ++$i) {
            $mzPieces = explode("-", $arr[$i]);
            $allyPlayer = $mzPieces[0] == "MYALLY" ? $player : ($player == 1 ? 2 : 1);
            $ally = new Ally("MYALLY-" . $mzPieces[1], $allyPlayer);
            $ally->AttachExperience();
          }
          break;
        case "MULTIADDSHIELD":
          $arr = explode(",", $dqVars[0]);
          for($i = 0; $i < count($arr); ++$i) {
            $mzPieces = explode("-", $arr[$i]);
            $index = $mzPieces[1];
            $allyPlayer = $mzPieces[0] == "MYALLY" ? $player : ($player == 1 ? 2 : 1);
            $ally = new Ally("MYALLY-" . $index, $allyPlayer);
            $ally->Attach("8752877738");
          }
          break;
        case "MOVEARENA":
          $ally = new Ally($lastResult);
          $ally->MoveArena($parameterArr[1]);
          break;
        case "MOVEPILOTUNIT":
          global $CS_PlayedAsUpgrade;
          $uniqueIdRecipient = $dqVars[0];
          $pilotUnitToMove = new Ally($lastResult);
          $pilotUpgrade = $pilotUnitToMove->CardID();
          $pilotUnitOwner = $pilotUnitToMove->Owner();
          $turnsInPlay = $pilotUnitToMove->TurnsInPlay();
          RemoveAlly($player, $pilotUnitToMove->Index(), removedFromPlay:false);
          $ally = new Ally($uniqueIdRecipient);
          SetClassState($player, $CS_PlayedAsUpgrade, 1);
          $ally->Attach($pilotUpgrade, $pilotUnitOwner, turnsInPlay: $turnsInPlay);
          break;
        case "MOVEPILOTUPGRADE":
          $attachedAlly = new Ally($dqVars[0]);
          $subcardIsLeader = CardIDIsLeader($lastResult);
          $upgrades = $attachedAlly->GetUpgrades(withMetadata:true);
          [$fromEpicAction, $turnsInPlay] = TupleFirstUpgradeWithCardID($upgrades, $lastResult);
          $attachedAlly->RemoveSubcard($lastResult, moving:true);
          $playedFrom = $fromEpicAction ? "EPICACTION" : "UPGRADE";
          $newUID = PlayAlly($lastResult, $attachedAlly->Owner(), from:$playedFrom, turnsInPlay: $turnsInPlay);
          if($subcardIsLeader) Ally::FromUniqueId($newUID)->Exhaust();
          return $newUID;
        case "FALLENPILOTUPGRADE":
          $params = explode(",", $lastResult);
          $newUID = PlayAlly($params[0], $player, from:"UPGRADE", turnsInPlay:$params[1]);//so far only Luke Skywalker JTL
          $discard = &GetDiscard($player);
          for($i=0; $i<count($discard); $i+=DiscardPieces()) {
            if($discard[$i] == $params[0]) {
              RemoveDiscard($player, $i);
              return $newUID;
            }
          }
          break;
        case "ADDSHIELD":
          $ally = new Ally($lastResult);
          $ally->Attach("8752877738");//Shield Token
          break;
        case "ADDEFFECT":
          $ally = new Ally($lastResult);
          $ally->AddEffect($parameterArr[1]);
          break;
        case "POWER":
          $ally = new Ally($lastResult);
          return $ally->CurrentPower();
        case "ADDDURABILITY":
          $mzArr = explode("-", $lastResult);
          $zone = &GetMZZone($player, $mzArr[0]);
          switch($mzArr[0]) {
            case "CHAR": case "MYCHAR": case "THEIRCHAR": $zone[$mzArr[1] + 2] += $dqVars[0]; return $lastResult;
            default: return $lastResult;
          }
        case "GETUPGRADES":
          $ally = new Ally($lastResult);
          $rv = implode(",", $ally->GetUpgrades());
          return $rv == "" ? "PASS" : $rv;
        case "MOVEUPGRADE":
          //DQVars[0]: mzSource (e.g. MYALLY-0, MYDISCARD-0)
          //DQVars[1]: upgrade card ID
          //LastResult: mzTarget/targetUniqueID (e.g. MYALLY-0, 12)
          $targetAlly = new Ally($lastResult);
          $mzSource = $dqVars[0];
          $upgradeID = $dqVars[1];
          $mzSourceArr = explode("-", $mzSource);
          $upgradeOwnerID = null;
          $sourceCardID = count($parameterArr) > 1 ? $parameterArr[1] : "NA";
          $takesControl = EffectTakesControlOfUpgrade($sourceCardID) && $mzSourceArr[0] == "THEIRALLY";
          [$epicAction, $turnsInPlay] = TupleFirstUpgradeWithCardID($targetAlly->GetUpgrades(withMetadata:true), $upgradeID);

          switch ($mzSourceArr[0]) {
            case "MYALLY": case "THEIRALLY":
              $sourceAlly = new Ally($mzSource);
              $upgradeOwnerID = $sourceAlly->RemoveSubcard($upgradeID, moving: true);
              break;
            case "MYDISCARD": case "THEIRDISCARD":
              MZRemove($player, $mzSource);
              break;
            default: break;
          }
          //temp hack, will revise upgrade filters later
          $upgradeIsPilot = TraitContains($upgradeID, "Pilot", $player);
          if($upgradeID != "5375722883" && $upgradeIsPilot) {
            if(!$targetAlly->CanAddPilot()) {
              WriteLog("Cannot add pilot to " . CardLink($targetAlly->CardID(), $targetAlly->CardID()) . ". Reverting gamestate.");
              RevertGamestate();
              return;
            }
          }
          if($upgradeIsPilot) {
            global $CS_PlayedAsUpgrade;
            SetClassState($player, $CS_PlayedAsUpgrade, 1);
          }
          $targetAlly->Attach($upgradeID, $upgradeOwnerID, $epicAction ?? false, $turnsInPlay ?? 0, $takesControl);
          CheckHealthAllAllies();
          return $lastResult;
        case "GETOWNEDCAPTIVES":
          $ally = new Ally($lastResult);
          $captives = $ally->GetCaptives(true);
          $rv = [];
          for ($i = 0; $i < count($captives); $i += SubcardPieces()) {
            if ($captives[$i+1] == $player) {
              $rv[] = $captives[$i];
            }
          }
          $rv = implode(",", $rv);
          return $rv == "" ? "PASS" : $rv;
        case "GETCAPTIVES":
          $ally = new Ally($lastResult);
          $rv = implode(",", $ally->GetCaptives());
          return $rv == "" ? "PASS" : $rv;
        // case "GETMEMORYCOST"://FAB
        //   $mzArr = explode("-", $lastResult);
        //   $zone = &GetMZZone($player, $mzArr[0]);
        //   return MemoryCost($zone[$mzArr[1]], $player);
        case "TAKECONTROL":
          $uniqueID = AllyTakeControl($player, $lastResult);
          return $uniqueID;
        case "CAPTURE":
          $uniqueID = $parameterArr[1];
          $cardID = GetMZCard($player, $lastResult);
          $otherPlayer = ($player == 1 ? 2 : 1);
          $targetPlayer = str_starts_with($lastResult, "MY") ? $player : $otherPlayer;
          $captured = new Ally($lastResult, $targetPlayer);
          $capturedCardID = $captured->IsCloned() ? "0345124206" : $cardID; //Clone - Replace the cloned card to the original one when being captured
          $ownerId = $captured->Owner();
          if($captured->AvoidsCapture()) {
            WriteLog(CardLink($cardID, $cardID) . " avoided capture.");
            return $cardID;
          }
          if($cardID == "3417125055") { //IG-11
            DestroyAlly($otherPlayer, explode("-", $lastResult)[1]);
            $allies = &GetAllies($player);
            for($i=count($allies)-AllyPieces(); $i>=0; $i-=AllyPieces())
            {
              $ally = new Ally("MYALLY-" . $i, $player);
              if(ArenaContains($ally->CardID(), "Ground", $ally)) $ally->DealDamage(3);
            }
            WriteLog(CardLink($cardID, $cardID) . " resisted capture.");
            return $cardID;
          }

          $capturedCardID = $captured->CardID();
          $capturedUniqueID = $captured->UniqueID();
          $capturedExhausted = $captured->IsExhausted();
          $capturedOwner = $captured->Owner();
          $capturedUpgrades = $captured->GetUpgrades();

          if(IsToken($cardID)) {
            // token unit cannot be captured, when they should they are removed from play but bounties can be collected
            CollectBounties($targetPlayer, $capturedCardID, $capturedUniqueID, $capturedExhausted, $capturedOwner, $capturedUpgrades, capturerUniqueID:$uniqueID);
            RemoveAlly($targetPlayer, explode("-", $lastResult)[1]);
            return $cardID;
          }

          $index = SearchAlliesForUniqueID($uniqueID, $player);
          if($index >= 0) {
            $ally = new Ally("MYALLY-" . $index, $player);
            $ally->AddSubcard($capturedCardID, $ownerId);
          }
          CollectBounties($targetPlayer, $capturedCardID, $capturedUniqueID, $capturedExhausted, $capturedOwner, $capturedUpgrades, capturerUniqueID:$uniqueID);
          MZRemove($player, $lastResult);
          return $cardID;
        case "WRITECHOICE":
          $ally = new Ally($lastResult);
          $player = $ally->Controller();
          $selected = CardLink($ally->CardID(), $ally->CardID());
          $message = LogSelectedTarget($player, $lastResult);
          WriteLog($message);
          return $lastResult;
        case "WRITECHOICEFROMUNIQUE":
            $controller = UnitUniqueIDController($lastResult);
            $controller = $controller != -1 ? $controller : 1;
            $index = SearchAlliesForUniqueID($lastResult, $controller);
            $mzIndex = ($controller == $currentPlayer ? "MYALLY-" . $index : "THEIRALLY-" . $index);
            $ally = new Ally($mzIndex);
            $player = $ally->Controller();
            WriteLog(LogSelectedTarget($player, $mzIndex));
            return $lastResult;
        default: break;
      }
      return $lastResult;
    case "OP":
      $paramArr = explode(",", $parameter);
      $parameter = $paramArr[0];
      switch($parameter)
      {
        case "DESTROYFROZENARSENAL": DestroyFrozenArsenal($player); return "";
        //case "BOOST": return DoBoost($player);//FAB
        case "REMOVECARD":
          if($lastResult == "" || $lastResult == "PASS") return $dqVars[0];
          $cards = explode(",", $dqVars[0]);
          for($i = 0; $i < count($cards); ++$i) {
            if($cards[$i] == $lastResult) {
              unset($cards[$i]);
              $cards = array_values($cards);
              break;
            }
          }
          return implode(",", $cards);
        case "ADDTOPDECKASRESOURCE":
          AddTopDeckAsResource($player);
          return $lastResult;
        case "GETLASTALLYMZ":
          $allies = &GetAllies($player);
          if(count($allies) == 0) return "";
          return "MYALLY-" . count($allies)-AllyPieces();
        case "DISCARDHAND": DiscardHand($player); return $lastResult;
        case "DISCARDRANDOM": DiscardRandom($player, isset($paramArr[1]) ? $paramArr[1] : ''); return $lastResult;
        case "MILL": return Mill($player, $lastResult);
        case "DEFEATUPGRADE":
          $upgradeID = $lastResult;
          $mzArr = explode("-", $dqVars[0]);
          $allyPlayer = $mzArr[0] == "MYALLY" ? $player : ($player == 1 ? 2 : 1);
          $ally = new Ally($dqVars[0], $allyPlayer);
          $ownerId = $ally->DefeatUpgrade($upgradeID);
          if(!IsToken($upgradeID) && !CardIDIsLeader($upgradeID)) AddGraveyard($upgradeID, $ownerId, "PLAY");
          return $lastResult;
        case "BOUNCEUPGRADE":
          $upgradeID = $lastResult;
          if(str_contains($upgradeID, "-")) {
            $upgradeDefinition = explode("-", $upgradeID);
            $upgradeID = $upgradeDefinition[0];
            $ownerId = $upgradeDefinition[1];
            global $myDiscard, $theirDiscard;
            if($ownerId == $player) $graveyard = $myDiscard;
            else $graveyard = $theirDiscard;
            for ($i = 0; $i < count($graveyard); $i += DiscardPieces()) {
              if($graveyard[$i] == $upgradeID) {
                RemoveGraveyard($ownerId, $i);
                break;
              }
            }
          } else {
            $mzArr = explode("-", $dqVars[0]);
            $allyPlayer = $mzArr[0] == "MYALLY" ? $player : ($player == 1 ? 2 : 1);
            $ally = new Ally($dqVars[0], $allyPlayer);
            $ownerId = $ally->DefeatUpgrade($upgradeID);
          }
          if(!IsToken($upgradeID)) AddHand($ownerId, $upgradeID);
          return $lastResult;
        case "RESCUECAPTIVE":
          $captiveID = $lastResult;
          $otherPlayer = ($player == 1 ? 2 : 1);
          if($lastResult == "3401690666" &&GetClassState($otherPlayer, $CS_NumEventsPlayed) == 0 ) AddCurrentTurnEffect("3401690666", $otherPlayer, from:"PLAY"); // Relentless
          $mzArr = explode("-", $dqVars[0]);
          $allyPlayer = $mzArr[0] == "MYALLY" ? $player : ($player == 1 ? 2 : 1);
          $ally = new Ally($dqVars[0], $allyPlayer);
          $ally->RescueCaptive($captiveID);
          return $lastResult;
        case "PLAYCAPTIVE":
          global $CS_AfterPlayedBy;
          $captiveID = $lastResult;
          $otherPlayer = ($player == 1 ? 2 : 1);
          if($lastResult == "3401690666" &&GetClassState($otherPlayer, $CS_NumEventsPlayed) == 0 ) AddCurrentTurnEffect("3401690666", $otherPlayer, from:"PLAY"); // Relentless
          $mzArr = explode("-", $dqVars[0]);
          $allyPlayer = $mzArr[0] == "MYALLY" ? $player : ($player == 1 ? 2 : 1);
          $ally = new Ally($dqVars[0], $allyPlayer);
          $ally->RemoveSubcard($captiveID);
          SetClassState($currentPlayer, $CS_AfterPlayedBy, "PLAYCAPTIVE");
          PlayCardSkipCosts($captiveID, "CAPTIVE");
          return $lastResult;
        case "DISCARDCAPTIVE":
          $captiveID = $lastResult;
          $mzArr = explode("-", $dqVars[0]);
          $allyPlayer = $mzArr[0] == "MYALLY" ? $player : ($player == 1 ? 2 : 1);
          $ally = new Ally($dqVars[0], $allyPlayer);
          $ally->DiscardCaptive($captiveID);
          return $lastResult;
        case "PLAYCARD":
          PlayCard($lastResult, $paramArr[1], -1, -1);
          return $lastResult;
        case "SWAPDQPERSPECTIVE":
          $arr = explode(",", $lastResult);
          $output = "";
          for($i=0; $i<count($arr); ++$i) {
            if($output != "") $output .= ",";
            $mzArr = explode("-", $arr[$i]);
            $output .= ($mzArr[0] == "MYALLY" ? "THEIRALLY" : "MYALLY") . "-" . $mzArr[1];
          }
          return $output;
        case "MZTONORMALINDICES":
          $arr = explode(",", $lastResult);
          $output = "";
          for($i=0; $i<count($arr); ++$i) {
            if($output != "") $output .= ",";
            $mzArr = explode("-", $arr[$i]);
            $output .= $mzArr[1];
          }
          if($output == "") $output = "PASS";
          return $output;
        default: return $lastResult;
      }
    case "FILTER":
      $params = explode("-", $parameter);
      $from = $params[0];
      $relationship = $params[1];//exclude other or include
      $type = isset($params[2]) ? $params[2] : "";
      $compareValue = isset($params[3]) ? $params[3] : "";
      $input = [];
      switch($from)
      {
        case "LastResult": $input = explode(",", $lastResult); for($i=0; $i<count($input); ++$i) $input[$i] = $input[$i] . "-" . $input[$i]; break;
        // case "CombatChain"://FAB
        //   $lastResultArr = explode(",", $lastResult);
        //   for($i=0; $i<count($lastResultArr); ++$i) $input[] = $combatChain[$lastResultArr[$i]+CCOffset($type)] . "-" . $lastResultArr[$i];
        //   break;
        case "Deck":
          $lastResultArr = explode(",", $lastResult);
          $deck = &GetDeck($player);
          for($i=0; $i<count($lastResultArr); ++$i) $input[] = $deck[$lastResultArr[$i] * DeckPieces()] . "-" . $lastResultArr[$i];
          break;
        default: break;
      }
      $output = [];
      for($i=0; $i<count($input); ++$i)
      {
        $inputArr = explode("-", $input[$i]);
        $passFilter = !($relationship == "include");
        switch($type)
        {
          case "type": if(CardType($inputArr[0]) == $compareValue) $passFilter = !$passFilter; break;
          case "subtype": if(SubtypeContains($inputArr[0], $compareValue, $player)) $passFilter = !$passFilter; break;
          case "trait": if(TraitContains($inputArr[0], $compareValue, $player)) $passFilter = !$passFilter; break;
          case "player": if($inputArr[0] == $compareValue) $passFilter = !$passFilter; break;
          case "definedType":
            if(DefinedTypesContains($inputArr[0], $compareValue, $player)) $passFilter = !$passFilter; break;
          case "aspect": if(AspectContains($inputArr[0], $compareValue, $player)) $passFilter = !$passFilter; break;
          case "maxCost": if(CardCost($inputArr[0]) <= $compareValue) $passFilter = !$passFilter; break;
          case "isToken": if(IsToken($inputArr[0])) $passFilter = !$passFilter; break;
          case "isLeader": if(CardIDIsLeader($inputArr[0])) $passFilter = !$passFilter; break;
          case "isUnique": if(CardIsUnique($inputArr[0])) $passFilter = !$passFilter; break;
          default: break;
        }
        if($passFilter) $output[] = $inputArr[1];
      }
      if (count($output) == 0 && !str_starts_with($parameter, "Deck")) {
        WriteLog("Invalid target. Reverting gamestate.");
        RevertGamestate();
      } else if (count($output) == 0) return "PASS";

      return implode(",", $output);
    case "MZFILTER":
      $params = explode("=", $parameter);
      $arr = explode(",", $lastResult);
      $isFilteringAlly = ((count($arr) > 0) && (is_numeric($arr[0]) || str_contains($arr[0], "ALLY")));
      $forUpgradeEligible = $params[0] == "filterUpgradeEligible";
      if($forUpgradeEligible) {
        $params = explode("=", UpgradeFilter($params[1]));
        $paramsCopy = $params;
      }
      $invertedMatching = str_ends_with($params[0], "!");
      $params[0] = rtrim($params[0], "!");
      for($i=count($arr)-1; $i>=0; --$i) {
        $match = false;
        switch($params[0]) {
          case "index": if($arr[$i] == $params[1]) $match = true; break;
          case "uniqueID":
            $mzArr = explode("-", $arr[$i]);
            if ($mzArr[0] == "MYALLY" || $mzArr[0] == "THEIRALLY") {
              $ally = new Ally($arr[$i]);
              $match = $ally->UniqueID() == $params[1];
            }
            break;
          case "trait":
            if(!isset($paramsCopy)) $traitParams = explode("&", implode("=",$params));
            else $traitParams = explode("&", implode("=",$paramsCopy));
            for($j=0; $j<count($traitParams); ++$j) {
              $traitString = str_replace("_", " ", explode("=", $traitParams[$j])[1]);
              if(TraitContains(GetMZCard($player, $arr[$i]), $traitString, $player)) {
                $match = true;
              }
            }
            break;
          case "aspect": if(AspectContains(GetMZCard($player, $arr[$i]), $params[1],$player)) $match = true; break;
          case "definedType": if(DefinedTypesContains(GetMZCard($player, $arr[$i]), $params[1], $player)) $match = true; break;
          case "token":
            $isToken = IsToken(GetMZCard($player, $arr[$i]));
            if($isToken && $params[1] == 1) $match = true;
            if(!$isToken && $params[1] == 0) $match = true;
            break;
          case "maxCost":
            $cardID = str_starts_with($arr[$i], "MY") || str_starts_with($arr[$i], "THEIR") ? GetMZCard($player, $arr[$i]) : $arr[$i];
            if(CardCost($cardID) > $params[1]) $match = true;
            break;
          case "dqVar": // Supports mzIndex or uniqueID (e.g. MYALLY-0,18,THEIRALLY-7,12)
            $mzArr = explode(",", $dqVars[$params[1]]);
            for($j=0; $j<count($mzArr); ++$j) {
              if($mzArr[$j] == "" || $mzArr[$j] == "-") continue;

              if ($isFilteringAlly) {
                $ally = new Ally($arr[$i]);
                $filterAlly = new Ally($mzArr[$j]);
                if($ally->UniqueID() == $filterAlly->UniqueID()) {
                  $match = true;
                  break;
                }
              } else if ($arr[$i] == $mzArr[$j]) {
                  $match = true;
                  break;
              }
            }
            break;
          case "status":
            $mzArr = explode("-", $arr[$i]);
            if($mzArr[0] == "MYALLY" || $mzArr[0] == "THEIRALLY") {
              $ally = new Ally($arr[$i]);
              if($params[1] == 1 && $ally->IsExhausted()) $match = true;
              else if($params[1] == 0 && !$ally->IsExhausted()) $match = true;
            } else if($mzArr[0] == "MYRESOURCES" || $mzArr[0] == "THEIRRESOURCES") {
              $resources = &GetResourceCards($player);
              if($params[1] == 1 && $resources[$mzArr[1]+4] == 1) $match = true;
              else if($params[1] == 0 && $resources[$mzArr[1]+4] != 1) $match = true;
            }
            break;
          case "damaged":
            $mzArr = explode("-", $arr[$i]);
            if($mzArr[0] == "MYALLY" || $mzArr[0] == "THEIRALLY") {
              $ally = new Ally($arr[$i]);
              if($params[1] == 1 && $ally->IsDamaged()) $match = true;
              else if($params[1] == 0 && !$ally->IsDamaged()) $match = true;
            } else if($mzArr[0] == "MYCHAR" || $mzArr[0] == "THEIRCHAR") {
              $health = GetBaseDamage($mzArr[0] == "MYCHAR" ? $player : ($player == 1 ? 2 : 0));
              if($params[1] == 1 && $health > 0) $match = true;
              else if($params[1] == 0 && $health == 0) $match = true;
            }
            break;
          case "canAddPilot":
            $mzArr = explode("-", $arr[$i]);
            if($mzArr[0] == "MYALLY") {
              $ally = new Ally($arr[$i]);
              if($params[1] == 1 && $ally->CanAddPilot()) $match = true;
              else if($params[1] == 0 && !$ally->CanAddPilot()) $match = true;
            }
            break;
          case "hasPilot":
            $mzArr = explode("-", $arr[$i]);
            if($mzArr[0] == "MYALLY" || $mzArr[0] == "THEIRALLY") {
              $ally = new Ally($arr[$i]);
              $hasPilot = $ally->HasPilot();
              if($params[1] == 1 && $hasPilot) $match = true;
              else if($params[1] == 0 && !$hasPilot) $match = true;
            }
            break;
          case "leader":
            $mzArr = explode("-", $arr[$i]);
            if($mzArr[0] == "MYALLY" || $mzArr[0] == "THEIRALLY") {
              $ally = new Ally($arr[$i]);
              $isLeader = $ally->IsLeader();
              if($params[1] == 1 && $isLeader) $match = true;
              else if($params[1] == 0 && !$isLeader) $match = true;
            }
            break;
          case "hasSentinel":
            $mzArr = explode("-", $arr[$i]);
            if($mzArr[0] == "MYALLY" || $mzArr[0] == "THEIRALLY") {
              $ally = new Ally($arr[$i]);
              $hasSentinel = HasSentinel($ally->CardID(), $player, $ally->Index());
              if($params[1] == 1 && $hasSentinel) $match = true;
              else if($params[1] == 0 && !$hasSentinel) $match = true;
            }
            break;
          case "hasExperience":
            $mzArr = explode("-", $arr[$i]);
            if($mzArr[0] == "MYALLY" || $mzArr[0] == "THEIRALLY") {
              $ally = new Ally($arr[$i]);
              $hasXp = $ally->HasExperience();
              if($params[1] == 1 && $hasXp) $match = true;
              else if($params[1] == 0 && !$hasXp) $match = true;
            }
            break;
          case "hasShield":
            $mzArr = explode("-", $arr[$i]);
            if($mzArr[0] == "MYALLY" || $mzArr[0] == "THEIRALLY") {
              $ally = new Ally($arr[$i]);
              $hasShield = $ally->HasShield();
              if($params[1] == 1 && $hasShield) $match = true;
              else if($params[1] == 0 && !$hasShield) $match = true;
            }
            break;
          case "unique":
            $mzArr = explode("-", $arr[$i]);
            if($mzArr[0] == "MYALLY" || $mzArr[0] == "THEIRALLY") {
              $ally = new Ally($arr[$i]);
              $isUnique = CardIsUnique($ally->CardID());
              if($params[1] == 1 && $isUnique) $match = true;
              else if($params[1] == 0 && !$isUnique) $match = true;
            } else {
              $isUnique = CardIsUnique($mzArr[0]);
              if($params[1] == 1 && $isUnique) $match = true;
              elseif($params[1] == 0 && !$isUnique) $match = true;
            }
            break;
          case "turns":
            $mzArr = explode("-", $arr[$i]);
            $paramsArr = explode(">", $params[1]);
            if($mzArr[0] == "MYALLY" || $mzArr[0] == "THEIRALLY") {
              $ally = new Ally($arr[$i]);
              if($ally->TurnsInPlay() > $paramsArr[1]) $match = true;
            }
            break;
          case "numAttacks":
            $mzArr = explode("-", $arr[$i]);
            if($mzArr[0] == "MYALLY" || $mzArr[0] == "THEIRALLY") {
              $ally = new Ally($arr[$i]);
              if($ally->NumAttacks() == $params[1]) $match = true;
            }
            break;
          case "hasCaptives":
            $mzArr = explode("-", $arr[$i]);
            if($mzArr[0] == "MYALLY" || $mzArr[0] == "THEIRALLY") {
              $ally = new Ally($arr[$i]);
              $hasCaptives = count($ally->GetCaptives()) > 0;
              if($params[1] == 1 && $hasCaptives) $match = true;
              else if($params[1] == 0 && !$hasCaptives) $match = true;
            }
            break;
          case "cardID":
            if(!isset($paramsCopy)) $cardIDParams = explode("&", implode("=",$params));
            else $cardIDParams = explode("&", implode("=",$paramsCopy));
            for($j=0; $j<count($cardIDParams); ++$j) {
              $cardIDString = explode("=", $cardIDParams[$j])[1];
              $mzArr = explode("-", $arr[$i]);
              if($mzArr[0] == "MYALLY") {
                $ally = new Ally("MYALLY-" . $mzArr[1], $player);
                if($ally->CardID() == $cardIDString) $match = true;
              } else if($mzArr[0] == "THEIRALLY") {
                $otherPlayer = $player == 1 ? 2 : 1;
                $ally = new Ally("MYALLY-" . $mzArr[1], $otherPlayer);
                if($ally->CardID() == $cardIDString) $match = true;
              }
            }
            break;
          case "cardTitle":
            $mzArr = explode("-", $arr[$i]);
            if($mzArr[0] == "MYALLY" || $mzArr[0] == "THEIRALLY") {
              $ally = new Ally($arr[$i]);
              if($ally->HasTitle($params[1])) $match = true;
            }
            break;
          default: break;
        }
        if($invertedMatching && !$match) unset($arr[$i]);
        else if(!$invertedMatching && $match) unset($arr[$i]);
      }
      $rv = implode(",", $arr);
      return $rv == "" ? "PASS" : $rv;
    case "PASSPARAMETER":
      return $parameter;
    case "DISCARDCARD":
      AddGraveyard($lastResult, $player, $parameter);
      CardDiscarded($player, $lastResult);
      WriteLog(CardLink($lastResult, $lastResult) . " was discarded");
      return $lastResult;
    case "ADDDISCARD":
      $paramArr = explode(",", $parameter);
      $modifier = count($paramArr) > 1 ? $paramArr[1] : "-";
      AddGraveyard($lastResult, $player, $paramArr[0], $modifier);
      return $lastResult;
    case "ADDBOTDECK":
      $deck = &GetDeck($player);
      $deck[] = $lastResult;
      return $lastResult;
    case "MULTIADDDECK":
      $deck = &GetDeck($player);
      $cards = explode(",", $lastResult);
      for($i = 0; $i < count($cards); ++$i) $deck[] = $cards[$i];
      return $lastResult;
    case "MULTIADDTOPDECK":
      $deck = &GetDeck($player);
      $cards = explode(",", $lastResult);
      for($i = 0; $i < count($cards); ++$i) {
        if($parameter == "1") WriteLog(CardLink($cards[$i], $cards[$i]));
        array_unshift($deck, $cards[$i]);
      }
      return $lastResult;
    case "MULTIADDDISCARD":
      $paramArr = explode(",", $parameter);
      $modifier = count($paramArr) > 1 ? $paramArr[1] : "-";
      $deck = &GetDeck($player);
      $cards = explode(",", $lastResult);
      for($i = 0; $i < count($cards); ++$i) {
        AddGraveyard($cards[$i], $player, $paramArr[0], $modifier);
      }
      return $lastResult;
    case "MULTIREMOVEDECK":
      if(!is_array($lastResult)) $lastResult = ($lastResult == "" ? [] : explode(",", $lastResult));
      $cards = "";
      $deck = &GetDeck($player);
      for($i = 0; $i < count($lastResult); ++$i) {
        if($cards != "") $cards .= ",";
        $cards .= $deck[$lastResult[$i]];
        unset($deck[$lastResult[$i]]);
      }
      $deck = array_values($deck);
      return $cards;
    case "PLAYAURA":
      PlayAura($parameter, $player);
      break;
    case "DESTROYALLY":
      $ally = new Ally($lastResult);
      DestroyAlly($player, $ally->Index());
      break;
    case "PARAMDELIMTOARRAY":
      return explode(",", $parameter);
    case "SHUFFLEDECK":
      $deck = &GetDeck($player);
      AddEvent("SHUFFLE", "P" . $player . "DECK");
      $skipSeed = $parameter == "SKIPSEED";
      RandomizeArray($deck, $skipSeed);
      return $lastResult;
    case "EXHAUSTCHARACTER":
      $character = &GetPlayerCharacter($player);
      $character[$parameter+1] = 1;
      return $parameter;
    case "DECKCARDS":
      $indices = explode(",", $parameter);
      $deck = &GetDeck($player);
      $rv = "";
      for($i = 0; $i < count($indices); ++$i) {
        if(count($deck) <= $i) continue;
        if($rv != "") $rv .= ",";
        $rv .= $deck[$i];
      }
      return ($rv == "" ? "PASS" : $rv);
    case "SHOWOPTIONS":
      $params = explode("&", $parameter);
      $cardID = $params[0];
      $options = explode(";", $params[1]);
      $selectedOption = str_replace("_", " ", $options[$lastResult]);
      WriteLog("Selected option for " . CardLink($parameter, $parameter) . " is: $selectedOption");
      return $lastResult;
    case "SHOWMODES":
      if(is_array($lastResult)) $modes = $lastResult;
      else {
        $modes = [];
        $modes[] = $lastResult;
      }
      $text = "";
      if (count($modes) > 0) {
        $fmodes = array_map('FmtKeyword', $modes);
        $text = implode(", ", array_slice($fmodes, 0, count($fmodes) - 1));
        $text = implode(" and ", array($text, end($fmodes)));
        $text = implode(" ", explode("_", $text));
      }
      WriteLog("Selected mode" . (count($modes) > 1 ? "s" : "") . " for " . CardLink($parameter, $parameter) . (count($modes) > 1 ? " are" : " is") . ": " . $text);
      return $lastResult;
    case "REVEALCARDS"://Parameter = FROM
      $cards = (is_array($lastResult) ? implode(",", $lastResult) : $lastResult);
      $revealed = RevealCards($cards, $player);
      if($revealed && $parameter != "-" && SearchCount($cards) == 1) {
        AddEvent("REVEAL", $player . "!" . $parameter . "!" . $cards);
      }
      return ($revealed ? $lastResult : "PASS");
    case "REVEALHANDCARDS":
      $hand = &GetHand($player);
      $cards = "";
      for($i = 0; $i < count($hand); $i += HandPieces()) {
        if($cards != "") $cards .= ",";
        $cards .= $hand[$i];
      }
      RevealCards($cards, $player);
      return $lastResult;
    case "REVEALDECKCARDS":
      $hand = &GetDeck($player);
      $cards = "";
      for($i = 0; $i < count($hand); $i += DeckPieces()) {
        if($cards != "") $cards .= ",";
        $cards .= $hand[$i];
      }
      RevealCards($cards, $player);
      return $lastResult;
    case "WRITELOG":
      WriteLog(implode(" ", explode("_", $parameter)));
      return $lastResult;
    case "ADDIMMEDIATECURRENTEFFECT":
      AddCurrentTurnEffect($parameter, $player, "PLAY");
      return "1";
    case "ADDCURRENTEFFECT":
      AddCurrentTurnEffect($parameter, $player);
      UpdateLinkAttack();
      return $lastResult;
    case "ADDROUNDEFFECT":
      AddRoundEffect($parameter, $player);
      UpdateLinkAttack();
      return $lastResult;
    case "ADDPERMANENTEFFECT":
      AddPermanentEffect($parameter, $player);
      UpdateLinkAttack();
      return $lastResult;
    case "REMOVECURRENTEFFECT":
      SearchCurrentTurnEffects($parameter, $player, true);
      UpdateLinkAttack();
      return $lastResult;
    case "ADDCURRENTANDNEXTTURNEFFECT":
      AddCurrentTurnEffect($parameter, $player);
      UpdateLinkAttack();
      AddNextTurnEffect($parameter, $player);
      return "1";
    case "ADDLIMITEDCURRENTEFFECT":
    case "ADDLIMITEDROUNDEFFECT":
    case "ADDLIMITEDPERMANENTEFFECT":
    case "ADDLIMITEDNEXTTURNEFFECT":
    case "ADDLIMITEDNEXTROUNDEFFECT":
    case "ADDLIMITEDNEXTPERMANENTEFFECT":
      $uniqueID = $lastResult;
      $params = explode(",", $parameter);
      $controller = UnitUniqueIDController($uniqueID);
      $from = "";
      if ($controller == -1) {
        $controller = $player;
      }
      if (isset($params[1])) {
        $from = $params[1];
      }
      if (isset($params[2])) {
        $controller = $params[2]; // Override controller
      }

      $lastingType = 1;
      switch ($phase) {
        case "ADDLIMITEDROUNDEFFECT":
        case "ADDLIMITEDNEXTROUNDEFFECT":
          $lastingType = 2;
          break;
        case "ADDLIMITEDPERMANENTEFFECT":
        case "ADDLIMITEDNEXTPERMANENTEFFECT":
          $lastingType = 3;
          break;
      }

      switch ($phase) {
        case "ADDLIMITEDCURRENTEFFECT":
        case "ADDLIMITEDROUNDEFFECT":
        case "ADDLIMITEDPERMANENTEFFECT":
          AddCurrentTurnEffect($params[0], $controller, $from, $uniqueID, lastingType: $lastingType);
          UpdateLinkAttack();
          break;
        case "ADDLIMITEDNEXTTURNEFFECT":
        case "ADDLIMITEDNEXTROUNDEFFECT":
        case "ADDLIMITEDNEXTPERMANENTEFFECT":
          AddNextTurnEffect($params[0], $controller, $lastResult, lastingType: $lastingType);
          break;
      }

      return $lastResult;
    case "ADDAIMCOUNTER":
      $arsenal = &GetArsenal($player);
      $arsenal[$lastResult+3] += 1;
      return $lastResult;
    case "ADDARSENALCURRENTEFFECT":
      $arsenal = &GetArsenal($player);
      $params = explode(",", $parameter);
      AddCurrentTurnEffect($params[0], $player, $params[1], $arsenal[$lastResult+5]);
      return $lastResult;
    case "OPTX":
      Opt("NA", $parameter);
      return $lastResult;
    case "SETCLASSSTATE":
      $data = is_array($lastResult) ? implode(",", $lastResult) : $lastResult;
      SetClassState($player, $parameter, $data);
      return $lastResult;
    case "GETCLASSSTATE":
      return GetClassState($player, $parameter);
    case "GAINACTIONPOINTS":
      GainActionPoints($parameter, $player);
      return $lastResult;
    case "EQUALPASS":
      if($lastResult == $parameter) return "PASS";
      return $lastResult;
    case "NOTEQUALPASS":
      if($lastResult != $parameter) return "PASS";
      return $lastResult;
    case "PASSIFNOTDEFINEDTYPE":
      if(!DefinedTypesContains($lastResult, $parameter, $player)) return "PASS";
      return $lastResult;
    case "NOPASS":
      if($lastResult == "NO") return "PASS";
      return $lastResult;
    case "YESPASS":
      if($lastResult == "YES") return "PASS";
      return $lastResult;
    case "USETHEFORCE":
      if(HasTheForce($player)) {
        UseTheForce($player);
        return $lastResult;
      }
      return "PASS";
    case "THEFORCEISWITHYOU":
      TheForceIsWithYou($player);
      return $lastResult;
    case "ATTACKEREXISTSORPASS":
      if(!Ally::FromUniqueId($parameter)->Exists()) return "PASS";
      return 1;
    case "CHOICETOPLAYERID":
      if ($lastResult == "Yourself") return $player;
      else if ($lastResult == "Opponent") return $player == 1 ? 2 : 1;
      return "PASS";
    case "NOTSHARETRAITPASS":
      $mzArr = explode("-", $lastResult);
      if(str_starts_with($mzArr[0], "THEIR")) $zone = &GetMZZone($player == 1 ? 2 : 1, $mzArr[0]);
      else $zone = &GetMZZone($player, $mzArr[0]);
      $cardID = $zone[$mzArr[1]];
      return TraitContainsAny($cardID, $parameter) ? $lastResult : "PASS";
    case "NOALLYUNIQUEIDPASS":
      $index = SearchAlliesForUniqueID($parameter, $player);
      if($index == -1) return "PASS";
      return 1;
    case "NUMUSESZEROPASS":
      $numUses = Ally::FromUniqueId($parameter)->NumUses();
      if($numUses == 0) return "PASS";
      return 1;
    case "NULLPASS":
      if($lastResult == "") return "PASS";
      return $lastResult;
    case "ELSE":
      if($lastResult == "PASS") return "0";
      else if($lastResult == "NO") return "NO";
      else return "PASS";
    case "FINDCURRENTEFFECTPASS":
      if(SearchCurrentTurnEffects($parameter, $player)) return "PASS";
      return $lastResult;
    case "LESSTHANPASS":
      if($lastResult < $parameter) return "PASS";
      return $lastResult;
    case "GREATERTHANPASS":
      if($lastResult > $parameter) return "PASS";
      return $lastResult;
    case "EQUIPDEFENSE":
      $char = &GetPlayerCharacter($player);
      $defense = BlockValue($char[$lastResult]) + $char[$lastResult + 4];
      if($defense < 0) $defense = 0;
      return $defense;
    case "ALLCARDTYPEORPASS":
      $cards = explode(",", $lastResult);
      for($i = 0; $i < count($cards); ++$i) {
        if(CardType($cards[$i]) != $parameter) return "PASS";
      }
      return $lastResult;
    case "MZALLCARDTRAITORPASS":
      $cards = explode(",", $lastResult);
      for($i = 0; $i < count($cards); ++$i) {
        $cardID = GetMZCard($player, $cards[$i]);
        if(!TraitContains($cardID, $parameter, $player)) return "PASS";
      }
      return $lastResult;
    case "MZNOCARDASPECTORPASS":
      $cards = explode(",", $lastResult);
      for($i = 0; $i < count($cards); ++$i) {
        $cardID = GetMZCard($player, $cards[$i]);
        if(AspectContains($cardID, $parameter, $player)) return "PASS";
      }
      return $lastResult;
    case "NONECARDTYPEORPASS":
      $cards = explode(",", $lastResult);
      for($i = 0; $i < count($cards); ++$i) {
        if(CardType($cards[$i]) == $parameter) return "PASS";
      }
      return $lastResult;
    case "ALLCARDSUBTYPEORPASS":
      $cards = explode(",", $lastResult);
      for($i = 0; $i < count($cards); ++$i) {
        if(!SubtypeContains($cards[$i], $parameter)) return "PASS";
      }
      return $lastResult;
    case "NONECARDDEFINEDTYPEORPASS":
      $cards = explode(",", $lastResult);
      for($i = 0; $i < count($cards); ++$i) {
        if(DefinedTypesContains($cards[$i], $parameter, $player)) return "PASS";
      }
      return $lastResult;
    // case "ALLCARDELEMENTORPASS"://FAB
    //   $cards = explode(",", $lastResult);
    //   for($i = 0; $i < count($cards); ++$i) {
    //     if(CardElement($cards[$i]) != $parameter) return "PASS";
    //   }
    //   return $lastResult;
    case "ALLCARDSCOMBOORPASS":
      $cards = explode(",", $lastResult);
      for($i = 0; $i < count($cards); ++$i) {
        if(!HasCombo($cards[$i])) return "PASS";
      }
      return $lastResult;
    case "ALLCARDMAXCOSTORPASS":
      $cards = explode(",", $lastResult);
      for($i = 0; $i < count($cards); ++$i) {
        if(CardCost($cards[$i]) > $parameter) return "PASS";
      }
      return $lastResult;
    // case "ALLCARDCLASSORPASS"://FAB
    //   $cards = explode(",", $lastResult);
    //   for($i = 0; $i < count($cards); ++$i) {
    //     if(!ClassContains($cards[$i], $parameter, $player)) return "PASS";
    //   }
    //   return $lastResult;
    case "CLASSSTATEGREATERORPASS":
      $parameters = explode("-", $parameter);
      $state = $parameters[0];
      $threshold = $parameters[1];
      if(GetClassState($player, $state) < $threshold) return "PASS";
      return 1;
    case "LEADERREADYORPASS":
      $char = &GetPlayerCharacter($player);
      if(count($char) < CharacterPieces() + 1 || $char[CharacterPieces() + 1] != 2) return "PASS";
      return 1;
    case "ATTACKMODIFIER":
      $amount = intval($parameter);
      WriteLog($amount);
      $combatChain[5] += $amount;
      return $parameter;
    case "DEALDAMAGE":
      $target = (is_array($lastResult) ? $lastResult : explode("-", $lastResult));
      $targetPlayer = ($target[0] == "MYCHAR" || $target[0] == "MYALLY" ? $player : ($player == 1 ? 1 : 2));
      $parameters = explode("-", $parameter);
      $damage = $parameters[0];
      $source = $parameters[1];
      $type = $parameters[2];
      if($target[0] == "THEIRALLY" || $target[0] == "MYALLY") {
        DealAllyDamage($targetPlayer, $target[1], $damage);
        return $damage;
      } else {
        PrependDecisionQueue("TAKEDAMAGE", $targetPlayer, $parameter);
        PrependDecisionQueue("PASSPARAMETER", $targetPlayer, "0");
      }
      return $damage;
    case "TAKEDAMAGE":
      global $mainPlayer;
      $params = explode("-", $parameter);
      $damage = intval($params[0]);
      $source = (count($params) > 1 ? $params[1] : "-");
      $type = (count($params) > 2 ? $params[2] : "-");
      // if(!CanDamageBePrevented($player, $damage, "DAMAGE")) $lastResult = 0;//FAB
      $damage -= intval($lastResult);
      if($type == "COMBAT" || $type == "OVERWHELM")
      {
        $dqState[6] = $damage;
      }
      $damage = DealDamageAsync($player, $damage, $type, $source, $mainPlayer);
      return $damage;
    // case "AFTERQUELL"://FAB
    //   $maxQuell = GetClassState($player, $CS_MaxQuellUsed);
    //   if($lastResult > 0) WriteLog("Player $player prevented $lastResult damage with Quell", $player);
    //   if($lastResult > $maxQuell) SetClassState($player, $CS_MaxQuellUsed, $lastResult);
    //   return $lastResult;
    // case "SPELLVOIDCHOICES"://FAB
    //   $damage = $parameter;
    //   if($lastResult != "PASS") {
    //     $damage -= $prevented;
    //     if($damage < 0) $damage = 0;
    //     $dqVars[0] = $damage;
    //     //if($damage > 0) CheckSpellvoid($player, $damage);
    //   }
    //   PrependDecisionQueue("INCDQVAR", $player, "1", 1);
    //   return $prevented;
    case "COLLECTBOUNTY":
      $paramArr = explode(",", $parameter);
      $unitCardID = $paramArr[0];
      $bountyCardID = $paramArr[1];
      $isExhausted = $paramArr[2];
      $owner = $paramArr[3];
      $capturerUniqueID = $paramArr[4];
      CollectBounty($player, $unitCardID, $bountyCardID, $isExhausted, $owner, reportMode:false, capturerUniqueID:$capturerUniqueID);
      return $lastResult;
    // case "ARCANECHOSEN"://FAB
    //   if($lastResult > 0) {
    //     if(SearchCharacterActive($player, "UPR166")) {
    //       $char = &GetPlayerCharacter($player);
    //       $index = FindCharacterIndex($player, "UPR166");
    //       if($char[$index+2] < 4 && GetClassState($player, $CS_AlluvionUsed) == 0) {
    //         ++$char[$index+2];
    //         SetClassState($player, $CS_AlluvionUsed, 1);
    //       }
    //     }
    //   }
    //   return $lastResult;
    // case "TAKEARCANE"://FAB
    //   $parameters = explode("-", $parameter);
    //   $damage = $parameters[0];
    //   $source = $parameters[1];
    //   $playerSource = $parameters[2];
    //   if(!CanDamageBePrevented($player, $damage, "ARCANE")) $lastResult = 0;
    //   $damage = DealDamageAsync($player, $damage - $lastResult, "ARCANE", $source);
    //   if($damage < 0) $damage = 0;
    //   if($damage > 0) IncrementClassState($playerSource, $CS_ArcaneDamageDealt, $damage);
    //   include "MenuFiles/ParseGamefile.php";
    //   WriteLog(FmtPlayer($playerName, $player) . " took $damage arcane damage from " . CardLink($source, $source), $player);
    //   if(DelimStringContains(CardSubType($source), "Ally") && $damage > 0) ProcessDealDamageEffect($source); // Interaction with Burn Them All! + Nekria
    //   $dqVars[0] = $damage;
    //   return $damage;
    case "PAYRESOURCES":
      $paramArr = explode(",", $parameter);
      $skipChoice = count($paramArr) > 1 && $paramArr[1] == 1;
      $numResources = $paramArr[0];
      if($skipChoice == 1) { //Skip choice
        $resourceCards = &GetResourceCards($currentPlayer);
        for($i = 0; $i < count($resourceCards); $i += ResourcePieces()) {
          if($numResources == 0) break;
          if($resourceCards[$i+4] == "0") {
            $resourceCards[$i+4] = "1";
            --$numResources;
          }
        }
      } else for($i = 0; $i < $numResources; ++$i) {
        PrependDecisionQueue("MZOP", $player, "REST", 1);
        PrependDecisionQueue("MAYCHOOSEMULTIZONE", $player, "<-", 1);
        PrependDecisionQueue("SETDQCONTEXT", $player, "Choose a resource to exhaust");
        PrependDecisionQueue("MZFILTER", $player, "status=1");
        PrependDecisionQueue("MULTIZONEINDICES", $player, "MYRESOURCES");
      }
      return $parameter;
    case "ADDCLASSSTATE":
      $parameters = explode("-", $parameter);
      IncrementClassState($player, $parameters[0], $parameters[1]);
      return 1;
    case "SUBTRACTCLASSSTATE":
      $parameters = explode("-", $parameter);
      DecrementClassState($player, $parameters[0], $parameters[1]);
      return $lastResult;
    case "APPENDCLASSSTATE":
      $parameters = explode("-", $parameter);
      AppendClassState($player, $parameters[0], $parameters[1]);
      return $lastResult;
    case "SUBPITCHVALUE":
      return $parameter - 1;
    case "LASTARSENALADDEFFECT":
      $params = explode(",", $parameter);
      $arsenal = &GetArsenal($player);
      if(count($arsenal) > 0 && count($params) == 2) AddCurrentTurnEffect($params[0], $player, $params[1], $arsenal[count($arsenal) - ArsenalPieces() + 5]);
      return $lastResult;
    case "PROCESSATTACKTARGET":
      if($parameter==="MULTI") {
        $targets=explode(",", $combatChainState[$CCS_MultiAttackTargets]);
        $lastResult="THEIRALLY-$targets[0]";
      }
      $combatChainState[$CCS_AttackTarget] = $lastResult;
      $mzArr = explode("-", $lastResult);
      $zone = &GetMZZone($defPlayer, $mzArr[0]);
      $uid = "-";
      switch($mzArr[0])
      {
        case "MYALLY": case "THEIRALLY": $uid = $zone[$mzArr[1]+5]; break;
        case "MYAURAS": case "THEIRAURAS": $uid = $zone[$mzArr[1]+6]; break;
        default: break;
      }
      $combatChainState[$CCS_AttackTargetUID] = $uid;
      WriteLog(LogSelectedTarget($defPlayer, $lastResult, true));
      return 1;
    case "STARTTURNABILITIES":
      StartTurnAbilities();
      return 1;
    case "ENDTURN":
      global $mainPlayer, $initiativePlayer, $currentRound, $initiativeTaken, $currentTurnEffects, $nextTurnEffects;

      // Log end turn stats
      LogEndTurnStats($mainPlayer);

      // Swap turn effects
      SwapTurnEffects();

      // Unset turn modifiers
      UnsetTurnModifiers();

      // Switch initiative
      $mainPlayer = $initiativePlayer == 1 ? 2 : 1;
      $initiativeTaken = 0;
      $currentRound += 1;
      WriteLog("<span style='color:#6E6DFF;'>A new round has begun</span>");
      return 1;
    case "REMOVEPHASEEFFECTS":
      RemovePhaseEffects();
      return 1;
    case "BACKUPSTARTTURN":
      BackupStartTurn();
      return 1;
    case "RESUMEROUNDPASS":
      ResumeRoundPass();
      return 1;
    case "DRAWTOINTELLECT":
      $deck = &GetDeck($player);
      $hand = &GetHand($player);
      $char = &GetPlayerCharacter($player);
      for($i = 0; $i < CharacterIntellect($char[0]); ++$i) {
        $hand[] = array_shift($deck);
      }
      return 1;
    case "ROLLDIE":
      $roll = RollDie($player, true, $parameter == "1");
      return $roll;
    case "SETCOMBATCHAINSTATE":
      $combatChainState[$parameter] = $lastResult;
      return $lastResult;
    case "BANISHADDMODIFIER":
      $banish = &GetBanish($player);
      $banish[$lastResult + 1] = $parameter;
      return $lastResult;
    case "SETLAYERTARGET":
      global $layers, $CS_LayerTarget;
      $target = $lastResult;
      $targetArr = explode("-", $target);
      if($targetArr[0] == "LAYER") $target = "LAYERUID-" . $layers[intval($targetArr[1]) + 6];
      for($i=0; $i<count($layers); $i+=LayerPieces())
      {
        if($layers[$i] == $parameter)
        {
          $layers[$i+3] = $target;
        }
      }
      SetClassState($player, $CS_LayerTarget, $target);
      return $lastResult;
    case "SHOWSELECTEDTARGET":
      if(str_starts_with($lastResult, "THEIR")) {
        $otherP = ($player == 1 ? 2 : 1);
        WriteLog(GetMZCardLink($otherP, $lastResult) . " was targeted");
      } else {
        WriteLog(GetMZCardLink($player, $lastResult) . " was targeted");
      }
      return $lastResult;
    case "MULTIZONEFORMAT":
      return SearchMultizoneFormat($lastResult, $parameter);
    case "MULTIZONETOKENCOPY":
      $mzArr = explode("-", $lastResult);
      $source = $mzArr[0];
      $index = $mzArr[1];
      switch($source) {
        case "MYAURAS": TokenCopyAura($player, $index); break;
        default: break;
      }
      return $lastResult;
    case "COUNTITEM":
      return CountItem($parameter, $player);
    // case "FINDANDDESTROYITEM"://FAB
    //   $mzArr = explode("-", $parameter);
    //   $cardID = $mzArr[0];
    //   $number = $mzArr[1];
    //   for($i = 0; $i < $number; ++$i) {
    //     $index = GetItemIndex($cardID, $player);
    //     if($index != -1) DestroyItemForPlayer($player, $index);
    //   }
    //   return $lastResult;
    case "COUNTPARAM":
      $array = explode(",", $parameter);
      return count($array) . "-" . $parameter;
    case "VALIDATEALLSAMENAME":
      if($parameter == "DECK") {
        $zone = &GetDeck($player);
      }
      if(count($lastResult) == 0) return "PASS";
      $name = CardName($zone[$lastResult[0]]);
      for($i = 1; $i < count($lastResult); ++$i) {
        if(CardName($zone[$lastResult[$i]]) != $name) {
          WriteLog("You selected cards that do not have the same name. Reverting gamestate prior to that effect.");
          RevertGamestate();
          return "PASS";
        }
      }
      return $lastResult;
    case "GETITEMBYINDEX": // Get item by index, separated by comma. If the index is negative, it will be counted from the end of the array (e.g. -1 is the last item).
      if ($lastResult == "PASS" || $lastResult == "") return "PASS";
      $items = explode(",", $lastResult);
      $index = (int) $parameter;
      if ($index < 0) {
        $index = count($items) + $index;
      }
      if ($index >= count($items)) {
        return "PASS";
      }
      return $items[$index];
    case "PREPENDLASTRESULT":
      $rv = $lastResult == "PASS" ? $parameter : $parameter . $lastResult;
      $rv = rtrim($rv, ",");
      return $rv;
    case "APPENDLASTRESULT":
      return $lastResult . $parameter;
    case "CLEANEMPTYINDICES":
      $indices = explode(",", $lastResult);
      $rv = "";
      for($i = 0; $i < count($indices); ++$i) {
        if($rv != "") $rv .= ",";
        $mzPieces = explode("-", $indices[$i]);
        if($mzPieces[1] == "") continue;
        $rv .= $indices[$i];
      }
      return $rv;
    case "LASTRESULTPIECE":
      $pieces = explode("-", $lastResult);
      return $pieces[$parameter];
    case "IMPLODELASTRESULT":
      if(!is_array($lastResult)) return $lastResult;
      return ($lastResult == "" ? "PASS" : implode($parameter, $lastResult));
    case "VALIDATECOUNT":
      if(count($lastResult) != $parameter) {
        WriteLog("The count from the last step is incorrect. Reverting gamestate prior to that effect.");
        RevertGamestate();
        return "PASS";
      }
      return $lastResult;
    case "MODDEFCOUNTER":
      if($lastResult == "") return $lastResult;
      $character = &GetPlayerCharacter($player);
      $character[$lastResult+4] = intval($character[$lastResult+4]) + $parameter;
      if($parameter < 0) WriteLog(CardLink($character[$lastResult], $character[$lastResult]) . " got a negative defense counter");
      return $lastResult;
    case "AFTERRESOURCE":
      if($lastResult != "PASS")
        LogPlayCardStats($player, $lastResult, $parameter, type:"RESOURCED");
      // Reset characters, allies, and resources
      ResetCharacter($player);
      ResetAllies($player);
      ResetResources($player);
      // Trigger abilities
      CharacterEndRegroupPhaseAbilities($player);
      AllyEndRegroupPhaseAbilities($player);
      CurrentEffectEndRegroupPhaseAbilities();
      return $lastResult;
    case "REMOVECOUNTER":
      $character = &GetPlayerCharacter($player);
      $character[$lastResult+2] -= 1;
      WriteLog(CardLink($parameter, $parameter) . " removed a counter from " . CardLink($character[$lastResult], $character[$lastResult]));
      return $lastResult;
    case "APPENDDQVAR":
      if($dqVars[$parameter] == "-") $dqVars[$parameter] = $lastResult;
      else $dqVars[$parameter] .= "," . $lastResult;
      return $lastResult;
    case "SETDQVAR":
      $dqVars[$parameter] = $lastResult;
      return $lastResult;
    case "CLEARDQVAR":
      $dqVars[$parameter] = "";
      return $lastResult;
    case "INCDQVAR":
      $dqVars[$parameter] = intval($dqVars[$parameter]) + intval($lastResult);
      return $lastResult;
    case "DECDQVAR":
      $dqVars[$parameter] = intval($dqVars[$parameter]) - intval($lastResult);
      return $lastResult;
    case "DIVIDE":
      return floor($lastResult / $parameter);
    case "DQVARPASSIFSET":
      if ($dqVars[$parameter] == "1") return "PASS";
      return "PROCEED";
    //case "ADDCARDTOCHAIN"://unused
    //  AddCombatChain($lastResult, $player, $parameter, 0);
    //  return $lastResult;
    case "ATTACKWITHIT":
      PlayCardSkipCosts($lastResult, "DECK");
      return $lastResult;
    case "SETDQCONTEXT":
      $dqState[4] = implode("_", explode(" ", $parameter));
      return $lastResult;
    case "AFTERDIEROLL":
      AfterDieRoll($player);
      return $lastResult;
    case "MODAL":
      return ModalAbilities($player, $parameter, $lastResult);
    case "SETABILITYTYPE":
      global $CS_PlayIndex;
      $lastPlayed[2] = $lastResult;
      $index = GetAbilityIndex($parameter, GetClassState($player, $CS_PlayIndex), $lastResult);
      SetClassState($player, $CS_AbilityIndex, $index);
      if(IsAlly($parameter, $player)) {
        $ally = new Ally("MYALLY-" . GetClassState($player, $CS_PlayIndex), $player);
        if(AllyDoesAbilityExhaust($parameter)) $ally->Exhaust();
      }
      $names = explode(",", GetAbilityNames($parameter, GetClassState($player, $CS_PlayIndex)));
      $ability = implode(" ", explode("_", $names[$index]));
      WriteLog("<b><span style='color:Gray'>{$ability}</span></b> ability was chosen.");
      return $lastResult;
    case "SETABILITYTYPEOPP"://For activating opponent's cards
        global $CS_OppIndex, $CS_OppCardActive, $CS_PlayIndex;
        $lastPlayed[2] = $lastResult;
        $otherPlayer = ($player == 1 ? 2 : 1);
        $index = GetAbilityIndex($parameter, GetClassState($player, $CS_OppIndex), $lastResult, theirCard:true);
        SetClassState($player, $CS_AbilityIndex, $index);
        if(IsAlly($parameter, $otherPlayer) && TheirAllyDoesAbilityExhaust($parameter)) {
          $ally = new Ally("MYALLY-" . GetClassState($player, $CS_PlayIndex), $player);
          $ally->Exhaust();
        }
        $names = explode(",", GetOpponentControlledAbilityNames($parameter));
        $ability = implode(" ", explode("_", $names[$index]));
        WriteLog("<b><span style='color:Gray'>{$ability}</span></b> ability was chosen.");
        return $lastResult;
    case "MZSTARTTURNABILITY":
      MZStartTurnAbility($player, $lastResult);
      return "";
    case "MZDAMAGE":
      $lastResultArr = explode(",", $lastResult);
      $params = explode(",", $parameter);
      for($i = 0; $i < count($lastResultArr); ++$i) {
        $targetMZIndex = explode("-", $lastResultArr[$i]);
        $target = (str_starts_with($targetMZIndex[0], "MY")) ? $player : ($player == 1 ? 2 : 1);
        DamageTrigger($target, $params[0], $params[1], GetMZCard($target, $lastResultArr[$i]));
      }
      return $lastResult;
    case "MZDESTROY":
      return MZDestroy($player, $lastResult);
    case "MZUNDESTROY":
      return MZUndestroy($player, $parameter, $lastResult);
    // case "MZBANISH"://FAB
    //   return MZBanish($player, $parameter, $lastResult);
    case "MZREMOVE":
      return MZRemove($player, $lastResult);
    case "MZDISCARD":
      return MZDiscard($player, $parameter, $lastResult);
    case "MZADDZONE":
      return MZAddZone($player, $parameter, $lastResult);
    case "GAINRESOURCES":
      GainResources($player, $parameter);
      return $lastResult;
    // case "TRANSFORM"://FAB
    //   return "ALLY-" . ResolveTransform($player, $lastResult, $parameter);
    // case "TRANSFORMPERMANENT":
    //   return "PERMANENT-" . ResolveTransformPermanent($player, $lastResult, $parameter);
    // case "TRANSFORMAURA":
    //   return "AURA-" . ResolveTransformAura($player, $lastResult, $parameter);
    case "STARTGAME":
      global $initiativePlayer, $turn, $currentPlayer, $currentRound;
      $secondPlayer = ($initiativePlayer == 1 ? 2 : 1);
      $inGameStatus = "1";
      $MakeStartTurnBackup = true;
      $MakeStartGameBackup = true;

      foreach ([1, 2] as $p) {
        $startingHandSize = 6;
        $base = GetPlayerCharacter($p)[0];

        switch($base) {
          case "1029978899"://Colossus
            $startingHandSize -= 1;
            break;
          case "9586661707"://Nabat Village
            $startingHandSize += 3;
            break;
          default: break;
        }

        for($i=0; $i<$startingHandSize; ++$i) {
          Draw($p, false);
        }
      }

      if(!IsPlayerAI($initiativePlayer) && !PlayerIsUsingNabatVillage($initiativePlayer)) {
        AddDecisionQueue("SETDQCONTEXT", $initiativePlayer, "Would you like to mulligan?");
        AddDecisionQueue("YESNO", $initiativePlayer, "-");
        AddDecisionQueue("NOPASS", $initiativePlayer, "-");
        AddDecisionQueue("MULLIGAN", $initiativePlayer, "-", 1);
      }
      if(!IsPlayerAI($secondPlayer) && !PlayerIsUsingNabatVillage($secondPlayer)) {
        AddDecisionQueue("SETDQCONTEXT", $secondPlayer, "Would you like to mulligan?");
        AddDecisionQueue("YESNO", $secondPlayer, "-");
        AddDecisionQueue("NOPASS", $secondPlayer, "-");
        AddDecisionQueue("MULLIGAN", $secondPlayer, "-", 1);
      }
      CharacterStartActionPhaseAbilities($initiativePlayer);
      CharacterStartActionPhaseAbilities($secondPlayer);
      MZMoveCard($initiativePlayer, "MYHAND", "MYRESOURCES", may:false, context:"Choose a card to resource", silent:true);
      AddDecisionQueue("AFTERRESOURCE", $initiativePlayer, "HAND", 1);
      MZMoveCard($initiativePlayer, "MYHAND", "MYRESOURCES", may:false, context:"Choose a card to resource", silent:true);
      AddDecisionQueue("AFTERRESOURCE", $initiativePlayer, "HAND", 1);
      MZMoveCard($secondPlayer, "MYHAND", "MYRESOURCES", may:false, context:"Choose a card to resource", silent:true);
      AddDecisionQueue("AFTERRESOURCE", $secondPlayer, "HAND", 1);
      MZMoveCard($secondPlayer, "MYHAND", "MYRESOURCES", may:false, context:"Choose a card to resource", silent:true);
      if(PlayerIsUsingNabatVillage($initiativePlayer) && $currentRound == 1) {
        AddDecisionQueue("WRITELOG", $initiativePlayer, "Player $initiativePlayer will put 3 cards to the bottom of their deck");
        MZMoveCard($initiativePlayer, "MYHAND", "MYBOTDECK", context:"Choose a card to put on the bottom of your deck", silent:true);
        MZMoveCard($initiativePlayer, "MYHAND", "MYBOTDECK", context:"Choose a card to put on the bottom of your deck", silent:true);
        MZMoveCard($initiativePlayer, "MYHAND", "MYBOTDECK", context:"Choose a card to put on the bottom of your deck", silent:true);
      }
      if(PlayerIsUsingNabatVillage($secondPlayer) && $currentRound == 1) {
        AddDecisionQueue("WRITELOG", $secondPlayer, "Player $secondPlayer will put 3 cards to the bottom of their deck");
        MZMoveCard($secondPlayer, "MYHAND", "MYBOTDECK", context:"Choose a card to put on the bottom of your deck", silent:true);
        MZMoveCard($secondPlayer, "MYHAND", "MYBOTDECK", context:"Choose a card to put on the bottom of your deck", silent:true);
        MZMoveCard($secondPlayer, "MYHAND", "MYBOTDECK", context:"Choose a card to put on the bottom of your deck", silent:true);
      }
      AddDecisionQueue("AFTERRESOURCE", $secondPlayer, "HAND", 1);
      AddDecisionQueue("STARTTURNABILITIES", $initiativePlayer, "-");
      AddDecisionQueue("SWAPFIRSTTURN", 1, "-");

      return 0;
    case "SWAPFIRSTTURN":
      global $isPass;
      $isPass = true;
      return 0;
    case "SWAPTURN":
      PassTurn();
      return 0;
    case "MULLIGAN":
      WriteLog("Player $player took a mulligan");
      $hand = &GetHand($player);
      $deck = &GetDeck($player);
      for($i=0; $i<count($hand); $i+=HandPieces()) {
        AddBottomDeck($hand[$i], $player);
        PrependDecisionQueue("DRAW", $player, "0");
      }
      $hand = [];
      PrependDecisionQueue("SHUFFLEDECK", $player, "-");
      return 0;
    case "QUICKREMATCH":
      if($lastResult == "YES") {
        $currentTime = round(microtime(true) * 1000);
        SetCachePiece($gameName, 2, $currentTime);
        SetCachePiece($gameName, 3, $currentTime);
        ClearGameFiles($gameName);
        include "MenuFiles/ParseGamefile.php";
        $authKey = $playerID == 1 ? $p1Key : $p2Key;
        header("Location: " . $redirectPath . "/Start.php?gameName=$gameName&playerID=$playerID&authKey=$authKey");
        exit;
      } else {
        WriteLog("Player $player declined the quick rematch");
        CloseDecisionQueue();
        break;
      }
    case "REMATCH":
      global $GameStatus_Rematch, $inGameStatus, $gameName, $gameFileHandler;
      $parsedFormat = GetCurrentFormat();
      if($parsedFormat !== Formats::$PremierStrict && $parsedFormat !== Formats::$PreviewStrict) {
        if($lastResult == "YES") {
          $inGameStatus = $GameStatus_Rematch;
          IncrementCachePiece($gameName, 24);
          ClearGameFiles($gameName);
        } else {
          WriteLog("Player $player declined the rematch");
        }
      } else {
        if($lastResult == "YES") {
          $inGameStatus = $GameStatus_Rematch;
          IncrementCachePiece($gameName, 24);
          ClearGameFiles($gameName);
        } else {
          ConcedeMatch($player);
        }
      }
      return 0;
    case "UNIQUETOMZ":
      return SearchUniqueMultizone($parameter, $player);
    case "PLAYERTARGETEDABILITY":
      PlayerTargetedAbility($player, $parameter, $lastResult);
      return "";
    // case "DQPAYORDISCARD"://FAB
    //   PayOrDiscard($player, $parameter);
    //   return "";
    case "SPECIFICCARD":
      return SpecificCardLogic($player, $parameter, $lastResult);
    case "MZADDSTEAMCOUNTER":
      $lastResultArr = explode(",", $lastResult);
      $otherPlayer = ($player == 1 ? 2 : 1);
      $params = explode(",", $parameter);
      for($i = 0; $i < count($lastResultArr); ++$i) {
        $targetMZIndex = explode("-", $lastResultArr[$i]);
        switch($targetMZIndex[0]) {
          case "MYITEMS":
            $items = &GetItems($player);
            $items[$targetMZIndex[1] + 1 ] += 1;
            WriteLog(CardLink($items[$targetMZIndex[1]], $items[$targetMZIndex[1]]) . " gained a steam counter");
            break;
          default: break;
        }
      }
      return $lastResult;
    case "HITEFFECT":
      ProcessHitEffect($parameter);
      return $parameter;
    case "PASSREVERT":
      if($lastResult == "PASS") {
        WriteLog("That is not a valid action; reverting gamestate.");
        RevertGamestate();
      }
      return $lastResult;
    case "DAMAGEALLOTHERUNITS":
      DamageAllAllies($parameter, "-", except:$lastResult, player:$player);
      return $lastResult;
    case "ALLRANDOMBOTTOM":
      if($lastResult == "PASS") return "";
      $cards = explode(",", $lastResult);
      shuffle($cards);
      for($i=0; $i<count($cards); ++$i) {
        AddBottomDeck($cards[$i], $player);
      }
      AddEvent("SHUFFLE", "P" . $player . "DECK");
      return "";
    case "ATTACK":
      global $CCS_WeaponIndex, $CS_PlayIndex;
      $params = explode(",", $parameter);
      if(CardType($params[0]) == "AA" || GetResolvedAbilityType($params[0], $params[1]) == "AA") {
        $combatChainState[$CCS_WeaponIndex] = GetClassState($player, $CS_PlayIndex);
        Attack($params[0]);
      }
      return $lastResult;
    case "MILL":
      Mill($player, $parameter);
      return "";
    case "CARDDISCARDED":
      CardDiscarded($player, $lastResult);
      return $lastResult;
    case "NEGATE":
      NegateLayer($parameter);
      return "";
    case "MULTIDAMAGE":
      $lastResultArr = explode(",", $lastResult);
      for($i=count($lastResultArr)-1; $i>=0; --$i) {
        AddDecisionQueue("PASSPARAMETER", $player, $lastResultArr[$i]);
        AddDecisionQueue("MZOP", $player, $parameter, 1);
      }
      break;
    case "MULTIDISTRIBUTEDAMAGE":
      //see MZOpHelpers.php MultiDistributeDamageStringBuilder() function for param structure
      if(!is_array($lastResult) && !str_contains($lastResult, "&")) $lastResult = explode(",", $lastResult);
      if(!is_array($parameter)) $parameter = explode(",", $parameter);
      $maxPerTarget = count($parameter) > 2 ? $parameter[2] : 0;
      $sourcePlayer = count($parameter) > 3 ? $parameter[3] : $player;
      $preventable = count($parameter) > 4 ? $parameter[4] : 1;
      $zones = count($parameter) > 5 ? $parameter[5] : "THEIRALLY";
      $mineArr = [];
      if($zones == "OURALLIES") {
        $mineArr = $lastResult[1];
        $lastResult = $lastResult[0];
        $zones = "THEIRALLY";
        $nextZones = "THEIRALLY";
        if(count($lastResult) == 0) {
          $lastResult = $mineArr;
          $zones = "MYALLY";
          $nextZones = "MYALLY";
          $mineArr = [];
        }
      }
      else if(isset($dqVars[2]) && $dqVars[2] != "") {
        $mineArr = explode(",", $dqVars[2]);
        if(!is_numeric($mineArr[0])) $mineArr = [];
      }

      if($parameter[0] == "-") {
        $dqVars[0] = $dqVars[0] - $dqVars[1];
        $parameter[0] = $dqVars[0];
      }
      else {
        $dqVars[0] = $parameter[0];
        if(!$preventable && $lastResult[0] != "BASE") array_unshift($lastResult, "BASE");
      }

      $index = $lastResult[count($lastResult) - 1];
      if($index == null) {
        return;
      }
      if($zones == "MYALLIESANDBASE") {
        if($index == "BASE") {
          $zones = "MYCHAR";
          $index = 0;
        } else {
          $zones = "MYALLY";
        }
      }
      if($zones == "THEIRALLIESANDBASE") {
        if($index == "BASE") {
          $zones = "THEIRCHAR";
          $index = 0;
        } else {
          $zones = "THEIRALLY";
        }
      }
      $allies = &GetAllies($zones == "MYALLY" ? $player : ($player == 1 ? 2 : 1));
      $char = &GetPlayerCharacter($zones == "MYCHAR" ? $player : ($player == 1 ? 2 : 1));
      unset($lastResult[count($lastResult) - 1]);
      $lastResult = array_values($lastResult);
      $damageIndices = GetIndices(($maxPerTarget == 0 ? $parameter[0] : min($parameter[0], $maxPerTarget)) + 1);
      if(!$preventable) {
        $damageIndicesArr = explode(",", $damageIndices);
        if($zones == "MYALLY" || $zones == "THEIRALLY") {
          $ally = new Ally("MYALLY-" . $index, ($player != $sourcePlayer ? $player : ($player == 1 ? 2 : 1)));
          $currentMax = min($ally->Health(), count($damageIndicesArr) - 1);
          $damageIndicesArr = array_slice($damageIndicesArr, 0, $currentMax + 1);
        }
        $shiftTo = ($zones == "MYCHAR" || $zones == "THEIRCHAR") ? count($damageIndicesArr) - 1 : 1;
        for($i = 0; $i < $shiftTo; ++$i) {
          array_shift($damageIndicesArr);
        }
        $damageIndices = implode(",", $damageIndicesArr);
      }
      $nextZones = $zones;
      if(count($lastResult) == 1 && $lastResult[0] == "BASE") {
        $nextZones = $sourcePlayer == $player ? "THEIRCHAR" : "MYCHAR";
      }
      if(count($lastResult) > 0) {
        PrependDecisionQueue("MULTIDISTRIBUTEDAMAGE", $player, "-,$parameter[1],$maxPerTarget,$sourcePlayer,$preventable,$nextZones");
        PrependDecisionQueue("PASSPARAMETER", $player, implode(",", $lastResult));
      }

      if(count($mineArr) > 0) {
        PrependDecisionQueue("SETDQVAR", $player, "2");
        PrependDecisionQueue("PASSPARAMETER", $player, implode(",",$mineArr));
      }
      if($dqVars[0] > 0) {
      $cardLink = str_contains($zones, "CHAR") ? CardLink($char[0], $char[0]) : CardLink($allies[$index], $allies[$index]);
      $dqContext = "Choose an amount of damage to deal to " . $cardLink;
      PrependDecisionQueue("MZOP", $player, "DEALDAMAGE,{1},$sourcePlayer,$parameter[1],$preventable");
      PrependDecisionQueue("PASSPARAMETER", $player, "$zones-" . $index);
      PrependDecisionQueue("SETDQVAR", $player, "1");
      PrependDecisionQueue("BUTTONINPUTNOPASS", $player, $damageIndices);
      PrependDecisionQueue("SETDQCONTEXT", $player, $dqContext);
      }

      if(count($lastResult) == 0 && count($mineArr) > 0) {
        $lastResult = $mineArr;
        unset($mineArr[count($mineArr) - 1]);
        $mineArr = array_values($mineArr);
        if(count($lastResult) > 0) {
          $nextZones = "MYALLY";
          AddDecisionQueue("PASSPARAMETER", $player, implode(",", $mineArr));
          AddDecisionQueue("SETDQVAR", $player, "2");
          AddDecisionQueue("PASSPARAMETER", $player, implode(",", $lastResult));
          AddDecisionQueue("MULTIDISTRIBUTEDAMAGE", $player, "-,$parameter[1],$maxPerTarget,$sourcePlayer,$preventable,$nextZones");
        }
      }
      return $lastResult;
    case "GETLAYERTARGET":
      GetLayerTarget($parameter);
      return $lastResult;
    case "DEPLOYLEADERASUPGRADE":
      LeaderPilotDeploy($player, $parameter, $lastResult);
      return $lastResult;
    case "USEWHENDEFEATED":
      $ally = new Ally($lastResult, $player);
      if ($ally->Exists()) {
        AllyDestroyedAbility($player, $ally->CardID(), $ally->UniqueID(), $ally->LostAbilities(), $ally->IsUpgraded(),
          $ally->GetUpgrades(), $ally->GetUpgrades(true), $ally->CurrentPower(), $ally->Health(), $ally->Owner());
        if(!WhenDefeatedWasUseForceAbility($ally->CardID()))
          CheckThrawnJTL($player, SerializeAllyDestroyData($ally->UniqueID(), $ally->LostAbilities(), $ally->IsUpgraded(),
          $ally->GetUpgrades(), $ally->GetUpgrades(true), $ally->CurrentPower(), $ally->Health(), $ally->Owner()), $ally->CardID());
      }
      break;
    case "USEWHENPLAYED":
      $paramArr = explode(",", $parameter);
      $cardID = $paramArr[0];
      $resourcesPaid = $paramArr[1];
      $target = $paramArr[2];
      $additionalCosts = $paramArr[3];
      $uniqueId = $paramArr[4];
      PlayAbility($cardID, "HAND", $resourcesPaid, $target, $additionalCosts, false, $uniqueId);
      break;
    default:
      return "NOTSTATIC";
  }
}

function AddWhenPlayCardAbilityLayers($cardID, $from, $uniqueID = "-", $resourcesPaid=-1) {
  global $currentPlayer;

  foreach ([1, 2] as $p) {
    $characters = &GetPlayerCharacter($p);
    for ($i = 0; $i < count($characters); $i += CharacterPieces()) {
      if (CharacterHasWhenPlayCardAbility($p, $i, $cardID, $from)) {
        $targetData = implode(",", [$characters[$i], $p, $characters[$i+5], $cardID, $uniqueID]); // $cardID, $player, $numUses, $playedCardID, $uniqueID
        AddLayer("TRIGGER", $currentPlayer, "WHENPLAYCARDABILITY", $targetData, $from, $characters[$i + 3]);
      }
    }

    $allies = &GetAllies($p);
    for ($i = 0; $i < count($allies); $i += AllyPieces()) {
      if (AllyHasWhenPlayCardAbility($cardID, $uniqueID, $from, $allies[$i], $p, $i, $resourcesPaid)) {
        $targetData = implode(",", [$allies[$i], $p, $allies[$i+8], $cardID, $uniqueID]); // $cardID, $player, $numUses, $playedCardID, $uniqueID
        AddLayer("TRIGGER", $currentPlayer, "WHENPLAYCARDABILITY", $targetData, $from, $allies[$i + 5]);
      }
    }
  }
}

function LogSelectedTarget($player, $lastResult, $isAttack = false)
    {
      $targetCard = GetMZCard($player, $lastResult);
      $message = GetMZCardLink($player, $lastResult);
      if (str_contains($lastResult, "ALLY")) {
        $ally = new Ally($lastResult);
        $upgrades = $ally->GetUpgrades();
        if (count($upgrades) > 0) {
          $upgradeLinks = [];
          foreach ($upgrades as $upgrade) {
            $upgradeLinks[] = CardLink($upgrade, $upgrade);
          }
          $message .= " with " . implode(", ", $upgradeLinks);
        }
      }
      if($isAttack) {
        if(strLen($message) == 180){
          $message = "Base was chosen as the attack target";//edge case for the manual bases
        }
        $message .= " was chosen as the attack target";
      } else {
        $message .= " was chosen";
      }
      return $message;
    }