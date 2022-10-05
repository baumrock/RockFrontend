<?php

namespace RockFrontend;

use Exception;
use ProcessWire\Page;
use ProcessWire\Pageimage;
use ProcessWire\Pageimages;
use ProcessWire\Paths;
use ProcessWire\RockFrontend;
use ProcessWire\Wire;
use ProcessWire\WireData;
use ProcessWire\WireHttp;

class Seo extends Wire
{

  /** @var array */
  protected $rawValues;

  /** @var WireData */
  protected $strValues;

  /** @var WireData */
  protected $tags;

  /** @var WireData */
  protected $values;

  public function __construct()
  {
    $this->tags = new WireData();
    $this->values = new WireData();
    $this->rawValues = [];
    $this->strValues = new WireData();
    $this->setupDefaults();
  }

  /** ##### public API ##### */

  /**
   * Shortcut to set description and og:description at once
   */
  public function description($value): self
  {
    $this->setValue(['description', 'og:description'], $value);
    return $this;
  }

  /**
   * Get markup for given tag
   */
  public function getMarkup($tag): string
  {
    return $this->tags->get($tag) ?: '';
  }

  /**
   * Get the raw value of a placeholder in a tag
   * eg returns a Pageimage for og:image {value}
   * $seo->getRaw('og:image', 'value');
   * Note that the 2nd param optional since "value" is the default!
   * @return mixed
   */
  public function getRaw($tag, $key = "value")
  {
    $k = "$tag||$key";
    if (array_key_exists($k, $this->rawValues)) return $this->rawValues[$k];

    $values = $this->getValuesData($tag);
    $raw = $values->get($key);

    if (is_callable($raw) and !is_string($raw)) {
      $raw = $raw->__invoke($this->wire->page);
    }

    // save to cache
    $this->rawValues[$k] = $raw;
    return $raw;
  }

  /**
   * Get values array of given tag
   */
  public function getValues($tag): array
  {
    $values = $this->values->get($tag);
    if (is_array($values)) return $values;
    return [];
  }

  /**
   * Get or set markup of a tag
   *
   * Use as getter:
   * echo $seo->markup('title');
   *
   * Use as setter:
   * $seo->markup('title', '<title>{value} - My Company</title>');
   *
   * @return self
   */
  public function markup($tag, $value = null): self
  {
    // no value --> act as getter
    if ($value === null) {
      $this->tags->get($tag);
      return $this;
    }

    // value --> act as setter
    return $this->setMarkup($tag, $value);
  }

  public function render(): string
  {
    $out = '';
    foreach ($this->tags as $name => $tag) {
      $markup = $this->renderTag($name);
      if ($markup) $out .= "$markup\n  ";
    }
    return $out;
  }

  /**
   * Render given tag and populate placeholders
   */
  public function renderTag($tag): string
  {
    $out = '';

    // get markup and values
    $markup = $out = $this->getMarkup($tag);

    // populate placeholders
    $hasValue = false;
    preg_replace_callback(
      "/{(.*?)(:(.*))?}/",
      function ($matches) use (&$out, $tag, &$hasValue) {
        $key = $matches[1];
        $filter = array_key_exists(3, $matches) ? $matches[3] : false;
        $search = $filter ? "{{$key}:{$filter}}" : "{{$key}}";

        // get raw value for given key
        $value = $this->getStringValue($tag, $key);
        if ($value) $hasValue = true;

        // get truncated tag
        if ($filter) {
          // if filter is a number it is a truncate length
          $trunc = (int)$filter;
          if ($trunc) $value = $this->truncate($value, $filter, $tag);
        }

        // sanitize value
        $value = (string)$value;
        if ($filter != 'noescape') {
          $value = $this->wire->sanitizer->entities($value);
        }

        $out = str_replace($search, $value, $out);
      },
      $markup
    );

    if ($hasValue) return $out;
    return '';
  }

  /**
   * Set a tags markup
   */
  public function setMarkup($tag, $markup): self
  {
    $this->tags->set($tag, $markup);
    return $this;
  }

  /**
   * Set the value for the {value} tag
   *
   * This is a shortcut for using setValues()
   *
   * Usage:
   * $seo->setValue('title', $page->title);
   *
   * $seo->setValue('title', function($page) {
   *   if($page->template == 'foo') return $page->foo;
   *   elseif($page->template == 'bar') return $page->bar;
   *   return $page->title;
   * });
   */
  public function setValue($tag, $value): self
  {
    if (is_array($tag)) {
      foreach ($tag as $t) $this->setValue($t, $value);
      return $this;
    }
    return $this->setValues($tag, ['value' => $value]);
  }

