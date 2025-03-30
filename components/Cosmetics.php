<?php
function CardbacksDropdowns($settings) {
  global $SET_Cardback;
  $rv = "";
  $rv .= CreateSelectOption($SET_Cardback . "-" . 0, "", $SET_Cardback . "-" . $settings[$SET_Cardback]);
  $rv .= CreateSelectOption($SET_Cardback . "-" . 0, "Default", $SET_Cardback . "-" . $settings[$SET_Cardback]);
  $rv .= CreateSelectOption($SET_Cardback . "-" . 3, "Rebel Resource", $SET_Cardback . "-" . $settings[$SET_Cardback]);
  $rv .= CreateSelectOption($SET_Cardback . "-" . 4, "Rebel Resource Dark", $SET_Cardback . "-" . $settings[$SET_Cardback]);
  $rv .= CreateSelectOption($SET_Cardback . "-" . 5, "Golden Dice Podcast", $SET_Cardback . "-" . $settings[$SET_Cardback]);
  $rv .= CreateSelectOption($SET_Cardback . "-" . 6, "L8 Night Gaming", $SET_Cardback . "-" . $settings[$SET_Cardback]);
  $rv .= CreateSelectOption($SET_Cardback . "-" . 7, "Mobyus1 Simple", $SET_Cardback . "-" . $settings[$SET_Cardback]);
  $rv .= CreateSelectOption($SET_Cardback . "-" . 8, "Mobyus1 Titled", $SET_Cardback . "-" . $settings[$SET_Cardback]);
  $rv .= CreateSelectOption($SET_Cardback . "-" . 9, "Outmaneuver", $SET_Cardback . "-" . $settings[$SET_Cardback]);
  $rv .= CreateSelectOption($SET_Cardback . "-" . 10, "Bothan Network", $SET_Cardback . "-" . $settings[$SET_Cardback]);
  $rv .= CreateSelectOption($SET_Cardback . "-" . 11, "Padawan Unlimited", $SET_Cardback . "-" . $settings[$SET_Cardback]);
  $rv .= CreateSelectOption($SET_Cardback . "-" . 12, "RVA SWU", $SET_Cardback . "-" . $settings[$SET_Cardback]);
  $rv .= CreateSelectOption($SET_Cardback . "-" . 13, "Baddest Batch", $SET_Cardback . "-" . $settings[$SET_Cardback]);
  $rv .= CreateSelectOption($SET_Cardback . "-" . 15, "Holocron Card Hub", $SET_Cardback . "-" . $settings[$SET_Cardback]);
  $rv .= CreateSelectOption($SET_Cardback . "-" . 16, "Maclunky Gaming", $SET_Cardback . "-" . $settings[$SET_Cardback]);
  $rv .= CreateSelectOption($SET_Cardback . "-" . 17, "The Cantina Crew", $SET_Cardback . "-" . $settings[$SET_Cardback]);
  $rv .= CreateSelectOption($SET_Cardback . "-" . 18, "Rajeux TCG", $SET_Cardback . "-" . $settings[$SET_Cardback]);
  $rv .= CreateSelectOption($SET_Cardback . "-" . 19, "Under The Twin Suns", $SET_Cardback . "-" . $settings[$SET_Cardback]);
  $rv .= CreateSelectOption($SET_Cardback . "-" . 20, "Too Many Hans", $SET_Cardback . "-" . $settings[$SET_Cardback]);
  $rv .= CreateSelectOption($SET_Cardback . "-" . 21, "Porg Depot", $SET_Cardback . "-" . $settings[$SET_Cardback]);
  $rv .= CreateSelectOption($SET_Cardback . "-" . 22, "Darth Players", $SET_Cardback . "-" . $settings[$SET_Cardback]);
  $rv .= CreateSelectOption($SET_Cardback . "-" . 23, "Mainedalorians", $SET_Cardback . "-" . $settings[$SET_Cardback]);
  $rv .= CreateSelectOption($SET_Cardback . "-" . 24, "Galactic Gonks", $SET_Cardback . "-" . $settings[$SET_Cardback]);
  $rv .= CreateSelectOption($SET_Cardback . "-" . 25, "Fallen Order", $SET_Cardback . "-" . $settings[$SET_Cardback]);
  $rv .= CreateSelectOption($SET_Cardback . "-" . 26, "Mythic Force", $SET_Cardback . "-" . $settings[$SET_Cardback]);
  $rv .= CreateSelectOption($SET_Cardback . "-" . 27, "MoG TCG", $SET_Cardback . "-" . $settings[$SET_Cardback]);
  $rv .= CreateSelectOption($SET_Cardback . "-" . 28, "SWCGR", $SET_Cardback . "-" . $settings[$SET_Cardback]);
  $rv .= CreateSelectOption($SET_Cardback . "-" . 29, "SWU VIC", $SET_Cardback . "-" . $settings[$SET_Cardback]);
  $rv .= CreateSelectOption($SET_Cardback . "-" . 30, "GonkGang", $SET_Cardback . "-" . $settings[$SET_Cardback]);
  $rv .= CreateSelectOption($SET_Cardback . "-" . 31, "Galactic Shuffle", $SET_Cardback . "-" . $settings[$SET_Cardback]);
  $rv .= CreateSelectOption($SET_Cardback . "-" . 32, "Tropa do Boba", $SET_Cardback . "-" . $settings[$SET_Cardback]);
  $rv .= CreateSelectOption($SET_Cardback . "-" . 33, "Outer Rim CCG", $SET_Cardback . "-" . $settings[$SET_Cardback]);
  $rv .= CreateSelectOption($SET_Cardback . "-" . 34, "Central Spacers", $SET_Cardback . "-" . $settings[$SET_Cardback]);
  $rv .= CreateSelectOption($SET_Cardback . "-" . 35, "Enigma", $SET_Cardback . "-" . $settings[$SET_Cardback]);
  $rv .= CreateSelectOption($SET_Cardback . "-" . 36, "PrairiePirates", $SET_Cardback . "-" . $settings[$SET_Cardback]);
  $rv .= CreateSelectOption($SET_Cardback . "-" . 37, "Colorado Cantina Crew", $SET_Cardback . "-" . $settings[$SET_Cardback]);
  $rv .= CreateSelectOption($SET_Cardback . "-" . 39, "The Nordic Takedown", $SET_Cardback . "-" . $settings[$SET_Cardback]);
  $rv .= CreateSelectOption($SET_Cardback . "-" . 40, "Sekrit", $SET_Cardback . "-" . $settings[$SET_Cardback]);
  $rv .= CreateSelectOption($SET_Cardback . "-" . 41, "C4", $SET_Cardback . "-" . $settings[$SET_Cardback]);
  $rv .= CreateSelectOption($SET_Cardback . "-" . 42, "Golden Eagle Gaming", $SET_Cardback . "-" . $settings[$SET_Cardback]);
  $rv .= CreateSelectOption($SET_Cardback . "-" . 43, "SWU NZ", $SET_Cardback . "-" . $settings[$SET_Cardback]);
  return $rv;
}

