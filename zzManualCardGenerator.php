<?php

include_once 'MenuBar.php';

include_once './includes/functions.inc.php';
include_once "./includes/dbh.inc.php";

if (!isset($_SESSION["useruid"])) {
  echo ("Please login to view this page.");
  exit;
}

$useruid = $_SESSION["useruid"];
if ($useruid != "OotTheMonk" && $useruid != "love" && $useruid != "ninin" && $useruid != "Brubraz" && $useruid != "Mobyus1") {
  echo ("You must log in to use this page.");
  exit;
}

?>
<style>
  .form-group {
    margin-bottom: 15px;
  }
  .form-group label {
    margin-right: 8px;
    font-weight: bold;
  }
  .form-control {
    width: 100%;
    max-width: 240px;
    padding: 8px;
    margin-right: 24px;
    border-radius: 8px;
    border: 1px solid #ccc;
    box-sizing: border-box;
  }
  .form-control:focus {
    border-color: #007bff;
    outline: none;
  }
  .form-group select {
    display: inline;
  }
  .btn-primary {
    width: 80%;
    margin-left: 7.5%;
    background-color: #007bff;
    color: white;
    padding: 10px 20px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
  }
  .btn-primary:hover {
    background-color: #0056b3;
  }
  .fc-min {
    max-width: 120px;
  }