  /**
   * Set values for a tag
   *
   * Usage:
   * $seo->setValues('title', [
   *   'val' => $page->title,
   * ]);
   */
  public function setValues($tag, array $values): self
  {
    $values = array_merge($this->getValues($tag), $values);
    $this->values->set($tag, $values);
    return $this;
  }

  /**
   * Shortcut for setting both title and og:title
   */
  public function title($value): self
  {
    $this->setValue(['title', 'og:title'], $value);
    return $this;
  }

  /** ##### end public API ##### */

  /** ##### hookable methods ##### */

  /**
   * Given a pageimage return the full http url for usage in markup
   * The second parameter can be used to modify behaviour via hook.
   */
  public function ___getImageUrl($image, $tag): string
  {
    if (!$image) return '';
    if ($image instanceof Pageimages) $image = $image->first();
    return (string)$this->imageInfo($image, $tag)->url;
  }

  /**
   * Return the scaled image
   * @return mixed
   */
  public function ___getImageScaled(Pageimage $image, $tag)
  {
    $opt = ['upscaling' => true];
    return $image->size(1200, 630, $opt);
  }

  /**
   * Get the non-truncated string value for given tag and key
   * eg get key "value" for tag "title"
   */
  public function ___getStringValue($tag, $key = 'value'): string
  {
    $val = $this->strValues->get("$tag||$key");
    if (is_string($val)) return $val;

    // get raw value
    $value = $this->getRaw($tag, $key);

    // convert to string
    if ($tag == 'og:image') $value = $this->getImageUrl($value, $tag);

    // create final string that will be returned and stored in cache
    $str = $value ?: '';

    // save to cache
    $this->strValues->set("$tag||$key", $str);

    return $str;
  }

  /**
   * Set default tags
   * Recommendations from https://moz.com/learn/seo
   * https://neilpatel.com/blog/open-graph-meta-tags
   */
  public function ___setupDefaults()
  {
    // branding
    // you can remove the branding by adding ->setMarkup('branding', '')
    // please consider donating if you do so and the module helps you
    // https://github.com/sponsors/baumrock/ THANK YOU
    $this->setMarkup('branding', "\n  <!-- RockFrontend SEO by baumrock.com -->");

    // title
    $this->setMarkup('title', '<title>{value:60}</title>');
    $this->setMarkup('og:title', '<meta property="og:title" content="{value:95}">');
    $this->setValue(['title', 'og:title'], function ($page) {
      return $page->title;
    });

    // description
    $this->setMarkup('description', '<meta name="description" content="{value:160}">');
    $this->setValue('description', function ($page) {
      return $page->get("body|title");
    });
    $this->setMarkup('og:description', '<meta property="og:description" content="{value:160}">');
    $this->setValue('og:description', function ($page) {
      return $this->getRaw('description');
    });

    // og:image
    $this->setMarkup('og:image', '<meta property="og:image" content="{value}">');
    $this->setValue('og:image', function (Page $page) {
      try {
        return $this->wire->pages->get(1)->get(RockFrontend::field_ogimage);
      } catch (\Throwable $th) {
      }
      try {
        return $this->wire->pages->get(1)->images->first();
      } catch (\Throwable $th) {
      }
    });
    $this->setMarkup('og:image:type', '<meta property="og:image:type" content="{value}">');
    $this->setValue('og:image:type', function () {
      $img = $this->getRaw('og:image');
      return $this->imageInfo($img, 'og:image')->mime;
    });

    $this->setMarkup('og:image:width', '<meta property="og:image:width" content="{value}">');
    $this->setValue('og:image:width', function () {
      $img = $this->getRaw('og:image');
      return $this->imageInfo($img, 'og:image')->width;
    });
    $this->setMarkup('og:image:height', '<meta property="og:image:height" content="{value}">');
    $this->setValue('og:image:height', function () {
      $img = $this->getRaw('og:image');
      return $this->imageInfo($img, 'og:image')->height;
    });
    $this->setMarkup('og:image:alt', '<meta property="og:image:alt" content="{value:95}">');
    $this->setValue('og:image:alt', function () {
      if (!$this->getRaw('og:image')) return;
      return $this->getRaw('title');
    });

    // favicon
    // will be added automatically when uploaded to the favicon field
    // the PNG icons will be added to webmanifest
    $this->setMarkup('favicon', '{value:noescape}');
    $this->setValue('favicon', function () {
      // get favicon PNG and create all sizes
      $png = $this->wire->pages->get(1)->getFormatted(RockFrontend::field_favicon);
      if (!$png) return false;

      // create markup
      $opt = ['sharpening' => 'none']; // https://bit.ly/3qylo1S
      $n = "\n  ";
      return
        // all browsers
        "<link rel='icon' type='image/png' sizes='32x32' href={$png->size(32, 32,$opt)->url}>"
        . "$n<link rel='icon' type='image/png' sizes='16x16' href={$png->size(16, 16,$opt)->url}>"
        // google and android
        . "$n<link rel='icon' type='image/png' sizes='48x48' href={$png->size(48, 48,$opt)->url}>"
        . "$n<link rel='icon' type='image/png' sizes='192x192' href={$png->size(192, 192,$opt)->url}>"
        // apple iphone and ipad
        . "$n<link rel='apple-touch-icon' type='image/png' sizes='167x167' href={$png->size(167, 167,$opt)->url}>"
        . "$n<link rel='apple-touch-icon' type='image/png' sizes='180x180' href={$png->size(180, 180,$opt)->url}>";
    });

    // webmanifest
    $this->setMarkup('manifest', '<link rel="manifest" href="{value}">');
    $this->setValue('manifest', function () {
      /** @var RockFrontend $rf */
      $rf = $this->wire->modules->get('RockFrontend');
      $manifest = $rf->manifest();
      return $manifest->url();
    });
    $this->setMarkup('theme-color', '<meta name="theme-color" content="{value}">');
    $this->setValue('theme-color', function () {
      /** @var RockFrontend $rf */
      $rf = $this->wire->modules->get('RockFrontend');
      return $rf->manifest()->themeColor;
    });
  }

