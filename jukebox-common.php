<?php

function convertAndGetSettings($filename)
{
  global $settings;

  $cfgFile = $settings['configDirectory'] . "/plugin.fpp-" . $filename . ".json";
  if (file_exists($cfgFile)) {
    $j = file_get_contents($cfgFile);
    $json = json_decode($j, true);
    return $json;
  }
  // Create json for config not found
  if ($filename == 'jukebox') {
    $j = json_encode(["items" => []]);
  }
  // Create json for counts not found
  if ($filename == 'jukebox-counts') {
    $j = json_encode([]);
  }
  return json_decode($j, true);
}

function writeToJsonFile($filename, $data)
{
  global $settings;

  $cfgFile = $settings['configDirectory'] . "/plugin.fpp-jukebox-" . $filename . ".json";
  $json_data = json_encode($data);
  file_put_contents($cfgFile, $json_data);
}

function readCounts()
{
  global $settings;

  $url = $settings['configDirectory'] . "/plugin.fpp-jukebox-counts.json";
  $config = file_get_contents($url);
  $config = utf8_encode($config);
  return json_decode($config);
}
