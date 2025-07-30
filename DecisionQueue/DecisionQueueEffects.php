<?php

function ModalAbilities($player, $parameter, $lastResult)
{
  global $combatChain, $defPlayer;
  $paramArr = explode(",", $parameter);
  switch($paramArr[0])
  {
    case "RESTOCK":
      $discardOwner = $player;
      switch($lastResult) {
        case 0://My Discard
          AddDecisionQueue("MULTIZONEINDICES", $player, "MYDISCARD");
          AddDecisionQueue("OP", $player, "MZTONORMALINDICES", 1);
          AddDecisionQueue("PREPENDLASTRESULT", $player, "4-");
          AddDecisionQueue("MULTICHOOSEDISCARD", $player, "<-");
          break;
        case 1://Their Discard
          $discardOwner = $player == 1 ? 2 : 1;
          AddDecisionQueue("MULTIZONEINDICES", $player, "THEIRDISCARD");
          AddDecisionQueue("OP", $player, "MZTONORMALINDICES", 1);
          AddDecisionQueue("PREPENDLASTRESULT", $player, "4-");
          AddDecisionQueue("MULTICHOOSETHEIRDISCARD", $player, "<-");
          break;
      }
      AddDecisionQueue("SPECIFICCARD", $player, "RESTOCK,$discardOwner", 1);
      break;
    case "LUXBONTERI":
      switch($lastResult) {
        case 0: // Ready a unit
          AddDecisionQueue("MULTIZONEINDICES", $player, "MYALLY&THEIRALLY");
          AddDecisionQueue("SETDQCONTEXT", $player, "Choose a unit to ready");
          AddDecisionQueue("MAYCHOOSEMULTIZONE", $player, "<-", 1);
          AddDecisionQueue("MZOP", $player, "READY", 1);
          break;
        case 1: // Exhaust a unit
          AddDecisionQueue("MULTIZONEINDICES", $player, "MYALLY&THEIRALLY");
          AddDecisionQueue("SETDQCONTEXT", $player, "Choose a unit to exhaust");
          AddDecisionQueue("MAYCHOOSEMULTIZONE", $player, "<-", 1);
          AddDecisionQueue("MZOP", $player, "REST", 1);
          break;
        default: break;
      }
      return $lastResult;
    case "K2SO":
      $otherPlayer = ($player == 1 ? 2 : 1);
      switch($lastResult) {
        case 0: // Deal damage
          DealDamageAsync($otherPlayer, 3, "DAMAGE", "3232845719", sourcePlayer:$player);
          break;
        case 1: // Discard a card
          PummelHit($otherPlayer);
          break;
        default: break;
      }
      return $lastResult;
    case "OUTMANEUVER":
      $arena = $lastResult == 0 ? "Space" : "Ground";
      ExhaustAllAllies($arena, 1, $player);
      ExhaustAllAllies($arena, 2, $player);
      return $lastResult;
    case "EZRABRIDGER":
      switch($lastResult) {
        case 0: // Play it
          PrependDecisionQueue("SWAPTURN", $player, "-");
          MZPlayCard($player, "MYDECK-0");
          break;
        case 1: // Discard it
          Mill($player, 1);
          break;
        case 2: // Leave it
          break;
        default: break;
      }
      return $lastResult;
    case "LEIAORGANA":
      switch($lastResult) {
        case 0: // Ready a resource
          ReadyResource($player);
          break;
        case 1: // Exhaust a unit
          AddDecisionQueue("MULTIZONEINDICES", $player, "MYALLY&THEIRALLY");
          AddDecisionQueue("SETDQCONTEXT", $player, "Choose a unit to exhaust");
          AddDecisionQueue("MAYCHOOSEMULTIZONE", $player, "<-", 1);
          AddDecisionQueue("MZOP", $player, "REST", 1);
          break;
        default: break;
      }
      return $lastResult;
    case "BOMBINGRUN":
      $arena = $lastResult == 0 ? "Space" : "Ground";
      DamageAllAllies(3, "7916724925", arena:$arena);
      return 1;
    case "POEDAMERON":
      switch($lastResult) {
        case 0: // Deal damage
          PummelHit($player, may:true, context:"Discard a card to deal 2 damage to a unit or base");
          $otherPlayer = ($player == 1 ? 2 : 1);
          AddDecisionQueue("MULTIZONEINDICES", $player, "MYALLY&THEIRALLY", 1);
          AddDecisionQueue("PREPENDLASTRESULT", $player, "MYCHAR-0,THEIRCHAR-0,", 1);
          AddDecisionQueue("SETDQCONTEXT", $player, "Choose a unit or base to deal 2 damage to", 1);
          AddDecisionQueue("CHOOSEMULTIZONE", $player, "<-", 1);
          AddDecisionQueue("MZOP", $player, "DEALDAMAGE,2,$player", 1);
          break;
        case 1: // Defeat an upgrade
          PummelHit($player, may:true, context:"Discard a card to defeat an upgrade");
          DefeatUpgrade($player, passable:true);
          break;
        case 2: // Opponent discards a card
          PummelHit($player, may:true, context:"Discard a card to force your opponent to discard a card");
          $otherPlayer = ($player == 1 ? 2 : 1);
          PummelHit($otherPlayer, passable:true);
          break;
        default: break;
      }
      return $lastResult;
    case "VIGILANCE":
      switch($lastResult) {
        case 0: // Mill opponent
          $otherPlayer = ($player == 1 ? 2 : 1);
          AddDecisionQueue("MILL", $otherPlayer, "6");
          break;
        case 1: // Heal base
          AddDecisionQueue("PASSPARAMETER", $player, "MYCHAR-0");
          AddDecisionQueue("MZOP", $player, "RESTORE,5");
          break;
        case 2: // Defeat a unit
          AddDecisionQueue("MULTIZONEINDICES", $player, "MYALLY:maxHealth=3&THEIRALLY:maxHealth=3");
          AddDecisionQueue("SETDQCONTEXT", $player, "Choose a unit to defeat");
          AddDecisionQueue("CHOOSEMULTIZONE", $player, "<-", 1);
          AddDecisionQueue("MZOP", $player, "DESTROY,$player", 1);
          break;
        case 3: // Give a Shield token
          AddDecisionQueue("MULTIZONEINDICES", $player, "MYALLY&THEIRALLY");
          AddDecisionQueue("SETDQCONTEXT", $player, "Choose a unit to give a shield");
          AddDecisionQueue("CHOOSEMULTIZONE", $player, "<-", 1);
          AddDecisionQueue("MZOP", $player, "ADDSHIELD", 1);
          break;
        default: break;
      }
      return $lastResult;
    case "COMMAND":
      switch($lastResult) {
        case 0: // Give two experience tokens
          AddDecisionQueue("MULTIZONEINDICES", $player, "MYALLY&THEIRALLY");
          AddDecisionQueue("SETDQCONTEXT", $player, "Choose a unit to give two experience");
          AddDecisionQueue("MAYCHOOSEMULTIZONE", $player, "<-", 1);
          AddDecisionQueue("MZOP", $player, "ADDEXPERIENCE", 1);
          AddDecisionQueue("MZOP", $player, "ADDEXPERIENCE", 1);
          break;
        case 1: // Deal damage
          AddDecisionQueue("MULTIZONEINDICES", $player, "MYALLY");
          AddDecisionQueue("SETDQCONTEXT", $player, "Choose a unit to deal damage equal to it's power");
          AddDecisionQueue("MAYCHOOSEMULTIZONE", $player, "<-", 1);
          AddDecisionQueue("MZOP", $player, "POWER", 1);
          AddDecisionQueue("PREPENDLASTRESULT", $player, "DEALDAMAGE,", 1);
          AddDecisionQueue("SETDQVAR", $player, "0", 1);
          AddDecisionQueue("MULTIZONEINDICES", $player, "THEIRALLY");
          AddDecisionQueue("MZFILTER", $player, "unique=1");
          AddDecisionQueue("MZFILTER", $player, "definedType=Leader");//are leaders not already marked as unique?
          AddDecisionQueue("SETDQCONTEXT", $player, "Choose a unit to damage");
          AddDecisionQueue("MAYCHOOSEMULTIZONE", $player, "<-", 1);
          AddDecisionQueue("MZOP", $player, "{0},$player,1", 1);
          break;
        case 2: // Resource
          $discard = &GetDiscard($player);
          $discardIndex = 0;
          for ($i = count($discard) - 1; $i >= 0; --$i) {
            if ($discard[$i] == "0073206444") { //Command
              $discardIndex = $i;
              break;
            }
          }
          RemoveDiscard($player, $discardIndex);
          AddResources("0073206444", $player, "GY", "DOWN", isExhausted:1); //Command
          break;
        case 3: // Return a unit
          MZMoveCard($player, "MYDISCARD:definedType=Unit", "MYHAND", may:false);
          break;
        default: break;
      }
      return $lastResult;
    case "CUNNING":
      switch($lastResult) {
        case 0: // Return unit
          AddDecisionQueue("MULTIZONEINDICES", $player, "MYALLY:maxAttack=4&THEIRALLY:maxAttack=4");
          AddDecisionQueue("MZFILTER", $player, "leader=1");
          AddDecisionQueue("SETDQCONTEXT", $player, "Choose a unit to return", 1);
          AddDecisionQueue("MAYCHOOSEMULTIZONE", $player, "<-", 1);
          AddDecisionQueue("MZOP", $player, "BOUNCE", 1);
          break;
        case 1: // Buff unit
          AddDecisionQueue("MULTIZONEINDICES", $player, "MYALLY&THEIRALLY");
          AddDecisionQueue("SETDQCONTEXT", $player, "Choose a unit to buff", 1);
          AddDecisionQueue("MAYCHOOSEMULTIZONE", $player, "<-", 1);
          AddDecisionQueue("MZOP", $player, "GETUNIQUEID", 1);
          AddDecisionQueue("ADDLIMITEDCURRENTEFFECT", $player, "3789633661,HAND");
          break;
        case 2: // Exhaust units
          for ($i = 0; $i < 2; $i++) {
            AddDecisionQueue("MULTIZONEINDICES", $player, "MYALLY&THEIRALLY");
            AddDecisionQueue("MZFILTER", $player, "status=1");
            AddDecisionQueue("SETDQCONTEXT", $player, "Choose a unit to exhaust");
            AddDecisionQueue("MAYCHOOSEMULTIZONE", $player, "<-", 1);
            AddDecisionQueue("MZOP", $player, "REST", 1);
          }
          break;
        case 3: // Discard a card
          $otherPlayer = ($player == 1 ? 2 : 1);
          AddDecisionQueue("OP", $otherPlayer, "DISCARDRANDOM,3789633661");
          break;
        default: break;
      }
      return $lastResult;
    case "AGGRESSION":
      switch($lastResult) {
        case 0: // Draw
          Draw($player);
          break;
        case 1: // Defeat upgrades
          DefeatUpgrade($player, may:true);
          DefeatUpgrade($player, may:true);
          break;
        case 2: // Ready a unit
          AddDecisionQueue("MULTIZONEINDICES", $player, "MYALLY:maxAttack=3&THEIRALLY:maxAttack=3");
          AddDecisionQueue("SETDQCONTEXT", $player, "Choose a unit to ready");
          AddDecisionQueue("CHOOSEMULTIZONE", $player, "<-", 1);
          AddDecisionQueue("MZOP", $player, "READY", 1);
          break;
        case 3: // Deal damage
          AddDecisionQueue("MULTIZONEINDICES", $player, "MYALLY&THEIRALLY");
          AddDecisionQueue("SETDQCONTEXT", $player, "Choose a unit to deal 4 damage to");
          AddDecisionQueue("MAYCHOOSEMULTIZONE", $player, "<-", 1);
          AddDecisionQueue("MZOP", $player, "DEALDAMAGE,4,$player", 1);
          break;
        default: break;
      }
      return $lastResult;
    case "LETTHEWOOKIEEWIN":
      switch($lastResult) {
        case 0: // Ready resources
          ReadyResource($player, 6);
          break;
        case 1: // Ready a unit
          AddDecisionQueue("MULTIZONEINDICES", $player, "MYALLY");
          AddDecisionQueue("SETDQCONTEXT", $player, "Choose a unit to attack with");
          AddDecisionQueue("CHOOSEMULTIZONE", $player, "<-", 1);
          AddDecisionQueue("MZOP", $player, "READY", 1);
          AddDecisionQueue("MZALLCARDTRAITORPASS", $player, "Wookiee", 1);
          AddDecisionQueue("MZOP", $player, "ADDEFFECT,7578472075", 1);
          AddDecisionQueue("MZOP", $player, "ATTACK", 1);
          break;
        default: break;
      }
      return $lastResult;
    case "POLITICALPRESSURE":
      switch($lastResult) {
        case 0: // Discard a random card
          DiscardRandom($player, "3357486161");
          break;
        case 1: // Create Battle Droid tokens
          $otherPlayer = ($player == 1 ? 2 : 1);
          CreateBattleDroid($otherPlayer);
          CreateBattleDroid($otherPlayer);
          break;
        default: break;
      }
      return $lastResult;
    case "MANUFACTUREDSOLDIERS":
      switch($lastResult) {
        case 0: // Create Clone Trooper tokens
          CreateCloneTrooper($player);
          CreateCloneTrooper($player);
          break;
        case 1: // Create Battle Droid tokens
          CreateBattleDroid($player);
          CreateBattleDroid($player);
          CreateBattleDroid($player);
          break;
        default: break;
      }
      return $lastResult;
    case "CORVUS":
      switch($lastResult) {
        case 0: // Move Pilot unit to Corvus
          AddDecisionQueue("MULTIZONEINDICES", $player, "MYALLY:trait=Pilot");
          AddDecisionQueue("SETDQCONTEXT", $player, "Choose a Pilot unit to attach");
          AddDecisionQueue("CHOOSEMULTIZONE", $player, "<-", 1);
          AddDecisionQueue("MZOP", $player, "GETUNIQUEID", 1);
          AddDecisionQueue("MZOP", $player, "MOVEPILOTUNIT", 1);
          break;
        case 1: // Move Pilot upgrade to Corvus
          global $dqVars, $CS_PlayedAsUpgrade;
          $uniqueID = $dqVars[0];
          AddDecisionQueue("MULTIZONEINDICES", $player, "MYALLY:hasPilotOnly=1");
          AddDecisionQueue("SETDQCONTEXT", $player, "Choose a unit to move a Pilot from.");
          AddDecisionQueue("CHOOSEMULTIZONE", $player, "<-", 1);
          AddDecisionQueue("SETDQVAR", $player, "0", 1);
          AddDecisionQueue("MZOP", $player, "GETUPGRADES", 1);
          AddDecisionQueue("FILTER", $player, "LastResult-include-trait-Pilot", 1);
          AddDecisionQueue("SETDQCONTEXT", $player, "Choose a pilot upgrade to move.", 1);
          AddDecisionQueue("CHOOSECARD", $player, "<-", 1);
          AddDecisionQueue("SETDQVAR", $player, "1", 1);
          AddDecisionQueue("PASSPARAMETER", $player, "1", 1);
          AddDecisionQueue("SETCLASSSTATE", $player, $CS_PlayedAsUpgrade, 1);
          AddDecisionQueue("PASSPARAMETER", $player, $uniqueID, 1);
          AddDecisionQueue("MZOP", $player, "MOVEUPGRADE", 1);
          break;
        default: break;
      }
      return $lastResult;
    case "YULAREN_JTL":
      $effectType = intval($lastResult);
      $effectName = "3148212344_" . match($effectType) {
        0 => "Grit",
        1 => "Restore_1",
        2 => "Sentinel",
        3 => "Shielded",
      };
      $yularenUniqueID = $paramArr[1];
      AddDecisionQueue("PASSPARAMETER", $player, $yularenUniqueID, 1);
      AddDecisionQueue("ADDLIMITEDPERMANENTEFFECT", $player, "$effectName,HAND," . $player, 1);
      return $yularenUniqueID;
    case "WATTO":
      switch($lastResult) {
        case 0: // Give experience
          AddDecisionQueue("MULTIZONEINDICES", $player, "MYALLY");
          AddDecisionQueue("SETDQCONTEXT", $player, "Choose a unit to give experience");
          AddDecisionQueue("MAYCHOOSEMULTIZONE", $player, "<-", 1);
          AddDecisionQueue("MZOP", $player, "ADDEXPERIENCE", 1);
          break;
        case 1: // Draw a Card
          Draw($player);
          break;
        default: break;
      }
      return $lastResult;
    case "9069308523"://Impossible Escape
      switch($lastResult) {
        case 0://Exhaust a friendly unit
          AddDecisionQueue("MULTIZONEINDICES", $player, "MYALLY");
          AddDecisionQueue("MZFILTER", $player, "status=1");
          AddDecisionQueue("SETDQCONTEXT", $player, "Choose a friendly unit to exhaust");
          AddDecisionQueue("CHOOSEMULTIZONE", $player, "<-", 1);
          AddDecisionQueue("MZOP", $player, "REST", 1);
          break;
        case 1://Use the Force
          UseTheForce($player);
          break;
        default: break;
      }
      AddDecisionQueue("DRAW", $player, "-", 1);
      AddDecisionQueue("MULTIZONEINDICES", $player, "THEIRALLY", 1);
      AddDecisionQueue("SETDQCONTEXT", $player, "Choose an enemy unit to exhaust");
      AddDecisionQueue("CHOOSEMULTIZONE", $player, "<-", 1);
      AddDecisionQueue("MZOP", $player, "REST", 1);
      break;
    case "0033766648"://Shatterpoint
      switch($lastResult) {
        case 0://Defeat 3 or less HP
          MZChooseAndDestroy($player, "MYALLY:maxHealth=3&THEIRALLY:maxHealth=3", filter:"leader=1");
          break;
        case 1://Use the Force to defeat
          if(HasTheForce($player)) {
            UseTheForce($player);
            MZChooseAndDestroy($player, "MYALLY&THEIRALLY", filter:"leader=1");
          }
          break;
        default: break;
      }
      break;
    case "REY_LOF_LEADERDRAW_YODA_TWI":
      $playerHand = GetHand($player);
      $lastHandMZIndex = "MYHAND-" . (count($playerHand) - 1);
      switch($lastResult)
      {
        case 0://Keep without Reveal
          AddDecisionQueue("HANDTOPBOTTOM", $player, "-");
          break;
        case 1://Keep and Reveal
          AddDecisionQueue("MULTIZONEINDICES", $player, "MYHAND");
          AddDecisionQueue("MZFILTER", $player, "index=" . $lastHandMZIndex);
          AddDecisionQueue("SETDQCONTEXT", $player, "Choose a card to put to top or bottom that is not Rey.");
          AddDecisionQueue("CHOOSEMULTIZONE", $player, "<-", 1);
          AddDecisionQueue("MZREMOVE", $player, "-", 1);
          AddDecisionQueue("OPT", $player, "<-");
          ReyPalpatineLOF($player, auto: true);
          break;
        case 2://Put to Top
          AddDecisionQueue("PASSPARAMETER", $player, $lastHandMZIndex, 1);
          AddDecisionQueue("MZADDZONE", $player, "MYTOPDECK", 1);
          AddDecisionQueue("MZREMOVE", $player, "-", 1);
          break;
        case 3://Put to Bottom
          AddDecisionQueue("PASSPARAMETER", $player, $lastHandMZIndex, 1);
          AddDecisionQueue("MZADDZONE", $player, "MYBOTDECK", 1);
          AddDecisionQueue("MZREMOVE", $player, "-", 1);
          break;
      }
      break;
    default: return "";
  }
  //ModalAbilities end
}

