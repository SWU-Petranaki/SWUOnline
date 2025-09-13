<?php
include_once 'MenuBar.php';
include_once 'Header.php';
?>

<style>
  div.reference-page {
    width: 90vw;
    padding: 2rem;
    margin-left: auto;
    margin-right: auto;
    margin-top: auto;
    backdrop-filter: blur(16px);
    border-radius: 16px;
    display: flex;
    flex-direction: column;
    overflow: scroll;
  }

  .reference-page p {
    font-size: 1.2rem;
    line-height: 1.5rem;
    color: #fff;
  }

  .ref-page-brawl-current {
    font-weight: bold;
    color: #87CEEB;
    font-size: 1.33rem;
  }

  p.ref-page-brawl-rule-detail {
    font-size: 1rem;
  }

  a.ref-page-link {
    display: inline;
    color: #ffd24d;
    padding: 0;
    margin: 0;
  }
</style>
<div class="core-wrapper bg-yellow reference-page">
  <h1>Cantina Brawl</h1>
  <br/><br/>
  <p>
      Welcome to the Cantina! Prepare to Brawl! Currently featured: <span class="ref-page-brawl-current">Padawan/span>
  </p>
  <hr/>
  <h2>What is Cantina Brawl?</h2>
  <p>
    Cantina Brawl is a gameplay format on Petranaki.net that features a periodically rotating game mode! The modes you'll experience in the Cantina may require you to construct SWU decks with certain restrictions, play with pre-built themed decks, and even, on occasion, change some rules of the game entirely!
  </p>

  <h2>How do I know what the Cantina Brawl rules are?</h2>
  <p>
    If you try to enter a Cantina Brawl lobby with an invalid deck, an error should be displayed showing you the current rules as well as a list of what cards in your chosen deck violate those rules. Alternatively, you can visit this page for a list of all Cantina Brawl mode rules!
  </p>

  <h2>How often does the featured game mode rotate?</h2>
  <p>
    Since Cantina Brawl is new, we don't have a solid answer to this question just yet. We are still developing new modes in between squashing bugs found in Premier. Ideally, once a decent number of Cantina Brawl modes have been implemented, we imagine Cantina Brawl modes will rotate each week.
  </p>

  <h2>What if I want to play a mode that rotated out and is no longer featured?</h2>
  <p>
    Unfortunately the nature of the Cantina means modes will come and go over time, but don't worry, it shall return (and in greater numbers too... maybe)!  Also, we'd love to hear which modes you enjoy most, so please join our <a class="ref-page-link" href="https://discord.gg/ep9fj8Vj3F" target="_blank" rel="noopener noreferrer">Discord</a>  and leave some feedback!
  </p>

  <h2>How many cards can be in a Cantina Brawl deck?</h2>
  <p>
    The standard SWU deckbuilding requirements, such as a 50-card minimum, 3 maximum copies per card, and a 10-card sideboard, all remain true while playing in the Cantina unless the featured game mode specifically mentions otherwise!
  </p>
  <hr/>
  <h2>What game modes can I expect to see in the Cantina?</h2>

  <h2>Padawan:</h2>
  <p>
    Show your friends that you don't need a massive wallet to have fun in SWU! Construct a deck using only Common cards!
  </p>
  <p class="ref-page-brawl-rule-detail">
    (Any Leader allowed, No Rare bases, no Special rarity cards unless they are a Leader or have a Common variant)
  </p>

  <h2>Sandcrawler:</h2>
  <p>
    Try winning with whatever junk you can salvage from the desert! Construct a deck without any Rare or Legendary cards!
  </p>
  <p class="ref-page-brawl-rule-detail">
    (Rare Leadeers are allowed, Special rarity cards without a Rare or Legendary variant are allowed)
  </p>

  <h2>Galactic Civil War:</h2>
  <p>
    Fight for freedom as the Rebellion, or help bring order to the galaxy as the Galactic Empire! Your Leader and each Unit in your deck must have either the REBEL or IMPERIAL trait!
  </p>
  <p class="ref-page-brawl-rule-detail">
    (Bases, Events, and Upgrades are unaffected)
  </p>

  <br/>
  <p>
    More Coming Very Soon!
  </p>
  <br/><br/><br/>
  <div style="display: none;">
  <h2>The Clone Wars:</h2>
  <p>
    Simulate one of the many battles that were waged across the galaxy during The Clone Wars. Your Leader and each Unit in your deck must have either the REPUBLIC or SEPARATIST trait!
  </p>
  <p class="ref-page-brawl-rule-detail">
    (Bases, Events, and Upgrades are unaffected)
  </p>
  </div>
  <div style="display: none;">
    <h2>Theme Decks:</h2>
    <p>
      Players will join lobbies and get a theme deck assigned to them upon entry. Possible Theme Deck Cantina Brawls listed below.
    </p>
      <h3>Cloud City Clash:</h3>
      <p>
        Luke Skywalker and Darth Vader duel in the carbon freezing chamber while panic ensues in the streets of Cloud City!  Show your opponent that your skills are "most impressive" with one of two pre-built Bespin-themed decks!
      </p>

      <h3>Showdown in the Streets:</h3>
      <p>
        Boba Fett's rise to Daimyo-status has attracted the attention of other criminal organizations, and Cad Bane has been brought in to end his reign!  Use one of two pre-built decks to find out which legendary bounty hunter will come out on top!
      </p>

      <h3>It's A Trap!:</h3>
      <p>
        Admiral Ackbar leads the Rebel fleet to Endor only to find Admiral Piett aboard The Executor lying in wait. Use one of two pre-built decks to either engage those Star Destroyers at point-blank range, or to keep the Rebels from escaping while the Emperor's plan unfolds!
      </p>

      <h3>Aggressive Negotiations:</h3>
      <p>
        Padme Amidala and the newly deployed clone army faces off against Nute Gunray and his battle-hardened droid forces. Use one of two pre-built decks to show your opponent what happens when diplomacy fails!
      </p>

      <h3>Heir to Mandalore:</h3>
      <p>
        Bo-Katan and Gar Saxon rally their clans in a battle for the Mandalorian throne!  Use one of two pre-built decks to claim the Darksaber and unite Mandalore for your cause!
      </p>

      <h3>Inferno vs Spectre:</h3>
      <p>
        Iden Versio and Hera Syndulla fearlessly lead their squads into battle!  Use one of two pre-built decks to show whether Infero Squad or Spectre Squad are better at getting the job done!
      </p>
  </div>
</div>

<?php
include_once 'Disclaimer.php';
?>
