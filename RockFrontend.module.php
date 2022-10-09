<?php

namespace ProcessWire;

use Latte\Engine;
use Latte\Runtime\Html;
use RockFrontend\LiveReload;
use RockFrontend\Manifest;
use RockFrontend\ScriptsArray;
use RockFrontend\Seo;
use RockFrontend\StylesArray;
use RockPageBuilder\Block;
use Sabberworm\CSS\OutputFormat;
use Sabberworm\CSS\Parser;
use Sabberworm\CSS\Rule\Rule;
use Sabberworm\CSS\RuleSet\AtRuleSet;
use Sabberworm\CSS\RuleSet\RuleSet;

/**
 * @author Bernhard Baumrock, 05.01.2022
 * @license MIT
 * @link https://www.baumrock.com
 *
 * @method string render($filename, array $vars = array(), array $options = array())
 */
class RockFrontend extends WireData implements Module, ConfigurableModule
{

  const tags = "RockFrontend";
  const prefix = "rockfrontend_";
  const tagsUrl = "/rockfrontend-layout-suggestions/{q}";
  const permission_alfred = "rockfrontend-alfred";
  const livereloadCacheName = "rockfrontend_livereload"; // also in livereload.php
  const getParam = 'rockfrontend-livereload';
  const cache = 'rockfrontend-uikit-versions';
  const installedprofilekey = 'rockfrontend-installed-profile';
  const recompile = 'rockfrontend-recompile-less';

  const webfont_agents = [
    'woff2' => 'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:40.0) Gecko/20100101 Firefox/40.0', // very modern browsers
    'woff' => 'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:27.0) Gecko/20100101 Firefox/27.0', // modern browsers
    'ttf' => 'Mozilla/5.0 (Unknown; Linux x86_64) AppleWebKit/538.1 (KHTML, like Gecko) Safari/538.1 Daum/4.1', // safari, android, ios
    'svg' => 'Mozilla/4.0 (iPad; CPU OS 4_0_1 like Mac OS X) AppleWebKit/534.46 (KHTML, like Gecko) Version/4.1 Mobile/9A405 Safari/7534.48.3', // legacy ios
    'eot' => 'Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 6.1; Trident/4.0)', // IE
  ];
  const webfont_comments = [
    'woff2' => '/* Super Modern Browsers */',
    'woff' => '/* Pretty Modern Browsers */',
    'ttf' => '/* Safari, Android, iOS */',
    'svg' => '/* Legacy iOS */',
  ];

  const field_layout = self::prefix . "layout";
  const field_favicon = self::prefix . "favicon";
  const field_ogimage = self::prefix . "ogimage";

  /** @var WireData */
  public $alfredCache;

  /** @var WireArray $folders */
  public $folders;

  public $home;

  /** @var bool */
  public $hasAlfred = false;

  /** @var array */
  protected $js = [];

  /** @var Engine */
  private $latte;

  /** @var WireArray $layoutFolders */
  public $layoutFolders;

  /** @var Manifest */
  protected $manifest;

  /** @var string */
  public $path;

  /** @var WireData */
  public $postCSS;

  /**
   * REM base value (16px)
   */
  public $remBase;

  /** @var Seo */
  public $seo;

  private $scripts;
  private $styles;

  /** @var array */
  private $translations = [];

  public static function getModuleInfo()
  {
    return [
      'title' => 'RockFrontend',
      'version' => '2.0.2',
      'summary' => 'Module for easy frontend development',
      'autoload' => true,
      'singular' => true,
      'icon' => 'paint-brush',
      'requires' => [
        'PHP>=8.0',
      ],
      // The module will work without RockMigrations but you will have to create
      // the layout field manually and add it to templates if you want to use it
      // I'm not using the layout field though, so this feature might be dropped
    ];
  }

  public function __construct()
  {
    if (!$this->wire->config->livereload) return;
    if ($this->wire->config->ajax) return;
    $this->addHookBefore("Session::init", function (HookEvent $event) {
      if (!array_key_exists(self::getParam, $_GET)) return;
      $live = $this->getLiveReload();

      // return silently if secret does not match
      // somehow this check is called twice and always throws an error
      if (!$live->validSecret()) return;

      $event->object->sessionAllow = false;
      $live->watch();
    });
  }

  public function init()
  {
    $this->path = $this->wire->config->paths($this);
    $this->home = $this->wire->pages->get(1);

    require_once($this->path . "Asset.php");
    require_once($this->path . "AssetComment.php");
    require_once($this->path . "AssetsArray.php");
    require_once($this->path . "StylesArray.php");
    require_once($this->path . "ScriptsArray.php");

    // make $rockfrontend and $home variable available in template files
    $this->wire('rockfrontend', $this);
    $this->wire('home', $this->home);
    $this->alfredCache = $this->wire(new WireData());

    // JS defaults
    $this->remBase = 16; // default base for px-->rem conversion
    $this->js('growMin', 400);
    $this->js('growMax', 1440);
    $this->initPostCSS();

    // watch this file and run "migrate" on change or refresh
    if ($rm = $this->rm()) $rm->watch($this, 0.01);

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
    require_once __DIR__ . "/Functions.php";
    $this->createPermission(
      self::permission_alfred,
      "Is allowed to use ALFRED frontend editing"
    );
    $this->createCSS();

    // hooks
    $this->addHookAfter("ProcessPageEdit::buildForm", $this, "hideLayoutField");
    $this->addHook(self::tagsUrl, $this, "layoutSuggestions");
    $this->addHookAfter("Modules::refresh", $this, "refreshModules");
    $this->addHookBefore('TemplateFile::render', $this, "autoPrepend");

    // health checks
    $this->checkHealth();
  }

  public function ready()
  {
    $this->liveReload();
    $this->addAssets();
  }

  /**
   * Add assets to the html markup
   * @return void
   */
  public function addAssets()
  {
    $rockfrontend = $this;

    // we add RockFrontend.js by default because we want the rf-grow feature
    $rockfrontend->scripts()->add(__DIR__ . "/RockFrontend.js");

    // hook after page render to add script
    // this will also replace alfred tags
    $this->addHookAfter(
      "Page::render",
      function (HookEvent $event) use ($rockfrontend) {
        $page = $event->object;
        $html = $event->return;
        $styles = $this->styles();
        $scripts = $this->scripts();

        // early exit if html does not contain a head section
        if (!strpos($html, "</head>")) return;

        // add livereload secret
        if ($this->wire->config->livereload) {
          $this->js("rootUrl", $this->wire->config->urls->root);

          // create secret and send it to js
          /** @var WireRandom $rand */
          $rand = $this->wire(new WireRandom());
          $cache = $this->wire->cache->get(self::livereloadCacheName);
          if (!is_array($cache)) $cache = [];
          $secret = $rand->alphanumeric(0, ['minLength' => 30, 'maxLength' => 40]);
          $merged = array_merge($cache, [$secret]);
          $this->wire->cache->save(self::livereloadCacheName, $merged);
          $this->js("livereloadSecret", $secret);
        }

        // load alfred?
        if ($this->loadAlfred()) {
          $this->js("rootUrl", $this->wire->config->urls->root);
          $this->scripts()->add($this->path . "Alfred.js");
          $this->addAlfredStyles();
          $html = preg_replace_callback("/data-alfred-(.*?)=1/", function ($match) {
            $id = $match[1];
            return $this->alfredCache->get($id);
          }, $html);
        }

        // autoload scripts and styles
        $rockfrontend->autoload($page);

        // at the very end we inject the js variables
        $assets = '';
        $json = count($this->js) ? json_encode($this->js) : '';
        if ($json) $assets .= "\n  <script>let RockFrontend = $json</script>";

        // check if assets have already been added
        // if not we inject them at the end of the <head>
        if (!strpos($html, StylesArray::comment)) $assets .= $styles->render();
        if (!strpos($html, ScriptsArray::comment)) $assets .= $scripts->render();

        // return replaced markup
        $html = str_replace("</head>", "$assets</head>", $html);

        // add a fake edit tag to the page body
        // this ensures that jQuery is loaded via PageFrontEdit
        $faketag = "<div edit=title hidden>title</div>";
        $html = str_replace("</body", "$faketag</body", $html);

        $event->return = $html;
      }
    );
  }

