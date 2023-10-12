<?php

namespace ProcessWire;

/** @var RockFrontend $rockfrontend */
$rockfrontend->styles()
  ->add('/site/templates/uikit/dist/css/uikit.min.css')
  ->addDefaultFolders(); // autoload styles in sections, partials, etc
$rockfrontend->scripts()
  ->add('/site/templates/uikit/dist/js/uikit.min.js');
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>

<body>
  <?= $rockfrontend->renderLayout($page) ?>
</body>

</html>