function PlayerTargetedAbility($player, $card, $lastResult)
{
  global $dqVars;
  $target = ($lastResult == "Target_Opponent" ? ($player == 1 ? 2 : 1) : $player);
  switch($card)
  {

    default: return $lastResult;
  }
}

function SpecificCardLogic($player, $parameter, $lastResult)
{
  global $dqVars;
  $parameterArr = explode(",", $parameter);
  $card = $parameterArr[0];
  $otherPlayer = $player == 1 ? 2 : 1;
  switch($card)
  {
    case "FORCE_LIGHTNING":
      $numResourcesAvailable = NumResourcesAvailable($player);
      $choices = GetIndices($numResourcesAvailable + 1);//to include 0
      AddDecisionQueue("SETDQCONTEXT", $player, "Choose how many resources to spend (2 damage for each resource)");
      AddDecisionQueue("BUTTONINPUT", $player, $choices, 1);
      AddDecisionQueue("SPECIFICCARD", $player, "FORCE_LIGHTNING_DAMAGE", 1);
      break;
    case "FORCE_LIGHTNING_DAMAGE":
      $numResourcesToExhaust = $lastResult;
      ExhaustResource($player, $numResourcesToExhaust);
      AddDecisionQueue("PASSPARAMETER", $player, $dqVars[0], 1);
      AddDecisionQueue("MZOP", $player, DealDamageBuilder(2*$numResourcesToExhaust, $player), 1);
      break;
    case "SABINEWREN_TWI":
      $card = Mill($player, 1);
      if (!SharesAspect($card, GetPlayerBase($player))) {
        AddDecisionQueue("MULTIZONEINDICES", $player, "MYALLY:arena=Ground&THEIRALLY:arena=Ground");
        AddDecisionQueue("SETDQCONTEXT", $player, "Choose a unit to deal 2 damage");
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $player, "<-", 1);
        AddDecisionQueue("MZOP", $player, "DEALDAMAGE,2,$player,1", 1);
      }
      break;
    case "CAUGHTINTHECROSSFIRE":
      $cardArr = explode(",", $dqVars[0]);
      rsort($cardArr); // Sort the cards by index, with the highest first, to prevent errors caused by index changes after defeat.
      $ally1 = new Ally($cardArr[0]);
      $ally1Power = $ally1->CurrentPower();
      $ally2 = new Ally($cardArr[1]);
      $ally1->DealDamage($ally2->CurrentPower(), fromUnitEffect:true);
      $ally2->DealDamage($ally1Power, fromUnitEffect:true);
      break;
    case "AFINEADDITION":
      switch($lastResult)
      {
        case "My_Hand": AddDecisionQueue("MULTIZONEINDICES", $player, "MYHAND:definedType=Upgrade");
          break;
        case "My_Discard": AddDecisionQueue("MULTIZONEINDICES", $player, "MYDISCARD:definedType=Upgrade");
          break;
        case "Opponent_Discard": AddDecisionQueue("MULTIZONEINDICES", $player, "THEIRDISCARD:definedType=Upgrade");
          break;
      }
      AddDecisionQueue("SETDQCONTEXT", $player, "Choose a card to play");
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $player, "<-");
      AddDecisionQueue("SETDQVAR", $player, "0", 1);
      AddDecisionQueue("ADDCURRENTEFFECT", $player, "7895170711", 1);
      AddDecisionQueue("PASSPARAMETER", $player, "{0}", 1);
      AddDecisionQueue("MZOP", $player, "PLAYCARD", 1);
      break;
    case "CLEARTHEFIELD":
      $ally = new Ally($lastResult);
      $cardTitle = CardTitle($ally->CardID());
      MZBounce($player, $ally->MZIndex());
      $targetCards = SearchAlliesUniqueIDForTitle($otherPlayer, $cardTitle);
      $targetCardsArr = $targetCards ? explode(",", $targetCards) : [];

      for ($i = 0; $i < count($targetCardsArr); ++$i) {
        $targetAlly = new Ally($targetCardsArr[$i]);
        if (!$targetAlly->IsLeader()) {
          MZBounce($player, $targetAlly->MZIndex());
        }
      }
      break;
    case "RESOLUTE":
      $cardID = GetMZCard($player, $lastResult);
      $cardTitle = CardTitle($cardID);
      $targetCards = SearchAlliesUniqueIDForTitle($otherPlayer, $cardTitle);
      $targetCardsArr = explode(",", $targetCards);

      for($i=0; $i<count($targetCardsArr); ++$i) {
        $targetAlly = new Ally($targetCardsArr[$i]);
        $targetAlly->DealDamage(amount:2, enemyDamage:true);
      }
      break;
    case "FORCETHROW"://Force Throw
      $damage = CardCost($lastResult);
      AddDecisionQueue("MULTIZONEINDICES", $player, "MYALLY&THEIRALLY");
      AddDecisionQueue("SETDQCONTEXT", $player, "Choose a unit to deal " . $damage . " damage to");
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $player, "<-", 1);
      AddDecisionQueue("MZOP", $player, "DEALDAMAGE,$damage,$player", 1);
      break;
    case "REINFORCEMENTWALKER":
      if($lastResult == "YES") Draw($player);
      else {
        Mill($player, 1);
        Restore(3, $player);
      }
      break;
    case "OBIWANKENOBI":
      $cardID = GetMZCard($player, $lastResult);
      if(TraitContains($cardID, "Force", $player)) Draw($player);
      break;
    case "GALACTICAMBITION":
      DealDamageAsync($player, CardCost($lastResult), "DAMAGE", "5494760041", sourcePlayer:$player);
      break;
    case "C3PO":
      $deck = new Deck($player);
      WriteLog("Player $player chose number $lastResult.");
      AddDecisionQueue("PASSPARAMETER", $player, $deck->Top());
      AddDecisionQueue("SETDQVAR", $player, 0);
      if(CardCost($deck->Top()) == $lastResult) {
        AddDecisionQueue("SETDQCONTEXT", $player, "Do you want to draw <0>?");
        AddDecisionQueue("YESNO", $player, "-");
        AddDecisionQueue("NOPASS", $player, "-");
        AddDecisionQueue("DRAW", $player, "-", 1);
        AddDecisionQueue("REVEALCARDS", $player, "DECK", 1);
      }
      else {
        AddDecisionQueue("SETDQCONTEXT", $player, "The top card of your deck is <0>");
        AddDecisionQueue("OK", $player, "-");
      }
      break;
    case "FORACAUSEIBELIEVEIN":
      if ($dqVars[0] == '') break;
      $cardArr = explode(",", $dqVars[0]);
      for($i=0; $i<count($cardArr); ++$i) {
        AddGraveyard($cardArr[$i], $player, "DECK");
      }
      break;
    case "EQUALIZE":
      if (HasFewerUnits($player)) {
        $ally = new Ally($lastResult);
        AddDecisionQueue("MULTIZONEINDICES", $player, "MYALLY&THEIRALLY");
        if ($ally->Exists()) {
          AddDecisionQueue("MZFILTER", $player, "index=" . $ally->MZIndex());
        }
        AddDecisionQueue("SETDQCONTEXT", $player, "Choose a unit to give -2/-2", 1);
        AddDecisionQueue("CHOOSEMULTIZONE", $player, "<-", 1);
        AddDecisionQueue("SETDQVAR", $player, 0, 1);
        AddDecisionQueue("MZOP", $player, "GETUNIQUEID", 1);
        AddDecisionQueue("SETDQVAR", $player, 1, 1);
        AddDecisionQueue("ADDLIMITEDCURRENTEFFECT", $player, "5013214638,PLAY", 1);
        AddDecisionQueue("PASSPARAMETER", $player, "{0}", 1);
        AddDecisionQueue("MZOP", $player, "REDUCEHEALTH,2", 1);
      }
      break;
    case "FORCECHOKE":
      $mzArr = explode("-", $lastResult);
      if($mzArr[0] == "MYALLY") Draw($player);
      else Draw($player == 1 ? 2 : 1);
      return $lastResult;
    case "GRANDADMIRALTHRAWN":
      $targetPlayer = ($lastResult == "Yourself" ? $player : ($player == 1 ? 2 : 1));
      $deck = new Deck($targetPlayer);
      if($deck->Reveal()) {
        $cardCost = CardCost($deck->Top());
        AddDecisionQueue("MULTIZONEINDICES", $player, "MYALLY:maxCost=" . $cardCost . "&THEIRALLY:maxCost=" . $cardCost);
        AddDecisionQueue("SETDQCONTEXT", $player, "Choose a card to exhaust");
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $player, "<-", 1);
        AddDecisionQueue("MZOP", $player, "REST", 1);
      }
      break;
    case "THEEMPERORSLEGION":
      $search = SearchDiscard($player, definedType:"Unit", defeatedThisPhase:true);
      if (SearchCount($search) > 0) {
        $indices = explode(",", $search);
        for ($i = count($indices) - 1; $i >= 0; $i--) {
          MZMoveCard($player, "", "MYHAND", mzIndex:"MYDISCARD-" . $indices[$i]);
        }
      }
      break;
    case "YOUREALLCLEARKID":
      $totalEnemySpaceUnits = SearchCount(SearchAllies($otherPlayer, arena:"Space"));
      if ($totalEnemySpaceUnits == 0) {
        AddDecisionQueue("MULTIZONEINDICES", $player, "MYALLY&THEIRALLY");
        AddDecisionQueue("SETDQCONTEXT", $player, "Choose a unit to give an experience token");
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $player, "<-", 1);
        AddDecisionQueue("MZOP", $player, "ADDEXPERIENCE", 1);
      }
      break;
    case "AHSOKATANOJTL":
      if (DefinedTypesContains($lastResult, "Unit")) {
        AddDecisionQueue("MULTIZONEINDICES", $player, "MYALLY&THEIRALLY");
        AddDecisionQueue("MZFILTER", $player, "status=1");
        AddDecisionQueue("SETDQCONTEXT", $player, "Choose a unit to exhaust");
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $player, "<-", 1);
        AddDecisionQueue("MZOP", $player, "REST", 1);
      }
      break;
    case "UWINGREINFORCEMENT":
      $totalCost = 0;
      $cardArr = explode(",", $lastResult);
      if($lastResult == "") $cardArr = [];
      for($i=0; $i<count($cardArr); ++$i) {
        $totalCost += CardCost($cardArr[$i]);
      }
      if($totalCost > 7) {
        WriteLog("<span style='color:red;'>Too many units played. Let's just say we'd like to avoid any Imperial entanglements. Reverting gamestate.</span>");
        RevertGamestate();
        return "";
      }
      $totalUnits = count($cardArr);
      for($i=0; $i<$totalUnits; ++$i) {
        $unitNum = $i + 1;
        AddLayer("TRIGGER", $player, "UWINGPLAYCARD", $cardArr[$i], "$unitNum,$totalUnits");
      }
      $deck = new Deck($player);
      $searchLeftovers = explode(",", $deck->Bottom(true, 10 - count($cardArr)));
      shuffle($searchLeftovers);
      for($i=0; $i<count($searchLeftovers); ++$i) {
        AddBottomDeck($searchLeftovers[$i], $player);
      }
      break;
    case "DARTHVADER":
      $totalCost = 0;
      $cardArr = explode(",", $lastResult);
      if($lastResult == "") $cardArr = [];
      for($i=0; $i<count($cardArr); ++$i) {
        AddCurrentTurnEffect("8506660490", $player);
        PlayCard($cardArr[$i], "DECK");
        $totalCost += CardCost($cardArr[$i]);
      }
      if($totalCost > 3) {
        WriteLog("<span style='color:red;'>Too many units played. I find your lack of faith disturbing. Reverting gamestate.</span>");
        RevertGamestate();
        return "";
      }
      $deck = new Deck($player);
      $searchLeftovers = explode(",", $deck->Bottom(true, 10 - count($cardArr)));
      shuffle($searchLeftovers);
      for($i=0; $i<count($searchLeftovers); ++$i) {
        AddBottomDeck($searchLeftovers[$i], $player);
      }
      break;
    case "POWERFAILURE":
      PrependDecisionQueue("SPECIFICCARD", $player, "POWERFAILURE", 1);
      PrependDecisionQueue("OP", $player, "DEFEATUPGRADE", 1);
      PrependDecisionQueue("MAYCHOOSECARD", $player, "<-", 1);
      PrependDecisionQueue("SETDQCONTEXT", $player, "Choose an upgrade to defeat", 1);
      PrependDecisionQueue("MZOP", $player, "GETUPGRADES", 1);
      PrependDecisionQueue("PASSPARAMETER", $player, "{0}", 1);
      break;
    case "RESTOCK":
      $owner = $parameterArr[1];
      ShuffleToBottomDeck($lastResult, $owner);
      break;
    case "REPROCESS":
      $numCards = count($lastResult);
      ShuffleToBottomDeck($lastResult, $player);
      for($i=0;$i<$numCards;++$i) CreateBattleDroid($player);
      break;
    case "BAMBOOZLE":
      $upgradesReturned = [];
      $controller = MZPlayerID($player, $lastResult);
      $ally = new Ally($lastResult, $controller);
      $upgrades = $ally->GetUpgrades(true);
      for($i=0; $i<count($upgrades); $i+=SubcardPieces()) {
        $ally->RemoveSubcard($upgrades[$i], skipDestroy:true);
        if(!IsToken($upgrades[$i]) && !CardIDIsLeader($upgrades[$i])) AddHand($upgrades[$i+1], $upgrades[$i]);
      }
      if ($ally->Health() <= 0) {
        $ally->Destroy();
      }
      return $lastResult;
    case "JUMPTOLIGHTSPEED":
      $upgradesReturned = [];
      $controller = MZPlayerID($player, $lastResult);
      $ally = new Ally($lastResult, $controller);
      $upgrades = $ally->GetUpgrades(true);
      for($i=0; $i<count($upgrades); $i+=SubcardPieces()) {
        $ally->RemoveSubcard($upgrades[$i], skipDestroy:true);
        if(!IsToken($upgrades[$i]) && !CardIDIsLeader($upgrades[$i])) AddHand($upgrades[$i+1], $upgrades[$i]);
      }
      AddCurrentTurnEffect("5329736697", $player, "EFFECT", $ally->CardID());
      return $lastResult;
    case "SHOOTDOWN":
      $controller = MZPlayerID($player, $lastResult);
      $ally = new Ally($lastResult, $controller);
      $wasDestroyed = $ally->DealDamage(3, enemyDamage:$ally->Controller() != $player);
      if($wasDestroyed) {
        DealDamageAsync($otherPlayer, 2, "DAMAGE", "7730475388", sourcePlayer:$player);
      }
      break;
    case "PIERCINGSHOT":
      $controller = MZPlayerID($player, $lastResult);
      $ally = new Ally($lastResult, $controller);
      foreach ($ally->GetUpgrades(true) as $upgrade) {
        if ($upgrade == "8752877738") { // Shield token
          $ally->DefeatUpgrade($upgrade);
        }
      }
      $ally->DealDamage(3, enemyDamage:$ally->Controller() != $player);
      break;
    case "SUPERHEAVYIONCANNON":
      $controller = MZPlayerID($player, $lastResult);
      $ally = new Ally($lastResult, $controller);
      IndirectDamage("5016817239", $player, $ally->CurrentPower(), true, targetPlayer:$ally->Controller());
      break;
    case "THEANNIHILATOR":
      $otherPlayer = $player == 1 ? 2 : 1;
      $destroyedID = $lastResult;
      $destroyedCardTitle = CardTitle($destroyedID);
      $hand = &GetHand($otherPlayer);
      for($i = count($hand) - 1; $i >= 0; $i -= HandPieces()) {
        if(CardTitle($hand[$i]) == $destroyedCardTitle) {
          WriteLog(CardLink($hand[$i], $hand[$i]) . " was discarded from hand.");
          DiscardCard($otherPlayer, $i);
        }
      }
      $deck = &GetDeck($otherPlayer);
      $deckClass = new Deck($otherPlayer);

      for ($i = count($deck) - 1; $i >= 0; $i -= DeckPieces()) {
        $cardTitle = CardTitle($deck[$i]);
        if ($cardTitle == $destroyedCardTitle) {

          WriteLog(CardLink($deck[$i], $deck[$i]) . " was discarded from deck.");
          AddGraveyard($deck[$i], $otherPlayer, "DECK");
          $deckClass->Remove($i);
        }
      }
      break;
    case "DONTGETCOCKY":
      $deck = new Deck($player);
      $deck->Reveal();
      $card = $deck->Remove(0);
      $dqVars[1] += CardCost($card);
      $deck->Add($card);
      if($dqVars[1] > 7) {
        WriteLog("<span style='color:goldenrod;'>Great Kid, Don't Get Cocky...</span>");
        return "";
      }
      PrependDecisionQueue("MZOP", $player, "DEALDAMAGE," . $dqVars[1] . ",$player", 1);
      PrependDecisionQueue("PASSPARAMETER", $player, $dqVars[0], 1);
      PrependDecisionQueue("ELSE", $player, "-");
      PrependDecisionQueue("SPECIFICCARD", $player, "DONTGETCOCKY", 1);
      PrependDecisionQueue("NOPASS", $player, "-");
      PrependDecisionQueue("YESNO", $player, "-");
      PrependDecisionQueue("SETDQCONTEXT", $player, "Do you want to continue? (Damage: " . $dqVars[1] . ")");
      return $lastResult;
    case "ADMIRALACKBAR":
      $targetAlly = new Ally($lastResult, MZPlayerID($player, $lastResult));
      $damage = SearchCount(SearchAllies($player, arena:$targetAlly->CurrentArena()));
      AddDecisionQueue("PASSPARAMETER", $player, $lastResult);
      AddDecisionQueue("MZOP", $player, DealDamageBuilder($damage,$player,isUnitEffect:1,unitCardID:"0827076106"), 1);
      return $lastResult;
    case "LIGHTSPEEDASSAULT":
      $ally = new Ally($lastResult);
      $currentPower = $ally->CurrentPower();
      $ally->Destroy();
      AddDecisionQueue("MULTIZONEINDICES", $player, "THEIRALLY:arena=Space");
      AddDecisionQueue("SETDQCONTEXT", $player, "Choose an enemy space unit to deal " . $currentPower . " damage to");
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $player, "<-", 1);
      AddDecisionQueue("SETDQVAR", $player, 0, 1);
      AddDecisionQueue("SPECIFICCARD", $player, "LIGHTSPEEDASSAULT2", 1);
      AddDecisionQueue("PASSPARAMETER", $player, "{0}", 1);
      AddDecisionQueue("MZOP", $player, DealDamageBuilder($currentPower, $player), 1);
      return $lastResult;
    case "LIGHTSPEEDASSAULT2":
      $ally = new Ally($lastResult);
      $power = $ally->CurrentPower();
      IndirectDamage("8606123385", $player, $power, false, targetPlayer:$ally->Controller());
      return $lastResult;
    case "ALLWINGSREPORTIN":
      foreach ($lastResult as $index) {
        $ally = new Ally("MYALLY-" . $index, $player);
        $ally->Exhaust(enemyEffects: false);
        CreateXWing($player);
      }
      return $lastResult;
    case "GUERILLAINSURGENCY":
      DamageAllAllies(4, "7235023816", arena: "Ground");
      return $lastResult;
    case "PLANETARYINVASION":
      if($lastResult == "PASS") {
        return $lastResult;
      }

      for($i=0; $i<count($lastResult); ++$i) {
        $ally = new Ally("MYALLY-" . $lastResult[$i], $player);
        $ally->Ready();
        $ally->AddEffect("1167572655");//Planetary Invasion
      }
      return $lastResult;
    case "NODISINTEGRATIONS":
      $ally = new Ally($lastResult, MZPlayerID($player, $lastResult));
      $ally->DealDamage($ally->Health() - 1);
      return $lastResult;
    case "LTCHILDSEN":
      if($lastResult == "PASS" || !is_array($lastResult) || count($lastResult) == 0) {
        return $lastResult;
      }
      $hand = &GetHand($player);
      $reveal = "";
      for($i=0; $i<count($lastResult); ++$i) {
        $ally = new Ally("MYALLY-" . LastAllyIndex($player), $player);
        $ally->Attach("2007868442");//Experience token
        $reveal .= $hand[$lastResult[$i]] . ",";
      }
      $reveal = rtrim($reveal, ",");
      RevealCards($reveal, $player);
      return $lastResult;
    case "CONSOLIDATIONOFPOWER":
      if (count($lastResult) > 0) {
        $totalPower = 0;
        $sacrifices = [];
        for ($i=0; $i<count($lastResult); ++$i) {
          $ally = new Ally("MYALLY-" . $lastResult[$i], $player);
          $totalPower += $ally->CurrentPower();
          $sacrifices[] = $ally;
        }

        AddDecisionQueue("SETDQCONTEXT", $player, "Choose a card to put into play");
        AddDecisionQueue("MULTIZONEINDICES", $player, "MYHAND:definedType=Unit;maxCost=" . $totalPower);
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $player, "<-", 1);
        AddDecisionQueue("ADDCURRENTEFFECT", $player, "4895747419", 1);
        AddDecisionQueue("MZOP", $player, "PLAYCARD", 1);

        foreach ($sacrifices as $sacrificed) {
          $sacrificed->Destroy();
        }
      }
      return $lastResult;
    case "BOLDRESISTANCE":
      if (count($lastResult) == 0) {
        return $lastResult;
      } else if (count($lastResult) > 1) { // If there are multiple units, check if they share a trait
        $firstAlly = new Ally("MYALLY-" . $lastResult[0], $player);
        $traits = CardTraits($firstAlly->CardID());
        for ($i = 1; $i < count($lastResult); $i++) {
          $ally = new Ally("MYALLY-" . $lastResult[$i], $player);
          if (!DelimStringShares($traits, CardTraits($ally->CardID()))) {
            WriteLog("<span style='color:red;'>You must choose units that share the same trait. Reverting gamestate.</span>");
            RevertGamestate();
            return "PASS";
          }
        }
      }

      for ($i=0; $i<count($lastResult); $i++) {
        $ally = new Ally("MYALLY-" . $lastResult[$i], $player);
        AddCurrentTurnEffect("8022262805", $player, uniqueID:$ally->UniqueID()); //Bold Resistance
      }
      return $lastResult;
    case "MULTIGIVEEXPERIENCE":
      for($i=0; $i<count($lastResult); ++$i) {
        $ally = new Ally("MYALLY-" . $lastResult[$i], $player);
        $ally->Attach("2007868442");//Experience token
      }
      return $lastResult;
    case "MULTIGIVESHIELD":
      for($i=0; $i<count($lastResult); ++$i) {
        $ally = new Ally("MYALLY-" . $lastResult[$i], $player);
        $ally->Attach("8752877738");//Shield Token
      }
      return $lastResult;
    case "IHADNOCHOICE":
      $cards = explode(",", MZSort($dqVars[0]));
      for($i=count($cards)-1; $i>=0; --$i) {
        if($cards[$i] == $lastResult) {
          MZBounce($player, $cards[$i]);
        } else {
          MZSink($player, $cards[$i]);
        }
      }
      return $lastResult;
    case "CALCULATEDLETHALITY":
      $controller = MZPlayerID($player, $lastResult);
      $target = new Ally($lastResult, $controller);
      $numUpgrades = $target->NumUpgrades();
      $target->Destroy();
      if($numUpgrades > 0) {
        for($i=0; $i<$numUpgrades; ++$i) PrependDecisionQueue("MZOP", $player, "ADDEXPERIENCE", 1);
        PrependDecisionQueue("CHOOSEMULTIZONE", $player, "<-", 1);
        PrependDecisionQueue("SETDQCONTEXT", $player, "Choose a unit to give " . $numUpgrades . " experience");
        PrependDecisionQueue("MULTIZONEINDICES", $player, "MYALLY");
      }
      return $lastResult;
    case "L337":
      $target = $lastResult;
      if($target == "PASS") {
        $ally = new Ally($parameterArr[1]);
        $ally->Attach("8752877738");//Shield Token
      } else {
        RescueUnit($player, $target);
      }
      return $lastResult;
    case "XANADUBLOOD":
      if($lastResult == "Resource") {
        WriteLog(CardLink("5818136044", "5818136044") . " exhausts a resource");
        ExhaustResource($player == 1 ? 2 : 1, 1);
      } else {
        WriteLog(CardLink("5818136044", "5818136044") . " exhausts a unit");
        PrependDecisionQueue("MZOP", $player, "REST", 1);
        PrependDecisionQueue("CHOOSEMULTIZONE", $player, "<-", 1);
        PrependDecisionQueue("SETDQCONTEXT", $player, "Choose a unit to exhaust");
        PrependDecisionQueue("MULTIZONEINDICES", $player, "THEIRALLY");
      }
      return $lastResult;
    case "THEMARAUDER":
      $cardID = GetMZCard($player, $lastResult);
      if(UnitCardSharesName($cardID, $player))
      {
        $mzArr = explode("-", $lastResult);
        RemoveDiscard($player, $mzArr[1]);
        AddResources($cardID, $player, "GY", "DOWN", isExhausted:1);
      }
      return $lastResult;
    case "ROSETICO":
      $ally = new Ally($lastResult, $player);
      if($ally->HasUpgrade("8752877738"))//Shield token
      {
        $ally->DefeatUpgrade("8752877738");//Shield token
        $ally->Attach("2007868442");//Experience token
        $ally->Attach("2007868442");//Experience token
      }
      return $lastResult;
    case "DOCTORAPHRA":
      $index = GetRandom() % count($lastResult);
      $cardID = RemoveDiscard($player, $lastResult[$index]);
      WriteLog(CardLink($cardID, $cardID) . " is returned by " . CardLink("0254929700", "0254929700"));
      AddHand($player, $cardID);
      return $lastResult;
    case "ENDLESSLEGIONS":
      global $CS_AfterPlayedBy;
      $resources = &GetResourceCards($player);
      $resourceIndices = [];

      for ($i=0; $i < count($resources); $i += ResourcePieces()) {
        if(DefinedTypesContains($resources[$i], "Unit", $player)) {
          $resourceIndices[] = "MYRESOURCES-" . $i;
        }
      }
      if (count($resourceIndices) == 0) return "PASS";

      AddDecisionQueue("PASSPARAMETER", $player, implode(",", $resourceIndices));
      AddDecisionQueue("SETDQCONTEXT", $player, "You may choose a unit to play for free<br/>When Played effects will resolve in the order selected");
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $player, "<-");
      AddDecisionQueue("SETDQVAR", $player, "0", 1);
      AddDecisionQueue("PASSPARAMETER", $player, "5576996578", 1);
      AddDecisionQueue("SETCLASSSTATE", $player, $CS_AfterPlayedBy, 1);
      AddDecisionQueue("ADDCURRENTEFFECT", $player, "5576996578", 1);
      AddDecisionQueue("PASSPARAMETER", $player, "{0}", 1);
      AddDecisionQueue("MZOP", $player, "PLAYCARD", 1);
      return 1;
    case "HUNTEROUTCASTSERGEANT":
      $chosenResourceIndex = explode("-", $lastResult)[1];
      $resourceCardID = &GetResourceCards($player)[$chosenResourceIndex];
      $resourceTitle = CardTitle($resourceCardID);
      RevealCards($resourceCardID, $player, "RESOURCES");
      if(CardIsUnique($resourceCardID) && SearchAlliesForTitle($player, $resourceTitle) != "") {
        //Technically only the ally in play needs to be unique, but I'm going to assume that if the resource card is unique
        //and the ally in play shares a name with it then the ally in play is unique.
        //If for some reason cards are printed that make this not guaranteed we can make the check more rigorous.
        MZBounce($player, $lastResult);
        AddTopDeckAsResource($player);
      }
      return 1;
    case "SURVIVORS'GAUNTLET":
      $prefix = str_starts_with($dqVars[0], "MY") ? "MY" : "THEIR";
      AddDecisionQueue("MULTIZONEINDICES", $player, $prefix . "ALLY", 1);
      AddDecisionQueue("MZFILTER", $player, "filterUpgradeEligible={1}", 1);
      AddDecisionQueue("MZFILTER", $player, "index=" . $dqVars[0], 1);
      AddDecisionQueue("SETDQCONTEXT", $player, "Choose a unit to move <1> to.", 1);
      AddDecisionQueue("CHOOSEMULTIZONE", $player, "<-", 1);
      AddDecisionQueue("MZOP", $player, "MOVEUPGRADE", 1);
      return 1;
    case "PREVIZSLA":
      $upgradeID = $dqVars[1];
      $upgradeCost = CardCost($upgradeID);
      if(NumResourcesAvailable($player) >= $upgradeCost) {
        AddDecisionQueue("YESNO", $player, "if you want to pay " . $upgradeCost . " to steal " . CardName($upgradeID), 1);
        AddDecisionQueue("NOPASS", $player, "-", 1);
        AddDecisionQueue("PAYRESOURCES", $player, $upgradeCost . ",1", 1);
        $preIndex = "MYALLY-" . SearchAlliesForCard($player, "3086868510");
        //Check if DQ effect returns "PASS"
        if(DecisionQueueStaticEffect("MZFILTER", $player, "filterUpgradeEligible=" . $upgradeID, $preIndex) != "PASS") {
          AddDecisionQueue("PASSPARAMETER", $player, $preIndex, 1);
          AddDecisionQueue("MZOP", $player, "MOVEUPGRADE", 1);
        }
        else {
          AddDecisionQueue("PASSPARAMETER", $player, $upgradeID, 1);
          AddDecisionQueue("OP", $player, "DEFEATUPGRADE", 1);
        }
      }
      return 1;
    case "GENERALRIEEKAN":
      $targetAlly = new Ally($lastResult, $player);
      AddDecisionQueue("PASSPARAMETER", $player, $lastResult, 1);
      if(HasSentinel($targetAlly->CardID(), $player, $targetAlly->Index())) {
        AddDecisionQueue("MZOP", $player, "ADDEXPERIENCE", 1);
      }
      else {
        AddDecisionQueue("MZOP", $player, "GETUNIQUEID", 1);
        AddDecisionQueue("ADDLIMITEDCURRENTEFFECT", $player, "3468546373,PLAY", 1);
      }
      return 1;
    case "RULEWITHRESPECT":
      global $CS_UnitsThatAttackedBase;
      $unitsThatAttackedBase = explode(",", GetClassState($player, $CS_UnitsThatAttackedBase));
      $opponent = $player == 1 ? 2: 1;
      for($i = 0; $i < count($unitsThatAttackedBase); ++$i) {
      $targetMZIndex = "THEIRALLY-" . SearchAlliesForUniqueID($unitsThatAttackedBase[$i], $opponent);
      if($targetMZIndex == "THEIRALLY--1") continue;
      $ally = new Ally($targetMZIndex, $opponent);
      if($ally->IsLeader()) continue;
      DecisionQueueStaticEffect("MZOP", $player, "CAPTURE," . $lastResult, $targetMZIndex);
      }
      return 1;
    case "ANEWADVENTURE":
      $owner = str_starts_with($lastResult, "MY") ? $player : ($player == 1 ? 2 : 1);
      $lastResult = str_replace("THEIR", "MY", $lastResult);
      $cardID = &GetHand($owner)[explode("-", $lastResult)[1]];
      PrependDecisionQueue("REMOVECURRENTEFFECT", $owner, "4717189843", 1);
      PrependDecisionQueue("MZOP", $owner, "PLAYCARD", 1);
      PrependDecisionQueue("PASSPARAMETER", $owner, $lastResult, 1);
      PrependDecisionQueue("ADDCURRENTEFFECT", $owner, "4717189843", 1);
      PrependDecisionQueue("NOPASS", $owner, "-", 1);
      PrependDecisionQueue("YESNO", $owner, "if you want to play " . CardLink($cardID, $cardID) . " for free");
      return 1;
    case "FLEETLIEUTENANT":
      $ally = new Ally($lastResult, $player);

      if (TraitContains($ally->CardID(), "Rebel", $player)) {
        AddDecisionQueue("PASSPARAMETER", $player, $ally->UniqueID());
        AddDecisionQueue("ADDLIMITEDCURRENTEFFECT", $player, "3038238423,HAND"); //Fleet Lieutenant
      }

      AddDecisionQueue("PASSPARAMETER", $player, $ally->MZIndex());
      AddDecisionQueue("MZOP", $player, "ATTACK");
      break;
    case "COMMENCEPATROL":
      if ($lastResult == "Yours") {
        $search = "MYDISCARD";
        $where = "MYBOTDECK";
        $filter = "index=" . GetLastDiscardedMZ($player);
      } else {
        $search = "THEIRDISCARD";
        $where = "THEIRBOTDECK";
        $filter = "";
      }
      MZMoveCard($player, $search, $where, filter:$filter, context:"Choose a card to put on the bottom of its owner's deck");
      AddDecisionQueue("CREATEXWING", $player, "-", 1);
      break;
    case "REDEMPTION":
      $ally = new Ally($parameterArr[1]);
      $healedTargets = explode(",", $lastResult);

      $totalHealAmount = 0;
      foreach ($healedTargets as $healedTarget) {
        $healAmount = explode("-", $healedTarget)[0];
        $totalHealAmount += $healAmount;
      }
      if ($totalHealAmount > 0) {
        $ally->DealDamage($totalHealAmount, fromUnitEffect:true);
      }
      break;
    case "YODAOLDMASTER":
      if($lastResult == "Both") {
        WriteLog("Both player drew a card from Yoda, Old Master");
        Draw($player);
        Draw($otherPlayer);
      } else if($lastResult == "Yourself") {
        WriteLog("Player $player drew a card from Yoda, Old Master");
        Draw($player);
      } else {
        WriteLog("Player $otherPlayer drew a card from Yoda, Old Master");
        Draw($otherPlayer);
      }
      break;
    case "PRISONEROFWAR":
      $capturer = new Ally("MYALLY-" . SearchAlliesForUniqueID($dqVars[0], $player), $player);
      if(CardCost($lastResult) < CardCost($capturer->CardID())) {
        CreateBattleDroid($player);
        CreateBattleDroid($player);
      }
      break;
    case "COUNTDOOKU_TWI":
      $power = $lastResult;
      AddDecisionQueue("MULTIZONEINDICES", $player, "THEIRALLY", 1);
      AddDecisionQueue("SETDQCONTEXT", $player, "You may choose a unit to deal " . $power . " damage to", 1);
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $player, "<-", 1);
      AddDecisionQueue("MZOP", $player, DealDamageBuilder($power, $player, isUnitEffect:1, unitCardID:"8655450523"), 1);
      break;
    case "LETHALCRACKDOWN":
      $enemyAlly = new Ally($lastResult);
      $enemyPower = $enemyAlly->CurrentPower();
      $enemyAlly->Destroy();
      DealDamageAsync($player, $enemyPower, "DAMAGE", "1389085256", sourcePlayer:$player);
      break;
    case "KASHYYYKDEFENDER":
      $args = explode("-", $lastResult);
      $ally = new Ally($args[0], $player);
      $healAmount = $args[1];
      $ally->DealDamage($healAmount);
      break;
    //Jump to Lightspeed
    case "KIMOGILAHEAVYFIGHTER":
      $targets = explode(",", $lastResult);
      for ($i=0; $i<count($targets); $i++) {
        if (str_starts_with("B", $targets[$i])) continue; // Skip base

        $ally = new Ally($targets[$i]);
        if ($ally->Exists()) {
          $ally->Exhaust(enemyEffects:$player != $ally->Controller());
        }
      }
      break;
    case "BOBA_FETT_LEADER_JTL":
      IndirectDamage("9831674351", $player, 1);
      break;
    case "HAN_SOLO_LEADER_JTL":
      $ally = new Ally($lastResult, $player);
      $attackerCost = CardCost($ally->CardID());
      $attackerCostIsOdd = $attackerCost % 2 == 1;
      $odds = intval($dqVars[0]);
      $oddsIsOdd = $odds % 2 == 1;
      if($attackerCostIsOdd && $oddsIsOdd && $attackerCost != $odds) {
        AddCurrentTurnEffect("0616724418", $player);
      }
      AddDecisionQueue("MZOP", $player, "ATTACK", 1);
      return $lastResult;
    case "LEIA_JTL":
      $ally = new Ally($lastResult, $player);
      AddDecisionQueue("PASSPARAMETER", $player, $ally->UniqueID());
      AddDecisionQueue("ADDLIMITEDCURRENTEFFECT", $player, "7924461681,HAND");
      AddDecisionQueue("PASSPARAMETER", $player, $ally->MZIndex());
      AddDecisionQueue("MZOP", $player, "ATTACK");
      break;
    case "SWEEPTHEAREA":
      $totalCost = 0;
      for($i=count($lastResult)-1; $i>=0; --$i) {
        $owner = MZPlayerID($player, $lastResult[$i]);
        $ally = new Ally($lastResult[$i], $owner);
        $totalCost += CardCost($ally->CardID());
      }
      if($totalCost > 3) {
        WriteLog("<span style='color:red;'>The unit cost was too high. Reverting gamestate.</span>");
        RevertGamestate();
        return "";
      } else {
        for($i=count($lastResult)-1; $i>=0; --$i) {
          $owner = MZPlayerID($player, $lastResult[$i]);
          MZBounce($player, $lastResult[$i]);
        }
      }
      break;
    case "THRAWN_JTL":
      $data = explode(";", $dqVars[1]);
      $target = $data[0];
      $leaderUnitSide = $data[1];
      $trigger = $data[2];
      $dd=DeserializeAllyDestroyData($trigger);
      AllyDestroyedAbility($player, $target, $dd["UniqueID"], $dd["LostAbilities"],$dd["IsUpgraded"],$dd["Upgrades"],$dd["UpgradesWithOwnerData"],
        $dd["LastPower"], $dd["LastRemainingHP"], $dd["Owner"]);
      if($leaderUnitSide == "1") {
        $thrawnLeaderUnit = new Ally("MYALLY-" . SearchAlliesForCard($player, "53207e4131"));
        if($thrawnLeaderUnit->Exists()) {
          $thrawnLeaderUnit->SumNumUses(-1);
        }
      }
      break;
    case "ACKBAR_JTL":
      $ally = new Ally($lastResult);
      CreateXWing($ally->Controller());
      break;
    case "PROFUNDITY":
      $playerChosen = $lastResult == "Yourself" ? $player : $otherPlayer;
      WriteLog("Player $playerChosen discarded a card from Profundity");
      PummelHit($playerChosen);

      if($playerChosen == $otherPlayer && (CountHand($player) < (CountHand($otherPlayer) - 1))) {
        WriteLog("Player $otherPlayer discarded another card from Profundity");
        PummelHit($otherPlayer);
      }
      break;
    case "TURBOLASERSALVO":
      $arena = $dqVars[0];
      $damage = $dqVars[1];
      $otherPlayer = $player == 1 ? 2 : 1;
      DamagePlayerAllies($otherPlayer, $damage, "8174214418", arena:$arena);
      break;
    case "SABINES_MP_CUNNING":
      if($lastResult == "Exhaust_Theirs") ExhaustResource($otherPlayer, 1);
      else if ($lastResult == "Ready_Mine") ReadyResource($player, 1);
      break;
    case "INVISIBLE_HAND_JTL":
      $cardCost = CardCost($lastResult);
      if($cardCost <= 2) {
        AddDecisionQueue("SETDQCONTEXT", $player, "Do you want to play " . CardLink($lastResult, $lastResult) . " for free?");
        AddDecisionQueue("YESNO", $player, "-", 1);
        AddDecisionQueue("NOPASS", $player, "-", 1);
        AddDecisionQueue("ADDCURRENTEFFECT", $player, "7138400365", 1);
        AddDecisionQueue("FINDINDICES", $player, "MZLASTHAND", 1);
        AddDecisionQueue("MZOP", $player, "PLAYCARD", 1);
      }
      break;
    case "TRENCH_JTL_OPP":
      if($dqVars[0] == "") break;
      $cards = explode(",",$dqVars[0]);
      $index = array_search($lastResult, $cards);
      unset($cards[$index]);
      array_values($cards);
      $dqVars[0] = implode(",", $cards);
      AddGraveyard($lastResult, $player, "DECK");
      break;
    case "TRENCH_JTL":
      if($dqVars[0] == "") break;
      $cards = explode(",",$dqVars[0]);
      $index = array_search($lastResult, $cards);
      unset($cards[$index]);
      $cardLeft = array_values($cards)[0] ?? "";
      AddPlayerHand($lastResult, $player, "DECK");
      if($cardLeft != "")
        AddGraveyard($cardLeft, $player, "DECK");
      break;
    case "CAT_AND_MOUSE":
      $enemyAlly = new Ally($lastResult);
      $enemyArena = $enemyAlly->CurrentArena();
      $enemyPower = $enemyAlly->CurrentPower();
      $enemyAlly->Exhaust(true);
      AddDecisionQueue("MULTIZONEINDICES", $player, "MYALLY:arena=" . $enemyArena . ";maxAttack=" . $enemyPower);
      AddDecisionQueue("SETDQCONTEXT", $player, "Choose a unit in the same arena to ready", 1);
      AddDecisionQueue("CHOOSEMULTIZONE", $player, "<-", 1);
      AddDecisionQueue("MZOP", $player, "READY", 1);
      break;
    case "KAZUDA_JTL":
      for($i=0; $i<count($lastResult); ++$i) {
        $ally = new Ally("MYALLY-" . $lastResult[$i], $player);
        AddRoundEffect("c1700fc85b", $player, "c1700fc85b", $ally->UniqueID());
        WriteLog(CardLink($ally->CardID(), $ally->CardID()) . " loses all abilities for this round.");
      }
      break;
    case "FOCUS_FIRE":
      $target = new Ally($lastResult);
      $targetArena = CardArenas($target->CardID());
      $allies = &GetAllies($player);
      $damage = 0;
      for ($i = 0; $i < count($allies); $i += AllyPieces()) {
        if (TraitContains($allies[$i], "Vehicle", $player) && CardArenas($allies[$i]) == $targetArena) {
          $ally = new Ally($allies[$i+5], $player);
          $damage += $ally->CurrentPower();
        }
      }
      AddDecisionQueue("PASSPARAMETER", $player, $lastResult, 1);
      AddDecisionQueue("MZOP", $player, DealDamageBuilder($damage, $player, isUnitEffect:1), 1);
      break;
    case "L337_JTL":
      $L3Ally = Ally::FromUniqueId($parameterArr[1]);
      if($lastResult == "YES") {
        AddDecisionQueue("MULTIZONEINDICES", $player, "MYALLY:trait=Vehicle");
        AddDecisionQueue("MZFILTER", $player, "hasPilot=1");
        AddDecisionQueue("PASSREVERT", $player, "-");
        AddDecisionQueue("SETDQCONTEXT", $player, "Choose a vehicle to move L3's brain to");
        AddDecisionQueue("CHOOSEMULTIZONE", $player, "<-", 1);
        AddDecisionQueue("MZOP", $player, "GETUNIQUEID", 1);
        AddDecisionQueue("SETDQVAR", $player, "0", 1);
        AddDecisionQueue("PASSPARAMETER", $player, $L3Ally->UniqueID());
        AddDecisionQueue("MZOP", $player, "MOVEPILOTUNIT", 1);
      } else if ($lastResult == "NO") {
        DestroyAlly($player, $L3Ally->Index(), skipSpecialCase:true);
      }
      break;
    case "VADER_UNIT_JTL":
      $attackerCardID = $parameterArr[1];
      $pingedAlly = new Ally($lastResult);
      $enemyDamage = str_starts_with($lastResult, "MYALLY-") ? false : true;
      $defeated = $pingedAlly->DealDamage(1, enemyDamage: $enemyDamage, fromUnitEffect:true);
      if($defeated) {
        AddDecisionQueue("MULTIZONEINDICES", $player, "MYALLY&THEIRALLY");
        AddDecisionQueue("PREPENDLASTRESULT", $player, "MYCHAR-0,THEIRCHAR-0,");
        AddDecisionQueue("SETDQCONTEXT", $player, "Choose something to deal 1 damage to");
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $player, "<-", 1);
        AddDecisionQueue("MZOP", $player, DealDamageBuilder(1, $player, isUnitEffect:1, unitCardID:$attackerCardID), 1);
      }
      break;
    case "PAY_READY_TAX":
      $tax = $parameterArr[1];
      if(NumResourcesAvailable($player) >= $tax) {
        $affectedUid = $parameterArr[2];
        $ally = Ally::FromUniqueId($affectedUid);
        $cardID = $ally->CardID();
        AddDecisionQueue("YESNO", $player, "Pay $tax resources to ready " . CardLink($cardID, $cardID) . "?", 1);
        AddDecisionQueue("NOPASS", $player, "-", 1);
        AddDecisionQueue("PAYRESOURCES", $player, $tax, 1);
        AddDecisionQueue("SPECIFICCARD", $player, "PAID_READY_TAX,$affectedUid", 1);
      }
      break;
    case "PAID_READY_TAX":
      Ally::FromUniqueId($parameterArr[1])->Ready(resolvedSpecialCase:true);
      break;
    case "HEARTLESSTACTICS":
      $ally = Ally::FromUniqueId($lastResult);
      if(!$ally->IsLeader() && $ally->CurrentPower() == 0) {
        AddDecisionQueue("SETDQCONTEXT", $player, "Bounce " . CardLink($ally->CardID(), $ally->CardID()) . "?");
        AddDecisionQueue("YESNO", $player, "-", 1);
        AddDecisionQueue("NOPASS", $player, "-", 1);
        AddDecisionQueue("PASSPARAMETER", $player, $ally->MZIndex(), 1);
        AddDecisionQueue("MZOP", $player, "BOUNCE", 1);
      }
      break;
    case "SYSTEMSHOCK":
      $targetAllyUID = Ally::FromUniqueId($lastResult)->UniqueID();
      AddDecisionQueue("PASSPARAMETER", $player, $targetAllyUID, 1);
      AddDecisionQueue("SETDQVAR", $player, "0", 1);
      AddDecisionQueue("MZOP", $player, "GETUPGRADES", 1);
      AddDecisionQueue("FILTER", $player, "LastResult-exclude-isLeader", 1);
      AddDecisionQueue("SETDQCONTEXT", $player, "Choose a non-leader upgrade to defeat.", 1);
      AddDecisionQueue("CHOOSECARD", $player, "<-", 1);
      AddDecisionQueue("OP", $player, "DEFEATUPGRADE", 1);
      AddDecisionQueue("UNIQUETOMZ", $player, $targetAllyUID, 1);
      AddDecisionQueue("MZOP", $player, DealDamageBuilder(1, $player), 1);
      break;
    case "THEREISNOESCAPE":
      foreach($lastResult as $index) {
        $mzArr = explode("-", $index);
        $allyPlayer = $mzArr[0] == "MYALLY" ? $player : $otherPlayer;
        $ally = new Ally("MYALLY-" . $mzArr[1], $allyPlayer);
        AddRoundEffect("9184947464", $allyPlayer, "PLAY", $ally->UniqueID());
      }
      break;
    case "UWINGLANDER":
      $upgradeID = $dqVars[1];
      if(PilotingCost($upgradeID) > -1) {
        global $CS_PlayedAsUpgrade;
        SetClassState($player, $CS_PlayedAsUpgrade, 1);
        AddDecisionQueue("MULTIZONEINDICES", $player, "MYALLY:trait=Vehicle", 1);
        AddDecisionQueue("MZFILTER", $player, "hasPilot=1", 1);
        AddDecisionQueue("PASSREVERT", $player, "-");
      } else {
        AddDecisionQueue("MULTIZONEINDICES", $player, "MYALLY:trait=Vehicle");
        AddDecisionQueue("MZFILTER", $player, "filterUpgradeEligible={1}", 1);
      }
      AddDecisionQueue("SETDQCONTEXT", $player, "Choose a unit to move <1> to.", 1);
      AddDecisionQueue("CHOOSEMULTIZONE", $player, "<-", 1);
      AddDecisionQueue("MZOP", $player, "MOVEUPGRADE", 1);
      break;
    case "GREYSQUADYWING":
      if (str_contains($lastResult, "CHAR")) {
        AddDecisionQueue("SETDQCONTEXT", $player, "Do you want to deal 2 damage to opponent's base?");
      } else {
        $ally = new Ally($lastResult, $otherPlayer);
        if (!$ally->Exists()) break;
        AddDecisionQueue("SETDQCONTEXT", $player, "Do you want to deal 2 damage to " . CardLink($ally->CardID(), $ally->CardID()) . "?");
      }
      AddDecisionQueue("YESNO", $player, "-");
      AddDecisionQueue("NOPASS", $player, "-", 1);
      AddDecisionQueue("PASSPARAMETER", $player, $lastResult, 1);
      AddDecisionQueue("MZOP", $otherPlayer, DealDamageBuilder(2, $player, isUnitEffect: 1), 1);
      break;
    case "COMMANDEER":
      $ally = Ally::FromUniqueId($lastResult);
      AddDecisionQueue("PASSPARAMETER", $player, $ally->UniqueID(), 1);
      if($ally->Controller() != $player) {
        AddDecisionQueue("MZOP", $player, "TAKECONTROL", 1);
        AddDecisionQueue("MZOP", $player, "READY", 1);
      }
      AddDecisionQueue("ADDLIMITEDROUNDEFFECT", $player, "8105698374,HAND", 1);
      break;
    //Legends of the Force
    case "SAVAGEOPRESS_LOF":
      if($lastResult == "YES") {
        UseTheForce($player);
      } else {
        DealDamageAsync($player, 9, "DAMAGE", "1636013021", $player);
      }
      break;
    case "QUIGONJINN_LOF":
      $card = GetMZCard($player, $lastResult);
      $cost = CardCost($card);
      $leaderUnitSide = $parameterArr[1];
      AddDecisionQueue("MULTIZONEINDICES", $player, "MYHAND:definedType=Unit;maxCost=" . ($cost - 1));
      AddDecisionQueue("MZFILTER", $player, "aspect=Villainy");
      AddDecisionQueue("SETDQCONTEXT", $player, "Choose a non-Villainy unit from your hand that costs less than " . $cost . " to play for free");
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $player, "<-", 1);
      AddDecisionQueue("ADDCURRENTEFFECT", $player, $leaderUnitSide ? "6def6570f5": "2580909557", 1);
      AddDecisionQueue("MZOP", $player, "PLAYCARD", 1);
      break;
    case "QUIGONJINN_UNIT_LOF":
      $ally = new Ally($lastResult);
      $controller = $ally->Controller();
      $owner = $ally->Owner();
      $cardID = $ally->CardID();
      RemoveAlly($controller, $ally->Index());
      if(!IsToken($cardID)) {
        AddDecisionQueue("PASSPARAMETER", $owner, $cardID, 1);
        AddDecisionQueue("OPT", $owner, "<-", 1);
      }
      break;
    case "DEALRESTOREDAMAGE":
      $may = isset($parameterArr[1]) && $parameterArr[1] === "MAY";
      $args = explode("-", $lastResult);
      $ally = new Ally($args[0], $player);
      $healed = $args[1];
      if ($healed > 0) {
        AddDecisionQueue("MULTIZONEINDICES", $player, "MYALLY&THEIRALLY");
        AddDecisionQueue("SETDQCONTEXT", $player, "Choose a unit to deal " . $healed . " damage to");
        if($may){
          AddDecisionQueue("MAYCHOOSEMULTIZONE", $player, "<-", 1);
          AddDecisionQueue("MZOP", $player, DealDamageBuilder($healed, $player, isUnitEffect:1), 1);
        }else{
          AddDecisionQueue("CHOOSEMULTIZONE", $player, "<-", 1);
          AddDecisionQueue("MZOP", $player, DealDamageBuilder($healed, $player, isUnitEffect:1), 1);
        }
      }
      break;
    case "SIFODYAS_LOF":
      $totalCost = 0;
      $cardArr = explode(",", $lastResult);
      if($lastResult == "") $cardArr = [];
      for($i=0; $i<count($cardArr); ++$i) {
        $totalCost += CardCost($cardArr[$i]);
        AddGraveyard($cardArr[$i], $player, "DECK", "TTFREE");
      }
      if($totalCost > 4) {
        WriteLog("<span style='color:red;'>Combined cost greater than 4. Reverting gamestate.</span>");
        RevertGamestate();
        return "";
      }
      $deck = new Deck($player);
      $searchLeftovers = explode(",", $deck->Bottom(true, 8 - count($cardArr)));
      shuffle($searchLeftovers);
      for($i=0; $i<count($searchLeftovers); ++$i) {
        AddBottomDeck($searchLeftovers[$i], $player);
      }
      break;
    case "CURIOUS_FLOCK":
      $numChosen = $lastResult;
      $uid = $parameterArr[1];
      for($i=0; $i<$numChosen; ++$i) {
        ExhaustResource($player, 1);
        Ally::FromUniqueId($uid)->AttachExperience();
      }
      break;
    case "MIND_TRICK":
      $selectedUnits = explode(",",$dqVars[0]);
      $totalPower = 0;
      for($i=0; $i<count($selectedUnits); ++$i) {
        $allyPlayer = MZPlayerID($player, $selectedUnits[$i]);
        $ally = new Ally($selectedUnits[$i], $allyPlayer);
        $totalPower += $ally->CurrentPower();
      }
      if($totalPower > 4) {
        WriteLog("<span style='color:red;'>Combined power greater than 4. Reverting gamestate.</span>");
        RevertGamestate();
        return "";
      }
      $withTheForce = HasUnitWithTraitInPlay($player, "Force");
      for($i=0; $i<count($selectedUnits); ++$i) {
        $allyPlayer = MZPlayerID($player, $selectedUnits[$i]);
        $ally = new Ally($selectedUnits[$i], $allyPlayer);
        $ally->Exhaust($player != $allyPlayer);
        if($withTheForce) $ally->AddEffect("1146162009");
      }
      break;
    case "OLDDAKA_LOF":
      $target = new Ally($lastResult, $player);
      $cardID = $target->CardID();
      $canPlayFromDiscard = $target->Owner() == $target->Controller();
      $target->Destroy();
      if($canPlayFromDiscard) {
        AddDecisionQueue("MULTIZONEINDICES", $player, "MYDISCARD:definedType=Unit;cardID=$cardID");
        AddDecisionQueue("SETDQCONTEXT", $player, "Choose a unit to play for free (or pass to skip)");
        AddDecisionQueue("ADDCURRENTEFFECT", $player, "0564229530", 1);
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $player, "<-", 1);
        AddDecisionQueue("MZOP", $player, "PLAYCARD", 1);
      }
      break;
    case "EETHKOTH_LOF":
      $owner = $parameterArr[1];
      $from = $owner == $player ? "MYDISCARD" : "THEIRDISCARD";
      $mzID = SearchMultizone($player, "$from:cardID=1160624693");
      $first = explode(",", $mzID)[0];
      MZMoveCard($player, "", "MYRESOURCES", mzIndex: $first);
      AddDecisionQueue("EXHAUSTRESOURCES", $player, "1", 1);
      break;
    case "QGJSABER_LOF":
      $selectedUnits = explode(",",$dqVars[0]);
      $totalCost = 0;
      for($i=0; $i<count($selectedUnits); ++$i) {
        $allyPlayer = MZPlayerID($player, $selectedUnits[$i]);
        $ally = new Ally($selectedUnits[$i], $allyPlayer);
        $totalCost += CardCost($ally->CardID());
      }
      if($totalCost > 6) {
        WriteLog("<span style='color:red;'>Combined cost greater than 6. Reverting gamestate.</span>");
        RevertGamestate();
        return "";
      }
      for($i=0; $i<count($selectedUnits); ++$i) {
        $allyPlayer = MZPlayerID($player, $selectedUnits[$i]);
        $ally = new Ally($selectedUnits[$i], $allyPlayer);
        $ally->Exhaust($player != $allyPlayer);
      }
      break;
    case "MAZKANATA_LOF":
      $ally = new Ally($lastResult, $player);
      AddDecisionQueue("PASSPARAMETER", $player, $ally->UniqueID());
      AddDecisionQueue("ADDLIMITEDCURRENTEFFECT", $player, "8834515285,HAND"); //Maz Kanata
      AddDecisionQueue("PASSPARAMETER", $player, $ally->MZIndex());
      AddDecisionQueue("MZOP", $player, "ATTACK");
      break;
    case "LUMINOUSBEINGS":
      $numCards = count($lastResult);
      ShuffleToBottomDeck($lastResult, $player);
      DQMultiUnitSelect($player, $numCards, "MYALLY", "to give +4/+4");
      AddDecisionQueue("SPECIFICCARD", $player, "LUMINOUSBEINGS-BUFF", 1);
      break;
    case "LUMINOUSBEINGS-BUFF":
      $selectedUnits = explode(",",$dqVars[0]);
      for($i=0; $i<count($selectedUnits); ++$i) {
        $allyPlayer = MZPlayerID($player, $selectedUnits[$i]);
        $ally = new Ally($selectedUnits[$i], $allyPlayer);
        $ally->AddEffect("6801641285");
        $ally->AddRoundHealthModifier(4);
      }
      break;
    case "INFUSEDBRAWLER":
      $controller = MZPlayerID($player, $lastResult);
      $ally = new Ally($lastResult, $controller);
      $ally->AttachExperience();
      $ally->AttachExperience();
      break;
    case "WILLOFTHEFORCE":
      $mzIndex = $dqVars[0];
      $owner = str_starts_with($mzIndex, "MY") ? $player : $otherPlayer;
      DiscardRandom($owner, "9021149512");
      break;
    case "KITFISTOAETHERSPRITE":
      PrependDecisionQueue("SPECIFICCARD", $player, "KITFISTOAETHERSPRITE", 1);
      PrependDecisionQueue("OP", $player, "DEFEATUPGRADE", 1);
      PrependDecisionQueue("MAYCHOOSECARD", $player, "<-", 1);
      PrependDecisionQueue("SETDQCONTEXT", $player, "Choose an upgrade to defeat", 1);
      PrependDecisionQueue("MZOP", $player, "GETUPGRADES", 1);
      PrependDecisionQueue("PASSPARAMETER", $player, "{0}", 1);
      break;
    case "BAYLANSKOLL":
      if(!IsToken($dqVars[1])) {
        $owner = str_starts_with($lastResult, "MY") ? $player : ($player == 1 ? 2 : 1);
        $lastResult = str_replace("THEIR", "MY", $lastResult);
        $cardID = &GetHand($owner)[explode("-", $lastResult)[1]];
        PrependDecisionQueue("REMOVECURRENTEFFECT", $owner, "4729355863", 1);
        PrependDecisionQueue("MZOP", $owner, "PLAYCARD", 1);
        PrependDecisionQueue("PASSPARAMETER", $owner, $lastResult, 1);
        PrependDecisionQueue("ADDCURRENTEFFECT", $owner, "4729355863", 1);
        PrependDecisionQueue("NOPASS", $owner, "-", 1);
        PrependDecisionQueue("YESNO", $owner, "if you want to play " . CardLink($cardID, $cardID) . " for free");
      }
      return 1;
    case "SECONDSISTER_LOF":
        $numToDiscard = 2;
        for ($i = 0; $i < $numToDiscard; $i++) {
          $cardID = Mill($player, 1);
          if ($cardID !== null && TraitContains($cardID, "Force", $player)) {
            ReadyResource($player, 1);
          }
        }
      break;
    case "KYLOREN_LOF_DEPLOY":
      global $CS_AfterPlayedBy;
      AddDecisionQueue("MULTIZONEINDICES", $player, "MYDISCARD:definedType=Upgrade", 1);
      AddDecisionQueue("SETDQCONTEXT", $player, "Choose an upgrade to attach to " . CardLink("d911b778e4", "d911b778e4") . "<br/>(or Pass to skip)", 1);
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $player, "<-", 1);
      AddDecisionQueue("SETDQVAR", $player, "0", 1);
      AddDecisionQueue("ADDCURRENTEFFECT", $player, "d911b778e4", 1);
      AddDecisionQueue("PASSPARAMETER", $player, "d911b778e4", 1);
      AddDecisionQueue("SETCLASSSTATE", $player, $CS_AfterPlayedBy, 1);
      AddDecisionQueue("PASSPARAMETER", $player, "{0}", 1);
      AddDecisionQueue("MZOP", $player, "PLAYCARD", 1);
      break;
    case "LEIAORGANA_LOF":
      $allies = GetAllies($player);
      for($i=0; $i<count($allies); $i+=AllyPieces()) {
        $ally = Ally::FromUniqueId($allies[$i+5]);
        if(AspectContains($ally->CardID(), "Heroism", $player)) {
          $ally->AddRoundHealthModifier(2);
          $ally->AddEffect("2236831712");//Leia Organa (Extraordinary)
        }
      }
      break;
    case "ZUCKUSS_LOF":
      $filteredName = str_replace("<45>", " ", $lastResult);
      $milled = Mill($otherPlayer, 1);
      if(CardTitle($milled) == $filteredName) {
        AddCurrentTurnEffect("0406487670", $player, "PLAY", $parameterArr[1]);
      }
      break;
    case "ANAKINSKYWALKER_LOF":
      $verify = !DefinedTypesContains($lastResult, "Unit") || (HasKeyword($lastResult, "Piloting") && SearchCount(SearchAllies($player, canAddPilot:true)) > 0);
      if(!$verify) {
        WriteLog("<span style='color:red;'>Anakin Skywalker (Legends of the Force) can only tap into the dark side for non-unit cards.</span> Reverting gamestate.");
        RevertGamestate();
        return "";
      }
      AddDecisionQueue("PASSPARAMETER", $player, "{0}", 1);
      AddDecisionQueue("MZOP", $player, "PLAYCARD", 1);
      break;
    case "KYLOREN_LOF_RUMMAGE":
      if(DefinedTypesContains($lastResult, "Upgrade")) {
        Draw($player);
      }
      break;
    case "ALWAYS_TWO":
      $numSith = 0;
      $units = &GetAllies($player);
      foreach ($lastResult as $item) {
        if (TraitContains($units[$item], "Sith", $player)) {
          $numSith++;
        }
      }
      if($numSith < 2) {
        WriteLog("<span style='color:red;'>He promised he would teach me everything. Too bad he didn’t live long enough.</span>");
        return "";
      }
      //Now we've chosen our two Sith, we can continue
      for ($i = count($units) - AllyPieces(); $i >= 0; $i -= AllyPieces()) {
        $ally = new Ally("MYALLY-" . $i, $player);
        if (in_array($i, $lastResult)) {
          $ally->Attach("2007868442"); // Experience token
          $ally->Attach("2007868442"); // Experience token
          $ally->Attach("8752877738"); // Shield token
          $ally->Attach("8752877738"); // Shield token
        } else {
          $ally->Destroy();
        }
      }
      break;
    case "PSYCHOMETRY":
      $cardID = GetMZCard($player, $lastResult);
      $lastTraits = explode(",", CardTraits($cardID));
      $search = "5;1;";
      for($i=0; $i<count($lastTraits); ++$i) {
        $search .= "include-trait-" . $lastTraits[$i];
        if($i < count($lastTraits) - 1) $search .= "|";
      }
      AddDecisionQueue("SEARCHDECKTOPX", $player, $search);
      AddDecisionQueue("MULTIADDHAND", $player, "-", 1);
      AddDecisionQueue("REVEALCARDS", $player, "DECK", 1);
      break;
    case "A_Precarious_Predicament":
      AddDecisionQueue("MULTIZONEINDICES", $player, "MYHAND:cardID=6707315263&MYRESOURCES:cardID=6707315263");
      AddDecisionQueue("SETDQCONTEXT", $player, "Choose It's Worse from your hand or resources to play");
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $player, "<-", 1);
      AddDecisionQueue("ADDCURRENTEFFECT", $player, "5562351003", 1);
      AddDecisionQueue("MZOP", $player, "PLAYCARD", 1);
      return 1;
    case "FORCE_SPEED":
      $defAlly = Ally::FromUniqueId($dqVars[0]);
      if($defAlly->HasUpgradesThatAreNotUnique()) {
        PrependDecisionQueue("SPECIFICCARD", $player, "FORCE_SPEED", 1);
        PrependDecisionQueue("OP", $player, "BOUNCEUPGRADE", 1);
        PrependDecisionQueue("MAYCHOOSECARD", $player, "<-", 1);
        PrependDecisionQueue("SETDQCONTEXT", $player, "Choose an upgrade to bounce", 1);
        PrependDecisionQueue("FILTER", $player, "LastResult-exclude-isUnique", 1);
        PrependDecisionQueue("MZOP", $player, "GETUPGRADES", 1);
        PrependDecisionQueue("PASSPARAMETER", $player, "{0}", 1);
      }
      break;
    case "REY_LOF_LEADERDRAW":
      $fromLeaderCardID = $parameterArr[1];
      switch($fromLeaderCardID) {
        case "2847868671"://Yoda TWI Leader
          if(count(GetHand($player)) > 1) {
            $options = "Keep without Reveal;Keep and Reveal;Put to Top;Put to Bottom";
            AddDecisionQueue("SETDQCONTEXT", $player, "You drew " . CardLink("6172986745", "6172986745") . ". Choose one");
            AddDecisionQueue("CHOOSEOPTION", $player, "6172986745&$options");
            AddDecisionQueue("MODAL", $player, "REY_LOF_LEADERDRAW_YODA_TWI");
          } else AddDecisionQueue("HANDTOPBOTTOM", $player, "-");
          break;
        default: break;
      }
      break;
    case "DRENGIR_SPAWN":
      $dsUniqueID = $parameterArr[1];
      $spawn = Ally::FromUniqueId($dsUniqueID);
      if($spawn->Exists()) {
        $defeatedCost = $parameterArr[2];
        for($i=0; $i<$defeatedCost; ++$i) {
          $spawn->AttachExperience();
        }
      }
      break;
    case "DO_OR_DO_NOT":
      if($lastResult == "YES") {
        UseTheForce($player);
        Draw($player);
        Draw($player);
      } else {
        Draw($player);
      }
      break;
      //Intro Battle: Hoth
    case "LEIA_ORGANA_IBH":
      $selectedUnits = explode(",",$dqVars[0]);
      //heal 1 damage from each selected unit
      for($i=0; $i<count($selectedUnits); ++$i) {
        $ally = new Ally($selectedUnits[$i], $player);
        $ally->Heal(1);
      }
      break;
    case "THE_DESOLATION_OF_HOTH":
      $selectedUnits = explode(",",$dqVars[0]);
      for($i=0; $i<count($selectedUnits); ++$i) {
        $allyPlayer = MZPlayerID($player, $selectedUnits[$i]);
        $ally = new Ally($selectedUnits[$i], $allyPlayer);
        $ally->Destroy();
      }
      break;
    //Secrets of Power
    case "PLAY_PLOT":
      global $CS_AfterPlayedBy, $CS_PlayedAsPlot;
      AddDecisionQueue("MULTIZONEINDICES", $player, "MYRESOURCES:keyword=Plot", 1);
      AddDecisionQueue("SETDQCONTEXT", $player, "Choose a Plot to play");
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $player, "<-", 1);
      AddDecisionQueue("SETDQVAR", $player, "0", 1);
      AddDecisionQueue("PASSPARAMETER", $player, "PLAY_PLOT", 1);
      AddDecisionQueue("SETCLASSSTATE", $player, $CS_AfterPlayedBy, 1);
      AddDecisionQueue("PASSPARAMETER", $player, "1", 1);
      AddDecisionQueue("SETCLASSSTATE", $player, $CS_PlayedAsPlot, 1);
      AddDecisionQueue("PASSPARAMETER", $player, "{0}", 1);
      AddDecisionQueue("MZOP", $player, "PLAYCARD", 1);
      break;
    //SpecificCardLogic End
    default: return "";
  }
}

?>
