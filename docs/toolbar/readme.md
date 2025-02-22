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

## Toggle Tools

The toolbar includes a powerful toggle system that allows you to create interactive tools with on/off states. This system manages state changes, provides visual feedback, and can even persist user preferences.

### Basic Usage

To create a toggleable tool, add the `data-toggle` attribute to your toolbar item:

```html
<a href="#" data-toggle="myfeature">
  Toggle Feature
</a>
```

### Toggle States

The toggle system manages three state indicators:
1. CSS classes on the toolbar item (`on`/`off`)
2. CSS class on the toolbar root (using the toggle name)
3. LocalStorage state (when using `data-persist`)

### Showing Different Icons

You can show different icons based on the toggle state. Just add the classes `visible-on` and `visible-off` to your toolbar item:

```html
<a href="#" data-toggle="myfeature">
  <i class="visible-on fa fa-eye"></i>
  <i class="visible-off fa fa-eye-slash"></i>
</a>
```

### JavaScript Toggle Callbacks

The toolbar provides a JavaScript API to react to toggle events. Each toggle button (like overlays, grid, etc.) can have multiple callbacks that are executed when the toggle state changes. Here's how it works:

```js
// Add a callback for the 'overlays' toggle
RockFrontendToolbar.onToggle('overlays', (type) => {
  // type will be either 'on' or 'off'
  if (type === 'off') {
    document.body.classList.add("no-alfred");
  } else {
    document.body.classList.remove("no-alfred");
  }
});
```

### Persisting Toggle States

The toolbar supports persisting toggle states across page reloads using the `data-persist` attribute. This is particularly useful for maintaining user preferences like the sticky toolbar state.

When you add the `data-persist` attribute to a toggle button:
- The state (on/off) is automatically saved in the browser's localStorage
- The state is restored when the page reloads
- Users' preferences remain consistent as they navigate through different pages

Example of a sticky toggle button with persistence:

```html
<a
  title="Sticky on/off"
  uk-tooltip
  data-toggle="sticky"
  data-persist>
</a>
```

## Custom Tool Files

You can add custom tools to the RockFrontend toolbar by placing your PHP files in the directory `/site/modules/[your-module-name]/RockFrontendToolbar/`

Each PHP file in this directory will be automatically loaded as a tool. The file should output the HTML for your tool button. See one of the existing tools as an example!
