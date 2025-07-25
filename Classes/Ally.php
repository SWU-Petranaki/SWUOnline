<?php
// Ally Class to handle interactions involving allies

class Ally {

  // Properties
  private $allies = [];
  private $playerID;
  private $index;

  // Constructor
  function __construct($MZIndexOrUniqueID, $player="") {
    global $currentPlayer;
    $mzArr = explode("-", $MZIndexOrUniqueID);

    if ($mzArr[0] != "MYALLY" && $mzArr[0] != "THEIRALLY") {
      $mzArr = ["MYALLY", ""]; // Default non-existent ally
      $initialPlayer = ($player == 1 || $player == 2) ? $player : $currentPlayer;
      $players = [$initialPlayer, ($initialPlayer % 2) + 1];
      foreach ($players as $p) {
        $index = SearchAlliesForUniqueID($MZIndexOrUniqueID, $p);
        if ($index > -1) {
          $mzArr = ["MYALLY", $index];
          $player = $p;
          break;
        }
      }
    }

    if ($player == "") {
      $otherPlayer = $currentPlayer == 1 ? 2 : 1;
      $player = $mzArr[0] == "MYALLY" ? $currentPlayer : $otherPlayer;
    }

    if ($mzArr[1] == "") {
      for($i=0; $i<AllyPieces(); ++$i) $this->allies[] = 9999;
      $this->index = -1;
    } else {
      $this->index = intval($mzArr[1]);
      $this->allies = &GetAllies($player);
    }

    $this->playerID = $player;
  }
  //static functional constructors
  public static function FromMyIndex($index, $player) { return new Ally("MYALLY-" . $index, $player); }
  public static function FromTheirIndex($index, $player) { return new Ally("THEIRALLY-" . $index, $player); }
  public static function FromUniqueId($uniqueId) { return new Ally($uniqueId); }

  // Methods
  function MZIndex() {
    global $currentPlayer;
    return ($currentPlayer == $this->Controller() ? "MYALLY-" : "THEIRALLY-") . $this->index;
  }

  function CardID() {
    if($this->index == -1) return "";
    return $this->allies[$this->index];
  }

  function UniqueID() {
    return $this->allies[$this->index+5];
  }

  function Exists() {
    return $this->index > -1;
  }

  //Controller
  function PlayerID() {
    return $this->playerID;
  }

  function Index() {
    return $this->index;
  }

  function ResetCounters() {
    $this->allies[$this->index+6] = 0;
  }

  function Counters() {
    return $this->allies[$this->index+6];
  }

  function IncreaseCounters() {
    $this->allies[$this->index+6]++;
  }

  function DecreaseCounters() {
    $this->allies[$this->index+6]--;
  }

  function SetCounters($amount) {
    $this->allies[$this->index+6] = $amount;
  }

  function Damage() {
    return $this->allies[$this->index+2];
  }

  function AddDamage($amount) {
    $this->allies[$this->index+2] += $amount;
  }

  function RemoveDamage($amount) {
    if($this->allies[$this->index+2] > 0) $this->allies[$this->index+2] -= $amount;
  }

  function Owner() {
    return $this->allies[$this->index+11];
  }

  function SetOwner($owner) {
    $this->allies[$this->index+11] = $owner;
  }

  function Controller() {
    return $this->playerID;
  }

  function TurnsInPlay() {
    return $this->allies[$this->index+12];
  }

  function Heal($amount) {
    $healed = $amount;
    if($amount > $this->Damage()) {
      $healed = $this->Damage();
      $this->allies[$this->index+2] = 0;
    } else {
      $this->allies[$this->index+2] -= $amount;
    }
    $this->allies[$this->index+14] = 1;//Track that the ally was healed this round
    AddEvent("RESTORE", $this->UniqueID() . "!" . $healed);
    //ally healed side effects
    switch($this->CardID()) {
      case "8352777268"://Silver Angel
        if($healed > 0) {
          $player = $this->Controller();
          AddDecisionQueue("MULTIZONEINDICES", $player, "MYALLY:arena=Space&THEIRALLY:arena=Space");
          AddDecisionQueue("SETDQCONTEXT", $player, "Choose a unit to deal 1 damage to");
          AddDecisionQueue("MAYCHOOSEMULTIZONE", $player, "<-", 1);
          AddDecisionQueue("MZOP", $player, DealDamageBuilder(1, $player, isUnitEffect:1, unitCardID:$this->CardID()),1);
        }
        break;
      default: break;
    }
    return $healed;
  }

