<?php

//0 - Card ID
//1 - Status (2=ready, 1=unavailable, 0=destroyed)
//2 - Epic Action Used?
//3 - Unique ID
//4 - The Force is With You (1 = yes, 0 = no)
//5 - Num Uses
//6 - (free)
//7 - (free)
//8 - (free)
//9 - (free)
//10 - Counters (damage/healing counters)
class Character {
  // property declaration
  private $characters = [];
  private $playerID;
  private $index;

  public function __construct($mzIndexOrUniqueID, $player = "") {
    global $currentPlayer;

    if (str_contains($mzIndexOrUniqueID, "BASE")) {
      $this->index = 0;
      $player = $mzIndexOrUniqueID[1];
    } else if (str_contains($mzIndexOrUniqueID, "LEADER")) {
      $this->index = CharacterPieces();
      $player = $mzIndexOrUniqueID[1];
    } else {
      $mzArr = explode("-", $mzIndexOrUniqueID);
      $player = $player == "" ? $currentPlayer : $player;
      $player = $mzArr[0] == "MYCHAR" ? $player : ($player == 1 ? 2 : 1); // Unlike the Ally class, Character doesn't ignore the mzIndex's prefix

      if ($mzArr[1] == "") {
        for($i=0; $i<CharacterPieces(); ++$i) $this->characters[] = 9999;
        $this->index = -1;
      } else {
        $this->index = intval($mzArr[1]);
      }
    }

    $this->playerID = $player;
    $this->characters = &GetPlayerCharacter($player);
  }

  // Returns the unique ID of the character
  // P<playerID>BASE for base character
  // P<playerID>LEADER for leader character
  public function UniqueId() {
    return $this->characters[$this->index + 3];
  }

  public function CardId() {
    return $this->characters[$this->index];
  }

  public function Status() {
    return $this->characters[$this->index + 1];
  }

  public function ForceToken() {
    return $this->characters[$this->index + 4];
  }

  public function Counters() {
    return $this->characters[$this->index + 10];
  }

  public function SetCounters($amount) {
    $this->characters[$this->index + 10] = $amount;
  }

  public function IncreaseCounters() {
    $this->characters[$this->index + 10]++;
  }

  public function DecreaseCounters() {
    $this->characters[$this->index + 10]--;
  }

  public function PlayerID() {
    return $this->playerID;
  }

  public function Index() {
    return $this->index;
  }

  public function MZIndex() {
    global $currentPlayer;
    return ($currentPlayer == $this->playerID ? "MYCHAR-" : "THEIRCHAR-") . $this->index;
  }

  public function Exists() {
    return $this->index != -1;
  }

  public function IsReady() {
    return $this->Exists() && $this->characters[$this->index + 1] == 2;
  }
}
//FAB
// function CharacterCounters ($cardID)
// {
//   switch($cardID) {
//     case "DYN492a": return 8;
//     default: return 0;
//   }
// }

function CharacterTakeDamageAbility($player, $index, $damage, $preventable)
{
  // This code is commented out because it is not currently used
  // $char = &GetPlayerCharacter($player);
  // $otherPlayer = $player == 1 ? 1 : 2;
  // $type = "-";//Add this if it ever matters
  // switch ($char[$index]) {

  //   default:
  //     break;
  // }
  // if ($remove == 1) {
  //   DestroyCharacter($player, $index);
  // }
  if ($damage <= 0) $damage = 0;
  return $damage;
}

function CharacterStartRegroupPhaseAbilities($player) {
  // To function correctly, use uniqueID instead of MZIndex
  $character = &GetPlayerCharacter($player);

  for ($i = 0; $i < count($character); $i += CharacterPieces()) {
    if ($character[$i + 1] == 0 || $character[$i + 1] == 1) continue; //Do not process ability if it is destroyed
    switch($character[$i]) {
      case "0254929700"://Doctor Aphra
        Mill($player, 1);
        break;
      case "7204128611"://Vergence Temple
        //If you control a unit with 4 or more remaining HP, the Force is with you
        if(SearchCount(SearchAllies($player, minHealth:4)) > 0)
          TheForceIsWithYou($player);
        break;
      default:
        break;
    }
  }
}

