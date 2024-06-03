<?php

namespace RockFrontend;

use Latte\Runtime\Html;
use ProcessWire\Debug;
use ProcessWire\Less;
use ProcessWire\Scss;
use ProcessWire\RockFrontend;
use ProcessWire\WireArray;
use ProcessWire\WireData;

class StylesArray extends AssetsArray
{

  public $cssDir = false;
  protected $vars = [];

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
    $ext = ['css', 'less', 'scss'],
    $endsWith = null,
  ) {
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
    $this->addAll('/site/templates/styles');
    $this->addAll('/site/templates/less');
    $this->addAll('/site/templates/scss');
    $this->addAll('/site/templates/sections');
    $this->addAll('/site/templates/partials');

    // load less files from rockblocks
    $rpb = $this->wire->modules->get("RockPageBuilder");
    if ($rpb && $rpb->useRockBlocks) {
      $this->addAll('/site/modules/RockPageBuilder/blocks');
    }

    // add the webfonts.css file if it exists
    $file = $this->rockfrontend()->getFile('/site/templates/webfonts/webfonts.css');
    if (is_file($file)) $this->add($file);

    return $this;
  }

  /**
   * Parse LESS files and add the generated CSS file to output
   * If there are any less files we render them at the beginning.
   * This makes it possible to overwrite styles via plain CSS later.
   */
  private function parseLessFiles($opt, $cacheName)
  {
    /** @var Less $less */
    $less = $this->wire->modules->get('Less');
    $lessCache = $this->wire->cache->get($cacheName);
    $lessCurrent = ''; // string to store file info
    $mtime = 0;
    $parse = false;
    $entries = new WireArray();
    $intro = "Recompile $cacheName";

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
      if ($asset->m > $mtime) $mtime = $asset->m;
      $lessCurrent .= $asset->path . "|" . $asset->m . "--";
    }

    // if a modification timestamp is set in the options we apply it now
    // this is necessary for making LESS recompile if less variables in
    // PHP have been changed without updating any less files
    $mtime = max($mtime, $opt->lessVarsModified);

    // prepare variables needed for recompile check
    $cssPath = $this->wire->config->paths->root . ltrim($opt->cssDir, "/");
    $cssFile = $cssPath . $opt->cssName . ".css";
    $lessCacheArray = $this->getChangedFiles($lessCache, $lessCurrent);

    // we have a less parser installed and some less files to parse
    if ($less and $parse) {
      $recompile = false;

      // if it is a livereload stream we do not recompile
      if ($this->rockfrontend()->isLiveReload) $recompile = false;

      // if css file does not exist we recompile
      elseif (!is_file($cssFile)) {
        $this->log("$intro: $cssFile does not exist.");
        $recompile = true;
      }

      // cache strings are different
      // that means a file or a timestamp has changed
      elseif ($lessCurrent !== $lessCache) {
        // show info which file changed to log
        foreach ($lessCacheArray as $str) {
          $parts = explode("|", $str);
          $url = $this->rockfrontend()->toUrl($parts[0]);
          $this->log("$intro: Change detected in $url");
        }
        $recompile = true;
      }

      // nothing changed so far, check for mtime variable
      elseif ($mtime > filemtime($cssFile)) {
        $recompile = true;
        $this->log("$intro: Change detected in less variables from PHP file");
      }

      // maybe recompile is forced by the session flag?
      elseif ($this->wire->session->get(RockFrontend::recompile)) {
        $this->log("$intro: Forced by RockFrontend::recompile.");
        $recompile = true;
      }

      // check if any of the less files in RockFrontend folder have changed
      else {
        foreach (glob(__DIR__ . "/less/*.less") as $f) {
          if (filemtime($f) > filemtime($cssFile)) {
            $this->log("$intro: $f changed.");
            $recompile = true;
          }
        }
      }

      // recompile CSS
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
        $this->wire->cache->save($cacheName, $lessCurrent);
        $this->wire->session->set(RockFrontend::recompile, false);

        $url = $this->rockfrontend()->toUrl($cssFile);
        $this->log("RockFrontend recompiled $url");
      }

      $asset = new Asset($cssFile);
      $asset->debug('LESS compiled by RockFrontend');
      $entries->add($asset);
    }

    foreach ($entries->reverse() as $entry) $this->prepend($entry);
  }

  /**
   * Parse SCSS files and add the generated CSS file to output
   * If there are any scss files we render them at the beginning.
   * This makes it possible to overwrite styles via plain CSS later.
   */
  private function parseScssFiles($opt, $cacheName)
  {
    $compiler = $this->wire->modules->get('Scss');
    $scssCache = $this->wire->cache->get($cacheName);
    $scssCurrent = ''; // string to store file info
    $mtime = 0;
    $parse = false;
    $entries = new WireArray();
    $intro = "Recompile $cacheName";

    // loop all scss files and add them to the scss parser
    // this will also memorize the latest file timestamp to check for recompile
    foreach ($this as $asset) {
      if ($asset->ext !== 'scss') continue;
      if ($opt->debug) {
        $entries->add(new AssetComment("loading {$asset->url} ({$asset->debug()})"));
      }
      if (!$compiler) {
        $entries->add(new AssetComment("install scss processwire module (https://processwire.com/modules/scss/) for parsing {$asset->url}"));
        continue;
      }
      $cssPathAsset = $this->wire->config->paths->root . ltrim($opt->cssDir, "/");
      $cssFileAsset = $cssPathAsset . $asset->filename . ".css";
      $url = str_replace(
        $this->wire->config->paths->root,
        $this->wire->config->urls->root,
        $cssFileAsset
      );
      $parse = true;
      if ($asset->m > $mtime) $mtime = $asset->m;
      $scssCurrent .= $url . "|" . $asset->m . "--";
    }

    // if a modification timestamp is set in the options we apply it now
    // this is necessary for making LESS recompile if less variables in
    // PHP have been changed without updating any less files
    $mtime = max($mtime, $opt->lessVarsModified);

    // prepare variables needed for recompile check
    $cssPath = $this->wire->config->paths->root . ltrim($opt->cssDir, "/");
    $cssFile = $cssPath . $opt->cssName . ".css";
    $SourcemapFile = $cssPath . $asset->filename . ".map";
    $scssCacheArray = $this->getChangedFiles($scssCache, $scssCurrent);

    // we have a scss parser installed and some scss files to parse
    if ($compiler and $parse) {
      $recompile = false;

      // if it is a livereload stream we do not recompile
      if ($this->rockfrontend()->isLiveReload) $recompile = false;

      // if css file does not exist we recompile
      elseif (!is_file($cssFile)) {
        $this->log("$intro: $cssFile does not exist.");
        $recompile = true;
      }

      // cache strings are different
      // that means a file or a timestamp has changed
      elseif ($scssCurrent !== $scssCache) {
        // show info which file changed to log
        foreach ($scssCacheArray as $str) {
          $parts = explode("|", $str);
          $url = $this->rockfrontend()->toUrl($parts[0]);
          $this->log("$intro: Change detected in $url");
        }
        $recompile = true;
      }

      // nothing changed so far, check for mtime variable
      elseif ($mtime > filemtime($cssFile)) {
        $recompile = true;
        $this->log("$intro: Change detected in scss variables from PHP file");
      }

      // maybe recompile is forced by the session flag?
      elseif ($this->wire->session->get(RockFrontend::recompile)) {
        $this->log("$intro: Forced by RockFrontend::recompile.");
        $recompile = true;
      }

      // check if any of the SCSS files contained in the asset folder (recursively) has changed as usually you only load the master scss file
      else {
        foreach($this->wire->files->find($asset->dir, ['extensions' => ['scss'], 'recursive' => 3]) as $f) {
          if (filemtime($f) > filemtime($cssFile)) {
            $this->log("$intro: $f changed.");
            $recompile = true;
          }
        }
      }

      // recompile CSS
      if ($recompile) {
        if (!is_dir($cssPath)) $this->wire->files->mkdir($cssPath);

        $style = 'compressed';
        if ($opt->debug) {
          $style = "expanded";
        }

        $sourcemap = '';
        if ($opt->sourcemaps) {
          $sourcemap = $SourcemapFile;
        }

        $compiler->compileRF($asset->basename, $cssFile, $cssPath, $asset->dir, $style, $sourcemap);

        $this->wire->cache->save($cacheName, $scssCurrent);
        $this->wire->session->set(RockFrontend::recompile, false);

        $url = $this->rockfrontend()->toUrl($cssFile);
        $this->log("Recompiled RockFrontend $url");
      }

      $asset = new Asset($cssFile);
      $asset->debug('SCSS compiled by RockFrontend');
      $entries->add($asset);
    }

    foreach ($entries->reverse() as $entry) $this->prepend($entry);
  }

  public function getChangedFiles($lessCache, $lessCurrent): array
  {
    $lessCache = array_filter(explode("--", (string)$lessCache));
    $lessCurrent = array_filter(explode("--", (string)$lessCurrent));
    return array_diff($lessCache, $lessCurrent);
  }

  /**
   * Add some postCSS magic to css files
   *
   * We save generated CSS files to /site/assets as it should be writable.
   *
   */
  public function postCSS($asset)
  {
    if (!$this->rockfrontend()->isEnabled('postCSS')) return $asset;

    $markup = $asset->getPostCssMarkup();
    if (!$markup) return $asset;
    $rf = $this->rockfrontend();

    // write markup to cached file
    $newFile = $rf->assetPath("css/" . $asset->basename);
    $isNewer = $rf->isNewer($asset->path, $newFile);
    if ($isNewer) {
      // asset has been changed, update cached file
      $markup = $rf->postCSS($markup);
      $rf->writeAsset($newFile, $markup);

      // if there is a sourcemap file for the given asset we copy it
      // over to the new location
      if (is_file($src = $asset->path . ".map")) {
        $this->wire->files->copy($src, $newFile . ".map");
      }
    }
    $asset->setPath($newFile);
    return $asset;
  }

  public function render($options = [])
  {
    if ($this->rendered) return;
    $this->rendered = true;

    if (is_string($options)) $options = ['indent' => $options];

    // setup options
    $opt = $this->wire(new WireData());
    /** @var WireData $opt */
    $opt->setArray([
      'debug' => $this->wire->config->debug,
      'indent' => '  ',
      'cssDir' => $this->cssDir ?: "/site/templates/bundle/",
      'cssName' => $this->name,
      'sourcemaps' => $this->wire->config->debug,

      // manual file modification timestamp provided by calling module
      // this is needed in RockPdf to make the less update when variables
      // in PHP changed but no less file was changed
      'lessVarsModified' => false,
    ]);
    $opt->setArray($options);

    // make sure that cssDir is a relative path
    // if a path was provided we strip the pw root
    $opt->cssDir = str_replace(
      $this->wire->config->paths->root,
      $this->wire->config->urls->root,
      $opt->cssDir
    );

    $cacheName = "rockfrontend-styles-" . $this->name;
    $this->parseLessFiles($opt, $cacheName);
    $this->parseScssFiles($opt, $cacheName);
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
      if ($asset->ext === 'scss') continue;
      $asset = $this->minifyAsset($asset);
      $asset = $this->postCSS($asset);
      $out .= $this->renderTag($asset, $opt, 'style');
    }
    return $out;
  }

  /**
   * Set a less variable
   * @return self
   */
  public function setVar($key, $value)
  {
    $vars = $this->vars;
    $vars[$key] = $value;
    $this->vars = $vars;
    return $this;
  }
}
