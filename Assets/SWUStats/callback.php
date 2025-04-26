<?php
// SWUStats OAuth Callback Handler
// This script handles the redirect from SWUStats after user authorization

// --- CONFIGURATION ---
// Load SWUStats OAuth client credentials from APIKeys/APIKeys.php
include_once __DIR__ . '/../../APIKeys/APIKeys.php';
$client_id = $swustatsClientID;
$client_secret = $swustatsClientSecret;
$redirect_uri = 'http://petranaki.net/SWUOnline/Assets/SWUStats/callback.php'; // Change to your production URL when deploying
$token_url = 'https://swustats.net/oauth/token';

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
try {
    $pdo = new PDO('mysql:host=localhost;dbname=YOUR_DB_NAME', 'YOUR_DB_USER', 'YOUR_DB_PASS');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Example: update the logged-in user's row (replace logic as needed)
    session_start();
    if (!isset($_SESSION['userid'])) {
        die('User not logged in.');
    }
    $stmt = $pdo->prepare('UPDATE users SET swustatsAccessToken=?, swustatsRefreshToken=?, swustatsTokenExpiry=? WHERE usersId=?');
    $stmt->execute([$access_token, $refresh_token, $expires_in, $_SESSION['userid']]);
    echo '<h2>SWUStats account linked successfully!</h2>';
} catch (PDOException $e) {
    die('Database error: ' . $e->getMessage());
}
?>

<!-- You may want to redirect the user or display a success message here. -->
