# postCSS

<div class='uk-alert'>Note: You need to enable the `postCSS` feature in the module's settings!</div>

## rfGrow

This is one of my favourite features!! It's hard to explain why it is so great - you need to try it and see for yourself 🤩

Be sure to check out the video about the `rfGrow` feature aka fluid font sizes:

<a href='https://www.youtube.com/watch?v=6ld4daFDQlY'><img src=rfgrow.png></a>

See this example:

```css
.my-section {
  padding: rfGrow(20px, 100px) 0;
}
```

Here we add a vertical padding of 20px on mobile and 100px on desktop.

By default RockFrontend will use `360px` as the mobile screen with and `1440px` as desktop screen with. You can adjust these settings in your `config.php`:

```php
$config->growMin = 250;
$config->growMax = 1920;
```

You can also provide the min/max settings directly in the method call:

```css
.foo {
  font-size: rfGrow(20px, 50px, 500, 1000);
}
```

This means that we have a font-size of 20px on screens up to 500px width that grows up to 50px on 1000px screen width. It converts to this css rule:

```css
.foo {
  font-size: clamp(20px, 20px + 30 * ((100vw -  500px) / ( 1000 -  500)), 50px);
}
```

## rfShrink

The shrink feature works just like the grow feature but in different direction:

```css
.hero {
  padding-left: rfShrink(100px, 10px);
}
```

That means we get 100px padding on mobile that shrinks down to 10px on desktop screens.

## PX -> REM

Whenever you write a value as `pxrem` instead of just `px` RockFrontend will convert that value from pixels to rem units.

```css
div {font-size: 16pxrem;}
/* converts to */
div {font-size: 1rem;}
```