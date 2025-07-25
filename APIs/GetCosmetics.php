<?php

include "../AccountFiles/AccountSessionAPI.php";
include_once '../includes/functions.inc.php';
include_once "../includes/dbh.inc.php";
include_once "../Libraries/PlayerSettings.php";
include_once "../Libraries/HTTPLibraries.php";
include_once "../Assets/patreon-php-master/src/PatreonDictionary.php";

session_start();

SetHeaders();

$response = new stdClass();
$response->cardBacks = [];

//Add default card back
$cardBack = new stdClass();
$cardBack->name = "Default";
$cardBack->id = 0;
$response->cardBacks[] = $cardBack;
$cardBack = new stdClass();
$cardBack->name = "Rebel Resource";
$cardBack->id = 3;
$response->cardBacks[] = $cardBack;
$cardBack = new stdClass();
$cardBack->name = "Rebel Resource Dark";
$cardBack->id = 4;
$response->cardBacks[] = $cardBack;
$cardBack = new stdClass();
$cardBack->name = "L8 Night Gaming";
$cardBack->id = 6;
$response->cardBacks[] = $cardBack;
$cardBack = new stdClass();
$cardBack->name = "Mobyus1 Simple";
$cardBack->id = 7;
$response->cardBacks[] = $cardBack;
$cardBack = new stdClass();
$cardBack->name = "Mobyus1 Titled";
$cardBack->id = 8;
$response->cardBacks[] = $cardBack;
$cardBack = new stdClass();
$cardBack->name = "Outmaneuver";
$cardBack->id = 9;
$response->cardBacks[] = $cardBack;
$cardBack = new stdClass();
$cardBack->name = "Bothan Network";
$cardBack->id = 10;
$response->cardBacks[] = $cardBack;
$cardBack = new stdClass();
$cardBack->name = "Padawan Unlimited";
$cardBack->id = 11;
$response->cardBacks[] = $cardBack;
$cardBack = new stdClass();
$cardBack->name = "RVA SWU";
$cardBack->id = 12;
$response->cardBacks[] = $cardBack;
$cardBack = new stdClass();
$cardBack->name = "Baddest Batch";
$cardBack->id = 13;
$response->cardBacks[] = $cardBack;
$cardBack = new stdClass();
$cardBack->name = "Holocron Card Hub";
$cardBack->id = 15;
$response->cardBacks[] = $cardBack;
$cardBack = new stdClass();
$cardBack->name = "Maclunky Gaming";
$cardBack->id = 16;
$response->cardBacks[] = $cardBack;
$cardBack = new stdClass();
$cardBack->name = "The Cantina Crew";
$cardBack->id = 17;
$response->cardBacks[] = $cardBack;
$cardBack = new stdClass();
$cardBack->name = "Rajeux TCG";
$cardBack->id = 18;
$response->cardBacks[] = $cardBack;
$cardBack = new stdClass();
$cardBack->name = "Under The Twin Suns";
$cardBack->id = 19;
$response->cardBacks[] = $cardBack;
$cardBack = new stdClass();
$cardBack->name = "Too Many Hans";
$cardBack->id = 20;
$response->cardBacks[] = $cardBack;
$cardBack = new stdClass();
$cardBack->name = "Porg Depot";
$cardBack->id = 21;
$response->cardBacks[] = $cardBack;
$cardBack = new stdClass();
$cardBack->name = "Darth Players";
$cardBack->id = 22;
$response->cardBacks[] = $cardBack;
$cardBack = new stdClass();
$cardBack->name = "Mainedalorians";
$cardBack->id = 23;
$response->cardBacks[] = $cardBack;
$cardBack = new stdClass();
$cardBack->name = "Galactic Gonks";
$cardBack->id = 24;
$response->cardBacks[] = $cardBack;
$cardBack = new stdClass();
$cardBack->name = "Fallen Order";
$cardBack->id = 25;
$response->cardBacks[] = $cardBack;
$cardBack = new stdClass();
$cardBack->name = "Mythic Force";
$cardBack->id = 26;
$response->cardBacks[] = $cardBack;
$cardBack = new stdClass();
$cardBack->name = "MoG TCG";
$cardBack->id = 27;
$response->cardBacks[] = $cardBack;
$cardBack = new stdClass();
$cardBack->name = "SWCGR";
$cardBack->id = 28;
$response->cardBacks[] = $cardBack;
$cardBack = new stdClass();
$cardBack->name = "SWU AUS";
$cardBack->id = 29;
$response->cardBacks[] = $cardBack;
$cardBack = new stdClass();
$cardBack->name = "GonkGang";
$cardBack->id = 30;
$response->cardBacks[] = $cardBack;
$cardBack = new stdClass();
$cardBack->name = "Galactic Shuffle";
$cardBack->id = 31;
$response->cardBacks[] = $cardBack;
$cardBack = new stdClass();
$cardBack->name = "Tropa do Boba";
$cardBack->id = 32;
$response->cardBacks[] = $cardBack;
$cardBack = new stdClass();
$cardBack->name = "Outer Rim CCG";
$cardBack->id = 33;
$response->cardBacks[] = $cardBack;
$cardBack = new stdClass();
$cardBack->name = "Central Spacers";
$cardBack->id = 34;
$response->cardBacks[] = $cardBack;
$cardBack = new stdClass();
$cardBack->name = "Enigma";
$cardBack->id = 35;
$response->cardBacks[] = $cardBack;
$cardBack = new stdClass();
$cardBack->name = "PrairiePirates";
$cardBack->id = 36;
$response->cardBacks[] = $cardBack;
$cardBack = new stdClass();
$cardBack->name = "Colorado Cantina Crew";
$cardBack->id = 37;
$response->cardBacks[] = $cardBack;
$cardBack = new stdClass();
$cardBack->name = "The Nordic Takedown";
$cardBack->id = 39;
$response->cardBacks[] = $cardBack;
$cardBack = new stdClass();
$cardBack->name = "Sekrit";
$cardBack->id = 40;
$response->cardBacks[] = $cardBack;
$cardBack = new stdClass();
$cardBack->name = "C4";
$cardBack->id = 41;
$response->cardBacks[] = $cardBack;
$cardBack = new stdClass();
$cardBack->name = "Golden Eagle Gaming";
$cardBack->id = 42;
$response->cardBacks[] = $cardBack;
$cardBack = new stdClass();
$cardBack->name = "SWU NZ";
$cardBack->id = 43;
$response->cardBacks[] = $cardBack;
$cardBack = new stdClass();
$cardBack->name = "Golden Squadron";
$cardBack->id = 44;
$response->cardBacks[] = $cardBack;
$cardBack = new stdClass();
$cardBack->name = "Epic Action [X]";
$cardBack->id = 45;
$response->cardBacks[] = $cardBack;
$cardBack = new stdClass();
$cardBack->name = "Make an Opening";
$cardBack->id = 46;
$response->cardBacks[] = $cardBack;
$cardBack = new stdClass();
$cardBack->name = "Aixopluc Squadron";
$cardBack->id = 47;
$response->cardBacks[] = $cardBack;
$cardBack = new stdClass();
$cardBack->name = "Kaloret Warriors";
$cardBack->id = 48;
$response->cardBacks[] = $cardBack;
$cardBack = new stdClass();
$cardBack->name = "Ruthless Raiders";
$cardBack->id = 49;
$response->cardBacks[] = $cardBack;
$cardBack = new stdClass();
$cardBack->name = "Bordure Exterieure";
$cardBack->id = 50;
$response->cardBacks[] = $cardBack;
$cardBack = new stdClass();
$cardBack->name = "RTchompGG";
$cardBack->id = 51;
$response->cardBacks[] = $cardBack;
$cardBack = new stdClass();
$cardBack->name = "Les Cartes sur Table";
$cardBack->id = 52;
$response->cardBacks[] = $cardBack;
$cardBack = new stdClass();
$cardBack->name = "Against The Galaxy";
$cardBack->id = 53;
$response->cardBacks[] = $cardBack;
$cardBack = new stdClass();
$cardBack->name = "Canadian Snow Troopers";
$cardBack->id = 54;
$response->cardBacks[] = $cardBack;