  function MaxHealth() {
    $max = SpecificCardHP("MYALLY-" . $this->index, $this->PlayerID());
    $upgrades = $this->GetUpgrades();

    // Upgrades buffs
    for($i=0; $i<count($upgrades); ++$i) {
      if ($upgrades[$i] != "-") {
        $max += CardUpgradeHPDictionary($upgrades[$i]);
      }

      switch ($upgrades[$i]) {
        case "3292172753"://Squad Support
          $max += SearchCount(SearchAlliesUniqueIDForTrait($this->Controller(), "Trooper"));
          break;
        case "2633842896"://Biggs Darklighter
          $max += TraitContains($this->CardID(), "Transport", $this->Controller()) ? 1 : 0;
          break;

        default:
          break;
      }
    }

    $max += $this->allies[$this->index+9];
    for($i=count($this->allies)-AllyPieces(); $i>=0; $i-=AllyPieces()) {
      if(AllyHasStaticHealthModifier($this->allies[$i])) {
        $max += AllyStaticHealthModifier($this->CardID(), $this->Index(), $this->PlayerID(), $this->allies[$i], $i, $this->PlayerID());
      }
    }
    $otherPlayer = $this->PlayerID() == 1 ? 2 : 1;
    $theirAllies = &GetAllies($otherPlayer);
    for($i=0; $i<count($theirAllies); $i+=AllyPieces()) {
      if(AllyHasStaticHealthModifier($theirAllies[$i])) {
        $max += AllyStaticHealthModifier($this->CardID(), $this->Index(), $this->PlayerID(), $theirAllies[$i], $i, $otherPlayer);
      }
    }
    $max += CharacterStaticHealthModifiers($this->CardID(), $this->Index(), $this->PlayerID());
    $max += NameBasedHealthModifiers($this->CardID(), $this->Index(), $this->PlayerID());
    $max += BaseHealthModifiers($this->CardID(), $this->Index(), $this->PlayerID());
    return $max;
  }

  function Health() {
    return $this->MaxHealth() - $this->Damage();
  }

  //Returns true if the ally is destroyed
  function DefeatIfNoRemainingHP() {
    if (!$this->Exists()) return true;
    if ($this->Health() <= 0
        && ($this->CardID() != "d1a7b76ae7" || $this->LostAbilities() || $this->HasEffect("d1a7b76ae7"))//Chirrut Imwe Leader
        && ($this->CardID() != "6032641503" || $this->LostAbilities())//L3-37 JTL
        && (!$this->HasEffect("9242267986"))//The Tragedy of Darth Plagueis
        && ($this->CardID() != "0345124206")) {  //Clone - Ensure that Clone remains in play while resolving its ability
      DestroyAlly($this->playerID, $this->index);
      return true;
    }
    return false;
  }

  function IsDamaged() {
    return $this->Damage() > 0;
  }

  function IsUnique() {
    return CardIsUnique($this->CardID());
  }

  function CanAddPilot() {
    $maxPilots = 1;
    $currentPilots = 0;
    $maxPilots += match ($this->CardID()) {
      "8845408332" => 1,//Millennium Falcon (Get Out and Push)
      default => 0
    };

    $subcards = $this->GetUpgrades(withMetadata:true);
    for($i=0; $i<count($subcards); $i+=SubcardPieces()) {
      $maxPilots += match ($subcards[$i]) {
        "5375722883" => 1,//R2-D2 (Artooooooooo!)
        default => 0
      };
      $currentPilots += ($subcards[$i+2] == "1"
          && $subcards[$i] != "5306772000") //Phantom II exception (added as Pilot but not really a Pilot)
        ? 1 : 0;
    }

    return $currentPilots < $maxPilots;
  }

  function HasPilot() {
    $subcards = $this->GetUpgrades(withMetadata:true);
    for($i=0; $i<count($subcards); $i+=SubcardPieces()) {
      if($subcards[$i+2] == "1"
          && $subcards[$i] != "5306772000")//Phantom II exception (added as Pilot but not really a Pilot)
        return true;
    }
    return false;
  }

  function ReceivingPilot($cardID, $player = "") {
    global $CS_PlayedAsUpgrade;
    if($player == "") $player = $this->PlayerID();
    $isLeaderPilot = $cardID == "3eb545eb4b" //Poe Dameron JTL leader
      || (CardIDIsLeader($cardID) && LeaderCanPilot(LeaderUndeployed($cardID)));

    return $isLeaderPilot || PilotingCost($cardID) >= 0 && GetClassState($player, $CS_PlayedAsUpgrade) == "1";
  }

  function From() {
    return $this->allies[$this->index+16];
  }

  function FromEpicAction() {
    return $this->allies[$this->index+16] == "EPICACTION";
  }

  function IsExhausted() {
    return $this->allies[$this->index+1] == 1;
  }

  function CantAttack() {
    global $currentTurnEffects, $combatChainState, $CCS_CantAttackBase;

    switch($this->CardID()) {
      case "4332645242": //Corporate Defense Shuttle
      case "7504035101": //Loth-Wolf
        if(!$this->LostAbilities()) return true;
        break;
      case "2508430135": //Oggdo Bogdo
        if($this->Damage() == 0 && !$this->LostAbilities()) return true;
        break;
      default:
        break;
    }

    $canAttackBase = $combatChainState[$CCS_CantAttackBase] == 0;
    $attackTargets = GetTargetsForAttack($this, $canAttackBase);
    switch($attackTargets) {
      case "":
        return true;
      case "THEIRCHAR-0":
        if(!$canAttackBase) return true;
        break;
      default: break;
    }

    for($i = 0; $i < count($currentTurnEffects); $i += CurrentTurnPieces()) {
      if($currentTurnEffects[$i+2] != $this->UniqueID()) continue;
      switch($currentTurnEffects[$i]) {
        case "3381931079"://Malevolence
          return true;
        default: break;
      }
    }

    return false;
  }

