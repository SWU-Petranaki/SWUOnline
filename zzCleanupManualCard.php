<?php
include_once 'MenuBar.php';
include_once './includes/functions.inc.php';

// Authentication check
if (!isset($_SESSION["useruid"])) {
  echo ("Please login to view this page.");
  exit;
}

$useruid = $_SESSION["useruid"];
if ($useruid != "OotTheMonk" && $useruid != "test" && $useruid != "ninin" && $useruid != "Brubraz" && $useruid != "Mobyus1") {
  echo ("You must log in to use this page.");
  exit;
}

// Include the dictionaries
include_once './ManualDictionaries.php';

// Function to get card title for confirmation
function getCardTitle($internalId) {
  $titles = ManualCardTitleData();
  return isset($titles[$internalId]) ? $titles[$internalId] : "Unknown Card";
}

// Process the removal request
$message = "";
$cardTitle = "";
$cardFound = false;

if (isset($_POST['submit'])) {
  $internalId = trim($_POST['internalId']);

  // Validate input format
  if (preg_match('/^[a-z0-9]+$/', $internalId)) {
    $cardTitle = getCardTitle($internalId);
    $cardFound = ($cardTitle !== "Unknown Card");

    if ($cardFound && isset($_POST['confirm'])) {
      // Get the card ID for lookups
      $cardIdLookup = ManualCardIDLookupData();
      $cardId = isset($cardIdLookup[$internalId]) ? $cardIdLookup[$internalId] : "";

      // Read the ManualDictionaries.php file
      $filePath = __DIR__ . '/ManualDictionaries.php';
      $fileContent = file_get_contents($filePath);

      // Remove entries from each dictionary
      $patterns = [
        // Remove from key-based dictionaries (standard pattern)
        "/\s*'$internalId' => [^,]+,\n/",

        // Remove from UUID lookup (value-based)
        "/\s*'$cardId' => '$internalId',\n/",
      ];

      foreach ($patterns as $pattern) {
        $fileContent = preg_replace($pattern, "\n", $fileContent);
      }

      // Special handling for CardTitlesData which is a pipe-delimited string
      if (!empty($cardTitle)) {
        $escapedTitle = preg_quote(addslashes($cardTitle), '/');
        $fileContent = preg_replace("/\|$escapedTitle/", "", $fileContent);
      }

      // Write the updated content back to the file
      if (file_put_contents($filePath, $fileContent)) {
        $message = "<div class='alert alert-success'>Card '$internalId' ($cardTitle) has been removed successfully!</div>";

        // Delete image files if they exist
        $imageLocations = [
          __DIR__ . '/Images/' . $internalId . '.webp',
          __DIR__ . '/WebpImages2/' . $internalId . '.webp',
          __DIR__ . '/UnimplementedCards/' . $internalId . '.webp',
          __DIR__ . '/concat/' . $internalId . '.webp',
        ];

        foreach ($imageLocations as $imgPath) {
          if (file_exists($imgPath)) {
            unlink($imgPath);
          }
        }
      } else {
        $message = "<div class='alert alert-danger'>Error updating file. Check permissions.</div>";
      }
    } elseif (!$cardFound) {
      $message = "<div class='alert alert-warning'>Card with ID '$internalId' was not found.</div>";
    }
  } else {
    $message = "<div class='alert alert-danger'>Invalid internal ID format.</div>";
  }
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
  .btn-danger {
    background-color: #dc3545;
  }
  .btn-danger:hover {
    background-color: #bd2130;
  }
  .alert {
    padding: 15px;
    margin-bottom: 20px;
    border: 1px solid transparent;
    border-radius: 5px;
  }
  .alert-success {
    color: #155724;
    background-color: #d4edda;
    border-color: #c3e6cb;
  }
  .alert-danger {
    color: #721c24;
    background-color: #f8d7da;
    border-color: #f5c6cb;
  }
  .alert-warning {
    color: #856404;
    background-color: #fff3cd;
    border-color: #ffeeba;
  }
  .card-info {
    margin-top: 20px;
    padding: 15px;
    background-color: #e9ecef;
    border-radius: 5px;
  }
</style>

<div style='padding:10px; width:80vw; max-width: 800px; height: 80vh; margin: 8vh auto;
  background-color:rgba(74, 74, 74, 0.9); border: 2px solid #1a1a1a; border-radius: 5px; overflow-y: scroll;'>
  <h2>Manual Card Cleanup</h2>

  <?php echo $message; ?>

  <form method="post" action="">
    <div class="form-group">
      <label for="internalId">Internal Card ID (e.g., abcdefg013)</label>
      <input type="text" name="internalId" id="internalId" required class="form-control"
        value="<?php echo isset($_POST['internalId']) ? htmlspecialchars($_POST['internalId']) : ''; ?>">
    </div>

    <?php if ($cardFound && !isset($_POST['confirm'])): ?>
      <div class="card-info">
        <h3>Card Found: <?php echo htmlspecialchars($cardTitle); ?></h3>
        <p>Are you sure you want to remove this card from the manual dictionaries?</p>
        <p><strong>Warning:</strong> This action cannot be undone!</p>
        <input type="hidden" name="confirm" value="1">
        <button type="submit" name="submit" class="btn btn-primary btn-danger">Yes, Remove This Card</button>
      </div>
    <?php else: ?>
      <button type="submit" name="submit" class="btn btn-primary">Find Card</button>
    <?php endif; ?>
  </form>

  <div style="margin-top: 30px;">
    <h3>Instructions</h3>
    <ol>
      <li>Enter the internal ID of the card you want to remove (format: abcdefg123)</li>
      <li>Click "Find Card" to verify the card exists</li>
      <li>Confirm the removal when prompted</li>
    </ol>
    <p>This tool will remove the card from all manual dictionaries and delete associated image files.</p>
  </div>
</div>