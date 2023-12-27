<?php

namespace ProcessWire;

$info = [
  'title' => 'RockFrontend',
  'version' => json_decode(file_get_contents(__DIR__ . "/package.json"))->version,
  'summary' => 'Module for easy frontend development',
  'autoload' => true,
  'singular' => true,
  'icon' => 'paint-brush',
  // composer dependencies set to php8.1
  'requires' => [
    'PHP>=8.1',
  ],
  // The module will work without RockMigrations but you will have to create
  // the layout field manually and add it to templates if you want to use it
  // I'm not using the layout field though, so this feature might be dropped
];