  function HasShield() {
    $subcards = $this->GetSubcards();
    for($i=0; $i<count($subcards); $i+=SubcardPieces()) {
      if($subcards[$i] == "8752877738") { //Shield Token
        return true;
      }
    }
    return false;
  }

  function HasExperience() {
    $subcards = $this->GetSubcards();
    for($i=0; $i<count($subcards); $i+=SubcardPieces()) {
      if($subcards[$i] == "2007868442") { //Experience token
        return true;
      }
    }
    return false;
  }

  function HasTitle($cardTitle) {
    $spacesTitle = str_replace("_", " ", $cardTitle);
    return CardTitle($this->CardID()) == $spacesTitle;
  }

  function WasHealed() {
    return $this->allies[$this->index+14] == 1;
  }

  function Destroy($enemyEffects = true) {
    if($this->index == -1) return "";
    if($enemyEffects && $this->AvoidsDestroyByEnemyEffects()) {
      WriteLog(CardLink($this->CardID(), $this->CardID()) . " cannot be defeated by enemy card effects.");
      return "";
    }
    return DestroyAlly($this->playerID, $this->index);
  }

  //Returns true if the ally is destroyed
  function DealDamage($amount, $bypassShield = false, $fromCombat = false, &$damageDealt = NULL,
      $enemyDamage = false, $fromUnitEffect=false, $preventable=true, $unitCardID = "")
  {
    global $currentTurnEffects, $CS_PlayIndex;
    if($this->index == -1 || $amount <= 0) return false;
    if(!$preventable) $bypassShield = true;
    global $mainPlayer;
    if(!$fromCombat && $preventable && $this->AvoidsDamage($enemyDamage)) return;
    if($fromCombat && !$this->LostAbilities()) {
      if($this->CardID() == "6190335038" && $this->PlayerID() == $mainPlayer && $this->HasEffect("6190335038")) return false;//Aayla Secura
    }

    if(!$enemyDamage && SearchCount(SearchAlliesForCard($this->Controller(),"4945479132")) //Malakili
        && TraitContains($unitCardID, "Creature", $this->Controller())) {
      $amount = 0;
    }

    if($amount > 0 && $this->HasEffect("7981459508")) {//Shien Flurry
      $amount -= 2;
      if($amount < 0) $amount = 0;
      AddDecisionQueue("REMOVECURRENTEFFECT", $this->Controller(), "7981459508");
    }

    //Upgrade damage prevention
      if($preventable) {
      $subcards = $this->GetSubcards();
      for ($i = count($subcards) - SubcardPieces(); $i >= 0; $i -= SubcardPieces()) {
        if($subcards[$i] == "8752877738") {//Shield Token
          for ($j = SubcardPieces() - 1; $j >= 0; $j--) {
            unset($subcards[$i+$j]);
          }
          $subcards = array_values($subcards);
          $this->allies[$this->index+4] = count($subcards) > 0 ? implode(",", $subcards) : "-";
          if(!$bypassShield) return false;//Cancel the damage if shield prevented it
        }
        switch($subcards[$i]) {
          case "5738033724"://Boba Fett's Armor
            if(CardTitle($this->CardID()) == "Boba Fett") $amount -= 2;
            if($amount < 0) $amount = 0;
            break;
          default: break;
        }
      }
      //Current effect damage prevention
      for($i=0; $i<count($currentTurnEffects); $i+=CurrentTurnEffectPieces()) {
        if($currentTurnEffects[$i+1] != $this->PlayerID()) continue;
        if($currentTurnEffects[$i+2] != -1 && $currentTurnEffects[$i+2] != $this->UniqueID()) continue;
        switch($currentTurnEffects[$i]) {
          case "7244268162"://Finn
            $amount -= 1;
            if($amount < 0) $amount = 0;
            break;
          default: break;
        }
      }
    }
    //Unit damage redirection (NOT prevention!)
    switch($this->CardID()) {
      case "8862896760"://Maul
        $preventUniqueID = SearchLimitedCurrentTurnEffects("8862896760", $this->PlayerID(), remove:true);
        if($preventUniqueID != -1) {
          $preventIndex = SearchAlliesForUniqueID($preventUniqueID, $this->PlayerID());
          if($preventIndex > -1) {
            $preventAlly = new Ally("MYALLY-" . $preventIndex, $this->PlayerID());
            $preventAlly->DealDamage($amount, $bypassShield, $fromCombat, $damageDealt);
            return false;
          }
        }
        break;
      default: break;
    }
    if($damageDealt != NULL) $damageDealt = $amount;
    $this->AddDamage($amount);
    AddEvent("DAMAGE", $this->UniqueID() . "!" . $amount);

    CheckBobaFettJTL($this->PlayerID(), $enemyDamage, $fromCombat);

    if($this->Health() <= 0 &&
      ($this->CardID() != "d1a7b76ae7" || $this->LostAbilities() || $this->HasEffect("d1a7b76ae7"))//Chirrut Imwe
        && !$this->HasEffect("9242267986") //The Tragedy of Darth Plagueis
        && (!AllyIsMultiAttacker($this->CardID()) || !IsMultiTargetAttackActive())) {
      DestroyAlly($this->playerID, $this->index, fromCombat:$fromCombat);
      return true;
    }
    AllyDamageTakenAbilities($this->playerID, $this->index, $amount, $fromCombat, $enemyDamage, $fromUnitEffect, $preventable);
    switch($this->CardID())
    {
      case "4843225228"://Phase-III Dark Trooper
        if($fromCombat) $this->Attach("2007868442");//Experience token
        break;
      default: break;
    }
    return false;
  }

