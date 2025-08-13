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
    <link rel="stylesheet" href="css/petranaki250812.css">
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
      /* Desktop nav-bar styles (default) */
      .nav-bar {
        position: absolute;
        right: 0;
        top: 10px;
        font-size: 18px;
        font-weight: 600;
        z-index: 100;
        width: auto;
        display: flex;
        flex-direction: row;
        align-items: center;
        background: none;
      }
      .nav-bar-user, .nav-bar-links, .nav-bar-karabast {
        display: flex;
        align-items: center;
      }
      .nav-bar-karabast {
        margin-left: 16px;
      }
      .menu-toggle, .menu-overlay, .mobile-menu-panel, .mobile-karabast {
        display: none;
      }
      /* Mobile overrides */
      @media screen and (max-width: 768px) {
        .nav-bar {
          position: relative;
          width: 100%;
          padding: 10px;
          align-items: stretch;
          background: none !important;
        }
        .menu-toggle {
          display: block;
          position: absolute;
          top: 0px;
          right: 10px;
          z-index: 10100;
          margin-right: 15px;
          font-size: 30px;
          background-color: rgba(90, 70, 30, 0.7); /* Add background shade */
          color: white; /* Ensure the text/icon is visible */
          padding: 3px 7px; /* Add proportional padding: 3px top/bottom, 6px left/right */
          border-radius: 4px; /* Optional: Add rounded corners */
        }
        .nav-bar-user, .nav-bar-links, .nav-bar-karabast {
          display: none !important;
        }
        .menu-overlay {
          display: none;
          position: fixed;
          top: 0;
          left: 0;
          width: 100vw;
          height: 100vh;
          background: rgba(0,0,0,0.4);
          z-index: 10000;
        }
        .menu-overlay.show {
          display: block;
        }
        .mobile-menu-panel {
          display: none;
          position: fixed;
          top: 56px;
          left: 0;
          right: 0;
          margin: 0 auto;
          width: 95vw;
          max-width: 400px;
          background-color: rgba(50, 40, 20, 0.97);
          backdrop-filter: blur(10px);
          z-index: 11000;
          border-radius: 0 0 16px 16px;
          box-shadow: 0 4px 16px rgba(0,0,0,0.25);
          overflow-y: auto;
        }
        .mobile-menu-panel.show {
          display: block;
        }
        .mobile-menu-panel ul {
          list-style: none;
          padding: 0;
          margin: 0;
        }
        .mobile-menu-panel li {
          width: 100%;
          text-align: center;
          padding: 10px 0;
          border-bottom: 1px solid rgba(220, 200, 160, 0.2);
        }
        .mobile-karabast {
          display: block;
          width: 100%;
          text-align: center;
          padding: 10px 0;
          border-top: 1px solid rgba(220, 200, 160, 0.2);
        }
      }
    </style>
    <!-- Desktop nav-bar (default) -->
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
                        <li><a href='$baseUri/Conduct.php'>Code of Conduct</a></li>
                      </ul>
                    </li>";
                    echo "<li class='dropdown'>
                      <a href='javascript:void(0)' onclick='toggleToolsNav()' class='NavBarItem info-nav'>Tools/Apps <span id='nav-tri-tools' class='nav-triangle'>▼</span></a>
                      <ul id='tools-dd' class='dropdown-content'>
                        <li><a href='$baseUri/Tools/OnePlayerMode.php'>One Player Mode (Beta)</a></li>
                        <li><a href='$baseUri/Tools/ShuffleIntegrity.php'>Shuffle Integrity</a></li>
                        <li><a href='$baseUri/Tools/TCGPMEConverter.php'>TCGP ME Converter</a></li>
                      </ul>
                    </li>";
                    echo "<li><a href='https://swustats.net/TCGEngine/SharedUI/MainMenu.php' target='_blank' class='NavBarItem'>SWU Stats</a></li>";
                    echo "<li><a href='$baseUri/PreviewCards.php'>Preview Cards</a></li>";
                    echo "<li><a href='$baseUri/ProfilePage.php' class='NavBarItem'>Profile</a></li>";
                    echo "<li><a href='$baseUri/AccountFiles/LogoutUser.php' class='NavBarItem'>Log Out</a></li>";
                } else {
                    echo "<li><a href='$baseUri/Signup.php' class='NavBarItem'>Sign Up</a></li>";
                    echo "<li><a href='$baseUri/LoginPage.php' class='NavBarItem'>Log In</a></li>";
                }
                ?>
            </ul>
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
            Looking for&nbsp;<a href="https://karabast.net">Karabast</a>?
        </div>
    </div>
    <!-- Mobile overlay and menu panel (mobile only) -->
    <div class="menu-overlay" id="menuOverlay" onclick="toggleMobileMenu()"></div>
    <div class="mobile-menu-panel" id="mobileMenuPanel">
        <ul>
            <?php
            if (isset($_SESSION["useruid"])) {
                echo "<li class='dropdown'>
                  <a href='javascript:void(0)' onclick='toggleInfoNavMobile()' class='NavBarItem info-nav'>Info <span id='nav-tri-info-mobile' class='nav-triangle'>▼</span></a>
                  <ul id='info-dd-mobile' class='dropdown-content'>
                    <li><a href='$baseUri/CantinaBrawl.php'>Cantina Brawl</a></li>"
                    . "<li><a href='$baseUri/PreviewCards.php'>Preview Cards</a></li>"
                    . "<li><a href='$baseUri/Conduct.php'>Code of Conduct</a></li>
                  </ul>
                </li>";
                echo "<li class='dropdown'>
                  <a href='javascript:void(0)' onclick='toggleToolsNavMobile()' class='NavBarItem info-nav'>Tools/Apps <span id='nav-tri-tools-mobile' class='nav-triangle'>▼</span></a>
                  <ul id='tools-dd-mobile' class='dropdown-content'>
                    <li><a href='$baseUri/Tools/OnePlayerMode.php'>One Player Mode (Beta)</a></li>
                    <li><a href='$baseUri/Tools/ShuffleIntegrity.php'>Shuffle Integrity</a></li>
                    <li><a href='$baseUri/Tools/TCGPMEConverter.php'>TCGP ME Converter</a></li>
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
            <!-- Add icon links to mobile menu -->
            <li style="display: flex; justify-content: center; gap: 18px; border-bottom: none; padding-top: 12px;">
                <a target="_blank" href="https://discord.gg/ep9fj8Vj3F"><img src="<?=$baseUri?>/Images/icons/discord.svg" alt="Discord" style="width:28px;height:28px;"></a>
                <a target="_blank" href="https://github.com/SWU-Petranaki/SWUOnline"><img src="<?=$baseUri?>/Images/icons/github.svg" alt="GitHub" style="width:28px;height:28px;"></a>
                <a href="javascript:void(0);" onclick="toggleLanguagesMobile()"><img src="<?=$baseUri?>/Images/icons/globe.svg" alt="Languages" style="width:28px;height:28px;"></a>
            </li>
            <ul id="languageListMobile" style="display: none; background: rgba(50,40,20,0.97); border-radius: 0 0 16px 16px;">
                <?php
                foreach ($languages as $code => $lang) {
                    echo "<li onclick=\"setLanguage('$code')\"><img src='$baseUri/Images/icons/$code.svg' alt='$lang' class='language-icon'>   $lang</li>";
                }
                ?>
            </ul>
        </ul>
        <div class="mobile-karabast">
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
        function toggleLanguagesMobile() {
            var languageList = document.getElementById("languageListMobile");
            if (languageList.style.display === "none" || languageList.style.display === "") {
                languageList.style.display = "block";
            } else {
                languageList.style.display = "none";
            }
        }
        function setLanguage(langCode) {
            document.cookie = "selectedLanguage=" + langCode + "; path=/";
            location.reload();
        }
        function toggleMobileMenu() {
            var overlay = document.getElementById('menuOverlay');
            var panel = document.getElementById('mobileMenuPanel');
            overlay.classList.toggle('show');
            panel.classList.toggle('show');
        }

        function closeAllDropdowns() {
            const dropdowns = ['#info-dd', '#tools-dd'];
            const triangles = ['#nav-tri-info', '#nav-tri-tools'];
            dropdowns.forEach((dd, i) => {
          document.querySelector(dd).style.display = 'none';
          if (document.querySelector(triangles[i])) {
              document.querySelector(triangles[i]).innerHTML = '▼';
          }
            });
        }

        function toggleInfoNav() {
            var dropdownContent = document.querySelector('#info-dd');
            var isOpen = dropdownContent.style.display === 'block';
            closeAllDropdowns();
            if (!isOpen) {
          dropdownContent.style.display = 'block';
          var triangle = document.querySelector('#nav-tri-info');
          if (triangle) triangle.innerHTML = '▲';
            }
        }

        function toggleToolsNav() {
            var dropdownContent = document.querySelector('#tools-dd');
            var isOpen = dropdownContent.style.display === 'block';
            closeAllDropdowns();
            if (!isOpen) {
          dropdownContent.style.display = 'block';
          var triangle = document.querySelector('#nav-tri-tools');
          if (triangle) triangle.innerHTML = '▲';
            }
        }

        function closeAllDropdownsMobile() {
            const dropdowns = ['#info-dd-mobile', '#tools-dd-mobile'];
            const triangles = ['#nav-tri-info-mobile', '#nav-tri-tools-mobile'];
            dropdowns.forEach((dd, i) => {
          document.querySelector(dd).style.display = 'none';
          if (document.querySelector(triangles[i])) {
              document.querySelector(triangles[i]).innerHTML = '▼';
          }
            });
        }

        function toggleInfoNavMobile() {
            var dropdownContent = document.querySelector('#info-dd-mobile');
            var isOpen = dropdownContent.style.display === 'block';
            closeAllDropdownsMobile();
            if (!isOpen) {
          dropdownContent.style.display = 'block';
          var triangle = document.querySelector('#nav-tri-info-mobile');
          if (triangle) triangle.innerHTML = '▲';
            }
        }

        function toggleToolsNavMobile() {
            var dropdownContent = document.querySelector('#tools-dd-mobile');
            var isOpen = dropdownContent.style.display === 'block';
            closeAllDropdownsMobile();
            if (!isOpen) {
          dropdownContent.style.display = 'block';
          var triangle = document.querySelector('#nav-tri-tools-mobile');
          if (triangle) triangle.innerHTML = '▲';
            }
        }
    </script>
</body>