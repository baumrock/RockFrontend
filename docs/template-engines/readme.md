# Template Engines

RockFrontend makes it super easy to use Template Engines like `Latte` or `Twig`. As always RockFrontend does not force you to use one but it helps you if you want to.

## Latte

Latte by Nette is the default template engine and shipped with RockFrontend. See the docs <a href=https://latte.nette.org/en/>here</a> or check out all available tags <a href=https://latte.nette.org/en/tags>here</a>.

Also check out the thread <a href=https://processwire.com/talk/topic/27367-why-i-love-the-latte-template-engine>Why I love the Latte Template Engine</a> in the ProcessWire forum!

To use latte for your project all you have to do is to render a latte file like this:

```php
echo $rockfrontend->render('sections/header.latte');
```

### Translatable Strings

Since version 2.24.0 RockFrontend supports translatable strings in latte files!! 😎🥳

You just need to make sure that latte files are enabled in PW's translation system. RockFrontend can do that for you if you have RockMigrations installed and check the checkbox:

<img src=translations.png class=blur>

These are the three versions that you can use to translate strings in your LATTE files - choose whatever you prefer.

```latte
<p>{=__('This is a test')}</p>
<p>{=_x('foo bar', 'context')}</p>
<p>{=_n('Found one item', 'Found multiple items', 1)}</p>
<p>{=_n('Found one item', 'Found multiple items', 2)}</p>

<p>{$rf->_('This is a test')}</p>
<p>{$rf->_x('foo bar', 'context')}</p>
<p>{$rf->_n('Found one item', 'Found multiple items', 1)}</p>
<p>{$rf->_n('Found one item', 'Found multiple items', 2)}</p>

<p>{$rockfrontend->_('This is a test')}</p>
<p>{$rockfrontend->_x('foo bar', 'context')}</p>
<p>{$rockfrontend->_n('Found one item', 'Found multiple items', 1)}</p>
<p>{$rockfrontend->_n('Found one item', 'Found multiple items', 2)}</p>
```

Note that when using the function-syntax you must prepend the function call with an equal sign! While the translation will - in theory - also work without the equal sign you will not be able to translate the string in the backend, because the regex will not find it!

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
