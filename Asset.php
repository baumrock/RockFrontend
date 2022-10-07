<?php

namespace RockFrontend;

use ProcessWire\RockFrontend;
use ProcessWire\WireData;

class Asset extends WireData
{

  public $debug;
  public $ext;
  public $m;
  public $path;
  public $suffix;
  public $url;
  public $comment;

  public function __construct($file, $suffix = '')
  {
    /** @var RockFrontend $rockfrontend */
    $rockfrontend = $this->wire->modules->get('RockFrontend');
    $this->path = $rockfrontend->getFile($file, true);
    $this->url = $rockfrontend->url($file);

    // inroot check prevents open basedir errors on files that are not found
    // but kept as url to get a 404 in the devtools network tab
    $inRoot = $this->wire->files->fileInPath($this->path, $this->wire->config->paths->root);
    $this->m = ($inRoot and is_file($this->path)) ? filemtime($this->path) : null;

    $this->suffix = $suffix;
    $this->ext = strtolower(pathinfo($this->path, PATHINFO_EXTENSION));
  }

  /**
   * Set debug message or return debug message without html comments <!-- -->
   */
  public function debug($str = null): string
  {
    if ($str) return $this->debug = "<!-- $str -->";
    if (!$this->debug) return '';
    return substr($this->debug, 5, -4);
  }

  public function __debugInfo()
  {
    return [
      'path' => $this->path,
      'url' => $this->url,
      'm' => $this->m,
      'suffix' => $this->suffix,
      'ext' => $this->ext,
      'comment' => $this->comment,
      'debug' => $this->debug,
    ];
  }
}
