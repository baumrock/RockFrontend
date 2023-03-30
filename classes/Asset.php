<?php

namespace RockFrontend;

use ProcessWire\RockFrontend;
use ProcessWire\WireData;

class Asset extends WireData
{

  public $basename;
  public $comment;
  public $debug;
  public $dir;
  public $ext;
  public $filename;
  public $m;
  public $minify = 'auto';
  public $path;
  public $suffix;
  public $url;

  public function __construct($file, $suffix = '')
  {
    $this->setPath($file);

    // inroot check prevents open basedir errors on files that are not found
    // but kept as url to get a 404 in the devtools network tab
    $inRoot = $this->wire->files->fileInPath($this->path, $this->wire->config->paths->root);
    $this->m = ($inRoot and is_file($this->path)) ? filemtime($this->path) : null;

    $this->suffix = $suffix;
    $this->ext = strtolower(pathinfo($this->path, PATHINFO_EXTENSION));
    $this->basename = pathinfo($this->path, PATHINFO_BASENAME);

    $this->dir = dirname($this->path) . "/";
    $this->filename = pathinfo($this->path, PATHINFO_FILENAME);
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

  /**
   * Get postCSS markup
   * Returns FALSE if no postcss markup was found
   */
  public function getPostCssMarkup()
  {
    if (!$this->path) return false;
    // if it is not a regular file we return false
    // this is for css files loaded from CDN for example
    if (!is_file($this->path)) return false;
    $markup = $this->wire->files->fileGetContents($this->path);
    if (!$this->hasPostCss($markup)) return false;
    return $markup;
  }

  public function hasPostCss($markup): bool
  {
    foreach ($this->rockfrontend()->postCSS as $k => $v) {
      if (strpos($markup, $k)) return true;
    }
    return false;
  }

  public function isExternal()
  {
    if (strpos($this->path, "http://") === 0) return true;
    if (strpos($this->path, "https://") === 0) return true;
    if (strpos($this->path, "//") === 0) return true;
    return false;
  }

  public function isJS()
  {
    return $this->ext == 'js';
  }

  public function isMin()
  {
    return substr($this->basename, -7) == '.min.js'
      or substr($this->basename, -8) == '.min.css';
  }

  /**
   * Return current path with minified extension
   * foo.js --> foo.min.js
   */
  public function minPath()
  {
    $ext = $this->isJS() ? '.min.js' : '.min.css';
    return $this->dir . $this->filename . $ext;
  }

  public function rockfrontend(): RockFrontend
  {
    return $this->wire->modules->get('RockFrontend');
  }

  /**
   * Update path
   *
   * This also updates the url but keeps comments etc.
   * Needed by StylesArray::postCSS
   */
  public function setPath($path)
  {
    $rockfrontend = $this->rockfrontend();
    $this->path = $rockfrontend->getFile($path, true);
    $this->url = $rockfrontend->url($path);

    // early exit for external files
    if ($this->isExternal()) return;

    // if path and url are the same that means that we requested a file that does not exist
    // in that case we prepend the root path to the url
    if ($this->path and $this->path == $this->url) {
      $this->path = $this->wire->config->paths->root . ltrim($this->url, "/");
    }
  }

  public function __toString()
  {
    return $this->path;
  }

  public function __debugInfo()
  {
    return [
      'basename' => $this->basename,
      'filename' => $this->filename,
      'path' => $this->path,
      'dir' => $this->dir,
      'url' => $this->url,
      'm' => $this->m,
      'minify' => $this->minify,
      'suffix' => $this->suffix,
      'ext' => $this->ext,
      'comment' => $this->comment,
      'debug' => $this->debug,
      'isMin()' => $this->isMin(),
    ];
  }
}
