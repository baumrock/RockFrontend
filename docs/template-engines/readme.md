# Template Engines

RockFrontend makes it super easy to use Template Engines like `Latte` or `Twig`. As always RockFrontend does not force you to use one but it helps you if you want to.

## Latte

Latte by Nette is the default template engine and shipped with RockFrontend. See the docs <a href=https://latte.nette.org/en/>here</a> or check out all available tags <a href=https://latte.nette.org/en/tags>here</a>.

Also check out the thread <a href=https://processwire.com/talk/topic/27367-why-i-love-the-latte-template-engine>Why I love the Latte Template Engine</a> in the ProcessWire forum!

To use latte for your project all you have to do is to render a latte file like this:

```php
echo $rockfrontend->render('sections/header.latte');
```

### Hooking Latte

To extend the functionality of Latte (for example to add custom filters), you can hook into the `RockFrontend::loadLatte` method. This allows you to modify the Latte environment before it's used to render templates. The example below demonstrates how to add a custom filter named `shortify`, which truncates a string to 10 characters.

```php
// site/ready.php
$wire->addHookAfter("RockFrontend::loadLatte", function ($event) {
  $latte = $event->return;
  $latte->addFilter('shortify', fn (string $s) => mb_substr($s, 0, 10));
});
```

You created a useful filter? Share it with us in the forum!


## Twig

Twig is not shipped with RockFrontend by default, but can easily be added via composer in the PW root directory:

```sh
composer require "twig/twig:^3.0"
```

Then you can render twig files like this:

```php
echo $rockfrontend->render('sections/header.twig');
```

## Adding Other Template Engines

You can easily add any other Template Engine to RockFrontend. Say we wanted to add the `Foo` engine that renders all `.foo` files.

First we create the file `demo.foo`:

```latte
I am the foo demo file
```

Then we add the `renderFileFoo` method to RockFrontend via hook in `init.php`:

```php
$wire->addHookMethod("RockFrontend::renderFileFoo", function ($event) {
  $file = $event->arguments(0);
  $out = $this->wire->files->render($file);
  $event->return = "--foo-- $out --foo--";
});
```

Then we can render that file in any of our template files:

```php
echo $rockfrontend->render('sections/demo.foo');
```

RockFrontend will see the `.foo` extension and call the `renderFileFoo` method that we added. The output would look like this:

```latte
--foo-- I am the foo demo file --foo--
```
