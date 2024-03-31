# Consent Tools

<div class="uk-alert uk-alert-warning">Disclaimer: These docs are only a technical guide and no legal advice in any means. It's your responsibility to comply with your local laws.</div>

## Setup

To make the consent tools available you need to include RockFrontend's JavaScript file in your main markup file! See <a href="../javascript/">JavaScript</a>.

## Idea

The idea of RockFrontend's consent tools is to take care of the tedious parts and let the markup be 100% up to you.

The tedious part that RockFrontend will do for you:

- It will remember the users decision in `localStorage`
- It will make that accessible via `rf-consent` attributes in your markup without the need to write custom JS
- It will provide a Textformatter that renders checkboxes on your privacy page to toggle the decision any time
- It provide an easy way to make sure scripts are only loaded if consent is given
- It will make sure that scripts are loaded as soon as consent is given (not only on the next page load)

## Hello World Example

This simple example will demonstrate how it works:

```html
<button rfc-allow="hello-world">Allow Hello World Script</button>
<template rf-consent="hello-world">
  <script>alert('hello world')</script>
</template>
```

<button rfc-allow="hello-world">Allow Hello World Script</button>
<template rf-consent="hello-world">
  <script>alert('hello world')</script>
</template>




The tedious part is to remember the user's choice (eg. did he/she allow embedding of youtube?) and to render different markup based on the user's decision (and making sure script tags are loaded only if we are allowed and also make). RockFrontend does that by storing the decision in the `localStorage` of the browser and you can access this state easily via `rf-consent` attributes in your markup.

##













## Alternate Consent Markup

If you want to show alternate markup if the user has not granted consent yet, you can use the `consent()` method:

```php
echo $rockfrontend->consent(
  // consent name
  'youtube',
  // code shown if "youtube" access granted
  '<iframe src=...',
  // code/file shown if "youtube" not granted
  'sections/youtube-consent.latte'
);
```

This is similar to if-else short syntax and means:

- if the `youtube` consent has been granted by the user
- then render the markup of the second argument (here: iframe...)
- else render the markup of the third argument (here: sections/youtube...)

Note that this

### Granting Consent

In `youtube-consent.latte` you can have any code you want. The only thing you need to have is a link that when clicked grants access and it is as easy as adding the `rfc-allow` attribute to your link (matching the name of your consent embed of course):

```latte
<h2>We need your consent</h2>
<p><a href=# rfc-allow="youtube">Allow YouTube</a></p>
```

Once the user clicks on that link rockfrontend will show all `youtube` embeds.

## Opt-Out

If you want to include a script that you don't need prior consent for (like Plausible Analytics for example) you can do so like this:

```php
echo $rockfrontend->consentOptout(
  "plausible",
  "<script defer data-domain='{$config->httpHost}' src='https://plausible.yourdomain.com/js/script.js'></script>"
);
```

This will render the following tag:

```html
<script
  rfconsent="plausible"
  rfconsent-type="optout"
  rfconsent-src="https://plausible.yourdomain.com/js/script.js"
  data-domain="yourdomain.com.ddev.site"
  defer
></script>
```

On the first pageload RockFrontend will check if an entry of `plausible` exists in the browsers localstorage. If not, it will create an entry which tells RockFrontend that it can load the script.

Then RockFrontend will add the `src` attribute to the script tag which will make the script load and do its work.

<div class=uk-alert>Note that you still need the possibility for the user to opt-out in your privacy policy. See the next section how to do that!</div>

## Consent Confirmation Modal

Another option is to intercept clicks on buttons and show a confirmation modal before the actual action takes place. For example you could have a button that says "play video" which would add the Youtube player in an UIkit modal.

`label: Usage`
```html
<a
  rfconsent="youtube"
  rfconsent-click=".player"
  rfconsent-ask=".ask"
  rfconsent-allow="#allow"
  class="watch uk-button uk-button-default"
  href="https://youtu.be/..."
>
  Watch the video
</a>
```

These are the necessary attributes:

### `rfconsent`

Name of the consent group.

### `rfconsent-click`

CSS selector of the element that gets clicked on consent.

### `rfconsent-ask`

CSS selector of the elmenent that gets clicked to ask for confirmation.

### `rfconsent-allow`

CSS selector of the element that needs to be clicked (usually in the modal) to grant consent.

### UIkit Example

In this example we use the UIkit modal component, but you can use RockFrontend's consent tools with whatever framework or markup you want!

This is the modal we want to show:

<img src=modal.png class=blur>

And here is the code that creates that modal in UIkit:

```html
<a class="ask" href="#yt-modal" uk-toggle></a>
<div id="yt-modal" uk-modal>
  <div class="uk-modal-dialog uk-modal-body">
    <h2 class="uk-modal-title">
      <svg ...></svg> YouTube
    </h2>
    <p>
      Wir möchten ...
    </p>
    <p class="uk-text-right">
      <button class="uk-button uk-button-default uk-modal-close" type="button">Abbrechen</button>
      <button id="allow" class="uk-button uk-button-primary uk-modal-close" type="button">Video ansehen</button>
    </p>
  </div>
</div>
```

You need to add this markup to all the pages that could possibly show the consent dialog. In other words: All pages that have a youtube link on them.

Note that the toggle anchor that opens the modal is empty! We only need the element to be present in the markup, but we will click it via JavaScript AFTER the user has given consent.

Also note that we set `#allow` as ID rather than as class, because UIkit creates the modal div outside of the `#example` wrapper, so `#example .allow` would not work!

## Managing Consent

If the user has already granted access to one of your embeds he/she can toggle consent via simple checkboxes that you typically place into your privacy policy. Simply enable the `TextformatterRockFrontend` textformatter on any markup field and add the `rf-consent` tags to that markup:

```txt
Privacy Policy
We would like to ...
[rf-consent=youtube]Allow embedding YouTube videos[/rf-consent]
```

This would render like this when showing that page:

<img src=checkbox.png class=blur>

And it would render like this when frontend editing that page:

<img src=checkbox2.png class=blur>

---

Looking for the docs of an older version? See <a href=https://github.com/baumrock/RockFrontend/blob/25349daa183546f86e19bbfbd01d189ec33ac152/docs/consent-tools/readme.md>here.</a>
