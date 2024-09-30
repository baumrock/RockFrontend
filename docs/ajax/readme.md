# AJAX Endpoints

<div class='uk-alert uk-alert-warning'>Always make sure to properly sanitize user input and also make sure to protect your endpoints from unauthorized access!</div>

<a href='https://youtu.be/xT4Y7MQwP3M'><img src=https://i.imgur.com/qOlJKRz.jpeg></a>

As modern websites become more interactive, the need for AJAX endpoints has become increasingly important. RockFrontend makes it easy to create and use AJAX endpoints with HTMX, `fetch()` or any other AJAX technology.

To create a new endpoint, all you need to do is place a PHP file within the `/site/templates/ajax/` directory. For example, adding a file named `foo.php` in this directory automatically generates an endpoint accessible via `/ajax/foo`.

## Trailing Slashes

Note that RockFrontend ajax endpoints will never have a trailing slash. All requests to an url with a trailing slash will be redirected to the same url without a trailing slash and get parameters will be lost.

## Nested Endpoints

You can also create nested endpoints by creating folders inside the ajax folder. Requests to `/ajax/foo/bar` will look for a file named `foo/bar.php` in the ajax folder and will be served from the url `/ajax/foo/bar`.

## Example Endpoint

A minimal endpoint can look like this:

```php
<?php
return ['foo', 'bar'];
```

If your endpoint returns an array, RockFrontend automatically formats the response as JSON. This feature allows you to focus on your data logic without worrying about the response format.

## User Input

All user input will be available to your endpoint via the `$input` variable. This variable will hold all variables that are sent with the request (GET, POST, Body Payload).

Note that the body payload has the highest precedence, followed by POST and then GET.

Take this example:

```html
<script>
  fetch('/ajax/foo?foo=xxx', {
    method: 'POST',
    body: "foo=BLA",
  })
  .then(response => response.json())
  .then(data => console.log('Success:', data))
  .catch((error) => console.error('Error:', error));
</script>
```

`$input->foo` will be `BLA` in the endpoint, because `foo=BLA` has been sent as payload even though the endpoint `?foo=xxx` has been requested!

You can also send payload as JSON:

```html
<script>
  fetch('/ajax/foo', {
    method: 'POST',
    body: JSON.stringify({ foo: 'BLA' }),
  })
  .then(response => response.json())
  .then(data => console.log('Success:', data))
  .catch((error) => console.error('Error:', error));
</script>
```

### Sanitizing Input

Always remember that `$input` holds the user's raw input, so you should always sanitize it before using it. For example:

```php
<?php

$name = wire()->sanitizer->pageName($input->name);

return ['name' => $name];
```

### Access Control

Always make sure to check if the user is authorized to access the endpoint. For example:

```php
<?php

if (!wire()->user->isSuperuser()) {
  return ['error' => 'Unauthorized'];
}

return ['foo' => 'bar'];
```

## Debugging Endpoints (for Superusers)

<div class='uk-alert'>Visit /ajax/your-endpoint-name in the browser</div>

When logged in as a superuser, RockFrontend provides a straightforward UI that shows all the important information for debugging your endpoints:

<img src=ajax.png class=blur>

RockFrontend will automatically reload the page when the endpoint file is changed. If TracyDebugger is installed you can also use `bd()` in your endpoint to dump data.

## Adding Endpoints to 3rd party modules

You can use the AJAX feature from other modules as well. An example is RockCommerce that serves ajax endpoints via RockFrontend. All you have to do is to add the AJAX folder to rockfrontend in the module's `init()` method:

```php
rockfrontend()->addAjaxFolder('rockcommerce', __DIR__ . '/ajax');
```

This will expose all files in the folder "ajax" under the url `/rockcommerce/` so the file `cart/add.php` will be accessible via `/rockcommerce/cart/add`.
