# Quickstart Guide for RockFrontend 🚀

## Installation

1. Download the RockFrontend module
2. Copy it to your ProcessWire installation under /site/modules/
3. Install the module in the ProcessWire admin

## Site Profile

Please check out the RockFrontend Site Profile here: https://processwire.com/talk/topic/29884-rockfrontend-site-profile-rockfrontend-uikit-tailwindcss/

## Basic Usage

### Latte

RockFrontend can be used to render template files written in a templating language. It supports Latte and Twig by default, but any other language can be added.

### SEO Tools

```php
// Generate sitemap
$rockfrontend->sitemap(function($page) {
  return $page->template != 'admin';
});
```

### DOM Manipulation

```php
// Modify HTML content
$html = $rockfrontend->dom($content)
  ->find('a')
  ->addClass('my-link-class')
  ->html();
```

## Need Help?

- 📖 Check the detailed documentation for several features
- 🎥 Watch the tutorial video: [RockFrontend on YouTube](https://www.youtube.com/watch?v=7CoIj--u4ps)
- 💬 Visit the [ProcessWire Forum](https://processwire.com/talk/) for community support
