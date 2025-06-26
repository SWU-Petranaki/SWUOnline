<?php
// SWUStats OAuth Callback Handler
// This script handles the redirect from SWUStats after user authorization

// --- CONFIGURATION ---
// Load SWUStats OAuth client credentials from APIKeys/APIKeys.php
include_once __DIR__ . '/../../APIKeys/APIKeys.php';
$client_id = $swustatsClientID;
$client_secret = $swustatsClientSecret;
$redirect_uri = 'https://petranaki.net/Arena/Assets/SWUStats/callback.php'; // Change to your production URL when deploying
$token_url = 'https://swustats.net/TCGEngine/APIs/OAuth/token.php';

// --- STEP 1: Receive authorization code ---
if (!isset($_GET['code'])) {
    die('Authorization code not found.');
}
$code = $_GET['code'];

// --- STEP 2: Exchange code for access token ---
$ch = curl_init($token_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    'grant_type' => 'authorization_code',
    'code' => $code,
    'redirect_uri' => $redirect_uri,
    'client_id' => $client_id,
    'client_secret' => $client_secret
]));
$response = curl_exec($ch);
if (curl_errno($ch)) {
    die('Curl error: ' . curl_error($ch));
}
curl_close($ch);
$token_data = json_decode($response, true);

if (!isset($token_data['access_token'])) {
    die('Access token not found in response: ' . htmlspecialchars($response));
}
$access_token = $token_data['access_token'];
$refresh_token = isset($token_data['refresh_token']) ? $token_data['refresh_token'] : null;
$expires_in = isset($token_data['expires_in']) ? $token_data['expires_in'] : null;
// Optionally: fetch user info here using the access token

// --- STEP 3: Store tokens in the database ---
// You should link the tokens to the currently logged-in user or create a new user as needed.
// Example using PDO (update with your DB connection):
include_once __DIR__ . '/../../Database/ConnectionManager.php';
include_once __DIR__ . '/../../AccountFiles/AccountSessionAPI.php';

$userId = LoggedInUser();
if (!$userId) {
    if (isset($_COOKIE["rememberMeToken"])) {
        include_once '../Assets/patreon-php-master/src/PatreonLibraries.php';
        include_once '../Assets/patreon-php-master/src/API.php';
        include_once '../Assets/patreon-php-master/src/PatreonDictionary.php';
        include_once '../../includes/functions.inc.php';
        include_once '../../includes/dbh.inc.php';
        loginFromCookie();
    }
    $userId = LoggedInUser();
    if (!$userId) {
        die('User not logged in.');
    }
}

$conn = GetLocalMySQLConnection();
if (!$conn) {
    die('Database connection failed.');
}

$stmt = $conn->prepare("UPDATE users SET swustatsAccessToken=?, swustatsRefreshToken=?, swustatsTokenExpiry=? WHERE usersId=?");
$stmt->bind_param("sssi", $access_token, $refresh_token, $expires_in, $userId);
if ($stmt->execute()) {
    session_start();
    $_SESSION['swustats_linked_success'] = true;
    header('Location: /Arena/ProfilePage.php');
    exit();
} else {
    die('Database error: ' . $stmt->error);
}
$stmt->close();
$conn->close();
// No output here, user will be redirected.
