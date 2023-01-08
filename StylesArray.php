<?php

namespace RockFrontend;

use Latte\Runtime\Html;
use ProcessWire\Less;
use ProcessWire\RockFrontend;
use ProcessWire\WireArray;
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

  /**
   * @return self
   */
  public function addDefaultFolders()
  {
    if ($this->wire->page->template == 'admin') return $this;

    // add all style files in the following folders
    $this->addAll('/site/templates/layouts');
    $this->addAll('/site/templates/less');
    $this->addAll('/site/templates/sections');
    $this->addAll('/site/templates/partials');
    $this->addAll('/site/assets/RockMatrix');

    // add the webfonts.css file if it exists
    $file = $this->rockfrontend()->getFile('/site/templates/webfonts/webfonts.css');
    if (is_file($file)) $this->add($file);

    return $this;
  }

  private function addInfo($opt)
  {
    $indent = $opt->indent;
    $out = "\n";
    if ($opt->debug) {
      $out .= "$indent<!-- DEBUG enabled! You can disable it either via \$config or use \$rf->styles()->setOptions(['debug'=>false]) -->\n";
    }
    if ($this->name == 'head') $out .= $indent . self::comment . "\n";
    return $out;
  }

  /**
   * Parse LESS files and add the generated CSS file to output
   * If there are any less files we render them at the beginning.
   * This makes it possible to overwrite styles via plain CSS later.
   */
  private function parseLessFiles($opt)
  {
    /** @var Less $less */
    $less = $this->wire->modules->get('Less');
    $lessCache = $this->wire->cache->get(self::cacheName);
    $lessCurrent = ''; // string to store file info
    $m = 0;
    $parse = false;
    $entries = new WireArray();

    // loop all less files and add them to the less parser
    // this will also memorize the latest file timestamp to check for recompile
    foreach ($this as $asset) {
      if ($asset->ext !== 'less') continue;
      if ($opt->debug) {
        $entries->add(new AssetComment("loading {$asset->url} ({$asset->debug()})"));
      }
      if (!$less) {
        $entries->add(new AssetComment("install Less module for parsing {$asset->url}"));
        continue;
      }
      $less->addFile($asset->path);
      $parse = true;
      if ($asset->m > $m) $m = $asset->m;
      $lessCurrent .= $asset->path . "|" . $asset->m . "--";
    }

    // we have a less parser installed and some less files to parse
    if ($less and $parse) {
      $cssPath = $this->wire->config->paths->root . ltrim($opt->cssDir, "/");
      $cssFile = $cssPath . $opt->cssName . ".css";

      $recompile = false;
      // if it is a livereload stream we do not recompile
      if ($this->rockfrontend()->isLiveReload) $recompile = false;
      elseif (!is_file($cssFile)) $recompile = true;
      elseif ($lessCurrent !== $lessCache) $recompile = true;
      elseif ($this->wire->session->get(RockFrontend::recompile)) $recompile = true;
      else {
        // check if any of the less files in RockFrontend module folder have changed
        foreach (glob(__DIR__ . "/less/*.less") as $f) {
          if (filemtime($f) > filemtime($cssFile)) $recompile = true;
        }
      }

      // create css file
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

      $asset = new Asset($cssFile);
      $asset->debug('LESS compiled by RockFrontend');
      $entries->add($asset);
    }

    foreach ($entries->reverse() as $entry) $this->prepend($entry);
  }

  /**
   * Add some postCSS magic to css files
   */
  public function postCSS($asset)
  {
    if (!$this->rockfrontend()->isEnabled('postCSS')) return $asset;

    $markup = $asset->getPostCssMarkup();
    if (!$markup) return $asset;
    $rf = $this->rockfrontend();

    // write markup to cached file
    $newFile = $rf->assetPath("css/" . $asset->basename);
    if ($rf->isNewer($asset->path, $newFile)) {
      // asset has been changed, update cached file
      $markup = $rf->postCSS($markup);
      $rf->writeAsset($newFile, $markup);
    }
    $asset->setPath($newFile);
    return $asset;
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

    // make sure that cssDir is a relative path
    // if a path was provided we strip the pw root
    $opt->cssDir = str_replace(
      $this->wire->config->paths->root,
      $this->wire->config->urls->root,
      $opt->cssDir
    );

    $this->parseLessFiles($opt);
    $out = $this->renderAssets($opt);
    if ($out) $out = $this->addInfo($opt) . $out;
    try {
      return new Html($out);
    } catch (\Throwable $th) {
      return $out;
    }
  }

  /**
   * Create markup for including all assets
   */
  public function ___renderAssets($opt): string
  {
    $out = '';
    foreach ($this as $asset) {
      if ($asset->ext === 'less') continue;
      $asset = $this->minify($asset);
      $asset = $this->postCSS($asset);
      $out .= $this->renderTag($asset, $opt, 'style');
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
