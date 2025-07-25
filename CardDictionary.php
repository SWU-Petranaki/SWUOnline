<?php

include "Constants.php";
include "GeneratedCode/GeneratedCardDictionaries.php";

function CardName($cardID) {
  if(!$cardID || $cardID == "" || strlen($cardID) < 3) return "";
  return CardTitle($cardID) . " " . CardSubtitle($cardID);
}

// 0: portrait, 1: landscape
function CardOrientation($cardID): int {
  if (DefinedCardType($cardID) == "Base") return 1;
  if (DefinedCardType($cardID) == "Leader" && DefinedCardType2($cardID) != "Unit") return 1;
  return 0;
}

function CardType($cardID)
{
  if(!$cardID) return "";
  $definedCardType = DefinedCardType($cardID);
  if($definedCardType == "Leader") return "C";
  else if($definedCardType == "Base") return "W";
  return "A";
}

function CardSubType($cardID)
{
  if(!$cardID) return "";
  return "";
  //return CardSubTypes($cardID);
}
//FAB
// function CharacterHealth($cardID)
// {
//   if($cardID == "DUMMY") return 1000;
//   return CardLife($cardID);
// }

function CharacterIntellect($cardID)
{
  switch($cardID) {
    default: return 4;
  }
}

//FAB
// function CardClass($cardID)
// {
//   return CardClasses($cardID);
// }

function NumResources($player) {
  $resources = &GetResourceCards($player);
  return count($resources)/ResourcePieces();
}

function NumResourcesAvailable($player) {
  $resources = &GetResourceCards($player);
  $numAvailable = 0;
  for($i=0; $i<count($resources); $i+=ResourcePieces()) {
    if($resources[$i+4] == 0) ++$numAvailable;
  }
  return $numAvailable;
}

//FAB
// function CardTalent($cardID)
// {
//   $set = substr($cardID, 0, 3);
//   if($set == "MON") return MONCardTalent($cardID);
//   else if($set == "ELE") return ELECardTalent($cardID);
//   else if($set == "UPR") return UPRCardTalent($cardID);
//   else if($set == "DYN") return DYNCardTalent($cardID);
//   else if($set == "ROG") return ROGUECardTalent($cardID);
//   return "NONE";
// }

function RestoreAmount($cardID, $player, $index, $isRecursion = false)
{
  global $initiativePlayer, $currentTurnEffects;
  $amount = 0;
  $allies = &GetAllies($player);
  for($i=0; $i<count($allies); $i+=AllyPieces())
  {
    switch($allies[$i])
    {
      case "4919000710"://Home One
        if($index != $i) $amount += 1;
        break;
      default: break;
    }
  }
  $ally = new Ally("MYALLY-" . $index, $player);
  for($i=0; $i<count($currentTurnEffects); $i+=CurrentTurnPieces()) {
    if($currentTurnEffects[$i+1] != $player) continue;
    if($currentTurnEffects[$i] ==  "3148212344_Restore_1" && TraitContains($cardID, "Vehicle", $player)) $amount += 1;//Admiral Yularen - unique ID belongs to Yularen
    if($currentTurnEffects[$i+2] != -1 && $currentTurnEffects[$i+2] != $ally->UniqueID()) continue;
    switch($currentTurnEffects[$i]) {
      case "1272825113"://In Defense of Kamino
        $amount += 2;
        break;
      case "7924461681"://Leia Organa
        $amount += 1;
        break;
      case "4808722909"://Yaddle
        $amount += 1;
        break;
      default: break;
    }
  }
  $upgrades = $ally->GetUpgrades();
  for($i=0; $i<count($upgrades); ++$i) {
    $upgradeCardID = $upgrades[$i];
    switch($upgradeCardID) {
      case "8788948272":
        $amount += 2;
        break;
      case "7884488904"://For The Republic
        $amount += IsCoordinateActive($player) ? 2 : 0;
        break;
      //Jump to Lightspeed
      case "9430527677"://Hera Syndulla Pilot
        $amount += 1;
        break;
      case "3688574857"://Constructed Lightsaber
          if(AspectContains($cardID, "Heroism", $player))
          $amount += 2;
          break;
      case "9852723156"://Heriloom Lightsaber
        if(TraitContains($cardID, "Force", $player))
        $amount += 1;;
        break;
    }
  }
  switch($cardID)
  {
    case "0074718689": $amount += 1; break;//Restored Arc 170
    case "1081012039": $amount += 2; break;//Regional Sympathizers
    case "1611702639": $amount += $initiativePlayer == $player ? 2 : 0; break;//Consortium Starviper
    case "4405415770": $amount += 2; break;//Yoda (Old Master)
    case "0827076106": $amount += 1; break;//Admiral Ackbar
    case "4919000710": $amount += 2; break;//Home One
    case "9412277544": $amount += 1; break;//Del Meeko
    case "e2c6231b35": $amount += !LeaderAbilitiesIgnored() ? 2 : 0; break;//Director Krennic Leader Unit
    case "7109944284": $amount += 3; break;//Luke Skywalker unit
    //Shadows of the Galaxy
    case "8142386948": $amount += 2; break;//Razor Crest
    case "4327133297": $amount += 2; break;//Moisture Farmer
    case "5977238053": $amount += 2; break;//Sundari Peacekeeper
    case "9503028597": $amount += 1; break;//Clone Deserter
    case "5511838014": $amount += 1; break;//Kuiil
    case "e091d2a983": $amount += 3; break;//Rey
    case "7022736145": $amount += 2; break;//Tarfful
    case "6870437193": $amount += 2; break;//Twin Pod Cloud Car
    case "3671559022": $amount += 2; break;//Echo
    //Twilight of the Republic
    case "9185282472": $amount += 2; break;//ETA-2 Light Interceptor
    case "5350889336": $amount += 3; break;//AT-TE Vanguard
    case "3420865217": $amount += $ally->IsDamaged() ? 0 : 2; break;//Daughter of Dathomir
    case "6412545836": $amount += 1; break;//Morgan Elsbeth
    case "0268657344": $amount += 1; break;//Admiral Yularen
    case "e71f6f766c": $amount += 2; break;//Yoda
    case "3381931079": $amount += 2; break;//Malevolence
    case "4ae6d91ddc": $amount += 1; break;//Padme Amidala
    //Jump to Lightspeed
    case "7924461681": $amount += 1; break;//Leia Organa
    case "0753794638": $amount += 2; break;//Corvus
    case "9430527677": $amount += 1; break;//Hera Syndulla
    case "7610382003": $amount += 2; break;//CR90 Relief Runner
    case "7500360419": $amount += 2; break;//Adept ARC-170
    //Legends of the Force
    case "6797297267": $amount += 2; break;//Darth Sidious
    case "754e979196": $amount += 1; break;//Darth Revan Leader Unit
    case "5264998537": $amount += 2; break;//Owen Lars
    case "1978321046": $amount += 1; break;//Village Tender
    case "4808722909": $amount += 1; break;//Yaddle
    case "6745607382": $amount += 2; break;//Jedi Temple Guards
    case "6180656125": $amount += 1; break;//Eye of Sion
    case "5887691594": $amount += 2; break;//Magistrate's Scout
    case "2628450872": $amount += 1; break;//Gungan Warrior
    case "9023764122": $amount += 1; break;//Hive Defense Wing
    case "9107238716": $amount += 1; break;//Longbeam Cruiser
    case "3242169334": $amount += 2; break;//Relic Scavenger
    case "2968188569": $amount += 4; break;//The Purggil King
    case "2370458497"://Oppo Rancisis
      if($isRecursion) return false; //Prevent recursion
      $units = &GetAllies($player);
      for($i=0; $i<count($units); $i+=AllyPieces()) {
        if($i == $index) continue;
        if(RestoreAmount($units[$i], $player, $i, true)) {
          $amount += 2;
          break;
        }
      }
      break;
    //Inro Battle: Hoth
    case "1174196426": $amount += 2; break;//Luke Skywalker
    case "0008529481": $amount += 1; break;//Lambda Shuttle
    default: break;
  }
  //The Ghost JTL
  $theGhostIndex = SearchAlliesForCard($player, "5763330426");
  if($theGhostIndex != "" && TraitContains($cardID, "Spectre", $player)
      && $index != $theGhostIndex
      && RestoreAmount("5763330426", $player, $theGhostIndex) > 0)
    $amount += RestoreAmount("5763330426", $player, $theGhostIndex);

  if($amount > 0 && $ally->LostAbilities()) return 0;
  return $amount;
}

function ExploitAmount($cardID, $player, $reportMode=true) {
  global $currentTurnEffects;
  $amount = 0;
  for($i=count($currentTurnEffects)-CurrentTurnPieces(); $i>=0; $i-=CurrentTurnPieces()) {
    if($currentTurnEffects[$i+1] != $player) continue;
    $remove = false;
    switch($currentTurnEffects[$i]) {
      case "5683908835"://Count Dooku
        $amount += 1;
        $remove = true;
        break;
      case "6fa73a45ed"://Count Dooku Leader Unit
        if(TraitContains($cardID, "Separatist", $player)) {
          $amount += 3;
          $remove = true;
        }
        break;
      default: break;
    }
    if($remove) {
      if(!$reportMode) RemoveCurrentTurnEffect($i);
    }
  }
  switch($cardID) {
    case "6772128891": $amount += 2; break;//Hailfire Tank
    case "6623894685": $amount += 1; break;//Infiltrating Demolisher
    case "6700679522": $amount += 2; break;//Tri-Droid Suppressor
    case "8201333805": $amount += 3; break;//Squadron of Vultures
    case "9283787549": $amount += 3; break;//Separatist Super Tank
    case "3348783048": $amount += 2; break;//Geonosis Patrol Fighter
    case "2554988743": $amount += 3; break;//Gor
    case "1320229479": $amount += 2; break;//Multi-Troop Transport
    case "1083333786": $amount += 2; break;//Battle Droid Legion
    case "5243634234": $amount += 2; break;//Baktoid Spider Droid
    case "5084084838": $amount += 2; break;//Droideka Security
    case "6436543702": $amount += 2; break;//Providence Destroyer
    case "8655450523": $amount += 2; break;//Count Dooku
    case "0021045666": $amount += 3; break;//San Hill
    case "4210027426": $amount += 2; break;//Heavy Persuader Tank
    case "7013591351": $amount += 1; break;//Admiral Trench
    case "2565830105": $amount += 4; break;//Invasion of Christophsis
    case "2041344712": $amount += 3; break;//Osi Sobeck
    case "3381931079": $amount += 4; break;//Malevolence
    case "3556557330": $amount += 2; break;//Asajj Ventress (Count Dooku's Assassin)
    case "3589814405": $amount += 2; break;//Tactical Droid Commander
    case "1167572655": $amount += 3; break;//Planetary Invasion
    default: break;
  }
  return $amount;
}

function RaidAmount($cardID, $player, $index, $reportMode = false, $isRecursion = false)
{
  global $currentTurnEffects, $combatChain;
  if(count($combatChain) == 0 && !$reportMode) return 0;
  $amount = 0;
  $allies = &GetAllies($player);
  for($i=0; $i<count($allies); $i+=AllyPieces())
  {
    switch($allies[$i])
    {
      case "8995892693"://Red Three
        if($index != $i && AspectContains($cardID, "Heroism", $player)) $amount += 1;
        break;
      case "fb475d4ea4"://IG-88 Leader Unit
        if($index != $i) $amount += 1;
        break;
      case "9921128444"://General Hux
        if($index != $i && TraitContains($cardID, "First Order", $player)) $amount += 1;
        break;
      case "9937756875 "://Invasion Control Ship
        if(TraitContains($cardID, "Droid", $player)) $amount +=2;
        break;
      default: break;
    }
  }
  $ally = new Ally("MYALLY-" . $index, $player);
  for($i=0; $i<count($currentTurnEffects); $i+=CurrentTurnPieces()) {
    if($currentTurnEffects[$i+1] != $player) continue;
    if($currentTurnEffects[$i+2] != -1 && $currentTurnEffects[$i+2] != $ally->UniqueID()) continue;
    switch($currentTurnEffects[$i]) {
      case "0256267292"://Benthic "Two Tubes"
        $amount += 2;
        break;
      case "1208707254"://Rallying Cry
        $amount += 2;
        break;
      case "8719468890"://Sword and Shield Maneuver
        $amount += TraitContains($cardID, "Trooper", $player) ? 1 : 0;
        break;
      case "8743459187"://Focus Determines Reality
        $amount += TraitContains($cardID, "Force", $player) ? 1 : 0;
        break;
      default: break;
    }
  }
  $upgrades = $ally->GetUpgrades();
  for($i=0; $i<count($upgrades); ++$i)
  {
    if($upgrades[$i] == "2007876522") $amount += 2;//Clone Cohort
    if($upgrades[$i] == "0587196584") $amount += 1;//Independent Smuggler
    if($upgrades[$i] == "3688574857")//Constructed Lightsaber
      if(AspectContains($cardID, "Villainy", $player)) $amount += 2;
  }
  switch($cardID)
  {
    //card specific
    //Spark of Rebellion
    case "1017822723": $amount += 2; break; //Rogue Operative
    case "2404916657": $amount += 2; break; //Cantina Braggart
    case "7495752423": $amount += 2; break; //Green Squadron A-Wing
    case "4642322279": $amount += SearchCount(SearchAllies($player, aspect:"Aggression")) > 1 ? 2 : 0; break;//Partisan Insurgent
    case "6028207223": $amount += 1; break; //Pirated Starfighter
    case "8995892693": $amount += 1; break; //Red Three
    case "3613174521": $amount += 1; break; //Outer Rim Headhunter
    case "4111616117": $amount += 1; break; //Volunteer Soldier
    case "87e8807695": $amount += !LeaderAbilitiesIgnored() ? 1 : 0; break; //Leia Leader Unit
    case "8395007579": $amount += $ally->MaxHealth() - $ally->Health(); break;//Fifth Brother
    case "6208347478": $amount += SearchCount(SearchAllies($player, trait:"Spectre")) > 1 ? 1 : 0; break;//Chopper
    //Shadows of the Galaxy
    case "3487311898": $amount += 3; break;//Clan Challengers
    case "5977238053": $amount += 2; break;//Sundari Peacekeeper
    case "1805986989": $amount += 2; break;//Modded Cohort
    case "415bde775d": $amount += 1; break;//Hondo Ohnaka Leader Unit
    case "724979d608": $amount += !LeaderAbilitiesIgnored() ? 2 : 0; break;//Cad Bane Leader Unit
    case "5818136044": $amount += 2; break;//Xanadu Blood
    case "8991513192": $amount += SearchCount(SearchAllies($player, aspect:"Aggression")) > 1 ? 2 : 0; break;//Hunting Nexu
    case "1810342362": $amount += 2; break;//Lurking TIE Phantom
    //Twilight of the Republic
    case "8426882030": $amount += 1; break;//Ryloth Militia
    case "8247495024": $amount += 1; break;//Seasoned Fleet Admiral
    case "5936350569": $amount += 1; break;//Jesse
    case "2800918480": $amount += 1; break;//Soldier of the 501st
    case "7494987248": $amount += IsCoordinateActive($player) ? 3 : 0; break;//Plo Koon
    case "5027991609": $amount += SearchCount(SearchAllies($player, trait:"Separatist")) > 1 ? 2 : 0; break;//Separatist Commando
    case "0354710662": $amount += 2; break;//Saw Gerrera
    case "0683052393": $amount += IsCoordinateActive($player) ? 2 : 0; break;//Hevy
    case "9964112400": $amount += 2; break;//Rush Clovis
    case "0249398533": $amount += 1; break;//Obedient Vanguard
    //Jump to Lightspeed
    case "0587196584": $amount += 1; break;//Independent Smuggler
    case "1034181657": $amount += SearchCount(SearchAllies($player, tokenOnly: true)) > 0 ? 1 : 0; break;//First Order TIE Fighter
    case "0756051511": $amount += 1; break;//MC30 Assault Frigate
    case "0626954301": $amount += SearchCount(SearchAllies($player, trait:"Fighter")) > 1 ? 2 : 0; break;//Flanking Fang Fighter
    case "7458361203": $amount += 1; break;//Corporate Light Cruiser
    case "2948071304": $amount += ($ally->CurrentPower(reportMode:true) + $amount) >= 6 ? 1 : 0; break;//Vonreg's TIE Interceptor
    //Legends of the Force
    case "6082085272": $amount += 1; break;//Forged Starfighter
    case "2290426901": $amount += 3; break;//Thralls of the Coven
    case "4043787241": $amount += 3; break;//Strikeship
    case "7877401512": $amount += 2; break;//Aurra Sing
    case "4460062481": $amount += 3; break;//Nihil Marauder
    case "4464627339": $amount += 1; break;//Blue Suqadron Assault Wing
    case "3288909829": $amount += 2; break;//Cartel Interceptor
    case "0686684746": $amount += 1; break;//Grand Inquisitor LOF
    case "1548886844": $amount += 2; break;//Tusken Tracker
    case "5522824465"://Life Wind Sage
      $otherPlayer = $player == 1 ? 2 : 1;
      $opponentAllies = &GetAllies($otherPlayer);
      for ($j = 0; $j < count($opponentAllies); $j += AllyPieces()) {
        $opponentAlly = new Ally("MYALLY-" . $j, $otherPlayer);
        if ($opponentAlly->IsExhausted()) {
          $amount += 2;
          break;
        }
      }
      break;
    case "2370458497"://Oppo Rancisis
      if($isRecursion) return false; //Prevent recursion
      $units = &GetAllies($player);
      for($i=0; $i<count($units); $i+=AllyPieces()) {
        if($i == $index) continue;
        if(RaidAmount($units[$i], $player, $i, true, true)) {
          $amount += 2;
          break;
        }
      }
      break;
    //Inro Battle: Hoth
    case "8332320550": $amount += 2; break;//Rogue Squadron Speeder
    case "6815960456": $amount += 3; break;//E-Web Gunner
    case "9127322562": $amount += 1; break;//Surface Assault Bomber
    case "4136801536": $amount += 2; break;//Han Solo
    default: break;
  }
  //The Ghost JTL
  $theGhostIndex = SearchAlliesForCard($player, "5763330426");
  if($theGhostIndex != "" && TraitContains($cardID, "Spectre", $player)
      && $index != $theGhostIndex
      && RaidAmount("5763330426", $player, $theGhostIndex, $reportMode) > 0)
    $amount += RaidAmount("5763330426", $player, $theGhostIndex, $reportMode);

  //Marchion Ro
  $marchionRoIndex = SearchAlliesForCard($player, "4627342747");
  if($marchionRoIndex !== false && $marchionRoIndex !== "") $amount*=2;

  if($amount > 0 && $ally->LostAbilities()) return 0;

  return $amount;
}

