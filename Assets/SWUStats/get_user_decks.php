<?php
// get_user_decks.php
// Fetches decks for the authenticated user using their SWUStats OAuth access token

include_once __DIR__ . '/../../Database/ConnectionManager.php';
include_once __DIR__ . '/../../AccountFiles/AccountSessionAPI.php';

$userId = LoggedInUser();
if (!$userId) {
    http_response_code(401);
    die('User not logged in.');
}

$conn = GetLocalMySQLConnection();
if (!$conn) {
    http_response_code(500);
    die('Database connection failed.');
}

$stmt = $conn->prepare("SELECT swustatsAccessToken, swustatsRefreshToken FROM users WHERE usersId=?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$stmt->bind_result($access_token, $refresh_token_db);
$stmt->fetch();
$stmt->close();
$conn->close();

if (empty($access_token)) {
    http_response_code(401);
    die('No SWUStats access token found.');
}

// Always use the refresh token from the database
$refreshToken = $refresh_token_db;

// Load SWUStats client ID
include_once __DIR__ . '/../../APIKeys/APIKeys.php';
$client_id = $swustatsClientID;

// SWUStats API endpoint (update if needed)
$api_url = 'https://swustats.net/TCGEngine/APIs/UserAPIs/GetUserDecks.php?refresh_token=' . urlencode($refreshToken) . '&client_id=' . urlencode($client_id);

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
