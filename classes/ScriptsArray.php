<?php

namespace RockFrontend;

use ProcessWire\WireData;

class ScriptsArray extends AssetsArray
{

  public function render($options = [])
  {
    if ($this->rendered) return;
    $this->rendered = true;

    if (is_string($options)) $options = ['indent' => $options];

    // setup options
    $opt = $this->wire(new WireData());
    /** @var WireData $opt */
    $opt->setArray([
      'debug' => $this->wire->config->debug,
      'indent' => '  ',
    ]);
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
    return implode("", $tags);
  }
}