$cardBack = new stdClass();
$cardBack->name = "Omaha Alliance";
$cardBack->id = 55;
$response->cardBacks[] = $cardBack;

$cardBack = new stdClass();
$cardBack->name = "Star Wars Dad (Dad)";
$cardBack->id = 56;
$response->cardBacks[] = $cardBack;

$cardBack = new stdClass();
$cardBack->name = "Star Wars Dad (Family)";
$cardBack->id = 57;
$response->cardBacks[] = $cardBack;

$cardBack = new stdClass();
$cardBack->name = "Unplayable";
$cardBack->id = 58;
$response->cardBacks[] = $cardBack;

$cardBack = new stdClass();
$cardBack->name = "Wasatch Wookies";
$cardBack->id = 59;
$response->cardBacks[] = $cardBack;

$cardBack = new stdClass();
$cardBack->name = "MoTheMonster";
$cardBack->id = 60;
$response->cardBacks[] = $cardBack;

$cardBack = new stdClass();
$cardBack->name = "Coastal Cantina";
$cardBack->id = 61;
$response->cardBacks[] = $cardBack;

$cardBack = new stdClass();
$cardBack->name = "Coastal Cantina (Name)";
$cardBack->id = 62;
$response->cardBacks[] = $cardBack;

$cardBack = new stdClass();
$cardBack->name = "Top Cut Target";
$cardBack->id = 63;
$response->cardBacks[] = $cardBack;

