# Asset Tools

<div class='uk-alert uk-alert-warning'>NOTE: RockFrontend's asset tools may cause problems when using template cache! See https://processwire.com/talk/topic/30145-- for details. ProCache works fine!</div>

Working with site assets can be tedious.

In your HTML markup the browser expects file urls whereas PHP expects system paths for several file operations. Converting from one to another can quickly lead to errors like forgetting to add/remove a slash somewhere. Or you forget to add a cache busting mechanism and waste time fixing bugs that do actually not exist just because your browser is serving an outdated version...

That's why RockFrontend comes with a dedicated PHP class that provides several neat helpers for working with scripts and styles.

## Usage

Typically you'd add styles and scripts in your `_main.php` file:

```php
/** @var RockFrontend $rockfrontend */
$rockfrontend->styles()
  ->add("/site/templates/uikit/src/less/uikit.theme.less")
  ...
  ->addDefaultFolders() // autoload styles in sections, partials, etc
  ->minify(!$config->debug);

$rockfrontend->scripts()
  ->add("/site/modules/RockFrontend/scripts/rf-scrollclass.js", "defer")
  ->add("/site/templates/uikit/dist/js/uikit.min.js")
  ->add("/site/templates/scripts/main.js", "defer")
  ->minify(!$config->debug);
```

<div class="uk-alert uk-alert-warning">Note that we only add assets, but we do NOT echo anything here! See section about Auto-Rendering below.</div>

## AssetsArray

If you call `$rockfrontend->styles()` RockFrontend will return a `StylesArray` object. If you call `$rockfrontend->scripts()` you'll get a `ScriptsArray`. Both are instances of the base class `AssetsArray`, which provides helper methods for quick and easy working with your sites assets.

Technically you can create as many AssetArrays as you want:

```php
// all examples are the same for ->scripts()
$rockfrontend->styles('head')
$rockfrontend->styles() // short version of the above!

$rockfrontend->styles('foo')
$rockfrontend->styles('bar')
$rockfrontend->styles('baz')
```

## Auto-Rendering

Note that only the `head` scripts and styles will be rendered automatically. They will be rendered into the `<head>` of your website.

RockFrontend will hook into `Page::render` and inject all styles and scripts that you added to your `head` arrays (using either `scripts()->add(...)` or `styles()->add(...)`).

All other AssetArrays can be rendered manually wherever you want:

```html
<html>
  <head>...</head>
  <body>
  <?= $rockfrontend->scripts('body')->add('foo.js')->render(); ?>
  </body>
</html>
```

Note that the preferred way of adding scripts to your site is in `<head>` with the `defer` attribute rather than adding it to the bottom of your `<body>` element. Personally I've never ever needed any other assets than the default ones with name `head`.

## Adding files

You can either add single files:

```php
$rockfrontend->styles()->add('path/to/your/file.css');
```

Or you can add all files within a directory:

```php
$rockfrontend->styles()->addAll('path/to/your/files');
```

You can also use the `addDefaultFolders()` shortcut to add all styles in `site/templates/ layouts | less | sections | partials`

You can also add files only if a condition is met:

```php
$rockfrontend->scripts()->addIf(
  'foo.js',                  // filename
  $page->template == 'home', // condition
  'defer'                    // optional suffix
);
```

## Rendering Scripts

```php
$rockfrontend->scripts()->add('foo.js')->add('bar.js');

// renders the following
<script src='foo.js'></script><!-- _main.php:17 -->
<script src='bar.js'></script><!-- _main.php:17 -->
```

If debug mode is enabled RockFrontend will add notes about where the script was added - in our case both scripts were added in line 17 of `_main.php`.

### Defer / Suffix

When adding scripts to your site you often want them to be loaded after the dom content to be non-blocking. This can be done by adding the `defer` suffix to your `add()` call:

```php
$rockfrontend->scripts()->add('foo.js', 'defer');
```

Note that you can use any string you want as suffix:

