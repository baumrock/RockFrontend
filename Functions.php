<?php
use ProcessWire\ProcessWire;
function alfred($page = null, $options = []) {
  return ProcessWire::getCurrentInstance()->modules->get('RockFrontend')->alfred($page, $options);
}
