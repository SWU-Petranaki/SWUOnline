<?php


function ProcessHitEffect($cardID)
{
  global $mainPlayer, $combatChainState, $CCS_DamageDealt, $defPlayer, $combatChain;
  $attackerAlly = new Ally(AttackerMZID($mainPlayer), $mainPlayer);
  if(HitEffectsArePrevented()) return;

  if($combatChain[7] != "-") {
    $upgrades = explode(",", $combatChain[7]);
    for($i = 0; $i < count($upgrades); $i+=SubcardPieces()) {
      switch($upgrades[$i]) {
        case "9338356823"://Dorsal Turret
          AnyCombatDamageDefeats(includeLeaders:true);
          break;
        default: break;
      }
    }
  }

  switch($cardID)
  {
    //Spark of Rebellion
    case "0828695133"://Seventh Sister
      if(GetAttackTarget() == "THEIRCHAR-0") {
        AddDecisionQueue("MULTIZONEINDICES", $mainPlayer, "THEIRALLY:arena=Ground");
        AddDecisionQueue("SETDQCONTEXT", $mainPlayer, "Choose a card to deal 3 damage", 1);
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $mainPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $mainPlayer, DealDamageBuilder(3, $mainPlayer, isUnitEffect:1, unitCardID:$cardID), 1);
      }
      break;
    case "3280523224"://Rukh
      AnyCombatDamageDefeats();
      break;
    case "87e8807695"://Leia Organa Leader Unit
      if(LeaderAbilitiesIgnored()) break;
      AddCurrentTurnEffect("87e8807695", $mainPlayer);
      break;
    //Shadows of the Galaxy
    case "4595532978"://Ketsu Onyo
      if (GetAttackTarget() == "THEIRCHAR-0") {
        DefeatUpgrade($mainPlayer, true, upgradeFilter: "maxCost=2");
      }
      break;
    //Jump to Lightspeed
    case "7312183744"://Moff Gideon
      if(GetAttackTarget() == "THEIRCHAR-0") {
        AddCurrentTurnEffect("7312183744", $defPlayer, from: "PLAY");
      }
      break;
    //Legends of the Force
    case "3099740319"://Blockade Runner
      if (GetAttackTarget() == "THEIRCHAR-0") {
        $attackerAlly->AttachExperience();
      }
      break;
    default: break;
  }
  AllyHitEffects();
}

