<?php namespace RockFrontend;
class StylesArray extends AssetsArray {

  public function render($indent = '') {
    $out = '';
    foreach($this as $asset) {
      $m = $asset->m ? "?m=".$asset->m : "";
      $out .= "$indent<link rel='stylesheet' href='{$asset->url}$m'{$asset->suffix}>\n";
      $indent = '  ';
    }
    return $out;
  }

}
