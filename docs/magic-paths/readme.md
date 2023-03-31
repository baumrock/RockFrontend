# Magic Paths

RockFrontend supports `magic paths` in several places, for example when adding files to a `ScriptsArray` or `StylesArray` or when using `$rockfrontend->render(...)`.

```php
// all of them will work
$rockfrontend->render('sections/foo')
$rockfrontend->render('sections/foo.latte')
$rockfrontend->render('/site/templates/sections/foo')
$rockfrontend->render('/site/templates/sections/foo.latte')
```

## Subfolder Installations

Magic Paths make it possible to define paths relative to the PW root or relative to the templates folder.

For example if you had a PW installation in the `foo` subfolder and you defined your scripts like this:

```php
$rockfrontend->scripts()->add('/site/templates/scripts/foo.js');
$rockfrontend->scripts()->add('scripts/foo.js');
```

Both versions would work and would result in the following tag (note the `/foo` at the beginning of `src`):

```html
<script src="/foo/site/templates/scripts/foo.js?m=1680038677"></script>
```

## Extensions

The short version without providing the file extension works for `php` and `latte` files.