function HasSentinel($cardID, $player, $index, $isRecursion = false)
{
  global $initiativePlayer, $currentTurnEffects;
  $ally = new Ally("MYALLY-" . $index, $player);
  if($ally->LostAbilities()) return false;
  $hasSentinel = false;
  //Effect Sentinel
  for($i=0; $i<count($currentTurnEffects); $i+=CurrentTurnPieces()) {
    if($currentTurnEffects[$i+1] != $player) continue;
    if($currentTurnEffects[$i] ==  "3148212344_Sentinel" && TraitContains($cardID, "Vehicle", $player)) return true;//Admiral Yularen - unique ID belongs to Yularen
    if($currentTurnEffects[$i+2] != -1 && $currentTurnEffects[$i+2] != $ally->UniqueID()) continue;
    $effectParams = explode("_", $currentTurnEffects[$i]);
    $effectCardID = $effectParams[0];
    switch($effectCardID) {
      case "8294130780": $hasSentinel = true; break;//Gladiator Star Destroyer
      case "3572356139": $hasSentinel = true; break;//Chewbacca (Walking Carpet)
      case "3468546373": $hasSentinel = true; break;//General Rieekan
      case "2359136621": $hasSentinel = true; break;//Guarding The Way
      case "9070397522": return false;//SpecForce Soldier
      case "2872203891": $hasSentinel = true; break;//General Grievous Leader
      case "fb7af4616c": $hasSentinel = true; break;//General Grievous Leader unit
      case "3272995563": $hasSentinel = true; break;//In The Heat of Battle
      case "1039828081": if ($cardID == "1039828081") {$hasSentinel = true;} break;//Calculating MagnaGuard
      case "3033790509": $hasSentinel = true; break;//Captain Typho
      case "8719468890"://Sword and Shield Maneuver
        if(TraitContains($cardID, "Jedi", $player)) $hasSentinel = true;
        break;
      case "7214707216": $hasSentinel = true; break;//Diversion
      case "2301911685": $hasSentinel = true; break;//Timely Reinforcements
      //Legends of the Force
      case "6059510270": $hasSentinel = true; break;//Obi-Wan Kenobi (Protective Padawan)
      case "7077983867": $hasSentinel = true; break;//Ahsoka Tano Leader
      case "1a61e6df76": $hasSentinel = true; break;//Ahsoka Tano Leader unit
      case "1906860379": $hasSentinel = true; break;//Force Illusion
      default: break;
    }
  }
  if($hasSentinel) return true;
  //Upgrades Sentinel
  $upgrades = $ally->GetUpgrades();
  for($i=0; $i<count($upgrades); ++$i)
  {
    switch($upgrades[$i]) {
      case "4550121827"://Protector
      case "4991712618"://Unshakeable Will
      case "3874382333"://Academy Graduate
      case "3064aff14f"://Lando Calrissian leader unit
        $hasSentinel = true;
        break;
      //conditional upgrade sentinel
      case "9706341387"://Jarek Yeager
        $hasSentinel = SearchCount(SearchAllies($player, arena:"Ground")) > 0
          && SearchCount(SearchAllies($player, arena:"Space")) > 0;
          break;
      case "3688574857"://Constructed Lightsaber
        if(!AspectContains($cardID, "Villainy", $player) && !(AspectContains($cardID, "Heroism", $player))) $hasSentinel = true;
        break;
      default: break;
    }
  }
  if($hasSentinel) return true;
  //Self Sentinel
  switch($cardID)
  {
    //Spark of Rebellion
    case "2524528997"://Cell Block Guard
    case "6385228745"://Corellian Freighter
    case "6912684909"://Echo Base Defender
    case "7751685516"://System Patrol Craft
    case "9702250295"://Cloud City Wing Guard
    case "6253392993"://Bright Hope
    case "7596515127"://Academy Defense Walker
    case "5707383130"://Bendu
    case "8918765832"://Chewbacca
    case "4631297392"://Devastator
    case "8301e8d7ef"://Chewbacca Leader unit
    case "4786320542"://Obi-Wan Kenobi
    case "3896582249"://Redemption
    case "2855740390"://Lieutenant Childsen
    //Shadows of the Galaxy
    case "1982478444"://Vigilant Pursuit Craft
    case "1747533523"://Village Protectors
    case "6585115122"://The Mandalorian unit
    case "2969011922"://Pyke Sentinel
    case "8552719712"://Pirate Battle Tank
    case "4843225228"://Phase-III Dark Trooper
    case "7486516061"://Concord Dawn Interceptors
    case "6409922374"://Niima Outpost Constables
    case "0315522200"://Black Sun Starfighter
    case "8228196561"://Clan Saxon Gauntlet
    //Twilight of the Republic
    case "7884088000"://Armored Saber Tank
    case "6330903136"://B2 Legionnaires
    case "6257858302"://B1 Security Team
    case "6238512843"://Republic Defense Carrier
    case "9927473096"://Patrolling AAT
    case "2554988743"://Gor
    case "8845972926"://Falchion Ion Tank
    case "5084084838"://Droideka Security
    case "0ee1e18cf4"://Obi-wan Kenobi
    //Jump to Lightspeed
    case "3874382333"://Academy Graduate
    case "0235116526"://Fleet Interdictor
    case "3064aff14f"://Lando Calrissian leader unit
    case "3584805138"://Scouting Headhunter
    case "9014161111"://Contracted Jumpmaster
    case "6854247423"://Tantive IV
    case "7508489374"://Wing Guard Security Team
    case "9056204789"://Perimeter AT-RT
    case "8776260462"://Shadowed Hover Tank
    case "6332346890"://Omicron Strike Craft
    case "1036605983"://Rogue-Class Starfighter
      return true;
    //conditional sentinel
    //Spark of Rebellion
    case "2739464284"://Gamorrean Guards
      return SearchCount(SearchAllies($player, aspect:"Cunning")) > 1;
    case "3138552659"://Homestead Militia
      return NumResources($player) >= 6;
    case "7622279662"://Vigilant Honor Guards
      $ally = new Ally("MYALLY-" . $index, $player);
      return !$ally->IsDamaged();
    case "5879557998"://Baze Melbus
      return $initiativePlayer == $player;
    case "1780978508"://Emperor's Royal Guard
    //Shadows of the Galaxy
      return SearchCount(SearchAllies($player, trait:"Official")) > 0;
    case "9405733493"://Protector of the Throne
      $ally = new Ally("MYALLY-" . $index, $player);
      return $ally->IsUpgraded();
    case "4590862665"://Gamorrean Retainer
        return SearchCount(SearchAllies($player, aspect:"Command")) > 1;
    case "4341703515"://Supercommando Squad
      $ally = new Ally("MYALLY-" . $index, $player);
      return $ally->IsUpgraded();
    case "9871430123"://Sugi
      $otherPlayer = $player == 1 ? 2 : 1;
      return SearchCount(SearchAllies($otherPlayer, hasUpgradeOnly:true)) > 0;
    //Twilight of the Republic
    case "8919416985"://Outspoken Representative
      return SearchCount(SearchAllies($player, trait:"Republic")) > 1;
    case "4179773207"://Infantry of the 212th
      return IsCoordinateActive($player);
    case "7289764651"://Duchess's Champion
      $otherPlayer = $player == 1 ? 2 : 1;
      return IsCoordinateActive($otherPlayer);
    //Jump to Lightspeed
    case "8779760486"://Raddus
      return SearchCount(SearchUpgrades($player, trait:"Resistance")) > 0
        || SearchCount(SearchAllies($player, trait:"Resistance")) > 1
        || SearchCount(SearchCharacter($player, trait:"Resistance")) > 0;
    case "8248876187"://Bunker Defender
      return SearchCount(SearchAllies($player, trait:"Vehicle")) > 0;
    case "5763330426"://The Ghost
      return $ally->IsUpgraded();
    //Legends of the Force
    case "0775347605"://Chirrut Imwe
    case "7504035101"://Loth-Wolf
    case "9213315483"://Graceful Purrgil
    case "6148303031"://Supremacy TIE/sf
    case "d911b778e4"://Kylo Ren Leader unit
      return true;
    case "5573238875"://Jedi Sentinel
      return HasTheForce($player);
    case "5841324811"://Praetorian Guard
      return SearchCount(SearchAllies($player, minAttack:4)) > 0;
    case "2370458497"://Oppo Rancisis
      if($isRecursion) return false; //Prevent recursion
      $units = &GetAllies($player);
      for($i=0; $i<count($units); $i+=AllyPieces()) {
        if($i == $index) continue;
        if(HasSentinel($units[$i], $player, $i, true)) return true;
      }
      break;
    //Inro Battle: Hoth
      case "1112130807"://Chewbacca
      case "5509338180"://Bright Hope
      case "0162722840"://Death Squadron Star Destroyer
      case "6199578966"://Blizzard Force AT-ST
        return true;
    default: break;
  }
  //The Ghost JTL
  $theGhostIndex = SearchAlliesForCard($player, "5763330426");
  if($theGhostIndex != "" && TraitContains($cardID, "Spectre", $player)
      && $index != $theGhostIndex
      && HasSentinel("5763330426", $player, $theGhostIndex))
    return true;

  return false;
}

function HasGrit($cardID, $player, $index, $isRecursion = false)
{
  global $currentTurnEffects;
  $ally = new Ally("MYALLY-" . $index, $player);
  if($ally->LostAbilities()) return false;
  if(!$ally->IsLeader()) {
    $allies = &GetAllies($player);
    for ($i = 0; $i < count($allies); $i += AllyPieces()) {
      switch ($allies[$i]) {
        case "4783554451"://First Light
          return true;
        default:
          break;
      }
    }
  }
  $upgrades = $ally->GetUpgrades();
  for($i=0; $i<count($upgrades); ++$i)
  {
    switch($upgrades[$i]) {
      case "3f0b5622a7"://Asajj Leader Unit
      case "3878744555"://Interceptor Ace
      case "5306772000"://Phantom II
        return true;
      case "2633842896"://Biggs Darklighter
        if(TraitContains($cardID, "Speeder", $player)) return true;
        break;
      case "9566815036"://Darth Revan's Lightsabers (yes there are two of them in one card)
        if(TraitContains($cardID, "Sith", $player)) return true;
        break;
      default: break;
    }
  }
  for($i=0; $i<count($currentTurnEffects); $i+=CurrentTurnPieces()) {
    if($currentTurnEffects[$i+1] != $player) continue;
    if($currentTurnEffects[$i] ==  "3148212344_Grit" && TraitContains($cardID, "Vehicle", $player)) return true;//Admiral Yularen - unique ID belongs to Yularen
    if($currentTurnEffects[$i+2] != -1 && $currentTurnEffects[$i+2] != $ally->UniqueID()) continue;
    switch($currentTurnEffects[$i]) {
      case "6669050232": return true;//Grim Resolve
      default: break;
    }
  }
  switch($cardID)
  {
    case "5335160564"://Guerilla Attack Pod
    case "9633997311"://Scout Bike Pursuer
    case "8098293047"://Occupier Siege Tank
    case "5879557998"://Baze Malbus (Temple Guardian)
    case "4599464590"://Rugged Survivors
    case "8301e8d7ef"://Chewbacca (Walking Carpet)
    case "5557494276"://Death Watch Loyalist
    case "6878039039"://Hylobon Enforcer
    case "8190373087"://Gentle Giant
    case "1304452249"://Covetous Rivals
    case "4383889628"://Wroshyr Tree Tender
    case "0252207505"://Synara San
    case "4783554451"://First Light
    case "4aa0804b2b"://Qi'Ra Leader Unit
    case "1477806735"://Wookiee Warrior
    case "9195624101"://Heroic Renegade
    case "5169472456"://Chewbacca Pykesbane
    case "8552292852"://Kashyyyk Defender
    case "6787851182"://Dwarf Spider Droid
    case "2761325938"://Devastating Gunship
    case "3f0b5622a7"://Asajj Leader Unit
    case "5412384703"://Royal Security Fighter
    case "3878744555"://Interceptor Ace
    case "5306772000"://Phantom II
      return true;
    case "9832122703"://Luminara Unduli
      return IsCoordinateActive($player);
    //Legends of the Force
    case "8633377277"://Pong Krell
    case "3612941171"://Sandtrooper Cavalry
      return true;
    case "2352895392"://Plo Koon
      return HasTheForce($player);
    case "2370458497"://Oppo Rancisis
      if($isRecursion) return false; //Prevent recursion
      $units = &GetAllies($player);
      for($i=0; $i<count($units); $i+=AllyPieces()) {
        if($i == $index) continue;
        if(HasGrit($units[$i], $player, $i, true)) return true;
      }
      break;
    default: break;
  }
  //The Ghost JTL
  $theGhostIndex = SearchAlliesForCard($player, "5763330426");
  if($theGhostIndex != "" && TraitContains($cardID, "Spectre", $player)
      && $index != $theGhostIndex
      && HasGrit("5763330426", $player, $theGhostIndex))
    return true;

  return false;
}

function HasCoordinate($cardID, $player, $index)
{
  $ally = new Ally("MYALLY-" . $index, $player);
  if($ally->LostAbilities()) return false;
  $upgrades = $ally->GetUpgrades();
  for ($i = 0; $i < count($upgrades); ++$i) {
    if($upgrades[$i] == "7884488904") return true;//For the republic
  }
  return match ($cardID) {
    "2260777958",//41st Elite Corps
    "9832122703",//Luminara Unduli
    "4179773207",//Infantry of the 212th
    "7200475001",//Ki-Adi-Mundi
    "2265363405",//Echo
    "9966134941",//Pelta Supply Frigate
    "6190335038",//Aayla Secura
    "7380773849",//Coruscant Guard
    "9017877021",//Clone Commander Cody
    "2282198576",//Anakin Skywalker
    "9227411088",//Clone Heavy Gunner
    "2298508689",//Reckless Torrent
    "0683052393",//Hevy
    "1641175580",//Kit Fisto
    "8307804692",//Padme Abmidala
    "7494987248",//Plo Koon
    "5445166624",//Clone Dive Trooper
    "4512764429",//Sanctioner's Shuttle
    "1209133362",//332nd Stalwart
    "8187818742",//Republic Commando
    "7224a2074a",//Ahsoka Tano
    "4ae6d91ddc" => true,//Padme Amidala
    default => false,
  };
}