  function AddRoundHealthModifier($amount) {
    if($this->index == -1) return;
    $this->allies[$this->index+9] += $amount;
    $this->DefeatIfNoRemainingHP();
  }

  function MoveArena($arena) {
    if($this->index == -1) return;
    $this->allies[$this->index+15] = $arena;
  }

  function ArenaOverride() {
    return $this->allies[$this->index+15];
  }

  function CurrentArena() {
    if($this->ArenaOverride() != "NA") return $this->ArenaOverride();
    return CardArenas($this->CardID());
  }

  function NumAttacks() {
    return $this->allies[$this->index+10];
  }

  function IncrementTimesAttacked() {
    ++$this->allies[$this->index+10];
  }

  function CurrentPower($reportMode = false) {
    global $currentTurnEffects, $combatChain;
    $power = ((int) (SpecificCardPower("MYALLY-" . $this->index, $this->playerID))) + ((int) $this->allies[$this->index+7]);
    $power += AttackModifier($this->CardID(), $this->playerID, $this->index, $reportMode);
    $upgrades = $this->GetUpgrades();
    $otherPlayer = $this->playerID == 1 ? 2 : 1;
    // Grit buff
    if(HasGrit($this->CardID(), $this->playerID, $this->index)) {
      $damage = $this->Damage();
      if($damage > 0) $power += $damage;
    }

    // Upgrades buffs
    for ($i=0; $i<count($upgrades); ++$i) {
      if ($upgrades[$i] != "-") {
        $power += CardUpgradePower($upgrades[$i]);
      }

      switch ($upgrades[$i]) {
        case "3292172753"://Squad Support
          $power += SearchCount(SearchAlliesUniqueIDForTrait($this->Controller(), "Trooper"));
          break;
        //Jump to Lightspeed
        case "1463418669"://IG-88
          //end workaround
          $power += SearchCount(SearchAllies($otherPlayer, damagedOnly:true)) > 0 ? 3 : 0;
          break;
        case "6610553087"://Nien Nunb
          $power += CountPilotUnitsAndPilotUpgrades($this->PlayerID(), other: true);
          break;
        case "81a416eb1f":
          $power += TraitContains($this->CardID(), "Transport", $this->Controller()) ? 1 : 0;
        default:
          break;
      }
    }

    // Friendly ally buffs
    $allies = &GetAllies($this->playerID);
    for ($i = 0; $i < count($allies); $i += AllyPieces()) {
      $ally = new Ally("MYALLY-" . $i, $this->playerID);
      if ($ally->LostAbilities()) continue;

      switch($allies[$i]) {
        case "6097248635"://4-LOM
          if(CardTitle($this->CardID()) == "Zuckuss") $power += 1;
          break;
        case "1690726274"://Zuckuss
          if(CardTitle($this->CardID()) == "4-LOM") $power += 1;
          break;
        case "e2c6231b35"://Director Krennic Leader Unit
          if($this->IsDamaged() && !LeaderAbilitiesIgnored()) $power += 1;
          break;
        case "1557302740"://General Veers
          if($i != $this->index && TraitContains($this->CardID(), "Imperial", $this->PlayerID())) $power += 1;
          break;
        case "9799982630"://General Dodonna
          if($i != $this->index && TraitContains($this->CardID(), "Rebel", $this->PlayerID())) $power += 1;
          break;
        case "3666212779"://Captain Tarkin
        case "4339330745"://Wedge Antilles
          if(TraitContains($this->CardID(), "Vehicle", $this->PlayerID())) $power += 1;
          break;
        case "4484318969"://Moff Gideon Leader Unit
          global $mainPlayer;
          //As defined on NetworkingLibraries.Attack, $mainPlayer is always the attacker
          if(CardCost($this->CardID()) <= 3 && $mainPlayer == $this->playerID && AttackIndex() == $this->index && IsAllyAttackTarget()) {
            $power += 1;
          }
          break;
        case "3feee05e13"://Gar Saxon Leader Unit
          if($this->IsUpgraded() && !LeaderAbilitiesIgnored()) $power += 1;
          break;
        case "919facb76d"://Boba Fett Green Leader
          if($i != $this->index && HasKeyword($this->CardID(), "Any", $this->playerID, $this->index)) $power += 1;
          break;
        case "1314547987"://Shaak Ti
          if($i != $this->index && IsToken($this->CardID())) $power += 1;
          break;
        case "9017877021"://Clone Commander Cody
          if($i != $this->index && IsCoordinateActive($this->playerID)) $power += 1;
          break;
        case "7924172103"://Bariss Offee
          if($this->WasHealed()) $power += 1;
          break;
        case "9811031405"://Victor Leader
          if($i != $this->index && CardArenas($this->CardID()) == "Space") $power += 1;
          break;
        case "4478482436"://Supremacy
          if($i != $this->index && TraitContains($this->CardID(), "Vehicle", $this->PlayerID())) $power += 6;
          break;
        case "5460831827"://The Son
          if (HasTheForce($this->playerID)) $power += 2;
          break;
        default: break;
      }
    }

    // Enemy ally buffs
    $theirAllies = &GetAllies($otherPlayer);
    for ($i = 0; $i < count($theirAllies); $i += AllyPieces()) {
      $ally = new Ally("MYALLY-" . $i, $otherPlayer);
      if ($ally->LostAbilities()) continue;

      switch ($theirAllies[$i]) {
        case "3731235174"://Supreme Leader Snoke
          if (!$this->IsLeader()) {
            $power -= 2;
          }
          break;
        case "3567283316"://Radiant VII
          if (!$this->IsLeader()) {
            $power -= $this->Damage();
          }
          break;
        default: break;
      }
    }

    // Leader buffs
    $myChar = &GetPlayerCharacter($this->playerID);
    for($i=0; $i<count($myChar); $i+=CharacterPieces()) {
      if (LeaderAbilitiesIgnored()) continue;

      switch($myChar[$i]) {
        case "8560666697"://Director Krennic Leader
          if ($this->IsDamaged()) $power += 1;
          break;
        case "9794215464"://Gar Saxon Leader
          if ($this->IsUpgraded()) $power += 1;
          break;
        default: break;
      }
    }

    // Current effect buffs
    for ($i = 0; $i < count($currentTurnEffects); $i += CurrentTurnEffectPieces()) {
      $effectCardID = $currentTurnEffects[$i];
      $effectPlayerID = $currentTurnEffects[$i + 1];
      $effectUniqueID = $currentTurnEffects[$i + 2];

      if ($effectPlayerID != $this->PlayerID() && $effectUniqueID != $this->UniqueID()) continue;
      if ($effectUniqueID != -1 && $effectUniqueID != $this->UniqueID()) continue;

      $power += EffectAttackModifier($effectCardID, $this->PlayerID());
    }

    if($this->HasEffect("6501780064")) {//Babu Frik
      return $this->Health();
    }

    return max($power, 0);
  }

