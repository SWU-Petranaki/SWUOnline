<?php

// Parameter structure:
// 0 - DEALDAMAGE
// 1 - Damage amount
// 2? - Player causing the damage
// 3? - Indicates if the damage is caused by unit effects (1 = yes, 0 = no)
// 4? - Indicates if the damage is preventable (1 = yes, 0 = no) (not preventable = indirect)
function DealDamageBuilder($damage, $sourcePlayer, $isUnitEffect = 0, $isPreventable = 1) {
  $isUnitEffect = $isUnitEffect ? 1 : 0;
  $isPreventable = $isPreventable ? 1 : 0;
  return "DEALDAMAGE,$damage,$sourcePlayer,$isUnitEffect,$isPreventable";
}

/**
 * Builds a string for dealing damage to multiple targets
 * 
 * Parameter structure:
 * 0 - DEALMULTIDAMAGE
 * 1 - Damage amount
 * 2 - Player causing the damage
 * 3 - Indicates if the damage is caused by unit effects (1 = yes, 0 = no)
 * 4 - Indicates if the damage is preventable (1 = yes, 0 = no) (not preventable = indirect)
 * 
 * @param int $damage The amount of damage to deal
 * @param int $sourcePlayer The player causing the damage
 * @param int $isUnitEffect Whether the damage is caused by unit effects (1 = yes, 0 = no)
 * @param int $isPreventable Whether the damage is preventable (1 = yes, 0 = no)
 * @return string The formatted damage string for the decision queue
 */
function DealMultiDamageBuilder($sourcePlayer, $isUnitEffect = 0, $isPreventable = 1) {
  $isUnitEffect = $isUnitEffect ? 1 : 0;
  $isPreventable = $isPreventable ? 1 : 0;
  return "DEALMULTIDAMAGE,$sourcePlayer,$isUnitEffect,$isPreventable";
}

// Parameter structure:
//0 - total damage
//1 - from unit effect (1 = yes, 0 = no)
//2 - max damage per target (0 means no max)
//3 - source player
//4 - preventable (1 = yes, 0 = no); default is yes (not preventable = indirect)
//6 - zones (THEIRALLY, MYALLY, OURALLIES, OURALLIESANDBASES, MYALLIESANDBASE, THEIRALLIESANDBASE); default is THEIRALLY
function MultiDistributeDamageStringBuilder($totalDamage, $sourcePlayer, $isUnitEffect = 0, $maxDamagePerTarget = 0, $isPreventable = 1, $zones = "THEIRALLY") {
  $isUnitEffect = $isUnitEffect ? 1 : 0;
  $maxDamagePerTarget = $maxDamagePerTarget ? $maxDamagePerTarget : 0;
  $isPreventable = $isPreventable ? 1 : 0;
  return "$totalDamage,$isUnitEffect,$maxDamagePerTarget,$sourcePlayer,$isPreventable,$zones";
}

/**
 * Builds a string for assigning indirect damage
 * 
 * Parameter structure:
 * 0 - ASSIGNINDIRECTDAMAGE
 * 1 - Card ID
 * 2 - Source player
 * 3 - Amount of damage
 * 4 - From unit effect (1 = yes, 0 = no)
 * 5 - Source modifier card ID
 * 
 * @param string $cardID The ID of the card that caused the indirect damage
 * @param int $sourcePlayer The player causing the indirect damage
 * @param int $amount The amount of damage to assign
 * @param int $fromUnitEffect Whether the damage is caused by unit effects (1 = yes, 0 = no)
 * @param string $sourceModifierCardID The ID of the card that caused the indirect damage
 * @return string The formatted indirect damage string for the decision queue
 */
function AssignIndirectDamageBuilder($cardID, $sourcePlayer, $amount, $fromUnitEffect = 0, $sourceModifierCardID = "") {
  $fromUnitEffect = $fromUnitEffect ? 1 : 0;
  return "ASSIGNINDIRECTDAMAGE,$cardID,$sourcePlayer,$amount,$fromUnitEffect,$sourceModifierCardID";
}

/**
 * Builds a string for giving experience to multiple targets
 * 
 * Parameter structure:
 * 0 - MULTIGIVEEXPERIENCE
 * 1 - Amount of experience
 * 2 - Source player
 * 
 * @param int $amount The amount of experience to give
 * @param int $sourcePlayer The player giving the experience
 * @return string The formatted experience string for the decision queue
 */
function GiveExperienceBuilder($sourcePlayer, $isUnitEffect = 0, $isPreventable = 1) {
  $isUnitEffect = $isUnitEffect ? 1 : 0;
  $isPreventable = $isPreventable ? 1 : 0;
  return "MULTIADDEXPERIENCE,$sourcePlayer,$isUnitEffect,$isPreventable";
}
?>