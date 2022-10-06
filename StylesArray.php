<?php

namespace RockFrontend;

use ProcessWire\Less;
use ProcessWire\RockFrontend;
use ProcessWire\WireData;

class StylesArray extends AssetsArray
{

  const cacheName = 'rockfrontend-stylesarray-cache';
  const comment = '<!-- rockfrontend-styles-head -->';

  protected $vars = [];

  /**
   * Add all files of folder to assets array
   *
   * Depth is 2 to make it work with RockPageBuilder by default.
   *
   * @return self
   */
  public function addAll($path, $suffix = '', $levels = 2, $ext = ['css', 'less'])
  {
    return parent::addAll($path, $suffix, $levels, $ext);
  }

  public function render($options = [])
  {
    if (is_string($options)) $options = ['indent' => $options];

    // TODO make API version of options to support hook injected assets

    // setup options
    $opt = $this->wire(new WireData());
    /** @var WireData $opt */
    $opt->setArray([
      'debug' => $this->wire->config->debug,
      'indent' => '  ',
      'cssDir' => "/site/templates/bundle/",
      'cssName' => $this->name,
      'sourcemaps' => $this->wire->config->debug,
    ]);
    $opt->setArray($this->options);
    $opt->setArray($options);

    $indent = $opt->indent;
    $out = "\n";
    if ($opt->debug) {
      $out .= "$indent<!-- DEBUG enabled! You can disable it either via \$config or use \$rf->styles()->setOptions(['debug'=>false]) -->\n";
      if ($this->opt('autoload')) {
        $out .= "$indent<!-- autoloading of default styles enabled - disable using ->setOptions(['autoload'=>false]) -->\n";
      }
    }
    $out .= $this->name == 'head' ? $indent . self::comment . "\n" : '';

    // if there are any less files we render them at the beginning
    // this makes it possible to overwrite styles via plain CSS later
    /** @var Less $less */
    $less = $this->wire->modules->get('Less');
    $lessCache = $this->wire->cache->get(self::cacheName);
    $lessCurrent = ''; // string to store file info
    $m = 0;
    $filesCnt = 0;

    // parse all less files
    foreach ($this as $asset) {
      // bd($asset);
      if ($asset->ext !== 'less') continue;
      if ($opt->debug) $out .= "$indent<!-- loading {$asset->path} -->{$asset->debug}\n";
      if (!$less) {
        $out .= "$indent<script>alert('install Less module for parsing {$asset->url}')</script>\n";
        continue;
      }
      $less->addFile($asset->path);
      $filesCnt++;
      if ($asset->m > $m) $m = $asset->m;
      $lessCurrent .= $asset->path . "|" . $asset->m . "--";
    }
    if ($less and $filesCnt) {
      $cssPath = $this->wire->config->paths->root . ltrim($opt->cssDir, "/");
      $cssFile = $cssPath . $opt->cssName . ".css";

      $recompile = false;
      if (!is_file($cssFile)) $recompile = true;
      elseif ($lessCurrent !== $lessCache) $recompile = true;
      elseif ($this->wire->session->get(RockFrontend::recompile)) $recompile = true;

      // create css file
      $m = "?m=$m";
      $url = str_replace(
        $this->wire->config->paths->root,
        $this->wire->config->urls->root,
        $cssFile
      );
      if ($recompile) {
        if (!is_dir($cssPath)) $this->wire->files->mkdir($cssPath);
        $less->setOptions([
          'sourceMap' => $opt->sourcemaps,
        ]);

        // modify variables
        // you can add color modifications via PHP like this:
        // $rf->styles()->setVar('alfred-primary', 'blue');
        $less->parser()->ModifyVars($this->vars);

        // save css file to disk
        $less->saveCss($cssFile);
        $this->wire->cache->save(self::cacheName, $lessCurrent);
        $this->wire->session->set(RockFrontend::recompile, false);
        $this->log("Recompiled RockFrontend $url");
      }
      $debug = $opt->debug ? "<!-- less compiled by RockFrontend -->" : '';
      $out .= "$indent<link rel='stylesheet' href='{$url}$m'>$debug\n";
      $indent = '  ';
    }

    // render all assets
    foreach ($this as $asset) {
      if ($asset->ext === 'less') continue;
      if ($asset instanceof AssetComment) {
        $out .= "$indent<!-- {$asset->comment} -->\n";
        continue;
      }

      $m = $asset->m ? "?m=" . $asset->m : "";

      // add rel=stylesheet if no other relation is set
      $suffix = " " . $asset->suffix;
      $rel = " rel='stylesheet'";
      if (strpos($suffix, " rel=") === false) $suffix .= $rel;

      $debug = $opt->debug ? $asset->debug : '';
      $out .= "$indent<link href='{$asset->url}$m' $suffix>$debug\n";
      $indent = '  ';
    }
    return $out;
  }

  /**
   * Set a less variable
   * @return array
   */
  public function setVar($key, $value)
  {
    $vars = $this->vars;
    $vars[$key] = $value;
    return $this->vars = $vars;
  }
}
