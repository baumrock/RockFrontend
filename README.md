# RockFrontend

## Adding folders to scan for frontend files

By default RockFrontend scans the folders `/site/assets` and `/site/templates` for files that you want to render via `$rf->render("layouts/foo")`.

If you want to add another directory to scan you can add it to the `folders` property of RockFrontend:

```php
// in site/ready.php
$rockfrontend->folders->add('/site/templates/my-template/');
```
