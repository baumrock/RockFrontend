# Latte Autoload Layout

By default, RockFrontend will load the file `_main.latte` with every request.

If you don't want this, you have several options to change this behaviour:

- You can set a different layout file name in the module settings (eg layout.latte).
- You can disable this feature completely by checking the "Disable Autoload-Layout" checkbox in the module settings.
- You can disable this feature during runtime (see next section).

## Disable Autoload-Layout during runtime

This can be useful for non-standard requests, eg. when rendering PDFs or when rendering completely custom layouts different from the default one. All you have to do is to set the variable `$rockfrontend->noLayoutFile` to `true` before the layout is being loaded:

```php
$rockfrontend->noLayoutFile = true;
```

For example, you could create this file: `site/templates/basic-page.latte`:

```latte
{do $rockfrontend->noLayoutFile = true}
<html>
<head>
  <title>Basic Page</title>
</head>
<body>
  <h1>Basic Page</h1>
</body>
</html>
```

As you can see, we set `$rockfrontend->noLayoutFile = true` right at the top of the file.

This will prevent the default layout from being loaded and RockFrontend will render the custom markup instead.
