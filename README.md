# RockFrontend

## Example _main.php

```php
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
    // add uikit theme from wire folder
    // this is just for demonstration! RockFrontend does NOT depend on UIkit!
    ->add('/wire/modules/AdminTheme/AdminThemeUikit/uikit/src/less/uikit.theme.less')
    ->add('/site/templates/bundle/main.css')
    ->render();
  echo $rockfrontend->scripts('head')
    ->render();
  ?>
</head>
<body>
  <?= $body ?>
  <?php
  echo $rockfrontend->scripts('body')
    ->add('site/templates/bundle/main.js')
    ->render();
  ?>
</body>
</html>
```

## Adding assets to your site (JS or CSS)

While you can always add custom `<script>` or `<link>` tags to your site's markup it is recommended that you use RockFrontend's `AssetsArray` feature:

```php
echo $rockfrontend->scripts('head')
  ->add('/path/to/your/script.js')
  ->addIf($rockfrontend->foo, '/path/to/foo.js')
  ->render();
echo $rockfrontend->styles('head')
  ->add(...)
  ->addIf(...)
  ->render();
```

There are several reasons why this is preferable over adding custom script/style tags:

* addIf() keeps your markup file cleaner than using if / echo / endif
* render() automatically adds timestamps of files for cache busting
* You can inject scripts/styles from within other files (eg PW modules)

RockFrontend itself uses this technique to inject the styles and scripts necessary for frontend editing (Alfred). Have a look at the module's init() method!

## Adding folders to scan for frontend files

By default RockFrontend scans the folders `/site/assets` and `/site/templates` for files that you want to render via `$rf->render("layouts/foo")`.

If you want to add another directory to scan you can add it to the `folders` property of RockFrontend:

```php
// in site/ready.php
$rockfrontend->folders->add('/site/templates/my-template/');
```
