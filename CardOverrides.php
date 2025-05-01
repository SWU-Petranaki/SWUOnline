<?php
function CardIDOverride($cardID) {
  switch($cardID) {
    case "SHD_030": return "SOR_033"; //Death Trooper
    case "SHD_063": return "SOR_066"; //System Patrol Craft
    case "SHD_066": return "SOR_068"; //Cargo Juggernaut
    case "SHD_070": return "SOR_069"; //Resilient
    case "SHD_081": return "SOR_080"; //General Tagge
    case "SHD_085": return "SOR_083"; //Superlaser Technician
    case "SHD_083": return "SOR_081"; //Seasoned Shoretrooper
    case "SHD_166": return "SOR_162"; //Disabling Fang Fighter
    case "SHD_223": return "SOR_215"; //Snapshot Reflexes
    case "SHD_231": return "SOR_220"; //Surprise Strike
    case "SHD_236": return "SOR_227"; //Snowtrooper Lieutenant
    case "SHD_238": return "SOR_229"; //Cell Block Guard
    case "SHD_257": return "SOR_247"; //Underworld Thug
    case "SHD_262": return "SOR_251"; //Confiscate
    case "SHD_121": return "SOR_117"; //Mercenary Company
    case "TWI_077": return "SOR_078"; //Vanquish
    case "TWI_107": return "SOR_111"; //Patrolling V-Wing
    case "TWI_123": return "SHD_128"; //Outflank
    case "TWI_124": return "SOR_124"; //Tactical Advantage
    case "TWI_127": return "SOR_126"; //Resupply
    case "TWI_128": return "SHD_131"; //Take Captive
    case "TWI_170": return "SHD_178"; //Daring Raid
    case "TWI_174": return "SOR_172"; //Open Fire
    case "TWI_226": return "SOR_222"; //Waylay
    case "TWI_254": return "SOR_248"; //Volunteer Soldier
    case "C24_001": return "SOR_038"; //Count Dooku (Darth Tyranus)
    case "C24_002": return "SOR_087"; //Darth Vader (Commanding the First Legion)
    case "C24_003": return "SOR_135"; //Emperor Palpatine (Master of the Dark Side)
    case "C24_004": return "SHD_141"; //Kylo Ren (Killing the Past)
    case "C24_005": return "TWI_134"; //Asajj Ventress (Count Dooku's Assassin)
    case "C24_006": return "TWI_135"; //Darth Maul (Revenge at Last)
    case "J24_001": return "SOR_040"; //Avenger
    case "J24_002": return "SOR_145"; //K-2SO
    case "J24_003": return "SHD_037"; //Supreme Leader Snoke
    case "J24_004": return "SHD_090"; //Maul
    case "J24_005": return "SHD_154"; //Wrecker
    case "J24_006": return "SHD_248"; //Tech
    case "GG_001": return "SOR_021"; //Dagobah Swamp
    case "GG_002": return "SOR_024"; //Echo Base
    case "GG_003": return "SOR_026"; //Catacombs of Cadera
    case "GG_004": return "SOR_026"; //Jabba's Palace
    case "GG_005": return "SOR_001"; //Experience (Token Upgrade)
    case "GG_006": return "SOR_002"; //Shield (Token Upgrade)
    case "JTL_258": return "SOR_250"; //Corellian Freighter
    case "JTL_113": return "SOR_113"; //Homestead Militia
    case "JTL_167": return "SOR_165"; //Occupier Siege Tank
    case "JTL_128": return "SOR_125"; //Prepare Prepare for Takeoff
    case "JTL_075": return "SOR_074"; //Repair
    case "JTLW_001": return "JTL_032"; //Director Krennic
    case "JTLW_002": return "JTL_033"; //Onyx Squadron Brute
    case "JTLW_003": return "JTL_045"; //Hera Syndulla
    case "JTLW_004": return "JTL_048"; //Cassian Andor
    case "JTLW_005": return "JTL_097"; //Leia Organa
    case "JTLW_006": return "JTL_100"; //Poe Dameron
    case "JTLW_007": return "JTL_103"; //Chewbacca
    case "JTLW_008": return "7901484215"; //General Hux
    case "JTLW_009": return "8437892403"; //IG-88
    case "JTLW_010": return "2810644441"; //BB-8
    case "JTLW_011": return "6864010809"; //Red Squadron Y-Wing
    case "JTLW_012": return "0256547071"; //Red Five
    case "JTLW_013": return "3248826052"; //Captain Tarkin
    case "JTLW_014": return "8666297378"; //Shoot Down
    case "JTLW_015": return "1919456338"; //Boba Fett
    case "JTLW_016": return "2757811795"; //Anakin Skywalker
    case "JTLW_017": return "8384504746"; //Han Solo
    case "JTLW_018": return "6489060437"; //Fett's Firespray
    case "JTLW_019": return "0417314165"; //Millennium Falcon
    case "JTLW_020": return "5191307053"; //Swarming Vulture Droid
    case "P25_001":
    case "P25_002": return "4299138542"; //Bossk JTL
    case "P25_003":
    case "P25_004":
    case "P25_005":
    case "P25_006": return "4966205041"; //Devastator JTL
    case "P25_007":
    case "P25_008": return "1715486607"; //Black One JTL
    case "P25_009":
    case "P25_010":
    case "P25_011":
    case "P25_012": return "7223762901"; //Home One JTL
    case "J25_001": return "5975430516"; //The Ghost
    case "J25_002": return "7713416118"; //Ki-Adi-Mundi
    case "J25_003": return "8562538488"; //Poggle The Lesser
    case "J25_004": return "JTL_094"; //Luke Skywalker JTL
    case "J25_005": return "4737300535"; //Darth Maul
    case "J25_006": return "0428448817"; //Darth Vader JTL
    case "J25_007": return "8776875806"; //Count Dooku TWI
    case "J25_008": return "2412836213"; //Ahsoka Tano
    case "J25_009": return "9092942444"; //Enfys Nest
    case "P25_046": return "SHD_141"; //Kylo Ren
    case "P25_044": return "4246161751"; //Obi-Wan Kenobi SOR
    case "P25_039": return "5712012074"; //Admiral Ackbar
    case "P25_040": return "4965992740"; //Agent Kallus
    case "P25_041": return "4015981529"; //Red Three
    case "P25_042": return "SOR_196"; //Chewbacca
    case "P25_043": return "1367697710"; //Han Solo SOR
    case "P25_052": return "6818212006"; //Mandalorian Armor
    case "P25_053": return "8558118489"; //Count Dooku TWI
    case "P25_054": return "4728275106"; //Kit Fisto
    case "P25_055": return "3824479984"; //Fett's Firespray SOR
    case "P25_056": return "0958864493"; //Unnatural Life
    case "P25_057": return "9776208290"; //Emperor Palpatine (Master of the Dark Side)
    case "P25_059": return "6814503441"; //Rey
    default: return $cardID;
  }
}

function CardUUIDOverride($cardID)
{
  switch ($cardID) {
    case "1706333706": return "8380936981";//Jabba's Rancor
    //TODO: left here just in case we need these IDs
    //case "1401885853"://con exclusive 2024 Count Dooku (Darth Tyranus)
      //return "9624333142";
    //case "8292269690"://con exclusive 2024 Darth Vader (Commanding the First Legion)
      //return "8506660490";
    //case "9954244145"://con exclusive 2024 Emperor Palpatine (Master of the Dark Side)
      //return "9097316363";
    //case "3038397952"://con exclusive 2024 Kylo Ren (Killing the Past)
      //return "6263178121";
    //case "7315203824"://con exclusive 2024 Asajj Ventress (Count Dooku's Assassin)
      //return "3556557330";
    //case "5866567543"://con exclusive 2024 Darth Maul (Revenge at Last)
      //return "8613680163";
    default: return $cardID;
  }
}