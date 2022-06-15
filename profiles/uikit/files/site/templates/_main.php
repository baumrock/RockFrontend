<?php namespace ProcessWire;
/** @var RockFrontend $rockfrontend */
// render layout from page field or from /site/templates/layouts
// do this above markup so that we can add scripts and styles from layout files
$body = $rockfrontend->renderLayout($page);
?><!DOCTYPE html>
<html lang="de">
<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <?= $page->seo ?>
  <?php
  echo $rockfrontend->styles('head')
    // note that rockfrontend will add ALFRED
    // to your "head" scripts when logged in
    // if you want to use alfred don't rename the name of this styles() call

    // add uikit theme from wire folder
    // this is just for demonstration! RockFrontend does NOT depend on UIkit!
    // by default this will place the resulting css file in /site/templates
    // but you can custimize that (see blow in render method call)
    ->add('/wire/modules/AdminTheme/AdminThemeUikit/uikit/src/less/uikit.theme.less')

    // add all css and less files that you find in /site/templates/sections
    // this makes it possible to split your stylesheets into smaller parts
    // eg you can have slider.php for code and slider.less for the styling
    ->addAll('sections')

    // same as above with layouts folder
    ->addAll('layouts')

    // add all style files of RockMatrix blocks (2 levels deep)
    ->addAll('/site/assets/RockMatrix', null, 2)

    // of course you can include
    ->add('/site/templates/bundle/main.css')
    ->render([
      // here you can define custom settings for the render call
      // 'cssDir' => "/site/templates/bundle/",

      // the name of the css file
      // $this->name refers to the name provided in the styles() call
      // in our case this would be "head" which would create the file "head.css"
      // 'cssName' => $this->name,
    ]);
  echo $rockfrontend->scripts('head')
    // when logged in rockfrontend will inject Alfred.js here!
    // don't remove this rendering block even if you don't add custom scripts
    ->add('/wire/modules/AdminTheme/AdminThemeUikit/uikit/dist/js/uikit.min.js')
    ->render();
  ?>
</head>
<body>
  <?= $body ?>
  <?php
  // this is just an example of how you could add another scripts section
  // you can safely remove this call if you don't want to add any scripts
  // at the bottom of your page body
  echo $rockfrontend->scripts('body')
    ->add('site/templates/bundle/main.js')
    ->render();
  ?>
</body>
</html>
