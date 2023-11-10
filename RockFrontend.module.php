<?php

namespace ProcessWire;

use Latte\Engine;
use Latte\Runtime\Html;
use RockFrontend\Asset;
use RockFrontend\HumanDates;
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
use Wa72\HtmlPageDom\HtmlPageCrawler;

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
  const defaultVspaceScale = 0.66;

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
  const field_footerlinks = self::prefix . "footerlinks";
  const field_images = self::prefix . "images";
  const field_less = self::prefix . "less";

  /** @var WireData */
  public $alfredCache;

  public $autoloadScripts;
  public $autoloadStyles;

  /** @var WireArray $folders */
  public $folders;

  public $home;

  /** @var bool */
  public $hasAlfred = false;

  public $isLiveReload = false;

  /** @var array */
  protected $js = [];

  /** @var Engine */
  private $latte;

  /** @var Engine */
  private $latteWithLayout;

  /** @var WireArray $layoutFolders */
  public $layoutFolders;

  private $liveReload;

  /** @var Manifest */
  protected $manifest;

  /** @var bool */
  public $noAssets = false;

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
  private $textdomain;

  /** @var array */
  private $translations = [];

  /** @var array */
  private $viewfolders = [];

  public function __construct()
  {
    if (!$this->wire->config->livereload) return;
    if ($this->wire->config->ajax) return;
    if (!array_key_exists(self::getParam, $_GET)) return;
    $this->addHookBefore("Session::init", function (HookEvent $event) {

      // disable tracy for the SSE stream
      $event->wire->config->tracy = ['enabled' => false];

      // get livereload instance
      $live = $this->getLiveReload();
      $this->liveReload = $live;

      // return silently if secret does not match
      // somehow this check is called twice and always throws an error
      if (!$live->validSecret()) return;

      $event->object->sessionAllow = false;
      $this->isLiveReload = true;
      $live->watch();
    });
  }

  public function init()
  {
    $this->wire->classLoader->addNamespace("RockFrontend", __DIR__ . "/classes");

    // if settings are set in config.php we make sure to use these settings
    $config = $this->wire->config;
    if ($config->livereloadBackend !== null) {
      $this->livereloadBackend = $config->livereloadBackend;
    }

    $this->path = $this->wire->config->paths($this);
    $this->home = $this->wire->pages->get(1);

    if (!is_array($this->features)) $this->features = [];
    if (!is_array($this->migrations)) $this->migrations = [];


    // make $rockfrontend and $home variable available in template files
    $this->wire('rockfrontend', $this);
    $this->wire('home', $this->home);
    $this->autoloadScripts = new WireArray();
    $this->autoloadStyles = new WireArray();
    $this->alfredCache = $this->wire(new WireData());

    // JS defaults
    $this->remBase = 16; // default base for px-->rem conversion
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
    $this->lessToCss($this->path . "Alfred.less");

    // hooks
    $this->addHookAfter("ProcessPageEdit::buildForm", $this, "hideLayoutField");
    $this->addHook(self::tagsUrl, $this, "layoutSuggestions");
    $this->addHookAfter("Modules::refresh", $this, "refreshModules");
    $this->addHookBefore('TemplateFile::render', $this, "autoPrepend");
    $this->addHookAfter("InputfieldForm::processInput", $this, "createWebfontsFile");
    $this->addHookBefore("Inputfield::render", $this, "addFooterlinksNote");
    $this->addHookAfter("Page::changed", $this, "resetCustomLess");
    $this->addHookBefore("Page::render", $this, "createCustomLess");
    $this->addHookMethod("Page::otherLangUrl", $this, "otherLangUrl");

    // health checks
    $this->checkHealth();
  }

  public function ready()
  {
    $this->liveReload();
    $this->addAssets();
    $this->copyLayoutFileIfNewer();
  }

  /**
   * Add assets to the html markup
   * @return void
   */
  public function addAssets()
  {
    // hook after page render to add script
    // this will also replace alfred tags
    $this->addHookAfter(
      "Page::render",
      function (HookEvent $event) {
        $html = $event->return;

        // early exits
        if (!strpos($html, "</body>")) return;
        if (!strpos($html, "</head>")) return;

        $this->addLiveReloadSecret();
        $this->addRockFrontendJS();
        $this->addAlfredMarkup($html);
        $this->addTopBar($html);
        $this->injectJavascriptSettings($html);
        $this->injectAssets($html);
        $event->return = $html;
      }
    );
  }

  private function addAlfredMarkup(string &$html): void
  {
    if (!$this->loadAlfred()) return;

    $this->js("rootUrl", $this->wire->config->urls->root);
    $this->js("defaultVspaceScale", number_format(self::defaultVspaceScale, 2, ".", ""));
    $this->scripts('rockfrontend')->add($this->path . "Alfred.js");
    $this->addAlfredStyles();

    // replace alfred cache markup
    // if alfred was added without |noescape it has quotes around
    if (strpos($html, '"#alfredcache-')) {
      foreach ($this->alfredCache as $key => $str) {
        $html = str_replace("\"$key\"", $str, $html);
      }
    }
    // if alfred was added with |noescape filter we don't have quotes
    if (strpos($html, '#alfredcache-')) {
      foreach ($this->alfredCache as $key => $str) {
        $html = str_replace("$key", $str, $html);
      }
    }

    // add a fake edit tag to the page body
    // this ensures that jQuery is loaded via PageFrontEdit
    $faketag = "<div edit=title hidden>title</div>";
    $html = str_replace("</body", "$faketag</body", $html);
  }

  private function addLiveReloadSecret(): void
  {
    if (!$this->wire->config->livereload) return;
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

  private function addRockFrontendJS(): void
  {
    if (!$this->isEnabled('RockFrontend.js')) return;
    $file = __DIR__ . "/RockFrontend.js";
    if ($this->wire->config->debug) {
      // load the non-minified script
      $this->scripts('rockfrontend')->add($file, "defer");
      // when logged in as superuser we make sure to create the minified
      // file even if the non-minified version is used.
      if ($this->wire->user->isSuperuser()) $this->minifyFile($file);
    } else $this->scripts('rockfrontend')->add($this->minifyFile($file), "defer");
  }

  public function ___addAlfredStyles()
  {
    $this->styles('rockfrontend')->add($this->path . "Alfred.css", "", ['minify' => false]);
  }

  /**
   * Add note for superusers on footerlinks inputfield
   */
  public function addFooterlinksNote(HookEvent $event)
  {
    if (!$this->wire->user->isSuperuser()) return;
    $f = $event->object;
    if ($f->name != self::field_footerlinks) return;
    if ($f->notes) $f->notes .= "\n";
    $f->notes .= "Superuser Note: Use \$rockfrontend->footerlinks() to access links as a PageArray in your template file (ready for foreach).";
  }

  private function addLiveReloadScript()
  {
    // get and minify the livereload script
    // dont worry, this will only be done for superusers ;)
    $file = $this->minifyFile($this->path . "livereload.js");
    $page = $this->wire->page;

    // for backend requests we need more caution
    // this is because live reload will break the module installation screen for example
    if ($page->template == 'admin') {
      // if livereload is disabled on backend pages we exit early
      if (!$this->livereloadBackend) return;

      // on module config screens we disable livereload if it is not explicitly
      // forced to be enabled. this is to prevent problems when downloading
      // and installing modules!
      if ($page->process == "ProcessModule" && !$this->liveReloadModules) return;
    }

    // if we got that far we add livereload to our site :)
    $this->scripts('rockfrontend')->add($file, "defer");
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
   * Show topbar with sitemap and edit and mobile preview
   */
  public function addTopBar(&$html)
  {
    if (!$this->isEnabled('topbar')) return;

    $page = $this->wire->page;
    if (!$page->editable()) return;
    if ($page->template == 'admin') return;
    if ($this->wire->input->get('rfpreview')) return;
    if ($this->wire->config->hideTopBar) return;

    /** @var RockMigrations $rm */
    $less = __DIR__ . "/bar/bar.less";
    /** @var RockMigrations $rm */
    $rm = $this->wire->modules->get('RockMigrations');
    if ($rm) $rm->saveCSS($less, minify: true);
    $css = $this->toUrl(__DIR__ . "/bar/bar.min.css", true);
    $style = "<link rel='stylesheet' href='$css'>";
    $html = str_replace("</head", "$style</head", $html);

    $topbar = $this->wire->files->render(__DIR__ . "/bar/topbar.php", [
      'rf' => $this,
      'logourl' => $this->toUrl(__DIR__ . "/RockFrontend.svg", true),
      'z' => is_int($this->topbarz) ? $this->topbarz : 999,
    ]);
    $html = str_replace("</body", "$topbar</body", $html);
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
   * Show alfred without plus-icons for adding a new block before/after
   * alfred($block, false);
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
    if ($options === false) {
      $options = [
        'addTop' => false,
        'addBottom' => false,
      ];
    } elseif (is_string($options)) $options = ['fields' => $options];

    // set flag to show that at least one alfred tag is on the page
    // this flag is used to load the PW frontend editing assets
    $this->hasAlfred = true;

    // set the page to be edited
    $p = false;
    if ($page) $p = $this->wire->pages->get((string)$page);
    if ($p instanceof Page and !$p->id) $p = false;
    $page = $p;

    // check if the current page is a RPB block
    // that can happen if RPB blocks are used as regular pages (nfkinder)
    // without this check we'd end up with RPB hover GUI for every
    // alfred($page) call which is not what we want
    // you can force showing the edit icon by alfred($page, ['noBlock' => true])
    // eg for editing a single images field of the current page without showing other icons
    $noBlock = array_key_exists('noBlock', $options);
    if (!$noBlock and $page instanceof Block and $page->id === $this->wire->page->id) {
      $page = false;
    }

    // setup options
    /** @var WireData $opt */
    $opt = $this->wire(new WireData());
    $opt->setArray([
      'fields' => '', // fields to edit
      'path' => $this->getTplPath(), // path to edit file
      'edit' => true,
      'blockid' => null,
    ]);
    $opt->setArray($options);

    // add quick-add-icons for rockpagebuilder
    if ($rpb = $this->wire->modules->get("RockPageBuilder")) {
      /** @var RockPageBuilder $rpb */
      $data = $this->wire(new WireData());
      $data->page = $page;
      $data->opt = $opt;
      $data->options = $options;
      $opt = $rpb->addAlfredOptions($data);
    }
    // bd($opt, 'opt after');

    // icons
    $icons = $this->getIcons($page, $opt);
    if (!count($icons)) return;

    $str = json_encode((object)[
      'icons' => $icons,
      'addTop' => $opt->addTop,
      'addBottom' => $opt->addBottom,
      'addLeft' => $opt->addLeft,
      'addRight' => $opt->addRight,
      'widgetStyle' => $opt->widgetStyle,
      'type' => $opt->type,
    ]);

    // entity encode alfred string
    // this is to avoid "invalid json" errors when using labels with apostrophes
    // like "Don't miss any updates"
    $str = $this->wire->sanitizer->entities1($str);

    // save markup to cache and generate alfred tag
    // the tag will be replaced on page render
    // this is to make it possible to use alfred() without |noescape filter)
    $id = uniqid();
    $str = " {$opt->blockid} alfred='$str'";
    $key = "#alfredcache-$id";
    $this->alfredCache->set($key, $str);

    return $key;
  }

  /**
   * Shortcut to create ALFRED links with horizontal add buttons
   * Thx @gebeer for the PR!!
   * @return string
   */
  public function alfredH($page = null, $options = [])
  {
    if ($options === false) {
      $options = [
        'addLeft' => false,
        'addRight' => false,
      ];
    }
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
   * Render a portion of HTML that needs consent from the user.
   *
   * This will replace all "src" attributes by "data-src" attributes.
   * All scripts will therefore only be loaded when the user clicks on the
   * consent button.
   *
   * Usage:
   * $rockfrontend->consent(
   *   'youtube',
   *   '<iframe src=...',
   *   '<a href=# rfc-allow=youtube>Allow YouTube-Player on this website</a>'
   * );
   *
   * You can also render files instead of markup:
   * $rockfrontend->consent(
   *   'youtube'
   *   'your youtube embed code',
   *   'sections/youtube-consent.latte'
   * );
   */
  public function consent($name, $enabled, $disabled = null)
  {
    $enabled = str_replace(" src=", " rfconsent='$name' rfconsent-src=", $enabled);
    if ($disabled) {
      // we only add the wrapper if we have a disabled markup
      // if we dont have a disabled markup that means we only have
      // a script tag (like plausible analytics) so we don't need the
      // wrapping div!
      $enabled = "<div data-rfc-show='$name' hidden>$enabled</div>";
      $file = $this->getFile($disabled);
      if ($file) $disabled = $this->render($file);
      $disabled = "<div data-rfc-hide='$name' hidden>$disabled</div>";
    }
    return $this->html($enabled . $disabled);
  }

  public function consentOptout($name, $script, $condition = true)
  {
    if (!$condition) return;
    $enabled = str_replace(" src=", " rfconsent='$name' rfconsent-type=optout rfconsent-src=", $script);
    return $this->html($enabled);
  }

  /**
   * Copy the appendFile to /site/templates
   */
  public function copyLayoutFileIfNewer()
  {
    if ($this->noLayoutFile) return;
    if (!$this->copyLayoutFile) return;
    $src = __DIR__ . "/stubs/_rockfrontend.php";
    $dst = $this->wire->config->paths->templates . "_rockfrontend.php";
    $msrc = @filemtime($src);
    $mdst = @filemtime($dst);
    $files = $this->wire->files;
    if ($msrc > $mdst) $files->copy($src, $dst);
  }

  /**
   * Create CSS from LESS file
   *
   * This will only be executed for superusers as it is intended to be used
   * on dev environments to parse module styles on the fly.
   *
   * Usage:
   * $rockfrontend->lessToCss("/path/to/file.less", $minify = true);
   */
  public function lessToCss($lessFile, $minify = true): void
  {
    if (!$this->wire->user->isSuperuser()) return;

    // get path of less file
    $lessFile = $this->getFile($lessFile);
    if (!is_file($lessFile)) throw new WireException("$lessFile not found");

    // get path of css file
    $css = substr($lessFile, 0, -5) . ".css";

    // if css file is newer we don't do anything
    if (@filemtime($css) > @filemtime($lessFile)) return;

    // we need to create CSS from less
    if (!$less = $this->wire->modules->get("Less")) return;
    /** @var Less $less */
    $less->addFile($lessFile);
    $less->saveCSS($css);

    // if minify option is true we minify the file
    // and return the path of the minified css file
    if ($minify) $this->minifyFile($css);
  }

  public function createCustomLess(HookEvent $event): void
  {
    // if the less field does not exist we exit early
    $lessField = $this->wire->fields->get(self::field_less);
    if (!$lessField) return;

    // if the less file already exists we have nothing to do
    // the less file will be deleted on page save
    $file = $this->lessFilePath();
    if (is_file($file)) return;
    $less = $this->wire->pages->get(1)->getFormatted(self::field_less);

    // make sure that the less directory exists
    $dir = $this->wire->config->paths->templates . "less";
    if (!is_dir($dir)) $this->wire->files->mkdir($dir);

    // write less content to file
    $this->wire->files->filePutContents($file, $less);
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
   * Load HtmlPageDom
   *
   * If you have plain HTML like from TinyMCE field some methods will not work
   * because they need a hierarchy with a single root element. That's why by
   * default the dom() method will add a wrapping div unless you specify
   * FALSE as second param.
   *
   * Usage:
   * $rockfrontend->dom("your html string")
   *   ->filter("img")
   *   ->each(function($img) {
   *     $img->attr('src', '/foo/bar.png');
   *   });
   */
  public function dom($data, $addWrapperDiv = true): HtmlPageCrawler
  {
    require_once __DIR__ . "/vendor/autoload.php";
    if ($addWrapperDiv) $data = "<div>$data</div>";
    return HtmlPageCrawler::create($data);
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
    $this->wire->files->rmdir($tpl . "uikit", true);
    foreach (glob($tpl . "uikit-*") as $dir) {
      $this->wire->files->rename($dir, $tpl . "uikit");
    }
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
   * Get all footerlinks
   */
  public function footerlinks(): PageArray
  {
    return $this->wire->pages->get(1)->get(self::field_footerlinks . "[]");
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
    if (strpos($file, "http://") === 0) return $file;
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
    if ($page and $opt->fields) {
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

    // icon to edit page
    if ($page and $page->editable() and $opt->edit) {
      $icons[] = (object)[
        'icon' => 'edit',
        'tooltip' => "Edit Block #{$page->id}",
        'href' => $page->editUrl() . $fields,
        'class' => 'pw-modal alfred-edit',
        'suffix' => 'data-buttons="button.ui-button[type=submit]" data-autoclose data-reload',
      ];
    }

    // add rockpagebuilder icons
    if ($page) {
      $rpb = $this->wire->modules->get("RockPageBuilder");
      if ($page instanceof Block) $page->addAlfredIcons($icons, $opt);
      elseif ($page instanceof RepeaterPage and $rpb) {
        $rpb->addAlfredIcons($page, $icons, $opt);
      }
    }

    if ($this->wire->user->isSuperuser()) {
      // view file edit link
      $icons[] = (object)[
        'icon' => 'code',
        'label' => $opt->path,
        'href' => $this->vscodeLink($opt->path),
        'tooltip' => $opt->path,
      ];

      $ext = pathinfo($opt->path, PATHINFO_EXTENSION);

      // php file edit link
      $php = substr($opt->path, 0, strlen($ext) * -1 - 1) . ".php";
      if (is_file($php)) {
        $icons[] = (object)[
          'icon' => 'php',
          'label' => $php,
          'href' => $this->vscodeLink($php),
          'tooltip' => $php,
        ];
      }
      // style edit link
      $less = substr($opt->path, 0, strlen($ext) * -1 - 1) . ".less";
      if (is_file($less)) {
        $icons[] = (object)[
          'icon' => 'eye',
          'label' => $less,
          'href' => $this->vscodeLink($less),
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
    if ($this->liveReload) return $this->liveReload;
    require_once __DIR__ . "/LiveReload.php";
    return $this->liveReload = new LiveReload();
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
      if (is_readable($dir) and is_dir($dir)) return rtrim($dir, '/') . '/';
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

      // first check for latte cache files
      // these files are .php files compiled from the original .latte file
      // we use these files because that also works when using latte "include" statements
      if (str_contains($file, ".latte--") and str_ends_with($file, ".php")) {
        // the template file seems to be a latte file
        // get source file from the cached content
        $content = file_get_contents($file);
        $pattern = '/\/\*\* source: (.+?) \*\//s';
        if (preg_match($pattern, $content, $matches)) {
          $sourceFile = $matches[1];
          return $sourceFile;
        }
      }

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
  public function getUikitVersions($noCache = false)
  {
    $expire = 60 * 5;
    if ($noCache) $expire = WireCache::expireNow;
    $versions = $this->wire->cache->get(self::cache, $expire, function () {
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
    if ($versions) return $versions;
    if (!$noCache) return $this->getUikitVersions(true);
    return [];
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
      require_once __DIR__ . "/vendor/autoload.php";
      return new Html($str);
    } catch (\Throwable $th) {
      return $str;
    }
  }

  function humandates($locale = "de_AT"): HumanDates
  {
    require_once __DIR__ . "/HumanDates.php";
    return new HumanDates($locale);
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
    return "<div class='{$opt->wrapClass}' style='{$opt->style}'>
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
      return preg_replace_callback("/rfGrow\((.*?),(.*?)(,(.*?))?(,(.*?))?\)/", function ($match) {
        // bd($match);
        if (count($match) < 3) return false;
        try {
          $data = [
            'min' => $match[1],
            'max' => $match[2],
          ];
          if (count($match) > 4) $data['growMin'] = $match[4];
          if (count($match) > 6) $data['growMax'] = $match[6];
          return $this->rfGrow($data);
        } catch (\Throwable $th) {
          return $th->getMessage();
        }
      }, $markup);
    });

    // rfShrink() postCSS replacer
    $data->set("rfShrink(", function ($markup) {
      return preg_replace_callback("/rfShrink\((.*?),(.*?)(,(.*?))?(,(.*?))?\)/", function ($match) {
        // bd($match);
        if (count($match) < 3) return false;
        try {
          $data = [
            'min' => $match[2],
            'max' => $match[1],
          ];
          if (count($match) > 4) $data['growMin'] = $match[4];
          if (count($match) > 6) $data['growMax'] = $match[6];
          return $this->rfGrow($data, true);
        } catch (\Throwable $th) {
          return $th->getMessage();
        }
      }, $markup);
    });

    // convert pxrem to px
    // font-size: 20pxrem; --> font-size: 1.25rem;
    $data->set("pxrem", function ($markup) {
      return preg_replace_callback("/([0-9\.]+)(pxrem)/", function ($matches) {
        $px = $matches[1];
        $rem = round($px / $this->remBase, 3);
        return $rem . "rem";
      }, $markup);
    });

    $this->postCSS = $data;
  }

  private function injectAssets(string &$html): void
  {
    $assets = '';
    foreach ($this->autoloadScripts as $script) $assets .= $script->render();
    foreach ($this->autoloadStyles as $style) $assets .= $style->render();
    $html = str_replace("</head>", "$assets</head>", $html);
  }

  private function injectJavascriptSettings(string &$html): void
  {
    // at the very end we inject the js variables
    if (!count($this->js)) return;
    $json = json_encode($this->js);
    $markup = "<script>var RockFrontend = $json</script>";
    $html = str_replace("</head>", "$markup</head>", $html);
  }

  /**
   * Install less module for the pagebuilder profile
   */
  public function installLessModule(Page $page)
  {
    if ($this->wire->modules->get('Less')) return;

    // less module is not installed
    // if rockmigrations is installed we use it to install the less module
    /** @var RockMigrations $rm */
    $rm = $this->wire->modules->get('RockMigrations');
    if ($rm) {
      $rm->installModule("Less", "https://github.com/ryancramerdesign/Less/archive/refs/heads/main.zip");
      return $this->renderLayout($page);
    }

    // rockmigrations not installed, show info to install less manually
    return "<h1 style='text-align:center;padding:50px;color:red;'>Please install the Less module to use this profile!</h1>";
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
   * Get path to latte layout file
   */
  public function latteLayoutFile()
  {
    if ($this->noLayoutFile) return false;
    $tpl = rtrim($this->wire->config->paths->templates, "/");
    $layoutFile = ltrim($this->layoutFile, "/");
    if ($layoutFile) return "$tpl/$layoutFile";
    return "$tpl/layout.latte";
  }

  /**
   * Return layout suggestions
   */
  public function layoutSuggestions(HookEvent $event)
  {
    return $this->findSuggestFiles($event->q);
  }

  private function lessFilePath(): string
  {
    return $this->wire->config->paths->templates . "less/rf-custom.less";
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
   * Get the script tag that is necessary for livereload to be present
   */
  public function livereloadScriptTag(): string
  {
    $file = $this->minifyFile($this->path . "livereload.js");
    $url = $this->url($file, true);
    return "<script>var RockFrontend;</script><script src=$url defer></script>";
  }

  /**
   * Load ALFRED assets?
   */
  public function loadAlfred(): bool
  {
    if (!$this->hasAlfred) return false;
    $permission = false;
    if ($this->wire->user->isSuperuser()) $permission = true;
    if ($this->wire->user->hasPermission(self::permission_alfred)) $permission = true;
    return $permission and $this->hasAlfred;
  }

  /**
   * @return Engine
   */
  public function loadLatte($withLayout = false)
  {
    if ($withLayout && $this->latteWithLayout) return $this->latteWithLayout;
    if ($this->latte) return $this->latte;

    try {
      require_once __DIR__ . "/translate.php";
      require_once $this->path . "vendor/autoload.php";

      $latte = new Engine;
      $latte->setTempDirectory($this->wire->config->paths->cache . "Latte");
      if ($this->wire->modules->isInstalled("TracyDebugger")) {
        $latte->addExtension(new \Latte\Bridges\Tracy\TracyExtension());
      }

      // latte with layout was requested
      if ($withLayout) {
        $latte->addProvider(
          'coreParentFinder',
          function (\Latte\Runtime\Template $template) {
            // if no {layout} is set in the template we use the default
            if (!$template->getReferenceType()) {
              // this returns /site/templates/layout.latte by default
              return $this->latteLayoutFile();
            }
          }
        );
        return $this->latteWithLayout = $latte;
      }

      return $this->latte = $latte;
    } catch (\Throwable $th) {
      $this->log($th->getMessage());
      return false;
    }
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
    $this->migrateLess();
    $this->migrateImages();
    $this->migrateFavicon();
    $this->migrateOgImage();
    $this->migrateFooterlinks();
    $this->mgirateLatteTranslations();
    // $this->migrateLayoutField();
  }

  private function migrateFavicon()
  {
    if (!in_array("favicon", $this->migrations)) return;
    $rm = $this->rm();
    $rm->migrate([
      'fields' => [
        self::field_favicon => [
          'type' => 'image',
          'label' => 'Favicon',
          'maxFiles' => 1,
          'descriptionRows' => 0,
          'extensions' => 'png',
          'maxSize' => 3, // max 3 megapixels
          'icon' => 'picture-o',
          'outputFormat' => FieldtypeFile::outputFormatSingle,
          'description' => 'For best browser support and quality upload a high resolution PNG (min 512x512). You can use transparency in your favicon.',
          'notes' => '[See here](https://loqbooq.app/blog/add-favicon-modern-browser-guide) and [here](https://css-tricks.com/svg-favicons-and-all-the-fun-things-we-can-do-with-them/) to learn more about favicons',
          'tags' => self::tags,
        ],
      ],
    ]);
    $rm->addFieldToTemplate(self::field_favicon, 'home');
  }

  private function migrateFooterlinks()
  {
    if (!in_array("footerlinks", $this->migrations)) return;
    $rm = $this->rm();
    $rm->migrate([
      'fields' => [
        self::field_footerlinks => [
          'type' => 'page',
          'label' => 'Footer-Menu',
          'derefAsPage' => FieldtypePage::derefAsPageArray,
          'inputfield' => 'InputfieldPageListSelectMultiple',
          'findPagesSelector' => 'id>0,template!=admin',
          'labelFieldName' => 'title',
          'tags' => self::tags,
        ],
      ],
    ]);
    $rm->addFieldToTemplate(self::field_footerlinks, 'home');
  }

  private function migrateImages()
  {
    if (!in_array("images", $this->migrations)) return;
    $rm = $this->rm();
    $rm->migrate([
      'fields' => [
        self::field_images => [
          'type' => 'image',
          'label' => 'Media',
          'maxFiles' => 0,
          'descriptionRows' => 0,
          'columnWidth' => 50,
          'extensions' => 'png jpg jpeg svg',
          'okExtensions' => ['svg'],
          'maxSize' => 3, // max 3 megapixels
          'icon' => 'picture-o',
          'outputFormat' => FieldtypeFile::outputFormatArray,
          'description' => 'Images that can be included somewhere (eg in Hanna Codes).',
          'tags' => self::tags,
          'notes' => 'API: $home->images()->get("name=foo.jpg");',
        ],
      ],
    ]);
    $rm->addFieldToTemplate(self::field_images, 'home');
  }

  private function mgirateLatteTranslations()
  {
    if (!in_array("lattetranslations", $this->migrations)) return;
    /** @var RockMigrations $rm */
    $rm = $this->wire->modules->get('RockMigrations');
    $ext = $rm->getModuleConfig('ProcessLanguageTranslator', 'extensions');
    if (strpos((string)$ext, "latte") !== false) return;
    $rm->setModuleConfig("ProcessLanguageTranslator", ['extensions' => "$ext latte"]);
  }

  private function migrateLess()
  {
    if (!in_array("less", $this->migrations)) return;
    $rm = $this->rm();
    $rm->migrate([
      'fields' => [
        self::field_less => [
          'type' => 'textarea',
          'label' => 'LESS',
          'rows' => 15,
          'icon' => 'css3',
          'collapsed' => Inputfield::collapsedYes,
          'notes' => 'This feature is experimental',
        ],
      ],
    ]);
    $rm->addFieldToTemplate(self::field_less, 'home');
  }

  private function migrateOgImage()
  {
    if (!in_array("ogimage", $this->migrations)) return;
    $rm = $this->rm();
    $rm->migrate([
      'fields' => [
        self::field_ogimage => [
          'type' => 'image',
          'label' => 'og:image',
          'maxFiles' => 1,
          'descriptionRows' => 0,
          'columnWidth' => 50,
          'extensions' => 'png jpg jpeg',
          'maxSize' => 3, // max 3 megapixels
          'icon' => 'picture-o',
          'outputFormat' => FieldtypeFile::outputFormatSingle,
          'description' => 'Here you can add the fallback og:image that will be used by RockFrontend\'s SEO-Tools.',
          'tags' => self::tags,
        ],
      ],
    ]);
    $rm->addFieldToTemplate(self::field_ogimage, 'home');
  }

  /**
   * Minify file and return path of minified file
   */
  public function minifyFile($file, $minFile = null): string
  {
    $file = new Asset($file);
    if (!$minFile) $minFile = $file->minPath();
    $minFile = new Asset($minFile);
    if ($minFile->m < $file->m) {
      require_once __DIR__ . "/vendor/autoload.php";
      if ($file->ext == 'js') $minify = new \MatthiasMullie\Minify\JS($file);
      else $minify = new \MatthiasMullie\Minify\CSS($file);
      $minify->minify($minFile->path);
    }
    return $minFile->path;
  }

  /**
   * Get other's language url of current page
   * For super-simple language switchers, see here:
   * https://processwire.com/talk/topic/12243-language-switcher-on-front-end/?do=findComment&comment=178873
   *
   * Provide true to add url segment string:
   * $page->otherLangUrl(true);
   */
  public function otherLangUrl(HookEvent $event): void
  {
    $page = $event->object;
    $lang = $this->wire->languages->findOther()->first();
    $url = $page->localUrl($lang);
    if ($event->arguments(0)) $url .= $this->wire->input->urlSegmentStr();
    $event->return = $url;
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
    // refresh uikit cache
    $this->wire->cache->save(self::cache, "");

    // force recreation of assets
    $dir = $this->wire->config->paths->assets . "RockFrontend/css/";
    if (is_dir($dir)) $this->wire->files->rmdir($dir, true);
    $this->forceRecompile();

    // block direct access to template and less files
    $this->updateHtaccess();
  }

  /**
   * Convert px to rem
   */
  public function rem($value): RemData
  {
    require_once __DIR__ . "/RemData.php";
    $value = strtolower(trim($value));
    preg_match("/(.*?)([a-z]+)/", $value, $matches);
    $val = trim($matches[1]);
    $unit = trim($matches[2]);

    $data = $this->wire(new RemData());
    $data->val = $val;
    $data->unit = $unit;

    if ($unit !== 'pxrem') return $data;

    // convert pixel to rem
    $data->val = round($val / $this->remBase, 3);
    $data->unit = 'rem';

    return $data;
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
    if ($ext == 'php' or $ext == 'html') {
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

    return $this->html($html);
  }

  /**
   * LATTE renderer
   */
  protected function renderFileLatte($file, $vars)
  {
    // should we load latte with layout file or without?
    // for regular page rendering we want the auto-prepend-layout feature
    // but for RockPdf rendering we don't want it.
    if ($this->noLayoutFile) $withLayout = false;
    else {
      $withLayout = $file === __DIR__ . "/default.latte"
        || dirname($file) . "/" === $this->wire->config->paths->templates;
    }

    // load latte and return rendered file
    $latte = $this->loadLatte($withLayout);
    if (!$latte) throw new WireException("Unable to load Latte");
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
      $twig = new \Twig\Environment($loader, [
        'debug' => true,
      ]);
      $twig->addExtension(new \Twig\Extension\DebugExtension());
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
   * Get markup of a single view file
   */
  public function ___view(string $file): Html|string
  {
    $file = $this->viewFile($file);
    $markup = $this->render($file);
    return $this->html($markup);
  }

  /**
   * Check if main content file exists and if not throw a 404
   */
  public function viewCheck404(): void
  {
    $file = $this->wire->input->urlSegmentStr;
    if (!$this->viewFile("main/$file")) throw new Wire404Exception("Page not found");
  }

  private function viewFile(string $file): string|false
  {
    $file = Paths::normalizeSeparators($file);
    foreach ($this->viewfolders as $folder) {
      $folder = Paths::normalizeSeparators($folder);
      $path = $this->wire->config->paths->root . trim($folder, "/") . "/";
      $f = $this->getFile($path . ltrim($file, "/"));
      if (is_file($f)) return $f;
    }
    return false;
  }

  /**
   * View files in folders based on the url segment string of a page
   *
   * Usage:
   * echo $rf->viewFolders([
   *   '/site/templates/foo',
   *   '/site/modules/MyModule/frontend',
   * ], [
   *   // options
   * ]);
   */
  public function viewFolders(array $folders, array $options = []): Html|string
  {
    // prepare options
    $opt = new WireData();
    $opt->setArray([
      'removeMainStyles' => true,
      'trailingslash' => false,
      'entry' => '_main.php',
    ]);
    $opt->setArray($options);

    // save folders for later
    $this->viewfolders = $folders;

    // check trailing slash setting and redirect if needed
    $this->viewTrailingSlash($opt->trailingslash);

    // remove all styles that have been added to the main styles array
    // this is because the mail style array is for the main website
    // and we usually don't need it for custom frontends
    if ($opt->removeMainStyles) $this->styles('main')->removeAll();

    // render the main markup file
    return $this->view($opt->entry);
  }

  private function viewTrailingSlash(bool $slash): void
  {
    // we only check this if we have an url segment
    // otherwise it's a regular page request to the rootpage
    // in that case we use the page's native slash setting
    if (!$this->wire->input->urlSegmentStr) return;
    $session = $this->wire->session;

    $url = $this->wire->input->url;
    $query = $this->wire->input->queryString();

    $hasSlash = str_ends_with($url, "/");
    if ($slash and !$hasSlash) $session->redirect("$url/$query");
    if (!$slash and $hasSlash) $session->redirect(rtrim($url, "/") . "?" . $query);
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
  public function renderLayout(Page $page = null, $fallback = [], $noMerge = false)
  {
    if (!$page) $page = $this->wire->page;
    $defaultFallback = [
      "layouts/{$page->template}",
      "layouts/default",
    ];

    // by default we will merge the default array with the array
    // provided by the user
    if (!$noMerge) $fallback = $fallback + $defaultFallback;

    // if a static file matches the url of the requested page we return that one
    $static = $this->renderStaticFile($page);
    if ($static) return $static;

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

  public function renderPagerUIkit(PageArray $items, $options = [])
  {
    $options = array_merge([
      'numPageLinks' => 5,
      'listMarkup' => "<ul class='uk-pagination uk-flex-center'>{out}</ul>",
      'nextItemLabel' => '<span uk-pagination-next></span>',
      'previousItemLabel' => '<span uk-pagination-previous></span>',
      'currentItemClass' => 'uk-active',
    ], $options);
    return $this->html($items->renderPager($options));
  }

  /**
   * Render file from /site/templates/static for given page
   */
  public function renderStaticFile(Page $page): string|false
  {
    $file = $this->wire->config->paths->templates . "static" . $page->url;
    $file = rtrim($file, "/");
    $content = $this->render($file);
    if ($content) return $content;
    return false;
  }

  public function resetCustomLess(HookEvent $event): void
  {
    /** @var Page $page */
    $page = $event->object;
    $field = $event->arguments(0);
    if ($field != self::field_less) return;
    $file = $this->lessFilePath();
    $this->wire->files->unlink($file);
    $newValue = $event->arguments(2);
    $this->wire->files->filePutContents($file, $newValue);
  }

  public function rfGrow($_data, $shrink = false): string
  {
    if (is_string($_data)) {
      $tmp = explode(",", $_data);
      $_data = [
        'min' => trim($tmp[0]),
        'max' => trim($tmp[1]),
      ];
    }
    if (!is_array($_data)) {
      // bd(Debug::backtrace());
      throw new WireException("data for rfGrow must be an array");
    }
    $data = new WireData();
    $data->setArray([
      'min' => null,
      'max' => null,
      'growMin' => $this->wire->config->growMin ?: 360,
      'growMax' => $this->wire->config->growMax ?: 1440,
      'scale' => 1,
    ]);
    $data->setArray($_data);

    $scale = $data->scale;

    // prepare growmin and growmax values
    // we remove px to make sure we can use less variables in rfGrow()
    // eg: @min = 360px; @max = 1440px;
    // rfGrow(20, 50, @min, @max);
    $growMin = str_replace("px", "", $data->growMin);
    $growMax = str_replace("px", "", $data->growMax);

    $min = $this->rem($data->min);
    $max = $this->rem($data->max);
    if ($min->unit !== $max->unit) throw new WireException(
      "rfGrow(error: min and max value must have the same unit)"
    );

    $diff = $max->val - $min->val;
    if ($max->unit == 'rem') $diff = $diff * $this->remBase;
    // return $min;

    $percent = "((100vw - {$growMin}px) / ($growMax - $growMin))";
    if ($shrink) {
      $grow = "$max - $diff * $percent";
      return "clamp($min, $grow, $max)";
    } else {
      // if scale is one we return a nicer syntax
      if ($scale === 1 or $scale === '1') {
        return "clamp($min, $min + $diff * $percent, $max)";
      }
      $grow = "$min * $scale + $diff * $scale * $percent";
      return "clamp($min * $scale, $grow, $max * $scale)";
    }
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
   * $rockfrontend->scripts('main')->add(...);
   * // file2.php
   * $rockfrontend->scripts('main')->add(...);
   * // _main.php
   * $rockfrontend->scripts('main')->render();
   *
   * @return ScriptsArray
   */
  public function scripts($name = 'main')
  {
    $name = trim($name);
    if (!$name) $name = 'main';
    if (!$this->scripts) $this->scripts = new WireData();
    $script = $this->scripts->get("rockfrontend-script-$name") ?: new ScriptsArray($name);
    $this->scripts->set("rockfrontend-script-$name", $script);
    return $script;
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
  public function styles($name = 'main', $cssDir = null)
  {
    $name = trim($name);
    if (!$name) $name = 'main';
    if (!$this->styles) $this->styles = new WireData();
    $style = $this->styles->get("rf-style-$name") ?: new StylesArray($name);
    if ($name) $this->styles->set("rf-style-$name", $style);
    if ($cssDir) $style->cssDir = $cssDir;
    return $style;
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
   * Update /site/templates/.htaccess file to block direct access
   * to latte/twig/blade/less files
   * Triggered on every modules::refresh
   * Thx to @netcarver
   */
  private function updateHtaccess()
  {
    $file = $this->wire->config->paths->templates . ".htaccess";
    if (!is_file($file)) $this->wire->files->filePutContents($file, "");
    $content = $this->wire->files->fileGetContents($file);
    $rules = $this->wire->files->fileGetContents(__DIR__ . "/stubs/htaccess.txt");
    $err = "/site/templates/.htaccess not writeable - some template files might be publicly accessible!";
    if (!strpos($content, "# RockFrontend: ")) {
      // add rockfrontend rules
      if (is_writable($file)) {
        $this->wire->files->filePutContents($file, $rules, FILE_APPEND);
      } else $this->error($err);
    } elseif (!strpos($content, $rules)) {
      // update rockfrontend rules
      $newcontent = preg_replace(
        "/# RockFrontend: (.*)# End RockFrontend/s",
        $rules,
        $content
      );
      if (is_writable($file)) {
        $this->wire->files->filePutContents($file, $newcontent);
      } else $this->error($err);
    }
  }

  public function vscodeLink($path)
  {
    $tracy = $this->wire->config->tracy;
    if (is_array($tracy) and array_key_exists('localRootPath', $tracy))
      $root = $tracy['localRootPath'];
    else $root = $this->wire->config->paths->root;
    $link = str_replace($this->wire->config->paths->root, $root, $path);
    $link = Paths::normalizeSeparators($link);
    return "vscode://file/" . ltrim($link, "/");
  }

  /** translation support in LATTE files */

  public function _($str)
  {
    return \ProcessWire\__($str, $this->textdomain());
  }

  public function _x($str, $context)
  {
    return \ProcessWire\_x($str, $context, $this->textdomain());
  }

  public function _n($textsingular, $textplural, $count)
  {
    return \ProcessWire\_n($textsingular, $textplural, $count, $this->textdomain());
  }

  public function setTextdomain($file = false)
  {
    $this->textdomain = $file;
  }

  /**
   * Method to find the correct textdomain file for translations in latte files
   */
  public function textdomain()
  {
    $trace = Debug::backtrace();
    if ($this->textdomain) return $this->textdomain;

    // loop the backtrace to find the file where the translation happens
    foreach ($trace as $item) {
      $call = $item['call'];
      $file = explode(":", $item['file'], 2)[0];

      // no latte file? continue!
      if (!strpos($file, ".latte--")) continue;

      // we have a translation in a latte file
      if (
        str_starts_with($call, '$rockfrontend->_(')
        || str_starts_with($call, '__(')
        || str_starts_with($call, '_x(')
        || str_starts_with($call, '_n(')
      ) {
        // get the sourcefile from the note in the cached file
        $content = file_get_contents($this->toPath($file));
        $from = strpos($content, "/** source: ") + 12;
        $to = strpos($file, ".latte--") - 5;
        $templateFile = substr($content, $from, $to);
        return $templateFile;
      }
    }
    return false;
  }

  /** END translation support in LATTE files */

  /**
   * Make sure that the given file/directory path is absolute
   * This will NOT check if the directory or path exists!
   * It will always prepend the PW root directory so this method does not work
   * for absolute paths outside of PW!
   */
  public function toPath($url): string
  {
    $url = $this->toUrl($url);
    return $this->wire->config->paths->root . ltrim($url, "/");
  }

  /**
   * Make sure that the given file/directory path is relative to PW root
   * This will NOT check if the directory or path exists!
   * If provided a path outside of PW root it will return that path because
   * the str_replace only works if the path starts with the pw root path!
   */
  public function toUrl($path, $cachebuster = false): string
  {
    $cache = '';
    if ($cachebuster) {
      $path = $this->toPath($path);
      if (is_file($path)) $cache = "?m=" . filemtime($path);
    }
    return str_replace(
      $this->wire->config->paths->root,
      $this->wire->config->urls->root,
      Paths::normalizeSeparators((string)$path) . $cache
    );
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

  /** ##### module config ##### */

  /**
   * Config inputfields
   * @param InputfieldWrapper $inputfields
   */
  public function getModuleConfigInputfields($inputfields)
  {
    $this->migrate();

    $name = strtolower($this);
    $inputfields->add([
      'type' => 'markup',
      'label' => 'Documentation & Updates',
      'icon' => 'life-ring',
      'value' => "<p>Hey there, coding rockstars! 👋</p>
        <ul>
          <li><a href=https://www.baumrock.com/en/processwire/modules/$name/docs>Read the docs</a> and level up your coding game! 🚀💻😎</li>
          <li><a href=https://github.com/baumrock/$name>Show some love by starring the project</a> and keep us motivated to build more awesome stuff for you! 🌟💻😊</li>
          <li><a href=https://www.baumrock.com/rock-monthly>Sign up now for our monthly newsletter</a> and receive the latest updates and exclusive offers right to your inbox! 🚀💻📫</li>
        </ul>",
    ]);

    $config = $this->wire->config;
    $config->styles->add($config->urls($this) . "RockFrontend.module.css");

    $this->configLivereload($inputfields);
    $this->configLatte($inputfields);
    $this->configSettings($inputfields);
    $this->configTools($inputfields);

    return $inputfields;
  }

  private function configLatte(InputfieldWrapper $inputfields): void
  {
    $hasLatteFiles = $this->wire->files->find(
      $this->wire->config->paths->templates,
      ['extensions' => ['latte']]
    );
    $fs = new InputfieldFieldset();
    $fs->label = "Latte";
    $fs->icon = "code";
    $fs->collapsed = $hasLatteFiles
      ? Inputfield::collapsedNo
      : Inputfield::collapsedYes;
    $inputfields->add($fs);

    $f = new InputfieldCheckbox();
    $f->name = "noLayoutFile";
    $f->label = "Disable Autoload-Layout";
    $f->attr('checked', $this->noLayoutFile);
    $f->columnWidth = 50;
    $fs->add($f);

    $f = new InputfieldCheckbox();
    $f->name = "copyLayoutFile";
    $f->entityEncodeLabel = false;
    $f->label = "Copy file <i class='uk-background-muted' style='padding: 5px 10px;'>_rockfrontend.php</i> to /site/templates";
    $f->attr('checked', $this->copyLayoutFile);
    $f->columnWidth = 50;
    $f->showIf = "noLayoutFile=0";
    $f->notes = 'Make sure to also set this in /site/config.php:
      $config->appendTemplateFile = "_rockfrontend.php";';
    $fs->add($f);

    $dir = $this->wire->config->paths->templates;
    $f = new InputfieldText();
    $f->name = 'layoutFile';
    $f->label = 'Filename of Autoload-Layout';
    $f->icon = 'file-code-o';
    $f->value = $this->layoutFile ?: 'layout.latte';
    $f->notes = "File relative to $dir";
    $f->showIf = "noLayoutFile=0";
    $fs->add($f);
  }

  private function configLivereload(InputfieldWrapper $inputfields)
  {
    $fs = new InputfieldFieldset();
    $fs->label = "LiveReload";
    $fs->icon = "refresh";
    $inputfields->add($fs);

    $f = new InputfieldMarkup();
    if ($live = $this->wire->config->livereload) {
      if (is_numeric($live)) $value = "LiveReload is enabled (via config.php) - Interval: $live seconds.";
      else $value = var_export($live, true);
    } else $value = 'LiveReload is disabled. To enable it set this in your config.php:
      <pre class="uk-margin-small-top uk-margin-remove-bottom">$config->livereload = 1;</pre>';
    $f->value = $value;
    $f->notes = "LiveReload saved you from hitting refresh <span id=live-cnt></span> times on this device :)";
    $f->entityEncodeText = false;
    $f->appendMarkup = "<script>document.getElementById('live-cnt').innerText = localStorage.getItem('livereload-count') || 0;</script>";
    $fs->add($f);

    // early exit if live reload is disabled
    if (!$live) return;

    $fs->add([
      'type' => 'checkbox',
      'name' => 'livereloadBackend',
      'label' => 'Add livereload to backend pages',
      'checked' => $this->livereloadBackend ? 'checked' : '',
      'columnWidth' => 50,
      'notes' => 'Really handy when working with RockMigrations!',
    ]);

    $fs->add([
      'type' => 'checkbox',
      'name' => 'liveReloadModules',
      'label' => 'Add livereload to module pages',
      'checked' => $this->liveReloadModules ? 'checked' : '',
      'columnWidth' => 50,
      'notes' => 'Caution: LiveReload on module pages can cause problems, '
        . 'for example module installations might not work. Enable this only '
        . 'when needed!',
    ]);
  }

  private function configSettings($inputfields)
  {
    $fs = new InputfieldFieldset();
    $fs->label = "Settings";
    $fs->icon = "cogs";
    $inputfields->add($fs);

    $f = new InputfieldText();
    $f->label = 'Webfonts';
    $f->name = 'webfonts';
    $f->icon = "font";
    $f->description = "Enter url to webfonts ([fonts.google.com](https://fonts.google.com/)). These webfonts will automatically be downloaded to /site/templates/webfonts and a file webfonts.less will be created with the correct paths. The download will only be triggered when the URL changed and it will wipe the fonts folder before download so that unused fonts get removed."
      . "\nExample URL: https://fonts.googleapis.com/css2?family=Ubuntu:wght@400;700 (see here: [screenshot](https://i.imgur.com/b8aJPQW.png))";
    $f->value = $this->webfonts;
    $f->notes = $this->showFontFileSize();
    $fs->add($f);

    $f = $this->wire->modules->get('InputfieldCheckboxes');
    $f->name = 'features';
    $f->label = "Features";
    $f->icon = "star-o";
    $f->addOption('RockFrontend.js', 'RockFrontend.js - Load this file on the frontend (eg to use consent tools).');
    $f->addOption('postCSS', 'postCSS - Use the internel postCSS feature (eg to use rfGrow() syntax).');
    $f->addOption('minify', 'minify - Auto-create minified CSS/JS assets ([see docs](https://github.com/baumrock/RockFrontend/wiki/Minify-Feature)).');
    $f->addOption('topbar', 'topbar - Show topbar (sitemap, edit page, toggle mobile preview).');
    $f->value = (array)$this->features;
    $fs->add($f);

    $f = new InputfieldInteger();
    $f->name = 'topbarz';
    $f->label = 'Topbar Z-Index';
    $f->initValue = 999;
    $f->value = $this->topbarz;
    $f->showIf = 'features=topbar';
    $f->notes = 'Default is 999';
    $fs->add($f);

    $f = $this->wire->modules->get('InputfieldCheckboxes');
    $f->name = 'migrations';
    $f->label = "Migrations";
    $f->icon = "rocket";
    $f->addOption('favicon', 'favicon - Create an image field for a favicon and add it to the home template');
    $f->addOption('ogimage', 'ogimage - Create an image field for an og:image and add it to the home template');
    $f->addOption('images', 'images - Create an image field for general image uploads and add it to the home template');
    $f->addOption('footerlinks', 'footerlinks - Create a page field for selecting pages for the footer menu and add it to the home template');
    $url = $this->wire->pages->get(2)->url . "module/edit?name=ProcessLanguageTranslator";
    $f->addOption('lattetranslations', "lattetranslations - Add latte extension to translatable files in [ProcessLanguageTranslator]($url)");
    $f->addOption('less', "less - Add textarea to inject custom LESS/CSS");
    $f->value = (array)$this->migrations;
    $f->notes = "Note that removing a checkbox does not undo an already executed migration!";
    if (!$this->wire->modules->isInstalled("RockMigrations")) {
      $f->prependMarkup = "<div class='uk-alert uk-alert-warning uk-margin-remove'>These features will only work if RockMigrations is installed!</div>";
      $f->collapsed = Inputfield::collapsedYes;
    }
    $fs->add($f);
  }

  private function configTools(&$inputfields)
  {
    $fs = new InputfieldFieldset();
    $fs->label = "Tools";
    $fs->icon = "wrench";

    $this->manifestConfig($fs);

    $f = new InputfieldText();
    $f->name = "postCssTool";
    $f->label = "PostCSS";
    $f->notes = "Enter rfGrow/rfShrink CSS and save the page then you'll get the transformed CSS to copy paste.
      Eg rfGrow(10px, 100px);";
    $f->collapsed = 1;
    $f->value = $this->postCssTool;
    if ($f->value) {
      $f->collapsed = 0;
      $f->notes = $this->postCSS($f->value);
    }
    $fs->add($f);

    $this->profileExecute();
    $f = new InputfieldSelect();
    $f->label = "Install Profile";
    $f->name = 'profile';
    $f->collapsed = Inputfield::collapsedYes;
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
    $f->prependMarkup = "<p class='uk-text-warning'>WARNING: This will overwrite existing files in /site/templates - make sure to have backups or use GIT for version controlling your project!</p>";
    $f->prependMarkup .= $accordion;
    $f->notes = $this->profileInstalledNote();
    $fs->add($f);

    // download uikit
    $this->downloadUikit();
    $f = new InputfieldSelect();
    $f->name = 'uikit';
    $f->label = 'Download UIkit';
    $f->collapsed = Inputfield::collapsedYes;
    $f->notes = "WARNING: This will wipe the folder /site/templates/uikit and then download the selected uikit version into that folder!";
    foreach ($this->getUikitVersions() as $k => $v) $f->addOption($k);
    $fs->add($f);

    $this->downloadCDN();
    $f = new InputfieldMarkup();
    $f->name = 'cdn';
    $f->label = 'CDN-Downloader';
    $f->collapsed = Inputfield::collapsedYes;
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
    $fs->add($f);

    // webfont downloader
    $data = $this->downloadWebfont();
    if ($data->suggestedCss) {
      $f = new InputfieldMarkup();
      $f->label = 'Suggested CSS';
      $f->description = "You can copy&paste the created CSS into your stylesheet. The paths expect it to live in /site/templates/layouts/ - change the path to your needs!
          See [https://css-tricks.com/snippets/css/using-font-face-in-css/](https://css-tricks.com/snippets/css/using-font-face-in-css/) for details!";
      $f->value = "<pre style='max-height:400px;'><code>{$data->suggestedCss}</code></pre>";
      $f->notes = "Data above is stored in the current session and will be reset on logout";
      $fs->add($f);
    }
    if ($data->rawCss) {
      $f = new InputfieldMarkup();
      $f->label = 'Raw CSS (for debugging)';
      $f->value = "<pre style='max-height:400px;'><code>{$data->rawCss}</code></pre>";
      $f->notes = "Data above is stored in the current session and will be reset on logout";
      $f->collapsed = Inputfield::collapsedYes;
      $fs->add($f);
    }
    $inputfields->add($fs);
  }

  private function manifestConfig(InputfieldWrapper $fs)
  {
    $wrapper = new InputfieldFieldset();
    $wrapper->label = 'Manifest File';
    $wrapper->collapsed = Inputfield::collapsedYes;
    $create = false;

    $wrapper->add([
      'type' => 'markup',
      'label' => 'Docs',
      'value' => 'RockFrontend Docs: <a href=https://www.baumrock.com/modules/RockFrontend/docs/seo/#website-manifest-file">https://www.baumrock.com/modules/RockFrontend/docs/seo/#website-manifest-file</a>
        <br>Mozilla Reference: <a href=https://developer.mozilla.org/en-US/docs/Web/Manifest>https://developer.mozilla.org/en-US/docs/Web/Manifest</a>',
    ]);

    $wrapper->add([
      'type' => 'text',
      'label' => 'Name',
      'name' => 'm_name',
      'value' => $this->m_name,
    ]);
    if ($this->m_name) $create = true;

    $wrapper->add([
      'type' => 'text',
      'label' => 'Theme-Color',
      'name' => 'm_theme_color',
      'value' => $this->m_theme_color,
      'notes' => 'eg #00bb86',
    ]);
    if ($this->m_theme_color) $create = true;

    $wrapper->add([
      'type' => 'text',
      'label' => 'Background-Color',
      'name' => 'm_background_color',
      'value' => $this->m_background_color,
      'notes' => 'Leave blank for white background.',
    ]);
    if ($this->m_background_color) $create = true;

    $wrapper->add([
      'type' => 'markup',
      'label' => 'Icon',
      'value' => 'TBD',
    ]);

    if ($create) {
      $this->manifest()
        ->name($this->m_name)
        ->themeColor($this->m_theme_color)
        ->backgroundColor($this->m_background_color ?: '#fff')
        ->saveToFile();
      $this->message('Manifest File has been saved to PW root folder.');
    }

    $fs->add($wrapper);
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

  private function drop($str)
  {
    $str = str_replace(
      ["/**\n", " * ", "\n */"],
      "",
      $str
    );
    return "<div class='uk-inline'>
      <span uk-icon='info' class='uk-margin-small-left'></span>
      <div class='uk-card uk-card-body uk-card-default' uk-drop>"
      . nl2br($this->wire->sanitizer->entities($str))
      . "</div>
    </div>";
  }

  public function isEnabled($feature): bool
  {
    if (!is_array($this->features)) return false;
    return in_array($feature, $this->features);
  }

  public function profileInstalledNote()
  {
    $note = $this->wire->pages->get(1)->meta(self::installedprofilekey);
    if ($note) return "Installed profile: $note";
  }

  /** ##### webfont downloader ##### */

  public function createWebfontsFile(HookEvent $event)
  {
    if ($event->process != "ProcessModule") return;
    if ($this->wire->input->get('name', 'string') != 'RockFrontend') return;
    $url = $this->wire->input->post->webfonts;
    if ($this->webfonts == $url) return; // no change
    $css = $this->downloadWebfontFiles($url);
    $this->wire->files->filePutContents(
      $this->wire->config->paths->templates . "webfonts/webfonts.css",
      $css
    );
  }

  public function downloadWebfontFiles($url)
  {
    $data = $this->getFontData();

    // reset fonts path to remove unused font files
    $fontsdir = $this->wire->config->paths->templates . "webfonts";
    $this->wire->files->rmdir($fontsdir, true);
    $this->wire->files->mkdir($fontsdir);

    /** @var WireHttp $http */
    $http = $this->wire(new WireHttp());
    foreach (self::webfont_agents as $format => $agent) {
      $data->rawCss .= "/* requesting format '$format' by using user agent '$agent' */\n";
      $http->setHeader("user-agent", $agent);
      $result = $http->get($url);
      $data->rawCss .= $result;
      $data = $this->parseResult($result, $format, $data);
    }
    return trim($this->createCssSuggestion($data, false), "\n");
  }

  private function createCssSuggestion($data, $deep = true): string
  {
    // bd($data->files, 'files');
    $fontsdir = $this->wire->config->urls->templates . "webfonts/";
    $css = $deep ? "/* suggestion for practical level of browser support */" : '';
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
        $src .= "\n    $comment\n    url('$fontsdir{$file->name}') format('{$file->format}'),";
      }
      $src = rtrim($src, ",\n ");
      $rule->setValue($src);
      $set->addRule($rule);

      $css .= "\n" . $set->render($data->parserformat);
    }

    if (!$deep) return $css;

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
        $src .= "url('$fontsdir{$eot->name}'); /* IE9 Compat Modes */\n  ";
        $src .= "src: url('$fontsdir{$eot->name}?#iefix') format('embedded-opentype'), /* IE6-IE8 */\n  ";
      }
      foreach ($files->find("format!=eot") as $file) {
        $format = $file->format;
        if ($format == 'ttf') $format = 'truetype';
        $comment = self::webfont_comments[$file->format];
        // comment needs to be first!
        // last comma will be trimmed and css render() will add ; at the end!
        $src .= "\n    $comment\n    url('$fontsdir{$file->name}') format('{$file->format}'),";
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
    $url = $this->wire->input->post('webfont-downloader', 'string');
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
    $dir = $this->wire->config->paths->templates . "webfonts/";
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

  private function showFontFileSize(): string
  {
    $out = "Filesize of all .woff2 files in /site/templates/webfonts: {size}";
    $size = 0;
    foreach (glob($this->wire->config->paths->templates . "webfonts/*.woff2") as $file) {
      $size += filesize($file);
      $out .= "\n" . basename($file);
    }
    return str_replace("{size}", wireBytesStr($size, true), $out);
  }

  /** ##### END webfont downloader ##### */

  public function __debugInfo()
  {
    return [
      'folders' => $this->folders->getArray(),
      'liveReload' => $this->getLiveReload(),
      'autoloadStyles' => $this->autoloadStyles,
      'autoloadScripts' => $this->autoloadScripts,
    ];
  }
}