function HasOverwhelm($cardID, $player, $index, $isRecursion=false)
{
  global $defPlayer, $currentTurnEffects, $mainPlayer;
  $ally = new Ally("MYALLY-" . $index, $player);
  if($ally->LostAbilities()) return false;
  $allies = &GetAllies($player);
  for($i=0; $i<count($allies); $i+=AllyPieces())
  {
    switch($allies[$i])
    {
      case "4484318969"://Moff Gideon Leader Unit //TODO: make a similar function for AttackerUID
        if(CardCost($cardID) <= 3 && IsAllyAttackTarget() && AttackerMZID($mainPlayer && !LeaderAbilitiesIgnored()) == "MYALLY-" . $index) return true;
        break;
      case "40b649e6f6"://Maul Leader Unit
        if($index != $i && !LeaderAbilitiesIgnored()) return true;
        break;
      case "9017877021"://Clone Commander Cody
        if($index != $i && IsCoordinateActive($player)) return true;
        break;
      case "3666212779"://Captain Tarkin
        if(TraitContains($cardID, "Vehicle", $player)) return true;
        break;
      default: break;
    }
  }
  for($i=0; $i<count($currentTurnEffects); $i+=CurrentTurnPieces()) {
    if($currentTurnEffects[$i+1] != $player) continue;
    if($currentTurnEffects[$i+2] != -1 && $currentTurnEffects[$i+2] != $ally->UniqueID()) continue;
    switch($currentTurnEffects[$i]) {
      case "6461101372"://Maul Leader
        return !LeaderAbilitiesIgnored();
      case "4085341914"://Heroic Resolve
      case "1167572655"://Planetary Invasion
      case "2167393423"://Darth Maul's Lightsaber
      case "3893171959"://Kaadu
      case "5049217986"://Overpower
        return true;
      default: break;
    }
  }
  // Check upgrades
  $upgrades = $ally->GetUpgrades();
  for($i=0; $i<count($upgrades); ++$i) {
    switch($upgrades[$i]) {
      case "0875550518"://Grievous's Wheel Bike
      case "4886127868"://Nameless Valor
        return true;
      case "2633842896"://Biggs Darklighter
        if(TraitContains($cardID, "Fighter", $player)) return true;
      default: break;
    }
  }
  switch($cardID)
  {
    case "6072239164"://AT-ST
    case "6577517407"://Wampa
    case "6718924441"://Mercenary Company
    case "9097316363"://Emperor Palpatine (Master of the Dark Side)
    case "3232845719"://K-2SO (Cassian's Counterpart)
    case "4631297392"://Devastator (Inescapable)
    case "6432884726"://Steadfast Battalion
    case "5557494276"://Death Watch Loyalist
    case "2470093702"://Wrecker
    case "4721657243"://Kihraxz Heavy Fighter
    case "5351496853"://Gideon's Light Cruiser
    case "4935319539"://Krayt Dragon
    case "8862896760"://Maul
    case "9270539174"://Wild Rancor
    case "3803148745"://Ruthless Assassin
    case "1743599390"://Trandoshan Hunters
    case "c9ff9863d7"://Hunter (Outcast Sergeant)
    case "3722493191"://IG-2000
    case "7072861308"://Profundity
    case "1087522061"://AT-DP Occupier
    case "9752523457"://Finalizer
    case "2870117979"://Executor
      return true;
    case "4619930426"://First Legion Snowtrooper
      $target = GetAttackTarget();
      if($target == "THEIRCHAR-0") return false;
      $targetAlly = new Ally($target, $defPlayer);
      return $targetAlly->IsDamaged();
    case "3487311898"://Clan Challengers
      return $ally->IsUpgraded();
    case "6769342445"://Jango Fett (Renowned Bounty Hunter)
      if(IsAllyAttackTarget() && $mainPlayer == $player) {
        $targetAlly = new Ally(GetAttackTarget(), $defPlayer);
        if($targetAlly->HasBounty()) return true;
      }
      return false;
    case "8640210306"://Advanced Recon Commando
    case "8084593619"://Dendup's Loyalist
    case "6330903136"://B2 Legionnaires
    case "2554988743"://Gor
    case "3693364726"://Aurra Sing
    case "3476041913"://Low Altitude Gunship
    case "8655450523"://Count Dooku (Fallen Jedi)
    case "0756051511"://MC30 Assault Frigate
    case "6576881465"://Decimator of Dissidents
    case "9017877021"://Clone Commander Cody
      return true;
    case "4484318969"://Moff Gideon Leader Unit
    case "24a81d97b5"://Anakin Skywalker Leader Unit
    case "6fa73a45ed"://Count Dooku Leader Unit
    case "40b649e6f6"://Maul Leader Unit
      return !LeaderAbilitiesIgnored();
    case "8139901441"://Bo-Katan Kryze
      return SearchCount(SearchAllies($player, trait:"Mandalorian")) > 1;
    case "2948071304"://Vonreg's TIE Interceptor
      return $ally->CurrentPower(reportMode:true) >= 4;
    //Legends of the Force
    case "1636013021"://Savage Opress
    case "2285555274"://Darth Malak
    case "4043787241"://Strikeship
    case "4145147486"://Kylo Ren LOF
    case "3092212109"://Drengir Spawn
    case "9757688123"://Mace Windu
    case "6180656125"://Eye of Sion
    case "9796715682"://Exegol Patroller
    case "6584072416"://Mynock
    case "4199027631"://Trident Assault Ship
      return true;
    case "e6dc0d1cee"://Avar Kriss Leader unit
      return HasTheForce($player);
    case "2370458497"://Oppo Rancisis
      if($isRecursion) return false; //Prevent recursion
      $units = &GetAllies($player);
      for($i=0; $i<count($units); $i+=AllyPieces()) {
        if($i == $index) continue;
        if(HasOverwhelm($units[$i], $player, $i, true)) return true;
      }
      break;
    default: break;
  }
  //The Ghost JTL
  $theGhostIndex = SearchAlliesForCard($player, "5763330426");
  if($theGhostIndex != "" && TraitContains($cardID, "Spectre", $player)
      && $index != $theGhostIndex
      && HasOverwhelm("5763330426", $player, $theGhostIndex))
    return true;

  return false;
}

function HasAmbush($cardID, $player, $index, $from, $isRecursion=false)
{
  if ($cardID == "0345124206") return false; //Clone - Prevent bugs related to ECL and Timely.

  global $currentTurnEffects;
  $ally = new Ally("MYALLY-" . $index, $player);
  for($i=count($currentTurnEffects)-CurrentTurnPieces(); $i>=0; $i-=CurrentTurnPieces()) {
    if($currentTurnEffects[$i+1] != $player) continue;
    if($currentTurnEffects[$i+2] != -1 && $currentTurnEffects[$i+2] != $ally->UniqueID()) continue;
    switch($currentTurnEffects[$i]) {
      case "8327910265":
        AddDecisionQueue("REMOVECURRENTEFFECT", $player, "8327910265");
        return true;//Energy Conversion Lab (ECL)
      case "6847268098"://Timely Intervention
        AddDecisionQueue("REMOVECURRENTEFFECT", $player, "6847268098");
        return true;
      case "0911874487"://Fennec Shand
        AddDecisionQueue("REMOVECURRENTEFFECT", $player, "0911874487");
        return true;
      case "2b13cefced"://Fennec Shand Leader Unit
        AddDecisionQueue("REMOVECURRENTEFFECT", $player, "2b13cefced");
        return true;
      case "7981459508"://Shien Flurry
        AddDecisionQueue("REMOVECURRENTEFFECT", $player, "7981459508");
        return true;
      case "2554581368-P"://Deceptive Shade
        AddDecisionQueue("REMOVECURRENTEFFECT", $player, "2554581368-P");
        return true;
      default: break;
    }
  }
  $allies = &GetAllies($player);
  for($i=0; $i<count($allies); $i+=AllyPieces())
  {
    switch($allies[$i])
    {
      case "4566580942"://Admiral Piett
        if(CardCost($cardID) >= 6 && DefinedCardType($cardID) == "Unit" && $from != "EQUIP") return true;
        break;
      case "4339330745"://Wedge Antilles
        if(TraitContains($cardID, "Vehicle", $player)) return true;
        break;
      case "6097248635"://4-LOM
        if(CardTitle($cardID) == "Zuckuss") return true;
        break;
      default: break;
    }
  }
  switch($cardID)
  {
    //Spark of Rebellion
    case "5346983501"://Syndicate Lackeys
    case "6718924441"://Mercenary Company
    case "7285270931"://Auzituck Liberator Gunship
    case "3377409249"://Rogue Squadron Skirmisher
    case "5230572435"://Mace Windu (Party Crasher)
    case "0052542605"://Bossk (Deadly Stalker)
    case "2649829005"://Agent Kallus
    case "1862616109"://Snowspeeder
    case "3684950815"://Bounty Hunter Crew
    case "9500514827"://Han Solo (Reluctant Hero)
    case "8506660490"://Darth Vader (Commanding the First Legion)
    //Shadows of the Galaxy
    case "1805986989"://Modded Cohort
    case "7171636330"://Chain Code Collector
    case "7982524453"://Fennec Shand
    case "8862896760"://Maul
    case "2143627819"://The Marauder
    case "2121724481"://Cloud-Rider
    case "8107876051"://Enfys Nest
    case "6097248635"://4-LOM
    case "9483244696"://Weequay Pirate Gang
    case "1086021299"://Arquitens Assault Cruiser
    //Twilight of the Republic
    case "7953154930"://Hidden Sharpshooter
    case "1988887369"://Phase II Clone Trooper
    case "4824842849"://Subjugating Starfighter
    case "2554988743"://Gor
    case "7494987248"://Plo Koon
    //Jump to Lightspeed
    case "2388374331"://Blue Leader
    case "1356826899"://Home One
    case "6720065735"://Han Solo (Has His Moments)
    case "0097256640"://TIE Ambush Squadron
    case "4240570958"://Fireball
    case "7489502985"://Contracted Hunter
    case "7198833142"://X-34 Landspeeder
    case "7458361203"://Corporate Light Cruiser
    case "2913957813"://Eager Escort Fighter
    case "0728753133"://The Starhawk
    case "9667260960"://Retrofitted Airspeeder
    //Legends of the Force
    case "1270747736"://Qui-Gon Jinn unit
    case "7529152088"://Depa Billaba
    case "6745607382"://Jedi Temple Guards
    case "6180656125"://Eye of Sion
    case "4478482436"://Supremacy
    case "5663262393"://Charging Phillak
    case "9893266972"://Kowakian Monkey-Lizard
    case "0346642321"://Mysterious Hermit
    case "7821324752"://Eighth Brother
      return true;

    //conditional ambush
    case "2027289177"://Escort Skiff
      return SearchCount(SearchAllies($player, aspect:"Command")) > 1;
    case "4685993945"://Frontier AT-RT
      return SearchCount(SearchAllies($player, trait:"Vehicle")) > 1;

    case "5752414373"://Millennium Falcon
      return $from == "HAND";
    case "7380773849"://Coruscant Guard
      return IsCoordinateActive($player);
    case "6999668340"://Droid Commando
      return SearchCount(SearchAllies($player, trait:"Separatist")) > 1;
    case "5243634234"://Baktoid Spider Droid
      return true;
    case "7144880397"://Ahsoka Tano
      return HasMoreUnits($player == 1 ? 2 : 1);

    case "8660042329"://Terentatek
      $otherPlayer = $player == 1 ? 2 : 1;
      return HasUnitWithTraitInPlay($otherPlayer, "Force");
    case "4347039495"://Darth Tyranus
      return HasTheForce($player);
    case "2370458497"://Oppo Rancisis
      if($isRecursion) return false; //Prevent recursion
      $units = &GetAllies($player);
      for($i=0; $i<count($units); $i+=AllyPieces()) {
        if($i == $index) continue;
        if(HasAmbush($units[$i], $player, $i, $from, true)) return true;
      }
      break;
    default: break;
  }
  //The Ghost JTL
  $theGhostIndex = SearchAlliesForCard($player, "5763330426");
  if($theGhostIndex != "" && TraitContains($cardID, "Spectre", $player)
      && $index != $theGhostIndex
      && HasAmbush("5763330426", $player, $theGhostIndex, $from))
    return true;

  return false;
}

function HasShielded($cardID, $player, $index, $isRecursion=false)
{
  global $currentTurnEffects;

  for($i=count($currentTurnEffects)-CurrentTurnPieces(); $i>=0; $i-=CurrentTurnPieces()) {
    if($currentTurnEffects[$i+1] != $player) continue;
    if($currentTurnEffects[$i] == "3148212344_Shielded" && TraitContains($cardID, "Vehicle", $player)) return true;//Admiral Yularen (Must be outside applies to loop because that's for Yularen)
    //if($currentTurnEffects[$i+2] != -1 && $currentTurnEffects[$i+2] != $ally->UniqueID()) continue;
    switch($currentTurnEffects[$i]) {
      default: break;
    }
  }
  switch($cardID)
  {
    //leaders when deployed
    case "b0dbca5c05"://Iden Versio (SOR) Leader Unit
    case "fadc48bab2"://Kanan Jarrus (LOF) Leader Unit
    case "5b24706856"://Grand Inquisitor Leader Unit
        return !LeaderAbilitiesIgnored();
    //Spark of Rebellion
    case "0700214503"://Crafty Smuggler
    case "5264521057"://Wilderness Fighter
    case "9950828238"://Seventh Fleet Defender
    case "9459170449"://Cargo Juggernaut
    case "6931439330"://The Ghost
    case "9624333142"://Count Dooku (Darth Tyranus)
    case "3280523224"://Rukh
    case "7728042035"://Chimaera
    case "7870435409"://Bib Fortuna
      return true;
    //Shadows of the Galaxy
    case "0088477218"://Privateer Scyk
      return SearchCount(SearchAllies($player, aspect:"Cunning")) > 1;
    case "6939947927"://Hunter of the Haxion Brood
      $otherPlayer = $player == 1 ? 2 : 1;
      return SearchCount(SearchAllies($otherPlayer, hasBountyOnly:true)) > 0;
    case "6135081953"://Doctor Evazan
    case "1747533523"://Village Protectors
    case "1090660242"://The Client
    case "5080989992"://Rose Tico
    case "0598830553"://Dryden Vos
    case "6635692731"://Hutt's Henchman
    case "4341703515"://Supercommando Squad
      return true;
    //Jump to Lightspeed
    case "6311662442"://Director Krennic
    case "1519837763"://Shuttle ST-149
    case "6300552434"://Gold Leader
    case "7700932371"://Boba Fett
    case "9325037410"://Iden Versio
    case "7385763727"://Techno Union Transport
    case "3770706835"://Outer Rim Outlaws
    case "2644994192"://Hondo Ohnaka
      return true;
    //Legends of the Force
    case "3967581160"://Anakin Skywalker Child LOF
    case "4347039495"://Darth Tyranus
    case "5221323929"://Shin Hati
    case "7323186775"://Itinerant Warrior
    case "1462779761"://Mythosaur
    case "2628450872"://Gungan Warrior
    case "8601222247"://Secretive Sage
    case "0126487527"://Axe Woves
    case "0355203328"://Sorcerers of Tund
      return true;
    case "2370458497"://Oppo Rancisis
      if($isRecursion) return false; //Prevent recursion
      $units = &GetAllies($player);
      for($i=0; $i<count($units); $i+=AllyPieces()) {
        if($i == $index) continue;
        if(HasShielded($units[$i], $player, $i, true)) return true;
      }
      break;
    default: break;
  }
  //The Ghost JTL
  $theGhostIndex = SearchAlliesForCard($player, "5763330426");
  if($theGhostIndex != "" && TraitContains($cardID, "Spectre", $player)
      && $index != $theGhostIndex
      && HasShielded("5763330426", $player, $theGhostIndex))
    return true;

  return false;
}

function HasSaboteur($cardID, $player, $index, $isRecursion=false)
{
  global $currentTurnEffects;
  $ally = new Ally("MYALLY-" . $index, $player);
  if($ally->LostAbilities()) return false;
  for($i=0; $i<count($currentTurnEffects); $i+=CurrentTurnPieces()) {
    if($currentTurnEffects[$i+1] != $player) continue;
    if($currentTurnEffects[$i+2] != -1 && $currentTurnEffects[$i+2] != $ally->UniqueID()) continue;
    switch($currentTurnEffects[$i]) {
      case "4663781580": return true;//Swoop Down
      case "9210902604": return true;//Precision Fire
      case "4910017138": return true;//Breaking In
      case "5610901450": return true;//Heroes on Both Sides
      //Jump to Lightspeed
      case "3272995563": return false;//In The Heat of Battle
      case "8656409691": return true;//Rio Durant leader
      //Legends of the Force
      case "0024409893": return true;//BD-1
      case "8743459187": //Focus Determines Reality
        return TraitContains($cardID, "Force", $player);
      default: break;
    }
  }
  $upgrades = $ally->GetUpgrades();
  for($i=0; $i<count($upgrades); ++$i)
  {
    switch($upgrades[$i]) {
      case "0797226725"://Infiltrator's Skill
      case "81a416eb1f"://Rio Durant pilot leader
      case "6128668392"://Ascension Cable
        return true;
      default: break;
    }
  }
  $allies = &GetAllies($player);
  for($i=0; $i<count($allies); $i+=AllyPieces())
  {
    switch($allies[$i])
    {
      case "1690726274"://Zuckuss
        if(CardTitle($cardID) == "4-LOM") return true;
        break;
      default: break;
    }
  }
  switch($cardID)
  {
    //Spark of Rebellion
    case "1017822723"://Rogue Operative
    case "9859536518"://Jawa Scavenger
    case "0046930738"://Rebel Pathfinder
    case "7533529264"://Wolffe
    case "1746195484"://Jedha Agitator
    case "5907868016"://Fighters for Freedom
    case "0828695133"://Seventh Sister
    case "9250443409"://Lando Calrissian (Responsible Businessman)
    case "3c60596a7a"://Cassian Andor (Dedicated to the Rebellion)
    //Shadows of the Galaxy
    case "1690726274"://Zuckuss
    case "4595532978"://Ketsu Onyo
    case "3786602643"://House Kast Soldier
    case "2b13cefced"://Fennec Shand Leader Unit
    case "7922308768"://Valiant Assault Ship
    case "2151430798"://Guavian Antagonizer
    case "2556508706"://Resourceful Pursuers
    case "2965702252"://Unlicensed Headhunter
    //Twilight of the Republic
    case "6404471739"://Senatorial Corvette
    case "4050810437"://Droid Starfighter
    case "3600744650"://Bold Recon Commando
    case "6623894685"://Infiltrating Demolisher
    case "1641175580"://Kit Fisto
    case "8414572243"://Enfys Nest (Champion of Justice)
    case "3434956158"://Fives
    //Jump to Lightspeed
    case "81a416eb1f"://Rio Durant leader unit
    case "3310100725"://Insurgent Saboteurs
    case "1107172562"://Orbiting K-Wing
    //Legends of the Force
    case "7529152088"://Depa Billaba
    case "1028870559"://Kit Fisto's Aethersprite
    case "90e2d4d83e"://Kit Fisto Leader unit
    case "3099740319"://Blockade Runner
      return true;

    //conditional saboteur
    case "8187818742"://Republic Commando
      return IsCoordinateActive($player);
    case "11299cc72f"://Pre Viszla
      $hand = &GetHand($player);
      if(count($hand)/HandPieces() >= 3) return true;
      break;
    case "8139901441"://Bo-Katan Kryze
      return SearchCount(SearchAllies($player, trait:"Mandalorian")) > 1;
    case "7099699830"://Jyn Erso
      global $CS_NumAlliesDestroyed;
      $otherPlayer = $player == 1 ? 2 : 1;
      return GetClassState($otherPlayer, $CS_NumAlliesDestroyed) > 0;
    case "2370458497"://Oppo Rancisis
      if($isRecursion) return false; //Prevent recursion
      $units = &GetAllies($player);
      for($i=0; $i<count($units); $i+=AllyPieces()) {
        if($i == $index) continue;
        if(HasSaboteur($units[$i], $player, $i, true)) return true;
      }
      break;
    default: break;
  }
  //The Ghost JTL
  $theGhostIndex = SearchAlliesForCard($player, "5763330426");
  if($theGhostIndex != "" && TraitContains($cardID, "Spectre", $player)
      && $index != $theGhostIndex
      && HasSaboteur("5763330426", $player, $theGhostIndex))
    return true;

  return false;
}

