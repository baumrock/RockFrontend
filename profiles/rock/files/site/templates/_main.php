<?php

namespace ProcessWire;

/** @var RockFrontend $rockfrontend */
$rockfrontend->styles()
  ->add('/site/templates/uikit-3.15.12/src/less/uikit.theme.less')
  ->add('/site/modules/RockFrontend/uikit/defaults.less')
  ->add('/site/modules/RockFrontend/uikit/offcanvas.less')
  ->add('/site/modules/RockFrontend/less/defaults.less')
  ->add('/site/modules/RockFrontend/less/boxed-layout.less')
  ->add('/site/modules/RockFrontend/less/sticky-footer.less')
  ->add('/site/modules/RockFrontend/less/headlines.less')
  ->addDefaultFolders(); // finally autoload styles in sections, partials, etc
$rockfrontend->scripts()
  ->add('/site/templates/uikit-3.15.12/dist/js/uikit.min.js');
?>
<!DOCTYPE html>
<html lang="de">

<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <?= $rockfrontend->seo() ?>
</head>

<body class="rf-boxed rf-sticky-footer uk-card-default">
  <?= $rockfrontend->renderLayout($page) ?>
</body>

</html>