function CompletesAttackEffect($cardID) {
  global $mainPlayer, $defPlayer, $currentTurnEffects;
  global  $CS_NumLeftPlay;

  //uogrades
  $mzId = AttackerMZID($mainPlayer);
  $attackerAlly = new Ally($mzId, $mainPlayer);
  $upgrades = $attackerAlly->GetUpgrades();
  for($i=0; $i<count($upgrades); ++$i) {
    switch($upgrades[$i]) {
      //Jump to Lightspeed
      case "8523415830"://Anakin Skywalker pilot
        AddDecisionQueue("YESNO", $mainPlayer, "Do you want to return Anakin Skywalker pilot to your hand?");
        AddDecisionQueue("NOPASS", $mainPlayer, "-");
        AddDecisionQueue("PASSPARAMETER", $mainPlayer, $mzId, 1);
        AddDecisionQueue("SETDQVAR", $mainPlayer, "0", 1);
        AddDecisionQueue("PASSPARAMETER", $mainPlayer, "8523415830", 1);
        AddDecisionQueue("OP", $mainPlayer, "BOUNCEUPGRADE", 1);
        break;
      default: break;
    }
  }

  for($i=0;$i<count($currentTurnEffects);$i+=CurrentTurnPieces()) {
    switch($currentTurnEffects[$i]) {
      case "7660822254"://Barrel Roll
        AddDecisionQueue("MULTIZONEINDICES", $mainPlayer, "MYALLY:arena=Space&THEIRALLY:arena=Space");
        AddDecisionQueue("SETDQCONTEXT", $mainPlayer, "Choose a space unit to exhaust");
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $mainPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $mainPlayer, "REST", 1);
        AddDecisionQueue("REMOVECURRENTEFFECT", $mainPlayer, $currentTurnEffects[$i], 1);
        break;
      default: break;
    }
  }

  switch($cardID)
  {
    case "9560139036"://Ezra Bridger
      AddCurrentTurnEffect("9560139036", $mainPlayer);
      break;
    case "0e65f012f5"://Boba Fett Leader Unit
      if(GetClassState($defPlayer, $CS_NumLeftPlay) > 0) ReadyResource($mainPlayer, 2);
      break;
    case "9647945674"://Zeb Orrelios
      if(GetAttackTarget() == "NA") {//This means the target was defeated
        AddDecisionQueue("MULTIZONEINDICES", $mainPlayer, "THEIRALLY:arena=Ground");
        AddDecisionQueue("SETDQCONTEXT", $mainPlayer, "Choose a unit to deal 4 damage to");
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $mainPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $mainPlayer, DealDamageBuilder(4, $mainPlayer, isUnitEffect:1, unitCardID:$cardID), 1);
      }
      break;
    case "0518313150"://Embo
      if(GetAttackTarget() == "NA") {//This means the target was defeated
        AddDecisionQueue("MULTIZONEINDICES", $mainPlayer, "MYALLY:damagedOnly=1&THEIRALLY:damagedOnly=1");
        AddDecisionQueue("SETDQCONTEXT", $mainPlayer, "Choose a unit to heal up to 2 damage", 1);
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $mainPlayer, "<-", 1);
        AddDecisionQueue("PREPENDLASTRESULT", $mainPlayer, "2-", 1);
        AddDecisionQueue("SETDQCONTEXT", $mainPlayer, "Heal up to 2 damage", 1);
        AddDecisionQueue("PARTIALMULTIHEALMULTIZONE", $mainPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $mainPlayer, "MULTIHEAL", 1);
        AddDecisionQueue("PREPENDLASTRESULT", $mainPlayer,$attackerAlly->UniqueID() . "-", 1);
      }
      break;
    case "7244268162"://Finn
      AddDecisionQueue("MULTIZONEINDICES", $mainPlayer, "MYALLY&THEIRALLY");
      AddDecisionQueue("MZFILTER", $mainPlayer, "unique=0");
      AddDecisionQueue("SETDQCONTEXT", $mainPlayer, "Choose a unit for Finn to protect");
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $mainPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $mainPlayer, "GETUNIQUEID", 1);
      AddDecisionQueue("ADDLIMITEDCURRENTEFFECT", $mainPlayer, "7244268162,PLAY", 1);
      break;
    //Jump To Lightspeed
    case "7138400365"://The Invisible Hand JTL
      InvisibleHandJTL($mainPlayer);
      break;
    case "8544209291"://U-Wing Lander
      AddDecisionQueue("PASSPARAMETER", $mainPlayer, "MYALLY-" . $attackerAlly->Index(), 1);
      AddDecisionQueue("SETDQVAR", $mainPlayer, "0", 1);
      AddDecisionQueue("PASSPARAMETER", $mainPlayer,$attackerAlly->UniqueID(), 1);
      AddDecisionQueue("MZOP", $mainPlayer, "GETUPGRADES", 1);
      AddDecisionQueue("SETDQCONTEXT", $mainPlayer, "Choose an upgrade to move.", 1);
      AddDecisionQueue("CHOOSECARD", $mainPlayer, "<-", 1);
      AddDecisionQueue("SETDQVAR", $mainPlayer, "1", 1);
      AddDecisionQueue("SPECIFICCARD", $mainPlayer, "UWINGLANDER", 1);
      break;
    case "6def6570f5"://Qui-Gon Jinn Leader unit
      QuiGonJinnLOF($mainPlayer, true);
      break;
    case "8633377277"://Pong Krell
      $lessThanCurrenPower = $attackerAlly->CurrentPower() - 1;
      MZChooseAndDestroy($mainPlayer,
        "MYALLY:maxHealth=$lessThanCurrenPower&THEIRALLY:maxHealth=$lessThanCurrenPower",
        may:true,
        context:"You may choose a unit with less than " . ($lessThanCurrenPower + 1) . " health to defeat.");
      break;
    case "5390030381"://Infused Brawler
      $attackerAlly->DefeatUpgrade("2007868442");
      break;
    default: break;
  }
}

