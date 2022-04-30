<?php
use ProcessWire\ProcessWire;
function alfred($page, $options = []) {
  return ProcessWire::getCurrentInstance()->modules->get('RockFrontend')->alfred($page, $options);
}
