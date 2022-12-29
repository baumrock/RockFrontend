# Take your ProcessWire Frontend Development to the Next Level 🚀🚀

<br>

See the video here:

<a href="https://www.youtube.com/watch?v=7CoIj--u4ps"><img src=thumb.jpg height=300></a>
<a href="https://www.youtube.com/watch?v=6ld4daFDQlY"><img src=https://user-images.githubusercontent.com/8488586/200658445-641f8127-7c22-4d41-8eb1-6c00bc0fccba.png height=300></a>

<br>

# Support

https://processwire.com/talk/topic/27417-rockfrontend-%F0%9F%9A%80%F0%9F%9A%80-take-your-processwire-frontend-development-to-the-next-level/

# Donations

https://github.com/sponsors/baumrock 😎🤗👍

<img src=hr.svg>

## Intro

RockFrontend is a progressive frontend module for ProcessWire that can help you take your frontend development to the next level.

- Zero-config auto-refresh and LESS-Support
- Better project structure to make your project scalable and future proof
- Support for template engines - LATTE on board
- Google Font Downloader (in the module's config GUI)

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

```php
// make RockFrontend watch for changes every second
$config->livereload = 1;
```

This will make RockFrontend watch for changes with the default settings:

<img src=https://i.imgur.com/pdUHelD.png height=500>

Optionally you can customize your setup if you need:

```php
$config->livereload = [
  // interval to watch for changes
  // default is 1s
  'interval' => 1,

  // user defined includes
  'include' => [],

  // you can reset default include paths
  'includeDefaults' => [],

  // user defined exclude regexes
  'exclude' => [],

  // you can reset default excludes
  'excludeDefaults' => [],
]
```

If you notice unexpected reloads you can inspect the logs which file triggered the reload:

<img src=https://i.imgur.com/Rzm5Eyy.png height=300>

### Browser support

Note that Firefox will always jump to the top of the page while Chrome will keep the scroll position!

If using DDEV make sure you have a correct webserver type otherwise the reloads might be buggy and slow: `webserver_type: apache-fpm`

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

## SEO

### Favicon

Creating favicons and adding the correct markup is a pain. Not with RockFrontend! Just upload a 512x512 PNG to your root page's favicon field and add the seo tags to your main markup file:

```php
echo $rockfrontend->seo();
```

This will add all the necessary markup, eg:

```html
<link rel='icon' type='image/png' sizes='32x32'
href=/site/assets/files/1/favicon.32x32.png> <link rel='icon' type='image/png'
sizes='16x16' href=/site/assets/files/1/favicon.16x16.png> <link rel='icon'
type='image/png' sizes='48x48' href=/site/assets/files/1/favicon.48x48.png>
<link rel='icon' type='image/png' sizes='192x192'
href=/site/assets/files/1/favicon.192x192.png> <link rel='apple-touch-icon'
type='image/png' sizes='167x167' href=/site/assets/files/1/favicon.167x167.png>
<link rel='apple-touch-icon' type='image/png' sizes='180x180'
href=/site/assets/files/1/favicon.180x180.png>
<link
  rel="manifest"
  href="/website.webmanifest"
/>
<meta
  name="theme-color"
  content="#074589"
/>
```

### Adding a manifest file to your project

By adding a webmanifest file to your project you can improve the mobile experinece of your site. RockFrontend makes it super simple to set the browsers statusbar color for example:

```php
// site/init.php
/** @var RockFrontend $rockfrontend */
$rockfrontend->manifest()
  ->name('My App')
  ->themeColor('#6764A4')
  ;
```

Next you just need to render the SEO tags in your main markup file:

```php
echo $rockfrontend->seo();
```

This will create the file `website.webmanifest` in the PW root folder if the file does not exist. If you want to update your manifest file you can simply delete it and reload your page. If you need your manifest file to recreate on page save you can do so like this:

```php
// site/init.php
/** @var RockFrontend $rockfrontend */
$rockfrontend->manifest()
  ->name($pages->get(1)->title)
  ->themeColor('#6764A4')
  ->createOnSave('template=home') // use any page selector you need
  ;
```

Note that many favicon generators use `site.webmanifest` as filename. It's intentionally not used in RockFrontend because if you are on the commandline in the pw root folder and want to quickly navigate into the site folder by typing "si + tab" it would ask you where to navigate because you have /site and /site.webmanifest - I found that very annoying so it's called `website.webmanifest`.

## Using template engines

### LATTE

RockFrontend ships with the LATTE template engine. I love LATTE because it is very easy to use and it has some neat little helpers that make your markup a whole lot cleaner. In contrary to other template engines that I've tried LATTE has the huge benefit that it still let's you write PHP and so you don't have to learn a new language/syntax!

If you haven't tried LATTE yet, check out the docs: https://latte.nette.org/

- Latte can simplify the markup a lot (see `n:if` or `n-foreach` here: https://latte.nette.org/en/syntax)
- Latte adds additional security (see https://latte.nette.org/en/safety-first)
- Latte makes it possible to still use PHP expressions (see https://latte.nette.org/en/tags#toc-var-expr-expr)

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

If your pagebuilder-blocks are regular PHP files you can simply call `echo $page->your_pagebuilder` and ProcessWire will render the field for you. But if you want to use LATTE files instead, you can use RockFrontend to do so!

While you can always render repeater pagebuilder fields manually RockFrontend has some nice helpers. This is the long and manual way of rendering a pagebuilder field:

```php
// main.php
foreach($page->your_pagebuilder_field as $item) {
  // render every block and make the $page variable be the current block
  // instead of the viewed page.
  echo $rockfrontend->render("/pagebuilder/".$item->type, ['page' => $item]);
}

// pagebuilder type foo (/site/templates/pagebuilder/foo.php)
<h1><?= $page->title ?></h1>
```

Or simply use the shortcut:

```php
echo $rockfrontend->render($page->your_pagebuilder);

// or in a latte file
{$rockfrontend->render($page->your_pagebuilder)}

// example pagebuilder block: /site/templates/fields/your_pagebuilder/foo.latte
<h1>Foo block having id {$page->id}</h1>
```

Note that when using $rockfrontend->render() to render pagebuilder fields you can also use latte files for rendering and the `$page`variable in the view file will be the current pagebuilder block instead of the currently viewed page. If you need to access the current page you can use`$wire->page` instead of `$page`.

<img src=hr.svg>

## Using the /site/templates/\_init.php file

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
$scripts = $rockfrontend->scripts()
  ->add('path/to/your/script.js')
  ->addAll('add/all/scripts/of/this/folder')
  ;
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
echo $customscripts->render(); // from the example above
```

If you add LESS files to the StylesArray RockFrontend will use the name of the array as filename of the parsed CSS file:

```php
$rockfrontend->styles()->render() --> /site/templates/bundle/head.css
$rockfrontend->styles('head')->render() --> /site/templates/bundle/head.css
$rockfrontend->styles('foo')->render() --> /site/templates/bundle/foo.css
```

## FAQ

### Does RockFrontend force me to use a CSS Frontend Framework?

No! Some examples might use UIkit classes, but you can choose whatever framework you like (or none of course). You can also use TailwindCSS but of course you'll need to add your own frontend build pipeline!

### Does RockFrontend use an MVC pattern or force me to use one?

RockFrontend does not force you to use an MVC architecture, though I'm always using one. It's as simple as adding one file with very little code using the [brilliant core feature "custom page classes"](https://processwire.com/blog/posts/pw-3.0.152/#new-ability-to-specify-custom-page-classes).

<img src=hr.svg>

# Example \_main.php

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

- addIf() keeps your markup file cleaner than using if / echo / endif
- It automatically adds timestamps of files for cache busting
- You can inject scripts/styles from within other files (eg PW modules)

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
{$rockfrontend->render('img/icon.svg')}

// php
echo $rockfrontend->render('img/icon.svg');
```

You can even provide variables to replace, so you can create completely dynamic SVGs with custom rotation angles or colors etc...

```php
{$rockfrontend->render('img/triangle.svg', [
  // replace the {rotate} tag in the svg markup
  'rotate'=>45,
  'color'=>'blue',
])}

// add the replacement tag to your svg file
// img/triangle.svg
<svg style="transform: rotate({rotate}deg); border: 2px solid {color};">...
```

## Menus

RockFrontend comes with a handy method `isActive()` to keep your menu markup clean. Using `latte` you'll get super simple markup without if-else-hell:

```html
<nav
  id="tm-menu"
  class="tm-boxed-padding"
  uk-navbar
>
  <div class="uk-navbar-center uk-visible@m">
    <ul class="uk-navbar-nav">
      <li n:foreach="$home->children() as $item">
        <a
          href="{$item->url}"
          n:class="$rockfrontend->isActive($item) ? 'uk-active'"
        >
          {$item->title}
        </a>
        <div
          class="uk-navbar-dropdown"
          n:if="$item->numChildren()"
        >
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

## Grow/Shrink feature

<a href="https://www.youtube.com/watch?v=6ld4daFDQlY"><img src=https://user-images.githubusercontent.com/8488586/200658445-641f8127-7c22-4d41-8eb1-6c00bc0fccba.png height=300></a>

## PostCSS

All the above is done with some postCSS magic. You can have a look at `RockFrontend::addPostCSS()` how it is done.

You can also add custom postCSS rules quite easily:

```php
// eg in site/ready.php
$rockfrontend->addPostCSS('foo', function($markup) {
  return str_replace('foo', 'bar', $markup);
});
```

Will modify this CSS file:

```css
/* This is a foo + foo comment */
```

Into that output:

```css
/* This is a bar + bar comment */
```

## Multisite

Some features of RockFrontend might rely on the /site folder being present and therefore might not work in a multisite setup. See https://processwire.com/talk/topic/27895-multisite-support/
