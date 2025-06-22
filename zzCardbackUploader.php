<meta name="viewport" content="width=device-width, initial-scale=0.67">
<?php

include_once 'MenuBar.php';
include_once './includes/functions.inc.php';
include_once "./includes/dbh.inc.php";
include_once './AccountFiles/AccountSessionAPI.php';

if (strpos($_SERVER['HTTP_HOST'], 'localhost') !== 0) {
  exit;
}

if (!IsUserLoggedInAsMod()) {
  echo ("You must log in to use this page.");
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $displayName = $_POST['displayName'] ?? '';
  $fileName = $_POST['fileName'] ?? '';

  $errors = [];

  if (empty($displayName || $fileName)) {
    $errors[] = "Display name and file name are required.";
  }

  if (!empty($_FILES['image400']['name'])) {
    $image400 = $_FILES['image400'];
    $allowedTypes = ['image/png', 'image/jpeg', 'image/jpg'];
    if (!in_array($image400['type'], $allowedTypes)) {
      $errors[] = "400x400 image must be a PNG, JPEG, or JPG file.";
    }
    if ($image400['size'] > 500000) {
      $errors[] = "400x400 image file size must be under 500KB.";
    }
  } else {
    $errors[] = "400x400 image is required.";
  }

  if (!empty($_FILES['image450']['name'])) {
    $image450 = $_FILES['image450'];
    $allowedTypes = ['image/png', 'image/jpeg', 'image/jpg'];
    if (!in_array($image450['type'], $allowedTypes)) {
      $errors[] = "450x628 image must be a PNG, JPEG, or JPG file.";
    }
    if ($image450['size'] > 500000) {
      $errors[] = "450x628 image file size must be under 500KB.";
    }
  }

  if (empty($errors)) {
    $uploadDir400 = './concat/';
    $uploadDir450 = './WebpImages2/';
    //get cardback ID from latest in #GetCardBack function incrementing 1
    $getCardBackPath = './Libraries/PlayerSettings.php';
    $fileContents = file_get_contents($getCardBackPath);
    preg_match_all('/(\d+)\s*=>\s*"([^"]+)"/', $fileContents, $matches);
    $cardbackIds = array_map('intval', $matches[1]);
    $cardbackNames = $matches[2];
    $cardbackId = max($cardbackIds) + 1; // Increment the highest ID by 1
    $displayName = htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8'); // Sanitize cardback name

    $filePath400 = $uploadDir400 . $fileName . '.webp';
    $filePath450 = $uploadDir450 . $fileName . '.webp';

    //convert images to webp format before uploading
    $image400Path = $image400['tmp_name'];
    $image450Path = !empty($image450) ? $image450['tmp_name'] : null;
    if(!$image450Path) {
      $image450Path = $image400Path; // Use the 400x400 image if 450x628 is not provided
    }
    $image400Webp = imagecreatefromstring(file_get_contents($image400Path));
    $image450Webp = imagecreatefromstring(file_get_contents($image450Path));
    if ($image400Webp === false || $image450Webp === false) {
      $errors[] = "Failed to create image from uploaded file.";
    } else {
      // Save the images as webp
      if (!imagewebp($image400Webp, $filePath400)) {
        $errors[] = "Failed to save 400x400 image as webp.";
      }
      if (!imagewebp($image450Webp, $filePath450)) {
        $errors[] = "Failed to save 450x628 image as webp.";
      }
      imagedestroy($image400Webp);
      imagedestroy($image450Webp);
    }

    if (empty($errors)) {
      $getCosmeticsPath = './APIs/GetCosmetics.php';
      $codeToAdd = <<<PHP

\$cardBack = new stdClass();
\$cardBack->name = "$displayName";
\$cardBack->id = $cardbackId;
\$response->cardBacks[] = \$cardBack;

PHP;

      $fileContents = file_get_contents($getCosmeticsPath);
      $insertPosition = strpos($fileContents, '//continue adding card backs here')-1;
      if ($insertPosition !== false) {
        $fileContents = substr_replace($fileContents, $codeToAdd, $insertPosition, 0);
        file_put_contents($getCosmeticsPath, $fileContents);
        echo "Cardback successfully uploaded and added to GetCosmetics.php.<br/>";
      } else {
        $errors[] = "Failed to update GetCosmetics.php.";
      }
    }
    // Update Cosmetics.php to include the new cardback
    $cosmeticsPath = './components/Cosmetics.php';
    $cosmeticsContents = file_get_contents($cosmeticsPath);

    $newOption = " \$rv .= CreateSelectOption(\$SET_Cardback . \"-$cardbackId\", \"$displayName\", \$SET_Cardback . \"-\" . \$settings[\$SET_Cardback]);\n";

    $insertPosition = strpos($cosmeticsContents, '//continue adding card backs here')-1;
    if ($insertPosition !== false) {
      $cosmeticsContents = substr_replace($cosmeticsContents, $newOption, $insertPosition, 0);
      file_put_contents($cosmeticsPath, $cosmeticsContents);
      echo "Cardback successfully added to Cosmetics.php.<br/>";
    } else {
      $errors[] = "Failed to update Cosmetics.php.";
    }
  }

  // Update the GetCardBack function to include the new cardback
  $playerSettingsPath = './Libraries/PlayerSettings.php';
  $playerSettingsContents = file_get_contents($playerSettingsPath);

  $newCardbackCase = " $cardbackId => \"$fileName\",\n";

  $insertPosition = strpos($playerSettingsContents, '//continue adding card backs here')-1;
  if ($insertPosition !== false) {
    $playerSettingsContents = substr_replace($playerSettingsContents, $newCardbackCase, $insertPosition, 0);
    file_put_contents($playerSettingsPath, $playerSettingsContents);
    echo "Cardback successfully added to GetCardBack function.</br/>";
  } else {
    $errors[] = "Failed to update GetCardBack function.";
  }

  if (!empty($errors)) {
    foreach ($errors as $error) {
      echo "<p style='color:red;'>$error</p>";
    }
  }
}
?>

<div style='padding:10px; width:80vw; max-width: 640px; height: 70vh; margin: 20vh auto;
  background-color:rgba(74, 74, 74, 0.9); border: 2px solid #1a1a1a; border-radius: 5px; overflow-y: scroll;'>
<h2>Cardback Uploader</h2>
<form method="POST" enctype="multipart/form-data">
  <label for="displayName" style='font-weight:bolder; margin-left:10px;'>Display Name:</label>
  <input type="text" id="displayName" name="displayName" value="" required><br><br>

  <label for="fileName" style='font-weight:bolder; margin-left:10px;'>File Name:</label>
  <input type="text" id="fileName" name="fileName" value="" required><br><br>

  <label for="image400" style='font-weight:bolder; margin-left:10px;'>400x400 Image (.png or .jpeg/.jpg):</label><br/>
  <input type="file" id="image400" name="image400" accept="image/png, image/jpeg, image/jpg" required><br><br>

  <label for="image450" style='font-weight:bolder; margin-left:10px;'>450x628 Image (.png or .jpeg/.jpg, OPTIONAL):</label><br/>
  <input type="file" id="image450" name="image450" accept="image/png, image/jpeg, image/jpg"><br><br>

  <input type="submit" value="Upload Cardback">
</form>
</div>