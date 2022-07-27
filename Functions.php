<?php
use ProcessWire\ProcessWire;

// alfred function for frontend editing
function alfred($page = null, $options = []) {
  return ProcessWire::getCurrentInstance()->modules->get('RockFrontend')->alfred($page, $options);
}

// translation function for LATTE files
function x($key) {
  return ProcessWire::getCurrentInstance()
    ->modules->get('RockFrontend')
    ->getTranslation($key);
}
