# SEO Tools

RockFrontend comes with tools that help you build SEO optimized websites in no time.

## Usage

While you can customise every aspect of the output the main goal is that usage is still very simple.

For example you just return a `PageImage` object in the `og:image` tag and RockFrontend will take care of getting the correct image url and size.

Or you can just throw the content of a TinyMCE field into the `description()` function and RockFrontend will take care of removing all html tags and truncating the text to the desired length:

`label: /site/templates/_main.php`
```php
<head>
  ...
  <?php
  echo $rockfrontend->seo()

    ->title("ACME - " . $page->title)

    ->description(function (Page $page) {
      if($page->template == 'car') return $page->cardescription;
      return $page->body;
    })

    ->setValue('og:image', function (Page $page) {
      if($page->coverpic) return $page->coverpic;
      if($page->heroimage) return $page->heroimage;
      return $this->wire->pages->get(1)->defaultpic;
    });
  ?>
</head>
```

Most values can either be set as string (for simple situations like the `title` in the example above) or as a callback that receives the current `$page` as first argument (like in the `description` in the example above).

## Default Configuration

If you don't want to use the default configuration, all tags are fully configurable. The default configuration can be inspected by dumping the RockFrontend Seo object:

```php
bd($rockfrontend->seo());
```

<img src=seo.png class=blur alt='Default SEO Configuration'>

The recommendations are mostly inspired from https://moz.com/learn/seo.

You can manipulate tags via `setMarkup()` and `setValue()`:

### setMarkup()

Let's say we wanted to add a new `foo` tag to our SEO tools:

```php
$rockfrontend->seo()
  ->setMarkup("foo", "<meta property='foo' content='{value:20}'>");
```

### setValue()

Now we can set a new value for the `foo` tag based on the page's title:

`label: string`
```php
$rockfrontend->seo()
  ->setValue($page->title);
```

Note that as we defined `{value:20}` in the tag's markup the page title will be truncated to 20 characters!

## Page Title

The page title can either be set as a string or as a callback:

`label: string`
```php
$rockfrontend->seo()->title($page->title);
```

`label: callback`
```php
$rockfrontend->seo()
  ->title(function ($page) {
    if ($page->headline) return "{$page->title} - {$page->headline}";
    return $page->title;
  });
```

This will not only set the `<title>` tag but also the `og:title` tag:

`label: output`
```html
<title>RockFrontend - SEO Tools</title>
<meta property="og:title" content="RockFrontend - SEO Tools">
```

## Page Description

The same concept applies to the page description tag:

`label: string`
```php
$rockfrontend->seo()->description($page->body);
```

`label: callback`
```php
$rockfrontend->seo()
  ->description(function ($page) {
    if ($page->template == 'car') return $page->cardescription;
    return $page->body;
  });
```

`label: output`
```html
<meta name="description" content="Your Page Description">
<meta property="og:description" content="Your Page Description">
```

## Website Manifest File

You can create your website's manifest file from the RockFrontend module config. RockFrontend will then add that manifest file to your site's `<head>` section which will make the website appear in your selected color theme:

<img src=manifest.jpg class=blur alt='Manifest File Preview' width=250>

<img src=manifest2.png class=blur alt='Manifest File Settings' width=250>
