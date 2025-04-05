# The render() method

When using LATTE I'm mostly relying on its internal {include} feature. But before that I was heavily using the render method for anything and you can still do that as well.

Basic usage is simple:

```php
<?= $rockfrontend->render('/path/to/your/file.php') ?>
```

If your file lives in `/site/templates` you can use short paths:

```php
// will render /site/templates/sections/head.php
<?= $rockfrontend->render('sections/head.php') ?>
```

Your rendered files can be PHP or LATTE syntax. You can add other template engines easily (see below).

## Variables in rendered files

All ProcessWire API variables will be available in your rendered files:

```php
$rockfrontend->render('sections/foo.php');

// sections/foo.php
echo $page->title;
echo $config->httpHost;
echo $pages->get("/bar")->createdUser;
echo $user->isLoggedin();
```

## Custom variables in rendered files

Note that `render()` works different than PHP's `include` or `require`! It will NOT make all defined variables available to the rendered file by default.

This is best explained by an example:

```php
// define the foo variable
$foo = 'foo!';

// include the file
include "path/to/your/file.php";
```

And in that included file:

```php
echo $foo; // echos "foo!"
```

Whereas when using `$rockfrontend->render()` it works differently:

```php
$foo = 'foo!';
echo $rockfrontend->render("path/to/your/file.php");
```

And in that file:

```php
Current page id: <?= $page->id ?> // this will work
Value of foo: <?= $foo ?> // foo is not defined!
```

But you can provide custom variables easily:

```php
$foo = 'foo!';
echo $rockfrontend->render("path/to/your/file.php", [
  'foo' => $foo,
  'bar' => 'I am the bar value',
]);

// file.php
value of foo: <?= $foo ?> // outputs foo!
value of bar: <?= $bar ?> // outputs "I am the bar value"
```

You can also make all defined variables available in your rendered file, but note that this might overwrite already defined API variables (like `$pages`, `$files`, `$config`...) so use this technique with caution:

```php
echo $rockfrontend->render(
  '/path/to/your/file.php',
  get_defined_vars()
);
```
