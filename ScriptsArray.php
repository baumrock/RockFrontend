<?php namespace RockFrontend;

use ProcessWire\WireData;

class ScriptsArray extends AssetsArray {

  const comment = '<!-- rockfrontend-scripts-head -->';

  public function render($options = []) {
    if(is_string($options)) $options = ['indent' => $options];

    // setup options
    $opt = $this->wire(new WireData()); /** @var WireData $opt */
    $opt->setArray([
      'debug' => $this->wire->config->debug,
      'indent' => '  ',
    ]);
    $opt->setArray($this->options);
    $opt->setArray($options);
    $indent = $opt->indent;

    // add tag that shows RockFrontend that scripts are loaded

    $out = "\n";
    if($opt->debug) {
      $out .= "$indent<!-- DEBUG enabled! You can disable it either via \$config or use \$rf->scripts()->setOptions(['debug'=>false]) -->\n";
      if($this->opt('autoload')) {
        $out .= "$indent<!-- autoloading of default scripts enabled - disable using ->setOptions(['autoload'=>false]) -->\n";
      }
    }
    $out .= $this->name == 'head' ? $indent.self::comment."\n" : '';

    foreach($this as $asset) {
      $m = $asset->m ? "?m=".$asset->m : "";
      $suffix = $asset->suffix ? " ".$asset->suffix : '';
      $debug = $opt->debug ? $asset->debug : '';
      $out .= "$indent<script src='{$asset->url}$m'$suffix></script>$debug\n";
    }
    return $out;
  }

}
