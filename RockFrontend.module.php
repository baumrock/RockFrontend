<?php

namespace ProcessWire;

use ExposeFunctionsExtension;
use HumanDates;
use Latte\Engine;
use Latte\Runtime\Html;
use LogicException;
use MatthiasMullie\Minify\Exceptions\IOException;
use ProcessWire\Paths as ProcessWirePaths;
use RockFrontend\Asset;
use RockFrontend\Manifest;
use RockFrontend\Paths;
use RockFrontend\Seo;
use RockPageBuilder\Block;
use Sabberworm\CSS\OutputFormat;
use Sabberworm\CSS\Parser;
use Sabberworm\CSS\Rule\Rule;
use Sabberworm\CSS\RuleSet\AtRuleSet;
use Sabberworm\CSS\RuleSet\RuleSet;
use Tracy\Debugger;
use Tracy\Dumper;
use Wa72\HtmlPageDom\HtmlPageCrawler;

function rockfrontend(): RockFrontend
{
  return wire()->modules->get('RockFrontend');
}

// load the fieldmethod trait here,
// otherwise it throws an error when used in DefaultPage
require_once __DIR__ . '/classes/FieldMethod.php';

/**
 * @author Bernhard Baumrock, 05.01.2022
 * @license MIT
 * @link https://www.baumrock.com
 *
 * @method string render($filename, array $vars = array(), array $options = array())
 * @method string view(string $file, array|Page $vars = [], Page $page = null)
 */
class RockFrontend extends WireData implements Module, ConfigurableModule
{

  const tags = "RockFrontend";
  const prefix = "rockfrontend_";
  const tagsUrl = "/rockfrontend-layout-suggestions/{q}";
  const permission_alfred = "rockfrontend-alfred";
  const cache = 'rockfrontend-uikit-versions';
  const installedprofilekey = 'rockfrontend-installed-profile';
  const recompile = 'rockfrontend-recompile-less';
  const defaultVspaceScale = 0.66;
  const layoutFile = '_main.latte';

  const ajax_noaccess = "ajax-noaccess";
  const ajax_rendererror = "ajax-render-error";

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

  private $addMarkup;

  /**
   * Property that is true for ajax requests.
   * Compared to $config->ajax this will also account for HTMX etc.
   * @var false
   */
  public $ajax = false;

  private $ajaxFolders = [];

  /** @var WireData */
  public $alfredCache;

  public $autoloadScripts;
  public $autoloadStyles;

  private $contenttype = "text/html";

  public $createManifest = false;

  /** @var WireArray $folders */
  public $folders;

  public $home;

  /** @var bool */
  public $hasAlfred = false;

  /** @var array */
  protected $js = [];

  public $langMaps;

  /** @var Engine */
  private $latte;

  /** @var Engine */
  private $latteWithLayout;

  public $layoutFile = self::layoutFile;

  /** @var WireArray $layoutFolders */
  public $layoutFolders;

  /** @var Manifest */
  protected $manifest;

  /** @var bool */
  public $noAssets = false;

  public $noLayoutFile;

  private $onceKeys = [];

  /** @var string */
  public $path;

  private $paths;

  /** @var WireData */
  public $postCSS;

  /**
   * REM base value (16px)
   */
  public $remBase;

  /** @var Seo */
  public $seo;

  private $sitemapCallback;
  private $sitemapOptions;

  /** @var array */
  private $translations = [];

  /** @var array */
  private $viewfolders = [];

  public function __construct()
  {
    $this->folders = $this->wire(new WireArray());
  }

  public function init()
  {
    $this->path = wire()->config->paths($this);
    $this->home = wire()->pages->get(1);

    // load composer autoloader as early as possible
    // this is so that anyone can create custom latte extensions
    // without having to require the autoloader in their extension
    require_once $this->path . "vendor/autoload.php";
    wire()->classLoader->addNamespace("RockFrontend", __DIR__ . "/classes");

    if (!is_array($this->features)) $this->features = [];

    // make $rockfrontend and $home variable available in template files
    $this->wire('rockfrontend', $this);
    $this->wire('home', $this->home);
    $this->autoloadScripts = new WireArray();
    $this->autoloadStyles = new WireArray();
    $this->alfredCache = $this->wire(new WireData());

    // set ajax flag
    $htmx = isset($_SERVER['HTTP_HX_REQUEST']) && $_SERVER['HTTP_HX_REQUEST'];
    $this->ajax = $this->wire->config->ajax || $htmx;

    // JS defaults
    // set the remBase either from config setting or use 16 as fallback
    $this->remBase = $this->remBase ?: 16;
    $this->initPostCSS();

    // if in DDEV we set a js property accordingly
    // if not we set nothing, so no markup will be on the frontend
    // see https://processwire.com/talk/topic/27417-rockfrontend-%F0%9F%9A%80%F0%9F%9A%80-the-powerful-toolbox-for-processwire-frontend-development/?do=findComment&comment=240837
    if ($this->isDDEV()) $this->js('isDDEV', true);

    // watch this file and run "migrate" on change or refresh
    if ($rm = $this->rm()) {
      $rm->watch($this, 0.01);
      $rm->minify(__DIR__ . '/RockFrontend.js');
    }

    // setup folders that are scanned for files
    $this->folders->add($this->config->paths->templates);
    $this->folders->add($this->config->paths->assets);
    $this->folders->add($this->config->paths->root);

    // layout folders
    $this->layoutFolders = $this->wire(new WireArray());
    $this->layoutFolders->add($this->config->paths->templates);
    $this->layoutFolders->add($this->config->paths->assets);

    // add ajax folders
    // you can add custom endpoints in 3rd party modules in the same way
    // see RockCommerce for an example
    $this->addAjaxFolder(
      'ajax',
      $this->wire->config->paths->templates . 'ajax'
    );

    // Alfred
    require_once __DIR__ . "/Functions.php";
    $this->createPermission(
      self::permission_alfred,
      "Is allowed to use ALFRED frontend editing"
    );
    $this->lessToCss($this->path . "Alfred.less");

    // hooks
    wire()->addHookAfter("ProcessPageEdit::buildForm",   $this, "hideLayoutField");
    wire()->addHook(self::tagsUrl,                       $this, "layoutSuggestions");
    wire()->addHookAfter("Modules::refresh",             $this, "refreshModules");
    wire()->addHookBefore("TemplateFile::render",        $this, "autoPrepend");
    wire()->addHookAfter("InputfieldForm::processInput", $this, "createWebfontsFile");
    wire()->addHookBefore("Inputfield::render",          $this, "addFooterlinksNote");
    wire()->addHookAfter("Page::changed",                $this, "resetCustomLess");
    wire()->addHookBefore("Page::render",                $this, "createCustomLess");
    wire()->addHookMethod("Page::otherLangUrl",          $this, "otherLangUrl");
    wire()->addHookAfter("Page::render",                 $this, "hookAddMarkup");

    // others
    $this->checkHealth();

    // development helpers by rockmigrations
    if ($this->wire->modules->isInstalled('RockMigrations')) {
      try {
        $rm = rockmigrations();
        $rm->minify(__DIR__ . "/Alfred.js");
      } catch (\Throwable $th) {
        $this->warning("rockmigrations() not available - please update RockMigrations!");
      }
    }
  }

