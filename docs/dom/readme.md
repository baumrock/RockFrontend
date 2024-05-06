# Dom Tools

RockFrontend provides a powerful set of DOM manipulation tools designed to simplify the process of working with HTML and SVG content in your ProcessWire projects. Whether you're looking to dynamically modify HTML documents, efficiently incorporate SVG graphics, or perform complex DOM operations, RockFrontend's DOM tools offer a streamlined and intuitive API to achieve your goals with minimal effort.

## SVG

```php
// Assigning the logo variable. It can be sourced from a pagefile:
$logo = $pages->get(123)->logo;

// Alternatively, it can directly reference a file path:
$logo = "/path/to/file.svg";

// You can also provide a relative url instead of a file path:
$logo = "/site/templates/img/icon.svg";

// Displaying the SVG logo with an added CSS class for styling:
echo rockfrontend()
  ->svgDom($logo)
  ->addClass("max-h-5");
```

Incorporating SVGs within the Latte template engine:

```latte
{rockfrontend()->svgDom($logo)->addClass("max-h-5")|noescape}
```

<div class="uk-alert uk-alert-warning">
  Please note that this method returns only the first &lt;svg> element that it finds and strips out all the markup that might be before or after that element! If you don't want that behaviour use `file_get_contents()` or `include` or place your markup manually.
</div>

## HTML

You can throw any HTML markp to the `dom` method of RockFrontend and then do whatever you want with the result.

For example you could make all links inside a `body` field absolute before sending the content as an e-mail:

```php
use Wa72\HtmlPageDom\HtmlPageCrawler;

$dom = rockfrontend()->dom($page->body);
$dom
  ->filter("a")
  ->each(function (HtmlPageCrawler $node) {
    $href = $node->getAttribute("href");

    if (!$href) return;
    if (strpos($href, "http://") === 0) return;
    if (strpos($href, "https://") === 0) return;

    $https = rtrim($this->wire->pages->get(1)->httpUrl(true),"/");
    $node->setAttribute("href", $https.$href);
  });
bd($dom->html());
```

## Performance

While parsing HTML is generally [quite efficient](https://github.com/wasinger/htmlpagedom?tab=readme-ov-file#history), processing extensive HTML documents can be time-consuming. Therefore, it's advisable to perform this operation during saveReady or limit it to smaller HTML segments (such as an SVG) to minimize any impact on performance.

```php
$start = microtime(true);
rockfrontend()->svg("/path/to/baumrock-logo.svg");
$time = (microtime(true) - $start) * 1000;
bd($time); // 0.16498565673828 ms

$start = microtime(true);
rockfrontend()->svgDom("/path/to/baumrock-logo.svg")->addClass("foo bar");
$time = (microtime(true) - $start) * 1000;
bd($time); // 0.54693222045898
```

Of course you can always use ProcessWire's great caching options so that this is not an issue!
