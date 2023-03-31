# JavaScript Snippets

To use snippets simly add them to your `scripts()` array:

```php
$rockfrontend->scripts()
  ->add('/site/modules/RockFrontend/scripts/rf-scrollclass.min.js');
```

## Scrollclass

This snippet can add or remove a css class on a given scroll position. This is helpful for scroll-to-top buttons that only appear when the user scrolled down a little bit:

```html
<a href='#' rf-scrollclass='to-top show@300'>Scroll to top</a>
```

You can also add multiple classes at different scroll positions which can be helpful for treating things differently on different devices or screen resolutions:

```html
<a href='#' rf-scrollclass='to-top show-desktop@300 show-mobile@600'>Scroll to top</a>
```

```css
.to-top { display: none; }
.to-top.show-mobile {
  display: inline-block;
}
@media(min-width: 960px) {
  .to-top.show-desktop {
    display: inline-block;
  }
}
```

## Toggle

Toggle the "hidden" attribute of another element

```html
<a href=# rf-toggle='#foo'>toggle foo</a>
<div id='foo'>hello world</div>
```
