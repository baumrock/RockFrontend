<?php

namespace RockFrontend;

use ProcessWire\WireData;

class ScriptsArray extends AssetsArray
{

  const comment = '<!-- rockfrontend-scripts-head -->';

  private function addInfo($opt)
  {
    $indent = $opt->indent;
    $out = "\n";
    if ($opt->debug) {
      $out .= "$indent<!-- DEBUG enabled! You can disable it either via \$config or use \$rf->scripts()->setOptions(['debug'=>false]) -->\n";
      if ($this->opt('autoload')) {
        $out .= "$indent<!-- autoloading of default scripts enabled - disable using ->setOptions(['autoload'=>false]) -->\n";
      }
    }
    $out .= $this->name == 'head' ? $indent . self::comment . "\n" : '';
    return $out;
  }

  public function render($options = [])
  {
    if (is_string($options)) $options = ['indent' => $options];

    // setup options
    $opt = $this->wire(new WireData());
    /** @var WireData $opt */
    $opt->setArray([
      'debug' => $this->wire->config->debug,
      'indent' => '  ',
    ]);
    $opt->setArray($this->options);
    $opt->setArray($options);

    $out = $this->renderAssets($opt);
    if ($out) $out = $this->addInfo($opt) . $out;
    return $out;
  }

  /**
   * Create markup for including all assets
   */
  private function renderAssets($opt): string
  {
    $out = '';
    foreach ($this as $asset) {
      if ($asset->ext === 'less') continue;
      $out .= $this->renderTag($asset, $opt, 'script');
    }
    return $out;
  }
}
