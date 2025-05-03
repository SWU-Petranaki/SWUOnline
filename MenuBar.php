<?php

include_once 'Assets/patreon-php-master/src/OAuth.php';
include_once 'Assets/patreon-php-master/src/API.php';
include_once 'Assets/patreon-php-master/src/PatreonLibraries.php';
include_once 'Assets/patreon-php-master/src/PatreonDictionary.php';
include_once 'includes/functions.inc.php';
include_once 'includes/dbh.inc.php';
include_once 'Libraries/HTTPLibraries.php';
include_once 'HostFiles/Redirector.php';
session_start();

if (!isset($_SESSION["userid"])) {
    if (isset($_COOKIE["rememberMeToken"])) {
        loginFromCookie();
    }
}

$isPatron = isset($_SESSION["isPatron"]);
$isMobile = IsMobile();
$baseUri = "/Arena";
?>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Petranaki</title>
    <link rel="icon" type="image/png" href="Images/favicon-96x96.png" sizes="96x96" />
    <link rel="icon" type="image/svg+xml" href="Images/favicon.svg" />
    <link rel="shortcut icon" href="Images/favicon.ico" />
    <link rel="apple-touch-icon" sizes="180x180" href="Images/apple-touch-icon.png" />
    <link rel="manifest" href="site.webmanifest" />
    <link rel="stylesheet" href="css/petranaki250320.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Barlow:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap"
        rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Teko:wght@700&display=swap" rel="stylesheet">
</head>

