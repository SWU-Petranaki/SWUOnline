<?php
include_once '../MenuBar.php';
include_once '../includes/functions.inc.php';
include_once '../Libraries/CoreLibraries.php';

if (!isset($_SESSION["useruid"])) {
  echo ("Please login to view this page.");
  exit;
}
$useruid = $_SESSION["useruid"];
?>
<link rel="stylesheet" href="../css/petranaki251019.css">
<div style='padding:10px; width:90vw; max-width: 1200px; height: 60vh; margin: 10vh auto 0 auto;
  background-color:rgba(74, 74, 74, 0.9); border: 2px solid #1a1a1a; border-radius: 5px;'>

  <div style="display: flex; justify-content: space-between; height: 90%;">
    <div style="width: 45%;">
      <label for="inputJson" style='font-weight:bolder; margin-left:10px;'>Input JSON:</label>
      <textarea style="width: 100%; height: 80%; margin-top: 10px;"
        id="inputJson" name="inputJson"></textarea>
    </div>

    <div style="display: flex; align-items: center; flex-direction: column; margin: auto 1rem;">
      <button onclick="convertToTCGPAsync()" style="height: 40px; margin: 0 20px;">
        Convert to TCGP
      </button>
      <div style="display: flex; flex-direction: column; align-items: center; margin: 0 20px;">
        <label style="margin-bottom: 5px;">
          <input type="checkbox" id="ignoreLeader" name="ignoreLeader">
          Ignore Leader
        </label>
        <label style="margin-bottom: 5px;">
          <input type="checkbox" id="ignoreBase" name="ignoreBase">
          Ignore Base
        </label>
        <label style="margin-bottom: 5px;">
          <input type="checkbox" id="preserveInput" name="preserveInput">
          Preserve Input
        </label>
      </div>
    </div>

    <div style="width: 45%;">
      <label for="outputText" style='font-weight:bolder; margin-left:10px;'>Output:</label>
      <textarea style="width: 100%; height: 60%; margin-top: 10px;" id="outputText" name="outputText" readonly></textarea>
      <div style="max-height: 40%; overflow-y: scroll; margin-top: 8px;">
        <label for="marketPrice">Market Price</label>
        <input type="text" id="marketPrice" name="marketPrice" style="width: 100%; margin-top: 4px;" readonly>
        <label for="lowPrice">Low Price (including Damaged)</label>
        <input type="text" id="lowPrice" name="lowPrice" style="width: 100%; margin-top: 4px;" readonly>
        <a href="https://www.tcgplayer.com/massentry?productline=Star+Wars+Unlimited" target="_blank">
          <button>
            Open TCGPlayer Mass Entry Tool in New Tab
          </button>
        </a>
      </div>

    </div>
  </div>
</div>
<style>
.tcgpme-instructions {
  display: block;
}
.tcgpme-instructions ul,li {
  display: block;
}
</style>
<div class='tcgpme-instructions' style='padding:10px; width:90vw; max-width: 1200px; margin: 8px auto 0 auto;
  background-color:rgba(60, 60, 60, 0.95); border: 2px solid #222; border-radius: 5px; color: #fff;'>
  <h2 style="margin-top:0;">Instructions</h2>
  <ul style="margin-left: 20px;">
    <li>From your favorite deck-building site, find the "Copy JSON" or "Export JSON" button.</li>
    <li>Paste your deck JSON into the <b>Input JSON</b> box on the left.</li>
    <li>Choose options: Ignore Leader, Ignore Base, Preserve Input (keeps input after conversion)</li>
    <li>Click <b>Convert to TCGP</b> to generate the formatted deck list.</li>
    <li>Copy the output text to use in the <a href="https://www.tcgplayer.com/massentry?productline=Star+Wars+Unlimited" target="_blank">TCGPlayer Mass Entry Tool</a>.</li>
    <li>After adding to your cart, it's always good to click "Optimize" with "current printings" off. TCGPlayer will add the most expensive ones at first.</li>
  </ul>
