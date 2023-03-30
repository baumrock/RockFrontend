# Consent Tools

## Usage

```php
echo $rockfrontend->consent(
  'youtube',
  '<iframe src=...',
  'sections/youtube-consent.php'
);
```

This is similar to if-else short syntax and means:

- if the `youtube` consent has been given by the user
- then render the markup of the second argument
- else render the markup of the third argument

## Enabling Consent

In `youtube-consent.php` you could have the following code:

```php
<h2>We need your consent</h2>
<p><a href=# rfc-allow="youtube">Allow YouTube</a></p>
```

## Managing Consent

Once enabled the user can toggle consent via simple checkboxes that you typically place into your privacy policy. Simply enable the `TextformatterRockFrontend` textformatter on any markup field and add the `rf-consent` tags to that markup:

```
Privacy Policy

## YouTube

We want to ....

[rf-consent=youtube]Allow embedding YouTube videos[/rf-consent]
```