function HasHidden($cardID, $player, $index, $isRecursion=false) {
  global $currentTurnEffects;

  $ally = new Ally("MYALLY-" . $index, $player);
  if($ally->LostAbilities()) return false;
  //Check if ongoing effects prevent hidden
  for($i=0; $i<count($currentTurnEffects); $i+=CurrentTurnPieces()) {
    if($currentTurnEffects[$i+1] != $player) continue;
    if($currentTurnEffects[$i+2] != -1 && $currentTurnEffects[$i+2] != $ally->UniqueID()) continue;
    switch($currentTurnEffects[$i]) {
    case "1548886844": //Tusken Tracker
        return false;
      default: break;
    }
  }
  //ongoing effects
  for($i=0; $i<count($currentTurnEffects); $i+=CurrentTurnPieces()) {
    if($currentTurnEffects[$i+1] != $player) continue;
    if($currentTurnEffects[$i+2] != -1 && $currentTurnEffects[$i+2] != $ally->UniqueID()) continue;
    switch($currentTurnEffects[$i]) {
      case "3357344238"://Third Sister Leader
      case "5387ca4af6-P"://Third Sister Leader Unit
      case "5074877387"://Three Lessons
        return true;
      default: break;
    }
  }
  //upgrades that grant hidden
  $upgrades = $ally->GetUpgrades();
  for($i=0; $i<count($upgrades); ++$i)
  {
    switch($upgrades[$i]) {
      default: break;
    }
  }
  //other allies that grant hidden
  $allies = &GetAllies($player);
  for($i=0; $i<count($allies); $i+=AllyPieces())
  {
    switch($allies[$i]) {
      case "0686684746"://Grand Inquisitor LOF
        if(TraitContains($cardID, "Inquisitor", $player)) return true;
        break;
      default: break;
    }
  }
  //card specific
  switch($cardID) {
    //Legends of the Force
    case "3967581160"://Anakin Skywalker Child
    case "4389144613"://Grogu
    case "3995900674"://Tuk'ata
    case "6082085272"://Forged Starfighter
    case "1433284352"://Attuned Fyrnock
    case "2897264390"://Witch of the Mist
    case "1978321046"://Village Tender
    case "7877401512"://Aurra Sing
    case "6180656125"://Eye of Sion
    case "5221323929"://Shin Hati
    case "4729355863"://Baylan Skoll
    case "3052907071"://Dooku
    case "0024409893"://BD-1
    case "5451377567"://Banking Clan Shuttle
    case "5663262393"://Charging Phillak
    case "7742118411"://Vupltex
    case "0686684746"://Grand Inquisitor LOF
    case "7718974573"://Jedi In Hiding
      return true;
    case "5387ca4af6"://Third Sister Leader Unit
      return !LeaderAbilitiesIgnored();
    case "2370458497"://Oppo Rancisis
      if($isRecursion) return false; //Prevent recursion
      $units = &GetAllies($player);
      for($i=0; $i<count($units); $i+=AllyPieces()) {
        if($i == $index) continue;
        if(HasHidden($units[$i], $player, $i, true)) return true;
      }
      break;
    default: break;
  }

  return false;
}

function WhenDefeatedWasUseForceAbility($cardID) {
  return match ($cardID) {
    //Legends of the Force
    "1636013021" //Savage Opress
    ,"1991532931"//Karis
      => true,
    default => false,
  };
}

//FAB
// function MemoryCost($cardID, $player)
// {
//   $cost = CardMemoryCost($cardID);
//   switch($cardID)
//   {
//     case "s23UHXgcZq": if(IsClassBonusActive($player, "ASSASSIN")) --$cost; break;//Luxera's Map
//     default: break;
//   }
//   $allies = &GetAllies($player);
//   for($i=0; $i<count($allies); $i+=AllyPieces())
//   {
//     switch($allies[$i])
//     {
//       case "kk39i1f0ht": if(CardType($cardID) == "C") --$cost; break;//Academy Guide
//       default: break;
//     }
//   }
//   return $cost;
// }

function AbilityCost($cardID)
{
  global $currentPlayer;
  $abilityName = GetResolvedAbilityName($cardID);
  if($abilityName == "Heroic Resolve") return 2;
  if($abilityName == "Poe Pilot") return 1;
  switch($cardID) {
    //Spark of Rebellion
    case "2579145458"://Luke Skywalker
      return $abilityName == "Give Shield" ? 1 : 0;
    case "2912358777"://Grand Moff Tarkin
      return $abilityName == "Give Experience" ? 1 : 0;
    case "3187874229"://Cassian Andor
      return $abilityName == "Draw Card" ? 1 : 0;
    case "4300219753"://Fett's Firespray
      return $abilityName == "Exhaust" ? 2 : 0;
    case "7144880397"://Ahsoka Tano TWI
      return $abilityName == "Return" ? 2 : 0;
    case "5784497124"://Emperor Palpatine
      return $abilityName == "Deal Damage" ? 1 : 0;
    case "6088773439"://Darth Vader
      return $abilityName == "Deal Damage" ? 1 : 0;
    case "1951911851"://Grand Admiral Thrawn
      return $abilityName == "Exhaust" ? 1 : 0;
    //Shadows of the Galaxy
    case "1885628519"://Crosshair
      return $abilityName == "Buff" ? 2 : 0;
    case "2432897157"://Qi'Ra
      return $abilityName == "Shield" ? 1 : 0;
    case "4352150438"://Rey
      return $abilityName == "Experience" ? 1 : 0;
    case "0911874487"://Fennec Shand
      return $abilityName == "Ambush" ? 1 : 0;
    case "8709191884"://Hunter (Outcast Sergeant)
      return $abilityName == "Replace Resource" ? 1 : 0;
    case "3577961001"://Mercenary Gunship
      return $abilityName == "Take Control" ? 4 : 0;
    //Twilight of the Republic
    case "3258646001"://Steadfast Senator
      return $abilityName == "Buff" ? 2 : 0;
    case "0595607848"://Disaffected Senator
      return $abilityName == "Deal Damage" ? 2 : 0;
    case "5157630261"://Compassionate Senator
      return $abilityName == "Heal" ? 2 : 0;
    case "9262288850"://Independent Senator
      return $abilityName == "Exhaust" ? 2 : 0;
    case "5081383630"://Pre Viszla
      return $abilityName == "Deal Damage" ? 1 : 0;
    case "4628885755"://Mace Windu
      return $abilityName == "Deal Damage" ? 1 : 0;
    case "7734824762"://Captain Rex
      return $abilityName == "Clone" ? 2 : 0;
    case "2870878795"://Padme Amidala
      return $abilityName == "Draw" ? 1 : 0;
    //Jump to Lightspeed
    case "3658069276"://Lando Calrissian
      return $abilityName == "Play" ? 1 : 0;
    case "7514405173":
      return $abilityName == "Exhaust" ? 1 : 0;
    case "8656409691"://Rio Durant
      return $abilityName == "Attack" ? 1 : 0;
    case "8943696478"://Admiral Holdo
      return $abilityName == "Buff" ? 1 : 0;
    case "8520821318"://Poe Dameron
      return $abilityName == "Pilot" ? 1 : 0;
    case "3905028200"://Admiral Trench
      return $abilityName == "Deploy" ? 3 : 0;
    case "5306772000"://Phantom II
      return $abilityName == "Dock" ? 1 : 0;
    //Legends of the Force
    case "8304104587"://Kanan Jarrus Leader
      return $abilityName == "Shield" ? 1 : 0;
    case "3822427538"://Kit Fisto Leader
      return $abilityName == "Deal Damage" ? 1 : 0;
    case "9919167831"://Supreme Leader Snoke Leader
      return $abilityName == "Experience" ? 1 : 0;
    //Intro Battle: Hoth
    case "9389694773"://Darth Vader
      return $abilityName == "Deal Damage" ? 1 : 0;
    case "9970912404"://Leia Organa
      return $abilityName == "Heal" ? 1 : 0;
    case "9508246309"://Imperial Deck Officer
      return $abilityName == "Heal" ? 2 : 0;
    case "9782761594"://Ion Cannon
      return $abilityName == "Deal Damage" ? 1 : 0;
    default: break;
  }
  if(IsAlly($cardID)) return 0;
  return 0;
}

function DynamicCost($cardID)
{
  switch($cardID) {
    case "2267524398"://The Clone Wars
      return "2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20";
    default: return "";
  }
}

function PitchValue($cardID)
{
  return 0;
}

function BlockValue($cardID)
{
  return 0;
}

function SpecificCardPower($mzID, $player = -1)
{
  global $currentPlayer;
  if($player == -1) $player = $currentPlayer;
  $cardID = GetMZCard($player, $mzID);
  if(DefinedCardType($cardID) == "Unit" || DefinedCardType2($cardID) == "Unit") {
    $ally = new Ally($mzID, $player);
    $upgrades = $ally->GetUpgrades();
    for ($i = 0; $i < count($upgrades); ++$i) {
      switch ($upgrades[$i]) {
        case "6980075962"://Size Matters Not
          return 5;
        default:
          break;
      }
    }
  }
  return CardPower($cardID);
}

function SpecificCardHP($mzID, $player = -1)
{
  global $currentPlayer;
  if($player == -1) $player = $currentPlayer;
  $cardID = GetMZCard($player, $mzID);
  if(DefinedCardType($cardID) == "Unit" || DefinedCardType2($cardID) == "Unit") {
    $ally = new Ally($mzID, $player);
    $upgrades = $ally->GetUpgrades();
    for ($i = 0; $i < count($upgrades); ++$i) {
      switch ($upgrades[$i]) {
        case "6980075962"://Size Matters Not
          return 5;
        default:
          break;
      }
    }
  }

  return CardHPDictionary($cardID);
}

function AttackValue($cardID) {
  switch ($cardID) {
    default:
      return CardPower($cardID);
  }
}

function HasGoAgain($cardID)
{
  return true;
}

function GetAbilityType($cardID, $index = -1, $from="-")
{
  global $currentPlayer, $mainPlayer;

  if($from == "PLAY" && IsAlly($cardID)) {
    $myAllies = GetAllies($mainPlayer);
    if(isset($myAllies[$index]) && Ally::FromUniqueId($myAllies[$index + 5])->CantAttack()) {
      return "";
    }

    return "AA";
  }
  switch($cardID)
  {
    //bases
    case "2569134232"://Jedha City
      return "A";
    case "1393827469"://Tarkintown
      return "A";
    case "2429341052"://Security Complex
      return "A";
    case "8327910265"://Energy Conversion Lab (ECL)
      return "A";
    case "9434212852"://Mystic Monastery
      $char = &GetPlayerCharacter($currentPlayer);
      return $char[5] > 0 ? "A" : "";
    case "2699176260"://Tomb of Eilram
      return CountReadyAllies($currentPlayer) > 0 ? "A" : "";
    //end bases
    case "4626028465"://Boba Fett Leader
    case "7440067052"://Hera Syndulla Leader
    case "8560666697"://Director Krennic Leader
      if(LeaderAbilitiesIgnored()) return "";
      $char = &GetPlayerCharacter($currentPlayer);
      return $char[CharacterPieces() + 2] == 0 ? "A" : "";
    default: return "";
  }
}

function GetOpponentAbilityTypes($cardID, $index = -1, $from="-") {
  $abilityTypes = "";
  switch($cardID) {
    case "3577961001": {
      $abilityTypes = "A";
    }
  }
  return $abilityTypes;
}

function GetAbilityTypes($cardID, $index = -1, $from="-")
{
  global $currentPlayer, $CS_NumUsesLeaderUpgrade1;
  $abilityTypes = "";

  $set = CardSet($cardID);
  switch($set) {
    case "SOR": $abilityTypes = CheckSORAbilityTypes($cardID); break;
    case "SHD": $abilityTypes = CheckSHDAbilityTypes($cardID); break;
    case "TWI": $abilityTypes = CheckTWIAbilityTypes($cardID); break;
    case "JTL": $abilityTypes = CheckJTLAbilityTypes($cardID); break;
    case "LOF": $abilityTypes = CheckLOFAbilityTypes($cardID); break;
    case "IBH": $abilityTypes = CheckIBHAbilityTypes($cardID); break;
    default: break;//maybe throw error?
  }

  if(IsAlly($cardID, $currentPlayer)) {
    $ally = new Ally("MYALLY-" . $index, $currentPlayer);
    if($abilityTypes == "") $abilityTypes = "AA";
    if($cardID == "4332645242") {
      if(!$ally->LostAbilities()) {
        $abilityTypes = "";//Corporate Defense Shuttle can't attack
      }
    }

    if($ally->CantAttack()) {
      $abilityTypes = FilterOutAttackAbilityType($abilityTypes);
    }

    $upgrades = $ally->GetUpgrades();
    for($i=0; $i<count($upgrades); ++$i) {
      switch($upgrades[$i]) {
        case "4085341914"://Heroic Resolve
          if($abilityTypes != "") $abilityTypes .= ",";
          $abilityTypes .= "A";
          break;
        case "2397845395"://Strategic Acumen
          if($abilityTypes != "") $abilityTypes .= ",";
          $abilityTypes .= "A";
          break;
        case "3eb545eb4b"://Poe Dameron JTL leader
          if(GetClassState($currentPlayer, $CS_NumUsesLeaderUpgrade1) > 0) {
            if($abilityTypes != "") $abilityTypes .= ",";
            $abilityTypes .= "A";
          }
          break;
        default: break;
      }
    }

    if (AnyPlayerHasAlly("6384086894")) { //Satine Kryze
      $abilityTypes = "A," . $abilityTypes;
    }
  }
  else if(DefinedTypesContains($cardID, "Leader", $currentPlayer)) {
    $char = &GetPlayerCharacter($currentPlayer);
    if(count($char) > CharacterPieces()) {
      if($char[CharacterPieces() + 1] == 1) $abilityTypes = "";
      if($char[CharacterPieces() + 2] == 0) {
        //Chancellor Palpatine Leader + Darth Sidious Leader
        if(IsNotFlipatine($char) && IsNotExhaustedTrench($char)) {
          if($abilityTypes != "") $abilityTypes .= ",";
          $abilityTypes .= "A";
        }
      }
    }
  }

  return $abilityTypes;
}

function CardIDIsBase($cardID) {
  return DefinedTypesContains($cardID, "Base");
}

function CardIDIsLeader($cardID, $playerID = "") {
  return DefinedTypesContains($cardID, "Leader", $playerID);
}

function FilterOutAttackAbilityType($abilityTypes) {
  return str_replace("AA", "", str_replace(",AA", "", str_replace("AA,", "", $abilityTypes)));
}

function CheckSORAbilityTypes($cardID) {
  global $currentPlayer;

  switch($cardID) {
    case "4300219753"://Fett's Firespray
      return "A,AA";
    case "2554951775"://Bail Organa
      return "A,AA";
    case "2756312994"://Alliance Dispatcher
      return "A,AA";
    case "3572356139"://Chewbacca (Walking Carpet)
      return LeaderAbilitiesIgnored() ? "" : "A";
    case "2579145458"://Luke Skywalker
      return LeaderAbilitiesIgnored() ? "" : "A";
    case "2912358777"://Grand Moff Tarkin
      return LeaderAbilitiesIgnored() ? "" : "A";
    case "3187874229"://Cassian Andor
      return LeaderAbilitiesIgnored() ? "" : "A";
    case "4841169874"://Sabine Wren
      return LeaderAbilitiesIgnored() ? "" : "A";
    case "2048866729"://Iden Versio
      return LeaderAbilitiesIgnored() ? "" : "A";
    case "6088773439"://Darth Vader
      return LeaderAbilitiesIgnored() ? "" : "A";
    case "4263394087"://Chirrut Imwe
      return LeaderAbilitiesIgnored() ? "" : "A";
    case "7911083239"://Grand Inquisitor
      return LeaderAbilitiesIgnored() ? "" : "A";
    case "5954056864"://Han Solo
      return LeaderAbilitiesIgnored() ? "" : "A";
    case "6514927936"://Leia Organa
      return LeaderAbilitiesIgnored() ? "" : "A";
    case "8244682354"://Jyn Erso
      return LeaderAbilitiesIgnored() ? "" : "A";
    case "8600121285"://IG-88
      return LeaderAbilitiesIgnored() ? "" : "A";
    case "7870435409"://Bib Fortuna
      return "A,AA";
    case "5784497124"://Emperor Palpatine
      $allies = &GetAllies($currentPlayer);
      if(count($allies) == 0) return "";
      else return LeaderAbilitiesIgnored() ? "" : "A";
    case "8117080217"://Admiral Ozzel
      return "A,AA";
    case "2471223947"://Frontline Shuttle
      return "A,AA";
    case "1951911851"://Grand Admiral Thrawn
      return LeaderAbilitiesIgnored() ? "" : "A";

    default: return "";
  }
}

