<?php namespace ProcessWire;
/**
 * @author Bernhard Baumrock, 05.01.2022
 * @license COMMERCIAL DO NOT DISTRIBUTE
 * @link https://www.baumrock.com
 *
 * @method string render($filename, array $vars = array(), array $options = array())
 */
class RockFrontend extends WireData implements Module {

  const tags = "RockFrontend";
  const prefix = "rockfrontend_";
  const tagsUrl = "/rockfrontend-layout-suggestions/{q}";

  const field_layout = self::prefix."layout";

  /** @var WireArray $folders */
  public $folders;

  /** @var WireArray $layoutFolders */
  public $layoutFolders;

  public static function getModuleInfo() {
    return [
      'title' => 'RockFrontend',
      'version' => '0.0.13',
      'summary' => 'Module for easy frontend development',
      'autoload' => true,
      'singular' => true,
      'icon' => 'code',
      'requires' => [
        // The module will work without RockMigrations but you will have to create
        // the layout field manually and add it to templates if you want to use it
      ],
      'installs' => [],
    ];
  }

  public function init() {
    $this->path = $this->wire->config->paths($this);
    $this->wire('rockfrontend', $this);

    // watch this file and run "migrate" on change or refresh
    if($rm = $this->rm()) $rm->watch($this, 0.01);

    // setup folders that are scanned for files
    $this->folders = $this->wire(new WireArray());
    $this->folders->add($this->config->paths->templates);
    $this->folders->add($this->config->paths->assets);
    $this->folders->add($this->config->paths->root);

    // layout folders
    $this->layoutFolders = $this->wire(new WireArray());
    $this->layoutFolders->add($this->config->paths->templates);
    $this->layoutFolders->add($this->config->paths->assets);

    // hooks
    $this->addHookAfter("ProcessPageEdit::buildForm", $this, "hideLayoutField");
    $this->addHook(self::tagsUrl, $this, "layoutSuggestions");
  }

  /**
   * Find files to suggest
   * @return array
   */
  public function ___findSuggestFiles($q) {
    $suggestions = [];
    foreach($this->layoutFolders as $dir) {
      // find all files to add
      $files = $this->wire->files->find($dir, [
        'extensions' => ['php'],
        'excludeDirNames' => [
          'cache',
        ],
      ]);

      // modify file paths
      $files = array_map(function($item) use($dir) {
        // strip path from file
        $str = str_replace($dir, "", $item);
        // strip php file extension
        return substr($str, 0, -4);
      }, $files);

      // only use files from within subfolders of the specified directory
      $files = array_filter($files, function($str) use($q) {
        if(!strpos($str, "/")) return false;
        return !(strpos($str, $q)<0);
      });

      // merge files into final array
      $suggestions = array_merge(
        $suggestions,
        $files
      );
    }
    // bd($suggestions);
    return $suggestions;
  }

  /**
   * Get file path of file
   *
   * You can look for files in folders like this:
   * $rf->getFile("mockups/demo.png");
   *
   * If path is relative we look in $this->folders for matching files
   *
   * @return string
   */
  public function getFile($file) {
    $file = Paths::normalizeSeparators($file);

    // we always add a slash to the file
    // this is to ensure that relative paths are not found by is_file() below
    $file = "/".ltrim($file, "/");

    // add php extension if file has no extension
    if(!pathinfo($file, PATHINFO_EXTENSION)) $file .= ".php";

    // if file exists return it
    // this will also find files relative to /site/templates!
    // TODO maybe prevent loading of relative paths outside assets?
    if(is_file($file)) return $file;

    // look for the file specified folders
    foreach($this->folders as $folder) {
      $folder = Paths::normalizeSeparators($folder);
      $folder = rtrim($folder,"/")."/";
      $path = $folder.ltrim($file,"/");
      if(is_file($path)) return $path;
    }

    // no file, return false
    return false;
  }

  /**
   * Get layout from page field
   * @return array|false
   */
  public function getLayout($page) {
    $layout = $page->get(self::field_layout);
    if(!$layout) return false;
    return explode(" ", $layout);
  }

  /**
   * Hide layout field for non-superusers
   * @return void
   */
  public function hideLayoutField(HookEvent $event) {
    if($this->wire->user->isSuperuser()) return;
    $form = $event->return;
    $form->remove(self::field_layout);
  }

  /**
   * Return an image tag for the given file
   * @return string
   */
  public function img($file) {
    $url = $this->url($file);
    if($url) return "<img src='$url'>";
    return '';
  }

