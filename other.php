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

  $item = str_replace('.fseq', '', $_POST['item']);
  if (array_search($item, array_column($counts, 'name')) !== FALSE) {
    foreach ($counts as $key => $value) {
      if ($value['name'] == $item) {
        $current_count = (int) $value['count'];
        $counts[$key]['count'] = $current_count + 1;
      }
    }
  } else {
    $counts[] = [
      'name' => $item,
      'count' => 1
    ];
  }

  // echo print_r($counts, true);

  writeToJsonFile('counts', $counts);
}