  public function ___addAlfredStyles()
  {
    $this->styles()->add($this->path . "Alfred.css");
  }

  private function addLiveReloadScript()
  {
    // we only add live reloading to the frontend
    if ($this->wire->page->template == 'admin') return;
    $this->scripts('head')->add($this->path . "livereload.js");
  }

  /**
   * Return link to add a new page under given parent
   * @return string
   */
  public function addPageLink($parent)
  {
    $admin = $this->wire->pages->get(2)->url;
    return $admin . "page/add/?parent_id=$parent";
  }

  /**
   * Add a custom postCSS callback
   * 
   * Usage:
   * $rockfrontend->addPostCSS('foo', function($markup) {
   *   return str_replace('foo', 'bar', $markup);
   * });
   */
  public function addPostCSS($key, $callback)
  {
    $this->postCSS->set($key, $callback);
  }

  /**
   * Apply postCSS rules to given string
   */
  public function postCSS($str): string
  {
    foreach ($this->postCSS as $callback) $str = $callback($str);
    return $str;
  }

  /**
   * Convert px to rem
   */
  public function rem($value): WireData
  {
    $value = strtolower(trim($value));
    preg_match("/(.*?)([a-z]+)/", $value, $matches);
    $val = trim($matches[1]);
    $unit = trim($matches[2]);

    $data = $this->wire(new WireData());
    $data->val = $val;
    $data->unit = $unit;

    if ($unit !== 'pxrem') return $data;

    // convert pixel to rem
    $base = $this->rockfrontend()->remBase;
    $data->val = round($val / $base, 3);
    $data->unit = 'rem';

    return $data;
  }

  /**
   * ALFRED - A Lovely FRontend EDitor
   *
   * Usage:
   * alfred($page, "title,images")
   *
   * Usage with options array (for RockPageBuilder blocks)
   * alfred($page, [
   *   'trash' => false,
   *   'fields' => 'foo,bar',
   * ]);
   *
   * You can also provide fields as array when using the verbose syntax
   * alfred($page, [
   *   'trash' => false,
   *   'fields' => [
   *     'foo',
   *     'bar',
   *   ],
   * ]);
   *
   * @return string
   */
  public function alfred($page = null, $options = [])
  {
    if (!$this->alfredAllowed()) return;

    // support short syntax
    if (is_string($options)) $options = ['fields' => $options];

    // set flag to show that at least one alfred tag is on the page
    // this flag is used to load the PW frontend editing assets
    $this->hasAlfred = true;
    $page = $page ? $this->wire->pages->get((string)$page) : false;

    // is given page a widget block stored in field rockpagebuilder_widgets?
    $isWidget = false;
    if ($page instanceof Block and $page->isWidget()) $isWidget = true;

    // setup options
    $opt = $this->wire(new WireData());
    /** @var WireData $opt */
    $opt->setArray([
      'fields' => '', // fields to edit
      'path' => $this->getTplPath(), // path to edit file
      'edit' => true,

      // setting specific to rockpagebuilder blocks
      'noBlock' => false, // prevent block icons if true
      'addTop' => null, // set to false to prevent icon
      'addBottom' => null, // set to false to prevent icon
      'addHorizontal' => null, // shortcut for addLeft + addRight
      'move' => true,
      'isWidget' => $isWidget, // is block saved in rockpagebuilder_widgets?
      'widgetStyle' => $isWidget, // make it orange
      'trash' => true, // will set the trash icon for rockpagebuilder blocks
      'clone' => true, // can item be cloned?
      'widget' => !$isWidget, // can item be converted into a widget?
    ]);
    $opt->setArray($options);

    // icons
    $icons = $this->getIcons($page, $opt);
    if (!count($icons)) return;

    // setup links for add buttons
    $blockid = '';
    if ($page instanceof Block) {
      // see explanation about widget above
      $widget = $page->_widget ?: $page;

      if ($opt->noBlock) {
        if ($opt->addTop !== true) $opt->addTop = false;
        if ($opt->addBottom !== true) $opt->addBottom = false;
        if ($opt->addHorizontal !== true) {
          $opt->addLeft = false;
          $opt->addRight = false;
        }
      }
      if ($opt->addTop !== false) $opt->addTop = $widget->rpbUrl("/add/?block=$widget&above=1");
      if ($opt->addBottom !== false) $opt->addBottom = $widget->rpbUrl("/add/?block=$widget");
      if ($opt->addHorizontal === true) {
        $opt->addTop = false;
        $opt->addBottom = false;
        $opt->addLeft = $widget->rpbUrl("/add/?block=$widget&above=1");
        $opt->addRight = $widget->rpbUrl("/add/?block=$widget");
      }

      $blockid = " data-rpbblock=$widget ";
    }

    $str = json_encode((object)[
      'icons' => $icons,
      'addTop' => $opt->addTop,
      'addBottom' => $opt->addBottom,
      'addLeft' => $opt->addLeft,
      'addRight' => $opt->addRight,
      'widgetStyle' => $opt->widgetStyle,
    ]);

    // save markup to cache and generate alfred tag
    // the tag will be replaced on page render
    // this is to make it possible to use alfred() without |noescape filter)
    $id = "i" . uniqid();
    $str = "$blockid alfred='$str'";
    $this->alfredCache->set($id, $str);
    return "data-alfred-$id=1";
  }

  /**
   * Shortcut to create ALFRED links with horizontal add buttons
   * Thx @gebeer for the PR!!
   * @return string
   */
  public function alfredH($page = null, $options = [])
  {
    return $this->alfred(
      $page,
      array_merge(['addHorizontal' => true], $options)
    );
  }

  /**
   * Is ALFRED allowed for current user?
   */
  protected function alfredAllowed(): bool
  {
    if ($this->wire->user->isSuperuser()) return true;
    if ($this->wire->user->hasPermission(self::permission_alfred)) return true;
    return false;
  }

