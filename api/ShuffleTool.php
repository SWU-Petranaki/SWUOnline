<?php
include "../includes/functions.inc.php";
include "../includes/dbh.inc.php";
include "../Libraries/CoreLibraries.php";
header('Content-Type: application/json; charset=utf-8');
session_start();
if (!isset($_SESSION['userid'])) {
  echo json_encode(["error" => "Not logged in"]);
  exit;
}

$contributors = ["OotTheMonk", "love", "ninin", "Brubraz", "Mobyus1"];
$useruid = $_SESSION["useruid"];
if (!in_array($useruid, $contributors)) {
  echo json_encode(["error" => "Unauthorized"]);
  exit;
}

$deck = $_POST["deck"] ?? '';
$numCards = $_POST["numCards"] ?? 0;
$iterations = $_POST["iterations"] ?? 1000;
if ($deck == '' || $numCards <= 0) {
  echo json_encode(["error" => "Missing parameters"]);
  exit;
}
$deck = htmlentities($deck, ENT_QUOTES);
$deckArray = explode(" ", $deck);
$results = [
  "t1_1" => 0,
  "t1_2" => 0,
  "t2_2" => 0,
  "t3_2" => 0,
  "t4_2" => 0,
  "t1_3" => 0,
  "t2_3" => 0,
  "t3_3" => 0,
];
global $randomSeeded;
$randomSeeded = true;
for($i = 0; $i < $iterations; ++$i) {
  $deckCopy = $deckArray;
  //shuffle deck using algorithm with seed = true
  for($j=0;$j<3;++$j) {//we shuffle ten times in prod
    RandomizeArray($deckCopy, skipSeed: true);
  }
  //draw num cards
  $drawnCards = [];
  for($j=0;$j<$numCards;++$j) {
    $card = array_shift($deckCopy);
    if ($card === null) break;
    if (isset($drawnCards[$card])) {
      $drawnCards[$card]++;
    } else {
      $drawnCards[$card] = 1;
    }
  }
  //check for dupes
  $noDupes = false;
  $oneDupe = false;
  $twoDupes = false;
  $threeDupes = false;
  $fourDupes = false;
  $oneTrips = false;
  $twoTrips = false;
  $threeTrips = false;

  foreach($drawnCards as $card => $cards) {
    if ($cards == 1) continue;
    if ($cards == 2) {
      if ($oneDupe) {
        $oneDupe = false;
        $twoDupes = true;
      }
      else if ($twoDupes) {
        $twoDupes = false;
        $threeDupes = true;
      }
      else if ($threeDupes) {
        $threeDupes = false;
        $fourDupes = true;
      } else {
        $oneDupe = true;
      }
    }
    if ($cards == 3) {
      if ($oneTrips) {
        $oneTrips = false;
        $twoTrips = true;
      }
      if ($twoTrips) {
        $twoTrips = false;
        $threeTrips = true;
      } else {
        $oneTrips = true;
      }
    }
  }
  if(!$oneDupe && !$twoDupes && !$threeDupes && !$fourDupes && !$oneTrips && !$twoTrips && !$threeTrips) {
    $noDupes = true;
  }
  if($oneDupe) {
    $results["t1_2"]++;
  }
  if($twoDupes) {
    $results["t2_2"]++;
  }
  if($threeDupes) {
    $results["t3_2"]++;
  }
  if($oneTrips) {
    $results["t1_3"]++;
  }
  if($twoTrips) {
    $results["t2_3"]++;
  }
  if($threeTrips) {
    $results["t3_3"]++;
  }
  if($noDupes) {
    $results["t1_1"]++;
  }
}

echo json_encode($results);
?>