# Toolbar

The RockFrontend Toolbar is a powerful administrative interface that seamlessly integrates into your frontend. It provides quick access to essential page editing functions, navigation shortcuts, and administrative tools - making content management efficient and intuitive for website administrators.

<img src=https://i.imgur.com/7cZbtXF.png class=blur>

## Adding the toolbar to your frontend

You can add the toolbar to your frontend markup wherever you want. I like to place it right after the opening `<body>` tag - but if your design already has a sticky header, for example, you can place it into that header as well to make sure it is always visible!

This is how you output the un-modified toolbar:

```html
<html>
  <head>...</head>
  <body>
    <?= rockfrontend()->toolbar() ?>
  </body>
</html>

// latte example
<html>
  <head>...</head>
  <body>
    {rockfrontend()->toolbar()|noescape}
  </body>
</html>
```

But you can also customize the toolbar's markup easily via RockFrontend's dom-tools. This can be handy and you can make your toolbar 100% match the look & feel of your frontend.

All you have to do is to grab the toolbar, modify it, then finally output it. The principle is as follows:

```php
// for example in _init.php
// Customize RockFrontend Toolbar
$toolbar = '';
if ($user->isLoggedin()) {
  // ...
}

// at the desired position of your markup (eg _main.latte/php or whatever)
// note that the output must happen AFTER you modify the toolbar via dom()
echo $toolbar;
```

> Note: The toolbar will only show up for logged in users!

## Styling the Toolbar

The toolbar is designed to be highly customizable, allowing you to match your website's design perfectly. Using RockFrontend's DOM tools, you can modify the HTML structure, add classes, and integrate with any CSS framework (Tailwind, UIkit, Bootstrap, etc.) without writing complex CSS overrides.

Add this code to `/site/templates/_init.php`:

```php
// Customize RockFrontend Toolbar
$toolbar = '';
if ($user->isLoggedin()) {
  $toolbar = rockfrontend()->toolbar();
  $dom = $toolbar->dom();
  $dom->addClass('!bg-secondary text-sm');
  $dom->filter('#toolbar-tools')->addClass('uk-container uk-container-large');
  $dom->filter('a')->addClass('!text-secondary hover:!bg-primary-dark hover:!text-white transition');
  $toolbar = $dom->outerHtml();
}
```

## Custom Tool Files

You can add custom tools to the RockFrontend toolbar by placing your PHP files in one of the following directories:

- `/site/templates/RockFrontendToolbar/`
- `/site/modules/[your-module-name]/RockFrontendToolbar/`

Each PHP file in these directories will be automatically loaded as a tool. The file should output the HTML for your tool button. See one of the existing tools as an example!

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