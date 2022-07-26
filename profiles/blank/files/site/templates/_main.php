<?php namespace ProcessWire;
/** @var RockFrontend $rockfrontend */
?><!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <?php
  echo $rockfrontend->styles()
    ->addAll('sections')
    ->render();
  echo $rockfrontend->scripts('head')
    ->render();
  ?>
</head>
<body>
  <?= $rockfrontend->render("sections/header.php"); ?>
  <?= $rockfrontend->render("sections/main.php"); ?>
  <?= $rockfrontend->render("sections/footer.php"); ?>
</body>
</html>