  /**
   * Return truncated value
   */
  public function ___truncate($value, $length, $tag): string
  {
    return $this->wire->sanitizer->getTextTools()
      ->truncate((string)$value, [
        'type' => 'word',
        'maximize' => true,
        'maxLength' => $length,
        'visible' => true,
        'more' => false,
        'collapseLinesWith' => '; ',
      ]);
  }

  /** ##### end hookable methods ##### */

  /** ##### internal methods ##### */

  /**
   * Returns a WireData object instead of a plain php array
   * That ensures that requesting non-existing properties does not throw
   * an error.
   */
  protected function getValuesData($tag): WireData
  {
    $values = new WireData();
    $values->setArray($this->getValues($tag));
    return $values;
  }

  /**
   * Get image info of string or Pageimage
   */
  protected function imageInfo($img, $tag, $scale = true): WireData
  {
    $info = new WireData();

    if ($img instanceof Pageimages) $img = $img->first();
    if ($img instanceof Pageimage) {
      if ($scale) $img = $this->getImageScaled($img, $tag);
      $path = Paths::normalizeSeparators($img->filename);
      $info->setArray([
        'path' => $path,
        'url' => str_replace(
          $this->wire->config->paths->root,
          $this->wire->pages->get(1)->httpUrl(),
          $path
        ),
        'width' => $img->width,
        'height' => $img->height,
        'mime' => mime_content_type($img->filename),
      ]);
    } elseif (is_string($img)) {
      // image is a string
      // that means it is a relative string like /site/templates/img/foo.jpg
      $filename = Paths::normalizeSeparators($img);
      $filename = ltrim($filename, "/");
      $filename = $this->wire->config->paths->root . $filename;
      if (is_file($filename)) {
        $size = getimagesize($filename);
        $info->setArray([
          'path' => $filename,
          'url' => str_replace(
            $this->wire->config->paths->root,
            $this->wire->pages->get(1)->httpUrl(),
            $filename
          ),
          'width' => $size[0],
          'height' => $size[1],
          'mime' => $size['mime'],
        ]);
      }
    }

    // bd($info->getArray(), 'info');
    return $info;
  }

  /**
   * @return RockFrontend
   */
  public function rockfrontend()
  {
    return $this->wire->modules->get('RockFrontend');
  }

  /** ##### end internal methods ##### */

  /** ##### magic methods ##### */

  public function __debugInfo()
  {
    return [
      'tags' => $this->tags->getArray(),
      'values' => $this->values->getArray(),
      'rawValues (cache)' => $this->rawValues,
      'strValues (cache)' => $this->strValues->getArray(),
      'render()' => $this->render(),
    ];
  }

  public function __toString()
  {
    return $this->render();
  }
}
