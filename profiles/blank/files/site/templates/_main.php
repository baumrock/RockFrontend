<?php

namespace ProcessWire;

/** @var RockFrontend $rockfrontend */
$rockfrontend->styles()
  // add uikit theme from wire folder
  // this is just for demonstration and should not be used on production!
  // Better download UIkit yourself to ensure that PW upgrades do not break your frontend!
  // RockFrontend does NOT depend on UIkit!
  // by default this will place the resulting css file in /site/templates
  // but you can custimize that (see blow in render method call)
  ->add('/wire/modules/AdminTheme/AdminThemeUikit/uikit/src/less/uikit.theme.less')

  // some styles are added by default, see RockFrontend::ready() for details

  // you can include any custom css files as you wish
  // ->add('/site/templates/bundle/main.css')
;
$rockfrontend->scripts()
  ->add('/wire/modules/AdminTheme/AdminThemeUikit/uikit/dist/js/uikit.min.js');
?>
<!DOCTYPE html>
<html lang="de">

<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <?= $rockfrontend->seo() ?>
</head>

<body>
  <?= $rockfrontend->renderLayout($page) ?>
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