function CharacterEndRegroupPhaseAbilities($player) {
  // To function correctly, use uniqueID instead of MZIndex
}

function CharacterStartActionPhaseAbilities($player) {
  // To function correctly, use uniqueID instead of MZIndex
  $character = &GetPlayerCharacter($player);

  for($i = 0; $i < count($character); $i += CharacterPieces()) {
    if($character[$i + 1] == 0 || $character[$i + 1] == 1) continue; //Do not process ability if it is destroyed/exhausted
    switch($character[$i]) {
      case "1951911851"://Grand Admiral Thrawn
        AddDecisionQueue("PASSPARAMETER", $player, "MYDECK-0");
        AddDecisionQueue("MZOP", $player, "GETCARDID");
        AddDecisionQueue("SETDQVAR", $player, "0");
        AddDecisionQueue("PASSPARAMETER", $player, "THEIRDECK-0");
        AddDecisionQueue("MZOP", $player, "GETCARDID");
        AddDecisionQueue("SETDQVAR", $player, "1");
        AddDecisionQueue("SETDQCONTEXT", $player, "The top of your deck is <0> and the top of their deck is <1>.");
        AddDecisionQueue("OK", $player, "-");
        break;
      default:
        break;
    }
  }
}

function CharacterEndActionPhaseAbilities($player) {
  // To function correctly, use uniqueID instead of MZIndex
}

function DefCharacterStartTurnAbilities()
{
  global $defPlayer, $mainPlayer;
  $character = &GetPlayerCharacter($defPlayer);
  for($i = 0; $i < count($character); $i += CharacterPieces()) {
    if($character[$i + 1] == 0 || $character[$i + 1] == 1) continue; //Do not process ability if it is destroyed
    switch($character[$i]) {

      default:
        break;
    }
  }
}

function CharacterStaticHealthModifiers($cardID, $index, $player)
{
  $modifier = 0;
  $char = &GetPlayerCharacter($player);
  for($i=0; $i<count($char); $i+=CharacterPieces()) {
    switch($char[$i])
    {
      default: break;
    }
  }
  return $modifier;
}

function CharacterDestroyEffect($cardID, $player)
{
  switch($cardID) {

    default:
      break;
  }
}

function ResetCharacter($player) {
  $char = &GetPlayerCharacter($player);
  for ($i = 0; $i < count($char); $i += CharacterPieces()) {
    if ($char[$i+7] == 1) $char[$i+1] = 0; //Destroy if it was flagged for destruction
    if ($char[$i] != "9434212852") {//Mystic Monastery
      if ($char[$i+1] != 0) {
        $char[$i+1] = 2;//status (dimmed when exhausted)
      }
      $char[$i+5] = CharacterNumUsesPerTurn($char[$i]);//num uses for leaders
    }
    $char[$i+10] = 0;//damage/healing counters for bases
  }
}

// function MainCharacterHitAbilities()//FAB
// {
//   global $combatChain, $combatChainState, $CCS_WeaponIndex, $mainPlayer;
//   $attackID = $combatChain[0];
//   $mainCharacter = &GetPlayerCharacter($mainPlayer);

//   // This code is commented out because it is not currently used
//   // for($i = 0; $i < count($mainCharacter); $i += CharacterPieces()) {
//   //   switch($characterID) {

//   //     default:
//   //       break;
//   //   }
//   // }
// }

