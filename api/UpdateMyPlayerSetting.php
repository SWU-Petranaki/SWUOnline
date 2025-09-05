<?php
include "../Libraries/PlayerSettings.php";
include "../includes/functions.inc.php";
header('Content-Type: application/json; charset=utf-8');
session_start();
if (!isset($_SESSION['userid'])) {
  echo json_encode(["error" => "Not logged in"]);
  exit;
}

$sessionUserid = $_SESSION['userid'];
$requestUserid = $_GET['userid'] ?? '';

if ($sessionUserid != $requestUserid) {
  echo json_encode(["error" => "Unauthorized access"]);
  exit;
}

$settingPiece = $_GET['piece'] ?? '';
$settingValue = $_GET['value'] ?? '';

if ($settingPiece === '' || $settingValue === '') {
  echo json_encode(["error" => "Missing parameters"]);
  exit;
}

$settingPiece = htmlentities($settingPiece, ENT_QUOTES);
$settingValue = htmlentities($settingValue, ENT_QUOTES);

$settingPiece = str_replace("'", "\'", $settingPiece);
$settingValue = str_replace("'", "\'", $settingValue);
session_write_close();
if(!SaveSettingInDatabase($settingPiece))
{
  echo json_encode(["error" => "Invalid setting"]);
  exit;
}

ChangeSetting("", $settingPiece, $settingValue, $sessionUserid);
echo json_encode(["success" => "Setting updated"]);
exit;
?>