function AttackModifier($cardID, $player, $index, $reportMode = false)
{
  global $mainPlayer, $defPlayer, $initiativePlayer, $combatChain, $combatChainState, $currentTurnEffects,
    $CS_NumLeftPlay, $CCS_MultiAttackTargets;

  $modifier = 0;
  $otherPlayer = $player == 1 ? 2 : 1;
  if($player == $mainPlayer) {
    //Raid is only for attackers
    $attacker = AttackerMZID($mainPlayer);
    $mzArr = explode("-", $attacker);
    if($mzArr[1] == $index && !$reportMode) $modifier = RaidAmount($cardID, $mainPlayer, $mzArr[1]);
  }
  $ally = new Ally("MYALLY-" . $index, $player);
  //Base attack modifiers
  $char = &GetPlayerCharacter($player);
  switch($char[0]) {
    case "9652861741"://Petranaki Arena
      $modifier += $ally->IsLeader() ? 1 : 0;
      break;
    default: break;
  }
  if(!$ally->LostAbilities()) {
    switch($cardID) {
      //Spark of Rebellion
      case "3988315236"://Seasoned Shoretrooper
        $modifier += NumResources($player) >= 6 ? 2 : 0;
        break;
      case "6348804504"://Ardent Sympathizer
        $modifier += $initiativePlayer == $player ? 2 : 0;
        break;
      case "4619930426"://First Legion Snowtrooper
        if(count($combatChain) == 0 || $combatChain[0] !== "4619930426" || $player == $defPlayer) break;
        $target = GetAttackTarget();
        if($target == "THEIRCHAR-0") break;
        $defAlly = new Ally($target, $defPlayer);
        $modifier += $defAlly->IsDamaged() ? 2 : 0;
        break;
      case "7648077180"://97th Legion
        $modifier += NumResources($player);
        break;
      //Shadows of the Galaxy
      case "8def61a58e"://Kylo Ren
        $hand = &GetHand($player);
        $modifier -= LeaderAbilitiesIgnored() ? 0 : count($hand)/HandPieces();
        break;
      case "7486516061"://Concord Dawn Interceptors
      case "8069951120"://Jedi Guardian
        if($player == $defPlayer && GetAttackTarget() == "THEIRALLY-" . $index) $modifier += 2;
        break;
      case "6769342445"://Jango Fett
        if(IsAllyAttackTarget() && $player == $mainPlayer) {
          $defAlly = new Ally(GetAttackTarget(), $defPlayer);
          if($defAlly->HasBounty()) $modifier += 3;
        }
        break;
      case "4511413808"://Follower of the Way
        if($ally->IsUpgraded()) $modifier += 1;
        break;
      case "58f9f2d4a0"://Dr. Aphra
        $discard = &GetDiscard($player);
        $costs = [];
        for($i = 0; $i < count($discard); $i += DiscardPieces()) {
          $cost = CardCost($discard[$i]);
          $costs[$cost] = true;
        }
        if(count($costs) >= 5) $modifier += 3;
        break;
      case "8305828130"://Warbird Stowaway
        $modifier += $initiativePlayer == $player ? 2 : 0;
        break;
      //Twilight of the Republic
      case "2265363405"://Echo
        if(IsCoordinateActive($player)) $modifier += 2;
        break;
      case "1209133362"://332nd Stalwart
        if(IsCoordinateActive($player)) $modifier += 1;
        break;
      case "4718895864"://Padawan Starfighter
        if(SearchCount(SearchAllies($player, trait:"Force"))) return 1;
        else if(SearchCount(SearchUpgrades($player, trait:"Force"))) return 1;
        break;
      case "9227411088"://Clone Heavy Gunner
        if(IsCoordinateActive($player)) $modifier += 2;
        break;
      case "7224a2074a"://Ahsoka Tahno
        if(IsCoordinateActive($player)) $modifier += 2;
        break;
      case "11299cc72f"://Pre Viszla
        $hand = &GetHand($player);
        if(count($hand)/HandPieces() >= 6) $modifier += 2;
        break;
      case "24a81d97b5"://Anakin Skywalker Leader Unit
        if(LeaderAbilitiesIgnored()) break;
        $modifier += floor(GetBaseDamage($player)/5);
        break;
      case "8139901441"://Bo-Katan Kryze
        if(SearchCount(SearchAllies($player, trait:"Trooper")) > 1) $modifier += 1;
        break;
      case "1368135704"://Relentless Rocket Droid
        if(SearchCount(SearchAllies($player, trait:"Trooper")) > 1) $modifier += 2;
        break;
      case "4551109857"://Anakin's Interceptor
        if(!$ally->LostAbilities() && GetBaseDamage($player) >= 15) $modifier += 2;
        break;
      case "7099699830"://Jyn Erso
        global $CS_NumAlliesDestroyed;
        if(GetClassState($otherPlayer, $CS_NumAlliesDestroyed) > 0) $modifier += 1;
        break;
      //Jump to Lightspeed
      case "3389903389"://Black One JTL
        if ($ally->IsUpgraded()) $modifier += 1;
        break;
      case "2177194044"://Swarming Vulture Droid
        $modifier += (SearchCount(SearchAllies($player, cardTitle:"Swarming Vulture Droid")) - 1);
        break;
      case "8845408332"://Millennium Falcon (Get Out and Push)
        $upgrades = $ally->GetUpgrades();
        for($i = 0; $i < count($upgrades); ++$i) {
          if(TraitContains($upgrades[$i], "Pilot", $player)) $modifier += 1;
        }
        break;
      case "1463418669"://IG-88
        $modifier += SearchCount(SearchAllies($otherPlayer, damagedOnly:true)) > 0 ? 3 : 0;
        break;
      case "6610553087"://Nien Nunb
        $modifier += CountPilotUnitsAndPilotUpgrades($player, other: true);
        break;
      case "5422802110"://D'Qar Cargo Frigate
        $modifier -= $ally->Damage();
        break;
      case "5052103576"://Resistance X-Wing
        if($ally->HasPilot()) $modifier += 1;
        break;
      case "4203363893"://War Juggernaut
        $modifier += SearchCount(SearchAllies($player, damagedOnly:true));
        $modifier += SearchCount(SearchAllies($otherPlayer, damagedOnly:true));
        break;
      case "3213928129"://Clone Combat Squadron
        $modifier += (SearchCount(SearchAllies($player, arena:"Space")) - 1);
        break;
      case "6931439330"://The Ghost SOR (with Phantom II)
      case "5763330426"://The Ghost JTL (with Phantom II)
        $modifier += $ally->HasUpgrade("5306772000") ? 3 : 0;
        break;
      //Legends of the Force
      case "1540696516"://Scimitar
        $modifier += ($ally->Damage() > 0) ? 3 : 0;
        break;
      case "9722568619"://Captain Enoch
        $discard = &GetDiscard($player);
        $count = 0;
        for($i=0; $i<count($discard); $i+=DiscardPieces()) {
          if(TraitContains($discard[$i], "Trooper", $player)) ++$count;
        }
        $modifier += $count;
        break;
      case "4082337781"://Sith Legionnaire
        $modifier += (SearchCount(SearchAllies($player, aspect:"Villainy")) > 1) ? 2 : 0;
        break;
      case "fadc48bab2"://Kanan Jarrus Leader unit
        if(LeaderAbilitiesIgnored()) break;
        //see note in AllyHasStaticHealthModifier about potentially needing to check if Kanan becomes a Creature
        $atLeastOneCreature = SearchCount(SearchAllies($player, trait:"Creature")) > 0;
        $atLeastAnotherSpectre = SearchCount(SearchAllies($player, trait:"Spectre")) > 1;
        $modifier += ($atLeastOneCreature || $atLeastAnotherSpectre) ? 2 : 0;
        break;
      case "90e2d4d83e"://Kit Fisto Leader unit
        if(LeaderAbilitiesIgnored()) break;
        $modifier += (SearchCount(SearchAllies($player, trait:"Jedi")) - 1);
        break;
      case "e6dc0d1cee"://Avar Kriss Leadeer unit
        $modifier += HasTheForce($player) ? 4 : 0;
        break;
      case "0035741177"://Jedi Vector
        $modifier += SearchCount(SearchAllies($player, trait:"Jedi")) > 1 ? 1 : 0; //Another Jedi
        $modifier += SearchCount(SearchUpgrades($player, trait:"Lightsaber")) > 0 ? 1 : 0; //Lightsaber upgrade
        break;
      case "0126487527"://Axe Woves
        $upgrades = $ally->GetUpgrades();
        for($i = 0; $i < count($upgrades); ++$i) {
          $modifier += 1;
        }
        break;
      case "3337614029"://Paz Vizsla
        $modifier += 2 * $ally->Damage();
        break;
      default: break;
    }
  }

  if(!IsMultiTargetAttackActive() && GetAttackTarget() != "NA" && count($currentTurnEffects) > 0) {
    for($i=0;$i<count($currentTurnEffects);$i+=CurrentTurnPieces()) {
      switch($currentTurnEffects[$i]) {
        case "9399634203"://I Have the High Ground
          $target = GetAttackTarget();
          if($target == "THEIRCHAR-0") break;
          $defendingAlly = new Ally($target, $defPlayer);
          if($player != $defPlayer && $currentTurnEffects[$i+1] == $defPlayer && $currentTurnEffects[$i+2] == $defendingAlly->UniqueID()) {
            $modifier -= 4;
          }
          break;
        default: break;
      }
    }
  }

  return $modifier;
}