$cardBack = new stdClass();
$cardBack->name = "Outer Team";
$cardBack->id = 64;
$response->cardBacks[] = $cardBack;

$cardBack = new stdClass();
$cardBack->name = "SWUNeff";
$cardBack->id = 65;
$response->cardBacks[] = $cardBack;

$cardBack = new stdClass();
$cardBack->name = "BVS";
$cardBack->id = 66;
$response->cardBacks[] = $cardBack;

$cardBack = new stdClass();
$cardBack->name = "Babu Freaks";
$cardBack->id = 67;
$response->cardBacks[] = $cardBack;

//continue adding card backs here

$response->playmats = [];
if(IsUserLoggedIn()) {
  foreach(PatreonCampaign::cases() as $campaign) {
    if(isset($_SESSION[$campaign->SessionID()]) || (isset($_SESSION["useruid"]) && $campaign->IsTeamMember($_SESSION["useruid"]))) {
      //Check card backs first
      $cardBacks = $campaign->CardBacks();
      $cardBacks = explode(",", $cardBacks);
      for($i = 0; $i < count($cardBacks); ++$i) {
        $cardBack = new stdClass();
        $cardBack->name = $campaign->CampaignName() . (count($cardBacks) > 1 ? " " . $i + 1 : "");
        $cardBack->id = $cardBacks[$i];
        $response->cardBacks[] = $cardBack;
      }
    }
  }

  for ($i = 0; $i < 17; ++$i) {
    if($i == 7) continue;
    $playmat = new stdClass();
    $playmat->id = $i;
    $playmat->name = GetPlaymatName($i);
    $response->playmats[] = $playmat;
  }
}

session_write_close();
echo json_encode($response);

function GetPlaymatName($id)
{
  switch ($id) {
    case 0:
      return "Plain";
    // case 1:
    //   return "Demonastery";
    // case 2:
    //   return "Metrix";
    // case 3:
    //   return "Misteria";
    // case 4:
    //   return "Pits";
    // case 5:
    //   return "Savage";
    // case 6:
    //   return "Solana";
    // case 7:
    //   return "Volcor";
    // case 8:
    //   return "Data-Doll";
    // case 9:
    //   return "Aria";
    // case 10:
    //   return "Bare-Fangs-AHS";
    // case 11:
    //   return "Erase-Face-AHS";
    // case 12:
    //   return "Dusk-Till-Dawn-AHS";
    // case 13:
    //   return "Exude-Confidence-AHS";
    // case 14:
    //   return "Command-and-Conquer-AHS";
    // case 15:
    //   return "Swarming-Gloomveil-AHS";
    // case 16:
    //   return "FindCenter";
    default:
      return "N/A";
  }
}
