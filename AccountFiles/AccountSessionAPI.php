<?php
  function IsUserLoggedIn()
  {
    CheckSession();
    return isset($_SESSION['useruid']);
  }

  function IsUserLoggedInAsMod()
  {
    if(!IsUserLoggedIn()) return false;
    $useruid = $_SESSION["useruid"];
    return $useruid === "OotTheMonk" || $useruid === "love" || $useruid === "ninin" || $useruid === "Brubraz" || $useruid === "Mobyus1";
  }

  function IsUserLoggedInAsSqlMod()
  {
    if(!IsUserLoggedIn()) return false;
    $useruid = $_SESSION["useruid"];
    return $useruid === "OotTheMonk" || $useruid === "love" || $useruid === "ninin" || $useruid === "Brubraz";
  }

  function LoggedInUser()
  {
    CheckSession();
    return $_SESSION["userid"];
  }

  function LoggedInUserName()
  {
    CheckSession();
    return $_SESSION["useruid"];
  }

  function IsLoggedInUserPatron()
  {
    return (isset($_SESSION["isPatron"]) ? "1" : "0");
  }

  function SessionLastGameName()
  {
    CheckSession();
    if(!isset($_SESSION["lastGameName"])) return "";
    return $_SESSION["lastGameName"];
  }

  function SessionLastGamePlayerID()
  {
    CheckSession();
    return $_SESSION["lastPlayerId"];
  }

  function SessionLastAuthKey()
  {
    CheckSession();
    return $_SESSION["lastAuthKey"];
  }

  function ClearLoginSession()
  {
    //First clear the session
    session_start();
    session_unset();
    session_destroy();

    $domain = (!empty(getenv("DOMAIN")) ? getenv("DOMAIN") : "petranaki.net");
    //Also delete cookies
    if (isset($_COOKIE["rememberMeToken"])) setcookie("rememberMeToken", "", time() + 1, "/", $domain);
    if (isset($_COOKIE["lastAuthKey"])) setcookie("lastAuthKey", "", time() + 1, "/", $domain);
  }

  function CheckSession()
  {
    if (session_status() === PHP_SESSION_NONE) {
      session_start();
    }
  }
?>
