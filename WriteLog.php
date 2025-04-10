<?php

function LogPath($gameName, $path="./")
{
  return "{$path}Games/$gameName/gamelog.txt";
}

function CreateLog($gameName, $path="./")
{
  fclose(fopen(LogPath($gameName, $path), "w")); 
}

function FmtPlayer($name, $id) {
  return "<span class='p$id-label'>$name</span>";
}

function FmtKeyword($keyword) {
  return "<span class='keyword'>$keyword</span>";
}

function WriteLog($text, $player = 0, $highlight=false, $path="./", $error=false)
{
  global $gameName;

  if(!($handler = fopen(LogPath($gameName, $path), "a"))) {
    //File does not exist
    return;
  }
  
  $output = $highlight ? "<mark style='background-color: brown; color:azure;'>$text</mark>" : $text;
  $output = $player != 0 ? FmtPlayer($output, $player) : $output;
  $output = "<p class='log-entry'>$output</p>";
  $output = $output . "\r\n";
  $output = $error ? "<span style='color:red;'>$output</span>" : $output;
  
  fwrite($handler, $output);
  fclose($handler);
}

function ClearLog($n=20)
{
  global $gameName;
  /*
  $filename = "./Games/" . $gameName . "/gamelog.txt";
  $handler = fopen($filename, "w");
  fclose($handler);
  */

  $filename = LogPath($gameName);
  $handle = fopen($filename, "r");
  $lines = array_fill(0, $n-1, '');
  if ($handle) {
    while (!feof($handle)) {
        $buffer = fgets($handle);
        $lines[] = $buffer;
        array_shift($lines);
    }
    fclose($handle);
  }

  $handle = fopen($filename, "w");
  fwrite($handle, implode("", $lines));
  fclose($handle);

}

function WriteError($text)
{
  WriteLog("ERROR: " . $text);
}

function EchoLog($gameName, $playerID = 0)
{
  $filename = LogPath($gameName);
  $filesize = filesize($filename);
  if ($filesize > 0 && ($handler = fopen($filename, "r"))) {
    $content = fread($handler, $filesize);
    fclose($handler);
    
    if ($playerID == 3) {
      $content = preg_replace('/<span class=\'p1-label bold\'>(.*?)(<img.*?>)?\s*((?!Player 1).*?)<\/span>/', '<span class=\'p1-label bold\'>$2 Player 1</span>', $content);
      $content = preg_replace('/<span class=\'p2-label bold\'>(.*?)(<img.*?>)?\s*((?!Player 2).*?)<\/span>/', '<span class=\'p2-label bold\'>$2 Player 2</span>', $content);
    }
    
    echo($content);
  }
}

function JSONLog($gameName, $path="./")
{
  $filename = LogPath($gameName, $path);
  $filesize = filesize($filename);

  if ($filesize <= 0) {
    return "";
  }

  $handler = fopen($filename, "r");
  $response = fread($handler, $filesize);
  fclose($handler);

  return $response;
}
