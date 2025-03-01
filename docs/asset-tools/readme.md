# Asset Tools

<div class='uk-alert uk-alert-warning'>This feature was removed with version 5!</div>

Docs archive: https://github.com/baumrock/RockFrontend/tree/34c8a7900a2754cd869e567c405031b722b9f77e/docs/asset-tools

## Upgrade Guide

Asset tools functionality has been migrated to the new `RockDevTools` module. This change was necessary due to a critical compatibility issue between the previous implementation and ProcessWire's template cache system. While this is a breaking change, it provided an opportunity to implement a more robust and efficient solution.

## TL;DR

All you have to do to upgrade a site from RockFrontend < 5 to version 5+ is to remove all calls of `styles()` and `scripts()` from your templates and add all assets manually via RockDevTools or any other tool you want to use (like Webpack, Vite, etc).

- Search your codebase for the strings `->styles()` or `->scripts()`
- Note all folders or assets that have been added with that method calls
- Download RockDevTools and add all assets to the module's asset tools
- Add the compiled and merged assets to your main markup file

## The old concept and the reason for the change

The old concept relied on a `Page::render()` hook and injected assets (css/js) as needed and then compiled them on demand and wrote the `<script>` and `<style>` tags to the main markup of your website.

The benefit of this concept is that you have been able to inject files from anywhere in the page render process: `_init.php`, `ready.php`, a custom module (like RockPageBuilder), or a file that renders an image slider where you could directly place the `scripts()->add('slider.js')` and `styles()->add('slider.css')` into that file. This was great as it meant a more modular approach and less setup on the user's side (just install a module and that was all there was to do).

What was not so great about this concept is that it was not obvious to some users why/which/where assets have been added to their site. I was always happy to provide options to prevent that from happening, but I understand that it is a frustrating experience to see things in your code that you didn't add (at least not intentionally).

What was also not great is that it allowed adding files of mixed types (css and less) and then during compilation messed up the order in that files where added to the website. And we all know that the loading order of css files matters!

Finally the concept was totally incompatible with ProcessWire's template cache. And there was no way of fixing this.

## The new concept

The new concept is based on the `RockDevTools` module. The idea of this module is to make it easy to merge/minify/compile css/less/js assets and add them to our website.

The main difference here is that RockDevTools is intended to run only during development. The idea is that it helps you create one global `styles.min.css` and one global `scripts.min.js` file that you can add to your websites assets and then simply add these assets in your main markup!

No magic injections anywhere during the render process. Only implicit actions from an easy to understand syntax:

```php
// site/templates/_init.php
if ($config->rockdevtools) {
  $devtools = rockdevtools();
  // compile all less files to CSS
  $devtools->assets()
    ->less()
    ->add(...)
    ->save('/site/templates/src/.styles.css');

  // merge and minify css files
  $devtools->assets()
    ->css()
    ->add(...)
    ->save('/site/templates/dst/styles.min.css');

  // merge and minify JS files
  $devtools->assets()
    ->js()
    ->add(...)
    ->save('/site/templates/dst/scripts.min.js');
}
```

Please also check the docs of RockDevTools here: [baumrock.com/RockDevTools](https://www.baumrock.com/RockDevTools)