<body>
    <style>
      a.info-nav:hover {
        text-decoration: none;
      }
      span.nav-triangle {
        font-size: 0.7rem;
        margin-left: 0.2rem;
      }
      li.dropdown {
          position: relative;
          display: inline-block;
      }
      ul.dropdown-content {
        display: none;
        position: absolute;
        left: -1rem;
        top: 1.5rem;
        background-color: rgba(90, 70, 30, 0.7);
        min-width: 160px;
        box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2);
        z-index: 150;
      }
      .dropdown-content li {
        list-style: none;
        padding: 0;
        margin: 0;
        backdrop-filter: blur(16px);
      }
      .dropdown-content a {
        padding: 12px 16px;
        text-decoration: none;
        display: block;
        font-size: 1rem;
      }
      .dropdown-content a:hover {
        background-color: rgba(90, 70, 30, 0.9);
        width: 100%;
        color: rgba(220, 200, 160, 1);
      }
      
      /* Mobile menu styles */
      .menu-toggle {
        display: none;
        cursor: pointer;
        font-size: 1.5rem;
        padding: 5px 10px;
        background-color: rgba(90, 70, 30, 0.7);
        border-radius: 5px;
        color: rgba(220, 200, 160, 1);
      }
      
      /* Mobile karabast link styling */
      .mobile-karabast {
        display: none;
        width: 100%;
        text-align: center;
        padding: 10px 0;
        border-top: 1px solid rgba(220, 200, 160, 0.2);
      }
      
      .mobile-karabast a {
        color: rgba(220, 200, 160, 1);
        text-decoration: underline;
      }
      
      .mobile-karabast a:hover {
        color: rgba(240, 220, 180, 1);
      }
      
      .nav-bar {
        position: absolute;
        right: 0;
        top: 10px;
        font-size: 18px;
        font-weight: 600;
        z-index: 100;
        width: auto; /* Only as wide as needed on desktop */
        display: flex;
        flex-direction: column;
        align-items: flex-end;
      }
      
      /* Responsive styles */
      @media screen and (max-width: 768px) {
        .menu-toggle {
          display: block;
          position: absolute;
          top: 10px;
          right: 10px;
          z-index: 1000;
        }
        
        .nav-bar {
          position: relative;
          width: 100%; /* Full width on mobile */
          padding: 10px;
          align-items: stretch;
        }
        
        .nav-bar-user {
          display: none;
          width: 100%;
          background-color: rgba(50, 40, 20, 0.95);
          backdrop-filter: blur(10px);
          position: absolute;
          top: 40px;
          left: 0;
          z-index: 999;
          border-bottom: 1px solid rgba(220, 200, 160, 0.3);
        }
        
        .nav-bar-links {
          display: none;
          width: 100%;
          margin-top: 5px;
        }
        
        .mobile-karabast {
          display: block;
        }
        
        .nav-bar-user.show, .nav-bar-links.show {
          display: block;
        }
        
        .nav-bar-user ul.rightnav {
          display: flex;
          flex-direction: column;
          width: 100%;
          padding: 0;
          margin: 0;
        }
        
        .nav-bar-user ul.rightnav li {
          width: 100%;
          text-align: center;
          padding: 10px 0;
          border-bottom: 1px solid rgba(220, 200, 160, 0.2);
        }
        
        .nav-bar-links ul {
          display: flex;
          flex-direction: row;
          justify-content: center;
          width: 100%;
          padding: 0;
          margin: 10px 0 0 0;
        }
        
        .nav-bar-links ul li {
          margin: 0 15px;
        }
        
        ul.dropdown-content {
          position: static;
          width: 100%;
          box-shadow: none;
          margin-top: 10px;
        }
        
        .nav-bar-karabast {
          text-align: center;
          padding: 10px;
          margin-top: 10px;
        }
      }
    </style>
    
    <div class='nav-bar' style="display: block;">
          <div class="menu-toggle" onclick="toggleMobileMenu()">&#9776;</div>
          
          <div class='nav-bar-user'>
              <ul class='rightnav'>
                  <?php
                  if (isset($_SESSION["useruid"])) {
                      echo "<li class='dropdown'>
                        <a href='javascript:void(0)' onclick='toggleInfoNav()' class='NavBarItem info-nav'>Info <span id='nav-tri-info' class='nav-triangle'>▼</span></a>
                        <ul id='info-dd' class='dropdown-content'>
                          <li><a href='$baseUri/CantinaBrawl.php'>Cantina Brawl</a></li>
                          <li><a href='$baseUri/UnimplementedCards.php'>Preview Cards</a></li>
                          <li><a href='$baseUri/Conduct.php'>Code of Conduct</a></li>
                        </ul>
                      </li>";
                      echo "<li class='dropdown'>
                        <a href='javascript:void(0)' onclick='toggleToolsNav()' class='NavBarItem info-nav'>Tools <span id='nav-tri-tools' class='nav-triangle'>▼</span></a>
                        <ul id='tools-dd' class='dropdown-content'>
                          <li><a href='$baseUri/Tools/ShuffleIntegrity.php'>Shuffle Integrity</a></li>
                          <li><a href='$baseUri/Tools/MeleeToJson.php'>Melee To JSON</a></li>
                        </ul>
                      </li>";
                      echo "<li><a href='https://swustats.net/TCGEngine/SharedUI/MainMenu.php' target='_blank' class='NavBarItem'>SWU Stats</a></li>";
                      echo "<li><a href='$baseUri/ProfilePage.php' class='NavBarItem'>Profile</a></li>";
                      echo "<li><a href='$baseUri/AccountFiles/LogoutUser.php' class='NavBarItem'>Log Out</a></li>";
                  } else {
                      echo "<li><a href='$baseUri/Signup.php' class='NavBarItem'>Sign Up</a></li>";
                      echo "<li><a href='$baseUri/LoginPage.php' class='NavBarItem'>Log In</a></li>";
                  }
                  ?>
              </ul>
              <!-- Add Karabast link to mobile menu -->
              <div class="mobile-karabast">
                Looking for <a href="https://karabast.net">Karabast</a>?
              </div>
          </div>

          <div class='nav-bar-links'>
              <ul>
                  <?php
                  echo "<li><a target='_blank' href='https://discord.gg/ep9fj8Vj3F'><img src='$baseUri/Images/icons/discord.svg' alt='Discord'></a></li>";
                  echo "<li><a target='_blank' href='https://github.com/SWU-Petranaki/SWUOnline'><img src='$baseUri/Images/icons/github.svg' alt='GitHub'></a></li>";
                  echo "<li>
                            <a href='javascript:void(0);' onclick='toggleLanguages()'>
                              <img src='$baseUri/Images/icons/globe.svg' alt='Languages'>
                            </a>
                            <ul id='languageList' style='display: none;'>";

                  $languages = [
                      'EN' => 'English',
                      'DE' => 'German',
                      'FR' => 'French',
                      'ES' => 'Spanish',
                      'IT' => 'Italian',
                  ];

                  foreach ($languages as $code => $lang) {
                      echo "<li onclick=\"setLanguage('$code')\"><img src='$baseUri/Images/icons/$code.svg' alt='$lang' class='language-icon'>   $lang</li>";
                  }

                  echo '</ul>
              </li>';
                  ?>
              </ul>
          </div>
        <div class="nav-bar-karabast" style="z-index: 10;">
            Looking for <a href="https://karabast.net">Karabast</a>?
        </div>
    </div>


    <script>
        function toggleLanguages() {
            var languageList = document.getElementById("languageList");
            if (languageList.style.display === "none" || languageList.style.display === "") {
                languageList.style.display = "block";
            } else {
                languageList.style.display = "none";
            }
        }

        function setLanguage(langCode) {
            console.log("Selected language: " + langCode); // Log the selected language
            document.cookie = "selectedLanguage=" + langCode + "; path=/";
            location.reload();
        }
        
        function toggleMobileMenu() {
            var userNav = document.querySelector('.nav-bar-user');
            var linksNav = document.querySelector('.nav-bar-links');
            
            userNav.classList.toggle('show');
            linksNav.classList.toggle('show');
        }
        
        function toggleInfoNav() {
            var dropdownContent = document.querySelector('#info-dd');
            if (dropdownContent.style.display === 'block') {
                dropdownContent.style.display = 'none';
            } else {
                dropdownContent.style.display = 'block';
            }
            var triangle = document.querySelector('#nav-tri-info');
            if (dropdownContent.style.display === 'block') {
              triangle.innerHTML = '▲';
            } else {
              triangle.innerHTML = '▼';
            }
        }
        
        function toggleToolsNav() {
            var dropdownContent = document.querySelector('#tools-dd');
            if (dropdownContent.style.display === 'block') {
                dropdownContent.style.display = 'none';
            } else {
                dropdownContent.style.display = 'block';
            }
            var triangle = document.querySelector('#nav-tri-tools');
            if (dropdownContent.style.display === 'block') {
              triangle.innerHTML = '▲';
            } else {
              triangle.innerHTML = '▼';
            }
        }
    </script>
</body>