function CheckSHDAbilityTypes($cardID) {
  switch($cardID) {
    case "1480894253"://Kylo Ren
      return LeaderAbilitiesIgnored() ? "" : "A";
    case "6722700037"://Doctor Pershing
      return "A,AA";
    case "6536128825"://Grogu
      return "A,AA";
    case "1090660242"://The Client
      return "A,AA";
    case "1885628519"://Crosshair
      return "A,A,AA";
    case "2503039837"://Moff Gideon
      return LeaderAbilitiesIgnored() ? "" : "A";
    case "2526288781"://Bossk
      return LeaderAbilitiesIgnored() ? "" : "A";
    case "7424360283"://Bo-Katan Kryze
      return LeaderAbilitiesIgnored() ? "" : "A";
    case "5440730550"://Lando Calrissian
      return LeaderAbilitiesIgnored() ? "" : "A";
    case "040a3e81f3"://Lando Leader Unit
      return LeaderAbilitiesIgnored() ? "AA": "A,AA";
    case "2432897157"://Qi'Ra
      return LeaderAbilitiesIgnored() ? "" : "A";
    case "4352150438"://Rey
      return LeaderAbilitiesIgnored() ? "" : "A";
    case "0911874487"://Fennec Shand
      return LeaderAbilitiesIgnored() ? "" : "A";
    case "2b13cefced"://Fennec Shand Leader Unit
      return LeaderAbilitiesIgnored() ? "AA" : "A,AA";
    case "9226435975"://Han Solo Red
      return LeaderAbilitiesIgnored() ? "" : "A";
    case "a742dea1f1"://Han Solo Red Leader Unit
      return LeaderAbilitiesIgnored() ? "AA" : "A,AA";
    case "0622803599"://Jabba the Hutt
      return LeaderAbilitiesIgnored() ? "" : "A";
    case "f928681d36"://Jabba the Hutt Leader Unit
      return LeaderAbilitiesIgnored()? "AA" : "A,AA";
    case "9596662994"://Finn
      return LeaderAbilitiesIgnored() ? "" : "A";
    case "8709191884"://Hunter (Outcast Sergeant)
      return LeaderAbilitiesIgnored() ? "" : "A";
    case "2744523125"://Salacious Crumb
      return "A,AA";

    default: return "";
  }
}

function CheckTWIAbilityTypes($cardID) {
  global $currentPlayer;

  switch($cardID) {
    case "2870878795"://Padme Amidala
      return LeaderAbilitiesIgnored() ? "" : "A";
    case "2872203891"://General Grievous
      return LeaderAbilitiesIgnored() ? "" : "A";
    case "6064906790"://Nute Gunray
      return LeaderAbilitiesIgnored() ? "" : "A";
    case "2847868671"://Yoda
      return LeaderAbilitiesIgnored() ? "" : "A";
    case "1686059165"://Wat Tambor
      return LeaderAbilitiesIgnored() ? "" : "A";
    case "0026166404"://Chancellor Palpatine Leader
    case "ad86d54e97"://Darth Sidious Leader
      return LeaderAbilitiesIgnored() ? "" : "A";
    case "7734824762"://Captain Rex
      return LeaderAbilitiesIgnored() ? "" : "A";
    case "4628885755"://Mace Windu
      return LeaderAbilitiesIgnored() ? "" : "A";
    case "5683908835"://Count Dooku
      return LeaderAbilitiesIgnored() ? "" : "A";
    case "5081383630"://Pre Viszla
      return LeaderAbilitiesIgnored() ? "" : "A";
    case "2155351882"://Ahsoka Tano
      return IsCoordinateActive($currentPlayer) && !LeaderAbilitiesIgnored() ? "A" : "";
    case "6461101372"://Maul
      return LeaderAbilitiesIgnored() ? "" : "A";
    case "8929774056"://Asajj Ventress
      return LeaderAbilitiesIgnored() ? "" : "A";
    case "2784756758"://Obi-wan Kenobi
      return LeaderAbilitiesIgnored() ? "" : "A";
    case "8777351722"://Anakin Skywalker
      return LeaderAbilitiesIgnored() ? "" : "A";
    case "7144880397"://Ahsoka Tano TWI
      return "A,AA";
    case "5630404651"://MagnaGuard Wing Leader
      return "A,AA";
    case "0595607848"://Disaffected Senator
      return "A,AA";
    case "3258646001"://Steadfast Senator
      return "A,AA";
    case "9262288850"://Independent Senator
      return "A,AA";
    case "5157630261"://Compassionate Senator
      return "A,AA";

    default: return "";
  }
}

function CheckJTLAbilityTypes($cardID) {
  global $currentPlayer;

  switch($cardID) {
    case "4179470615"://Asajj Ventress Leader
      return LeaderAbilitiesIgnored() ? "" : "A";
    case "4030832630"://Admiral Piett Leader
      return LeaderAbilitiesIgnored() ? "" : "A";
    case "0011262813"://Wedge Antilles Leader
      return LeaderAbilitiesIgnored() ? "" : "A";
    case "3933322003"://Rose Tico Leader
      return LeaderAbilitiesIgnored() ? "" : "A";
    case "0616724418"://Han Solo Leader
      return LeaderAbilitiesIgnored() ? "" : "A";
    case "3658069276"://Lando Calrissian Leader
      return LeaderAbilitiesIgnored() ? "" : "A";
    case "7514405173"://Admiral Ackbar Leader
      return LeaderAbilitiesIgnored() ? "" : "A";
    case "8656409691"://Rio Durant
      return LeaderAbilitiesIgnored() ? "" : "A";
    case "8943696478"://Admiral Holdo
      return LeaderAbilitiesIgnored() ? "" : "A";
    case "0766281795"://Luke Skywalker
      return LeaderAbilitiesIgnored() ? "" : "A";
    case "7661383869"://Darth Vader
      return LeaderAbilitiesIgnored() ? "" : "A";
    case "3132453342"://Captain Phasma
      return LeaderAbilitiesIgnored() ? "" : "A";
    case "8520821318"://Poe Dameron
      return LeaderAbilitiesIgnored() ? "" : "A";
    case "4531112134"://Kazuda Xiono
      return LeaderAbilitiesIgnored() ? "" : "A";
    case "6600603122"://Massassi Tactical Officer
      return "A,AA";
    case "9921128444"://General Hux
      return "A,AA";
    case "3905028200"://Admiral Trench
      return LeaderAbilitiesIgnored() ? "" : "A";
    case "9763190770"://Major Vonreg
      return LeaderAbilitiesIgnored() ? "" : "A";
    case "5306772000"://Phantom II
      return "A,AA";
    default: return "";
  }
}

function CheckLOFAbilityTypes($cardID) {
  global $currentPlayer;
  switch($cardID) {
    //leaders
    case "2580909557"://Qui-Gon Jinn Leader
      return LeaderAbilitiesIgnored() ? "" : "A";
    case "0024560758"://Darth Maul Leader
      return LeaderAbilitiesIgnored() ? "" : "A";
    case "2693401411"://Obi-Wan Kenobi Leader
      return LeaderAbilitiesIgnored() ? "" : "A";
    case "3357344238"://Third Sister Leader
      return LeaderAbilitiesIgnored() ? "" : "A";
    case "8304104587"://Kanan Jarrus Leader
      return LeaderAbilitiesIgnored() ? "" : "A";
    case "2520636620"://Mother Talzin Leader
      return LeaderAbilitiesIgnored() ? "" : "A";
    case "3822427538"://Kit Fisto Leader
      return LeaderAbilitiesIgnored() ? "" : "A";
    case "5917432593"://Grand Inquisitor Leader
      return LeaderAbilitiesIgnored() ? "" : "A";
    case "0092239541"://Avar Kriss Leader
      return LeaderAbilitiesIgnored() ? "" : "A";
    case "5045607736"://Morgan Elsbeth Leader
      return LeaderAbilitiesIgnored() ? "" : "A";
    case "7077983867"://Ahsoka Tano Leader
      return LeaderAbilitiesIgnored() ? "" : "A";
    case "9919167831"://Supreme Leader Snoke Leader
      return LeaderAbilitiesIgnored() ? "" : "A";
    case "6677799440"://Cal Kestis Leader
      return LeaderAbilitiesIgnored() ? "" : "A";
    case "1184397926"://Barriss Offee Leader
      return LeaderAbilitiesIgnored() ? "" : "A";
    case "8536024453"://Anakin Skywalker Leader
      return LeaderAbilitiesIgnored() ? "" : "A";
    case "5174764156"://Kylo Ren Leader
      return LeaderAbilitiesIgnored() ? "" : "A";
    case "2762251208"://Rey Leader
      return LeaderAbilitiesIgnored() ? "" : "A";
    //non-leaders
    case "4024881604"://Adept of Anger
      return "A,AA";
    case "3433996932"://Heavy Missile Gunship
      return "A,AA";
    case "4389144613"://Grogu
      return "A,AA";
    case "5482818255"://Jedi Consular
      return "A,AA";
    case "6800160263"://Caretaker Matron
      return "A,AA";
    case "7d9f8bcb9b"://Anakin Skywalker Leader unit
      return LeaderAbilitiesIgnored() ? "AA" : "A,AA";
    case "20f7c21d8b"://Barriss Offee Leader unit
      return LeaderAbilitiesIgnored() ? "AA" : "A,AA";
    case "2236831712"://Leia Organa (Extraordinary)
      return "A,AA";
    case "6501780064"://Babu Frik
      return "A,AA";
    default: return "";
  }
}

function CheckIBHAbilityTypes($cardID) {
  switch($cardID) {
    case "9508246309"://Imperial Deck Officer
      return "A,AA";
    case "9782761594"://Ion Cannon
      return "A,AA";
    case "9389694773"://Darth Vader
      return LeaderAbilitiesIgnored() ? "" : "A";
    case "9970912404"://Leia Organa
      return LeaderAbilitiesIgnored() ? "" : "A";
    default: return "";
  }
}


function GetAbilityNames($cardID, $index = -1, $validate=false)
{
  global $currentPlayer, $CS_NumUsesLeaderUpgrade1;
  $abilityNames = "";
  $set = CardSet($cardID);
  switch($set) {
    case "SOR": $abilityNames = CheckSORAbilityNames($cardID, $index, $validate); break;
    case "SHD": $abilityNames = CheckSHDAbilityNames($cardID, $index, $validate); break;
    case "TWI": $abilityNames = CheckTWIAbilityNames($cardID, $index, $validate); break;
    case "JTL": $abilityNames = CheckJTLAbilityNames($cardID, $index, $validate); break;
    case "LOF": $abilityNames = CheckLOFAbilityNames($cardID, $index, $validate); break;
    case "IBH": $abilityNames = CheckIBHAbilityNames($cardID, $index, $validate); break;
    default: break;//maybe throw error?
  }

  if(IsAlly($cardID, $currentPlayer)) {
    $ally = new Ally("MYALLY-" . $index, $currentPlayer);
    if($abilityNames == "") $abilityNames = "Attack";
    if($cardID == "4332645242") {
      if(!$ally->LostAbilities()) {
        $abilityNames = "";//Corporate Defense Shuttle can't attack
      }
    }
    $upgrades = $ally->GetUpgrades();
    for($i=0; $i<count($upgrades); ++$i) {
      switch($upgrades[$i]) {
        case "4085341914"://Heroic Resolve
          if($abilityNames != "") $abilityNames .= ",";
          $abilityNames .= "Heroic Resolve";
          break;
        case "2397845395"://Strategic Acumen
          if($abilityNames != "") $abilityNames .= ",";
          $abilityNames .= "Strategic Acumen";
          break;
        case "3eb545eb4b"://Poe Dameron JTL leader
          if(GetClassState($currentPlayer, $CS_NumUsesLeaderUpgrade1) > 0) {
            if($abilityNames != "") $abilityNames .= ",";
            $abilityNames .= "Poe Pilot";
            if($validate && $ally->IsExhausted()) $abilityNames = FilterOutAttackAbilityName($abilityNames);
          }
          break;
        default: break;
      }
    }

    if (AnyPlayerHasAlly("6384086894")) { //Satine Kryze
      $abilityNames = "Mill," . $abilityNames;
    }

    if($ally->CantAttack()) {
      $abilityNames = FilterOutAttackAbilityName($abilityNames);
    }
  }
  else if(DefinedTypesContains($cardID, "Leader", $currentPlayer)) {
    $char = &GetPlayerCharacter($currentPlayer);
    if($char[CharacterPieces() + 1] == 1) $abilityNames = "";
    if($char[CharacterPieces() + 2] == 0) {
      if(IsNotFlipatine($char) && IsNotExhaustedTrench($char)) {
        if($abilityNames != "") $abilityNames .= ",";
        $abilityNames .= "Deploy";
      }
    }
  }

  return $abilityNames;
}

function FilterOutAttackAbilityName($abilityNames) {
  return str_replace("Attack", "", str_replace(",Attack", "", str_replace("Attack,", "", $abilityNames)));
}

function CheckSORAbilityNames($cardID, $index, $validate) {
  global $currentPlayer;

  switch($cardID) {
    case "4300219753"://Fett's Firespray
      $ally = new Ally("MYALLY-" . $index, $currentPlayer);
      if($validate) return $ally->IsExhausted() ? "Exhaust" : "Exhaust,Attack";
      else return "Exhaust,Attack";
    case "2554951775"://Bail Organa
      return "Give Experience,Attack";
    case "2756312994"://Alliance Dispatcher
      return "Play Unit,Attack";
    case "3572356139"://Chewbacca (Walking Carpet)
      return LeaderAbilitiesIgnored() ? "" : "Play Taunt";
    case "2579145458"://Luke Skywalker
      return LeaderAbilitiesIgnored() ? "" : "Give Shield";
    case "2912358777"://Grand Moff Tarkin
      return LeaderAbilitiesIgnored() ? "" : "Give Experience";
    case "3187874229"://Cassian Andor
      return LeaderAbilitiesIgnored() ? "" : "Draw Card";
    case "4841169874"://Sabine Wren
      return LeaderAbilitiesIgnored() ? "" : "Deal Damage";
    case "2048866729"://Iden Versio
      return LeaderAbilitiesIgnored() ? "" : "Heal";
    case "6088773439"://Darth Vader
      return LeaderAbilitiesIgnored() ? "" : "Deal Damage";
    case "4263394087"://Chirrut Imwe
      return LeaderAbilitiesIgnored() ? "" : "Buff HP";
    case "7911083239"://Grand Inquisitor
      return LeaderAbilitiesIgnored() ? "" : "Deal Damage";
    case "5954056864"://Han Solo
      return LeaderAbilitiesIgnored() ? "" : "Play Resource";
    case "6514927936"://Leia Organa
      return LeaderAbilitiesIgnored() ? "" : "Attack";
    case "8244682354"://Jyn Erso
      return LeaderAbilitiesIgnored() ? "" : "Attack";
    case "8600121285"://IG-88
      return LeaderAbilitiesIgnored() ? "" : "Attack";
    case "7870435409"://Bib Fortuna
      return "Play Event,Attack";
    case "5784497124"://Emperor Palpatine
      $allies = &GetAllies($currentPlayer);
      if(count($allies) == 0) return "";
      else return LeaderAbilitiesIgnored() ? "" : "Deal Damage";
    case "8117080217"://Admiral Ozzel
      return "Play Imperial Unit,Attack";
    case "2471223947"://Frontline Shuttle
      $ally = new Ally("MYALLY-" . $index, $currentPlayer);
      if($validate) return $ally->IsExhausted() ? "Shuttle" : "Shuttle,Attack";
      else return "Shuttle,Attack";
    case "1951911851"://Grand Admiral Thrawn
      return LeaderAbilitiesIgnored() ? "" : "Exhaust";

    default: return "";
  }
}

function CheckSHDAbilityNames($cardID, $index, $validate) {
  global $currentPlayer;

  switch($cardID) {
    case "1480894253"://Kylo Ren
      $hand = &GetHand($currentPlayer);
      if(count($hand) == 0) return LeaderAbilitiesIgnored() ? "" : "";
      return LeaderAbilitiesIgnored() ? "" : "Buff Attack";
    case "6722700037"://Doctor Pershing
      return "Draw,Attack";
    case "6536128825"://Grogu
      return "Exhaust,Attack";
    case "1090660242"://The Client
      return "Bounty,Attack";
    case "1885628519"://Crosshair
      $ally = new Ally("MYALLY-" . $index, $currentPlayer);
      if($validate) return $ally->IsExhausted() ? "Buff" : "Buff,Snipe,Attack";
      else return "Buff,Snipe,Attack";
    case "2503039837"://Moff Gideon
      return LeaderAbilitiesIgnored() ? "" : "Attack";
    case "2526288781"://Bossk
      return LeaderAbilitiesIgnored() ? "" : "Deal Damage/Buff";
    case "7424360283"://Bo-Katan Kryze
      return LeaderAbilitiesIgnored() ? "" : "Deal Damage";
    case "5440730550"://Lando Calrissian
      return LeaderAbilitiesIgnored() ? "" : "Smuggle";
    case "040a3e81f3"://Lando Leader Unit
      $abilityNames = "";
      if($validate) {
        $ally = new Ally("MYALLY-" . $index, $currentPlayer);
        $abilityNames = $ally->IsExhausted() ? "Smuggle" : "Smuggle,Attack";
      } else {
        $abilityNames = "Smuggle,Attack";
      }
      if(LeaderAbilitiesIgnored()) {
        $abilityNames = str_replace(",Smuggle", "", $abilityNames);
        $abilityNames = str_replace("Smuggle", "", $abilityNames);
      }
      return $abilityNames;
    case "2432897157"://Qi'Ra
      return LeaderAbilitiesIgnored() ? "" : "Shield";
    case "4352150438"://Rey
      return LeaderAbilitiesIgnored() ? "" : "Experience";
    case "0911874487"://Fennec Shand Leader Unit
      return LeaderAbilitiesIgnored() ? "" : "Ambush";
    case "2b13cefced"://Fennec Shand Leader Unit
      $abilityNames = "";
      if($validate) {
        $ally = new Ally("MYALLY-" . $index, $currentPlayer);
        $abilityNames = $ally->IsExhausted() ? "Ambush" : "Ambush,Attack";
      } else {
        $abilityNames = "Ambush,Attack";
      }
      if(LeaderAbilitiesIgnored()) {
        $abilityNames = str_replace(",Ambush", "", $abilityNames);
        $abilityNames = str_replace("Ambush", "", $abilityNames);
      }
      return $abilityNames;
    case "9226435975"://Han Solo Red
      return LeaderAbilitiesIgnored() ? "" : "Play";
    case "a742dea1f1"://Han Solo Red Unit
      $abilityNames = "";
      if($validate) {
        $ally = new Ally("MYALLY-" . $index, $currentPlayer);
        $abilityNames = $ally->IsExhausted() ? "Play" : "Play,Attack";
      } else {
        $abilityNames = "Play,Attack";
      }
      if(LeaderAbilitiesIgnored()) {
        $abilityNames = str_replace(",Play", "", $abilityNames);
        $abilityNames = str_replace("Play", "", $abilityNames);
      }
      return $abilityNames;
    case "0622803599"://Jabba the Hutt
      return LeaderAbilitiesIgnored() ? "" : "Bounty";
    case "f928681d36"://Jabba the Hutt Leader Unit
      return LeaderAbilitiesIgnored() ? "Attack" : "Bounty,Attack";
    case "9596662994"://Finn
      return LeaderAbilitiesIgnored() ? "" : "Shield";
    case "8709191884"://Hunter (Outcast Sergeant)
      return LeaderAbilitiesIgnored() ? "" : "Replace Resource";
    case "2744523125"://Salacious Crumb
      return "Bounce,Attack";

    default: return "";
  }
}