  function Ready($resolvedSpecialCase=false) {
    $upgrades = $this->GetUpgrades();
    for($i=0; $i<count($upgrades); ++$i) {
      switch($upgrades[$i]) {
        case "7718080954"://Frozen in Carbonite
          return false;
        case "7962923506"://In Debt to Crimson Dawn
          if($this->IsExhausted() && !$resolvedSpecialCase) {
             AddDecisionQueue("SPECIFICCARD", $this->Controller(), "PAY_READY_TAX,2," . $this->UniqueID());
            return false;
          }
          break;
        default: break;
      }
    }
    if($this->CardID() == "2236831712"//Leia Organa (Extraordinary)
        && $this->CurrentArena() == "Space"
        && !$this->LostAbilities() && !$resolvedSpecialCase)
      return false;
    if($this->allies[$this->index+3] == 1) return false;
    $this->allies[$this->index+1] = 2;
    return true;
  }

  function Exhaust($enemyEffects=false) {
    if($this->index == -1) return;
    if($enemyEffects && $this->AvoidsExhaust()) {
      WriteLog(CardLink($this->CardID(), $this->CardID()) . " cannot be exhausted by enemy card abilities.");
      return false;
    }
    AddEvent("EXHAUST", $this->UniqueID());
    $this->allies[$this->index+1] = 1;

    return true;
  }

  function AddSubcard($cardID, $ownerID = null, $asPilot = false, $epicAction = false, $turnsInPlay = 0, $controllerID = null) {
    $subCardUniqueID = GetUniqueId();
    $ownerID = $ownerID ?? $this->playerID;
    $controllerID = $controllerID ?? $ownerID;
    if($this->allies[$this->index+4] == "-") $this->allies[$this->index+4] = $cardID . "," . $ownerID . "," . ($asPilot ? "1" : "0") . "," . $subCardUniqueID . "," . ($epicAction ? "1" : "0") . ",$turnsInPlay,$controllerID,0";
    else $this->allies[$this->index+4] = $this->allies[$this->index+4] . "," . $cardID . "," . $ownerID . "," . ($asPilot ? "1" : "0") . "," . $subCardUniqueID . "," . ($epicAction ? "1" : "0") . ",$turnsInPlay,$controllerID,0";

    if($asPilot) {
      AddLayer("TRIGGER", $ownerID, "UNITPLAYEDASUPGRADE", $cardID, $this->UniqueID());
    }
    return $subCardUniqueID;
  }

