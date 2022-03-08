<?php namespace RockFrontend;

use ProcessWire\WireData;

class Asset extends WireData {

  public $m;
  public $path;
  public $suffix;

  public function __construct($file, $suffix = '') {
    /** @var RockFrontend $rockfrontend */
    $rockfrontend = $this->wire->modules->get('RockFrontend');
    $this->path = $rockfrontend->getFile($file);
    $this->url = $rockfrontend->url($file);
    $this->m = is_file($file) ? filemtime($file) : null;
    $this->suffix = $suffix;
  }

  public function __debugInfo() {
    return [
      'path' => $this->path,
      'm' => $this->m,
      'suffix' => $this->suffix,
    ];
  }

}