function CheckTWIAbilityNames($cardID, $index, $validate) {
  global $currentPlayer;

  switch($cardID) {
    case "7144880397"://Ahsoka Tano TWI
      $ally = new Ally("MYALLY-" . $index, $currentPlayer);
      if($validate) return $ally->IsExhausted() ? "Return" : "Return,Attack";
      else return "Return,Attack";
    case "2870878795"://Padme Amidala
      return LeaderAbilitiesIgnored() ? "" : "Draw";
    case "2872203891"://General Grievous
      return LeaderAbilitiesIgnored() ? "" : "Sentinel";
    case "6064906790"://Nute Gunray
      return LeaderAbilitiesIgnored() ? "" : "Droid";
    case "2847868671"://Yoda
      return LeaderAbilitiesIgnored() ? "" : "Draw";
    case "1686059165"://Wat Tambor
      return LeaderAbilitiesIgnored() ? "" : "Buff";
    case "7734824762"://Captain Rex
      return LeaderAbilitiesIgnored() ? "" : "Clone";
    case "0026166404"://Chancellor Palpatine Leader
    case "ad86d54e97"://Darth Sidious Leader
      return LeaderAbilitiesIgnored() ? "" : "Activate";
    case "4628885755"://Mace Windu
      return LeaderAbilitiesIgnored() ? "" : "Deal Damage";
    case "5683908835"://Count Dooku
      return LeaderAbilitiesIgnored() ? "" : "Exploit";
    case "5081383630"://Pre Viszla
      return LeaderAbilitiesIgnored() ? "" : "Deal Damage";
    case "2155351882"://Ahsoka Tano
      return IsCoordinateActive($currentPlayer) && !LeaderAbilitiesIgnored() ? "Attack" : "";
    case "6461101372"://Maul
      return LeaderAbilitiesIgnored() ? "" : "Attack";
    case "8929774056"://Asajj Ventress
      return LeaderAbilitiesIgnored() ? "" : "Attack";
    case "2784756758"://Obi-wan Kenobi
      return LeaderAbilitiesIgnored() ? "" : "Heal";
    case "8777351722"://Anakin Skywalker
      return LeaderAbilitiesIgnored() ? "" : "Attack";
    case "5630404651"://MagnaGuard Wing Leader
      $ally = new Ally("MYALLY-" . $index, $currentPlayer);
      if($validate) return $ally->IsExhausted() ? "Droid Attack" : "Droid Attack,Attack";
      else return "Droid Attack,Attack";
    case "0595607848"://Disaffected Senator
      return "Deal Damage,Attack";
    case "3258646001"://Steadfast Senator
      return "Buff,Attack";
    case "9262288850"://Independent Senator
      return "Exhaust,Attack";
    case "5157630261"://Compassionate Senator
      return "Heal,Attack";

    default: return "";
  }
}

function CheckJTLAbilityNames($cardID, $index, $validate) {
  global $currentPlayer;

  switch($cardID) {
    case "4179470615"://Asajj Ventress
      return LeaderAbilitiesIgnored() ? "" : "Damage";
    case "4030832630"://Admiral Piett
      return LeaderAbilitiesIgnored() ? "" : "Play";
    case "0011262813"://Wedge Antilles Leader
      return LeaderAbilitiesIgnored() ? "" : "Play";
    case "3933322003"://Rose Tico Leader
      return LeaderAbilitiesIgnored() ? "" : "Heal";
    case "0616724418"://Han Solo Leader
      return LeaderAbilitiesIgnored() ? "" : "Odds";
    case "3658069276"://Lando Calrissian Leader
      return LeaderAbilitiesIgnored() ? "" : "Play";
    case "7514405173"://Admiral Ackbar Leader
      return LeaderAbilitiesIgnored() ? "" : "Exhaust";
    case "8656409691"://Rio Durant
      return LeaderAbilitiesIgnored() ? "" : "Attack";
    case "8943696478"://Admiral Holdo
      return LeaderAbilitiesIgnored() ? "" : "Buff";
    case "0766281795"://Luke Skywalker
      return LeaderAbilitiesIgnored() ? "" : "Deal Damage";
    case "7661383869"://Darth Vader
      return LeaderAbilitiesIgnored() ? "" : "TIE Fighter";
    case "3132453342"://Captain Phasma
      return LeaderAbilitiesIgnored() ? "" : "Deal Damage";
    case "8520821318"://Poe Dameron
      return LeaderAbilitiesIgnored() ? "" : "Pilot";
    case "3905028200"://Admiral Trench
      return LeaderAbilitiesIgnored() ? "" : "Rummage";
    case "4531112134"://Kazuda Xiono
      return LeaderAbilitiesIgnored() ? "" : "Lose Abilities";
    case "6600603122"://Massassi Tactical Officer
      return "Fighter Attack,Attack";
    case "9921128444"://General Hux
      return "Draw,Attack";
    case "9763190770"://Major Vonreg
      return LeaderAbilitiesIgnored() ? "" : "Play";
    case "5306772000"://Phantom II
      $abilityNames = "Dock,Attack";
      if($validate) {
        $ally = new Ally("MYALLY-" . $index, $currentPlayer);
        if($ally->IsExhausted()) $abilityNames = FilterOutAttackAbilityName($abilityNames);
      }
      return $abilityNames;
    default: return "";
  }
}

function CheckLOFAbilityNames($cardID, $index, $validate) {
  global $currentPlayer;

  switch($cardID) {
    //leaders
    case "2580909557"://Qui-Gon Jinn Leader
      return LeaderAbilitiesIgnored() ? "" : "Bounce/Play";
    case "0024560758"://Darth Maul Leader
      return LeaderAbilitiesIgnored() ? "" : "Deal Damage";
    case "2693401411"://Obi-Wan Kenobi Leader
      return LeaderAbilitiesIgnored() ? "" : "Experience";
    case "3357344238"://Third Sister Leader
      return LeaderAbilitiesIgnored() ? "" : "Play";
    case "8304104587"://Kanan Jarrus Leader
      return LeaderAbilitiesIgnored() ? "" : "Shield";
    case "2520636620"://Mother Talzin Leader
      return LeaderAbilitiesIgnored() ? "" : "Debuff";
    case "3822427538"://Kit Fisto Leader
      return LeaderAbilitiesIgnored() ? "" : "Deal Damage";
    case "5917432593"://Grand Inquisitor Leader
      return LeaderAbilitiesIgnored() ? "" : "Attack";
    case "0092239541"://Avar Kriss Leader
      return LeaderAbilitiesIgnored() ? "" : "Meditate";
    case "5045607736"://Morgan Elsbeth Leader
      return LeaderAbilitiesIgnored() ? "" : "Play";
    case "7077983867"://Ahsoka Tano Leader
      return LeaderAbilitiesIgnored() ? "" : "Sentinel";
    case "9919167831"://Supreme Leader Snoke Leader
      return LeaderAbilitiesIgnored() ? "" : "Experience";
    case "6677799440"://Cal Kestis Leader
      return LeaderAbilitiesIgnored() ? "" : "Exhaust";
    case "1184397926"://Barriss Offee Leader
      return LeaderAbilitiesIgnored() ? "" : "Play";
    case "8536024453"://Anakin Skywalker Leader
      return LeaderAbilitiesIgnored() ? "" : "Play";
    case "5174764156"://Kylo Ren Leader
      return LeaderAbilitiesIgnored() ? "" : "Rummage";
    case "2762251208"://Rey Leader
      return LeaderAbilitiesIgnored() ? "" : "Deal Damage";
    //non-leaders
    case "4024881604"://Adept of Anger
      return "Exhaust,Attack";
    case "3433996932"://Heavy Missile Gunship
      return "Damage,Attack";
    case "4389144613"://Grogu
      return "Move Damage,Attack";
    case "5482818255"://Jedi Consular
      return "Play Unit,Attack";
    case "6800160263"://Caretaker Matron
      return "Draw,Attack";
    case "7d9f8bcb9b"://Anakin Skywalker Leader unit
    case "20f7c21d8b"://Barriss Offee Leader unit
      $abilityNames = "";
      if($validate) {
        $ally = new Ally("MYALLY-" . $index, $currentPlayer);
        $abilityNames = $ally->IsExhausted() ? "Play" : "Play,Attack";
      } else {
        $abilityNames = "Play,Attack";
      }
      if(LeaderAbilitiesIgnored()) {
        $abilityNames = str_replace(",Play", "", $abilityNames);
        $abilityNames = str_replace("Play", "", $abilityNames);
      }
      return $abilityNames;
    case "2236831712"://Leia Organa (Extraordinary)
      $abilityNames = "";
      if($validate) {
        $ally = new Ally("MYALLY-" . $index, $currentPlayer);
        $abilityNames = $ally->IsExhausted()
          ? ($ally->CurrentArena() == "Space" ? "Fly Through Space" : "")
          : ($ally->LostAbilities() ? "Attack" : ($ally->CurrentArena() == "Space" ? "Fly Through Space,Attack" : "Attack"));
      } else {
        $abilityNames = "Fly Through Space,Attack";
      }
      return $abilityNames;
    case "6501780064"://Babu Frik
      return "Droid Attack,Attack";
    default: return "";
  }
}

function CheckIBHAbilityNames($cardID, $index, $validate) {
  global $currentPlayer;

  switch($cardID) {
    case "9389694773"://Darth Vader leader
      return LeaderAbilitiesIgnored() ? "" : "Deal Damage";
    case "9970912404"://Leia Organa leader
      return LeaderAbilitiesIgnored() ? "" : "Heal";
    case "9508246309"://Imperial Deck Officer
        return "Heal,Attack";
    case "9782761594"://Ion Cannon
      return "Damage,Attack";
  }
}

function GetOpponentControlledAbilityNames($cardID) {
  global $currentPlayer, $CS_NumUsesLeaderUpgrade1;
  $otherPlayer = ($currentPlayer == 1 ? 2 : 1);
  $abilityNames = "";

  switch($cardID) {
    case "3577961001"://Mercenary Gunship
      $abilityNames = "Take Control";
      break;
    default: break;
  }

  $theirAlliesWithCardID = explode(",", SearchAlliesForCard($otherPlayer, $cardID));
  for($i=0; $i<count($theirAlliesWithCardID); ++$i) {
    $ally = new Ally("MYALLY-" . $theirAlliesWithCardID[$i], $otherPlayer);
    if($ally->IsUpgraded()) {
      $upgrades = $ally->GetUpgrades(withMetadata:true);
      for($j=0; $j<count($upgrades); ++$j) {
        switch($upgrades[$j]) {
          case "3eb545eb4b"://Poe Dameron JTL leader
            if($upgrades[$j+1] == $currentPlayer && GetClassState($currentPlayer, $CS_NumUsesLeaderUpgrade1) > 0) {
              if($abilityNames != "") $abilityNames .= ",";
              $abilityNames .= "Poe Pilot";
            }
            break;
          default: break;
        }
      }
    }
  }

  return $abilityNames;
}

function GetAbilityIndex($cardID, $index, $abilityName, $theirCard = false)
{
  $abilityName = str_replace("_", " ", $abilityName);
  $names = $theirCard
    ? explode(",", GetOpponentControlledAbilityNames($cardID))
    : explode(",", GetAbilityNames($cardID, $index));
  for($i = 0; $i < count($names); ++$i) {
    if($abilityName == $names[$i]) return $i;
  }
  return 0;
}

function GetResolvedAbilityType($cardID, $from="-", $theirCard = false)
{
  global $currentPlayer, $CS_AbilityIndex, $CS_PlayIndex;

  if($from == "HAND") return "";
  $abilityIndex = GetClassState($currentPlayer, $CS_AbilityIndex);
  $abilityTypes = GetAbilityTypes($cardID, GetClassState($currentPlayer, $CS_PlayIndex));
  if($abilityTypes == "" || $abilityIndex == "-") return GetAbilityType($cardID, -1, $from);
  $abilityTypes = explode(",", $abilityTypes);

  return $theirCard ? "A" : $abilityTypes[$abilityIndex]; //This will need to be updated if there are ever non-action abilities that can be activated on opponent's cards.
}

function GetResolvedAbilityName($cardID, $from="-")
{
  global $currentPlayer, $CS_AbilityIndex, $CS_PlayIndex, $CS_OppCardActive;
  $theirCard = GetClassState($currentPlayer, $CS_OppCardActive) == 1;
  if($from != "PLAY" && $from != "EQUIP" && $from != "-") return "";
  $abilityIndex = GetClassState($currentPlayer, $CS_AbilityIndex);
  $abilityNames = $theirCard ? GetOpponentControlledAbilityNames($cardID) : GetAbilityNames($cardID, GetClassState($currentPlayer, $CS_PlayIndex));
  if($abilityNames == "" || $abilityIndex == "-") return "";
  $abilityNames = explode(",", $abilityNames);
  return $abilityNames[$abilityIndex];
}

function IsPlayable($cardID, $phase, $from, $index = -1, &$restriction = null, $player = "")
{
  global $currentPlayer, $combatChainState, $CCS_BaseAttackDefenseMax, $CS_NumNonAttackCards, $CS_NumAttackCards;
  global $CCS_ResourceCostDefenseMin, $actionPoints, $mainPlayer, $defPlayer;
  global $combatChain;
  if($from == "ARS" || $from == "BANISH") return false;
  if($player == "") $player = $currentPlayer;
  if($phase == "P" && $from == "HAND") return true;
  if(IsPlayRestricted($cardID, $restriction, $from, $index, $player)) return false;
  $cardType = CardType($cardID);
  if($cardType == "W" || $cardType == "AA")
  {
    $char = &GetPlayerCharacter($player);
    if($char[1] != 2) return false;//Can't attack if rested
  }
  $otherPlayer = ($player == 1 ? 2 : 1);
  $potentialExploitAllies = GetAllyCount($currentPlayer);
  $potentialCost = (CardCost($cardID)
    + SelfCostModifier($cardID, $from, reportMode: true)
    + CurrentEffectCostModifiers($cardID, $from, reportMode:true)
    - 2*min(ExploitAmount($cardID, $currentPlayer), $potentialExploitAllies)
  );
  $potentialPilotingCost = PilotingCost($cardID) == -1
    ? -1
    : PilotingCost($cardID)
      + SelfCostModifier($cardID, $from, reportMode: true)
      + CurrentEffectCostModifiers($cardID, $from, reportMode:true);
  if($from == "HAND" //The Darksaber
      && $cardID == "3141660491"
      && ((SearchCount(SearchAllies($currentPlayer, trait:"Mandalorian")) > 0)  && NumResourcesAvailable($currentPlayer) > 4)
      || (ControlsNamedCard($currentPlayer, "Foundling") && NumResourcesAvailable($currentPlayer) > 4)
    )return true;
  if($from == "HAND"
    && $potentialCost > NumResourcesAvailable($currentPlayer)
    && ($potentialPilotingCost == -1 || $potentialPilotingCost > NumResourcesAvailable($currentPlayer) || SearchCount(SearchAllies($player, trait:"Vehicle")) == 0)
    && !HasAlternativeCost($cardID)) return false;
  if($from == "RESOURCES") {
    if(!PlayableFromResources($cardID, index:$index)) return false;
    if((SmuggleCost($cardID, index:$index) + SelfCostModifier($cardID, $from, reportMode:true) + CurrentEffectCostModifiers($cardID, $from, reportMode:true)) > NumResourcesAvailable($currentPlayer) && !HasAlternativeCost($cardID)) return false;
    if(!SmuggleAdditionalCosts($cardID)) return false;
  }
  if(DefinedTypesContains($cardID, "Upgrade", $player) && SearchCount(SearchAllies($player)) == 0 && SearchCount(SearchAllies($otherPlayer)) == 0) return false;
  if($phase == "M" && $from == "HAND") return true;
  if($phase == "M" && $from == "GY") {
    $discard = &GetDiscard($player);
    if($discard[$index] == "4843813137") return true;//Brutal Traditions
    return !str_starts_with($discard[$index+1], "TTOP") && str_starts_with($discard[$index+1], "TT");
  }
  if($phase == "M" && $from == "TGY") {
    $discard = &GetDiscard($player);
    return str_starts_with($discard[$index+1], "TTOP");
  }
  $isStaticType = IsStaticType($cardType, $from, $cardID);
  if($isStaticType) {
    $cardType = GetAbilityType($cardID, $index, $from);
    if($cardType == "") {
      $abilityTypes = GetAbilityTypes($cardID, $index);
      if($abilityTypes == "") return false;
      $typeArr = explode(",", $abilityTypes);
      $cardType = $typeArr[0];
    }
  }
  if($phase == "M" && ($cardType == "A" || $cardType == "AA" || $cardType == "I")) return true;
  if($cardType == "I" && ($phase == "INSTANT" || $phase == "A" || $phase == "D")) return true;
  return false;

}

