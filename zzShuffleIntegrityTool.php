<?php
include_once './MenuBar.php';

include_once './includes/functions.inc.php';
include_once "./includes/dbh.inc.php";
include_once './Libraries/CoreLibraries.php';

if (!isset($_SESSION["useruid"])) {
  echo ("Please login to view this page.");
  exit;
}
$useruid = $_SESSION["useruid"];
$contributors = ["OotTheMonk", "love", "ninin", "Brubraz", "Mobyus1"];
if (!in_array($useruid, $contributors)) {
  echo ("You must log in to use this page.");
  exit;
}
$iterations = 1000;
?>
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
    if (deckString == "") {
      alert("Please load a deck first.");
      return;
    }
    if (numCards <= 0) {
      alert("Please enter a valid number of cards.");
      return;
    }
    //clear inputs
    document.getElementById("deckJson").value = "";
    document.getElementById("loadedMessage").innerHTML = "";
    document.getElementById("deckString").innerHTML = "";
    //clear results
    document.getElementById("t1_1").innerHTML = "";
    document.getElementById("t1_2").innerHTML = "";
    document.getElementById("t2_2").innerHTML = "";
    document.getElementById("t3_2").innerHTML = "";
    document.getElementById("t4_2").innerHTML = "";
    document.getElementById("t1_3").innerHTML = "";
    document.getElementById("t2_3").innerHTML = "";
    document.getElementById("t3_3").innerHTML = "";
    //send request to api/ShuffleTool.php
    xhr.open("POST", "api/ShuffleTool.php", true);
    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
    xhr.onreadystatechange = function() {
      if (this.readyState === 4 && this.status === 200) {
        var results = JSON.parse(this.responseText);
        document.getElementById("t1_1").innerHTML = "Times No Card Repeated: " + (100*(parseInt(results.t1_1) / <?php echo $iterations ?>)).toFixed(2) + "%";
        document.getElementById("t1_2").innerHTML = "Times 1 Card Drawn Twice: " + (100*(parseInt(results.t1_2) / <?php echo $iterations ?>)).toFixed(2) + "%";
        document.getElementById("t2_2").innerHTML = "Times 2 Cards Drawn Twice Each: " + (100*(parseInt(results.t2_2) / <?php echo $iterations ?>)).toFixed(2) + "%";
        document.getElementById("t3_2").innerHTML = "Times 3 Cards Drawn Twice Each: " + (100*(parseInt(results.t3_2) / <?php echo $iterations ?>)).toFixed(2) + "%";
        document.getElementById("t4_2").innerHTML = "Times 4 Cards Drawn Twice Each: " + (100*(parseInt(results.t4_2) / <?php echo $iterations ?>)).toFixed(2) + "%";
        document.getElementById("t1_3").innerHTML = "Times 1 Cards Drawn Three Times: " + (100*(parseInt(results.t1_3) / <?php echo $iterations ?>)).toFixed(2) + "%";
        document.getElementById("t2_3").innerHTML = "Times 2 Cards Drawn Three Times Each: " + (100*(parseInt(results.t2_3) / <?php echo $iterations ?>)).toFixed(2) + "%";
        document.getElementById("t3_3").innerHTML = "Times 3 Cards Drawn Three Times Each: " + (100*(parseInt(results.t3_3) / <?php echo $iterations ?>)).toFixed(2) + "%";
      }
    };
    xhr.send("deck=" + encodeURIComponent(deckString) + "&numCards=" + encodeURIComponent(numCards) + "&iterations=" + encodeURIComponent(<?php echo $iterations; ?>));
  }
</script>
<div style='padding:10px; width:80vw; max-width: 640px; height: 70vh; margin: 20vh auto;
  background-color:rgba(74, 74, 74, 0.9); border: 2px solid #1a1a1a; border-radius: 5px; overflow-y: scroll;'>
  <label for="deckJson" style='font-weight:bolder; margin-left:10px;'>Deck JSON:</label>
  <textarea style="display:block; margin: 24px 0;" rows=18 cols=64 id="deckJson" name="deckJson" value=""></textarea>
  <button onclick="LoadDeck()">Load Deck</button>
  <div id="loadedMessage"></div>
  <label for="numCards" style='font-weight:bolder; margin-left:10px;'>Num Cards in Starting Hand:</label>
  <input type="number" id="numCards" name="numCards" value="6"><br/>
  <button onclick="RunShuffle()">Run <?php echo $iterations ?> Iterations</button>
  <div id="shuffle-results">
  <div id="t1_1"></div>
  <div id="t1_2"></div>
  <div id="t2_2"></div>
  <div id="t3_2"></div>
  <div id="t4_2"></div>
  <div id="t1_3"></div>
  <div id="t2_3"></div>
  <div id="t3_3"></div>
</div>
<div id="deckString" hidden></div>
</div>
<?php

$json = '{"metadata":{"name":"vader Nabat JTL"},"leader":{"id":"7661383869","count":1},"base":{"id":"9586661707","count":1},"deck":[{"id":9811031405,"count":3},{"id":1900571801,"count":3},{"id":1519837763,"count":3},{"id":1810342362,"count":3},{"id":3809048641,"count":3},{"id":7202133736,"count":2},{"id":9950828238,"count":3},{"id":5696041568,"count":3},{"id":7138400365,"count":3},{"id":"0693815329","count":1},{"id":8862896760,"count":2},{"id":8800836530,"count":1},{"id":8105698374,"count":2},{"id":6072239164,"count":2},{"id":2177194044,"count":7},{"id":3567283316,"count":3},{"id":5027991609,"count":3},{"id":6257858302,"count":2},{"id":3789633661,"count":1}],"sideboard":[{"id":7202133736,"count":1},{"id":3401690666,"count":2},{"id":5830140660,"count":2},{"id":1626462639,"count":2},{"id":2346145249,"count":1},{"id":8105698374,"count":1},{"id":3789633661,"count":1}]}';
//RandomizeArray($deck, );

?>