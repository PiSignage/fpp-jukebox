<?php

include_once 'jukebox-common.php';


$command_array = array(
  'save_song_count' => 'SaveSongCount'
);

$command = "";
$args = array();

if (isset($_GET['command']) && !empty($_GET['command'])) {
  $command = $_GET['command'];
  $args = $_GET;
} elseif (isset($_POST['command']) && !empty($_POST['command'])) {
  $command = $_POST['command'];
  $args = $_POST;
}

if (array_key_exists($command, $command_array)) {
  global $debug;

  if ($debug) {
    error_log("Calling " . $command);
  }

  call_user_func($command_array[$command]);
}
return;

function SaveSongCount()
{
  $counts = convertAndGetSettings('jukebox-counts');

  $item = $_POST['item'];
  if (isset($counts[$item])) {
    $count = (int) $counts[$item];
    $counts[$item] = $count + 1;
  } else {
    $counts[$item] = 1;
  }

  writeToJsonFile('counts', $counts);
}