function HasAlternativeCost($cardID) {
  switch($cardID) {
    case "9644107128"://Bamboozle
      return true;
    default: return false;
  }
}

//Preserve
function GoesWhereAfterResolving($cardID, $from = null, $player = "", $playedFrom="", $resourcesPaid="", $additionalCosts="")
{
  global $currentPlayer, $mainPlayer, $CS_PlayedAsUpgrade;
  if($player == "") $player = $currentPlayer;
  if(DefinedTypesContains($cardID, "Upgrade", $currentPlayer) || GetClassState($player, $CS_PlayedAsUpgrade) > 0) return "ATTACHTARGET";
  if(IsAlly($cardID)) return "ALLY";
  switch($cardID) {
    case "2703877689": return "RESOURCE";//Resupply
    default: return "GY";
  }
}


function UpgradeFilter($cardID)
{
  if($cardID == "5375722883") return "trait!=Vehicle";//R2-D2 (Artooooooooo!)
  if(PilotingCost($cardID) >= 0) return "trait!=Vehicle";
  switch($cardID) {
    //non-Vehicle upgrades
    case "0160548661"://Fallen Lightsaber
    case "8495694166"://Jedi Lightsaber
    case "0705773109"://Vader's Lightsaber
    case "6903722220"://Luke's Lightsaber
    case "1323728003"://Electrostaff
    case "3514010297"://Mandalorian Armor
    case "3525325147"://Vambrace Grappleshot
    case "5874342508"://Hotshot DL-44 Blaster
    case "0754286363"://The Mandalorian's Rifle
    case "5738033724"://Boba Fett's Armor
    case "6471336466"://Vambrace Flamethrower
    case "3141660491"://The Darksaber
    case "6775521270"://Inspiring Mentor
    case "6117103324"://Jetpack
    case "7280804443"://Hold-Out Blaster
    case "6410481716"://Mace Windu's Lightsaber
    case "0414253215"://General's Blade
    case "0741296536"://Ahsoka's Padawan Lightsaber
    case "0875550518"://Grievous's Wheel Bike
    case "2167393423"://Darth Maul's Lightsaber
    case "3445044882"://Qui-Gon Jinn's Lightsaber
    case "6128668392"://Ascension Cable
    case "1759165041"://Heavy Blaster Cannon
    case "8085392838"://Corrupted Saber
    case "5800386133"://Yoda's Lightsaber
    case "8580514429"://Pillio Star Compass
    case "1637958279"://Kylo Ren's Lightsaber
    case "3895004077"://Inquisitor's Lightsaber
      return "trait=Vehicle";
    //end non-Vehicle upgrades
    //Vehicle only upgrades
    case "3987987905"://Hardpoint Heavy Blaster
    case "7280213969"://Smuggling Compartment
    case "9338356823"://Dorsal Turret
    case "9981313319"://Twin Laser Turret
      return "trait!=Vehicle";
    //end Vehicle only upgrades
    //non-Leader upgrades
    case "1368144544"://Imprisoned
    case "7718080954"://Frozen in Carbonite
    case "6911505367"://Second Chance
    case "7270736993"://Unrefusable Offer
      return "leader=1";
    //end non-Leader upgrades
    //Token upgrades
    case "4886127868"://Nameless Valor
      return "token=0";
    //end Token upgrades
    //Force unit upgrades
    case "3688574857"://Constructed Lightsaber
    case "0412810079"://Sith Holocron
    case "0545149763"://Jedi Trials
    case "3730933081"://Bolstered Endurance
    case "7377298352"://Jedi Holocron
      return "trait!=Force";
    //end Force unit upgrades
    //other filters
    case "5016817239"://Superheavy Ion Cannon
      return "trait!=Capital_Ship&trait!=Transport";
    case "5306772000"://Phantom II
      return "cardID!=6931439330&cardID!=5763330426";
    case "6885149318"://Knight's Saber
      return "trait!=Jedi";//TODO: look into mixed = and != for trait=Vehicle&trait!=Jedi
    //end other filters
    default: return "";
  }
}

function UpgradeIsOnlyForFriendlyUnits($cardID) {
  switch($cardID) {
    case "8877249477"://Legal Authority
    case "0754286363"://The Mandalorian's Rifle
    case "3848295601"://Craving Power
    case "2167393423"://Darth Maul's Lightsaber
    case "3445044882"://Qui-Gon Jinn's Lightsaber
      return true;
    default: return false;
  }
}

function CanPlayInstant($phase)
{
  if($phase == "M") return true;
  if($phase == "A") return true;
  if($phase == "D") return true;
  if($phase == "INSTANT") return true;
  return false;
}

function IsCloned($uniqueID) {
  $ally = new Ally($uniqueID);
  return $ally->IsCloned();
}

function IsToken($cardID)
{
  return match($cardID) {
    "8752877738",//Shield
    "2007868442",//Experience
    "3463348370",//Battle Droid
    "3941784506",//Clone Trooper
    "9415311381",//X-Wing
    "7268926664" => true,//Tie Fighter
    default => false
  };
}

function IsPitchRestricted($cardID, &$restriction, $from = "", $index = -1)
{
  global $playerID;
  return false;
}

function IsPlayRestricted($cardID, &$restriction, $from = "", $index = -1, $player = "")
{
  global $currentPlayer, $mainPlayer;
  if($player == "") $player = $currentPlayer;
  $otherPlayer = ($currentPlayer == 1 ? 2 : 1);
  switch($cardID) {
    case "0523973552"://I Am Your Father
      $theirAllies = &GetAllies($otherPlayer);
      return count($theirAllies) == 0;
    default: return false;
  }
}

function IsDefenseReactionPlayable($cardID, $from)
{
  global $combatChain, $mainPlayer;
  if(CurrentEffectPreventsDefenseReaction($from)) return false;
  return true;
}

function IsAction($cardID)
{
  $cardType = CardType($cardID);
  if($cardType == "A" || $cardType == "AA") return true;
  $abilityType = GetAbilityType($cardID);
  if($abilityType == "A" || $abilityType == "AA") return true;
  return false;
}

function GoesOnCombatChain($phase, $cardID, $from, $theirCard = false)
{
  global $layers;
  if($theirCard) return false;
  if($phase != "B" && $from == "EQUIP" || $from == "PLAY") $cardType = GetResolvedAbilityType($cardID, $from, $theirCard);
  else $cardType = CardType($cardID);
  if($cardType == "I") return false; //Instants as yet never go on the combat chain
  if($phase == "B" && count($layers) == 0) return true; //Anything you play during these combat phases would go on the chain
  if(($phase == "A" || $phase == "D") && $cardType == "A") return false; //Non-attacks played as instants never go on combat chain
  if($cardType == "AR") return true;
  if($cardType == "DR") return true;
  if(($phase == "M" || $phase == "ATTACKWITHIT") && $cardType == "AA") return true; //If it's an attack action, it goes on the chain
  return false;
}

function IsStaticType($cardType, $from = "", $cardID = "")
{
  if($cardType == "C" || $cardType == "E" || $cardType == "W") return true;
  if($from == "PLAY") return true;
  //if($cardID != "" && $from == "BANISH" && AbilityPlayableFromBanish($cardID)) return true;//FAB
  return false;
}

function LeaderUnit($cardID) {
  switch($cardID) {
    //Spark of Rebellion
    case "3572356139"://Chewbacca (Walking Carpet)
      return "8301e8d7ef";
    case "2579145458"://Luke Skywalker
      return "0dcb77795c";
    case "2912358777"://Grand Moff Tarkin
      return "59cd013a2d";
    case "3187874229"://Cassian Andor
      return "3c60596a7a";
    case "4841169874"://Sabine Wren
      return "51e8757e4c";
    case "2048866729"://Iden Versio
      return "b0dbca5c05";
    case "6088773439"://Darth Vader
      return "0ca1902a46";
    case "4263394087"://Chirrut Imwe
      return "d1a7b76ae7";
    case "4626028465"://Boba Fett
      return "0e65f012f5";
    case "7911083239"://Grand Inquisitor
      return "6827598372";
    case "5954056864"://Han Solo
      return "5e90bd91b0";
    case "6514927936"://Leia Organa
      return "87e8807695";
    case "8244682354"://Jyn Erso
      return "20f21b4948";
    case "8600121285"://IG-88
      return "fb475d4ea4";
    case "5784497124"://Emperor Palpatine
      return "6c5b96c7ef";
    case "8560666697"://Director Krennic
      return "e2c6231b35";
    case "7440067052"://Hera Syndulla
      return "80df3928eb";
    case "1951911851"://Grand Admiral Thrawn
      return "02199f9f1e";
    //Shadows of the Galaxy
    case "1480894253"://Kylo Ren
      return "8def61a58e";
    case "2503039837"://Moff Gideon Leader
      return "4484318969";
    case "3045538805"://Hondo Ohnaka
      return "415bde775d";
    case "2526288781"://Bossk
      return "d2bbda6982";
    case "7424360283"://Bo-Katan Kryze
      return "a579b400c0";
    case "5440730550"://Lando Calrissian
      return "040a3e81f3";
    case "1384530409"://Cad Bane
      return "724979d608";
    case "2432897157"://Qi'Ra
      return "4aa0804b2b";
    case "4352150438"://Rey
      return "e091d2a983";
    case "9005139831"://The Mandalorian
      return "4088c46c4d";
    case "0911874487"://Fennec Shand
      return "2b13cefced";
    case "9794215464"://Gar Saxon
      return "3feee05e13";
    case "9334480612"://Boba Fett (Daimyo)
      return "919facb76d";
    case "0254929700"://Doctor Aphra
      return "58f9f2d4a0";
    case "9226435975"://Han Solo Red
      return "a742dea1f1";
    case "0622803599"://Jabba the Hutt
      return "f928681d36";
    case "9596662994"://Finn
      return "8903067778";
    case "8709191884"://Hunter (Outcast Sergeant)
      return "c9ff9863d7";
    //Twilight of the Republic
    case "8777351722"://Anakin Skywalker
      return "24a81d97b5";
    case "2784756758"://Obi-wan Kenobi
      return "0ee1e18cf4";
    case "8929774056"://Asajj Ventress
      return "f8e0c65364";
    case "6461101372"://Maul
      return "40b649e6f6";
    case "2155351882"://Ahsoka Tano
      return "7224a2074a";
    case "5081383630"://Pre Viszla
      return "11299cc72f";
    case "5683908835"://Count Dooku
      return "6fa73a45ed";
    case "2358113881"://Quinlan Vos
      return "3f7f027abd";
    case "4628885755"://Mace Windu
      return "9b212e2eeb";
    case "7734824762"://Captain Rex
      return "47557288d6";
    case "9155536481"://Jango Fett
      return "cfdcbd005a";
    case "1686059165"://Wat Tambor
      return "12122bc0b1";
    case "2742665601"://Nala Se
      return "f05184bd91";
    case "2847868671"://Yoda
      return "e71f6f766c";
    case "6064906790"://Nute Gunray
      return "b7caecf9a3";
    case "2872203891"://General Grievous
      return "fb7af4616c";
    case "2870878795"://Padme Amidala
      return "4ae6d91ddc";
    //Jump to Lightspeed
    case "0011262813"://Wedge Antilles
      return "6414788e89";
    case "0616724418"://Han Solo
      return "a015eb5c5e";
    case "3658069276"://Lando Calrissian
      return "3064aff14f";
    case "4179470615"://Asajj Ventress
      return "3f0b5622a7";
    case "7514405173"://Admiral Ackbar
      return "36859e7ec4";
    case "9831674351"://Boba Fett JTL
      return "f6eb711cf3";
    case "4030832630"://Admiral Piett
      return "649c6a9dbd";
    case "5846322081"://Grand Admiral Thrawn JTL
      return "53207e4131";
    case "3933322003"://Rose Tico
      return "590b638b18";
    case "8656409691"://Rio Durant's Leader unit
      return "81a416eb1f";
    case "8943696478"://Admiral Holdo
      return "ccf9474416";
    case "0766281795"://Luke Skywalker
      return "11e54776e9";
    case "7661383869"://Darth Vader
      return "fb0da8985e";
    case "3132453342"://Captain Phasma
      return "fda7bdc316";
    case "8520821318"://Poe Dameron
      return "3eb545eb4b";
    case "3905028200"://Admiral Trench
      return "7c082aefc9";
    case "4531112134"://Kazuda Xiono
      return "c1700fc85b";
    case "9763190770"://Major Vonreg
      return "d8a5bf1a15";
    //Legends of the Force
    case "2580909557"://Qui-Gon Jinn
      return "6def6570f5";
    case "0024560758"://Darth Maul
      return "b2072f156c";
    case "4637578649"://Darth Revan
      return "754e979196";
    case "2693401411"://Obi-Wan Kenobi
      return "d12b136775";
    case "3357344238"://Third Sister
      return "5387ca4af6";
    case "8304104587"://Kanan Jarrus
      return "fadc48bab2";
    case "2520636620"://Mother Talzin
      return "32fd8db633";
    case "3822427538"://Kit Fisto
      return "90e2d4d83e";
    case "5917432593"://Grand Inquisitor
      return "5b24706856";
    case "0092239541"://Avar Kriss
      return "e6dc0d1cee";
    case "5045607736"://Morgan Elsbeth
      return "b70416cfea";
    case "7077983867"://Ahsoka Tano
      return "1a61e6df76";
    case "9919167831"://Supreme Leader Snoke
      return "e8f5e7c3f6";
    case "6677799440"://Cal Kestis
      return "bf3545c5e0";
    case "1184397926"://Barriss Offee
      return "20f7c21d8b";
    case "8536024453"://Anakin Skywalker
      return "7d9f8bcb9b";
    case "5174764156"://Kylo Ren
      return "d911b778e4";
    case "2762251208"://Rey
      return "9d589c1981";
    //Intro Battle: Hoth
    case "9389694773"://Darth Vader
      return "9f6a0193d6";
    case "9970912404"://Leia Organa
      return "d1f7a7c11b";
    default: return "";
  }
}