function BlockModifier($cardID, $from, $resourcesPaid)
{
  global $defPlayer, $mainPlayer, $combatChain, $chainLinks;
  $blockModifier = 0;
  switch($cardID) {

    default: break;
  }
  return $blockModifier;
}

function PlayBlockModifier($cardID)
{
  switch($cardID) {

    default: return 0;
  }
}

function OnDefenseReactionResolveEffects()
{
  global $currentTurnEffects, $defPlayer, $combatChain;
  switch($combatChain[0])
  {
      default: break;
  }
  for($i = count($currentTurnEffects) - CurrentTurnPieces(); $i >= 0; $i -= CurrentTurnPieces()) {
    $remove = false;
    if($currentTurnEffects[$i + 1] == $defPlayer) {
      switch($currentTurnEffects[$i]) {

        default: break;
      }
    }
    if($remove) RemoveCurrentTurnEffect($i);
  }
}

function OnBlockResolveEffects()
{


}

function BeginningReactionStepEffects()
{
  global $combatChain, $mainPlayer, $defPlayer;
  switch($combatChain[0])
  {
    case "OUT050":
      if(ComboActive())
      {
        $blockingCards = GetChainLinkCards($defPlayer);
        if($blockingCards != "")
        {
          $blockArr = explode(",", $blockingCards);
          $index = $blockArr[GetRandom(0, count($blockArr) - 1)];
          AddDecisionQueue("PASSPARAMETER", $defPlayer, $index, 1);
          AddDecisionQueue("REMOVECOMBATCHAIN", $defPlayer, "-", 1);
          AddDecisionQueue("MULTIBANISH", $defPlayer, "CC,-", 1);
        }
      }
  }
}

