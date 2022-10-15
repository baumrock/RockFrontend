<?php

namespace RockFrontend;

use ProcessWire\Wire;

class Manifest extends Wire
{

  // see https://developer.mozilla.org/en-US/docs/Web/Manifest/display
  const display_fullscreen = 'fullscreen';
  const display_standalone = 'standalone';
  const display_minimal_ui = 'minimal-ui';
  const display_browser = 'browser';

  public $bgColor;
  public $created;
  public $display = self::display_standalone;
  public $filename = 'website.webmanifest';
  public $name;
  public $shortName;
  public $themeColor;

  /**
   * @return self
   */
  public function backgroundColor($color)
  {
    return $this->bgColor($color);
  }

  /**
   * @return self
   */
  public function bgColor($color)
  {
    $this->bgColor = $color;
    return $this;
  }

  /**
   * Create manifest when a PW page is saved
   *
   * By default this will create the manifest if the root page is saved.
   * You can adjust that by providing a selector that the saved page has to
   * match. You can also set a condition, for example to create the manifest
   * only if debug mode is ON:
   *
   * // put this in /site/init.php and echo $rockfrontend->seo() in your markup
   * $rockfrontend->manifest()
   *   ->themeColor(...)
   *   ->createOnSave("template=home", $config->debug);
   *
   * @return self
   */
  public function createOnSave($selector = 'id=1', $condition = true)
  {
    if (!$condition) return;
    $this->wire->addHookAfter("Pages::saved", function ($event) use ($selector) {
      $page = $event->arguments(0);
      if (!$page->matches($selector)) return;
      $this->saveToFile();
    });
    return $this;
  }

  /**
   * Set display value
   * @return self
   */
  public function display($val)
  {
    $this->display = $val;
    return $this;
  }

  /**
   * Get filepath of manifest file
   * @return string
   */
  public function filepath()
  {
    return $this->wire->config->paths->root . $this->filename;
  }

  /**
   * Array used for debugInfo and render() json
   */
  public function getArray()
  {
    return [
      'name' => $this->name,
      'short_name' => $this->shortname ?: $this->name,
      'background_color' => $this->bgColor,
      'theme_color' => $this->themeColor,
      'display' => $this->display,
      'filename' => $this->filename,
    ];
  }

  /**
   * @return self
   */
  public function name($name)
  {
    $this->name = $name;
    return $this;
  }

  public function render($merge = [])
  {
    $arr = array_merge($this->getArray(), $merge);
    unset($arr['filepath']);
    unset($arr['filename']);
    // this prevents null value warnings in the console
    foreach ($arr as $k => $v) if ($v === null) $arr[$k] = '';
    $str = json_encode($arr);
    $str = trim($str, "{}");
    $str = str_replace(',"', ",\n  \"", $str);
    return "{\n  $str\n}";
  }

  /**
   * Save manifest to file
   * @return self
   */
  public function saveToFile($filepath = null)
  {
    if (!$filepath) $filepath = $this->filepath();
    $this->wire->files->filePutContents($filepath, $this->render());
    return $this;
  }

  /**
   * @return self
   */
  public function shortName($name)
  {
    $this->shortName = $name;
    return $this;
  }

  /**
   * @return self
   */
  public function themeColor($color)
  {
    $this->themeColor = $color;
    return $this;
  }

  /**
   * Get url of manifest file
   * This will create the manifest file if it does not exist
   */
  public function url()
  {
    $path = $this->filepath();
    if (!is_file($path)) $this->saveToFile();
    return str_replace(
      $this->wire->config->paths->root,
      $this->wire->config->urls->root,
      $path
    );
  }

  public function __debugInfo()
  {
    return array_merge($this->getArray(), [
      'filepath' => $this->filePath(),
    ]);
  }

  public function __toString()
  {
    return $this->render();
  }
}