</div>

<script>
function getCardDataAsync(cardId) {
  return fetch('../api/GetCardDataFromSetID.php?cardID=' + encodeURIComponent(cardId))
    .then(response => response.json())
    .then(data => {
      if (data && data.title) {
        return data;
      } else {
        console.error("Card name not found for ID:", cardId);
        return { name: "Unknown Card (" + cardId + ")" };
      }
    })
    .catch(() => {
      console.error("Error fetching card name for ID:", cardId);
      return { name: "Unknown Card (" + cardId + ")" };
    });
}

 function convertToTCGPAsync() {
    var inputJson = document.getElementById("inputJson").value;
    var outputArea = document.getElementById("outputText");
    var ignoreLeader = document.getElementById("ignoreLeader").checked;
    var ignoreBase = document.getElementById("ignoreBase").checked;

    if (inputJson.trim() === "") {
        alert("Please enter some input JSON.");
        return;
    }
    outputArea.value = "";
    let parsed;
    try {
        parsed = JSON.parse(inputJson);
    } catch (e) {
        alert("Invalid JSON input.");
        return;
    }
    const data = [];
    const lines = [];
    const promises = [];

    if (!ignoreLeader && parsed.leader) {
      promises.push(
        getCardDataAsync(parsed.leader.id).then(leaderData => {
          data.push({
            name: leaderData.name,
            count: parsed.leader.count || 0,
            lowPrice: leaderData.lowPrice || 0,
            marketPrice: leaderData.marketPrice || 0
          })
          lines.push((parsed.leader.count || 1) + " " + leaderData.name);
        })
      );
    }
    if (!ignoreBase && parsed.base) {
      promises.push(
        getCardDataAsync(parsed.base.id).then(baseData => {
          data.push({
            name: baseData.name,
            count: parsed.base.count || 0,
            lowPrice: baseData.lowPrice || 0,
            marketPrice: baseData.marketPrice || 0
          })
          lines.push((parsed.base.count || 1) + " " + baseData.name);
        })
      );
    }
    if (Array.isArray(parsed.deck)) {
      parsed.deck.forEach(function(card) {
        promises.push(
          getCardDataAsync(card.id).then(cardData => {
            data.push({
              name: cardData.name,
              count: card.count || 1,
              lowPrice: cardData.lowPrice || 0,
              marketPrice: cardData.marketPrice || 0
            });
            lines.push((card.count || 1) + " " + cardData.name);
          })
        );
      });
    }
    if (Array.isArray(parsed.sideboard) && parsed.sideboard.length > 0) {
      parsed.sideboard.forEach(function(card) {
        promises.push(
          getCardDataAsync(card.id).then(cardData => {
            data.push({
              name: cardData.name,
              count: card.count || 1,
              lowPrice: cardData.lowPrice || 0,
              marketPrice: cardData.marketPrice || 0
            });
            lines.push((card.count || 1) + " " + cardData.name);
          })
        );
      });
    }

    Promise.all(promises).then(function() {
      outputArea.value = lines.join("\n");
      if (!document.getElementById("preserveInput").checked) {
        document.getElementById("inputJson").value = "";
      }
      // Calculate total prices
      let totalLowPrice = 0;
      let totalHighPrice = 0;
      let totalMarketPrice = 0;
      data.forEach(function(card) {
        totalLowPrice += card.lowPrice * (card.count || 1);
        totalMarketPrice += card.marketPrice * (card.count || 1);
      });
      document.getElementById("lowPrice").value = totalLowPrice.toFixed(2);
      document.getElementById("marketPrice").value = totalMarketPrice.toFixed(2);
    }).catch(function(error) {
      console.error("Error processing cards:", error);
      outputArea.value = "Error processing cards. Please check the input JSON format.";
      document.getElementById("lowPrice").value = "";
      document.getElementById("marketPrice").value = "";
    });

}
</script>