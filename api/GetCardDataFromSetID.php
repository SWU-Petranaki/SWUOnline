<?php
require_once __DIR__ . '/../GeneratedCode/GeneratedCardDictionaries.php';

$cardID = isset($_GET['cardID']) ? $_GET['cardID'] : null;

header('Content-Type: application/json');

$SWUCategoryID = '79';
$SORGroupID = '23405';
$SORProducts = "https://tcgcsv.com/tcgplayer/$SWUCategoryID/$SORGroupID/products";
$SORPrices = "https://tcgcsv.com/tcgplayer/$SWUCategoryID/$SORGroupID/prices";
$SHDGroupID = '23488';
$SHDProducts = "https://tcgcsv.com/tcgplayer/$SWUCategoryID/$SHDGroupID/products";
$SHDPrices = "https://tcgcsv.com/tcgplayer/$SWUCategoryID/$SHDGroupID/prices";
$TWIGroupID = '23597';
$TWIProducts = "https://tcgcsv.com/tcgplayer/$SWUCategoryID/$TWIGroupID/products";
$TWIPrices = "https://tcgcsv.com/tcgplayer/$SWUCategoryID/$TWIGroupID/prices";
$JTLGroupID = '23956';
$JTLProducts = "https://tcgcsv.com/tcgplayer/$SWUCategoryID/$JTLGroupID/products";
$JTLPrices = "https://tcgcsv.com/tcgplayer/$SWUCategoryID/$JTLGroupID/prices";
$LOFGroupID = '24279';
$LOFProducts = "https://tcgcsv.com/tcgplayer/$SWUCategoryID/$LOFGroupID/products";
$LOFPrices = "https://tcgcsv.com/tcgplayer/$SWUCategoryID/$LOFGroupID/prices";

$products = [];
$prices = [];
function fetchAndCacheJson($url, $cacheKey, $cacheTtl = 86400) {
  $cacheDir = sys_get_temp_dir() . '/swu_cache';
  if (!is_dir($cacheDir)) {
    mkdir($cacheDir, 0777, true);
  }
  $cacheFile = $cacheDir . '/' . md5($cacheKey) . '.json';

  if (file_exists($cacheFile) && (time() - filemtime($cacheFile) < $cacheTtl)) {
    $data = file_get_contents($cacheFile);
  } else {
    // Use cURL to fetch the URL
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_USERAGENT, 'PHP-cURL-fetcher/1.0');
    // Optional: disable SSL verify temporarily if you're testing a broken SSL setup
    // curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $data = curl_exec($ch);

    if (curl_errno($ch)) {
      $error = curl_error($ch);
      error_log("cURL Error fetching $url: $error");
      $data = '{}';
    } else {
      $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
      if ($httpCode >= 400) {
        error_log("cURL HTTP $httpCode error on $url");
        $data = '{}';
      } else {
        file_put_contents($cacheFile, $data);
      }
    }

    curl_close($ch);
  }

  return json_decode($data, true, 512, JSON_OBJECT_AS_ARRAY);
}

$products['SOR'] = fetchAndCacheJson($SORProducts, 'SORProducts');
$prices['SOR'] = fetchAndCacheJson($SORPrices, 'SORPrices');
$products['SHD'] = fetchAndCacheJson($SHDProducts, 'SHDProducts');
$prices['SHD'] = fetchAndCacheJson($SHDPrices, 'SHDPrices');
$products['TWI'] = fetchAndCacheJson($TWIProducts, 'TWIProducts');
$prices['TWI'] = fetchAndCacheJson($TWIPrices, 'TWIPrices');
$products['JTL'] = fetchAndCacheJson($JTLProducts, 'JTLProducts');
$prices['JTL'] = fetchAndCacheJson($JTLPrices, 'JTLPrices');
$products['LOF'] = fetchAndCacheJson($LOFProducts, 'LOFProducts');
$prices['LOF'] = fetchAndCacheJson($LOFPrices, 'LOFPrices');


if ($cardID) {
  $uuid = UUIDLookup($cardID);
  $title = CardTitle($uuid);
  $subtitle = CardSubtitle($uuid);
  if ($title === "" && $subtitle === "") {
    http_response_code(404);
    echo json_encode([
      "error" => "Card not found"
    ]);
    exit;
  }
  $fullName = $title . ($subtitle ? " - $subtitle" : "");
  if($cardID == "SOR_156")//Benthic 'Two Tubes' to "Two Tubes"
    $fullName = str_replace("'", '"', $fullName);
  $parts = explode('_', $cardID);
  $setCode = $parts[0] ?? '';
  $setNumber = $parts[1] ?? '';
  if(!isset($products[$setCode]) || !isset($prices[$setCode])) {
    http_response_code(404);
    echo json_encode([
      "error" => "Set not found"
    ]);
    exit;
  }
  //get object where "name" is equal to $fullName
  $product = array_filter($products[$setCode]["results"], function($item) use ($fullName) {
    return isset($item['name']) && $item['name'] === $fullName;
  });
  $productId = array_values($product)[0]['productId'];
  if ($productId) {
    //get object from prices where "productId" is equal to $productId
    $prices = array_filter($prices[$setCode]["results"], function($item) use ($productId) {
      return isset($item['productId']) && $item['productId'] === $productId && $item['subTypeName'] === 'Normal';
    });
  }
  $prices = array_values($prices);

  if ($uuid !== "") {
    echo json_encode([
      "cardID" => $cardID,
      "UUIDLookUp" => $uuid,
      "title" => $title,
      "subtitle" => $subtitle,
      "name" => $fullName,
      "lowPrice" => $prices ? (isset($prices[0]['lowPrice']) ? $prices[0]['lowPrice'] : null) : null,
      "marketPrice" => $prices ? (isset($prices[0]['marketPrice']) ? $prices[0]['marketPrice'] : null) : null,
      "highPrice" => $prices ? (isset($prices[0]['highPrice']) ? $prices[0]['highPrice'] : null) : null,
    ]);
    exit;
  }
}

http_response_code(404);
echo json_encode([
  "error" => "Card ID not found"
]);
?>
