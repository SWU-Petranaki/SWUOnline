<?php

function LoadUserData($username) {
	$conn = GetLocalMySQLConnection();
  $sql = "SELECT * FROM users WHERE usersUid = ?";
	$stmt = mysqli_stmt_init($conn);
	if (!mysqli_stmt_prepare($stmt, $sql)) {
	 	return NULL;
	}
	mysqli_stmt_bind_param($stmt, "s", $username);
	mysqli_stmt_execute($stmt);
	$result = mysqli_stmt_get_result($stmt);
  $row = mysqli_fetch_assoc($result);
	mysqli_stmt_close($stmt);
	mysqli_close($conn);

  return $row;
}


function PasswordLogin($username, $password, $rememberMe) {
	$conn = GetLocalMySQLConnection();
	try {
		$userData = LoadUserData($username);
	}
	catch (\Exception $e) { }

  if($userData == NULL) return false;

  try {
  	$passwordValid = password_verify($password, $userData["usersPwd"]);
  }
  catch (\Exception $e) { }

  if($passwordValid)
  {
    session_start();
		$_SESSION["userid"] = $userData["usersId"];
		$_SESSION["useruid"] = $userData["usersUid"];
		$_SESSION["useremail"] = $userData["usersEmail"];
		$_SESSION["userspwd"] = $userData["usersPwd"];
		$patreonAccessToken = $userData["patreonAccessToken"];
		$_SESSION["patreonEnum"] = $userData["patreonEnum"];
		$_SESSION["isBanned"] = $userData["isBanned"];

		try {
			PatreonLogin($patreonAccessToken);
		} catch (\Exception $e) { }

		if($rememberMe)
		{
      $domain = (!empty(getenv("DOMAIN")) ? getenv("DOMAIN") : "petranaki.net");
			$cookie = hash("sha256", rand() . $_SESSION["userspwd"] . rand());
			setcookie("rememberMeToken", $cookie, time() + (86400 * 90), "/", $domain);
			storeRememberMeCookie($conn, $_SESSION["useruid"], $cookie);
		}
		session_write_close();
		mysqli_close($conn);

		return true;
  }
  mysqli_close($conn);
  return false;
}

function IsBanned($username, $forceRefresh = false) {
	if (!isset($_SESSION["isBanned"]) || $forceRefresh) {
		$userData = LoadUserData($username);
		$_SESSION["isBanned"] = $userData["isBanned"];
	}
	return intval($_SESSION["isBanned"]) == 1 || IsBannedIP();
}

// Get the user's IP address
function getUserIP() {
  // Check for IP from Cloudflare
  if (!empty($SERVER['CF-Connecting-IP'])) {
    $ip = $_SERVER['CF-Connecting-IP'];
  }
  // Check for IP from shared internet
  elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
    $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
  }
  // Check for proxy forwarded IP
  elseif (!empty($_SERVER['HTTP_CLIENT_IP'])) {
      $ip = $_SERVER['HTTP_CLIENT_IP'];
  }
  // Get the standard remote address
  else {
      $ip = $_SERVER['REMOTE_ADDR'];
  }
  return $ip;
}

function IsBannedIP() {
  $ip = getUserIP();
  $bannedIPs = file(__DIR__ . "/../HostFiles/bannedIPs.txt", FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
  foreach ($bannedIPs as $bannedIP) {
    if (trim($bannedIP) == $ip) {
      return true;
    }
  }
  return false;
}

function logUserIP() {
  $watchFor = [
    "Bulwark",
    "wonderwonder",
    "wonderbread",
    "wonderbreader",
    "johhony",
    "jelyroll"
  ];
  // Check if the user is Brubraz and log their IP if so
  if (isset($_SESSION["useruid"])
      && in_array($_SESSION["useruid"], $watchFor)) {
    $ip = getUserIP();
    $timestamp = date('Y-m-d H:i:s');
    $gameInfo = "Username: " . $_SESSION["useruid"] . " - IP: " . $ip;
    $logEntry = $timestamp . " - " . $gameInfo . "\n";

    // Write to a log file in the root directory
    $logFile = "user_ip_log.txt";
    file_put_contents($logFile, $logEntry, FILE_APPEND);
  }
}

function AttemptPasswordLogin($username, $password, $rememberMe) {
	$conn = GetLocalMySQLConnection();
	$userData = LoadUserData($username);

  if (!$userData){
		header("location: ../LoginPage.php?error=invalidlogin");
		exit();
  }

  try {
  	$passwordValid = password_verify($password, $userData["usersPwd"]);
  }
  catch (\Exception $e) { }

  if($passwordValid)
  {
    session_start();
		$_SESSION["userid"] = $userData["usersId"];
		$_SESSION["useruid"] = $userData["usersUid"];
		$_SESSION["useremail"] = $userData["usersEmail"];
		$_SESSION["userspwd"] = $userData["usersPwd"];
		$patreonAccessToken = $userData["patreonAccessToken"];
		$_SESSION["patreonEnum"] = $userData["patreonEnum"];
		$rememberMeToken = $userData["rememberMeToken"];
		$_SESSION["isBanned"] = $userData["isBanned"];

		try {
			PatreonLogin($patreonAccessToken);
		} catch (\Exception $e) { }

		if($rememberMe)
		{
			if($rememberMeToken == "")
			{
				$cookie = hash("sha256", rand() . $_SESSION["userspwd"] . rand());
				storeRememberMeCookie($conn, $_SESSION["useruid"], $cookie);
			}
			else $cookie = $rememberMeToken;
      $domain = (!empty(getenv("DOMAIN")) ? getenv("DOMAIN") : "petranaki.net");
			setcookie("rememberMeToken", $cookie, time() + (86400 * 90), "/", $domain);
		}
		session_write_close();

		header("location: ../MainMenu.php");
		exit();
  }
  else {
    header("location: ../LoginPage.php?error=invalidlogin");
    exit();
  }
}

function storeRememberMeCookie($conn, $uuid, $cookie)
{
  $sql = "UPDATE users SET rememberMeToken=? WHERE usersUid=?";
	$stmt = mysqli_stmt_init($conn);
	if (mysqli_stmt_prepare($stmt, $sql)) {
		mysqli_stmt_bind_param($stmt, "ss", $cookie, $uuid);
		mysqli_stmt_execute($stmt);
		mysqli_stmt_close($stmt);
	}
}

 ?>