  function RemoveSubcard($subcardID, $subcardUniqueID = "", $moving = false, $skipDestroy = false) {
    global $CS_PlayIndex, $CS_CachedLeader1EpicAction, $CS_CachedLeader2EpicAction;
    if($this->index == -1) return false;
    $subcards = $this->GetSubcards();
    for($i=0; $i<count($subcards); $i+=SubcardPieces()) {
      $subcard = new SubCard($this, $i);
      $movingPilot = $moving && $subcard->IsPilot();
      if($subcard->CardID() == $subcardID && ($subcardUniqueID == "" || $subcards[$i+3] == $subcardUniqueID)) {
        $ownerId = $subcard->Owner();
        $isPilot = $subcard->IsPilot();
        $epicAction = $subcard->FromEpicAction();
        $turnsInPlay = $subcard->TurnsInPlay();
        $controller = $subcard->Controller();

        for ($j = SubcardPieces() - 1; $j >= 0; $j--) {
          unset($subcards[$i+$j]);
        }

        $subcards = array_values($subcards);
        $this->allies[$this->index + 4] = count($subcards) > 0 ? implode(",", $subcards) : "-";
        if(DefinedTypesContains($subcardID, "Upgrade") || $isPilot)
          UpgradeDetached($subcardID, $this->playerID, "MYALLY-" . $this->index, $turnsInPlay, $controller, $skipDestroy, $movingPilot);
        if(CardIDIsLeader($subcardID) && !$movingPilot) {
          $leaderUndeployed = LeaderUndeployed($subcardID);
          if($leaderUndeployed != "") {
            $usedEpicAction = $epicAction || (GetClassState($this->Owner(), $CS_CachedLeader1EpicAction) == 1);
            AddCharacter($leaderUndeployed, $ownerId, counters:$usedEpicAction ? 1 : 0, status:1);
          }
        }

        return $ownerId;
      }
    }
    return -1;
  }

  function AddEffect($effectID, $from="") {
    AddCurrentTurnEffect($effectID, $this->PlayerID(), from:$from, uniqueID:$this->UniqueID());
  }

  function RemoveEffect($effectID) {
    global $currentTurnEffects;
    for($i=0; $i<count($currentTurnEffects); $i+=CurrentTurnEffectPieces()) {
      if($currentTurnEffects[$i] == $effectID && $currentTurnEffects[$i+1] == $this->PlayerID() && $currentTurnEffects[$i+2] == $this->UniqueID()) {
        RemoveCurrentTurnEffect($i);
        return true;
      }
    }
    return false;
  }

  function HasEffect($effectID) {
    global $currentTurnEffects;
    for($i=0; $i<count($currentTurnEffects); $i+=CurrentTurnEffectPieces()) {
      if($currentTurnEffects[$i] == $effectID && $currentTurnEffects[$i+1] == $this->PlayerID() && $currentTurnEffects[$i+2] == $this->UniqueID())
        return true;
    }
    return false;
  }

  function AttachExperience() {
    return $this->Attach("2007868442"); //Experience token
  }

  function AttachShield() {
    return $this->Attach("8752877738"); //Shield token
  }

  function Attach($cardID, $ownerID = null, $epicAction = false, $turnsInPlay = 0, $takesControl = false) {
    $receivingPilot = $this->ReceivingPilot($cardID) || IsUnconventionalPilot($cardID);
    $controllerID = $takesControl ? $this->Controller() : $ownerID;
    $subcardUniqueID = $this->AddSubcard($cardID, $ownerID, $receivingPilot, $epicAction, $turnsInPlay, $controllerID);
    //Pilot attach side effects
    if($receivingPilot) {
      switch($this->CardID()) {
        //Jump to Lightspeed
        case "3711891756"://Red Leader
          CreateXWing($this->Controller());
          break;
        case "1935873883"://Razor Crest
          $player = $this->Controller();
          AddLayer("TRIGGER", $player, $this->CardID());
          break;
        default: break;
      }
    }
    if (CardIsUnique($cardID)) {
      CheckUniqueCard($cardID, $this->UniqueID());

      if ($receivingPilot) {
        switch ($cardID) {
          case "0979322247"://Sidon Ithano
            $this->DefeatIfNoRemainingHP();
            break;
          default: break;
        }
      }
    }
    //end Pilot attach side effects

   if($cardID == "6885149318"//TODO: Knight's Saber hack until we fix MZFILTER
      && TraitContains($this->CardID(), "Vehicle", $this->Controller())) {
        WriteLog("Vehicles can't hold lightsabers, reverting gamestate.");
        RevertGamestate();
      }

    return $subcardUniqueID;
  }

  function GetSubcards() {
    if(!$this->Exists() || !isset($this->allies[$this->index + 4]) || $this->allies[$this->index + 4] == "-") return [];
    $subcards = $this->allies[$this->index + 4];
    if($subcards == null || $subcards == "" || $subcards == "-") return [];
    return explode(",", $subcards);
  }

  function GetUpgrades($withMetadata = false) {
    if(!$this->Exists() || !isset($this->allies[$this->index + 4]) || $this->allies[$this->index + 4] == "-") return [];
    $subcards = $this->GetSubcards();
    $upgrades = [];
    for($i=0; $i<count($subcards); $i+=SubcardPieces()) {
      $isPilot = isset($subcards[$i+2]) && $subcards[$i+2] == "1";
      if(
        DefinedTypesContains($subcards[$i], "Upgrade", $this->PlayerID())
        || DefinedTypesContains($subcards[$i], "Token Upgrade", $this->PlayerID())
        || (DefinedTypesContains($subcards[$i], "Unit", $this->PlayerID()) && $isPilot)
      ) {
        if ($withMetadata) {
          for ($j = 0; $j < SubcardPieces(); $j++) {
            array_push($upgrades, $subcards[$i + $j]);
          }
        } else {
          $upgrades[] = $subcards[$i];
        }
      }
    }
    return $upgrades;
  }

  function HasCaptive() {
    return count($this->GetCaptives()) > 0;
  }

