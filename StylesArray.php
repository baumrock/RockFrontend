<?php namespace RockFrontend;

use ProcessWire\Less;
use ProcessWire\WireData;

class StylesArray extends AssetsArray {

  /**
   * Add all files of folder to assets array
   * @return self
   */
  public function addAll($path, $suffix = '', $levels = 1, $ext = ['css','less']) {
    return parent::addAll($path, $suffix, $levels, $ext);
  }

  public function render($options = []) {
    if(is_string($options)) $options = ['indent' => $options];

    // setup options
    $opt = $this->wire(new WireData()); /** @var WireData $opt */
    $opt->setArray([
      'indent' => '',
      'cssDir' => "/site/templates/bundle/",
      'cssName' => $this->name,
    ]);
    $opt->setArray($options);

    $out = '';
    $indent = $opt->indent;

    // if there are any less files we render them at the beginning
    // this makes it possible to overwrite styles via plain CSS later
    /** @var Less $less */
    $less = $this->wire->modules->get('Less');
    $m = 0;
    foreach($this as $asset) {
      if($asset->ext !== 'less') continue;
      if(!$less) {
        $out .= "$indent<!-- install Less module for parsing {$asset->url} -->\n";
        $indent = '  ';
        continue;
      }
      $less->addFile($asset->path);
      if($asset->m > $m) $m = $asset->m;
    }
    if($less) {
      $cssPath = $this->wire->config->paths->root.ltrim($opt->cssDir, "/");
      $cssFile = $cssPath.$opt->cssName.".css";
      $recompile = false;
      if(!is_file($cssFile)) $recompile = true;
      elseif(filemtime($cssFile) < $m) $recompile = true;
      // create css file
      $m = "?m=$m";
      $url = str_replace(
        $this->wire->config->paths->root,
        $this->wire->config->urls->root,
        $cssFile
      );
      if($recompile) {
        if(!is_dir($cssPath)) $this->wire->files->mkdir($cssPath);
        $less->saveCss($cssFile);
        $this->log("Recompiled RockFrontend $url");
      }
      $out .= "$indent<link rel='stylesheet' href='{$url}$m'>\n";
      $indent = '  ';
    }

    foreach($this as $asset) {
      if($asset->ext === 'less') continue;
      $m = $asset->m ? "?m=".$asset->m : "";
      $out .= "$indent<link rel='stylesheet' href='{$asset->url}$m'{$asset->suffix}>\n";
      $indent = '  ';
    }
    return $out;
  }

}
