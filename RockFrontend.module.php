<?php namespace ProcessWire;

use Latte\Engine;
use RockFrontend\ScriptsArray;
use RockFrontend\StylesArray;
use RockMatrix\Block;

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
  const permission_alfred = "rockfrontend-alfred";

  const field_layout = self::prefix."layout";

  /** @var WireArray $folders */
  public $folders;

  public $home;

  /** @var bool */
  private $hasAlfred = false;

  /** @var Engine */
  private $latte;

  /** @var WireArray $layoutFolders */
  public $layoutFolders;

  /** @var string */
  public $path;

  private $scripts;
  private $styles;

  public static function getModuleInfo() {
    return [
      'title' => 'RockFrontend',
      'version' => '1.2.0',
      'summary' => 'Module for easy frontend development',
      'autoload' => true,
      'singular' => true,
      'icon' => 'code',
      'requires' => [
        // The module will work without RockMigrations but you will have to create
        // the layout field manually and add it to templates if you want to use it
      ],
      'installs' => [
        'PageFrontEdit',
      ],
    ];
  }

  public function init() {
    $this->path = $this->wire->config->paths($this);
    $this->home = $this->wire->pages->get(1);
    $this->wire('rockfrontend', $this);

    // watch this file and run "migrate" on change or refresh
    if($rm = $this->rm()) $rm->watch($this, 0.01);

    // copy stubs files
    $this->copyStubs();

    // setup folders that are scanned for files
    $this->folders = $this->wire(new WireArray());
    $this->folders->add($this->config->paths->templates);
    $this->folders->add($this->config->paths->assets);
    $this->folders->add($this->config->paths->root);

    // layout folders
    $this->layoutFolders = $this->wire(new WireArray());
    $this->layoutFolders->add($this->config->paths->templates);
    $this->layoutFolders->add($this->config->paths->assets);

    // Alfred
    $this->createPermission(self::permission_alfred,
      "Is allowed to use ALFRED frontend editing");
    $this->createCSS();
    if($this->wire->user->isSuperuser() OR $this->wire->user->hasPermission(self::permission_alfred)) {
      $this->scripts('head')->add($this->path."Alfred.js");
      $this->styles('head')->add($this->path."Alfred.css");
    }

    // hooks
    $this->addHookAfter("ProcessPageEdit::buildForm", $this, "hideLayoutField");
    $this->addHookAfter("Page::render", $this, "addEditTag");
    $this->addHook(self::tagsUrl, $this, "layoutSuggestions");
  }

  /**
   * Add a fake edit tag to the page so that PageFrontEdit loads all assets
   */
  public function addEditTag(HookEvent $event) {
    if(!$this->hasAlfred) return;
    $html = $event->return;
    $faketag = "<div edit=title hidden>title</div>";
    $html = str_replace("</body", "$faketag</body", $html);
    $event->return = $html;
  }

  /**
   * Copy skeleton files to /site/templates
   * @return void
   */
  public function copyStubs() {
    $path = $this->wire->config->paths->templates;
    if(is_dir($path."layouts")) return;
    if(is_dir($path."sections")) return;
    $this->wire->files->copy($this->path."stubs", $path);
  }

  /**
   * Create CSS from LESS file
   * @return void
   */
  public function createCSS() {
    if(!$this->wire->user->isSuperuser()) return;
    $css = $this->path."Alfred.css";
    $lessFile = $this->path."Alfred.less";
    if(filemtime($css) > filemtime($lessFile)) return;
    if(!$less = $this->wire->modules->get("Less")) return;
    /** @var Less $less */
    $less->addFile($lessFile);
    $less->saveCSS($css);
    $this->message("Created $css from $lessFile");
  }

  /**
   * Create permission
   * @return void
   */
  public function createPermission($name, $title) {
    $p = $this->wire->permissions->get($name);
    if($p AND $p->id) return;
    $p = $this->wire->permissions->add($name);
    $p->setAndSave('title', $title);
  }

  /**
   * ALFRED - A Lovely FRontend EDitor
   * @return string
   */
  public function alfred($page = null, $options = []) {
    if(!$this->wire->user->isLoggedin()) return;
    // set flag to show that at least one alfred tag is on the page
    // this flag is used to load the PW frontend editing assets
    $this->hasAlfred = true;

    // setup options
    $opt = $this->wire(new WireData()); /** @var WireData $opt */
    $opt->setArray([
      'addTop' => false,
      'addBottom' => false,
      'trash' => true, // will set the trash icon for rockmatrix blocks
    ]);
    $opt->setArray($options);

    // icons
    $icons = [];
    if($page AND $page->editable()) {
      $icons[] = (object)[
        'icon' => 'edit',
        'label' => $page->title,
        'tooltip' => "Edit Block #{$page->id}",
        'href' => $page->editUrl(),
        'class' => 'pw-modal',
        'suffix' => 'data-buttons="button.ui-button[type=submit]" data-autoclose data-reload',
      ];
    }
    if($opt->trash AND $page AND $page instanceof Block AND $page->trashable()) {
      $icons[] = (object)[
        'icon' => 'trash-2',
        'label' => $page->title,
        'tooltip' => "Trash Block #{$page->id}",
        'href' => $page->rmxUrl("/trash/?block=$page"),
        'confirm' => 'Are you sure?',
      ];
    }

    if($this->wire->user->isSuperuser()) {
      $path = $this->getTplPath();
      $tracy = $this->wire->config->tracy;
      if(is_array($tracy) and array_key_exists('localRootPath', $tracy))
        $root = $tracy['localRootPath'];
      else $root = $this->wire->config->paths->root;
      $link = str_replace($this->wire->config->paths->root, $root, $path);

      // file edit link
      $icons[] = (object)[
        'icon' => 'code',
        'label' => $path,
        'href' => "vscode://file/$link",
        'tooltip' => $link,
      ];
      // style edit link
      $less = substr($path, 0, -4).".less";
      if(is_file($less)) {
        $icons[] = (object)[
          'icon' => 'eye',
          'label' => $less,
          'href' => "vscode://file/$less",
          'tooltip' => $less,
        ];
      }
    }
    if(!count($icons)) return;

    // setup links for add buttons
    if($page instanceof Block) {
      $opt->addTop = $page->rmxUrl("/add/?block=$page&above=1");
      $opt->addBottom = $page->rmxUrl("/add/?block=$page");
    }

    $str = json_encode((object)[
      'icons' => $icons,
      'addTop' => $opt->addTop,
      'addBottom' => $opt->addBottom,
    ]);
    return " alfred='$str'";
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
  public function getFile($file, $forcePath = false) {
    if(strpos($file, "//") === 0) return $file;
    if(strpos($file, "https://") === 0) return $file;
    if(strpos($file, "https://") === 0) return $file;
    $file = Paths::normalizeSeparators($file);

    // we always add a slash to the file
    // this is to ensure that relative paths are not found by is_file() below
    $file = "/".ltrim($file, "/");

    // if no extension was provided try php or latte extension
    if(!pathinfo($file, PATHINFO_EXTENSION)) {
      if($f = $this->getFile("$file.php", $forcePath)) return $f;
      if($f = $this->getFile("$file.latte", $forcePath)) return $f;
    }

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

    // no file found
    // if force path is set true we return the path nonetheless
    // this should help on frontend development to get a 404 when using wrong paths
    if($forcePath) return $file;

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
   * Find path in rockfrontend folders
   * Returns path with trailing slash
   * @return string|false
   */
  public function getPath($path, $forcePath = false) {
    $path = Paths::normalizeSeparators($path);

    // if the path is already absolute and exists we return it
    // we dont return relative paths!
    if(strpos($path, '/')===0 AND is_dir($path)) return rtrim($path,'/').'/';

    foreach($this->folders as $f) {
      $dir = $f.ltrim($path, '/');
      if(is_dir($dir)) return rtrim($dir, '/').'/';
    }

    if($forcePath) return rtrim($path,'/').'/';

    return false;
  }

  /**
   * Find template file from trace
   * @return string
   */
  public function getTplPath() {
    $trace = debug_backtrace();
    $paths = $this->wire->config->paths;
    foreach($trace as $step) {
      $file = $step['file'];
      $skip = [
        $paths->cache,
        $paths($this),
        $paths->root."vendor/"
      ];
      foreach($skip as $p) {
        if(strpos($file, $p)===0) $skip = true;
      }

      // special case: rockmatrix block
      if($file === $paths->siteModules."RockMatrix/Block.php") {
        // return the block view file instead of the block controller
        return $step['args'][0];
      }
      elseif($file === $paths->siteModules."RockFrontend/RockFrontend.module.php"
        AND count($step['args'])) {
        return $step['args'][0];
      }

      // try next entry or return file
      if($skip === true) continue;
      else return $file;
    }
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
    $vars = array_merge($this->wire('all')->getArray(), $vars, ['rf'=>$this]);

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

    if(pathinfo($file, PATHINFO_EXTENSION) === 'latte') {
      $latte = $this->latte;
      if(!$latte) {
        try {
          $loader = $this->wire->config->paths->root."vendor/autoload.php";
          if(!is_file($loader)) {
            throw new WireException("You need to install latte to render latte files!
              Use >> composer require latte/latte << in the PW root directory");
          }
          require_once $loader;
          $latte = new Engine();
          $latte->setTempDirectory($this->wire->config->paths->cache."Latte");
          $this->latte = $latte;
        } catch (\Throwable $th) {
          return $th->getMessage();
        }
      }
      return $latte->renderToString($file, $vars);
    }
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
   * Get given ScriptsArray instance or a new one if no name is provided
   *
   * Usage:
   * $rockfrontend->scripts()->add(...)->add(...)->render();
   *
   * // file1.php
   * $rockfrontend->scripts('head')->add(...);
   * // file2.php
   * $rockfrontend->scripts('head')->add(...);
   * // _main.php
   * $rockfrontend->scripts('head')->render();
   *
   * @return ScriptsArray
   */
  public function scripts($name = null) {
    if(!$this->scripts) $this->scripts = new WireData();
    require_once($this->path."Asset.php");
    require_once($this->path."AssetsArray.php");
    require_once($this->path."ScriptsArray.php");
    $script = $this->scripts->get($name) ?: new ScriptsArray($this);
    if($name) $this->scripts->set($name, $script);
    return $script;
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
   * Get given StylesArray instance or a new one if no name is provided
   *
   * Usage:
   * $rockfrontend->styles()->add(...)->add(...)->render();
   *
   * // file1.php
   * $rockfrontend->styles('head')->add(...);
   * // file2.php
   * $rockfrontend->styles('head')->add(...);
   * // _main.php
   * $rockfrontend->styles('head')->render();
   *
   * @return StylesArray
   */
  public function styles($name = null) {
    if(!$this->styles) $this->styles = new WireData();
    require_once($this->path."Asset.php");
    require_once($this->path."AssetsArray.php");
    require_once($this->path."StylesArray.php");
    $style = $this->styles->get($name) ?: new StylesArray($name);
    if($name) $this->styles->set($name, $style);
    return $style;
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
    $path = $this->getFile($path, true);
    $config = $this->wire->config;
    $m = (is_file($path) AND $cacheBuster) ? "?m=".filemtime($path) : '';
    return str_replace($config->paths->root, $config->urls->root, $path.$m);
  }

  public function ___install() {
    $this->init();
    $this->migrate();
  }

}