  public function ready()
  {
    $this->ajaxAddEndpoints();
    $this->addAssets();
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

        $html = $this->addAlfredMarkup($html);
        try {
          $this->addTopBar($html);
        } catch (\Throwable $th) {
          $this->log($th->getMessage());
        }
        $this->injectJavascriptSettings($html);
        $this->injectAssets($html);
        $event->return = $html;
      },
      // other modules also hooking to page::render can define load order
      // via hook priority, for example RockCommerce uses 200 to make sure
      // all RockCommerce releated stuff is loaded after default RF assets
      ['priority' => 100]
    );
  }

  private function addAlfredMarkup(string $html, $skipAssets = false): string
  {
    if (!$this->loadAlfred()) return $html;

    if (!$skipAssets) {
      $this->js("rootUrl", $this->wire->config->urls->root);
      $this->js("defaultVspaceScale", number_format(self::defaultVspaceScale, 2, ".", ""));
      $this->scripts('rockfrontend')->add(__DIR__ . "/Alfred.min.js", "defer");
      $this->addAlfredStyles();
    }

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

    return $html;
  }

  /**
   * Add a folder as ajax endpoint (without trailing slash!)
   *
   * Usage:
   * rockfrontend()->addAjaxFolder(
   *   '/rockcommerce/',
   *   __DIR__ . '/ajax/',
   * );
   *
   * This will add all .php files as ajax-endpoints. It also supports nested
   * folders, for example:
   *
   * /cart/add.php
   * /cart/reset.php
   *
   * rockfrontend()->addAjaxFolder(
   *   '/rockcommerce/',
   *   __DIR__ . '/ajax/',
   * );
   * --> /rockcommerce/cart/add
   * --> /rockcommerce/cart/reset
   *
   * @param string $url
   * @param string $folder
   * @return void
   */
  public function addAjaxFolder(
    string $url,
    string $folder,
  ): void {
    $url = '/' . trim($url, '/') . '/';
    $folder = $this->paths()->toPath($folder);
    $this->ajaxFolders[$url] = $folder;
  }

  /**
   * This method makes it possible to add a custom InputfieldWrapper to a
   * page edit form in the backend. You can for example easily place two fields
   * side-by-side using flexbox.
   *
   * Usage:
   * rockfrontend()->addPageEditWrapper(
   *   templateName: 'your-template',
   *   renderFile: '/path/to/markup.latte',
   * );
   */
  public function addPageEditWrapper(
    string $templateName,
    string $renderFile,
  ): void {
    // do everything only on buildForm
    wire()->addHookAfter(
      'ProcessPageEdit::buildForm',
      function ($e) use ($templateName, $renderFile) {
        if ($e->process->getPage()->template != $templateName) return;

        $markup = $this->render($renderFile);
        if (!$markup) return;

        // get array of field:myfield tags
        $fields = [];
        preg_match_all('/field:([a-zA-Z0-9_]+)/', $markup, $matches);
        if (!count($matches) === 2) return;

        $fields = $matches[1];
        if (!count($fields)) return;

        /** @var InputfieldForm $form */
        $form = $e->return;

        // add wrapper that holds the new markup
        $f = new InputfieldWrapper();
        $f->addHookBefore(
          'render',
          function ($e) use ($markup) {
            $e->return = $markup;
            $e->replace = true;
          }
        );
        $lastField = $fields[count($fields) - 1];
        $existing = $form->get($lastField);
        if (!$existing) return;
        $form->insertAfter($f, $existing);

        // manipulate the form html via dom tools
        $form->addHookAfter(
          'render',
          function ($e) use ($fields) {
            $html = $e->return;
            $dom = $this->dom($html);

            $replace = [];
            foreach ($fields as $i => $f) {
              $li = $dom->filter("#wrap_Inputfield_$f")->outerHtml();
              $replace["field:$f"] = "
                <style>
                .rf-pageeditwrapper { padding: 0; margin: 0; height: 100%; }
                .rf-pageeditwrapper > li { height: 100%; }
                </style>
                <ul class='rf-pageeditwrapper'>$li</ul>
              ";
              $dom->filter("#wrap_Inputfield_$f")->remove();
            }

            $html = str_replace(
              array_keys($replace),
              array_values($replace),
              $dom->outerHtml()
            );

            $e->return = $html;
          }
        );
      }
    );
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
    if ($page->template == 'form-builder') return;
    if ($this->wire->input->get('rfpreview')) return;
    if ($this->wire->config->hideTopBar) return;

    /** @var RockMigrations $rm */
    $less = __DIR__ . "/topbar/topbar.less";

    if ($this->wire->modules->isInstalled("RockMigrations")) {
      /** @var RockMigrations $rm */
      $rm = $this->wire->modules->get('RockMigrations');
      $rm->saveCSS($less, minify: true);
    }
    $css = $this->toUrl(__DIR__ . "/topbar/topbar.min.css", true);
    $style = "<link rel='stylesheet' href='$css'>";
    $html = str_replace("</head", "$style</head", $html);

    $topbar = $this->wire->files->render(__DIR__ . "/topbar/topbar.php", [
      'rf' => $this,
      'logourl' => $this->toUrl(__DIR__ . "/RockFrontend.svg", true),
      'z' => is_int($this->topbarz) ? $this->topbarz : 999,
    ]);
    $html = str_replace("</body", "$topbar</body", $html);
  }

  /**
   * Adjust brightness of a hex value
   * See https://stackoverflow.com/a/54393956 for details.
   * It might not be 100% accurate but good enough for my usecases :)
   *
   * @param mixed $hexCode
   * @param mixed $adjustPercent
   * @return string
   */
  private function adjustBrightness($hexCode, $adjustPercent)
  {
    $hexCode = ltrim($hexCode, '#');
    if (strlen($hexCode) == 3) {
      $hexCode = $hexCode[0] . $hexCode[0] . $hexCode[1] . $hexCode[1] . $hexCode[2] . $hexCode[2];
    }

    $hexCode = array_map('hexdec', str_split($hexCode, 2));
    foreach ($hexCode as &$color) {
      $adjustableLimit = $adjustPercent < 0 ? $color : 255 - $color;
      $adjustAmount = ceil($adjustableLimit * $adjustPercent);
      $color = str_pad(dechex($color + $adjustAmount), 2, '0', STR_PAD_LEFT);
    }

    return '#' . implode($hexCode);
  }

  /**
   * Add hooks for all ajax endpoints
   */
  protected function ajaxAddEndpoints(): void
  {
    foreach ($this->ajaxEndpoints() as $url => $file) {
      wire()->addHook(
        $url,
        function (HookEvent $event) use ($file, $url) {
          $isGET = $this->wire->input->requestMethod() === 'GET';

          // ajax requests always return the public endpoint
          if ($this->ajax || !$isGET) {
            return $this->ajaxPublic($file);
          }

          // non-ajax request show the debug screen for superusers
          if (wire()->user->isSuperuser()) {
            return $this->ajaxDebug($file, $url);
          }

          // guest and no ajax: no access!
          if (wire()->modules->isInstalled('TracyDebugger')) {
            Debugger::$showBar = false;
          }
          http_response_code(403);
          return "Access Denied";
        }
      );
    }
  }

  /**
   * Given a base url like /ajax/foo returns the subfolder-safe url like
   * /subfolder/ajax/foo ready to be used for get/post requests
   *
   * @param mixed $base
   * @return string
   */
  public function ajaxBaseToUrl(string $base): string
  {
    return $this->wire->pages->get(1)->url . ltrim($base, '/');
  }

  private function ajaxDebug($file, $url): string
  {
    // dont catch errors when debugging
    $raw = $this->ajaxResponse($file);
    $response = Dumper::toHtml($raw);

    // generate textarea content
    $textarea = '';
    foreach ($this->ajaxVars()->getArray() as $key => $value) {
      $textarea .= "$key: $value\n";
    }

    // render html
    $markup = $this->render(__DIR__ . "/stubs/ajax-debug.latte", [
      'endpoint' => $file,
      'ajaxUrl' => $this->ajaxBaseToUrl($url),
      'response' => $response,
      'formatted' => $this->ajaxFormatted($raw, $file),
      'contenttype' => $this->contenttype, // must be after formatted!
      'input' => Dumper::toHtml($this->ajaxVars()->getArray()),
      'textarea' => $textarea,
    ]);

    return $markup;
  }

  /**
   * Get array of all added ajax endpoints
   * Array is in format [base-url => filepath]
   *
   * ATTENTION: The base-url is the endpoint url without the subfolder prefix!
   *
   * Example: /ajax/foo will be the base-url, but /subfolder/ajax/foo will be
   * the endpoint used for GET/POST requests.
   *
   * You can get the subfolder-safe endpoint via ->ajaxBaseToUrl($baseurl)
   *
   * @return array
   */
  private function ajaxEndpoints(): array
  {
    // scan for these extensions
    // later listed extensions have priority
    $extensions = [
      'php',
      'latte',
    ];

    // attach hook for every found endpoint
    $arr = [];
    foreach ($extensions as $ext) {
      $opt = ['extensions' => [$ext]];
      foreach ($this->ajaxFolders as $baseurl => $folder) {
        // add all endpoints from this folder to RockFrontend
        $endpoints = $this->wire->files->find($folder, $opt);
        foreach ($endpoints as $file) {
          // get url after folder
          // we can't use basename because we support nested folders/endpoints
          $suffix = substr($file, strlen($folder), - (strlen($ext) + 1));
          $url = $baseurl . ltrim($suffix, '/');

          if (array_key_exists($url, $arr)) continue;
          $arr[$url] = $file;
        }
      }
    }

    return $arr;
  }

  private function ajaxFormatted($raw, $endpoint): string
  {
    $extension = pathinfo($endpoint, PATHINFO_EXTENSION);
    if ($extension === "latte") {
      $response = $this->render($endpoint);
    } else $response = $raw;

    // is response already a string?
    if (is_string($response)) {
      $exceptions = [
        self::ajax_noaccess => "No access",
        self::ajax_rendererror => "Error rendering endpoint - see logs for details.",
      ];
      if (array_key_exists($response, $exceptions)) {
        throw new WireException($exceptions[$response]);
      }

      // no exception - return string
      return $response;
    }

    // array --> json
    if (is_array($response)) {
      $this->contenttype = "application/json";
      return json_encode($response, JSON_PRETTY_PRINT);
    }

    // still no string, try to cast it to string
    try {
      $response = (string)$response;
    } catch (\Throwable $th) {
      throw new WireException("Invalid return type");
    }

    return $response;
  }

  private function ajaxPublic($endpoint): string
  {
    // return function to keep code DRY
    $return = function ($endpoint) {
      $raw = $this->ajaxResponse($endpoint);
      $response = $this->ajaxFormatted($raw, $endpoint);
      header('Content-Type: ' . $this->contenttype);
      return $response;
    };

    // for debugging we don't catch errors
    if (wire()->config->debug) return $return($endpoint);

    // public endpoints return a generic error message
    // to avoid leaking information
    try {
      return $return($endpoint);
    } catch (\Throwable $th) {
      $this->log($th->getMessage());
      return "Error in AJAX endpoint - error has been logged";
    }
  }

  private function ajaxResponse($endpoint)
  {
    // for superusers we don't catch errors
    if ($this->wire->user->isSuperuser()) {
      return $this->ajaxResult($endpoint);
    }
    // non superusers - use try catch
    try {
      return $this->ajaxResult($endpoint);
    } catch (\Throwable $th) {
      $this->log($th->getMessage());
      return self::ajax_rendererror;
    }
  }

  private function ajaxResult($endpoint)
  {
    $input = $this->ajaxVars();
    $result = $this->wire->files->render($endpoint, ['input' => $input]);
    if (is_string($result)) {
      return $this->addAlfredMarkup(
        $result,
        true
      );
    } else return $result;
  }

  public function ajaxUrl($base): string
  {
    return $this->wire->pages->get(1)->url . "ajax/$base";
  }

  /**
   * Get all input variables from GET and POST
   * POST has precedence over GET
   */
  private function ajaxVars(): WireInputData
  {
    $vars = new WireInputData();

    // grab GET data
    $vars->setArray(wire()->input->get->getArray());

    // grab POST data
    $vars->setArray(wire()->input->post->getArray());

    // grab raw input (for JSON or other content types)
    $raw_input = file_get_contents('php://input');
    if (!empty($raw_input)) {
      $json_data = json_decode($raw_input, true);
      if (is_array($json_data)) {
        // It's valid JSON
        $vars->setArray($json_data);
      } else {
        // It's not JSON, try parsing as form data
        parse_str($raw_input, $parsed_input);
        if (is_array($parsed_input)) {
          $vars->setArray($parsed_input);
        }
      }
    }

    // return result
    return $vars;
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

    // check if frontend editing is installed
    if (!$this->wire->modules->isInstalled("PageFrontEdit")) {
      $this->addMarkup .= "<script>alert('Please install PageFrontEdit to use ALFRED')</script>";
    }

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
    if ($this->wire->modules->isInstalled('RockPageBuilder')) {
      /** @var RockPageBuilder $rpb */
      $rpb = $this->wire->modules->get("RockPageBuilder");
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
    $path = ProcessWirePaths::normalizeSeparators($path);
    $dir = $this->wire->config->paths->assets . "RockFrontend/";
    if (strpos($path, $dir) === 0) return $path;
    return $dir . trim($path, "/");
  }

  /**
   * Auto-prepend file before rendering for exposing variables from _init.php
   */
  public function autoPrepend($event)
  {
    if (!wire()->config->rockfrontendAutoPrepend) return;

    /** @var Templatefile $tpl */
    $tpl = $event->object;
    $this->autoPrependFile = (string)$tpl;
    $tpl->setPrependFilename($this->path . "AutoPrepend.php");
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

  public function darken($hex, $percent): string
  {
    return $this->adjustBrightness($hex, -1 * ($percent / 100));
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

  public function editorLink($path)
  {
    $rootPath = $this->wire->config->paths->root;
    $editor = "vscode://file/%file";

    if ($this->wire->modules->isInstalled("TracyDebugger")) {
      $tracy = $this->wire->modules->get("TracyDebugger");
      $rootPath = $tracy->localRootPath;
      $editor = $tracy->editor;
    }

    $rootPath = getenv("TRACY_LOCALROOTPATH") ?: $rootPath;
    $editor = getenv("TRACY_EDITOR") ?: $editor;

    $rootPath = rtrim($rootPath, "/") . "/";
    $link = str_replace($this->wire->config->paths->root, $rootPath, $path);
    $link = ProcessWirePaths::normalizeSeparators($link);

    $handler = str_replace(":%line", "", $editor);
    $link = str_replace("%file", ltrim($link, "/"), $handler);
    return $link;
  }

  /**
   * Get field via short name (useful for prefixed fields) and define return type
   *
   * Example:
   * field = my_prefix_myfield
   * echo $page->field('myfield', 'u');
   *
   * See possible type values from the switch statement below
   */
  public function field(
    Page $page,
    string $shortname,
    string $type = null,
  ) {
    // default type is formatted
    if (!$type) $type = 'f';

    $type = strtolower($type);
    $fieldname = $this->getRealFieldname($page, $shortname);
    if (!$fieldname) return false;

    // the noEdit flag prevents rendering editable fields
    if ($type === 'e' && $this->noEdit) $type = 'f';
    switch ($type) {
      case 'e':
      case 'edit':
        // edit field
        return $this->html($page->edit($fieldname));
      case 'f':
      case 'formatted':
        // formatted
        return $page->getFormatted($fieldname);
      case 'u':
      case 'unformatted':
        // unformatted
        return $page->getUnformatted($fieldname);
      case 's':
      case 'string':
        // string
        return (string)$page->getFormatted($fieldname);
      case 'h':
      case 'html':
        // latte html object
        return $this->html((string)$page->getFormatted($fieldname));
      case 'i':
      case 'int':
        // integer
        // using (string) to convert pages to ids
        return (int)(string)$page->getFormatted($fieldname);
      case 'a':
      case 'array':
      case '[]':
        // formatted as array (eg pageimages)
        return $page->getFormatted("$fieldname.[]");
      case 'first':
        // formatted as single item (eg pageimage)
        return $page->getFormatted("$fieldname.first");
    }
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
  public function getFile(
    string $file,
    bool $forcePath = false,
  ): string {
    if (strpos($file, "//") === 0) return $file;
    if (strpos($file, "http://") === 0) return $file;
    if (strpos($file, "https://") === 0) return $file;
    $file = ProcessWirePaths::normalizeSeparators($file);

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
      $folder = ProcessWirePaths::normalizeSeparators($folder);
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
      $rpb = false;
      if ($this->wire->modules->isInstalled('RockPageBuilder')) {
        $rpb = $this->wire->modules->get("RockPageBuilder");
      }
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
        'href' => $this->editorLink($opt->path),
        'tooltip' => $opt->path,
      ];

      // get base filepath without extension
      $ext = pathinfo($opt->path, PATHINFO_EXTENSION);
      $base = substr($opt->path, 0, -strlen($ext) - 1);
      if (str_ends_with($base, ".view")) $base = substr($base, 0, -5);

      // php file edit link
      $php = "$base.php";
      if (is_file($php)) {
        $icons[] = (object)[
          'icon' => 'php',
          'label' => $php,
          'href' => $this->editorLink($php),
          'tooltip' => $php,
        ];
      }

      // style edit link
      $less = "$base.less";
      if (is_file($less)) {
        $icons[] = (object)[
          'icon' => 'eye',
          'label' => $less,
          'href' => $this->editorLink($less),
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
   * Find path in rockfrontend folders
   * Returns path with trailing slash
   * @return string|false
   */
  public function getPath($path, $forcePath = false)
  {
    $path = ProcessWirePaths::normalizeSeparators($path);

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
   * Given the short fieldname "foo" find the real fieldname "my_prefixed_foo"
   */
  public function getRealFieldname(Page $page, string $shortname): string|false
  {
    $fieldnames = $page->fields->each('name');

    // if the fieldname exists we return the unmodified name
    if (in_array($shortname, $fieldnames)) return $shortname;

    // otherwise we check for the final _xxx part of the name
    foreach ($page->fields as $field) {
      $suffix = strrchr($field->name, '_');
      if ($suffix === "_$shortname") return $field->name;
    }

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
      $file = ProcessWirePaths::normalizeSeparators($step['file']);

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
   * @param bool $noCache load from cache?
   * @return array
   */
  public function getUikitVersions($noCache = false)
  {
    $expire = 60 * 5;
    if ($noCache) $expire = WireCache::expireNow;
    $versions = $this->wire->cache->get(self::cache, $expire, function () {
      $http = new WireHttp();
      $refs = $http->getJSON('https://api.github.com/repos/uikit/uikit/git/refs/tags', $assoc = false);
      if ($refs) {
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
      }
      return [];
    });
    if (!empty($versions)) return $versions;
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
   * Inject markup to the page body
   * @param HookEvent $event
   * @return void
   */
  protected function hookAddMarkup(HookEvent $event)
  {
    if (!$this->addMarkup) return;
    $event->return = str_replace(
      "</body>",
      $this->addMarkup . "</body>",
      $event->return
    );
  }

  /**
   * Return a latte HTML object that doesn't need to be |noescaped
   * @return Html
   */
  public static function html($str, $trim = true)
  {
    // don't return empty string as html object
    // this os for easier checks in if conditions, eg n:if='$val'
    // as this condition would always be true for html objects
    if ($trim) $str = trim((string)$str);
    if (!$str) return '';

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
    require_once __DIR__ . "/vendor/autoload.php";
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
    foreach ($this->autoloadStyles as $style) $assets .= $style->render();
    foreach ($this->autoloadScripts as $script) $assets .= $script->render();
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
    if ($this->wire->modules->isInstalled("RockMigrations")) {
      /** @var RockMigrations $rm */
      $rm = $this->wire->modules->get('RockMigrations');
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
   * Check wether the environment is DDEV or not
   */
  public function isDDEV(): bool
  {
    return !!getenv('DDEV_HOSTNAME');
  }

  /**
   * Internal flag for development
   * @return bool
   */
  public function isDev(): bool
  {
    if (!$this->wire->config->debug) return false;
    if (!$this->wire->user->isSuperuser()) return false;
    if (!$this->isDDEV()) return false;
    return true;
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
    // loading of layout file disabled?
    if ($this->noLayoutFile) return false;

    // for RockPdf we need no layout file
    if ($this->rockPdf) return false;

    $tpl = rtrim($this->wire->config->paths->templates, "/");
    $layoutFile = ltrim($this->layoutFile, "/");
    if ($layoutFile) return "$tpl/$layoutFile";
    return "$tpl/" . self::layoutFile;
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

  public function lighten($hex, $percent): string
  {
    return $this->adjustBrightness($hex, $percent / 100);
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
  public function ___loadLatte($withLayout = false)
  {
    if ($withLayout && $this->latteWithLayout) return $this->latteWithLayout;
    if ($this->latte) return $this->latte;

    try {
      $latte = new Engine;
      $latte->setTempDirectory($this->wire->config->paths->cache . "Latte");
      if ($this->wire->modules->isInstalled("TracyDebugger")) {
        $latte->addExtension(new \Latte\Bridges\Tracy\TracyExtension());
      }

      // make processwire functions like wire() available in latte
      // see https://processwire.com/talk/topic/30449-questions-and-syntax-latte-template-engine-by-nette/?do=findComment&comment=244743
      // and https://forum.nette.org/en/36678-add-namespace-to-compiled-latte-files
      require_once __DIR__ . "/latte/ExposeFunctionsExtension.php";
      $latte->addExtension(new ExposeFunctionsExtension());

      // add custom filters
      // you can set $config->noLatteFilters = true to prevent loading of
      // custom filters.
      if (!$this->wire->config->noLatteFilters) {

        // add vurl to add a cache busting suffix to image or file urls
        // this ensures that new focus points or resized images are not loaded
        // from the browsers cache
        // Usage: {$img->maxSize(1920,1920)->webp->url|vurl}
        $latte->addFilter('vurl', function ($url) {
          return $this->wire->config->versionUrl($url, true);
        });

        // euro rendering: € 1.499,99
        $latte->addFilter('euro', function ($price) {
          return "€ " . number_format($price, 2, ",", ".");
        });
        // euro rendering: 1.499,99 €
        // see https://de.wikipedia.org/wiki/Schreibweise_von_Zahlen#Deutschland_und_%C3%96sterreich
        $latte->addFilter('euroAT', function ($price) {
          return number_format($price, 2, ",", ".") . " €";
        });
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

  public function ___loadTwig()
  {
    try {
      $debug = !!$this->wire->config->debugTwig;
      require_once $this->wire->config->paths->root . 'vendor/autoload.php';
      $loader = new \Twig\Loader\FilesystemLoader($this->wire->config->paths->root);
      $twig = new \Twig\Environment($loader, ['debug' => $debug]);
      if ($debug) $twig->addExtension(new \Twig\Extension\DebugExtension());
      return $twig;
    } catch (\Throwable $th) {
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

  /**
   * Minify file and return path of minified file
   */
  public function minifyFile($file, $minFile = null): string
  {
    $file = new Asset($file);
    if (!$minFile) $minFile = $file->minPath();
    $minFile = new Asset($minFile);
    if ($minFile->m < $file->m) {
      try {
        require_once __DIR__ . "/vendor/autoload.php";
        if ($file->ext == 'js') $minify = new \MatthiasMullie\Minify\JS($file);
        else $minify = new \MatthiasMullie\Minify\CSS($file);
        $minify->minify($minFile->path);
      } catch (\Throwable $th) {
        $this->log($th->getMessage());
      }
    }
    return $minFile->path;
  }

  /**
   * Minify all files in given directory
   * @param string $directory
   * @param array $options
   * @return void
   * @throws IOException
   */
  public function minifyFiles(string $directory, array $options = []): void
  {
    $dir = $this->toPath($directory);
    $files = $this->wire->files->find($dir, $options);
    foreach ($files as $file) {
      if (str_ends_with($file, ".min.js")) continue;
      if (str_ends_with($file, ".min.css")) continue;
      $this->minifyFile($file);
    }
  }

  /**
   * Helper to display markup only once
   *
   * Usage with LATTE:
   * <div n:if="$rockfrontend->once('demo')">
   *   Demo Content
   * </div>
   */
  public function once(string $key): bool
  {
    $found = in_array($key, $this->onceKeys);
    $this->onceKeys[] = $key;
    return !$found;
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
   * Load the paths helper class
   * @return Paths
   */
  public function paths(): Paths
  {
    return $this->paths ?? $this->paths = new Paths();
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
    $path = ProcessWirePaths::normalizeSeparators(__DIR__ . "/profiles");
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
    return ProcessWirePaths::normalizeSeparators(realpath($file));
  }

  /**
   * Things to do when modules are refreshed
   */
  public function refreshModules()
  {
    // update the _rockfrontend.php file if necessary
    $this->copyLayoutFileIfNewer();

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
      if (wire()->config->debug) {
        $method = "renderFile" . ucfirst(strtolower($ext));
        $html = $this->$method($file, $vars);
      } else {
        try {
          $method = "renderFile" . ucfirst(strtolower($ext));
          $html = $this->$method($file, $vars);
        } catch (\Throwable $th) {
          $this->log($th->getMessage());
          $html = '';
        }
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
    $twig = $this->loadTwig();
    if ($twig === false) {
      return 'Error loading Twig. Use composer require "twig/twig:^3.0" in PW root.';
    }
    $relativePath = str_replace(
      $this->wire->config->paths->root,
      $this->wire->config->urls->root,
      $file
    );
    $vars = array_merge((array)$this->wire('all'), $vars);
    return $twig->render($relativePath, $vars);
  }

  /**
   * Get markup of a single view file
   *
   * Usage:
   * $rf->view('foo/bar');
   * $rf->view('foo/bar', $page);
   * $rf->view('foo/bar', ['foo' => 'Foo!'], $page);
   */
  public function ___view(
    string $file,
    array|Page $vars = [],
    Page $page = null,
  ): Html|string {
    if ($vars instanceof Page) {
      $page = $vars;
      $vars = [];
    }
    $file = $this->viewFile($file);
    if ($page) $vars = array_merge($vars, ['page' => $page]);
    $markup = $this->render($file, $vars);
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
    $file = ProcessWirePaths::normalizeSeparators($file);
    foreach ($this->viewfolders as $folder) {
      $folder = ProcessWirePaths::normalizeSeparators($folder);
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
    // this is because the main style array is for the main website
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
   * @return RockMigrations|false
   */
  public function rm()
  {
    if (!$this->wire->modules->isInstalled('RockMigrations')) return false;
    return $this->wire->modules->get('RockMigrations');
  }

  /**
   * Return a script tag for a given url
   *
   * By default we will add the defer attribute (suffix)
   *
   * @param mixed $url
   * @param string $suffix
   * @return string
   * @throws WireException
   */
  public function scriptTag($url, $suffix = 'defer'): string
  {
    $src = wire()->config->versionUrl($url);
    return "<script src='$src' $suffix></script>";
  }

  /**
   * @return Seo
   */
  public function seo(
    bool $createManifest = false,
  ) {
    $this->createManifest = $createManifest;
    if ($this->seo) return $this->seo;
    require_once __DIR__ . "/Seo.php";
    return $this->seo = new Seo();
  }

  /**
   * Set viewFolders for folder rendering feature
   * This is required for RockCommerce htmx endpoint rendering.
   */
  public function setViewFolders(array $folders): void
  {
    $this->viewfolders = $folders;
  }

  /**
   * Create a sitemap.xml file with a simple callback
   * @return void
   */
  public function sitemap($callback = null, $options = []): void
  {
    if (!$callback) $callback = function (Page $page) {
      return $page;
    };
    $this->sitemapCallback = $callback;
    $this->sitemapOptions = $options;
    wire()->addHookAfter("/sitemap.xml", $this, "sitemapRender");
    wire()->addHookAfter("Modules::refresh", $this, "sitemapReset");
    wire()->addHookAfter("Pages::saved", $this, "sitemapReset");
  }

  public function sitemapLang(Language $lang): string
  {
    $langs = $this->sitemapLangData();
    return $langs->get($lang->name) ?: "";
  }

  private function sitemapLangData(): WireData
  {
    if ($this->sitemapLangData) return $this->sitemapLangData;
    $data = new WireData();
    $arr = array_filter(explode("\n", $this->langMaps));
    foreach ($arr as $item) {
      $item = trim($item);
      $parts = explode("=", $item, 2);
      $data->set($parts[0], $parts[1]);
    }
    $this->sitemapLangData = $data;
    return $data;
  }

  public function sitemapMarkup(): string
  {
    // start timer
    $time = Debug::startTimer();

    // make sure to render the sitemap as seen by the guest user
    // save current user for later
    $user = $this->wire->user;
    $this->wire->user = $this->wire->users->get('guest');

    // create markup
    $out = "<?xml version='1.0' encoding='UTF-8'?>\n";
    $out .= "<urlset xmlns='http://www.sitemaps.org/schemas/sitemap/0.9' xmlns:xhtml='http://www.w3.org/1999/xhtml'>\n";

    // recursive function to traverse the page tree
    $count = 0;
    $f = function ($items = null) use (&$f, &$out, &$count) {
      if (!$items) $items = wire()->pages->get(1);
      if ($items instanceof Page) $items = [$items];
      foreach ($items as $p) {
        /** @var Page $p */
        if (!$p->viewable()) continue;
        $callback = $this->sitemapCallback;
        $result = $callback($p);
        if ($result === false) {
          // don't traverse further down the tree
          return;
        } elseif ($result instanceof Page) {
          $out .= $this->sitemapMarkupPage($result);
        } elseif ($result) {
          // custom markup returned - add it to output
          $out .= "$result\n";
        }
        $count++;
        $f($p->children("include=hidden"));
      }
    };
    $f();
    $out .= '</urlset>';

    $seconds = Debug::stopTimer($time);
    $this->log("Sitemap showing $count pages generated in " . round($seconds * 1000) . " ms", [
      'url' => '/sitemap.xml',
    ]);

    $this->wire->user = $user;
    return $out;
  }

  private function sitemapMarkupPage(Page $page)
  {
    if ($page->noSitemap) return;

    $modified = date("Y-m-d", $page->modified);
    $multilang = "";

    // check for multilang system
    if ($this->wire->languages) {
      foreach ($this->wire->languages as $lang) {
        if ($lang->isDefault()) continue;

        // if page is not active in this language we dont add the alternate
        if ($page->get("status$lang") !== 1) continue;

        $this->wire->user->language = $lang;
        $multilang .= "<xhtml:link "
          . "rel='alternate' "
          . "hreflang='{$this->sitemapLang($lang)}' "
          . "href='{$page->httpUrl()}' />\n";
      }

      // reset language to default for final markup (default page)
      $this->wire->user->language = $this->wire->languages->getDefault();
    }

    // return final markup
    return "<url>\n"
      . "<loc>{$page->httpUrl()}</loc>\n"
      . $multilang
      . "<lastmod>$modified</lastmod>\n"
      . "</url>\n"
      . $page->sitemapAppendMarkup;
  }

  protected function sitemapRender()
  {
    // create sitemap.xml file
    $out = $this->sitemapMarkup();
    $file = $this->wire->config->paths->root . "sitemap.xml";
    $this->wire->files->filePutContents($file, $out);

    header('Content-Type: application/xml');
    return $out;
  }

  protected function sitemapReset(HookEvent $event): void
  {
    $file = $this->wire->config->paths->root . "sitemap.xml";
    if (is_file($file)) wire()->files->unlink($file);
  }

  /**
   * Get style tag
   *
   * $url must be relative to PW root!
   *
   * @param mixed $url
   * @param array $replacements
   * @return string
   * @throws WireException
   */
  public function styleTag($url): string
  {
    $href = wire()->config->versionUrl($url);
    return "<link rel='stylesheet' href='$href' />";
  }

  /**
   * Render svg file
   * @return string
   */
  public function svg($filename, $replacements = [])
  {
    if ($filename instanceof Pagefiles) $filename = $filename->first()->filename;
    elseif ($filename instanceof Pagefile) $filename = $filename->filename;
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
   * Load svg as DOM element
   *
   * Usage:
   * echo $rf->svgDom("/path/to/file.svg")->addClass("foo");
   *
   * You can also provide an url instead of the absolute path:
   * echo $rf->svgDom("/site/templates/img/icon.svg")->addClass("foo");
   *
   * @param mixed $data
   * @return HtmlPageCrawler
   * @throws LogicException
   */
  public function svgDom($data): HtmlPageCrawler
  {
    $str = $data;

    // if data is a pagefiles array we use the first pagefile
    if ($data instanceof Pagefiles) $data = $data->first();

    // if it is a pagefile get markup
    if ($data instanceof Pagefile) {
      $str = file_get_contents($data->filename);
    }

    // if data is a string that means it is a filepath or url
    elseif (is_string($data)) {
      $data = ProcessWirePaths::normalizeSeparators($data);

      // if the file does not exist we try to add the root path
      if (!is_file($data)) $data = $this->toPath($data);

      // if it is still no file we throw an exception
      if (!is_file($data)) {
        throw new WireException("File $data not found");
      }

      // $data is now the filepath, so we get the content
      $str = file_get_contents($data);
    }

    $dom = $this->dom($str)->filter("svg")->first();
    return $dom;
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

    // if htaccess file does not exist, create it
    if (!is_file($file)) $this->wire->files->filePutContents($file, "");

    // get content of files and content of rules to apply
    $content = $this->wire->files->fileGetContents($file);
    $rules = $this->wire->files->fileGetContents(__DIR__ . "/stubs/htaccess.txt");

    // prepare error message
    $err = "/site/templates/.htaccess not writeable - some template files might be publicly accessible!";

    // check if rules are added to htaccess file
    if (strpos($content, "# RockFrontend: ") === false) {
      // no rockfrontend rules in the htaccess file, try to add them
      if (is_writable($file)) {
        $this->wire->files->filePutContents($file, $rules, FILE_APPEND);
      } else $this->error($err);
    } elseif (strpos($content, $rules) === false) {
      // rockfrontend rules found, but outdated
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

  /** translation support in LATTE files */

  public function _($str)
  {
    $backtrace = debug_backtrace(limit: 1);
    $textdomain = self::textdomain($backtrace[0]["file"]);
    return \ProcessWire\__($str, $textdomain);
  }

  public function _x($str, $context)
  {
    $backtrace = debug_backtrace(limit: 1);
    $textdomain = self::textdomain($backtrace[0]["file"]);
    return \ProcessWire\_x($str, $context, $textdomain);
  }

  public function _n($textsingular, $textplural, $count)
  {
    $backtrace = debug_backtrace(limit: 1);
    $textdomain = self::textdomain($backtrace[0]["file"]);
    return \ProcessWire\_n($textsingular, $textplural, $count, $textdomain);
  }

  public function setTextdomain($file = false)
  {
    $this->textdomain = $file;
  }

  /**
   * Method to find the correct textdomain file for translations in latte files
   */
  public static function textdomain($file)
  {
    // if the translation was not invoked from a cached latte file
    // we return the file itself, which is the PHP file that called the
    // translation method
    if (!str_contains($file, '.latte--')) return $file;
    $content = file_get_contents($file);
    preg_match('/source: (.*?) /', $content, $matches);
    return $matches[1];
  }

  /** END translation support in LATTE files */

  /**
   * Ensures that given path is a path within the PW root.
   *
   * Usage:
   * $rockdevtools->toPath("/site/templates/foo.css");
   * $rockdevtools->toPath("/var/www/html/site/templates/foo.css");
   * @param string $path
   * @return string
   */
  public function toPath(string $path): string
  {
    $path = ProcessWirePaths::normalizeSeparators($path);
    $root = wire()->config->paths->root;
    if (str_starts_with($path, $root)) return $path;
    return $root . ltrim($path, '/');
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
      ProcessWirePaths::normalizeSeparators((string)$path) . $cache
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

  /** ##### module config ##### */

  /**
   * Config inputfields
   * @param InputfieldWrapper $inputfields
   */
  public function getModuleConfigInputfields($inputfields)
  {
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

    $this->configSEO($inputfields);
    $this->configLatte($inputfields);
    $this->configTailwind($inputfields);
    $this->configAjax($inputfields);
    $this->configSettings($inputfields);
    $this->configTools($inputfields);

    return $inputfields;
  }

  private function configAjax(InputfieldWrapper $inputfields): void
  {
    $html = '';
    foreach ($this->ajaxEndpoints() as $url => $file) {
      $href = rtrim(wire()->config->urls->root, '/') . $url;
      $html .= "<div><a href=$href>$url</a></div>";
    }
    $f = new InputfieldMarkup();
    $f->label = 'AJAX';
    $f->icon = 'exchange';
    $f->value = $html ?: 'No endpoints found.';
    $f->notes = 'To create a new endpoint simply add a LATTE or PHP file in /site/templates/ajax
      Detailed docs at [https://www.baumrock.com/en/processwire/modules/rockfrontend/docs/ajax/](https://www.baumrock.com/en/processwire/modules/rockfrontend/docs/ajax/)';
    $f->collapsed = Inputfield::collapsedYes;
    $inputfields->add($f);
  }

  private function configLatte(InputfieldWrapper $inputfields): void
  {
    $this->copyLayoutFileIfNewer();

    $fs = new InputfieldFieldset();
    $fs->label = "Latte";
    $fs->icon = "code";
    $fs->collapsed = Inputfield::collapsedYes;
    $inputfields->add($fs);

    $f = new InputfieldCheckbox();
    $f->name = "noLayoutFile";
    $f->label = "Disable Autoload-Layout";
    $f->notes = 'Please see [the docs](https://www.baumrock.com/en/processwire/modules/rockfrontend/docs/autoload-layout/) for details!';
    $f->attr('checked', $this->noLayoutFile);
    $fs->add($f);

    $f = new InputfieldCheckbox();
    $f->name = "copyLayoutFile";
    $f->entityEncodeLabel = false;
    $f->label = "Copy file <i class='uk-background-muted' style='padding: 5px 10px;'>_rockfrontend.php</i> to /site/templates";
    $f->attr('checked', $this->copyLayoutFile);
    $f->showIf = "noLayoutFile=0";
    $f->notes = 'Make sure to also set this in /site/config.php:
      $config->appendTemplateFile = "_rockfrontend.php";';
    $fs->add($f);

    $dir = $this->wire->config->paths->templates;
    $f = new InputfieldText();
    $f->name = 'layoutFile';
    $f->label = 'Filename of Autoload-Layout';
    $f->icon = 'file-code-o';
    $f->value = $this->layoutFile ?: self::layoutFile;
    $f->notes = "File relative to $dir";
    $f->showIf = "noLayoutFile=0";
    $fs->add($f);
  }

  private function configSEO($inputfields)
  {
    $fs = new InputfieldFieldset();
    $fs->label = "SEO";
    $fs->icon = "search";
    $fs->collapsed = Inputfield::collapsedYesAjax;
    $inputfields->add($fs);
    $root = $this->wire->config->paths->root;
    $warn = '<svg style="color:#F9A825" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"><path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12a9 9 0 1 0 18 0a9 9 0 1 0-18 0m9-3v4m0 3v.01"/></svg>';
    $check = '<svg style="color:#388E3C" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"><g fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"><path d="M3 12a9 9 0 1 0 18 0a9 9 0 1 0-18 0"/><path d="m9 12l2 2l4-4"/></g></svg>';

    if ($this->wire->config->ajax || $this->wire->input->post->langMaps) {
      $http = new WireHttp();

      $fs->add([
        'type' => 'markup',
        'label' => 'robots.txt',
        'value' => is_file($root . "robots.txt")
          ? "$check robots.txt is present"
          : "$warn no robots.txt in site root",
        'columnWidth' => 50,
      ]);

      $httpUrl = $this->wire->pages->get(1)->httpUrl() . "sitemap.xml";
      $hasSitemap = $http->status($httpUrl) === 200;
      $fs->add([
        'type' => 'markup',
        'label' => 'sitemap.xml',
        'value' => $hasSitemap
          ? "$check sitemap.xml was found"
          : "$warn no sitemap.xml in site root",
        'notes' => is_file($root . "sitemap.xml")
          ? 'Open [sitemap.xml](/sitemap.xml)'
          : 'See [docs](https://www.baumrock.com/en/processwire/modules/rockfrontend/docs/seo/).',
        'columnWidth' => 50,
      ]);

      $markup = $http->get($this->wire->pages->get(1)->httpUrl());
      $url = $http->getResponseHeaders('location') ?: '/';
      $dom = rockfrontend()->dom($markup);
      $ogimg = $dom->filter("meta[property='og:image']")->count() > 0;
      $minifyWarning = strpos($markup, "property=og:image") === false
        ? "" : "It looks like you are using ProCache's remove quotes from tag attributes feature - this feature will break WhatsApp preview images on Android! See [this forum post](https://processwire.com/talk/topic/29831-why-does-whatsapp-not-show-a-preview-image-for-my-site/?do=findComment&comment=240133)";
      $fs->add([
        'type' => 'markup',
        'label' => 'og:image',
        'value' => $ogimg
          ? "$check og:image tag found on page $url"
          : "$warn no og:image tag on page $url",
        'columnWidth' => 50,
        'notes' => $minifyWarning,
      ]);

      $favicon = $this->wire->pages->get(1)->httpUrl() . "favicon.ico";
      $hasFavicon = $http->status($favicon) === 200;
      $fs->add([
        'type' => 'markup',
        'label' => 'favicon.ico',
        'value' => $hasFavicon
          ? "$check favicon.ico was found"
          : "$warn no favicon.ico in site root",
        'notes' => $hasFavicon
          ? ''
          : 'Use [realfavicongenerator](https://realfavicongenerator.net/) to add a favicon to your site.',
        'columnWidth' => 50,
      ]);

      $fs->add([
        'type' => 'textarea',
        'label' => 'Sitemap Language Mappings',
        'name' => 'langMaps',
        'description' => 'Here you can define the language shortcode that ends up in the sitemaps "hreflang" attribute. Don\'t add the default language here.',
        'notes' => 'A setting of "german=de" will lead to output hreflang=de in your sitemap, where "german" is the name of the language.',
        'value' => $this->langMaps,
      ]);
    }
  }

  private function configSettings($inputfields)
  {
    $fs = new InputfieldFieldset();
    $fs->label = "Settings";
    $fs->icon = "cogs";
    $fs->collapsed = Inputfield::collapsedYes;
    $inputfields->add($fs);

    $fs->add([
      'type' => 'text',
      'name' => 'ideLinkHandler',
      'label' => 'IDE Link Handler',
      'value' => $this->ideLinkHandler,
      'notes' => 'Default: vscode://file/%file',
    ]);

    $f = new InputfieldText();
    $f->label = 'Webfonts';
    $f->name = 'webfonts';
    $f->icon = "font";
    $f->description = "Enter url to webfonts ([fonts.google.com](https://fonts.google.com/)). These webfonts will automatically be downloaded to /site/templates/webfonts and a file webfonts.less will be created with the correct paths. The download will only be triggered when the URL changed and it will wipe the fonts folder before download so that unused fonts get removed."
      . "\nExample URL: https://fonts.googleapis.com/css2?family=Ubuntu:wght@400;700 (see here: [screenshot](https://i.imgur.com/b8aJPQW.png))";
    $f->value = $this->webfonts;
    $f->notes = $this->showFontFileSize();
    $fs->add($f);

    $f = new InputfieldInteger();
    $f->name = 'remBase';
    $f->label = 'REM base font size';
    $f->value = $this->remBase;
    $f->notes = 'See [this forum thread for more info](https://processwire.com/talk/topic/29268-fr-make-rembase-a-config-setting/)';
    $fs->add($f);

    $f = $this->wire->modules->get('InputfieldCheckboxes');
    $f->name = 'features';
    $f->label = "Features";
    $f->icon = "star-o";
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
  }

  private function configTailwind(&$inputfields)
  {
    $fs = new InputfieldFieldset();
    $fs->label = "Tailwind CSS";
    $fs->icon = "css3";
    $fs->name = "config-tailwind";
    $fs->collapsed = Inputfield::collapsedYesAjax;
    $inputfields->add($fs);
    if ($this->wire->input->post->installTailwind) {
      $this->wire->files->copy(
        $this->wire->config->paths->root . "site/modules/RockFrontend/tailwind",
        $this->wire->config->paths->root,
      );
    }

    // all below only when ajax loading
    if (!$this->wire->config->ajax) return;

    $conf = $this->wire->config->paths->root . "tailwind.config.js";
    $pack = $this->wire->config->paths->root . "package.json";

    $fs->add([
      'type' => 'markup',
      'label' => 'NOTE',
      'value' => 'This section is intended to be used with the <a href=https://github.com/baumrock/site-rockfrontend>RockFrontend site profile</a>.',
    ]);

    $fs->add([
      'type' => 'markup',
      'label' => 'package.json',
      'value' => is_file($pack)
        ? "✅ file found"
        : "file not found - please check the checkbox and submit the form",
      'columnWidth' => 50,
    ]);

    $fs->add([
      'type' => 'markup',
      'label' => 'tailwind.config.js',
      'value' => is_file($conf)
        ? "✅ file found"
        : "file not found - please check the checkbox and submit the form",
      'columnWidth' => 50,
    ]);

    $fs->add([
      'type' => 'checkbox',
      'label' => 'Install Tailwind CSS',
      'name' => 'installTailwind',
      'notes' => 'WARNING: This will copy package.json and tailwind.config.js to the root directory of your project. Existing files will be overwritten!',
    ]);

    $fs->add([
      'type' => 'markup',
      'label' => 'Finish Installation',
      'value' => 'After installation you have to run the following command in your ProcessWire root directory from the command line:
        <pre style="margin-top:10px;margin-bottom:10px;">npm install -D</pre>
        Then execute "npm run build" to see if it works. It should show something like this:
        <pre style="margin-top:10px;margin-bottom:10px;">Rebuilding...' . "\n"
        . 'Done in 123ms</pre>',
    ]);
  }

  private function configTools(&$inputfields)
  {
    $fs = new InputfieldFieldset();
    $fs->label = "Tools";
    $fs->icon = "wrench";
    $fs->collapsed = Inputfield::collapsedYes;

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

    // $wrapper->add([
    //   'type' => 'markup',
    //   'label' => 'Icon',
    //   'value' => 'TBD',
    // ]);

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
      'autoloadStyles' => $this->autoloadStyles,
      'autoloadScripts' => $this->autoloadScripts,
      'ajaxEndpoints' => $this->ajaxEndpoints(),
    ];
  }
}