```php
$rockfrontend->scripts()->add(
  'https://code.jquery.com/jquery-3.6.4.min.js',
  'integrity="sha256-oP6HI9z1XaZNBrJURtCoUT5SUnxFr8s3BzRl+cbzUq8=" crossorigin="anonymous"'
);

// renders to
<script src='https://code.jquery.com/jquery-3.6.4.min.js' integrity="sha256-oP6HI9z1XaZNBrJURtCoUT5SUnxFr8s3BzRl+cbzUq8=" crossorigin="anonymous"></script>
```

## Rendering Styles

Rendering styles is very similar to rendering scripts. The only notable difference is that you can provide multiple LESS files and RockFrontend will automatically parse and merge them to one single CSS file.

### Less Parser

```php
$rockfrontend->styles()
  ->add('/site/templates/uikit/src/less/uikit.theme.less')
  ->add('/site/modules/RockFrontend/uikit/defaults.less')
  ->add('/site/modules/RockFrontend/uikit/offcanvas.less')
  ->add('/site/modules/RockFrontend/less/defaults.less')
  ->add('/site/modules/RockFrontend/less/boxed-layout.less')
  ->add('/site/modules/RockFrontend/less/sticky-footer.less')
  ->add('/site/modules/RockFrontend/less/headlines.less')
  ->addDefaultFolders() // finally autoload styles in sections, partials, etc
  ->minify(!$config->debug);
```

Renders something like to the following:

```html
  [...]
  <!-- loading /site/templates/uikit/src/less/uikit.theme.less (_main.php:7) -->
  <!-- loading /site/modules/RockFrontend/uikit/defaults.less (_main.php:8) -->
  <!-- loading /site/modules/RockFrontend/uikit/offcanvas.less (_main.php:9) -->
  <!-- loading /site/modules/RockFrontend/less/defaults.less (_main.php:10) -->
  <!-- loading /site/modules/RockFrontend/less/boxed-layout.less (_main.php:11) -->
  <!-- loading /site/modules/RockFrontend/less/sticky-footer.less (_main.php:12) -->
  <!-- loading /site/modules/RockFrontend/less/headlines.less (_main.php:13) -->
  <!-- loading /site/templates/layouts/home.less (_main.php:14) -->
  <!-- loading /site/templates/less/_global.less (_main.php:14) -->
  <!-- loading /site/templates/less/hooks.less (_main.php:14) -->
  <!-- loading /site/templates/sections/docs.less (_main.php:14) -->
  <!-- loading /site/templates/sections/footer.less (_main.php:14) -->
  <!-- loading /site/templates/sections/header.less (_main.php:14) -->
  <link href='/site/assets/RockFrontend/css/head.css?m=1680272932' rel='stylesheet'><!-- LESS compiled by RockFrontend -->
```

### SCSS Parser

The SCSS Parser works similiar to the Less Parser, but uses the filetype .scss. In order for it to work, you need to install the module "Scss": https://processwire.com/modules/scss/

```php
$rockfrontend->styles()
  ->add('/site/templates/theme/site.scss'))
  ->addDefaultFolders() // finally autoload styles in sections, partials, etc
  ->minify(!$config->debug);
```

You can work with a main entry file according to the official UIKit (https://getuikit.com/docs/sass) or Bootstrap documentation (https://getbootstrap.com/docs/5.2/customize/sass/) as shown in the snippet above. In this case, consider creating a new folder (i.e. /site/templates/theme/) and using this folder for your main SCSS files. This gives you a certain level of control over your SCSS setup.

Or you can just have RockFrontend autoload everything in the /site/templates/sass/ folder.

## Cache Busting

All rendered assets will automatically get a cache busting timestamp based on the file modification date:

```html
<link href="/site/assets/RockFrontend/css/head.css?m=1680273513" rel="stylesheet">
```

This makes sure that the browser does not load an outdated version of your assets.

## Minify

RockFrontend can automatically minify your assets. Simply call `->minify()` on the assets array. You can also provide a parameter of `true` or `false` - usually I do this:

```php
->minify(!$config->debug)
```

This tells RockFrontend to minify assets if debug mode is OFF (which is typically the case on production environments) but it will not minify if debug mode is ON during development.