function LeaderUndeployed($cardID) {
  switch($cardID) {
    //Spark of Rebellion
    case "8301e8d7ef"://Chewbacca (Walking Carpet)
      return "3572356139";
    case "0dcb77795c"://Luke Skywalker
      return "2579145458";
    case "59cd013a2d"://Grand Moff Tarkin
      return "2912358777";
    case "3c60596a7a"://Cassian Andor
      return "3187874229";
    case "51e8757e4c"://Sabine Wren
      return "4841169874";
    case "b0dbca5c05"://Iden Versio
      return "2048866729";
    case "0ca1902a46"://Darth Vader
      return "6088773439";
    case "d1a7b76ae7"://Chirrut Imwe
      return "4263394087";
    case "0e65f012f5"://Boba Fett
      return "4626028465";
    case "6827598372"://Grand Inquisitor
      return "7911083239";
    case "5e90bd91b0"://Han Solo
      return "5954056864";
    case "87e8807695"://Leia Organa
      return "6514927936";
    case "20f21b4948"://Jyn Erso
      return "8244682354";
    case "fb475d4ea4"://IG-88
      return "8600121285";
    case "6c5b96c7ef"://Emperor Palpatine
      return "5784497124";
    case "e2c6231b35"://Director Krennic
      return "8560666697";
    case "80df3928eb"://Hera Syndulla
      return "7440067052";
    case "02199f9f1e"://Grand Admiral Thrawn
      return "1951911851";
    //Shadows of the Galaxy
    case "8def61a58e"://Kylo Ren
      return "1480894253";
    case "4484318969"://Moff Gideon Leader Unit
      return "2503039837";
    case "415bde775d"://Hondo Ohnaka Leader Unit
      return "3045538805";
    case "d2bbda6982"://Bossk
      return "2526288781";
    case "a579b400c0"://Bo-Katan Kryze
      return "7424360283";
    case "040a3e81f3"://Lando Calrissian
      return "5440730550";
    case "724979d608"://Cad Bane
      return "1384530409";
    case "4aa0804b2b"://Qi'Ra
      return "2432897157";
    case "e091d2a983"://Rey
      return "4352150438";
    case "4088c46c4d"://The Mandalorian
      return "9005139831";
    case "2b13cefced"://Fennec Shand
      return "0911874487";
    case "3feee05e13"://Gar Saxon
      return "9794215464";
    case "919facb76d"://Boba Fett (Daimyo)
      return "9334480612";
    case "58f9f2d4a0"://Doctor Aphra
      return "0254929700";
    case "a742dea1f1"://Han Solo Red
      return "9226435975";
    case "f928681d36"://Jabba the Hutt
      return "0622803599";
    case "8903067778"://Finn
      return "9596662994";
    case "c9ff9863d7"://Hunter (Outcast Sergeant)
      return "8709191884";
    //Twilight of the Republic
    case "24a81d97b5"://Anakin Skywalker
      return "8777351722";
    case "0ee1e18cf4"://Obi-wan Kenobi
      return "2784756758";
    case "f8e0c65364"://Asajj Ventress
      return "8929774056";
    case "40b649e6f6"://Maul
      return "6461101372";
    case "7224a2074a"://Ahsoka Tano
      return "2155351882";
    case "11299cc72f"://Pre Viszla
      return "5081383630";
    case "6fa73a45ed"://Count Dooku
      return "5683908835";
    case "3f7f027abd"://Quinlan Vos
      return "2358113881";
    case "9b212e2eeb"://Mace Windu
      return "4628885755";
    case "47557288d6"://Captain Rex
      return "7734824762";
    case "cfdcbd005a"://Jango Fett
      return "9155536481";
    case "12122bc0b1"://Wat Tambor
      return "1686059165";
    case "f05184bd91"://Nala Se
      return "2742665601";
    case "e71f6f766c"://Yoda
      return "2847868671";
    case "b7caecf9a3"://Nute Gunray
      return "6064906790";
    case "fb7af4616c"://General Grievous
      return "2872203891";
    case "4ae6d91ddc"://Padme Amidala
      return "2870878795";
    //Jump to Lightspeed
    case "6414788e89"://Wedge Antilles
      return "0011262813";
    case "a015eb5c5e"://Han Solo
      return "0616724418";
    case "3064aff14f"://Lando Calrissian
      return "3658069276";
    case "3f0b5622a7"://Asajj Ventress
      return "4179470615";
    case "36859e7ec4"://Admiral Ackbar
      return "7514405173";
    case "f6eb711cf3"://Boba Fett
      return "9831674351";
    case "649c6a9dbd"://Admiral Piett
      return "4030832630";
    case "53207e4131"://Grand Admiral Thrawn JTL
      return "5846322081";
    case "590b638b18"://Rose Tico
      return "3933322003";
    case "81a416eb1f"://Rio Durant
      return "8656409691";
    case "ccf9474416"://Admiral Holdo
      return "8943696478";
    case "11e54776e9"://Luke Skywalker
      return "0766281795";
    case "fb0da8985e"://Darth Vader
      return "7661383869";
    case "fda7bdc316"://Captain Phasma Leader Unit
      return "3132453342";
    case "3eb545eb4b"://Poe Dameron
      return "8520821318";
    case "7c082aefc9"://Admiral Trench Leader Unit
      return "3905028200";
    case "c1700fc85b"://Kazuda Xiono Leader Unit
      return "4531112134";
    case "d8a5bf1a15"://Major Vonreg
      return "9763190770";
    //Legends of the Force
    case "6def6570f5"://Qui-Gon Jinn
      return "2580909557";
    case "b2072f156c"://Darth Maul
      return "0024560758";
    case "754e979196"://Darth Revan
      return "4637578649";
    case "d12b136775"://Obi-Wan Kenobi
      return "2693401411";
    case "5387ca4af6"://Third Sister
      return "3357344238";
    case "fadc48bab2"://Kanan Jarrus
      return "8304104587";
    case "32fd8db633"://Mother Talzin
      return "2520636620";
    case "90e2d4d83e"://Kit Fisto
      return "3822427538";
    case "5b24706856"://Grand Inquisitor
      return "5917432593";
    case "e6dc0d1cee"://Avar Kriss
      return "0092239541";
    case "b70416cfea"://Morgan Elsbeth
      return "5045607736";
    case "1a61e6df76"://Ahsoka Tano
      return "7077983867";
    case "e8f5e7c3f6"://Supreme Leader Snoke
      return "9919167831";
    case "bf3545c5e0"://Cal Kestis
      return "6677799440";
    case "20f7c21d8b"://Barriss Offee
      return "1184397926";
    case "7d9f8bcb9b"://Anakin Skywalker
      return "8536024453";
    case "d911b778e4"://Kylo Ren
      return "5174764156";
    case "9d589c1981"://Rey
      return "2762251208";
    //Intro Battle: Hoth
    case "9f6a0193d6"://Darth Vader
      return "9389694773";
    case "d1f7a7c11b"://Leia Organa
      return "9970912404";
    default: return "";
  }
}

function LeaderCanPilot($cardID) {
  switch($cardID) {
    case "0011262813"://Wedge Antilles
    case "0616724418"://Han Solo
    case "3658069276"://Lando Calrissian
    case "4179470615"://Asajj Ventress
    case "9831674351"://Boba Fett
    case "8656409691"://Rio Durant
    case "0766281795"://Luke Skywalker
    case "7661383869"://Darth Vader
    case "4531112134"://Kazuda Xiono
    case "9763190770"://Major Vonreg
      return true;
    default: return false;
  }
}

function CardHP($cardID) {
  switch($cardID) {
    default:
      return CardHPDictionary($cardID);
  }
}

function HasBladeBreak($cardID)
{
  global $defPlayer;
  switch($cardID) {

    default: return false;
  }
}

function HasBattleworn($cardID)
{
  switch($cardID) {

    default: return false;
  }
}

function HasTemper($cardID)
{
  switch($cardID) {

    default: return false;
  }
}

function RequiresDiscard($cardID)
{
  switch($cardID) {

    default: return false;
  }
}

function ETASteamCounters($cardID)
{
  switch ($cardID) {

    default: return 0;
  }
}

function AbilityHasGoAgain($cardID)
{
  return true;
}

function DoesEffectGrantDominate($cardID)
{
  global $combatChainState;
  switch ($cardID) {

    default: return false;
  }
}

function CharacterNumUsesPerTurn($cardID)
{
  switch ($cardID) {
    default: return 1;
  }
}

//Active (2 = Always Active, 1 = Yes, 0 = No)
function CharacterDefaultActiveState($cardID)
{
  switch ($cardID) {

    default: return 2;
  }
}

//Hold priority for triggers (2 = Always hold, 1 = Hold, 0 = Don't Hold)
function AuraDefaultHoldTriggerState($cardID)
{
  switch ($cardID) {

    default: return 2;
  }
}

function ItemDefaultHoldTriggerState($cardID)
{
  switch($cardID) {

    default: return 2;
  }
}

function IsCharacterActive($player, $index)
{
  $character = &GetPlayerCharacter($player);
  return $character[$index + 9] == "1";
}

function HasReprise($cardID)
{
  switch($cardID) {

    default: return false;
  }
}

//Is it active AS OF THIS MOMENT?
function RepriseActive()
{
  global $currentPlayer, $mainPlayer;
  return 0;
}

function HasCombo($cardID)
{
  switch ($cardID) {
    case "WTR081": case "WTR083": case "WTR084": case "WTR085": case "WTR086": case "WTR087":
    case "WTR088": case "WTR089": case "WTR090": case "WTR091": case "WTR095": case "WTR096":
    case "WTR097": case "WTR104": case "WTR105": case "WTR106": case "WTR110": case "WTR111": case "WTR112":
      return true;
    case "CRU054": case "CRU055": case "CRU056": case "CRU057": case "CRU058": case "CRU059":
    case "CRU060": case "CRU061": case "CRU062":
      return true;
    case "EVR038": case "EVR040": case "EVR041": case "EVR042": case "EVR043":
      return true;
    case "DYN047":
    case "DYN056": case "DYN057": case "DYN058":
    case "DYN059": case "DYN060": case "DYN061":
      return true;
    case "OUT050":
    case "OUT051":
    case "OUT056": case "OUT057": case "OUT058":
    case "OUT059": case "OUT060": case "OUT061":
    case "OUT062": case "OUT063": case "OUT064":
    case "OUT065": case "OUT066": case "OUT067":
    case "OUT074": case "OUT075": case "OUT076":
    case "OUT080": case "OUT081": case "OUT082":
      return true;
  }
  return false;
}

function ComboActive($cardID = "")
{
  global $combatChainState, $combatChain, $chainLinkSummary, $mainPlayer;
  if ($cardID == "" && count($combatChain) > 0) $cardID = $combatChain[0];
  if ($cardID == "") return false;
  if(count($chainLinkSummary) == 0) return false;//No combat active if no previous chain links
  $lastAttackNames = explode(",", $chainLinkSummary[count($chainLinkSummary)-ChainLinkSummaryPieces()+4]);
  for($i=0; $i<count($lastAttackNames); ++$i)
  {
    $lastAttackName = GamestateUnsanitize($lastAttackNames[$i]);
    switch ($cardID) {

      default: break;
    }
  }
  return false;
}

function SmuggleCost($cardID, $player="", $index="")
{
  global $currentPlayer;
  if($player == "") $player = $currentPlayer;
  $minCost = -1;
  switch($cardID) {
    case "1982478444": $minCost = 7; break;//Vigilant Pursuit Craft
    case "0866321455": $minCost = 3; break;//Smuggler's Aid
    case "6037778228": $minCost = 5; break;//Night Owl Skirmisher
    case "2288926269": $minCost = 6; break;//Privateer Crew
    case "5752414373": $minCost = 6; break;//Millennium Falcon
    case "8552719712": $minCost = 7; break;//Pirate Battle Tank
    case "2522489681": $minCost = 6; break;//Zorii Bliss
    case "4534554684": $minCost = 4; break;//Freetown Backup
    case "9690731982": $minCost = 3; break;//Reckless Gunslinger
    case "5874342508": $minCost = 3; break;//Hotshot DL-44 Blaster
    case "3881257511": $minCost = 4; break;//Tech
    case "5830140660": $minCost = 4; break;//Bazine Netal
    case "8645125292": $minCost = 3; break;//Covert Strength
    case "4783554451": $minCost = 7; break;//First Light
    case "6847268098": $minCost = 2; break;//Timely Intervention
    case "5632569775": $minCost = 5; break;//Lom Pyke
    case "9552605383": $minCost = 4; break;//L3-37
    case "1312599620": $minCost = 4; break;//Smuggler's Starfighter
    case "8305828130": $minCost = 4; break;//Warbird Stowaway
    case "9483244696": $minCost = 5; break;//Weequay Pirate Gang
    case "5171970586": $minCost = 3; break;//Collections Starhopper
    case "6234506067": $minCost = 5; break;//Cassian Andor
    case "5169472456": $minCost = 9; break;//Chewbacca Pykesbane
    case "9871430123": $minCost = 6; break;//Sugi
    case "9040137775": $minCost = 6; break;//Principled Outlaw
    case "1938453783": $minCost = 4; break;//Armed to the Teeth
    case "1141018768": $minCost = 3; break;//Commission
    case "4002861992": $minCost = 7; break;//DJ (Blatant Thief)
    case "6117103324": $minCost = 4; break;//Jetpack
    case "7204838421": $minCost = 6; break;//Enterprising Lackeys
    case "3010720738": $minCost = 5; break;//Tobias Beckett
    default: break;
  }
  $allies = &GetAllies($player);
  for($i=0; $i<count($allies); $i+=AllyPieces())
  {
    switch($allies[$i]) {
      case "3881257511"://Tech
        $techAlly = new Ally("MYALLY-" . $i, $player);
        if(!$techAlly->LostAbilities()) {
          $cost = CardCost($cardID)+2;
          if($minCost == -1 || $minCost > $cost) $minCost = $cost;
        }
        break;
      default: break;
    }
  }
  return $minCost;
}

function SmuggleAdditionalCosts($cardID, $player = ""): bool {
  global $currentPlayer;
  if($player == "") $player = $currentPlayer;
  switch($cardID) {
    case "4783554451"://First Light
      return count(GetAllies($player)) > 0;
    default: return true;
  }
}

function PilotingCost($cardID, $player = "") {
  global $currentPlayer;
  if($player == "") $player = $currentPlayer;
  $minCost = -1;
  switch($cardID) {
    //Jump to Lightspeed
    case "0514089787": $minCost = 2; break;//Frisk
    case "0524529055": $minCost = 2; break;//Snap Wexley
    case "0587196584": $minCost = 1; break;//Independent Smuggler
    case "1039444094": $minCost = 2; break;//Paige Tico
    case "1911230033": $minCost = 1; break;//Wingman Victor Three
    case "1463418669": $minCost = 2; break;//IG-88
    case "2283726359": $minCost = 1; break;//BB-8
    case "2633842896": $minCost = 1; break;//Briggs Darklighter
    case "3282713547": $minCost = 2; break;//Dengar
    case "3475471540": $minCost = 2; break;//Cassian Andor
    case "3874382333": $minCost = 2; break;//Academy Graduate
    case "3878744555": $minCost = 3; break;//Interceptor Ace
    case "4573745395": $minCost = 2; break;//Bossk
    case "4921363233": $minCost = 1; break;//Wingman Victor Two
    case "5375722883": $minCost = 0; break;//R2-D2
    case "5673100759": $minCost = 2; break;//Boshek
    case "6421006753": $minCost = 2; break;//The Mandalorian
    case "6610553087": $minCost = 1; break;//Nien Nunb
    case "6720065735": $minCost = 2; break;//Han Solo (Has His Moments)
    case "7208848194": $minCost = 3; break;//Chewbacca
    case "7420426716": $minCost = 1; break;//Dagger Squadron Pilot
    case "7700932371": $minCost = 2; break;//Boba Fett
    case "8523415830": $minCost = 2; break;//Anakin Skywalker
    case "9325037410": $minCost = 3; break;//Iden Versio
    case "9430527677": $minCost = 2; break;//Hera Syndulla
    case "5942811090": $minCost = 3; break;//Luke Skywalker (You Still With Me?)
    case "8757741946": $minCost = 2; break;//Poe Dameron (One Hell of a Pilot)
    case "0511138070": $minCost = 2; break;//Astromech Pilot
    case "8930110877": $minCost = 2; break;//Hopeful Volunteer
    case "4164902248": $minCost = 1; break;//Sullustan Spacer
    case "0086781673": $minCost = 2; break;//Tam Ryvora
    case "2532510371": $minCost = 1; break;//Trace Martez
    case "3261127126": $minCost = 2; break;//Clone Pilot
    case "3287404938": $minCost = 2; break;//Determined Recruit
    case "3356393152": $minCost = 1; break;//Idoctrinated Conscript
    case "6032641503": $minCost = 3; break;//L3-37
    case "6079255999": $minCost = 3; break;//Darth Vader pilot unit
    case "9706341387": $minCost = 2; break;//Jarek Yeager
    default: break;
  }
  return $minCost;
}

function IsUnconventionalPilot($cardID) {
  switch($cardID) {
    case "0979322247"://Sidon Ithano
    case "6515230001"://Pantoran Starship Thief
    case "5306772000"://Phantom II
      return true;
    default: return false;
  }
}

function isBountyRecollectable($cardID) {
  switch ($cardID) {
    case "7642980906"://Stolen Landspeeder
    case "7270736993"://Unrefusable Offer
      return false;
    default: return true;
  }
}

// function PlayableFromBanish($cardID, $mod="")//FAB
// {
//   global $currentPlayer, $CS_NumNonAttackCards, $CS_Num6PowBan;
//   $mod = explode("-", $mod)[0];
//   if($mod == "TCL" || $mod == "TT" || $mod == "TTFREE" || $mod == "TCC" || $mod == "NT" || $mod == "INST") return true;
//   switch($cardID) {

//     default: return false;
//   }
// }

function PlayableFromResources($cardID, $player="", $index="") {
  global $currentPlayer;
  if($player == "") $player = $currentPlayer;
  if(SmuggleCost($cardID, $player, $index) > 0) return true;
  switch($cardID) {
    default: return false;
  }
}

// function AbilityPlayableFromBanish($cardID)//FAB
// {
//   global $currentPlayer, $mainPlayer;
//   switch($cardID) {
//     default: return false;
//   }
// }

function IsNotFlipatine($char) {
  //Chancellor Palpatine Leader + Darth Sidious Leader
  return $char[CharacterPieces()] != "0026166404" && $char[CharacterPieces()] != "ad86d54e97";
}

function IsNotExhaustedTrench($char) {
  return $char[CharacterPieces()] != "3905028200" || $char[CharacterPieces()+1] != "1";
}

function RequiresDieRoll($cardID, $from, $player)
{
  global $turn;
  if(GetDieRoll($player) > 0) return false;
  if($turn[0] == "B") return false;
  return false;
}

function IsSpecialization($cardID)
{
  switch ($cardID) {

    default:
      return false;
  }
}

function Is1H($cardID)
{
  switch ($cardID) {

    default:
      return false;
  }
}

function AbilityPlayableFromCombatChain($cardID)
{
  switch($cardID) {

    default: return false;
  }
}

function CardHasAltArt($cardID)
{
  switch ($cardID) {

  default:
      return false;
  }
}

function IsIyslander($character)
{
  switch($character) {
    default: return false;
  }
}

function WardAmount($cardID)
{
  switch($cardID)
  {
    default: return 0;
  }
}

function HasWard($cardID)
{
  switch ($cardID) {

    default: return false;
  }
}

//Used to correct inconsistencies from the data set
function DefinedCardType2Wrapper($cardID)
{
  switch($cardID)
  {
    case "1480894253"://Kylo Ren
    case "2503039837"://Moff Gideon
    case "3045538805"://Hondo Ohnaka
    case "2526288781"://Bossk
    case "7424360283"://Bo-Katan Kryze
    case "5440730550"://Lando Calrissian
    case "1384530409"://Cad Bane
    case "2432897157"://Qi'Ra
    case "4352150438"://Rey
    case "9005139831"://The Mandalorian
    case "0911874487"://Fennec Shand
    case "9794215464"://Gar Saxon
    case "9334480612"://Boba Fett (Daimyo)
    case "0254929700"://Doctor Aphra
    case "9226435975"://Han Solo Red
    case "0622803599"://Jabba the Hutt
    case "9596662994"://Finn
    case "8777351722"://Anakin Skywalker
    case "4179470615"://Asajj Ventress
      return "";
    case "8752877738"://Shield Token
    case "2007868442"://Experience Token
      return "Upgrade";
    default: return DefinedCardType2($cardID);
  }
}