// function MainCharacterAttackModifiers($index = -1, $onlyBuffs = false)//FAB
// {
//   global $combatChainState, $CCS_WeaponIndex, $mainPlayer, $CS_NumAttacks, $combatChain;
//   $modifier = 0;
//   $mainCharacterEffects = &GetMainCharacterEffects($mainPlayer);
//   $mainCharacter = &GetPlayerCharacter($mainPlayer);
//   if($index == -1) $index = $combatChainState[$CCS_WeaponIndex];
//   for($i = 0; $i < count($mainCharacterEffects); $i += CharacterEffectPieces()) {
//     if($mainCharacterEffects[$i] == $index) {
//       switch($mainCharacterEffects[$i + 1]) {
//         case "QQaOgurnjX": $modifier += 2; break;//Imbue in Frost
//         case "usb5FgKvZX": $modifier += 1; break;//Sharpening Stone
//         case "CgyJxpEgzk": $modifier += 3; break;//Spirit Blade: Infusion
//         default:
//           break;
//       }
//     }
//   }
//   if($onlyBuffs) return $modifier;

//   $mainCharacter = &GetPlayerCharacter($mainPlayer);
//   for($i = 0; $i < count($mainCharacter); $i += CharacterPieces()) {
//     switch($mainCharacter[$i]) {
//       //case "NfbZ0nouSQ": if(!IsAlly($combatChain[0])) $modifier += SearchCount(SearchBanish($mainPlayer,type:"WEAPON")); break;
//       default: break;
//     }
//   }
//   return $modifier;
// }

// function MainCharacterHitEffects()//FAB
// {
//   global $combatChainState, $CCS_WeaponIndex, $mainPlayer;
//   $modifier = 0;
//   $mainCharacterEffects = &GetMainCharacterEffects($mainPlayer);
//   for($i = 0; $i < count($mainCharacterEffects); $i += 2) {
//     if($mainCharacterEffects[$i] == $combatChainState[$CCS_WeaponIndex]) {
//       switch($mainCharacterEffects[$i + 1]) {
//         case "CgyJxpEgzk"://Spirit Blade: Infusion
//           Draw($mainPlayer);
//           break;
//         default: break;
//       }
//     }
//   }
//   return $modifier;
// }

// function MainCharacterGrantsGoAgain()
// {
//   global $combatChainState, $CCS_WeaponIndex, $mainPlayer;
//   if($combatChainState[$CCS_WeaponIndex] == -1) return false;
//   $mainCharacterEffects = &GetMainCharacterEffects($mainPlayer);
//   for($i = 0; $i < count($mainCharacterEffects); $i += 2) {
//     if($mainCharacterEffects[$i] == $combatChainState[$CCS_WeaponIndex]) {
//       switch($mainCharacterEffects[$i + 1]) {

//         default: break;
//       }
//     }
//   }
//   return false;
// }

// function CharacterCostModifier($cardID, $from)
// {
//   global $currentPlayer, $CS_NumSwordAttacks;
//   $modifier = 0;
//   if(CardSubtype($cardID) == "Sword" && GetClassState($currentPlayer, $CS_NumSwordAttacks) == 1 && SearchCharacterActive($currentPlayer, "CRU077")) {
//     --$modifier;
//   }
//   return $modifier;
// }

// function ShiyanaCharacter($cardID, $player="")
// {
//   global $currentPlayer;
//   if($player == "") $player = $currentPlayer;
//   if($cardID == "CRU097") {
//     $otherPlayer = ($player == 1 ? 2 : 1);
//     $otherCharacter = &GetPlayerCharacter($otherPlayer);
//     if(SearchCurrentTurnEffects($otherCharacter[0] . "-SHIYANA", $player)) $cardID = $otherCharacter[0];
//   }
//   return $cardID;
// }

function EquipPayAdditionalCosts($cardIndex, $from)
{
  global $currentPlayer;
  if($cardIndex == -1) return;//TODO: Add error handling
  $character = &GetPlayerCharacter($currentPlayer);
  $cardID = $character[$cardIndex];
  switch($cardID) {
    case "1393827469"://Tarkintown
    case "2569134232"://Jedha City
    case "2429341052"://Security Complex
    case "8327910265"://Energy Conversion Lab (ECL)
      $character[$cardIndex+1] = 0;
      break;
    case "2699176260"://Tomb of Eilram
      break;
    default:
      --$character[$cardIndex+5];
      if($character[$cardIndex+5] == 0) $character[$cardIndex+1] = 1; //By default, if it's used, set it to used
      break;
  }
}

