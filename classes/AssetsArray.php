<?php

namespace RockFrontend;

use ProcessWire\Debug;
use ProcessWire\RockFrontend;

class AssetsArray extends \ProcessWire\WireArray
{

  const debugInfo = "<!-- These comments are only visible when \$config->debug = true; -->\n";

  public $minify = false;
  public $name;
  public $noNewLine = false;
  public $rendered = false;

  public function __construct(string $name)
  {
    $this->name = $name;
    $this->autoload(true);
    parent::__construct();
  }

  protected function addInfo($opt)
  {
    if (!$opt->debug) return "";
    $indent = $opt->indent;
    $out = "$indent<!-- {$this->className()} '{$this->name}' -->\n";
    $out .= $indent . self::debugInfo;
    return $out;
  }

  /**
   * Add file to assets array
   *
   * Usage:
   * $rf->styles()->add("/path/to/file.css");
   *
   * Set noMinify property:
   * $rf->styles()->add("/path/to/file.css", '', ['noMinify' => true]);
   *
   * @return self
   */
  public function add(
    $file,
    $suffix = '',
    $properties = [],
    $quiet = false,
  ) {
    $debug = $this->getDebugNote($file);
    if (is_string($file)) $file = new Asset($file, $suffix);

    // early quiet exit if file does not exist and quiet parameter is set
    if (!is_file($file->path) and $quiet) return $this;

    foreach ($properties as $k => $v) $file->$k = $v;
    // prevent adding file multiple times
    if ($exists = $this->get('path=' . $file->path)) {
      foreach ($properties as $k => $v) $exists->$k = $v;
      return $this;
    }
    $file->debug = $debug;
    parent::add($file);
    return $this;
  }

  public function autoload($bool): self
  {
    $rf = $this->rockfrontend();
    if ($bool === true) {
      if ($this instanceof StylesArray) $rf->autoloadStyles->add($this);
      if ($this instanceof ScriptsArray) $rf->autoloadScripts->add($this);
    } else {
      if ($this instanceof StylesArray) $rf->autoloadStyles->remove($this);
      if ($this instanceof ScriptsArray) $rf->autoloadScripts->remove($this);
    }
    return $this;
  }

  public function getDebugNote($file = null)
  {
    if ($file instanceof Asset) return $file->debug;
    if ($file instanceof AssetComment) return $file->debug;
    $trace = array_reverse(Debug::backtrace(['getFile' => 'basename']));
    $debug = '';
    foreach ($trace as $i => $item) {
      if ($debug) continue;
      $call = $item['call'];
      $match = false;
      if (strpos($call, "ScriptsArray->") === 0) $match = true;
      if (strpos($call, "StylesArray->") === 0) $match = true;
      if ($match) $debug = "<!-- " . $item['file'] . " -->";
    }
    // bd($debug, $file);
    // bd($trace);
    return $debug;
  }

  /**
   * Add all files of folder to assets array
   *
   * Depth is 2 to make it work with RockPageBuilder by default.
   *
   * @return self
   */
  public function addAll(
    $path,
    $suffix = '',
    $levels = 2,
    $ext = ['js'],
    $endsWith = null,
  ) {
    /** @var RockFrontend $rf */
    $rf = $this->wire('modules')->get('RockFrontend');
    $path = $rf->getPath($path);
    if (!$path) return $this;
    $files = $this->wire->files->find($path, [
      'recursive' => $levels,
      'extensions' => $ext,
    ]);
    foreach ($files as $f) {
      if ($endsWith && !str_ends_with($f, $endsWith)) continue;
      $this->add($f, $suffix);
    }
    return $this;
  }

  /**
   * @return self
   */
  public function addIf($file, $condition, $suffix = '')
  {
    if (is_string($file)) $file = new Asset($file, $suffix);
    if ($condition) parent::add($file);
    return $this;
  }

  public function comment($str, $prepend = false): self
  {
    $comment = new AssetComment($str);
    $prepend ? $this->prepend($comment) : $this->add($comment);
    return $this;
  }

