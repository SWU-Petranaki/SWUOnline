<?php
include_once './MenuBar.php';
include_once './AccountFiles/AccountSessionAPI.php';

if (IsUserLoggedIn()) {
  header("Location: ./game/MainMenu.php");
}
?>

<?php
include_once 'Header.php';
?>

<div class="core-wrapper">
<div class="flex-padder"></div>

<div class="flex-wrapper">
  <div class="login container bg-yellow">
    <h2>Log In</h2>
    <p class="login-message">Make sure to use your username, not your email!</p>

    <form action="./AccountFiles/AttemptPasswordLogin.php" method="post" class="LoginForm">
      <label>Username</label>
      <input class="username" type="text" name="userID">
      <label>Password</label>
      <input class="password" type="password" name="password">
      <div class="remember-me">
      <input type="checkbox" checked='checked' id="rememberMe" name="rememberMe" value="rememberMe">
      <label for="rememberMe">Remember Me</label>
      </div>
      <div style="text-align:center;">
        <button type="submit" name="submit">Submit</button>
      </div>
    </form>
    <form action="ResetPassword.php" method="post" style='text-align:center;'>
      <!-- <button type="submit" name="reset-password">Forgot Password?</button> -->
    </form>

    <?php
    // Error messages
    if (isset($_GET["error"])) {
      if ($_GET["error"] == "invalidlogin") {
        echo "<h3 class='login-error-message'>Incorrect username or password.</h3>";
      }
    }
    ?>
  </div>

  <div class="container bg-yellow cookie-consent">
    <p>By using the Remember Me function, you consent to a cookie being stored in your browser for the purpose of identifying
      your account on future visits.</p>
    <a href='./MenuFiles/PrivacyPolicy.php'>Privacy Policy</a>
  </div>

</div>

<div class="flex-padder"></div>
</div>

<!-- Add custom styles to fix responsive issues -->
<style>
  /* Common styles for adequate header spacing */
  .core-wrapper {
    margin-top: 0px !important; /* Increased top margin for header space */
    min-height: calc(100vh - 200px) !important; /* Adjust to account for header and footer */
    position: relative;
    z-index: 1; /* Keep content above background, below header */
    width: 100%;
    max-width: 100%;
    box-sizing: border-box;
  }
  
  @media screen and (max-width: 768px) {
    .core-wrapper {
      margin-top: 0px !important; /* More space for mobile header */
      height: auto !important;
      min-height: calc(100vh - 220px) !important;
    }
    
    .flex-wrapper {
      flex-direction: column;
      padding: 0 10px;
      overflow-x: hidden;
    }
    
    .login.container, .cookie-consent.container {
      margin-bottom: 15px;
      width: 100%;
    }
    
    .flex-padder {
      display: none;
    }
    
    .disclaimer {
      left: 0;
      right: 0;
      border-radius: 0;
      width: 100%;
      z-index: 100;
    }
    
    .disclaimer p {
      padding: 10px 15px;
    }
  }
</style>

<?php
include_once './Disclaimer.php';
?>