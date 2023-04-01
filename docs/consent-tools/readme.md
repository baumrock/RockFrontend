# Consent Tools

<div class="uk-alert uk-alert-warning">Disclaimer: These docs are only a technical guide and no legal advice in any means. It's your responsibility to comply with your local laws.</div>

## Usage

```php
echo $rockfrontend->consent(
  'youtube',                       // consent name
  '<iframe src=...',               // code shown if access granted
  'sections/youtube-consent.latte' // code/file shown if not granted
);
```

This is similar to if-else short syntax and means:

- if the `youtube` consent has been granted by the user
- then render the markup of the second argument (here: iframe...)
- else render the markup of the third argument (here: sections/youtube...)

## Granting Consent

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
  defer
  rfconsent="optout"
  rfconsent-name="plausible"
  data-domain="yourdomain.com.ddev.site"
  rfconsent-src="https://plausible.yourdomain.com/js/script.js"
></script>
```

On the first pageload RockFrontend will check if an entry of `plausible` exists in the browsers localstorage. If not, it will create an entry which tells RockFrontend that it can load the script.

Then RockFrontend will add the `src` attribute to the script tag which will make the script load and do its work.

<div class=uk-alert>Note that you still need the possibility for the user to opt-out in your privacy policy. See the next section how to do that!</div>

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