function GameBackgroundDropdowns($settings) {
  global $SET_Background;
  $rv = "";
  $rv .= CreateSelectOption($SET_Background . "-" . 0, "Default", $SET_Background . "-" . $settings[$SET_Background]);
  $rv .= CreateSelectOption($SET_Background . "-" . 1, "Death Star", $SET_Background . "-" . $settings[$SET_Background]);
  $rv .= CreateSelectOption($SET_Background . "-" . 2, "Echo Base", $SET_Background . "-" . $settings[$SET_Background]);
  $rv .= CreateSelectOption($SET_Background . "-" . 3, "AT-AT Sand", $SET_Background . "-" . $settings[$SET_Background]);
  $rv .= CreateSelectOption($SET_Background . "-" . 4, "Overwhelming Barrage", $SET_Background . "-" . $settings[$SET_Background]);
  $rv .= CreateSelectOption($SET_Background . "-" . 5, "The Darksaber", $SET_Background . "-" . $settings[$SET_Background]);
  $rv .= CreateSelectOption($SET_Background . "-" . 6, "Space/Ground Battlefield 1", $SET_Background . "-" . $settings[$SET_Background]);
  $rv .= CreateSelectOption($SET_Background . "-" . 7, "Yoda TWI", $SET_Background . "-" . $settings[$SET_Background]);
  $rv .= CreateSelectOption($SET_Background . "-" . 8, "Ahsoka Sabers", $SET_Background . "-" . $settings[$SET_Background]);
  $rv .= CreateSelectOption($SET_Background . "-" . 9, "Capital Ship JTL", $SET_Background . "-" . $settings[$SET_Background]);
  $rv .= CreateSelectOption($SET_Background . "-" . 10, "Boba Daimyo", $SET_Background . "-" . $settings[$SET_Background]);
  $rv .= CreateSelectOption($SET_Background . "-" . 11, "Fett's Firespray", $SET_Background . "-" . $settings[$SET_Background]);
  $rv .= CreateSelectOption($SET_Background . "-" . 12, "Din Grogu", $SET_Background . "-" . $settings[$SET_Background]);
  $rv .= CreateSelectOption($SET_Background . "-" . 13, "High Ground", $SET_Background . "-" . $settings[$SET_Background]);
  $rv .= CreateSelectOption($SET_Background . "-" . 14, "Keep Fighting", $SET_Background . "-" . $settings[$SET_Background]);
  $rv .= CreateSelectOption($SET_Background . "-" . 15, "R2-D2", $SET_Background . "-" . $settings[$SET_Background]);
  $rv .= CreateSelectOption($SET_Background . "-" . 101, "SOR Starfield", $SET_Background . "-" . $settings[$SET_Background]);
  $rv .= CreateSelectOption($SET_Background . "-" . 102, "SHD Starfield", $SET_Background . "-" . $settings[$SET_Background]);
  $rv .= CreateSelectOption($SET_Background . "-" . 103, "TWI Starfield", $SET_Background . "-" . $settings[$SET_Background]);
  $rv .= CreateSelectOption($SET_Background . "-" . 104, "JTL Starfield", $SET_Background . "-" . $settings[$SET_Background]);
  $rv .= CreateSelectOption($SET_Background . "-" . 201, "SOR Artwork", $SET_Background . "-" . $settings[$SET_Background]);
  $rv .= CreateSelectOption($SET_Background . "-" . 202, "SHD Artwork", $SET_Background . "-" . $settings[$SET_Background]);
  $rv .= CreateSelectOption($SET_Background . "-" . 203, "TWI Artwork", $SET_Background . "-" . $settings[$SET_Background]);
  $rv .= CreateSelectOption($SET_Background . "-" . 204, "JTL Artwork", $SET_Background . "-" . $settings[$SET_Background]);
  return $rv;
}
?>