function CharacterTriggerInGraveyard($cardID)
{
  switch($cardID) {
    default: return false;
  }
}

function CharacterHasWhenPlayCardAbility($player, $characterIndex, $playedCardID, $playedFrom): bool {
  global $currentPlayer;
  $otherPlayer = ($player == 1 ? 2 : 1);
  $character = new Character("MYCHAR-" . $characterIndex, $player);
  if(LeaderAbilitiesIgnored()) return false;

  // When you play a card
  if ($player == $currentPlayer) {
    switch($character->CardID()) {
      case "3045538805"://Hondo Ohnaka
        return $character->IsReady() && $playedFrom == "RESOURCES";
      case "1384530409"://Cad Bane
        return $character->IsReady() && TraitContains($playedCardID, "Underworld", $player) && SearchCount(SearchAllies($otherPlayer)) > 0;
      case "2358113881"://Quinlan Vos
        if ($character->IsReady() && DefinedTypesContains($playedCardID, "Unit", $player) && !PilotWasPlayed($player, $playedCardID)) {
          $cardCost = CardCost($playedCardID);
          $theirAllies = &GetTheirAllies($player);

          for ($j = 0; $j < count($theirAllies); $j += AllyPieces()) {
            if (CardCost($theirAllies[$j]) == $cardCost) {
              return true;
            }
          }
        }
        return false;
      case "9005139831"://The Mandalorian Leader
        return $character->IsReady() && (DefinedTypesContains($playedCardID, "Upgrade", $player) || PilotWasPlayed($player, $playedCardID));
      case "9334480612"://Boba Fett (Daimyo)
        return $character->IsReady()
          && !LayersHasWhenPlayCardTrigger("9334480612", $player)
          && DefinedTypesContains($playedCardID, "Unit", $player)
          && !PilotWasPlayed($player, $playedCardID)
          && (HasKeyword($playedCardID, "Any", $player)
            || HasKeywordWhenPlayed($playedCardID));
      default:
        break;
    }
  } else { // When an opponent plays a card
  }

  return false;
}

