<?php namespace RockFrontend;
class ScriptsArray extends AssetsArray {

  const comment = '<!--rockfrontend-scripts-head-->';

  public function render($indent = '') {
    // TODO make API version of options to support hook injected assets
    $out = self::comment;
    foreach($this as $script) {
      $m = $script->m ? "?m=".$script->m : "";
      $out .= "$indent<script src='{$script->url}$m'{$script->suffix}></script>\n";
      $indent = '  ';
    }
    return $out;
  }

}
