<?php
include_once '../MenuBar.php';

include_once '../includes/functions.inc.php';
include_once "../includes/dbh.inc.php";
include_once '../Libraries/CoreLibraries.php';

if (!isset($_SESSION["useruid"])) {
  echo ("Please login to view this page.");
  exit;
}
$useruid = $_SESSION["useruid"];
?>
<link rel="stylesheet" href="../css/petranaki250812.css">
<script>
  function LoadDeck() {
    var deckJson = document.getElementById("deckJson").value;
    var numCards = document.getElementById("numCards").value;
    var deckParsed = JSON.parse(deckJson);
    var deck = deckParsed.deck;
    var deckString = "";
    for (var i = 0; i < deck.length; i++) {
      var card = deck[i];
      for(var j = 0; j < card.count; j++) {
        deckString += card.id + " ";
      }
    }
    deckString = deckString.trim();
    document.getElementById("deckString").innerHTML = deckString;
    document.getElementById("loadedMessage").innerHTML = "Deck Loaded!";
  }

  function RunShuffle() {
    var xhr = new XMLHttpRequest();
    var deckString = document.getElementById("deckString").innerHTML;
    var numCards = document.getElementById("numCards").value;
    var iterations = document.getElementById("iterations").value;
    if (iterations < 25 || iterations > 1000) {
      alert("Please enter a number of iterations between 25 and 1000.");
      return;
    }
    if (deckString == "") {
      alert("Please load a deck first.");
      return;
    }
    if (numCards <= 0) {
      alert("Please enter a valid number of cards.");
      return;
    }
    //clear inputs
    if (!document.getElementById("saveDeck").checked) {
      document.getElementById("deckJson").value = "";
      document.getElementById("loadedMessage").innerHTML = "";
      document.getElementById("deckString").innerHTML = "";
    }
    //clear results
    document.getElementById("t1_1").innerHTML = "";
    document.getElementById("t1_2").innerHTML = "";
    document.getElementById("t2_2").innerHTML = "";
    document.getElementById("t3_2").innerHTML = "";
    document.getElementById("t4_2").innerHTML = "";
    document.getElementById("t1_3").innerHTML = "";
    document.getElementById("t2_3").innerHTML = "";
    document.getElementById("t3_3").innerHTML = "";
    document.getElementById("res2orless").innerHTML = "";
    document.getElementById("firstHands").innerHTML = "";

    //send request to api/ShuffleTool.php
    xhr.open("POST", "../api/ShuffleTool.php", true);
    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
    xhr.onreadystatechange = function() {
      if (this.readyState === 4 && this.status === 200) {
        var results = JSON.parse(this.responseText);
        document.getElementById("res2orless").innerHTML = "Costs 2 Resources or Less: " + (100*(parseInt(results.res2orless) / iterations)).toFixed(2) + "%";
        document.getElementById("t1_1").innerHTML = "Times No Card Repeated: " + (100*(parseInt(results.t1_1) / iterations)).toFixed(2) + "%";
        document.getElementById("t1_2").innerHTML = "Times 1 Card Drawn Twice: " + (100*(parseInt(results.t1_2) / iterations)).toFixed(2) + "%";
        document.getElementById("t2_2").innerHTML = "Times 2 Cards Drawn Twice Each: " + (100*(parseInt(results.t2_2) / iterations)).toFixed(2) + "%";
        document.getElementById("t3_2").innerHTML = "Times 3 Cards Drawn Twice Each: " + (100*(parseInt(results.t3_2) / iterations)).toFixed(2) + "%";
        document.getElementById("t4_2").innerHTML = "Times 4 Cards Drawn Twice Each: " + (100*(parseInt(results.t4_2) / iterations)).toFixed(2) + "%";
        document.getElementById("t1_3").innerHTML = "Times 1 Cards Drawn Three Times: " + (100*(parseInt(results.t1_3) / iterations)).toFixed(2) + "%";
        document.getElementById("t2_3").innerHTML = "Times 2 Cards Drawn Three Times Each: " + (100*(parseInt(results.t2_3) / iterations)).toFixed(2) + "%";
        document.getElementById("t3_3").innerHTML = "Times 3 Cards Drawn Three Times Each: " + (100*(parseInt(results.t3_3) / iterations)).toFixed(2) + "%";

        var firstHands = results.firstHands;
        var firstHandsDiv = document.getElementById("firstHands");
        firstHandsDiv.innerHTML = "<h3>First Hands:</h3>";
        for (var i = 0; i < firstHands.length; i++) {
          var hand = firstHands[i];
          var handDiv = document.createElement("div");
          handDiv.innerHTML = "Hand " + (i + 1) + ": <br/>";
          handDiv.style.marginBottom = "18px";
          for (var j = 0; j < hand.length; j++) {
            var card = hand[j];
            var cardDiv = document.createElement("span");
            var numCards = document.getElementById("numCards").value;
            var width = Math.min(Math.max(120 - ((numCards - 6) * 3), 64), 128);
            cardDiv.innerHTML = `<img src="/Arena/concat/${card}.webp" width=${width}/>`
            handDiv.appendChild(cardDiv);
          }
          firstHandsDiv.appendChild(handDiv);
        }
      }
    };
    xhr.send("deck=" + encodeURIComponent(deckString) + "&numCards=" + encodeURIComponent(numCards) + "&iterations=" + encodeURIComponent(iterations));
  }
</script>
<div style='padding:10px; width:80vw; max-width: 800px; height: 80vh; margin: 10vh auto;
  background-color:rgba(74, 74, 74, 0.9); border: 2px solid #1a1a1a; border-radius: 5px; overflow-y: scroll;'>
  <label for="deckJson" style='font-weight:bolder; margin-left:10px;'>Deck JSON:</label>
  <textarea style="display:block; margin: 24px 0;" rows=8 cols=64 id="deckJson" name="deckJson" value=""></textarea>
  <button onclick="LoadDeck()">Load Deck</button>
  <div id="loadedMessage"></div>
  <label for="numCards" style='font-weight:bolder; margin-left:8px;'>Num Cards in Starting Hand:</label>
  <input type="number" id="numCards" name="numCards" value="6"><br/>
  <label for="iterations" style='font-weight:bolder; margin-left:8px;'>Number of Iterations:</label>
  <input type="number" id="iterations" name="iterations" value="25" min="25" max="1000"><br/>
  <div style="margin: 10px 8px;">
    <input type="checkbox" id="saveDeck" name="saveDeck">
    <label for="saveDeck">Save deck string for multiple runs</label>
  </div>
  <button onclick="RunShuffle()">Run Iterations</button>
  <hr/>
  <div id="shuffle-results" style="height: 180px">
    <div id="res2orless"></div>
    <div id="t1_1"></div>
    <div id="t1_2"></div>
    <div id="t2_2"></div>
    <div id="t3_2"></div>
    <div id="t4_2"></div>
    <div id="t1_3"></div>
    <div id="t2_3"></div>
    <div id="t3_3"></div>
  </div>
  <hr />
  <div id="firstHands">
  </div>
</div>
<div id="deckString" hidden></div>
</div>
<?php

?>