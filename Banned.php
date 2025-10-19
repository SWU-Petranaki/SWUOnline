<?php
ob_start();
include "HostFiles/Redirector.php";
include "Libraries/HTTPLibraries.php";
include_once "./AccountFiles/AccountDatabaseAPI.php";
ob_end_clean();

session_start();


?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>You Have Been Banned</title>
  <link rel="stylesheet" href="./css/petranaki251019.css">
  <link href="https://fonts.googleapis.com/css2?family=Barlow:wght@400;700&display=swap" rel="stylesheet">
  <style>
    body {
      background-size: cover;
      background-position: center;
      background-image: url('./Images/gamebg.jpg');
      width: 100%;
      min-height: 100vh;
      margin: 0;
      background-repeat: no-repeat;
      background-attachment: fixed;
    }
    .ban-container {
      text-align: center;
      max-width: 800px;
      margin: 0 auto;
      padding: 20px;

      p {
        font-size: 1.25em;
      }

      h1 {
        font-size: 2em;
      }
    }
    .ban-image {
      max-width: 400px;
      margin: 24px auto;
    }
    .ban-text {
      font-size: 1em;
      line-height: 1.6;
      margin: 20px 0;

      p {
        font-size: 1.25em;
      }
    }
    .quote {
      font-style: italic;
      color: #666;
      font-size: 1.25em;
      margin: 30px 0;
    }
    .april-fools {
      display: none;
      text-align: center;
      max-width: 800px;
      margin: 0 auto;
      padding: 20px;
    }
    .april-fools h1 {
      font-size: 3em;
      color: #FFD700;
      margin-bottom: 30px;
    }
    .april-fools p {
      font-size: 1.25em;
      max-width: 800px;
      margin: 20px auto;
    }
    .april-fools img {
      margin: 20px auto;
    }
    .button {
      padding: 10px 20px;
      margin-top: 20px;
      font-size: 1.2em;
      background-color: #ff3333;
      color: white;
      border: none;
      border-radius: 5px;
      cursor: pointer;
      transition: background-color 0.3s;
    }
    .button:hover {
      background-color: #cc0000;
    }
    .return-button {
      background-color: #FFD700;
      color: black;
    }
    .return-button:hover {
      background-color: #ccac00;
    }
  </style>
</head>
<body>
  <div class="ban-container" id="ban-message">
    <h1 style="margin-top: 80px; margin-bottom: 20px; color: #ff3333; font-size: 2.5em;">You Have Been Banned</h1>
    <img src="./Images/banned-vader.webp" alt="Darth Vader" height="200px" width="300px" class="ban-image" style="border-radius: 50%; box-shadow: 0 0 20px 5px rgba(255, 0, 0, 0.3); filter: drop-shadow(0 0 10px rgba(255, 0, 0, 0.5)); object-fit: cover;">
    <div class="ban-text">
      <p>"I find your lack of fair play disturbing."</p>
      <p>You have been banned from the game for violating our community guidelines.</p>
      <p>Like the Rebel Alliance against the Empire, we stand firm against those who would disrupt the peace and harmony of our gaming community.</p>
    </div>
    <div class="quote">
      "If you strike me down in anger, I shall become more powerful than you can possibly imagine."<br>
      - Unfortunately, this doesn't apply to banned accounts
    </div>
    <p>If you believe this is an error, please contact our support team.</p>
    <p style="margin-bottom: 10px;">May the Force be with you... but our servers won't.</p>
    <button class="button" onclick="showAprilFools()">Appeal Ban</button>
  </div>

  <!-- <div class="april-fools" id="april-fools">
    <h1 style="margin-top: 80px;">APRIL FOOLS!</h1>
    <img src="./Images/banned-obi.webp" alt="Obi-Wan Kenobi" height="200px" width="300px" style="border-radius: 50%; box-shadow: 0 0 20px 5px rgba(0, 255, 0, 0.3); object-fit: cover;">
    <div class="ban-text">
      <p>"Hello there! This was just a little trick from the high ground!"</p>
      <p>Your account is perfectly fine! We just couldn't resist playing a little Jedi mind trick on you.</p>
      <p>You are a bold one for clicking that button. Now return to the game, and may the Force be with you, always!</p>
    </div>
    <div class="quote">
      "These aren't the bans you're looking for. You can go about your business. Move along."<br>
      - Obi-Wan Kenobi
    </div>
    <button class="button return-button" onclick="window.location.href='MainMenu.php'">Return to Game</button>
  </div> -->

  <script>
    function showAprilFools() {
      return;
      document.getElementById('ban-message').style.display = 'none';
      document.getElementById('april-fools').style.display = 'block';
      window.scrollTo(0, 0); // Scroll back to the top of the page

      // Set cookie to expire at the end of the day
      const expiry = new Date();
      expiry.setHours(23, 59, 59, 999); // End of today
      document.cookie = `april_fools_seen=${new Date().getFullYear()}; expires=${expiry.toUTCString()}; path=/`;
    }
  </script>
</body>
</html>