function ModifyBlockForType($type, $amount)
{
  global $combatChain, $defPlayer;
  $count = 0;
  for($i=CombatChainPieces(); $i<count($combatChain); $i+=CombatChainPieces())
  {
    if($combatChain[$i+1] != $defPlayer) continue;
    if(CardType($combatChain[$i]) != $type) continue;
    ++$count;
    $combatChain[$i+6] += $amount;
  }
  return $count;
}

//FAB
// function OnBlockEffects($index, $from)
// {
//   global $currentTurnEffects, $combatChain, $currentPlayer, $combatChainState, $CCS_WeaponIndex, $mainPlayer;
//   $cardType = CardType($combatChain[$index]);
//   $otherPlayer = ($currentPlayer == 1 ? 2 : 1);
//   for($i = count($currentTurnEffects) - CurrentTurnPieces(); $i >= 0; $i -= CurrentTurnPieces()) {
//     $remove = false;
//     if($currentTurnEffects[$i + 1] == $currentPlayer) {
//       switch($currentTurnEffects[$i]) {
//         case "WTR092": case "WTR093": case "WTR094":
//           if(HasCombo($combatChain[$index])) {
//             $combatChain[$index + 6] += 2;
//           }
//           $remove = true;
//           break;
//         case "ELE004":
//           if($cardType == "DR") {
//             PlayAura("ELE111", $currentPlayer);
//           }
//           break;
//         case "DYN042": case "DYN043": case "DYN044":
//           if(ClassContains($combatChain[$index], "GUARDIAN", $currentPlayer) && CardSubType($combatChain[$index]) == "Off-Hand")
//           {
//             if($currentTurnEffects[$i] == "DYN042") $amount = 6;
//             else if($currentTurnEffects[$i] == "DYN043") $amount = 5;
//             else $amount = 4;
//             $combatChain[$index + 6] += $amount;
//             $remove = true;
//           }
//           break;
//         case "DYN115": case "DYN116":
//           if($cardType == "AA") $combatChain[$index + 6] -= 1;
//           break;
//         case "OUT005": case "OUT006":
//           if($cardType == "AR") $combatChain[$index + 6] -= 1;
//           break;
//         case "OUT007": case "OUT008":
//           if($cardType == "A") $combatChain[$index + 6] -= 1;
//           break;
//         case "OUT009": case "OUT010":
//           if($cardType == "E") $combatChain[$index + 6] -= 1;
//           break;
//         default:
//           break;
//       }
//     } else if($currentTurnEffects[$i + 1] == $otherPlayer) {
//       switch($currentTurnEffects[$i]) {
//         case "MON113": case "MON114": case "MON115":
//           if($cardType == "AA" && NumAttacksBlocking() == 1) {
//               AddCharacterEffect($otherPlayer, $combatChainState[$CCS_WeaponIndex], $currentTurnEffects[$i]);
//               WriteLog(CardLink($currentTurnEffects[$i], $currentTurnEffects[$i]) . " gives your weapon +1 for the rest of the turn.");
//           }
//           break;
//         default:
//           break;
//       }
//     }
//     if($remove) RemoveCurrentTurnEffect($i);
//   }
//   $currentTurnEffects = array_values($currentTurnEffects);
//   switch($combatChain[0]) {
//     case "CRU079": case "CRU080":
//       if($cardType == "AA" && NumAttacksBlocking() == 1) {
//         AddCharacterEffect($otherPlayer, $combatChainState[$CCS_WeaponIndex], $combatChain[0]);
//         WriteLog(CardLink($combatChain[0], $combatChain[0]) . " got +1 for the rest of the turn.");
//       }
//       break;
//     default:
//       break;
//   }
// }

