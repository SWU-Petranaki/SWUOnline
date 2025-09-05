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
<link rel="stylesheet" href="../css/petranaki250812.css">
<div style='padding:10px; width:90vw; max-width: 1200px; height: 80vh; margin: 10vh auto;
  background-color:rgba(74, 74, 74, 0.9); border: 2px solid #1a1a1a; border-radius: 5px;'>

  <div style="display: flex; justify-content: space-between; height: 90%;">
    <div style="width: 45%;">
      <label for="inputText" style='font-weight:bolder; margin-left:10px;'>Input Text:</label>
      <textarea style="width: 100%; height: 90%; margin-top: 10px;"
        id="inputText" name="inputText"></textarea>
      <label for="deckName" style='font-weight:bolder; margin-left:10px;'>Deck Name:</label>
      <input type="text" id="deckName" name="deckName" style="width: 100%; height: 40px; margin-top: 10px;" />
    </div>

    <div style="display: flex; align-items: center; flex-direction: column; margin: auto 1rem;">
      <button onclick="convertToJson()" style="height: 40px; margin: 0 20px;">
        Convert to JSON
      </button>
      <div style="display: flex; flex-direction: column; align-items: center; margin: 0 20px;">
        <label style="margin-bottom: 5px;">
          <input type="checkbox" id="preserveInput" name="preserveInput">
          Preserve Input
        </label>
      </div>
    </div>

    <div style="width: 45%;">
      <label for="outputJson" style='font-weight:bolder; margin-left:10px;'>Output JSON:</label>
      <textarea style="width: 100%; height: 90%; margin-top: 10px;"
        id="outputJson" name="outputJson" readonly></textarea>
    </div>
  </div>
</div>

<script>
function convertToJson() {
    var inputText = document.getElementById("inputText").value;
    var outputArea = document.getElementById("outputJson");
    var deckName = document.getElementById("deckName").value;
    if (inputText.trim() === "") {
        alert("Please enter some input text.");
        return;
    }
    if (deckName.trim() === "") {
        alert("Please enter a deck name.");
        return;
    }
    outputArea.value = "";
    var xhr = new XMLHttpRequest();
    xhr.open("POST", "/Arena/api/ConvertMeleeToJson.php", true);
    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
    xhr.onreadystatechange = function() {
      if (this.readyState === 4 && this.status === 200) {
        outputArea.value = JSON.stringify(JSON.parse(this.responseText), null, 2);
        if (document.getElementById("preserveInput").checked) {
          document.getElementById("inputText").value = inputText;
        } else {
          document.getElementById("inputText").value = "";
        }
      }
      document.getElementById("deckName").value = "";
    };
    xhr.send("input=" + encodeURIComponent(inputText) + "&deckName=" + encodeURIComponent(deckName));
}
</script>