  public function minify($bool): self
  {
    $this->minify = $bool;
    return $this;
  }

  /**
   * Auto-create minified version of asset
   * See docs here: https://github.com/baumrock/RockFrontend/wiki/Auto-Minify-Feature
   */
  public function minifyAsset(Asset $asset): Asset
  {
    if ($this->minify) return $this->minifyForced($asset);
    else return $this->minifyAuto($asset);
  }

  public function minifyAuto(Asset $asset): Asset
  {
    if (!$this->rockfrontend()->isEnabled('minify')) return $asset;
    if ($asset->isExternal()) return $asset;

    // check file type
    if ($asset->ext == 'js') $search = ".min.js";
    elseif ($asset->ext == 'css') $search = ".min.css";
    else return $asset;

    // check file ending
    $ending = substr($asset->basename, -1 * strlen($search));
    if ($ending !== $search) return $asset;

    // prepare paths
    $min = $asset->path;
    $nomin = substr($min, 0, strlen($min) - strlen($ending)) . "." . $asset->ext;

    // if no unminified file exists we return the asset as is
    if (!is_file($nomin)) return $asset;

    // else we minify the file if it has changed
    $this->rockfrontend()->minifyFile($nomin, $min);

    return $asset;
  }

  public function minifyForced(Asset $asset): Asset
  {
    if ($asset instanceof AssetComment) return $asset;
    if ($asset->isExternal()) return $asset;
    if ($asset->minify === false) return $asset;
    if ($asset->minify === 'auto' and !$this->minify) return $asset;
    if (substr($asset->path, -8) === '.min.css') return $asset;
    if (substr($asset->path, -7) === '.min.js') return $asset;

    $nomin = $asset->path;
    if ($asset->ext == 'css') $min = $asset->dir . $asset->filename . ".min.css";
    else $min = $asset->dir . $asset->filename . ".min.js";

    $asset = new Asset($min, $asset->suffix);

    // else we minify the file if it has changed
    $this->rockfrontend()->minifyFile($nomin, $min);

    return $asset;
  }

  /**
   * @return self
   */
  public function prepend($file, $suffix = '')
  {
    if (is_string($file)) $file = new Asset($file, $suffix);
    $debug = $this->getDebugNote($file);
    $file->debug = $debug;
    parent::prepend($file);
    return $this;
  }

  /**
   * Render script or styles tag
   */
  public function renderTag($asset, $opt, $type): string
  {
    $indent = $opt->indent;
    if ($asset instanceof AssetComment) {
      return "$indent<!-- {$asset->comment} -->\n";
    }

    // set defaults
    $m = $asset->m ? "?m=" . $asset->m : "";
    $suffix = " " . $asset->suffix;
    $debug = $opt->debug ? $asset->debug : '';

    if ($type == 'style') {
      // add rel=stylesheet if no other relation is set
      $rel = " rel='stylesheet'";
      if (strpos($suffix, " rel=") === false) $suffix .= $rel;
    }

    $suffix = trim($suffix);
    if ($suffix) $suffix = " $suffix";

    if ($type == 'style') {
      return "$indent<link href='{$asset->url}$m'$suffix>$debug\n";
    } else {
      return "$indent<script src='{$asset->url}$m'$suffix></script>$debug\n";
    }
  }

  public function rockfrontend(): RockFrontend
  {
    return $this->wire->modules->get('RockFrontend');
  }

  /**
   * Magic toString Method
   * We return an empty string in case an AssetsArray is requested as string
   * This is to make it possible to add scripts and styles from within latte files
   * {$rockfrontend->styles()->add(...)}
   * Without this magic method that would output something like "array|array"
   */
  public function __toString()
  {
    return '';
  }

  public function __debugInfo()
  {
    return array_merge([
      'name' => $this->name,
    ], parent::__debugInfo(), [
      'items' => $this->getArray(),
    ]);
  }
}
