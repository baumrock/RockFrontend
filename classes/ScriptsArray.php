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
  public function ___renderAssets($opt): string
  {
    $tags = [];
    $last = new WireData();
    if ($this->minify) {
      foreach ($this as $asset) {
        if ($asset->ext === 'less') continue;
        $asset = $this->minifyAsset($asset);
        // this ensures that we only add the asset once
        // and not both versions foo.js and foo.min.js
        if ($asset->path !== $last->path) $tags[] = $this->renderTag($asset, $opt, 'script');
        $last = $asset;
      }
    } else {
      // try to load unminified versions
      foreach ($this as $asset) {
        if ($asset->ext === 'less') continue;
        // this ensures that we only add the asset once
        // and not both versions foo.js and foo.min.js
        $unminified = substr($asset->path, 0, -7) . ".js";
        if ($last->path != $unminified) $tags[] = $this->renderTag($asset, $opt, 'script');
        $last = $asset;
      }
    }
    // bd($tags);
    return implode("\n", $tags);
  }
}
