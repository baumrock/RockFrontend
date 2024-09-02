# field() method

The `field()` method is a versatile function designed to retrieve and render fields from a Page object in ProcessWire. This method is particularly useful when working with prefixed fields or when using the Latte templating engine and provides several options for rendering the field's value.

On more complex projects or when your modules ship fields it is a good idea to add prefixes to make name clashes less likely. The downside is that field names become cumbersome, e.g. `rockpagebuilder_faq_question`. The field() api provides a quick way to access these fields from the blocks view file:

```php
echo $block->field('question', 'e');
```

## Usage

In RockPageBuilder blocks you can call the `field()` method directly on the $block object. For regular pages you need to use `$rockfrontend->field(...)` syntax or the shorter `$rf->field(...)`.

```php
echo $rockfrontend->field($page, 'myfield', 'e');
```

Or using latte syntax:

```latte
{$rockfrontend->field($page, 'myfield', 'e')}

{* or using short syntax *}
{$rf->field($page, 'myfield', 'e')}
```

## Parameters

- **Page $page**: The Page object from which the field will be retrieved.
- **string $shortname**: The short name of the field to be retrieved. This is useful for fields with prefixes.
- **string $type**: (optional) The type of rendering to be applied to the field's value. The default is 'f' for formatted. The available options are:
  - 'e': Frontend editable field.
  - 'u': Unformatted value.
  - 'f': Formatted value (default).
  - 's': Formatted value forced as a string.
  - 'a' or '[]': Formatted value as an array (e.g., for page images).
  - 'first': Formatted value as a single item (e.g., for a single page image).

## Short fieldname and prefixed fieldnames

The short fieldname is the fieldname without the prefix. For example, if your field is named `rockpagebuilder_faq_question` the short fieldname is `question`. That means it will always only check for the very last part of the fieldname (here "question" and not "faq_question").

## Latte

Latte will by default escape the field value, if you want to output the field value as raw text, you can use the `|noescape` modifier.

This often leads to headlines like `Hello &amp; World` instead of `Hello & World`.

When using the field method RockFrontend will automatically return a HTML object if Latte is used. This way you can use simple tags to output the field value and Latte will NOT escape it. That's because we handle the escaping on the ProcessWire side using text formatters.

```latte
{$rockfrontend->field('myfield', 'e', 'raw')}

{* or using short syntax *}
{$rf->field('myfield', 'e', 'raw')}
```

Not using Latte? No problem - it will only be used if Latte is available.
