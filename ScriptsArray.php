<?php namespace RockFrontend;
class ScriptsArray extends AssetsArray {

  public function render($indent = '') {
    $out = '';
    foreach($this as $script) {
      $m = $script->m ? "?m=".$script->m : "";
      $out .= "$indent<script src='{$script->url}$m'{$script->suffix}></script>\n";
      $indent = '  ';
    }
    return $out;
  }

}
