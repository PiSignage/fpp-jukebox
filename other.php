<?php

include_once "/opt/fpp/www/common.php";
include_once 'jukebox-common.php';


$command_array = array(
  'save_song_count' => 'SaveSongCount',
  'clear_stats' => 'ClearStats',
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

function setPluginJSON($plugin, $js)
{
  global $settings;

  $cfgFile = $settings['configDirectory'] . "/plugin." . $plugin . ".json";
  file_put_contents($cfgFile, json_encode($js, JSON_PRETTY_PRINT));
  // echo json_encode($js, JSON_PRETTY_PRINT);
}

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

  echo json_encode([
    'error' => false,
    'message' => 'Saved: ' . $_POST['item'],
  ]);
}

function ClearStats()
{
  setPluginJSON('fpp-jukebox-counts', []);
  echo json_encode([
    'error' => false
  ]);
}
