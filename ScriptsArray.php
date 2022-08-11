<?php namespace RockFrontend;
class ScriptsArray extends AssetsArray {

  const comment = '<!--rockfrontend-scripts-head-->';

  public function render($indent = '  ') {
    // TODO make API version of options to support hook injected assets
    $out = $this->name == 'head' ? "$indent".self::comment."\n" : '';
    foreach($this as $script) {
      $m = $script->m ? "?m=".$script->m : "";
      $suffix = $script->suffix ? " ".$script->suffix : '';
      $out .= "$indent<script src='{$script->url}$m'$suffix></script>\n";
    }
    return $out;
  }

}
