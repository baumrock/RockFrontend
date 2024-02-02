<?php

namespace ProcessWire;

/** @var RockFrontend $rockfrontend */

$htmlLang = "de";

$rockfrontend->styles()
  ->add('/site/templates/uikit/src/less/uikit.theme.less')
  ->add('/site/modules/RockFrontend/less/defaults.less')
  ->addDefaultFolders()
  ->minify(!$config->debug);

$rockfrontend->scripts()
  ->add('/site/templates/uikit/dist/js/uikit.min.js')
  ->add('/site/templates/scripts/main.js', 'defer')
  ->minify(!$config->debug);

$seo = $rockfrontend->seo(createManifest: false)
  ->title($page->title . " | example.com");
