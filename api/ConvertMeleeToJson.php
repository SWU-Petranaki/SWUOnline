<?php
include "../includes/functions.inc.php";
include "../Libraries/CoreLibraries.php";
include "../GeneratedCode/GeneratedCardDictionaries.php";
include "../CardOverrides.php";
header('Content-Type: application/json; charset=utf-8');
session_start();

if (!isset($_SESSION['userid'])) {
  echo json_encode(["error" => "Not logged in"]);
  exit;
}
$useruid = $_SESSION['useruid'];

$deck = $_POST["input"] ?? '';
if ($deck == '') {
  echo json_encode(["error" => "Missing input parameter"]);
  exit;
}
$deckName = $_POST["deckName"] ?? '';
if ($deckName == '') {
  echo json_encode(["error" => "Missing deck name"]);
  exit;
}

$result = [
  "metadata" => [
    "name" => $deckName,
    "author" => $useruid,
  ],
  "leader" => [],
  "base" => [],
  "deck" => [],
  "sideboard" => []
];

$lines = explode("\n", $deck);
$key = "deck";
foreach ($lines as $line) {
  $line = trim($line);
  if (empty($line)) {
    continue;
  }
  $skipLine = false;
  switch ($line) {
    case "MainDeck":
      $key = "deck";
      $skipLine = true;
      break;
    case "Sideboard":
      $key = "sideboard";
      $skipLine = true;
      break;
    case "Leader":
      $key = "leader";
      $skipLine = true;
      break;
    case "Base":
      $key = "base";
      $skipLine = true;
      break;
    default:
      break;
  }
  if ($skipLine) {
    continue;
  }
  if (preg_match('/^(\d+)\s+(.+)$/', $line, $matches)) {
    $count = $matches[1];
    $name = $matches[2];
    $pieces = explode(" | ", $name);
    $id = LookupCardIDFromTitles($pieces[0], $pieces[1] ?? null);
    $setId = CardIDOverride(CardIDLookup($id));
    if($key === "deck" || $key === "sideboard") {
      $result[$key][] = [
        "id" => $setId,
        "count" => $count,
      ];
    } else if($key === "leader") {
      $result[$key] = [
        "id" => $setId,
        "count" => $count,
      ];
    } else if($key === "base") {
      $result[$key] = [
        "id" => $setId,
        "count" => $count,
      ];
    }
  }
}

echo json_encode($result);
?>