  function GetCaptives($withMetadata = false) {
    if($this->allies[$this->index + 4] == "-") return [];
    $subcards = $this->GetSubcards();
    $capturedUnits = [];
    for($i=0; $i<count($subcards); $i+=SubcardPieces()) {
      $subcard = new SubCard($this, $i);
      if($subcard->IsCaptive()) {
        if ($withMetadata) {
          for ($j = 0; $j < SubcardPieces(); $j++) {
            array_push($capturedUnits, $subcards[$i + $j]);
          }
        } else {
          $capturedUnits[] = $subcards[$i];
        }
      }
    }
    return $capturedUnits;
  }

  function IsCloned() {
    if (!$this->Exists() || !isset($this->allies[$this->index + 13])) return false;
    return $this->allies[$this->index + 13] == 1;
  }

  function ClearSubcards() {
    $this->allies[$this->index + 4] = "-";
  }

  function GetSubcardForCard($cardID) {
    if($this->index == -1) return false;
    $subcards = $this->GetSubcards();
    for($i=0; $i<count($subcards); $i+=SubcardPieces()) {
      if($subcards[$i] == $cardID) {
        return new SubCard($this, $i);
      }
    }
    return null;
  }

  function HasUpgrade($upgradeID, $uniqueID = "") {
    if($this->index == -1) return false;
    $upgrades = $this->GetUpgrades(withMetadata:true);
    for($i=0; $i<count($upgrades); $i+=SubcardPieces()) {
      if($upgrades[$i] == $upgradeID && ($uniqueID == "" || $upgrades[$i+3] == $uniqueID)) {
        return true;
      }
    }
    return false;
  }

  function DefeatAllShields() {
    while ($this->HasShield()) {
      $this->DefeatUpgrade("8752877738"); //Shield token
    }
  }

  function DefeatUpgrade($upgradeID, $subcardUniqueID = "") {
    if($upgradeID == "11e54776e9") {//Luke Skywalker leader pilot
      WriteLog(CardLink($upgradeID, $upgradeID) . " cannot be defeated.");
      return;
    }
    $uniqueID = $this->UniqueID();
    $ownerId = $this->RemoveSubcard($upgradeID, $subcardUniqueID);
    $updatedAlly = new Ally($uniqueID); // Refresh the ally, as the index or controller may have changed
    $updatedAlly->DefeatIfNoRemainingHP();
    return $ownerId;
  }

  function RescueCaptive($captiveID, $newController=-1) {
    $ownerId = $this->RemoveSubcard($captiveID);
    if($ownerId != -1) {
      if($newController == -1) $newController = $ownerId;
      return PlayAlly($captiveID, $newController, from:"CAPTIVE", owner:$ownerId);
    }
    return -1;
  }

  function DiscardCaptive($captiveID) {
    $ownerId = $this->RemoveSubcard($captiveID);
    if($ownerId != -1) {
      AddGraveyard($captiveID, $ownerId, "CAPTIVE");
      return true;
    }
    else return false;
  }

  function NumUses() {
    return $this->allies[$this->index + 8];
  }

  function SetNumUses($numUses) {
    $this->allies[$this->index + 8] = $numUses;
  }

  function SumNumUses($amount): void {
    $this->allies[$this->index + 8] += $amount;
  }

  function LostAbilities($ignoreFirstCardId = ""): bool {
    global $currentTurnEffects;

    if (!$this->Exists()) return false;

    // Check for effects that prevent abilities
    for ($i = 0; $i < count($currentTurnEffects); $i += CurrentTurnEffectPieces()) {
      $effectCardID = $currentTurnEffects[$i];
      $effectPlayerID = $currentTurnEffects[$i + 1];
      $effectUniqueID = $currentTurnEffects[$i + 2];

      if ($effectPlayerID != $this->PlayerID()) continue;
      if ($effectUniqueID != -1 && $effectUniqueID != $this->UniqueID()) continue;

      switch ($effectCardID) {
        case "2639435822": //Force Lightning
        case "4531112134": //Kazuda Xiono leader side
        case "c1700fc85b": //Kazuda Xiono pilot Leader Unit
        case "9184947464": //There Is No Escape
        case "1146162009": //Mind Trick
          return true;
        default: break;
      }
    }

    // Check for upgrades that prevent abilities
    $upgrades = $this->GetUpgrades();
    $ignoredUpgrade = 0;
    for ($i = 0; $i < count($upgrades); $i++) {
      //in case of imprisoned, upgrade are added before all triggers, we need to ignore it for krayt
      if($ignoreFirstCardId != "" && $upgrades[$i] == $ignoreFirstCardId && $ignoredUpgrade == 0) {
        $ignoredUpgrade++;
        continue;
      }

      switch ($upgrades[$i]) {
        case "1368144544"://Imprisoned
          return true;
        default: break;
      }
    }

    if ($this->IsLeader() && LeaderAbilitiesIgnored()) {
      return true;
    }

    return false;
  }

  function IsLeader() {
    return AllyIsLeader($this->CardID(), $this->GetUpgrades(withMetadata:true));
  }

  function HasPilotLeaderUpgrade() {
    return AllyHasPilotLeaderUpgrade($this->GetUpgrades(withMetadata:true));
  }