  /**
   * Return full asset path from given path
   */
  public function assetPath($path): string
  {
    $path = Paths::normalizeSeparators($path);
    $dir = $this->wire->config->paths->assets . "RockFrontend/";
    if (strpos($path, $dir) === 0) return $path;
    return $dir . trim($path, "/");
  }

  /**
   * Autoload scripts and styles
   */
  public function autoload($page)
  {
    $styles = $this->styles();
    $scripts = $this->scripts();

    if ($page->template != 'admin') {
      // frontend
      if ($styles->opt('autoload')) {
        $styles->addAll('/site/templates/layouts');
        $styles->addAll('/site/templates/sections');
        $styles->addAll('/site/templates/partials');
        $styles->addAll('/site/assets/RockPageBuilder');
      }
    }
  }

  /**
   * Auto-prepend file before rendering for exposing variables from _init.php
   */
  public function autoPrepend($event)
  {
    $event->object->setPrependFilename($this->path . "AutoPrepend.php");
  }

  /**
   * Do several health checks
   */
  private function checkHealth()
  {
    if (!$this->wire->user->isSuperuser()) return;
    // removed version healthcheck as of v2.0.0
  }

  /**
   * Create CSS from LESS file
   * @return void
   */
  private function createCSS()
  {
    if (!$this->wire->user->isSuperuser()) return;
    $css = $this->path . "Alfred.css";
    $lessFile = $this->path . "Alfred.less";
    if (filemtime($css) > filemtime($lessFile)) return;
    if (!$less = $this->wire->modules->get("Less")) return;
    /** @var Less $less */
    $less->addFile($lessFile);
    $less->saveCSS($css);
    $this->message("Created $css from $lessFile");
  }

  /**
   * Create permission
   * @return void
   */
  private function createPermission($name, $title)
  {
    $p = $this->wire->permissions->get($name);
    if ($p and $p->id) return;
    $p = $this->wire->permissions->add($name);
    $p->setAndSave('title', $title);
  }

  /**
   * Download uikit
   * @return void
   */
  private function downloadUikit()
  {
    if (!$version = $this->wire->input->post('uikit', 'string')) return;
    $url = "https://github.com/uikit/uikit/archive/refs/tags/$version.zip";
    $tpl = $this->wire->config->paths->templates;
    $tmp = (new WireTempDir());
    (new WireHttp())->download($url, $tmp . "uikit.zip");
    $this->wire->files->unzip($tmp . "uikit.zip", $tpl);
  }

