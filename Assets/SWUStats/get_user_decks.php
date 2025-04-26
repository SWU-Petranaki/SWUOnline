<?php
// get_user_decks.php
// Fetches decks for the authenticated user using their SWUStats OAuth access token

session_start();

// Database connection (update with your actual DB credentials)
$pdo = new PDO('mysql:host=localhost;dbname=YOUR_DB_NAME', 'YOUR_DB_USER', 'YOUR_DB_PASS');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Get the current user's access token from the database
if (!isset($_SESSION['userid'])) {
    http_response_code(401);
    die('User not logged in.');
}
$stmt = $pdo->prepare('SELECT swustatsAccessToken FROM users WHERE usersId=?');
$stmt->execute([$_SESSION['userid']]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$row || empty($row['swustatsAccessToken'])) {
    http_response_code(401);
    die('No SWUStats access token found.');
}
$access_token = $row['swustatsAccessToken'];

// SWUStats API endpoint (update if needed)
$api_url = 'https://swustats.net/TCGEngine/APIs/UserAPIs/GetUserDecks.php';

$ch = curl_init($api_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $access_token
]);
$response = curl_exec($ch);
if (curl_errno($ch)) {
    http_response_code(500);
    die('Curl error: ' . curl_error($ch));
}
curl_close($ch);

header('Content-Type: application/json');
echo $response;
?>
