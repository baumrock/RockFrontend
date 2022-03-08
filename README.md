# RockFrontend

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
