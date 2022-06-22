# RockFrontend

## Does RockFrontend force me to use a CSS Frontend Framework?

No! Some examples might use UIkit classes, but you can choose whatever framework you like (or none of course).

## Browser Live Reloading

You'll get instant live reloading of your browser whenever a file changed. Just create the file `see.php` in your PW root directory:

```php
<?php
include __DIR__.'/site/modules/RockFrontend/SSE.php';
```

## LATTE Templating Engine

RockFrontend supports (but does not require) the `latte` templating engine by Nette (see https://latte.nette.org/en/syntax). To use latte you need to install it (you only have to do that once):

```
cd /path/to/your/pw/root
composer require latte/latte
```

To render a latte file simply call `$rockfrontend->render('/your/latte/file.latte')`

### Why Latte?

* Latte can simplify the markup a lot (see `n:if` or `n-foreach`)
* Latte adds additional security (see https://latte.nette.org/en/safety-first)
* Latte makes it possible to still use PHP expressions (see https://latte.nette.org/en/tags#toc-var-expr-expr)

For example you can use this statement to use Tracy's `bardump()` in your template file:

```php
{bd($page, 'test dump')}
```

### Is it possible to use other templating engines?

It is easy to add support for other templating engines as well. Please submit a PR or ask for support in the support forum.

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
```

## Adding assets to your site (JS or CSS)

While you can always add custom `<script>` or `<link>` tags to your site's markup it is recommended that you use RockFrontend's `AssetsArray` feature:

```php
echo $rockfrontend->scripts('head')
  ->add('/path/to/your/script.js')
  ->addIf('/path/to/foo.js', $rockfrontend->yourflag)
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

By default RockFrontend scans the folders `/site/assets` and `/site/templates` for files that you want to render via `$rockfrontend->render("layouts/foo")`.

If you want to add another directory to scan you can add it to the `folders` property of RockFrontend:

```php
// in site/ready.php
$rockfrontend->folders->add('/site/templates/my-template/');
```

## SVGs

You can use the `render()` method to write SVG markup directly to your template file:

```php
// latte
// icon is in /site/templates/img/icon.svg
{$rockfrontend->render('img/icon.svg')|noescape}

// php
echo $rockfrontend->render('img/icon.svg');
```

## Menus

RockFrontend comes with a handy method `isActive()` to keep your menu markup clean. Using `latte` you'll get super simple markup without if-else-hell:

```html
<nav id='tm-menu' class='tm-boxed-padding' uk-navbar>
  <div class="uk-navbar-center uk-visible@m">
    <ul class="uk-navbar-nav">
      <li n:foreach="$home->children() as $item">
        <a href="{$item->url}"
          n:class="$rockfrontend->isActive($item) ? 'uk-active'"
        >
          {$item->title}
        </a>
        <div class="uk-navbar-dropdown" n:if="$item->numChildren()">
          <ul class="uk-nav uk-navbar-dropdown-nav">
            <li
              n:foreach="$item->children() as $child"
              n:class="$rockfrontend->isActive($child) ? 'uk-active'"
            >
              <a href="{$child->url}">{$child->title}</a>
            </li>
          </ul>
        </div>
      </li>
    </ul>
  </div>
</nav>
