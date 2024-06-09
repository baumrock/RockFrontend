# AJAX/HTMX Endpoints

RockFrontend ❤️ HTMX, and the latest version significantly simplifies the process of adding endpoints to your HTMX powered websites.

<div class='uk-alert uk-alert-warning'>Always make sure to properly sanitize user input and also make sure to protect your endpoints from unauthorized access!</div>

To create a new endpoint, all you need to do is place a PHP file within the `/site/templates/ajax/` directory. For example, adding a file named `foo.php` in this directory automatically generates an endpoint accessible via `/ajax/foo`. This endpoint can then be seamlessly utilized with HTMX or the `fetch()` method in JavaScript.

Instead of cluttering your template or module code with `if($config->ajax) {...}`, you can encapsulate AJAX-specific logic within dedicated files in the `/site/templates/ajax/` directory. This approach not only simplifies the code but also enhances maintainability by segregating AJAX logic from the rest of your application logic.

## Example Endpoint

A minimal endpoint can look like this:

```php
<?php
return ['foo', 'bar'];
```

As you can see - to further simplify the process - if your endpoint returns an array, RockFrontend automatically formats the response as JSON. This feature allows you to focus on your data logic without worrying about the response format.

## Debugging Endpoints for Superusers

<div class='uk-alert'>Visit /ajax/your-endpoint-name in the browser</div>

When logged in as a superuser, RockFrontend provides a straightforward UI for debugging your endpoints. This feature is particularly useful for quickly identifying and resolving issues, ensuring your AJAX endpoints function as expected:

<img src=ajax.png class=blur>

## Adding Endpoints to 3rd party modules

You can use the AJAX feature from other modules. An example is RockCommerce that serves ajax endpoints via RockFrontend. All you have to do is to add the AJAX folder to rockfrontend in the module's `init()` method:

```php
rockfrontend()->addAjaxFolder('rc-ajax', __DIR__ . '/ajax');
```