  /**
   * Return layout suggestions
   */
  public function layoutSuggestions(HookEvent $event) {
    return $this->findSuggestFiles($event->q);
  }

  public function migrate() {
    $rm = $this->rm();
    $rm->migrate([
      'fields' => [
        self::field_layout => [
          'type' => 'text',
          'tags' => self::tags,
          'label' => 'Layout',
          'icon' => 'cubes',
          'collapsed' => Inputfield::collapsedYes,
          'notes' => 'This field is only visible to superusers',
          'inputfieldClass' => 'InputfieldTextTags',
          'allowUserTags' => false,
          'useAjax' => true,
          'tagsUrl' => self::tagsUrl,
          'closeAfterSelect' => 0, // dont use false
          'flags' => Field::flagSystem,
        ],
      ],
    ]);
    foreach($this->wire->templates as $tpl) {
      if($tpl->flags) continue;
      $rm->addFieldToTemplate(self::field_layout, $tpl);
    }
  }

  /**
   * Render file
   *
   * If path is provided as array then the first path that returns
   * some output will be used. This makes it possible to define a fallback
   * for rendering: echo $rf->render(["$template.php", "basic-page.php"]);
   *
   * Usage with selectors:
   * echo $rf->render([
   *  'id=1' => 'layouts/home',
   *  'template=foo|bar' => 'layouts/foobar',
   *  'layouts/default', // default layout (fallback)
   * ]);
   *
   * @param string|array $path
   * @param array $vars
   * @param array $options
   * @return string
   */
  public function ___render($path, $vars = null, $options = []) {
    $page = $this->wire->page;
    if(!$vars) $vars = [];

    // we add the $rf variable to all files that are rendered via RockFrontend
    $vars = array_merge($vars, ['rf'=>$this]);

    // options
    $opt = $this->wire(new WireData()); /** @var WireData $opt */
    $opt->setArray([
      'allowedPaths' => $this->folders,
    ]);
    $opt->setArray($options);

    // if path is an array render the first matching output
    if(is_array($path)) {
      foreach($path as $k=>$v) {
        // if the key is a string, it is a selector
        // if the selector does not match we do NOT try to render this layout
        if(is_string($k) AND !$page->matches($k)) continue;

        // no selector, or matching selector
        // try to render this layout/file
        // if no output we try the next one
        // if file returns FALSE we exit here
        $out = $this->render($v, $vars);
        if($out OR $out === false) return $out;
      }
      return; // no output found in any file of the array
    }

    // path is a string, render file
    $file = $this->getFile($path);
    if(!$file) return;

    $options = $opt->getArray();
    return $this->wire->files->render($file, $vars, $options);
  }

  /**
   * Render layout of given page
   *
   * Usage:
   * $rf->renderLayout($page);
   *
   * With custom options:
   * $rf->renderLayout($page, [
   *   'id=123' => 'layout/for/123page',
   *   'id=456' => 'layout/for/456page',
   * ]);
   *
   * Custom
   * @return string
   */
  public function renderLayout(Page $page, $fallback = [], $noMerge = false) {
    $defaultFallback = [
      "layouts/{$page->template}",
      "layouts/default",
    ];

    // by default we will merge the default array with the array
    // provided by the user
    if(!$noMerge) $fallback = $fallback + $defaultFallback;

    // bd($fallback);

    // try to find layout from layout field of the page editor
    $layout = $this->getLayout($page);
    if($layout) return $this->render($layout);
    return $this->render($fallback);
  }

  /**
   * @return RockMigrations
   */
  public function rm() {
    return $this->wire->modules->get('RockMigrations');
  }

  /**
   * Return script-tag
   * @return string
   */
  public function scriptTag($path, $cacheBuster = false) {
    $src = $this->url($path, $cacheBuster);
    return "<script type='text/javascript' src='$src'></script>";
  }

  /**
   * Return style-tag
   * @return string
   */
  public function styleTag($path, $cacheBuster = false) {
    $href = $this->url($path, $cacheBuster);
    return "<link href='$href' rel='stylesheet'>";
  }

  /**
   * Given a path return the url relative to pw root
   *
   * If second parameter is true we add ?m=filemtime for cache busting
   *
   * @return string
   */
  public function url($path, $cacheBuster = false) {
    $path = $this->getFile($path);
    $config = $this->wire->config;
    $m = (is_file($path) AND $cacheBuster) ? "?m=".filemtime($path) : '';
    return str_replace($config->paths->root, $config->urls->root, $path.$m);
  }

  public function ___install() {
    $this->init();
    $this->migrate();
  }

}
