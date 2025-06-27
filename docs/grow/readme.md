# Grow Feature

Sometimes instead of using breakpoints it is far easier to define fluid values (like font sizes or margins):

```php
<h2
  style='
    font-size: <?= $rf->grow(70, 200) ?>;
    margin-top: <?= $rf->grow(-10, -30) ?>;
  '
>A fancy headline that grows from 70px @ 375 up to 200px @ 1440 viewports</h2>
```

The minimum threshold is by default 375px and the maximum is 1440px. You can change this in `_init.php`:

```php
$rockfrontend->growMin = 500;
$rockfrontend->growMax = 900;
```

Or you can adjust it as needed with your grow() call:

```php
$rf->grow(10, 50, 500, 600)
```

This is a very simple but powerful helper that helps you create awesome responsive designs.

## shrink

Behind the scenes when you call `grow(50, 10)` RockFrontend will pass this to `shrink(50, 10)`, which is also a public method that you can use.