function NumNonEquipmentDefended()
{
  global $combatChain, $defPlayer;
  $number = 0;
  for($i = 0; $i < count($combatChain); $i += CombatChainPieces()) {
    $cardType = CardType($combatChain[$i]);
    if($combatChain[$i + 1] == $defPlayer && $cardType != "E" && $cardType != "C") ++$number;
  }
  return $number;
}

//FAB
// function CombatChainPlayAbility($cardID)
// {
//   global $combatChain, $defPlayer;
//   for($i = 0; $i < count($combatChain); $i += CombatChainPieces()) {
//     switch($combatChain[$i]) {
//       case "EVR122":
//         if(ClassContains($cardID, "WIZARD", $defPlayer)) {
//           $combatChain[$i + 6] += 2;
//           WriteLog(CardLink($combatChain[$i], $combatChain[$i]) . " gets +2 defense");
//         }
//         break;
//       default: break;
//     }
//   }
// }

function AnyCombatDamageDefeats($includeLeaders = false) {
  global $combatChainState, $CCS_DamageDealt, $defPlayer;
  if(IsAllyAttackTarget() && $combatChainState[$CCS_DamageDealt] > 0) {
    $ally = new Ally(GetAttackTarget(), $defPlayer);
    if($ally->Exists() && ($includeLeaders || !DefinedTypesContains($ally->CardID(), "Leader", $defPlayer))) {
      DestroyAlly($defPlayer, $ally->Index(), fromCombat:true);
    }
  }
}

function IsDominateActive()
{
  global $currentTurnEffects, $mainPlayer, $CCS_WeaponIndex, $combatChain, $combatChainState;
  global $CS_NumAuras, $chainLinks, $chainLinkSummary;
  if(count($combatChain) == 0) return false;
  if(SearchCurrentTurnEffectsForCycle("EVR097", "EVR098", "EVR099", $mainPlayer)) return false;
  $characterEffects = GetCharacterEffects($mainPlayer);
  for($i = 0; $i < count($currentTurnEffects); $i += CurrentTurnEffectPieces()) {
    if($currentTurnEffects[$i + 1] == $mainPlayer && IsCombatEffectActive($currentTurnEffects[$i]) && !IsCombatEffectLimited($i) && DoesEffectGrantDominate($currentTurnEffects[$i])) return true;
  }
  for($i = 0; $i < count($characterEffects); $i += CharacterEffectPieces()) {
    if($characterEffects[$i] == $combatChainState[$CCS_WeaponIndex]) {
      switch($characterEffects[$i + 1]) {
        case "WTR122": return true;
        default: break;
      }
    }
  }
  switch($combatChain[0]) {


    default: break;
  }
  return false;
}

function IsOverpowerActive()
{
  global $combatChain, $mainPlayer;
  if(count($combatChain) == 0) return false;
  switch($combatChain[0]) {
    case "DYN068": return SearchCurrentTurnEffects("DYN068", $mainPlayer);
    case "DYN088": return true;
    case "DYN227": case "DYN228": case "DYN229": return SearchCurrentTurnEffects("DYN227", $mainPlayer);
    case "DYN492a": return true;
    default: break;
  }
  return false;
}


?>
