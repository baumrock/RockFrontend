<?php namespace RockFrontend;

use ProcessWire\RockFrontend;

class AssetsArray extends \ProcessWire\WireArray {

  /**
   * @return self
   */
  public function add($file, $suffix = '') {
    $file = new Asset($file, $suffix);
    parent::add($file);
    return $this;
  }

  /**
   * @return self
   */
  public function addIf($condition, $file, $suffix = '') {
    if($condition) parent::add(new Asset($file, $suffix));
    return $this;
  }

}
