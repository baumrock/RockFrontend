<?php namespace RockFrontend;

use ProcessWire\RockFrontend;
use ProcessWire\WireData;

class Asset extends WireData {

  public $debug;
  public $ext;
  public $m;
  public $path;
  public $suffix;
  public $comment;

  public function __construct($file, $suffix = '') {
    /** @var RockFrontend $rockfrontend */
    $rockfrontend = $this->wire->modules->get('RockFrontend');
    $this->path = $rockfrontend->getFile($file, true);
    $this->url = $rockfrontend->url($file);

    // inroot check prevents open basedir errors on files that are not found
    // but kept as url to get a 404 in the devtools network tab
    $inRoot = $this->wire->files->fileInPath($this->path, $this->wire->config->paths->root);
    $this->m = ($inRoot AND is_file($this->path)) ? filemtime($this->path) : null;

    $this->suffix = $suffix;
    $this->ext = strtolower(pathinfo($this->path, PATHINFO_EXTENSION));
  }

  public function __debugInfo() {
    return [
      'path' => $this->path,
      'm' => $this->m,
      'suffix' => $this->suffix,
      'ext' => $this->ext,
      'comment' => $this->comment,
      'debug' => $this->debug,
    ];
  }

}
