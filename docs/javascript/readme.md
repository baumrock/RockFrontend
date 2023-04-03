# JavaScript

To use RockFrontend's JavaScript tools include the JS file in your main markup:

```php
$rockfrontend->scripts()->add(
  '/site/modules/RockFrontend/RockFrontend.min.js',
  'defer'
);
```

## Consent Tools

Please see the dedicated docs about [Consent Tools](../consent-tools/).

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

## Click

Trigger a click on another element:

```html
<a href="/foo/bar.jpg" rf-click="#foo">
  Trigger click on the #foo element
</a>
<div uk-lightbox>
  <a href="/foo/bar.jpg" id="foo">Show Image</a>
</div>
```

Triggering clicks on other elements can be helpful to follow the DRY (dont repeat yourself) principle. Another element might so something and might have additional logic applied (like asking for consent or confirmation) and you just want a second button that does exactly the same.

Note that we add the `href` attribute also to the triggering element to make it work even if JavaScript is disabled or to support "open in new tab" clicks.

## Toggle

Toggle the "hidden" attribute of another element

```html
<a href=# rf-toggle='#foo'>toggle foo</a>
<div id='foo'>hello world</div>
```