</style>
<div style='padding:10px; width:80vw; max-width: 800px; height: 80vh; margin: 8vh auto;
  background-color:rgba(74, 74, 74, 0.9); border: 2px solid #1a1a1a; border-radius: 5px; overflow-y: scroll;'>
  <h2>Manual Card Generator</h2>
  <form method="post" action="" enctype="multipart/form-data">
    <div class="form-group">
      <label for="cardImage">Card Image</label>
      <input type="file" name="cardImage" id="cardImage" class="form-control" accept="image/png">
    </div>

    <div class="form-group">
      <label for="cost">Card Cost <span style="color: yellowgreen; font-weight: bolder;">✸</span></label>
      <input type="number" name="cost" id="cost" min="0" required class="form-control">
    </div>

    <div class="form-group">
      <label for="cardTitle">Card Title <span style="color: yellowgreen; font-weight: bolder;">✸</span></label>
      <input type="text" name="cardTitle" id="cardTitle" required class="form-control">
      <label for="subtitle">Subtitle</label>
      <input type="text" name="subtitle" id="subtitle" class="form-control">
    </div>

    <div class="form-group">
      <label for="power">Power</label>
      <input type="number" name="power" id="power" min="0" class="form-control">
      <label for="hp">HP</label>
      <input type="number" name="hp" id="hp" min="1" class="form-control">
    </div>

    <div class="form-group">
      <label for="upgradePower">Upg. Power</label>
      <input type="number" name="upgradePower" id="upgradePower" min="0" class="form-control">
      <label for="upgradeHp">Upg. HP</label>
      <input type="number" name="upgradeHp" id="upgradeHp" min="1" class="form-control">
    </div>

    <div class="form-group">
      <label for="aspect1">Aspect 1</label>
      <select name="aspect1" id="aspect1" class="form-control">
        <option value="">None</option>
        <option value="Vigilance">Vigilance</option>
        <option value="Command">Command</option>
        <option value="Aggression">Aggression</option>
        <option value="Cunning">Cunning</option>
        <option value="Heroism">Heroism</option>
        <option value="Villainy">Villainy</option>
      </select>
      <label for="aspect2">Aspect 2</label>
      <select name="aspect2" id="aspect2" class="form-control">
        <option value="">None</option>
        <option value="Vigilance">Vigilance</option>
        <option value="Command">Command</option>
        <option value="Aggression">Aggression</option>
        <option value="Cunning">Cunning</option>
        <option value="Heroism">Heroism</option>
        <option value="Villainy">Villainy</option>
      </select>
    </div>

    <div class="form-group">
      <label for="traits">Traits (comma separated) <span style="color: yellowgreen; font-weight: bolder;">✸</span></label>
      <input type="text" name="traits" id="traits" required class="form-control">
    </div>

    <div class="form-group">
    <label for="arena">Arena</label>
      <select name="arena" id="arena" class="form-control">
        <option value="">None</option>
        <option value="Ground">Ground</option>
        <option value="Space">Space</option>
      </select>
      <label for="cardType">Card Type <span style="color: yellowgreen; font-weight: bolder;">✸</span></label>
      <select name="cardType" id="cardType" required class="form-control">
        <option value="Unit">Unit</option>
        <option value="Event">Event</option>
        <option value="Leader">Leader</option>
        <option value="Upgrade">Upgrade</option>
        <option value="Base">Base</option>
      </select>
      <label>
        <input type="checkbox" name="isUnique" value="1"> Unique
      </label>
    </div>

    <div class="form-group">
      <label for="setCode">Set Code <span style="color: yellowgreen; font-weight: bolder;">✸</span></label>
      <input type="text" name="setCode" id="setCode" value="LOF" required class="form-control fc-min">
      <label for="cardNumber">Card ID <span style="color: yellowgreen; font-weight: bolder;">✸</span></label>
      <input type="text" name="cardNumber" id="cardNumber" required class="form-control fc-min">
      <label for="rarity">Rarity <span style="color: yellowgreen; font-weight: bolder;">✸</span></label>
      <select name="rarity" id="rarity" required class="form-control fc-min">
        <option value="Common">Common</option>
        <option value="Uncommon">Uncommon</option>
        <option value="Rare">Rare</option>
        <option value="Legendary">Legendary</option>
        <option value="Special">Special</option>
      </select>
    </div>

    <button type="submit" name="submit" class="btn btn-primary">Add Card</button>
  </form>

  <?php
  if (isset($_POST['submit'])) {
    $cardTitle = $_POST['cardTitle'];
    $subtitle = isset($_POST['subtitle']) ? $_POST['subtitle'] : '';
    $cost = $_POST['cost'];
    $power = isset($_POST['power']) && $_POST['power'] !== '' ? $_POST['power'] : null;
    $hp = isset($_POST['hp']) && $_POST['hp'] !== '' ? $_POST['hp'] : null;
    $upgradePower = isset($_POST['upgradePower']) && $_POST['upgradePower'] !== '' ? $_POST['upgradePower'] : null;
    $upgradeHp = isset($_POST['upgradeHp']) && $_POST['upgradeHp'] !== '' ? $_POST['upgradeHp'] : null;
    $aspect1 = $_POST['aspect1'];
    $aspect2 = $_POST['aspect2'];
    $traits = $_POST['traits'];
    $arena = isset($_POST['arena']) ? $_POST['arena'] : '';
    $cardType = $_POST['cardType'];
    $isUnique = isset($_POST['isUnique']) ? 1 : 0;
    $setCode = $_POST['setCode'];
    $cardNumber = $_POST['cardNumber'];
    $rarity = $_POST['rarity'];

    // Generate a unique internal ID
    // Get the latest abcdefg ID by finding the highest number
    include './ManualDictionaries.php';
    $uuidData = ManualUUIDLookupData();
    $highestNum = 0;

    foreach ($uuidData as $cardId => $internalId) {
      // Check if the internal ID matches the pattern 'abcdefg' followed by numbers
      if (preg_match('/^abcdefg(\d+)$/', $internalId, $matches)) {
        $num = intval($matches[1]);
        if ($num > $highestNum) {
          $highestNum = $num;
        }
      }
    }

    // Increment the highest number by 1
    $newNum = $highestNum + 1;

    // Format with leading zeros (ensure 3 digits)
    $internalId = 'abcdefg' . str_pad($newNum, 3, '0', STR_PAD_LEFT);

    // Combine aspects
    $aspects = "";
    if (!empty($aspect1)) {
      $aspects = $aspect1;
      if (!empty($aspect2)) {
        $aspects .= "," . $aspect2;
      }
    } elseif (!empty($aspect2)) {
      $aspects = $aspect2;
    }

    // Full card ID with set
    $fullCardId = $setCode . '_' . $cardNumber;

    // Read the dictionary file
    $filePath = __DIR__ . '/ManualDictionaries.php';
    $fileContent = file_get_contents($filePath);

    // Update title
    $fileContent = preg_replace(
      "/\/\/continue manual card titles/",
      "'$internalId' => '" . addslashes($cardTitle) . "',\n    //continue manual card titles",
      $fileContent
    );

    // Update subtitle if not empty
    if (!empty($subtitle)) {
      $fileContent = preg_replace(
      "/\/\/continue manual card subtitles/",
      "'$internalId' => '" . addslashes($subtitle) . "',\n    //continue manual card subtitles",
      $fileContent
      );
    }

    // Update cost
    $fileContent = preg_replace(
      "/\/\/continue manual card costs/",
      "'$internalId' => $cost,\n    //continue manual card costs",
      $fileContent
    );

    // Update HP if not null
    if ($hp !== null) {
      $fileContent = preg_replace(
      "/\/\/continue manual card HP dictionary/",
      "'$internalId' => $hp,\n    //continue manual card HP dictionary",
      $fileContent
      );
    }

    // Update power if not null
    if ($power !== null) {
      $fileContent = preg_replace(
      "/\/\/continue manual card powers/",
      "'$internalId' => $power,\n    //continue manual card powers",
      $fileContent
      );
    }

    // Update upgrade HP if not null
    if ($upgradeHp !== null) {
      $fileContent = preg_replace(
      "/\/\/continue manual card upgrade HP dictionary/",
      "'$internalId' => $upgradeHp,\n    //continue manual card upgrade HP dictionary",
      $fileContent
      );
    }

    // Update upgrade power if not null
    if ($upgradePower !== null) {
      $fileContent = preg_replace(
      "/\/\/continue manual card upgrade powers/",
      "'$internalId' => $upgradePower,\n    //continue manual card upgrade powers",
      $fileContent
      );
    }

    // Update aspects
    if (!empty($aspects)) {
      $fileContent = preg_replace(
      "/\/\/continue manual card aspects/",
      "'$internalId' => '" . addslashes($aspects) . "',\n    //continue manual card aspects",
      $fileContent
      );
    }

    // Update traits
    $fileContent = preg_replace(
      "/\/\/continue manual card traits/",
      "'$internalId' => '" . addslashes($traits) . "',\n    //continue manual card traits",
      $fileContent
    );

    // Update arena if not empty
    if (!empty($arena)) {
      $fileContent = preg_replace(
      "/\/\/continue manual card arenas/",
      "'$internalId' => '" . addslashes($arena) . "',\n    //continue manual card arenas",
      $fileContent
      );
    }

    // Update card type
    $fileContent = preg_replace(
      "/\/\/continue manual card types/",
      "'$internalId' => '" . addslashes($cardType) . "',\n    //continue manual card types",
      $fileContent
    );

    // Update unique status
    if ($isUnique) {
      $fileContent = preg_replace(
      "/\/\/continue manual card unique status/",
      "'$internalId' => 1,\n    //continue manual card unique status",
      $fileContent
      );
    }

    // Update set
    $fileContent = preg_replace(
      "/\/\/continue manual card sets/",
      "'$internalId' => '" . addslashes($setCode) . "',\n    //continue manual card sets",
      $fileContent
    );

    // Update UUID lookup
    $fileContent = preg_replace(
      "/\/\/continue manual UUID lookups/",
      "'$fullCardId' => '$internalId',\n    //continue manual UUID lookups",
      $fileContent
    );

    // Update card ID lookup
    $fileContent = preg_replace(
      "/\/\/continue manual card ID lookups/",
      "'$internalId' => '$fullCardId',\n    //continue manual card ID lookups",
      $fileContent
    );

    // Update rarity
    $fileContent = preg_replace(
      "/\/\/continue manual card rarities/",
      "'$internalId' => '" . addslashes($rarity) . "',\n    //continue manual card rarities",
      $fileContent
    );

    // Update the CardTitlesData function
    $fileContent = preg_replace(
      "/\/\/to be added to the CardTitles function output\s*return '(.*)';/",
      "//to be added to the CardTitles function output\n    return '$1|" . addslashes($cardTitle) . "';",
      $fileContent
    );

    //Update the IsUnimplementedData function
    $fileContent = preg_replace(
      "/\/\/continue manual card unimplemented status/",
      "'$internalId' => true,\n    //continue manual card unimplemented status",
      $fileContent
    );

    // Write the updated content back to the file
    if (file_put_contents($filePath, $fileContent)) {
      echo "<div class='alert alert-success'>Card added successfully!</div>";
    } else {
      echo "<div class='alert alert-danger'>Error updating file. Check permissions.</div>";
    }

    // Handle image upload
    if (isset($_FILES['cardImage']) && $_FILES['cardImage']['error'] == 0) {
      // Get file info
      $fileTemp = $_FILES['cardImage']['tmp_name'];

      // Create target filename based on the card ID
      $targetFileName = $internalId . '.webp';

      //the directories where the images will be saved
      $originalDir = __DIR__ . '/Images/';
      $webp2Dir = __DIR__ . '/WebpImages2/';
      $unimplsDir = __DIR__ . '/UnimplementedCards/';

      // Save original image
      $originalPath = "{$originalDir}{$targetFileName}";
      $webp2Path = "{$webp2Dir}{$targetFileName}";
      $unimplsPath = "{$unimplsDir}{$targetFileName}";

      if (move_uploaded_file($fileTemp, $originalPath)) {
        // Copy from original to other directories
        $webp2Success = copy($originalPath, $webp2Path);
        $unimplsSuccess = copy($originalPath, $unimplsPath);
      }

      if ($webp2Success && $unimplsSuccess) {
        echo "<div class='alert alert-success'>Original image saved successfully to Images/, WebpImages2/, and UnimplementedCards/!</div>";

      // Create a 400x400 cropped version
      $croppedDir = __DIR__ . '/concat/';
      $source = imagecreatefrompng($originalPath);

      if ($source) {
        // Get source dimensions
        $sourceWidth = imagesx($source);
        $sourceHeight = imagesy($source);

        // Create empty square destination image
        $destination = imagecreatetruecolor(400, 400);

        // Preserve transparency
        imagealphablending($destination, false);
        imagesavealpha($destination, true);

        // Calculate crop dimensions
        $squareSize = min($sourceWidth, $sourceHeight);
        $srcX = ($sourceWidth - $squareSize) / 2;
        $srcY = ($sourceHeight - $squareSize) / 24;

        // Crop and resize
        imagecopyresampled(
        $destination,
        $source,
        0, 0, $srcX, $srcY,
        400, 400,
        $squareSize, $squareSize
        );

        // Save the cropped image
        $croppedPath = $croppedDir . $targetFileName;
        imagepng($destination, $croppedPath, 9);

        echo "<div class='alert alert-success'>Cropped 400x400 image created successfully!</div>";

        // Free memory
        imagedestroy($source);
        imagedestroy($destination);
      } else {
        echo "<div class='alert alert-danger'>Error processing the image.</div>";
      }
      } else {
      echo "<div class='alert alert-danger'>Error saving the image.</div>";
      }
    }
  }
  ?>
</div>