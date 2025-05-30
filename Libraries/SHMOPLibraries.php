<?php

/*
1 - Update Number
2 - P1 Last Connection Time
3 - P2 Last Connection Time
4 - Player 1 status
5 - Player 2 status
6 - Last gamestate update (time)
7 - P1 Hero
8 - P2 Hero
9 - Game visibility (1 = public, 0 = private)
10 - Is Replay
11 - Number P2 disconnects
12 - Current player status (0 = active, 1 = inactive)
13 - Format (see function FormatCode)
14 - Game status (see $GS_ constants)
15 - P1 Disconnect Status (0 = connected, 1 = first warning, 2 = second warning, 3 = disconnected; opponent can claim victory)
16 - P2 Disconnect Status
17 - Last Action Time
18 - Last Action Warning (0 = no warning, 1 = player 1, 2 = player 2)
19 - Player Autopassed Last Turn (0 = none, 1 = player 1, 2 = player 2)
20 - P1 Base
21 - P2 Base
22 - P1 Second Hero
23 - P2 Second Hero
24 - Round Game Number (eg. for a Bo3)
25 - P1 Game Wins
26 - P2 Game Wins
27 - P1 Undo Count per Round
28 - P2 Undo Count per Round
29 - P1 Revert Count Per Round
30 - P2 Revert Count Per Round
*/

// $useRedis = getenv('REDIS_ENABLED') ?? false;
$useRedis = false;
$redisHost = (!empty(getenv("REDIS_HOST")) ? getenv("REDIS_HOST") : "127.0.0.1");
$redisPort = (!empty(getenv("REDIS_PORT")) ? getenv("REDIS_PORT") : "6379");

if ($useRedis) {
  $redis = new Redis();
  $redis->connect($redisHost, $redisPort);
}

function WriteCache($name, $data)
{
  global $useRedis, $redis;
  //DeleteCache($name);
  if($name == 0) return;
  $serData = serialize($data);

  if($useRedis)
  {
    $redis->set($name, $serData);
  }
  else {
    $id = shmop_open($name, "c", 0644, 256);
    if($id == false) {
      exit;
     } else {
        $rv = shmop_write($id, $serData, 0);
    }
  }
}

function ReadCache($name)
{
  global $useRedis, $redis;
  if($name == 0) return "";

  $data = "";
  if($useRedis)
  {
    $data = RedisReadCache($name);
    if($data == "" && is_numeric($name))
    {
      $data = ShmopReadCache($name);
    }
  }
  else {
    $data = ShmopReadCache($name);
  }

  return unserialize($data);
}

function ShmopReadCache($name)
{
  @$id = shmop_open($name, "a", 0, 0);
  if(empty($id) || $id == false)
  {
    return "";
  }
  $data = shmop_read($id, 0, shmop_size($id));
  $data = preg_replace_callback( '!s:(\d+):"(.*?)";!', function($match) {
    return ($match[1] == strlen($match[2])) ? $match[0] : 's:' . strlen($match[2]) . ':"' . $match[2] . '";';
    }, $data);
  return $data;
}

function RedisReadCache($name)
{
  global $redis;
  if(!isset($redis)) $redis = RedisConnect();
  return $data = $redis->get($name);
}

function RedisConnect()
{
  global $redis, $redisHost, $redisPort;
  $redis = new Redis();
  $redis->connect($redisHost, $redisPort);
  return $redis;
}

function DeleteCache($name)
{
  global $useRedis, $redis;
  if($useRedis)
  {
    $redis->del($name);
    $redis->del($name . "GS");
  }
  //Always try to delete shmop
  $id=shmop_open($name, "w", 0644, 256);
  if($id)
  {
    shmop_delete($id);
    shmop_close($id); //shmop_close is deprecated
  }
}

function SHMOPDelimiter()
{
  return "!";
}

function SetCachePiece($name, $piece, $value)
{
  $piece -= 1;
  $cacheVal = ReadCache($name);
  if($cacheVal == "") return;
  $cacheArray = explode("!", $cacheVal);
  $cacheArray[$piece] = $value;
  WriteCache($name, implode("!", $cacheArray));
}

function GetCachePiece($name, $piece)
{
  $piece -= 1;
  $cacheVal = ReadCache($name);
  $cacheArray = explode("!", $cacheVal);
  if($piece >= count($cacheArray)) return "";
  return $cacheArray[$piece];
}

function IncrementCachePiece($gameName, $piece)
{
  $oldVal = GetCachePiece($gameName, $piece);
  SetCachePiece($gameName, $piece, $oldVal+1);
  return $oldVal+1;
}

function GamestateUpdated($gameName)
{
  global $currentPlayer;
  $cache = ReadCache($gameName);
  $cacheArr = explode(SHMOPDelimiter(), $cache);
  $cacheArr[0]++;
  $currentTime = round(microtime(true) * 1000);
  $cacheArr[5] = $currentTime;
  WriteCache($gameName, implode(SHMOPDelimiter(), $cacheArr));
}

?>
