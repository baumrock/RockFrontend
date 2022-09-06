# Take your ProcessWire Frontend Development to the Next Level 🚀🚀

<br>

See the video here:

<a href="https://www.youtube.com/watch?v=7CoIj--u4ps"><img src=thumb.jpg></a>

<br>

# Support

https://processwire.com/talk/topic/27417-rockfrontend-%F0%9F%9A%80%F0%9F%9A%80-take-your-processwire-frontend-development-to-the-next-level/

# Donations

<a href=https://github.com/sponsors/baumrock><img src=donate.svg></a>

😎🤗👍

<img src=hr.svg>

## Intro

RockFrontend is a progressive frontend module for ProcessWire that can help you take your frontend development to the next level.

* Zero-config auto-refresh and LESS-Support
* Better project structure to make your project scalable and future proof
* Support for template engines - LATTE on board
* Google Font Downloader (in the module's config GUI)

<img src=hr.svg>

## Quickstart

If you are using RockFrontend for the very first time it is recommended that you install one of the available profiles via the module's config screen.

Recommended folder structure

```
/site
  /templates
    /layouts
    /partials
    /sections
```

## Highlights

### Browser Live Reloading

ATTENTION: Make sure that this setting is only applied for local development! See https://bit.ly/3xVgtvA how you can setup different configs for dev/staging/production.

```
// make RockFrontend watch for changes every second
$config->livereload = 1;
```

Note that Firefox will always jump to the top of the page while Chrome will keep the scroll position!

Also note that you can prevent RockMigrations from automatically fire when a reload is triggered, which might speed up your reloads significantly depending on the migrations you have setup:

```php
// site/ready.php
/** @var RockMigrations $rm */
$rm = $this->wire->modules->get('RockMigrations');
$rm->noMigrate();
```

If using DDEV make sure you have a correct webserver type otherwise the reloads will be buggy and slow: `webserver_type: apache-fpm`

<img src=hr.svg>

## The render() method

One of the fundamental concepts of RockFrontend is its render() method. Whenever you want to output markup just use `render()` and provide the file you want to render as first parameter:

```php
<?= $rockfrontend->render('/path/to/your/file.php') ?>
```

If your file lives in /site/templates you can use short paths:

```php
// will render /site/templates/sections/head.php
<?= $rockfrontend->render('sections/head.php') ?>
```

Your rendered files can be PHP or LATTE syntax. You can add other template engines easily (see below).

### Variables in rendered files

All PW API variables will be available in your rendered files:

```php
$rockfrontend->render('sections/foo.php');

// sections/foo.php
echo $page->title;
echo $config->httpHost;
echo $pages->get("/bar")->createdUser;
echo $user->isLoggedin();
```

### Custom variables in rendered files

Syntax example:

```php
echo $rockfrontend->render("sections/header.latte");
foreach($page->children() as $child) {
  // in this case $child will be available as $page variable in card.latte
  echo $rockfrontend->render("partials/card.latte", $child);
}

echo $rockfrontend->render("sections/footer.latte", [
  'mail' => 'foo@bar.com', // available as $mail in footer.latte
  'today' => date("d.m.Y"), // available as $today in footer.latte
]);
```

If you use render() from within a LATTE file RockFrontend will automatically return an instance of a Latte Html object so that you don't need to add the |noescape filter!

```php
<div class="uk-child-width-1-3@m" uk-grid>
  <div n:foreach="$page->children() as $item">
    {$rockfrontend->render("partials/card", $item)}
  </div>
</div>
```

Note that render() works different than PHP's `include` or `require`! This is best explained by an example:

```php
$foo = 'foo!';
include "path/to/your/file.php";

// file.php
echo $foo; // echos "foo!"
```

Whereas when using `$rockfrontend->render()` it works differently:

```php
$foo = 'foo!';
echo $rockfrontend->render("path/to/your/file.php");

// file.php
Current page id: <?= $page->id ?> // this will work
Value of foo: <?= $foo ?> // foo is not defined!
```

But you can provide custom variables easily:

```php
$foo = 'foo!';
echo $rockfrontend->render("path/to/your/file.php", [
  'foo' => $foo, // available as $foo
  'today' => date("d.m.Y"), // available as $today
]);
```

You can also make all defined variables available in your rendered file, but note that this might overwrite already defined API variables (like $pages, $files, $config...) so use this technique with caution:

```php
echo $rockfrontend->render('/path/to/your/file.php', get_defined_vars());
```

## Using template engines

### LATTE

RockFrontend ships with the LATTE template engine. I love LATTE because it is very easy to use and it has some neat little helpers that make your markup a whole lot cleaner. In contrary to other template engines that I've tried LATTE has the huge benefit that it still let's you write PHP and so you don't have to learn a new language/syntax!

If you haven't tried LATTE yet, check out the docs: https://latte.nette.org/

* Latte can simplify the markup a lot (see `n:if` or `n-foreach` here: https://latte.nette.org/en/syntax)
* Latte adds additional security (see https://latte.nette.org/en/safety-first)
* Latte makes it possible to still use PHP expressions (see https://latte.nette.org/en/tags#toc-var-expr-expr)

Also see https://processwire.com/talk/topic/27367-why-i-love-the-latte-template-engine/

For example you can use this statement to use Tracy's `bardump()` in your template file:

```php
{bd($page, 'test dump')}
```

### Twig

If you want to use Twig instead of latte all you have to do is to download Twig by using composer:

```sh
cd /path/to/your/pw/root
composer require "twig/twig:^3.0"
```

Then you can render .twig files like this:

```php
echo $rockfrontend->render("sections/header.twig");
```

### Other template engines

It is very easy to add support for any other template engine as well:

```php
// put this in site/ready.php
// it will add support for rendering files with .foo extension
// usage: echo $rockfrontend->render("sections/demo.foo")
$wire->addHook("RockFrontend::renderFileFoo", function($event) {
  $file = $event->arguments(0);
  // implement the renderer here
  $event->return = "Rendering .foo-file $file";
});
```

## RockFrontend and RepeaterMatrix

If your matrix-blocks are regular PHP files you can simply call `echo $page->your_matrix` and ProcessWire will render the field for you. But if you want to use LATTE files instead, you can use RockFrontend to do so!

While you can always render repeater matrix fields manually RockFrontend has some nice helpers. This is the long and manual way of rendering a matrix field:

```php
// main.php
foreach($page->your_matrix_field as $item) {
  // render every block and make the $page variable be the current block
  // instead of the viewed page.
  echo $rockfrontend->render("/matrix/".$item->type, ['page' => $item]);
}

// matrix type foo (/site/templates/matrix/foo.php)
<h1><?= $page->title ?></h1>
```

Or simply use the shortcut:

```php
echo $rockfrontend->render($page->your_matrix);

// or in a latte file
{$rockfrontend->render($page->your_matrix)}

// example matrix block: /site/templates/fields/your_matrix/foo.latte
<h1>Foo block having id {$page->id}</h1>
```

Note that when using $rockfrontend->render() to render matrix fields you can also use latte files for rendering and the `$page` variable in the view file will be the current matrix block instead of the currently viewed page. If you need to access the current page you can use `$wire->page` instead of `$page`.

<img src=hr.svg>

## Using the /site/templates/_init.php file

You can define variables and functions in your `_init.php` file:

```php
<?php // no namespace here!! se note below

$foo = 'I am the foo variable';

function foo() {
  return 'I am the foo function';
}
```

Then you can access your variables and functions from within all your rendered files:

```php
// using LATTE example syntax
<p>Content of the foo variable: {$foo}</p>

<p>Return value of foo(): {foo()}</p>
```

Note that we are not using the `ProcessWire` namespace in our `_init.php` file, so that we can simply call `foo()` directly. If you want or need to use the ProcessWire namespace in your `_init.php` than you need to call `\ProcessWire\foo()` from your template files instead of just `foo()`:

```php
<p>Content of the foo variable: {$foo}</p>
<p>Return value of foo(): {\ProcessWire\foo()}</p>
```

## Assets (Scripts and Styles)

RockFrontend comes with a helper class for scripts and styles. You can add assets easily via the `add()` method or you can add all files in a folder with `addAll('/path/to/folder')`. That means you can split up your CSS in easy to understand and easy to maintain chunks, but you only need to add them once to your main markup file!

You can request a new ScriptsArray like this:

```php
$scripts = $rockfrontend->scripts();
bd($scripts); // you can use tracy to inspect the ScriptsArray object!
```

By default this will use `head` as the name for the ScriptsArray so you can add files to that array whenever and wherever you like.

```php
$headscripts = $rockfrontend->scripts();
$bodyscripts = $rockfrontend->scripts('body');
$customscripts = $rockfrontend->scripts('foobar');
```

RockFrontend will render the `head` scripts and styles automatically right before your `</head>` tag. If you want to render those scripts and styles in another place you can manually render them like this:

```php
echo $rockfrontend->styles()->render(); // render "head" styles
echo $rockfrontend->scripts()->render(); // render "head" scripts
```

When parsing LESS files RockFrontend will create a file `bundle/head.css` for you!

### What is the bundle/head.css file?

If you request a styles() array without


## FAQ

### Does RockFrontend force me to use a CSS Frontend Framework?

No! Some examples might use UIkit classes, but you can choose whatever framework you like (or none of course). You can also use TailwindCSS but of course you'll need to add your own frontend build pipeline!

### Does RockFrontend use an MVC pattern or force me to use one?

RockFrontend does not force you to use an MVC architecture, though I'm always using one. It's as simple as adding one file with very little code using the [brilliant core feature "custom page classes"](https://processwire.com/blog/posts/pw-3.0.152/#new-ability-to-specify-custom-page-classes).


<img src=hr.svg>

# Example _main.php

```php
<?php namespace ProcessWire;
/** @var RockFrontend $rockfrontend */
$rockfrontend->styles()
  ->add(/path/to/your/file.css)
  ;
$rockfrontend->scripts()
  ->add(/path/to/your/file.js)
  ;
?><!DOCTYPE html>
<html lang="de">
<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <?= $rockfrontend->seo() ?>
  <?php
  // your scripts will be injected here automatically
  // see the video for details!
  ?>
</head>
<body>
  <?php
  // render layout from page field or from /site/templates/layouts
  echo $rockfrontend->renderLayout($page);

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

<img src=hr.svg>

# Other notes

## LATTE and translatable strings

Unfortunately you can't use ProcessWire's translation system in LATTE files. You can either use an MVC approach and move your translatable strings into the controller file (custom page class) or you can use RockFrontend's translation helper:

```php
// define translations, eg in /site/init.php
/** @var RockFrontend $rf */
$rf = $this->wire->modules->get('RockFrontend');
$rf->x([
  'status_loggedin' => __('You are now logged in'),
  'status_loggedout' => __('Pleas log in'),
  'logout' => __('Logout'),
  'login' => __('Login'),
]);
```

In your LATTE files you can output translations like this:

```html
<button>{$user->isLoggedin() ? x('logout') : x('login')}</button>
```

## Adding assets to your site (JS or CSS)

While you can always add custom `<script>` or `<link>` tags to your site's markup it is recommended that you use RockFrontend's `AssetsArray` feature:

```php
$rockfrontend->scripts()
  ->add('/path/to/your/script.js')
  // you can add any custom flags to your $rockfrontend variable at runtime!
  ->addIf('/path/to/foo.js', $rockfrontend->needsFooScript)
  ;
$rockfrontend->styles()
  ->add(...)
  ->addIf(...)
  ;
```

There are several reasons why this is preferable over adding custom script/style tags:

* addIf() keeps your markup file cleaner than using if / echo / endif
* It automatically adds timestamps of files for cache busting
* You can inject scripts/styles from within other files (eg PW modules)

RockFrontend itself uses this technique to inject the styles and scripts necessary for frontend editing (ALFRED). Have a look at the module's init() method!

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
```
