<?php namespace RockFrontend;

use ProcessWire\RockFrontend;
use ProcessWire\WireData;

class Asset extends WireData {

  public $ext;
  public $m;
  public $path;
  public $suffix;

  public function __construct($file, $suffix = '') {
    /** @var RockFrontend $rockfrontend */
    $rockfrontend = $this->wire->modules->get('RockFrontend');
    $this->path = $rockfrontend->getFile($file, true);
    $this->url = $rockfrontend->url($file);
    $this->m = is_file($this->path) ? filemtime($this->path) : null;
    $this->suffix = $suffix;
    $this->ext = strtolower(pathinfo($this->path, PATHINFO_EXTENSION));
  }

  public function __debugInfo() {
    return [
      'path' => $this->path,
      'm' => $this->m,
      'suffix' => $this->suffix,
      'ext' => $this->ext,
    ];
  }

}
