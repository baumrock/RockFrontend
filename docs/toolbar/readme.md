# Toolbar

The RockFrontend Toolbar is a powerful administrative interface that seamlessly integrates into your frontend. It provides quick access to essential page editing functions, navigation shortcuts, and administrative tools - making content management efficient and intuitive for website administrators.

## Adding the toolbar to your frontend

Either directly:

```html
<html>
  <head>...</head>
  <body>
    <?= rockfrontend()->toolbar() ?>
  </body>
</html>
```

Or after customising it in `_init.php` via the dom() method:

```php
// _init.php
$dom = rockfrontend()->toolbar()->dom();
$dom->addClass('...');
$toolbar = $dom->outerHtml();

// _main.php
echo $toolbar;
```

## Styling the Toolbar

The toolbar is designed to be highly customizable, allowing you to match your website's design perfectly. Using RockFrontend's DOM tools, you can modify the HTML structure, add classes, and integrate with any CSS framework (Tailwind, UIkit, Bootstrap, etc.) without writing complex CSS overrides.

Add this code to `/site/templates/_init.php`:

```php
// Customize RockFrontend Toolbar
$dom = rockfrontend()->toolbar()->dom();
$dom->addClass('bg-secondary text-sm');
$dom->filter('#toolbar-tools')->addClass('uk-container uk-container-large');
$dom->filter('a')->addClass('text-white hover:text-secondary hover:bg-white transition');

// Write new markup to the $toolbar variable that will later
// be output in the main markup file
$toolbar = $dom->outerHtml();
```

## Making the Toolbar Sticky

For better usability, especially on long pages, you can make the toolbar stick to the top of the viewport. Simply add the `sticky` class to the toolbar DOM element:

```php
// Customize RockFrontend Toolbar
$dom = rockfrontend()->toolbar()->dom();
$dom->addClass('bg-secondary text-sm sticky');
```