  function IsUpgraded(): bool {
    return $this->NumUpgrades() > 0;
  }

  function NumUpgrades(): int {
    $upgrades = $this->GetUpgrades();
    return count($upgrades);
  }

  function HasUpgradesThatAreNotUnique(): bool {
    $upgrades = $this->GetUpgrades();
    foreach ($upgrades as $upgrade) {
      if (!CardIsUnique($upgrade)) {
        return true;
      }
    }
    return false;
  }

  function HasBounty(): bool {
    if(!$this->LostAbilities()) return CollectBounties($this->PlayerID(), $this->CardID(), $this->UniqueID(), $this->IsExhausted(), $this->Owner(), $this->GetUpgrades(), reportMode:true) > 0;
    return false;
  }

  function IsSpectreWithGhostBounty(): bool {
    //The Ghost JTL
    $theGhostIndex = SearchAlliesForCard($this->Controller(), "5763330426");
    if($theGhostIndex != "" && TraitContains($this->CardID(), "Spectre", $this->Controller()) && $this->Index() != $theGhostIndex) {
      $theGhost = new Ally("MYALLY-" . $theGhostIndex, $this->Controller());
      return $theGhost->HasBounty();
    }
    return false;
  }

  function Serialize() {
    $builder = [];
    for($i=0; $i<AllyPieces();++$i) {
      $builder[$i] = $this->allies[$this->index+$i];
    }
    return implode(";", $builder);
  }

  function AvoidsDestroyByEnemyEffects() {
    return !$this->LostAbilities()
      && ($this->CardID() == "1810342362"//Lurking TIE Phantom
        || $this->CardID() == "7208848194"//Chewbacca
        || $this->HasUpgrade("7208848194")//Chewbacca
        || $this->HasUpgrade("9003830954"))//Shadowed Intentions
    ;
  }

  function AvoidsCapture() {
    return !$this->LostAbilities()
      && ($this->CardID() == "1810342362"//Lurking TIE Phantom
        || $this->HasUpgrade("9003830954"))//Shadowed Intentions
    ;
  }

  function AvoidsDamage($enemyDamage) {
    global $mainPlayer;
    return ($mainPlayer != $this->playerID || $enemyDamage)
      && !$this->LostAbilities()
      && $this->CardID() == "1810342362"//Lurking TIE Phantom
    ;
  }

  function AvoidsBounce() {
    global $mainPlayer;
    $isOrHasChewbaccaJTL = $this->CardID() == "7208848194"|| $this->HasUpgrade("7208848194");//Chewbacca
    $hasMythosaurEffect = PlayerHasMythosaurActive($this->Controller()) && $this->IsUpgraded();
    return $mainPlayer != $this->playerID
      && !$this->LostAbilities()
      && ($this->HasUpgrade("9003830954")//Shadowed Intentions
      || $isOrHasChewbaccaJTL || $hasMythosaurEffect)
    ;
  }

  function AvoidsExhaust() {
    $hasMythosaurEffect = PlayerHasMythosaurActive($this->Controller()) && $this->IsUpgraded();
    $isAForceUnitWithKylosLightsaber = !$this->LostAbilities() && TraitContains($this->CardID(), "Force", $this->Controller())
      && $this->HasUpgrade("1637958279");//Kylo Ren's Lightsaber

    return $hasMythosaurEffect
      || $isAForceUnitWithKylosLightsaber;
  }
}

function AllyIsLeader($cardID, $upgradesWithMetadata): bool {
  return CardIDIsLeader($cardID)
    || AllyHasPilotLeaderUpgrade($upgradesWithMetadata);
}

function AllyHasPilotLeaderUpgrade($upgradesWithMetadata): bool {
  for($i=0; $i<count($upgradesWithMetadata); $i+=SubcardPieces()) {
    if (CardIDIsLeader($upgradesWithMetadata[$i]) && $upgradesWithMetadata[$i+2] == "1") {
      return $upgradesWithMetadata[$i] != "3eb545eb4b";//Poe Dameron JTL leader (so far the only one)
    }
  }
  return false;
}

function LastAllyIndex($player): int {
  $allies = &GetAllies($player);
  return count($allies) - AllyPieces();
}

//SubCard class to handle interactions involving subcards
class SubCard {
  // Properties
  private $subcards = [];
  private $index = -1;

  function __construct(Ally $ally, int $index) {
    $this->subcards = $ally->GetSubcards();
    $this->index = $index;
  }

  function Index() {
    return $this->index;
  }

  function CardID() {
    return $this->subcards[$this->index];
  }

  function Owner() {
    return $this->subcards[$this->index+1];
  }

  function SetOwner($owner) {
    $this->subcards[$this->index+1] = $owner;
  }

  function Controller() {
    return $this->subcards[$this->index+6];
  }

  function IsPilot() {
    return $this->subcards[$this->index+2] == "1";
  }

  function IsCaptive() {
    return DefinedTypesContains($this->CardID(), "Unit") && !$this->IsPilot();
  }

  function UniqueID() {
    return $this->subcards[$this->index+3];
  }

  function FromEpicAction() {
    return $this->subcards[$this->index+4] == "1";
  }

  function TurnsInPlay() {
    return $this->subcards[$this->index+5];
  }
}

?>
