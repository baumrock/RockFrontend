<?php

namespace ProcessWire;

/** @var RockFrontend $rockfrontend */
$rockfrontend->styles()
  ->add('/site/templates/uikit/src/less/uikit.theme.less')
  ->addDefaultFolders(); // autoload styles in sections, partials, etc
$rockfrontend->scripts()
  ->add('/site/templates/uikit/dist/js/uikit.min.js');
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
  <?php
  // check if Less module is installed
  // once installed you can remove this if/else and just render the layout
  if ($modules->get('Less')) echo $rockfrontend->renderLayout($page);
  else echo "<h1 style='text-align:center;padding:50px;color:red;'>Please install the Less module to use this profile!</h1>";
  ?>
</body>

</html>