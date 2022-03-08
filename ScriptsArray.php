<?php namespace RockFrontend;
class ScriptsArray extends AssetsArray {

  public function render() {
    $out = '';
    foreach($this as $script) {
      $m = $script->m ? "?m=".$script->m : "";
      $out .= "  <script src='{$script->url}$m'{$script->suffix}></script>\n";
    }
    return $out;
  }

}
