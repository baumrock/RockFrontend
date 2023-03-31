# Live-Reload

Good bye refresh-button, hello RockFrontend Live-Reload! 😎

To enable the live-reload feature you just need to add this to your site's config:

```php
// make RockFrontend watch for changes every second
$config->livereload = 1;
```

<div class="uk-alert uk-alert-danger">ATTENTION: Make sure that you disable livereload on production!</div>

Then refresh your page and open the developer tools. In the console you should see `RockFrontend is listening for file changes...` which shows that live-reload is working.

<div class="uk-alert">
<div class="uk-margin-small">Note that LiveReload does only reload the page if the tab is open!</div>
<div class="uk-text-small">This is to make sure that when using RockMigrations you don't get multiple migration runs at the same time because of multiple open browser tabs.</div>
</div>

## Config

If you do a `bd($rockfrontend->getLiveReload());` you can see the default setup:

<img src=livereload.png class=blur>

I've never had the need to do that, but you can optionally customize the config of LiveReload:

```php
$config->livereload = [
  // interval to watch for changes
  'interval' => 1, // 1s = default

  // user defined include paths
  'include' => [
    '.*/foo/bar',
  ],

  // you can reset default include paths
  'includeDefaults' => [],

  // user defined exclude regexes
  'exclude' => [
    '.*/site/my-ignored-folder',
  ],

  // you can reset default excludes
  'excludeDefaults' => [],
];
```

## How does it work?

RockFrontend starts an SSE stream once you visit a page. In that SSE stream it triggers LiveReload::watch() in the configured interval (usually every second). If it finds a file that has changed since the page has been visited it triggers a reload via JavaScript.

## Debugging

If you get unexpected reloads check the `livereload` log in the PW backend. Whenever RockFrontend detects a changed file in the LiveReload stream it will log the filename in the livereload log.

## Browser Support

Note that Firefox will always jump to the top of the page while Chrome will keep the scroll position!

## DDEV

If using DDEV make sure you have a correct webserver type otherwise the reloads might be buggy and slow! You need to have `webserver_type: apache-fpm`