function CharacterPlayCardAbility($player, $cardID, $uniqueID, $numUses, $playedCardID, $playedFrom, $playedUniqueID) {
  global $currentPlayer;
  $otherPlayer = $player == 1 ? 2 : 1;
  $character = new Character($uniqueID, $player);

  // When you play a card
  if ($player == $currentPlayer) {
    switch($cardID) {
      case "3045538805"://Hondo Ohnaka Leader
        if ($character->IsReady()) {
          AddDecisionQueue("MULTIZONEINDICES", $player, "MYALLY&THEIRALLY");
          AddDecisionQueue("SETDQCONTEXT", $player, "Choose a unit to give an experience token", 1);
          AddDecisionQueue("MAYCHOOSEMULTIZONE", $player, "<-", 1);
          AddDecisionQueue("MZOP", $player, "ADDEXPERIENCE", 1);
          AddDecisionQueue("EXHAUSTCHARACTER", $player, $character->Index(), 1);
        }
        break;
      case "1384530409"://Cad Bane Leader ability
        if ($character->IsReady() && SearchCount(SearchAllies($otherPlayer)) > 0) {
          AddDecisionQueue("YESNO", $player, "if you want use Cad Bane's ability");
          AddDecisionQueue("NOPASS", $player, "-");
          AddDecisionQueue("EXHAUSTCHARACTER", $player, $character->Index(), 1);
          AddDecisionQueue("MULTIZONEINDICES", $otherPlayer, "MYALLY", 1);
          AddDecisionQueue("SETDQCONTEXT", $otherPlayer, "Choose a unit to deal 1 damage to", 1);
          AddDecisionQueue("CHOOSEMULTIZONE", $otherPlayer, "<-", 1);
          AddDecisionQueue("MZOP", $otherPlayer, DealDamageBuilder(1, $player), 1);
        }
        break;
      case "2358113881"://Quinlan Vos
        if ($character->IsReady()) {
          $cost = CardCost($playedCardID);
          AddDecisionQueue("MULTIZONEINDICES", $player, "THEIRALLY:minCost=" . $cost . ";maxCost=" . $cost);
          AddDecisionQueue("SETDQCONTEXT", $player, "Choose a unit to deal 1 damage", 1);
          AddDecisionQueue("MAYCHOOSEMULTIZONE", $player, "<-", 1);
          AddDecisionQueue("MZOP", $player, DealDamageBuilder(1, $player), 1);
          AddDecisionQueue("EXHAUSTCHARACTER", $player, $character->Index(), 1);
        }
        break;
      case "9005139831"://Mandalorian Leader Ability
        if ($character->IsReady()) {
          AddDecisionQueue("MULTIZONEINDICES", $player, "THEIRALLY:maxHealth=4");
          AddDecisionQueue("MZFILTER", $player, "status=1", 1);
          AddDecisionQueue("SETDQCONTEXT", $player, "Choose a unit to exhaust", 1);
          AddDecisionQueue("MAYCHOOSEMULTIZONE", $player, "<-", 1);
          AddDecisionQueue("MZOP", $player, "REST", 1);
          AddDecisionQueue("EXHAUSTCHARACTER", $player, $character->Index(), 1);
        }
        break;
      case "9334480612"://Boba Fett (Daimyo)
        if ($character->IsReady()) {
          AddDecisionQueue("MULTIZONEINDICES", $player, "MYALLY");
          AddDecisionQueue("SETDQCONTEXT", $player, "Choose a card to give +1 power");
          AddDecisionQueue("MAYCHOOSEMULTIZONE", $player, "<-", 1);
          AddDecisionQueue("MZOP", $player, "GETUNIQUEID", 1);
          AddDecisionQueue("ADDLIMITEDCURRENTEFFECT", $player, "9334480612,HAND", 1);
          AddDecisionQueue("EXHAUSTCHARACTER", $player, $character->Index(), 1);
        }
        break;
      default:
        break;
    }
  } else { // When an oponent plays a card
  }
}

function AllyDealDamageAbilities($player, $damage, $type) {
  global $currentTurnEffects;
  $allies = &GetAllies($player);
  for($i = count($allies) - AllyPieces(); $i >= 0; $i -= AllyPieces()) {
    switch($allies[$i]) {
      case "3c60596a7a"://Cassian Andor Leader Unit
        $ally = new Ally("MYALLY-" . $i, $player);
        if ($ally->NumUses() > 0) {
          AddDecisionQueue("SETDQCONTEXT", $player, "Choose if you want to draw a card (Cassian's ability)");
          AddDecisionQueue("YESNO", $player, "-");
          AddDecisionQueue("NOPASS", $player, "-");
          AddDecisionQueue("PASSPARAMETER", $player, "MYALLY-" . $i, 1);
          AddDecisionQueue("ADDMZUSES", $player, "-1", 1);
          AddDecisionQueue("DRAW", $player, "-", 1);
        }
        break;
    }
  }

  //currentt turn effects from allies
  for($i=0;$i<count($currentTurnEffects);$i+=CurrentTurnPieces()) {
    switch($currentTurnEffects[$i]) {
      case "8734471238"://Stay On Target
        Draw($currentTurnEffects[$i+1]);
        break;
      case "6228218834"://Tactical Heavy Bomber
        if($type != "COMBAT") Draw($currentTurnEffects[$i+1]); // TODO: should check for indirect damage only
        break;
      case "2711104544"://Guerilla Soldier
        if($type != "COMBAT") {  // TODO: should check for indirect damage only
          $ally = new Ally($currentTurnEffects[$i+2]);
          $ally->Ready();
        }
        break;
      default: break;
      }
    }
}
?>