  public function editLinks($options = null, $list = true, $size = 32)
  {
    if ($options instanceof Page) {
      $options = ['page' => $options];
    }

    $opt = $this->wire(new WireData());
    $opt->setArray([
      'page' => $this->wire->page,
      'class' => 'tm-editlink',
    ]);

    if (!$opt->page->editable()) return;
    $pages = $this->wire->pages;
    $li = $list ? '<li>' : '';
    $endli = $list ? '</li>' : '';
    return $this->html("
      $li
        <a class='{$opt->class}' href='{$pages->get(2)->url}'>
          <svg xmlns='http://www.w3.org/2000/svg' xmlns:xlink='http://www.w3.org/1999/xlink' aria-hidden='true' role='img' class='iconify iconify--tabler' width='$size' height='$size' preserveAspectRatio='xMidYMid meet' viewBox='0 0 24 24'><g fill='none' stroke='currentColor' stroke-linecap='round' stroke-linejoin='round' stroke-width='2'><rect width='6' height='6' x='3' y='15' rx='2'></rect><rect width='6' height='6' x='15' y='15' rx='2'></rect><rect width='6' height='6' x='9' y='3' rx='2'></rect><path d='M6 15v-1a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v1m-6-6v3'></path></g></svg>
        </a>
      $endli
      $li
        <a class='{$opt->class}' href='{$opt->page->editUrl()}'>
          <svg xmlns='http://www.w3.org/2000/svg' xmlns:xlink='http://www.w3.org/1999/xlink' aria-hidden='true' role='img' class='iconify iconify--tabler' width='$size' height='$size' preserveAspectRatio='xMidYMid meet' viewBox='0 0 24 24'><g fill='none' stroke='currentColor' stroke-linecap='round' stroke-linejoin='round' stroke-width='2'><path d='M7 7H6a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h9a2 2 0 0 0 2-2v-1'></path><path d='M20.385 6.585a2.1 2.1 0 0 0-2.97-2.97L9 12v3h3l8.385-8.415zM16 5l3 3'></path></g></svg>
        </a>
      $endli
    ");
  }

  /**
   * Find files to suggest
   * @return array
   */
  public function ___findSuggestFiles($q)
  {
    $suggestions = [];
    foreach ($this->layoutFolders as $dir) {
      // find all files to add
      $files = $this->wire->files->find($dir, [
        'extensions' => ['php'],
        'excludeDirNames' => [
          'cache',
        ],
      ]);

      // modify file paths
      $files = array_map(function ($item) use ($dir) {
        // strip path from file
        $str = str_replace($dir, "", $item);
        // strip php file extension
        return substr($str, 0, -4);
      }, $files);

      // only use files from within subfolders of the specified directory
      $files = array_filter($files, function ($str) use ($q) {
        if (!strpos($str, "/")) return false;
        return !(strpos($str, $q) < 0);
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
   * Force recreation of CSS files
   */
  public function forceRecompile()
  {
    $this->wire->session->set(self::recompile, true);
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
  public function getFile($file, $forcePath = false)
  {
    if (strpos($file, "//") === 0) return $file;
    if (strpos($file, "https://") === 0) return $file;
    if (strpos($file, "https://") === 0) return $file;
    $file = Paths::normalizeSeparators($file);

    // we always add a slash to the file
    // this is to ensure that relative paths are not found by is_file() below
    // $file = "/".ltrim($file, "/");

    // if no extension was provided try php or latte extension
    if (!pathinfo($file, PATHINFO_EXTENSION)) {
      if ($f = $this->getFile("$file.php", $forcePath)) return $this->realpath($f);
      if ($f = $this->getFile("$file.latte", $forcePath)) return $this->realpath($f);
    }

    // if file exists return it
    // this will also find files relative to /site/templates!
    // TODO maybe prevent loading of relative paths outside assets?
    $inRoot = $this->wire->files->fileInPath($file, $this->wire->config->paths->root);
    if ($inRoot and is_file($file)) return $this->realpath($file);

    // look for the file in specified folders
    foreach ($this->folders as $folder) {
      $folder = Paths::normalizeSeparators($folder);
      $folder = rtrim($folder, "/") . "/";
      $path = $folder . ltrim($file, "/");
      if (is_file($path)) return $this->realpath($path);
    }

    // no file found
    // if force path is set true we return the path nonetheless
    // this should help on frontend development to get a 404 when using wrong paths
    if ($forcePath) return $file;

    // no file, return false
    return false;
  }

  /**
   * Get ALFRED icons
   * @return array
   */
  public function ___getIcons($page, $opt)
  {
    $icons = [];

    // prepare fields suffix
    $fields = '';
    if ($opt->fields) {
      if (is_array($opt->fields)) $opt->fields = implode(",", $opt->fields);

      // check if the requested fields are available on that page
      // if the field does not exist for that page we don't request it
      // this is to prevent exception errors breaking page editing
      $sep = "&fields=";
      foreach (explode(",", $opt->fields) as $field) {
        $field = trim($field);
        if (!$page->template->hasField($field)) continue;
        $fields .= $sep . $field;
        $sep = ",";
      }
    }

    if ($page and $page->editable() and $opt->edit) {
      $icons[] = (object)[
        'icon' => 'edit',
        'label' => $page->title,
        'tooltip' => "Edit Block #{$page->id}",
        'href' => $page->editUrl() . $fields,
        'class' => 'pw-modal alfred-edit',
        'suffix' => 'data-buttons="button.ui-button[type=submit]" data-autoclose data-reload',
      ];
    }

    // add rockpagebuilder icons
    if ($page and $page instanceof Block) $page->addAlfredIcons($icons, $opt);

    if ($this->wire->user->isSuperuser()) {
      $tracy = $this->wire->config->tracy;
      if (is_array($tracy) and array_key_exists('localRootPath', $tracy))
        $root = $tracy['localRootPath'];
      else $root = $this->wire->config->paths->root;
      $link = str_replace($this->wire->config->paths->root, $root, $opt->path);

      // view file edit link
      $icons[] = (object)[
        'icon' => 'code',
        'label' => $opt->path,
        'href' => "vscode://file/$link",
        'tooltip' => $link,
      ];

      $ext = pathinfo($link, PATHINFO_EXTENSION);

      // controller edit link
      $php = substr($opt->path, 0, strlen($ext) * -1 - 1) . ".php";
      if (is_file($php)) {
        $php = str_replace($this->wire->config->paths->root, $root, $php);
        $icons[] = (object)[
          'icon' => 'php',
          'label' => $php,
          'href' => "vscode://file/$php",
          'tooltip' => $php,
        ];
      }
      // style edit link
      $less = substr($opt->path, 0, strlen($ext) * -1 - 1) . ".less";
      if (is_file($less)) {
        $less = str_replace($this->wire->config->paths->root, $root, $less);
        $icons[] = (object)[
          'icon' => 'eye',
          'label' => $less,
          'href' => "vscode://file/$less",
          'tooltip' => $less,
        ];
      }
    }
    return $icons;
  }

  /**
   * Get layout from page field
   * @return array|false
   */
  public function getLayout($page)
  {
    $layout = $page->get(self::field_layout);
    if (!$layout) return false;
    return explode(" ", $layout);
  }

  /**
   * Get a new instance of LiveReload
   */
  public function getLiveReload(): LiveReload
  {
    require_once __DIR__ . "/LiveReload.php";
    return new LiveReload();
  }

  /**
   * Find path in rockfrontend folders
   * Returns path with trailing slash
   * @return string|false
   */
  public function getPath($path, $forcePath = false)
  {
    $path = Paths::normalizeSeparators($path);

    // if the path is already absolute and exists we return it
    // we dont return relative paths!
    // we also make sure that the path is somewhere within the pw root
    // to prevent open basedir restriction warnings
    $inRoot = strpos($path, $this->wire->config->paths->root) === 0;
    if (strpos($path, '/') === 0 and $inRoot and is_dir($path)) {
      return rtrim($path, '/') . '/';
    }

    foreach ($this->folders as $f) {
      $dir = $f . ltrim($path, '/');
      if (is_dir($dir)) return rtrim($dir, '/') . '/';
    }

    if ($forcePath) return rtrim($path, '/') . '/';

    return false;
  }

  /**
   * Find template file from trace
   * @return string
   */
  public function getTplPath()
  {
    $trace = debug_backtrace();
    $paths = $this->wire->config->paths;
    foreach ($trace as $step) {
      $file = $step['file'];
      $skip = [
        $paths->cache,
        $paths($this),
        $paths->root . "vendor/"
      ];
      foreach ($skip as $p) {
        if (strpos($file, $p) === 0) $skip = true;
      }

      // special case: rockpagebuilder block
      if ($file === $paths->siteModules . "RockPageBuilder/Block.php") {
        // return the block view file instead of the block controller
        return $step['args'][0];
      } elseif (
        $file === $paths->siteModules . "RockFrontend/RockFrontend.module.php"
        and count($step['args'])
      ) {
        $f = $step['args'][0];

        // in case of alfredH args(0) is the block object
        // in this case we continue and return the next entry
        if (!is_string($f)) continue;

        return $f;
      }

      // try next entry or return file
      if ($skip === true) continue;
      else return $file;
    }
  }

  /*
   * Get translated key by string
   * @return string
   */
  public function getTranslation($key)
  {
    if (array_key_exists($key, $this->translations)) return $this->translations[$key];
    return '';
  }

  /**
   * Get uikit versions from github
   */
  public function getUikitVersions()
  {
    return $this->wire->cache->get(self::cache, 60 * 5, function () {
      $http = new WireHttp();
      $json = $http->get('https://api.github.com/repos/uikit/uikit/git/refs/tags');
      $refs = json_decode($json);
      $versions = [];
      foreach ($refs as $ref) {
        $version = str_replace("refs/tags/", "", $ref->ref);
        $v = $version;
        if (strpos($version, "v.") === 0) continue;
        if (strpos($version, "v") !== 0) continue;
        $versions[$v] = $version;
      }
      uasort($versions, "version_compare");
      return array_reverse($versions);
    });
  }

  /**
   * Hide layout field for non-superusers
   * @return void
   */
  public function hideLayoutField(HookEvent $event)
  {
    if ($this->wire->user->isSuperuser()) return;
    $form = $event->return;
    $form->remove(self::field_layout);
  }

  /**
   * Return a latte HTML object that doesn't need to be |noescaped
   * @return Html
   */
  public static function html($str)
  {
    // we try to return a latte html object
    // If we are not calling that from within a latte file
    // the html object will not be available. This can be the case in Seo tags.
    // To make sure it returns something we catch erros and return the plain
    // string instead. That means if called from outside a latte file it will
    // still return the HTML.
    try {
      return new Html($str);
    } catch (\Throwable $th) {
      return $str;
    }
  }

  /**
   * Render icon link
   * @return string
   */
  public function iconLink($icon, $href, $options = [])
  {
    $opt = $this->wire(new WireData());
    /** @var WireData $opt */
    $opt->setArray([
      'class' => 'alfred-icon pw-modal',
      'wrapClass' => '',
      'attrs' => 'data-autoclose data-reload data-barba-prevent
        data-buttons="button.ui-button[type=submit]"',
      'title' => false,
      'style' => 'text-align: center; margin: 20px 0;',
    ]);
    $opt->setArray($options);
    $url = rtrim($this->wire->config->urls($this), "/");
    $title = $opt->title ? "title='{$opt->title}' uk-tooltip" : "";
    return "<div class='{$opt->wrapperClass}' style='{$opt->style}'>
      <a href='$href' $title class='{$opt->class}' {$opt->attrs}>
        <img src='$url/icons/$icon.svg' style='display:inline'>
      </a>
    </div>";
  }

  /**
   * Return an image tag for the given file
   * @return string
   */
  public function img($file)
  {
    $url = $this->url($file);
    if ($url) return "<img src='$url'>";
    return '';
  }

  public function initPostCSS()
  {
    $data = $this->wire(new WireData());

    // rfGrow() postCSS replacer
    $data->set("rfGrow(", function ($markup) {
      return preg_replace_callback("/rfGrow\((.*?),(.*?)\)/", function ($match) {
        // bd($match);
        if (count($match) !== 3) return false;
        try {
          $min = $this->rem($match[1]);
          $max = $this->rem($match[2]);
          if ($min->unit !== $max->unit) throw new WireException(
            "rfGrow(error: min and max value must have the same unit)"
          );

          $diff = $max->val - $min->val;
          return "calc({$min->val}{$min->unit} + {$diff}{$min->unit} * var(--rf-grow))";
        } catch (\Throwable $th) {
          return $th->getMessage();
        }
      }, $markup);
    });
    $this->postCSS = $data;
  }

  /**
   * Is the given page active in the menu?
   * 
   * The root page will only be active if itself is viewed (not any descendant)
   * 
   * @return bool
   */
  public function isActive($menuItem, $page = null)
  {
    $page = $page ?: $this->wire->page;

    // special treatment for the homepage (root page)
    // the "home" menu item is only marked as active if the
    // currently viewed page is really the homepage
    if ($menuItem->id === 1) return $page->id === 1;

    // all other menu items are marked as active if we are
    // either on that page or on one of its descendants
    $active = $page->parents()->add($page);
    return $active->has($menuItem);
  }

  /**
   * Is given file newer than the comparison file?
   * Returns true if comparison file does not exist
   * Returns false if file does not exist
   */
  public function isNewer($file, $comparison): bool
  {
    if (!is_file($file)) return false;
    if (!is_file($comparison)) return true;
    return filemtime($file) > filemtime($comparison);
  }

  /**
   * Get or set a javascript value that is sent to the frontend
   * @return mixed
   */
  public function js($key, $value = null)
  {
    // getter
    if ($value === null) {
      if (array_key_exists($key, $this->js)) return $this->js[$key];
      return false;
    }
    // setter
    $this->js[$key] = $value;
  }

  /**
   * Return layout suggestions
   */
  public function layoutSuggestions(HookEvent $event)
  {
    return $this->findSuggestFiles($event->q);
  }

  /**
   * Setup live reloading
   */
  public function livereload()
  {
    // early exit if live reload is disabled
    if (!$this->wire->config->livereload) return;

    if ($this->wire->page->template == 'admin') {
      $file = $this->wire->config->paths->root . "livereload.php";
      if ($this->wire->user->isSuperuser() and is_file($file)) {
        $this->warning("Found file $file which is not used any more - you can delete it");
      }
    }

    // early exit when page is opened in modal window
    // this is to prevent enless reloads when the parent frame is reloading
    if ($this->wire->input->get('modal')) return;
    // reset the livereload secret on every modules refresh
    $cachefile = $this->wire->config->paths->cache . self::livereloadCacheName . ".txt";
    $this->addHookAfter("Modules::refresh", function () use ($cachefile) {
      if (is_file($cachefile)) $this->wire->files->unlink($cachefile);
      $this->wire->cache->save(self::livereloadCacheName, null);
    });

    // add script that triggers stream on frontend
    $this->addLiveReloadScript();
  }

  /**
   * Load ALFRED assets?
   */
  protected function loadAlfred(): bool
  {
    if (!$this->hasAlfred) return false;
    if ($this->wire->user->isSuperuser()) return true;
    if ($this->wire->user->hasPermission(self::permission_alfred)) return true;
    return false;
  }

  /**
   * Create a site webmanifest in PW root
   * @return Manifest
   */
  public function manifest()
  {
    require_once $this->path . "Manifest.php";
    if ($this->manifest) return $this->manifest;
    $manifest = new Manifest();

    // by default we update the manifest file when the root page is saved
    $manifest->createOnSave('id=1');

    return $this->manifest = $manifest;
  }

  public function migrate()
  {
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
        self::field_favicon => [
          'type' => 'image',
          'label' => 'Favicon',
          'maxFiles' => 1,
          'descriptionRows' => 0,
          'columnWidth' => 50,
          'extensions' => 'png',
          'maxSize' => 3, // max 3 megapixels
          'icon' => 'picture-o',
          'outputFormat' => FieldtypeFile::outputFormatSingle,
          'description' => 'For best browser support and quality upload a high resolution PNG (min 512x512). You can use transparency in your favicon.',
          'notes' => '[See here](https://loqbooq.app/blog/add-favicon-modern-browser-guide) and [here](https://css-tricks.com/svg-favicons-and-all-the-fun-things-we-can-do-with-them/) to learn more about favicons'
        ],
        self::field_ogimage => [
          'type' => 'image',
          'label' => 'og:image',
          'maxFiles' => 1,
          'descriptionRows' => 0,
          'columnWidth' => 50,
          'extensions' => 'png jpg svg',
          'okExtensions' => ['svg'],
          'maxSize' => 3, // max 3 megapixels
          'icon' => 'picture-o',
          'outputFormat' => FieldtypeFile::outputFormatSingle,
          'description' => 'Here you can add the fallback og:image that will be used by RockFrontend\'s SEO-Tools.',
        ],
      ],
    ]);
    $rm->addFieldToTemplate(self::field_favicon, 'home');
    $rm->addFieldToTemplate(self::field_ogimage, 'home');
  }

  /**
   * Copy profile files to PW root
   * @return void
   */
  private function profileExecute()
  {
    $profile = $this->wire->input->post('profile', 'filename');
    foreach ($this->profiles() as $path => $label) {
      if ($label !== $profile) continue;
      $this->wire->files->copy("$path/files", $this->wire->config->paths->root);
      $this->wire->message("Copied profile $label to PW");
      $this->wire->pages->get(1)->meta(
        self::installedprofilekey,
        $profile . " (last installed @ " . date("Y-m-d H:i:s") . ")"
      );
      return true;
    }
    return false;
  }

  /**
   * Get array of available profiles
   * hookable so that other modules can extend available profiles
   * @return array
   */
  public function ___profiles()
  {
    $profiles = [];
    $path = Paths::normalizeSeparators(__DIR__ . "/profiles");
    foreach (array_diff(scandir($path), ['.', '..']) as $label) {
      $profiles["$path/$label"] = $label;
    }
    return $profiles;
  }

  /**
   * Return normalized realpath
   * @return string
   */
  public function realpath($file)
  {
    return Paths::normalizeSeparators(realpath($file));
  }

  /**
   * Things to do when modules are refreshed
   */
  public function refreshModules()
  {
    $this->wire->files->rmdir(
      $this->wire->config->paths->assets . "RockFrontend/css",
      true
    );
    $this->forceRecompile();
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
   * Render a RepeaterMatrix field
   * echo $rf->render($page->your_pagebuilder_field);
   *
   * Use render to render children of current page:
   * foreach($page->children() as $item) {
   *   echo $rockfrontend->render("partials/card.latte", $item);
   *   // note that $item will be available in card as $page variable!
   * }
   *
   * You can also provide custom variables as second parameter:
   * $rockfrontend->render("your/file.php", [
   *   'foo' => $pages->get('/foo'), // available as $foo
   *   'today' => date("d.m.Y"), // available as $today
   * ]);
   *
   * @param mixed $path
   * @param array $vars
   * @param array $options
   * @return string
   */
  public function ___render($path, $vars = null, $options = [])
  {
    $page = $this->wire->page;
    if (!$vars) $vars = [];

    // add support for rendering repeater pagebuilder fields
    if (!$path) return; // if field does not exist
    if ($path instanceof RepeaterMatrixPageArray) {
      return $this->renderMatrix($path, $vars, $options);
    }

    // prepare variables
    if ($vars instanceof Page) $vars = ['page' => $vars];

    // we add the $rf variable to all files that are rendered via RockFrontend
    $vars = array_merge($this->wire('all')->getArray(), $vars, ['rf' => $this]);

    // options
    $opt = $this->wire(new WireData());
    /** @var WireData $opt */
    $opt->setArray([
      'allowedPaths' => $this->folders,
    ]);
    $opt->setArray($options);

    // if path is an array render the first matching output
    if (is_array($path)) {
      foreach ($path as $k => $v) {
        // if the key is a string, it is a selector
        // if the selector does not match we do NOT try to render this layout
        if (is_string($k) and !$page->matches($k)) continue;

        // no selector, or matching selector
        // try to render this layout/file
        // if no output we try the next one
        // if file returns FALSE we exit here
        $out = $this->render($v, $vars);
        if ($out or $out === false) return $out;
      }
      return; // no output found in any file of the array
    }

    // path is a string, render file
    $file = $this->getFile($path);
    if (!$file) return;
    $html = '';

    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    if ($ext == 'php') {
      $options = $opt->getArray();
      $html = $this->wire->files->render($file, $vars, $options);
    } elseif ($ext == 'svg') $html = $this->svg($file, $vars);
    else {
      try {
        $method = "renderFile" . ucfirst(strtolower($ext));
        $html = $this->$method($file, $vars);
      } catch (\Throwable $th) {
        $html = $th->getMessage();
      }
    }

    // if render() was called from within a latte file we return a HTML object
    // so that we dont need to use the |noescape filter
    if (strpos(Debug::backtrace()[0]['file'], "/site/assets/cache/Latte/") === 0) {
      return $this->html($html);
    }

    return $html;
  }

  /**
   * LATTE renderer
   */
  protected function renderFileLatte($file, $vars)
  {
    $latte = $this->latte;
    if (!$latte) {
      try {
        require_once $this->path . "vendor/autoload.php";
        $latte = new Engine();
        $latte->setTempDirectory($this->wire->config->paths->cache . "Latte");
        $this->latte = $latte;
      } catch (\Throwable $th) {
        return $th->getMessage();
      }
    }
    return $latte->renderToString($file, $vars);
  }

  /**
   * SVG renderer
   */
  protected function renderFileSvg($file)
  {
    return $this->svg($file);
  }

  /**
   * Twig renderer
   */
  protected function renderFileTwig($file, $vars)
  {
    try {
      require_once $this->wire->config->paths->root . 'vendor/autoload.php';
      $loader = new \Twig\Loader\FilesystemLoader($this->wire->config->paths->root);
      $twig = new \Twig\Environment($loader);
      $relativePath = str_replace(
        $this->wire->config->paths->root,
        $this->wire->config->urls->root,
        $file
      );
      $vars = array_merge((array)$this->wire('all'), $vars);
      return $twig->render($relativePath, $vars);
    } catch (\Throwable $th) {
      return $th->getMessage() .
        '<br><br>Use composer require "twig/twig:^3.0" in PW root';
    }
  }

  /**
   * Proxy to render method if condition is met
   *
   * Usage:
   * echo $rf->renderIf("/sections/foo.latte", "template=foo");
   *
   * @param string|array $path
   * @param mixed $condition
   * @param array $vars
   * @param array $options
   * @return string
   */
  public function renderIf($path, $condition, $vars = null, $options = [])
  {
    $render = $condition;
    if (is_string($condition)) {
      // condition is a string so we assume it is a page selector
      $render = $this->wire->page->matches($condition);
    }
    if ($render) return $this->render($path, $vars, $options);
    return '';
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
  public function renderLayout(Page $page, $fallback = [], $noMerge = false)
  {
    $defaultFallback = [
      "layouts/{$page->template}",
      "layouts/default",
    ];

    // by default we will merge the default array with the array
    // provided by the user
    if (!$noMerge) $fallback = $fallback + $defaultFallback;

    // bd($fallback);

    // try to find layout from layout field of the page editor
    $layout = $this->getLayout($page);
    if ($layout) return $this->render($layout);
    return $this->render($fallback);
  }

  /**
   * Render RepeaterMatrix fields
   * @return string
   */
  public function renderMatrix($items, $vars, $options)
  {
    $out = '';
    foreach ($items as $item) {
      $field = $item->getForField();
      $type = $item->type;
      $file = "fields/$field/$type";
      $vars = array_merge($vars, ['page' => $item]);
      $out .= $this->render($file, $vars, $options);
    }

    // if renderMatrix was called from a latte file we return HTML instead
    // of a string so that we don't need to call |noescape filter
    $trace = Debug::backtrace()[1]['file'];
    if (strpos($trace, "/site/assets/cache/Latte/") === 0) $out = new Html($out);

    return $out;
  }

  /**
   * @return RockMigrations
   */
  public function rm()
  {
    return $this->wire->modules->get('RockMigrations');
  }

  /**
   * @return Seo
   */
  public function seo()
  {
    if ($this->seo) return $this->seo;
    require_once __DIR__ . "/Seo.php";
    return $this->seo = new Seo();
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
  public function scripts($name = 'head')
  {
    if (!$this->scripts) $this->scripts = new WireData();
    $script = $this->scripts->get($name) ?: new ScriptsArray($name);
    $this->scripts->set($name, $script);
    return $script;
  }

  /**
   * Return script-tag
   * @return string
   */
  public function scriptTag($path, $cacheBuster = false)
  {
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
   * $rockfrontend->styles()->add(...);
   * // file2.php
   * $rockfrontend->styles()->add(...);
   * // _main.php
   * $rockfrontend->styles()->render();
   *
   * @return StylesArray
   */
  public function styles($name = 'head')
  {
    if (!$this->styles) $this->styles = new WireData();
    $style = $this->styles->get($name) ?: new StylesArray($name);
    if ($name) $this->styles->set($name, $style);
    return $style;
  }

  /**
   * Return style-tag
   * @return string
   */
  public function styleTag($path, $cacheBuster = false)
  {
    $href = $this->url($path, $cacheBuster);
    return "<link href='$href' rel='stylesheet'>";
  }

  /**
   * Render svg file
   * @return string
   */
  public function svg($filename, $replacements = [])
  {
    $filename = $this->getFile($filename);
    if (!is_file($filename)) return;
    // we use file_get_contents because $files->render can cause parse errors
    // see https://wordpress.stackexchange.com/a/256445
    $svg = file_get_contents($filename);
    if (!is_array($replacements)) return $this->html($svg);
    if (!count($replacements)) return $this->html($svg);
    foreach ($replacements as $k => $v) {
      if (!is_string($v)) continue;
      $svg = str_replace("{{$k}}", $v, $svg);
    }
    return $this->html($svg);
  }

  /**
   * Given a path return the url relative to pw root
   *
   * If second parameter is true we add ?m=filemtime for cache busting
   *
   * @return string
   */
  public function url($path, $cacheBuster = false)
  {
    $path = $this->getFile($path, true);
    $config = $this->wire->config;
    $inRoot = $this->wire->files->fileInPath($path, $config->paths->root);
    $m = ($inRoot and is_file($path) and $cacheBuster) ? "?m=" . filemtime($path) : '';
    return str_replace($config->paths->root, $config->urls->root, $path . $m);
  }

  /**
   * Write content to asset
   */
  public function writeAsset($path, $content)
  {
    $files = $this->wire->files;
    $file = $this->assetPath($path);
    $files->mkdir(dirname($file), true);
    $comment = $files->fileGetContents($this->path . "AssetInfo.txt");
    $files->filePutContents($file, $comment . $content);
    return $file;
  }

  /**
   * Add translation strings to translations array
   *
   * Usage to set translations:
   * $rockfrontend->x([
   *   'submit' => __('Submit form'),
   *   'form_success' => __('Thank you for your message!'),
   * ]);
   *
   * Usage to get translations:
   * $rockfrontend->x('form_success');
   *
   * @return array
   */
  public function x($translations)
  {
    if (is_array($translations)) {
      return $this->translations = array_merge($this->translations, $translations);
    }
    return $this->getTranslation($translations);
  }

  public function ___install()
  {
    $this->init();
    if ($this->rm()) $this->migrate();
    // install FrontendEditing
    $this->wire->modules->get('PageFrontEdit');
    $this->message('Installed Module PageFrontEdit');
  }

  /**
   * Config inputfields
   * @param InputfieldWrapper $inputfields
   */
  public function getModuleConfigInputfields($inputfields)
  {

    $video = new InputfieldMarkup();
    $video->label = 'processwire-rocks.com';
    $video->value = '<iframe width="560" height="315" src="https://www.youtube.com/embed/7CoIj--u4ps" title="YouTube video player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>';
    $inputfields->add($video);

    /** @var RockMigrations $rm */
    $rm = $this->wire->modules->get('RockMigrations');
    if (!$rm) {
      $warn = new InputfieldMarkup();
      $warn->label = 'Warning';
      $warn->icon = 'exclamation-triangle';
      $warn->value = "<div class='uk-text-warning'>RockMigrations is not installed but may be required for some features of RockFrontend. For example it will create a field to upload a Favicon to your site. RockFrontend will then generate all necessary Favicon sizes and markup for all devices.</div>";
      $inputfields->add($warn);
    }

    $this->profileExecute();
    $f = new InputfieldSelect();
    $f->label = "Install Profile";
    $f->name = 'profile';
    $accordion = '<p>Available Profiles (click to see details):</p><ul uk-accordion>';
    foreach ($this->profiles() as $path => $label) {
      $text = $this->wire->sanitizer->entitiesMarkdown(
        file_get_contents("$path/readme.md"),
        true
      );
      $accordion .= "<li><a class='uk-accordion-title uk-text-small' href=#>$label</a>"
        . "<div class=uk-accordion-content>$text</div></li>";
      $f->addOption($label, $label);
    }
    $accordion .= "</ul>";
    $f->prependMarkup = "<p class='uk-text-warning'>WARNING: This will overwrite existing files - make sure to have backups or use GIT for version controlling your project!</p>";
    $f->prependMarkup .= $accordion;
    $f->notes = $this->profileInstalledNote();
    $inputfields->add($f);

    // download uikit
    $this->downloadUikit();
    $f = new InputfieldSelect();
    $f->name = 'uikit';
    $f->label = 'Download UIkit';
    $f->notes = "Will be downloaded to /site/templates/";
    foreach ($this->getUikitVersions() as $k => $v) $f->addOption($k);
    $inputfields->add($f);
    $this->addUikitNote($f);

    $this->downloadCDN();
    $f = new InputfieldMarkup();
    $f->name = 'cdn';
    $f->label = 'CDN-Downloader';
    $f->description = 'Loading assets via CDN might be illegal in your country due to GDPR regulations!';
    $f->notes = 'Files will be downloaded to /site/templates/assets/
      Need more presets? Let me know in the forum!';
    $f->value = "
      Presets:
      <ul class='presets'>
        <li><a href=# data-cdn='https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js' data-filename='alpine.js'>AlpineJS</a></li>
      </ul>
      <style>.cdntable td {padding: 0;margin:0}</style>
      <table class='uk-table cdntable'>
        <tr><td>CDN Url</td><td><input type='text' name='cdn'></td></tr>
        <tr><td>Local filename</td><td><input type='text' name='filename'></td></tr>
      </table>
      <script>
      (function() {
        let util = UIkit.util;
        util.on('.presets a', 'click', function(e) {
          e.preventDefault();
          let a = e.target.closest('a');
          let cdn = util.data(a, 'cdn');
          let filename = util.data(a, 'filename');
          util.$('input[name=cdn]').value = cdn;
          util.$('input[name=filename]').value = filename;
        });
      })()
      </script>
      ";
    $inputfields->add($f);

    $f = new InputfieldText();
    $f->name = 'webfont';
    $f->label = 'Webfont-Downloader';
    $f->description = 'Using webfonts might be illegal in your country due to GDPR regulations!';
    $f->notes = 'Enter URL to download webfont from, eg https://fonts.googleapis.com/css?family=Baloo+2:800|Open+Sans&display=swap
      Font files will be downloaded to /site/templates/fonts/';
    $inputfields->add($f);

    // webfont downloader
    $data = $this->downloadWebfont();
    if ($data->suggestedCss) {
      $f = new InputfieldMarkup();
      $f->label = 'Suggested CSS';
      $f->description = "You can copy&paste the created CSS into your stylesheet. The paths expect it to live in /site/templates/layouts/ - change the path to your needs!
        See [https://css-tricks.com/snippets/css/using-font-face-in-css/](https://css-tricks.com/snippets/css/using-font-face-in-css/) for details!";
      $f->value = "<pre style='max-height:400px;'><code>{$data->suggestedCss}</code></pre>";
      $f->notes = "Data above is stored in the current session and will be reset on logout";
      $inputfields->add($f);
    }
    if ($data->rawCss) {
      $f = new InputfieldMarkup();
      $f->label = 'Raw CSS (for debugging)';
      $f->value = "<pre style='max-height:400px;'><code>{$data->rawCss}</code></pre>";
      $f->notes = "Data above is stored in the current session and will be reset on logout";
      $f->collapsed = Inputfield::collapsedYes;
      $inputfields->add($f);
    }

    return $inputfields;
  }

  private function addUikitNote(InputfieldSelect $f)
  {
    $note = '';
    foreach (scandir($this->wire->config->paths->templates) as $p) {
      if (strpos($p, "uikit-") !== 0) continue;
      $note .= "\nFound /site/templates/$p";
    }
    $f->notes .= $note;
  }

  private function downloadCDN()
  {
    $url = $this->wire->input->post('cdn', 'url');
    $filename = $this->wire->input->post('filename', 'string')
      ?: pathinfo($url, PATHINFO_BASENAME);
    if (!$url) return;
    /** @var WireHttp $http */
    $http = $this->wire(new WireHttp());
    $path = $this->wire->config->paths->templates . "assets/";
    $this->wire->files->mkdir($path);
    $file = $path . $filename;
    $http->download($url, $file);

    $ext = pathinfo($file, PATHINFO_EXTENSION);
    if ($ext == 'js') {
      $content = $this->wire->files->fileGetContents($file);
      $this->wire->files->filePutContents($file, "// $url\n$content");
    }
  }

  public function profileInstalledNote()
  {
    $note = $this->wire->pages->get(1)->meta(self::installedprofilekey);
    if ($note) return "Installed profile: $note";
  }

  /** ##### webfont downloader ##### */

  private function createCssSuggestion($data): string
  {
    // bd($data->files, 'files');
    $css = "/* suggestion for practical level of browser support */";
    foreach ($data->fonts as $name => $set) {
      /** @var AtRuleSet $set */
      // bd('create suggestion for name '.$name);
      // bd($set, 'set');
      $files = $data->files->find("basename=$name");

      // remove src from set
      $set->removeRule('src');

      // add new src rule
      $rule = new Rule('src');
      $src = '';

      // see https://css-tricks.com/snippets/css/using-font-face-in-css/#practical-level-of-browser-support
      foreach ($files->find("format=woff|woff2") as $file) {
        $comment = self::webfont_comments[$file->format];
        // comment needs to be first!
        // last comma will be trimmed and css render() will add ; at the end!
        $src .= "\n    $comment\n    url('../fonts/{$file->name}') format('{$file->format}'),";
      }
      $src = rtrim($src, ",\n ");
      $rule->setValue($src);
      $set->addRule($rule);

      $css .= "\n" . $set->render($data->parserformat);
    }

    $css .= "\n\n/* suggestion for deepest possible browser support */";
    foreach ($data->fonts as $name => $set) {
      /** @var AtRuleSet $set */
      // bd('create suggestion for name '.$name);
      // bd($set, 'set');
      $files = $data->files->find("basename=$name");

      // remove src from set
      $set->removeRule('src');

      // add new src rule
      $rule = new Rule('src');
      $src = '';

      // see https://css-tricks.com/snippets/css/using-font-face-in-css/#practical-level-of-browser-support
      $eot = $files->get("format=eot");
      if ($eot) {
        $src .= "url('../fonts/{$eot->name}'); /* IE9 Compat Modes */\n  ";
        $src .= "src: url('../fonts/{$eot->name}?#iefix') format('embedded-opentype'), /* IE6-IE8 */\n  ";
      }
      foreach ($files->find("format!=eot") as $file) {
        $format = $file->format;
        if ($format == 'ttf') $format = 'truetype';
        $comment = self::webfont_comments[$file->format];
        // comment needs to be first!
        // last comma will be trimmed and css render() will add ; at the end!
        $src .= "\n    $comment\n    url('../fonts/{$file->name}') format('{$file->format}'),";
      }
      $src = trim($src, ",\n ");
      $rule->setValue($src);
      $set->addRule($rule);

      $css .= "\n" . $set->render($data->parserformat);
    }

    return $css;
  }

  private function downloadWebfont(): WireData
  {
    $url = $this->wire->input->post('webfont', 'string');
    if (!$url) {
      // get data from session and return it
      $sessiondata = (array)json_decode((string)$this->wire->session->webfontdata);
      $data = new WireData();
      $data->setArray($sessiondata);
      return $data;
    }
    $data = $this->getFontData();

    // url was set, prepare fresh data
    $data->url = $url;

    /** @var WireHttp $http */
    $http = $this->wire(new WireHttp());
    foreach (self::webfont_agents as $format => $agent) {
      $data->rawCss .= "/* requesting format '$format' by using user agent '$agent' */\n";
      $http->setHeader("user-agent", $agent);
      $result = $http->get($url);
      $data->rawCss .= $result;
      $data = $this->parseResult($result, $format, $data);
    }
    // bd($data, 'data after http');

    $data->suggestedCss = trim($this->createCssSuggestion($data), "\n");

    // save data to session and return it
    $this->wire->session->webfontdata = json_encode($data->getArray());
    return $data;
  }

  /**
   * Get a blank fontdata object
   */
  private function getFontData(): WireData
  {
    $data = new WireData();
    $data->rawCss = '';
    $data->suggestedCss = '';
    $data->fonts = new WireData();

    // load css parser
    require_once __DIR__ . "/vendor/autoload.php";
    $of = (new OutputFormat())->createPretty()->indentWithSpaces(2);
    $data->parserformat = $of;

    // create fonts dir
    $dir = $this->wire->config->paths->templates . "fonts/";
    $this->wire->files->mkdir($dir);
    $data->fontdir = $dir;

    // downloaded font files
    $data->files = new WireArray();

    return $data;
  }

  /**
   * Extract http url from src()
   * @return string
   */
  private function getHttpUrl($src)
  {
    preg_match("/url\((.*?)\)/", $src, $matches);
    return trim($matches[1], "\"' ");
  }

  /**
   * CSS parser helper method
   * @return Rule|false
   */
  private function getRuleValue($str, RuleSet $ruleset)
  {
    try {
      $rule = $ruleset->getRules($str);
      if (!count($rule)) return false;
      return $rule[0]->getValue();
    } catch (\Throwable $th) {
      return "";
    }
  }

  private function parseResult($result, $format, $data = null): WireData
  {
    if (!$data) $data = $this->getFontData();

    $parser = new Parser($result);
    $css = $parser->parse();

    $http = new WireHttp();
    foreach ($css->getAllRuleSets() as $set) {
      if (!$set instanceof AtRuleSet) continue;

      // create a unique name from family settings
      $name = $this->wire->sanitizer->pageName(
        $this->getRuleValue("font-family", $set) . "-" .
          $this->getRuleValue("font-style", $set) . "-" .
          $this->getRuleValue("font-weight", $set)
      );

      // save ruleset to fonts data
      $data->fonts->set($name, $set);

      // download url
      $src = (string)$this->getRuleValue("src", $set);
      $httpUrl = $this->getHttpUrl($src);
      // db($src, 'src');
      // db($httpUrl, 'httpUrl');

      // save font to file and add it to the files array
      $filename = $name . ".$format";
      $filepath = $data->fontdir . $filename;
      $http->download($httpUrl, $filepath);
      $size = wireBytesStr(filesize($filepath), true);
      $filedata = new WireData();
      $filedata->name = $filename;
      $filedata->basename = $name;
      $filedata->path = $filepath;
      $filedata->format = $format;
      $filedata->size = $size;
      $data->files->add($filedata);
    }
    // db($data, 'data');
    return $data;
  }

  /** ##### END webfont downloader ##### */
}
