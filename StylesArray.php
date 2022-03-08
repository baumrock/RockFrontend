<?php namespace RockFrontend;
class StylesArray extends AssetsArray {

  public function render() {
    $out = '';
    foreach($this as $asset) {
      $m = $asset->m ? "?m=".$asset->m : "";
      $out .= "  <link rel='stylesheet' href='{$asset->url}$m'{$asset->suffix}>\n";
    }
    return $out;
  }

}
