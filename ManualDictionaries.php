<?php
function ManualCardTitleData() {
  return array(
    'abcdefg001' => 'Darth Tyranus',
    'abcdefg002' => 'Malakili',
    'abcdefg003' => 'Anakin Skywalker',
    'abcdefg004' => 'Mind Trick',
    'abcdefg005' => 'Curious Flock',
    'abcdefg006' => 'Constructed Lightsaber',
    //continue manual card titles
    'zzzzzzz002' => 'Sith Temple (Placeholder)',
    'zzzzzzz003' => 'Hidden Jedi Temple (Placeholder)',
  );
}

function ManualCardSubtitleData() {
  return array(
    'abcdefg001' => 'Servant Of Sidious',
    'abcdefg002' => 'Loving Rancor Keeper',
    'abcdefg003' => 'Champion Of Mortis',
    //continue manual card subtitles
  );
}

function ManualCardCostData() {
  return array(
    'abcdefg001' => 4,
    'abcdefg002' => 2,
    'abcdefg003' => 6,
    'abcdefg004' => 2,
    'abcdefg005' => 1,
    'abcdefg006' => 3,
    //continue manual card costs
  );
}

function ManualCardHPDictionaryData() {
  return array(
    'abcdefg001' => 3,
    'abcdefg002' => 4,
    'abcdefg003' => 7,
    'abcdefg005' => 1,
    //continue manual card HP dictionary
    'zzzzzzz002' => 28,
    'zzzzzzz003' => 28,
  );
}

function ManualCardPowerData() {
  return array(
    'abcdefg001' => 4,
    'abcdefg002' => 1,
    'abcdefg003' => 5,
    'abcdefg005' => 1,
    //continue manual card powers
  );
}

function ManualCardUpgradeHPDictionaryData() {
  return array(
    'abcdefg006' => 2,
    //continue manual card upgrade HP dictionary
  );
}

function ManualCardUpgradePowerData() {
  return array(
    'abcdefg006' => 3,
    //continue manual card upgrade powers
  );
}

function ManualCardAspectsData() {
  return array(
    'abcdefg001' => 'Villainy',
    'abcdefg002' => 'Command',
    'abcdefg003' => 'Vigilance',
    'abcdefg004' => 'Cunning,Heroism',
    //continue manual card aspects
    'zzzzzzz002' => 'Aggression',
    'zzzzzzz003' => 'Cunning',
  );
}

function ManualCardTraitsData() {
  return array(
    'abcdefg001' => 'Force,Separatist,Sith',
    'abcdefg002' => 'Underworld',
    'abcdefg003' => 'Force,Jedi,Republic',
    'abcdefg004' => 'Force,Trick',
    'abcdefg005' => 'Creature',
    'abcdefg006' => 'Item,Weapon,Lightsaber',
    //continue manual card traits
  );
}

function ManualCardArenasData() {
  return array(
    'abcdefg001' => 'Ground',
    'abcdefg002' => 'Ground',
    'abcdefg003' => 'Ground',
    'abcdefg005' => 'Ground',
    //continue manual card arenas
  );
}

function ManualDefinedCardTypeData() {
  return array(
    'abcdefg001' => 'Unit',
    'abcdefg002' => 'Unit',
    'abcdefg003' => 'Unit',
    'abcdefg004' => 'Event',
    'abcdefg005' => 'Unit',
    'abcdefg006' => 'Upgrade',
    //continue manual card types
    'zzzzzzz002' => 'Base',
    'zzzzzzz003' => 'Base',
  );
}

function ManualDefinedCardType2Data() {
  return array(
    'abcdefg002' => 'Unit',
    'abcdefg003' => 'Unit',
    'abcdefg004' => 'Event',
    'abcdefg005' => 'Unit',
    'abcdefg006' => 'Upgrade',
    //continue manual card types 2
  );
}

function ManualCardIsUniqueData() {
  return array(
    'abcdefg001' => 1,
    'abcdefg002' => 1,
    'abcdefg003' => 1,
    //continue manual card unique status
  );
}

function ManualHasWhenPlayedData() {
  return array(
    'abcdefg003' => true,
    'abcdefg005' => true,
    //continue manual card when played status
  );
}

function ManualHasWhenDestroyedData() {
  return array(
    //continue manual card when destroyed status
  );
}

function ManualCardSetData() {
  return array(
    'abcdefg001' => 'LOF',
    'abcdefg002' => 'LOF',
    'abcdefg003' => 'LOF',
    'abcdefg004' => 'LOF',
    'abcdefg005' => 'LOF',
    'abcdefg006' => 'LOF',
    //continue manual card sets
    'zzzzzzz002' => 'LOF',
    'zzzzzzz003' => 'LOF',
  );
}

function ManualUUIDLookupData() {
  return array(
    'LOF_231' => 'abcdefg001',
    'LOF_108' => 'abcdefg002',
    'LOF_070' => 'abcdefg003',
    'LOF_202' => 'abcdefg004',
    'LOF_255' => 'abcdefg005',
    'LOF_261' => 'abcdefg006',
    //continue manual UUID lookups
  );
}

function ManualCardIDLookupData() {
  return array(
    'abcdefg001' => 'LOF_231',
    'abcdefg002' => 'LOF_108',
    'abcdefg003' => 'LOF_070',
    'abcdefg004' => 'LOF_202',
    'abcdefg005' => 'LOF_255',
    'abcdefg006' => 'LOF_261',
    //continue manual card ID lookups
  );
}

function ManualCardRarityData() {
  return array(
    'abcdefg001' => 'Special',
    'abcdefg002' => 'Rare',
    'abcdefg003' => 'Legendary',
    'abcdefg004' => 'Rare',
    'abcdefg005' => 'Common',
    'abcdefg006' => 'Legendary',
    //continue manual card rarities
  );
}

function ManualCardTitlesData() {
    //to be added to the CardTitles function output
    return '|Darth Tyranus|Malakili|Anakin Skywalker|Mind Trick|Curious Flock|Constructed Lightsaber';
}
?>