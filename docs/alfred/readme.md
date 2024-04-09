# ALFRED Frontend Editing

ALFRED stands for "<b>A</b> <b>L</b>ovely <b>FR</b>ontend <b>ED</b>itor".

It has two main goals:

- Better editing experience for your clients
- Better development experience for yourself

See the video here: https://www.youtube.com/watch?v=7CoIj--u4ps&t=1714s

<div class='uk-alert uk-alert-warning'>Note: To use ALFRED you need to install the PageFrontEdit module and make sure all users have the page-edit-front permission.</div>

## Easier Frontend Editing for Your Clients

A typical example that almost every website has are footer links - that's why RockFrontend comes with a migration that creates a footerlinks field for you. It creates that field on the `home` page and the client can manage the footerlinks by editing the `home` page in the PW backend.

Easy enough, right? Yes and no. Why would the user know that the footerlinks are defined on that page? That might make sense for a PW developer but certainly not for most of my clients.

So in reality the user journey might be more like that the user is on the frontend of their website and sees the footerlinks and realises that he/she needs to change those links.

ALFRED makes it possible to provide a simple GUI that appears on hover where the user can simply click on the edit icon (arrow 2) and see only the field that is responsible for holding all footerlinks:

<img src=footer.png class=blur>

<img src=alfred.png class=blur>

He she can then edit pages shown in the footer, click on save and the frontend will reload.

## Adding ALFRED

Adding ALFRED to your frontend is as simple as calling `alfred()` in your template file. The syntax is as follows:

```php
alfred($page, $fields)
```

In our footerlinks example this would look like this (using Latte):

```latte
<div class="uk-text-center" {alfred(1, "rockfrontend_footerlinks")}>
  <ul class="uk-breadcrumb">
    <li n:foreach="$home->footerlinks() as $link">
      <a href="{$link->url}">{$link->title}</a>
    </li>
  </ul>
</div>
```

So we make ALFRED show page 1 (the home page) and only show the `rockfrontend_footerlinks` field. If you don't provide a second parameter it will render all fields of that page.

When using regular PHP templates simply use `<?= alfred(...) ?>` instead of `{alfred(...)}`:

```php
<div
  <?= alfred($page, "your-field") ?>
>
```

## Easier Development

<img src=footer.png class=blur>

Another great thing about ALFRED is that it adds two other icons (see arrow 1):

- A code icon to jump to that file in your IDE
- An eye icon to jump to the corresponding LESS file if one exists

So in the example above the file that would open is `footer.latte` and the less file would be `footer.less`.

<div class=uk-alert>Note that this works only for VSCode</div>
