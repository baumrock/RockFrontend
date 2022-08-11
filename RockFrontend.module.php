<?php namespace ProcessWire;

use Latte\Engine;
use RockFrontend\ScriptsArray;
use RockFrontend\Seo;
use RockFrontend\StylesArray;
use RockMatrix\Block;

/**
 * @author Bernhard Baumrock, 05.01.2022
 * @license MIT
 * @link https://www.baumrock.com
 *
 * @method string render($filename, array $vars = array(), array $options = array())
 */
class RockFrontend extends WireData implements Module, ConfigurableModule {

  const tags = "RockFrontend";
  const prefix = "rockfrontend_";
  const tagsUrl = "/rockfrontend-layout-suggestions/{q}";
  const permission_alfred = "rockfrontend-alfred";
  const livereloadCacheName = "rockfrontend_livereload"; // also in livereload.php
  const cache = 'rockfrontend-uikit-versions';
  const installedprofilekey = 'rockfrontend-installed-profile';
  const recompile = 'rockfrontend-recompile-less';

  const field_layout = self::prefix."layout";

  /** @var WireArray $folders */
  public $folders;

  public $home;

  /** @var bool */
  public $hasAlfred = false;

  /** @var Engine */
  private $latte;

  /** @var WireArray $layoutFolders */
  public $layoutFolders;

  /** @var string */
  public $path;

  /** @var Seo */
  public $seo;

  private $scripts;
  private $styles;

  /** @var array */
  private $translations = [];

  public static function getModuleInfo() {
    return [
      'title' => 'RockFrontend',
      'version' => '1.14.1',
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

    require_once($this->path."Asset.php");
    require_once($this->path."AssetComment.php");
    require_once($this->path."AssetsArray.php");
    require_once($this->path."StylesArray.php");
    require_once($this->path."ScriptsArray.php");

    // make $rockfrontend and $home variable available in template files
    $this->wire('rockfrontend', $this);
    $this->wire('home', $this->home);

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

    // Alfred
    require_once __DIR__ . "/Functions.php";
    $this->createPermission(self::permission_alfred,
    "Is allowed to use ALFRED frontend editing");
    $this->createCSS();
    if($this->loadAlfred()) {
      $this->scripts()->add($this->path."Alfred.js");
      $this->styles()->add($this->path."Alfred.css");
    }

    // hooks
    $this->addHookAfter("ProcessPageEdit::buildForm", $this, "hideLayoutField");
    $this->addHook(self::tagsUrl, $this, "layoutSuggestions");
    $this->addHookAfter("Modules::refresh", $this, "refreshModules");
  }

  public function ready() {
    $this->liveReload();
    $this->addAssets();
  }

  /**
   * Add assets to the html markup
   * @return void
   */
  public function addAssets() {
    $rockfrontend = $this;
    $this->addHookAfter(
      "Page::render",
      function(HookEvent $event) use($rockfrontend) {
        $page = $event->object;
        $html = $event->return;
        $styles = $this->styles();
        $scripts = $this->scripts();

        // early exit if html does not contain a head section
        if(!strpos($html, "</head>")) return;

        // add livereload secret
        if($this->wire->config->livereload) {
          $secret = $this->getLivereloadSecret();
          $html = str_replace(
            "</head>",
            "\n  <script>let rf_livereload_secret = '$secret'</script></head>",
            $html
          );
        }

        // autoload scripts and styles
        $rockfrontend->autoload($page);

        // check if assets have already been added
        // if not we inject them at the end of the <head>
        $assets = '';
        if(!strpos($html, StylesArray::comment)) $assets .= $styles->render();
        if(!strpos($html, ScriptsArray::comment)) $assets .= $scripts->render();

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

  private function addLiveReloadScript() {
    // add script to the backend
    if($this->wire->page->template == 'admin') {
      $process = $this->wire->page->process;

      // on some pages in the backend live reloading can cause problems
      // or is just not helpful so we exclude it
      if($process == "ProcessModule") return;
      if($process == "ProcessPageList") return;

      $url = $this->wire->config->urls($this);
      $m = filemtime($this->path."livereload.js");
      $this->wire->config->scripts->add($url."livereload.js?m=$m");
    }
    // add script to the frontend
    else {
      $this->scripts('head')->add($this->path."livereload.js");
    }
  }

  /**
   * Return link to add a new page under given parent
   * @return string
   */
  public function addPageLink($parent) {
    $admin = $this->wire->pages->get(2)->url;
    return $admin."page/add/?parent_id=$parent";
  }

  /**
   * ALFRED - A Lovely FRontend EDitor
   *
   * Usage:
   * alfred($page, "title,images")
   *
   * Usage with options array (for RockMatrix blocks)
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
  public function alfred($page = null, $options = []) {
    if(!$this->wire->user->isLoggedin()) return;

    // support short syntax
    if(is_string($options)) $options = ['fields'=>$options];

    // set flag to show that at least one alfred tag is on the page
    // this flag is used to load the PW frontend editing assets
    $this->hasAlfred = true;
    $page = $page ? $this->wire->pages->get((string)$page) : false;

    // is given page a widget block stored in field rockmatrix_widgets?
    $isWidget = false;
    if($page instanceof Block AND $page->isWidget()) $isWidget = true;

    // setup options
    $opt = $this->wire(new WireData()); /** @var WireData $opt */
    $opt->setArray([
      'addTop' => false,
      'addBottom' => false,
      'move' => $isWidget ? false : true,
      'isWidget' => $isWidget, // is block saved in rockmatrix_widgets?
      'widgetStyle' => $isWidget, // make it orange
      'trash' => true, // will set the trash icon for rockmatrix blocks
      'fields' => '', // fields to edit
    ]);
    $opt->setArray($options);

    // prepare fields suffix
    $fields = '';
    if($opt->fields) {
      if(is_array($opt->fields)) $opt->fields = implode(",", $opt->fields);

      // check if the requested fields are available on that page
      // if the field does not exist for that page we don't request it
      // this is to prevent exception errors breaking page editing
      $sep = "&fields=";
      foreach(explode(",", $opt->fields) as $field) {
        $field = trim($field);
        if(!$page->template->hasField($field)) continue;
        $fields .= $sep.$field;
        $sep = ",";
      }
    }

    // icons
    $icons = [];
    if($page AND $page->editable()) {
      $icons[] = (object)[
        'icon' => 'edit',
        'label' => $page->title,
        'tooltip' => "Edit Block #{$page->id}",
        'href' => $page->editUrl().$fields,
        'class' => 'pw-modal',
        'suffix' => 'data-buttons="button.ui-button[type=submit]" data-autoclose data-reload',
      ];
    }
    if($page AND $page instanceof Block) {
      // if the _block context is set for this block we use it as block
      // this is to support the concept of "widgets" where widgets render global blocks.
      // when trashing such a block we want to trash the reference widget and not the global block itself!
      $block = $page;
      $widget = $page->_widget ?: $page;
      if($opt->move) {
        $icons[] = (object)[
          'icon' => 'move',
          'label' => $block->title,
          'tooltip' => "Move Block #{$widget->id}",
          'class' => 'pw-modal',
          'href' => $widget->getMatrixPage()->editUrl."&field=".$widget->getMatrixField()."&moveblock=$widget",
          'suffix' => 'data-buttons="button.ui-button[type=submit]" data-autoclose data-reload',
        ];
      }
      if($opt->trash AND $page->trashable()) {
        $icons[] = (object)[
          'icon' => 'trash-2',
          'label' => $page->title,
          'tooltip' => "Trash Block #{$widget->id}",
          'href' => $widget->rmxUrl("/trash/?block=$widget"),
          'confirm' => __('Do you really want to delete this element?'),
        ];
      }
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
        $less = str_replace($this->wire->config->paths->root, $root, $less);
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
      // see explanation about widget above
      $block = $page;
      $widget = $page->_widget ?: $page;

      if(!$isWidget) $opt->addTop = $widget->rmxUrl("/add/?block=$widget&above=1");
      if(!$isWidget) $opt->addBottom = $widget->rmxUrl("/add/?block=$widget");
    }

    $str = json_encode((object)[
      'icons' => $icons,
      'addTop' => $opt->addTop,
      'addBottom' => $opt->addBottom,
      'widgetStyle' => $opt->widgetStyle,
    ]);
    return " alfred='$str'";
  }

  /**
   * Autoload scripts and styles
   */
  public function autoload($page) {
    $styles = $this->styles();
    $scripts = $this->scripts();

    if($page->template != 'admin') {
      // frontend
      if($styles->opt('autoload')) {
        $styles->addAll('layouts');
        $styles->addAll('sections');
        $styles->addAll('partials');
        $styles->addAll('/site/assets/RockMatrix');
      }
    }
  }

  /**
   * Create CSS from LESS file
   * @return void
   */
  private function createCSS() {
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
  private function createPermission($name, $title) {
    $p = $this->wire->permissions->get($name);
    if($p AND $p->id) return;
    $p = $this->wire->permissions->add($name);
    $p->setAndSave('title', $title);
  }

  /**
   * Download uikit
   * @return void
   */
  private function downloadUikit() {
    if(!$version = $this->wire->input->post('uikit', 'string')) return;
    $url = "https://github.com/uikit/uikit/archive/refs/tags/$version.zip";
    $tpl = $this->wire->config->paths->templates;
    $tmp = (new WireTempDir());
    (new WireHttp())->download($url, $tmp."uikit.zip");
    $this->wire->files->unzip($tmp."uikit.zip", $tpl);
  }

  public function editLinks($page = null, $list = true, $size = 32) {
    if(!$page) $page = $this->wire->page;
    if(!$page->editable()) return;
    $pages = $this->wire->pages;
    $li = $list ? '<li>' : '';
    $endli = $list ? '</li>' : '';
    return "
      $li
        <a href='{$pages->get(2)->url}'>
          <svg xmlns='http://www.w3.org/2000/svg' xmlns:xlink='http://www.w3.org/1999/xlink' aria-hidden='true' role='img' class='iconify iconify--tabler' width='$size' height='$size' preserveAspectRatio='xMidYMid meet' viewBox='0 0 24 24'><g fill='none' stroke='currentColor' stroke-linecap='round' stroke-linejoin='round' stroke-width='2'><rect width='6' height='6' x='3' y='15' rx='2'></rect><rect width='6' height='6' x='15' y='15' rx='2'></rect><rect width='6' height='6' x='9' y='3' rx='2'></rect><path d='M6 15v-1a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v1m-6-6v3'></path></g></svg>
        </a>
      $endli
      $li
        <a href='{$page->editUrl()}'>
          <svg xmlns='http://www.w3.org/2000/svg' xmlns:xlink='http://www.w3.org/1999/xlink' aria-hidden='true' role='img' class='iconify iconify--tabler' width='$size' height='$size' preserveAspectRatio='xMidYMid meet' viewBox='0 0 24 24'><g fill='none' stroke='currentColor' stroke-linecap='round' stroke-linejoin='round' stroke-width='2'><path d='M7 7H6a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h9a2 2 0 0 0 2-2v-1'></path><path d='M20.385 6.585a2.1 2.1 0 0 0-2.97-2.97L9 12v3h3l8.385-8.415zM16 5l3 3'></path></g></svg>
        </a>
      $endli
    ";
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
    // $file = "/".ltrim($file, "/");

    // if no extension was provided try php or latte extension
    if(!pathinfo($file, PATHINFO_EXTENSION)) {
      if($f = $this->getFile("$file.php", $forcePath)) return realpath($f);
      if($f = $this->getFile("$file.latte", $forcePath)) return realpath($f);
    }

    // if file exists return it
    // this will also find files relative to /site/templates!
    // TODO maybe prevent loading of relative paths outside assets?
    $inRoot = $this->wire->files->fileInPath($file, $this->wire->config->paths->root);
    if($inRoot AND is_file($file)) return realpath($file);

    // look for the file in specified folders
    foreach($this->folders as $folder) {
      $folder = Paths::normalizeSeparators($folder);
      $folder = rtrim($folder,"/")."/";
      $path = $folder.ltrim($file,"/");
      if(is_file($path)) return realpath($path);
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
   * Request live reload secret or create a new one
   * @return string
   */
  public function getLivereloadSecret() {
    $cachefile = $this->wire->config->paths->cache.self::livereloadCacheName.".txt";
    return $this->wire->cache->get(
      self::livereloadCacheName,
      60*60,
      function() use($cachefile) {
        $secret = (new WireRandom())->alphanumeric(0, [
          'minLength' => 40,
          'maxLength' => 60,
        ]);
        $this->wire->files->filePutContents($cachefile, $secret);
        return $secret;
      }
    );
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
    // we also make sure that the path is somewhere within the pw root
    // to prevent open basedir restriction warnings
    $inRoot = strpos($path, $this->wire->config->paths->root) === 0;
    if(strpos($path, '/')===0 AND $inRoot AND is_dir($path)) {
      return rtrim($path,'/').'/';
    }

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

  /*
   * Get translated key by string
   * @return string
   */
  public function getTranslation($key) {
    if(array_key_exists($key, $this->translations)) return $this->translations[$key];
    return '';
  }

  /**
   * Get uikit versions from github
   */
  public function getUikitVersions() {
    return $this->wire->cache->get(self::cache, 60*5, function() {
      $http = new WireHttp();
      $json = $http->get('https://api.github.com/repos/uikit/uikit/git/refs/tags');
      $refs = json_decode($json);
      $versions = [];
      foreach($refs as $ref) {
        $version = str_replace("refs/tags/", "", $ref->ref);
        $v = $version;
        if(strpos($version, "v.")===0) continue;
        if(strpos($version, "v")!==0) continue;
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
  public function hideLayoutField(HookEvent $event) {
    if($this->wire->user->isSuperuser()) return;
    $form = $event->return;
    $form->remove(self::field_layout);
  }

  /**
   * Render icon link
   * @return string
   */
  public function iconLink($icon, $href, $options = []) {
    $opt = $this->wire(new WireData()); /** @var WireData $opt */
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
    $title = $opt->title ? "title='{$opt->title}'" : "";
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
  public function img($file) {
    $url = $this->url($file);
    if($url) return "<img src='$url'>";
    return '';
  }

  /**
   * Is the given page active in the menu?
   * @return bool
   */
  public function isActive($menuItem, $page = null) {
    $page = $page ?: $this->wire->page;
    $active = $page->parents()->add($page);
    return $active->has($menuItem);
  }

  /**
   * Return layout suggestions
   */
  public function layoutSuggestions(HookEvent $event) {
    return $this->findSuggestFiles($event->q);
  }

  /**
   * Setup live reloading
   */
  public function livereload() {
    // early exit if live reload is disabled
    if(!$this->wire->config->livereload) return;

    // early exit when page is opened in modal window
    // this is to prevent enless reloads when the parent frame is reloading
    if($this->wire->input->get('modal')) return;

    // reset the livereload secret on every modules refresh
    $cachefile = $this->wire->config->paths->cache.self::livereloadCacheName.".txt";
    $this->addHookAfter("Modules::refresh", function() use($cachefile) {
      if(is_file($cachefile)) $this->wire->files->unlink($cachefile);
      $this->wire->cache->save(self::livereloadCacheName, null);
    });

    // copy stubfile to PW root if it does not exist
    try {
      $root = $this->wire->config->paths->root;
      if(!is_file($root."livereload.php")) {
        $this->wire->files->copy(__DIR__."/stubs/livereload.php", $root);
      }
    } catch (\Throwable $th) {
      $this->log($th->getMessage());
    }

    // add live reloading script
    $this->addLiveReloadScript();
  }

  /**
   * Load ALFRED assets?
   */
  protected function loadAlfred(): bool {
    if(!$this->hasAlfred) return false;
    if($this->wire->user->isSuperuser()) return true;
    if($this->wire->user->hasPermission(self::permission_alfred)) return true;
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
  }

  /**
   * Copy profile files to PW root
   * @return void
   */
  private function profileExecute() {
    $profile = $this->wire->input->post('profile', 'filename');
    foreach($this->profiles() as $path=>$label) {
      if($label !== $profile) continue;
      $this->wire->files->copy("$path/files", $this->wire->config->paths->root);
      $this->wire->message("Copied profile $label to PW");
      $this->wire->pages->get(1)->meta(
        self::installedprofilekey,
        $profile." (last installed @ ".date("Y-m-d H:i:s").")"
      );
      return true;
    }
    return false;
  }

  /**
   * Get array of available profiles
   * @return array
   */
  private function profiles() {
    $profiles = [];
    $path = Paths::normalizeSeparators(__DIR__."/profiles");
    foreach(array_diff(scandir($path), ['.','..']) as $label) {
      $profiles["$path/$label"] = $label;
    }
    return $profiles;
  }

  /**
   * Things to do when modules are refreshed
   */
  public function refreshModules() {
    $this->wire->session->set(self::recompile, true);
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

    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    if($ext == 'php') {
      $options = $opt->getArray();
      return $this->wire->files->render($file, $vars, $options);
    }
    elseif($ext == 'svg') return $this->svg($file);

    try {
      $method = "renderFile".ucfirst(strtolower($ext));
      return $this->$method($file, $vars);
    } catch (\Throwable $th) {
      return $th->getMessage();
    }

  }

  /**
   * LATTE renderer
   */
  protected function renderFileLatte($file, $vars) {
    $latte = $this->latte;
    if(!$latte) {
      try {
        require_once $this->path."vendor/autoload.php";
        $latte = new Engine();
        $latte->setTempDirectory($this->wire->config->paths->cache."Latte");
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
  protected function renderFileSvg($file) {
    return $this->svg($file);
  }

  /**
   * Twig renderer
   */
  protected function renderFileTwig($file, $vars) {
    try {
      require_once $this->wire->config->paths->root.'vendor/autoload.php';
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
      return $th->getMessage().
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
  public function renderIf($path, $condition, $vars = null, $options = []) {
    $render = $condition;
    if(is_string($condition)) {
      // condition is a string so we assume it is a page selector
      $render = $this->wire->page->matches($condition);
    }
    if($render) return $this->render($path, $vars, $options);
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
   * @return Seo
   */
  public function seo() {
    if($this->seo) return $this->seo;
    require_once __DIR__."/Seo.php";
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
  public function scripts($name = 'head') {
    if(!$this->scripts) $this->scripts = new WireData();
    $script = $this->scripts->get($name) ?: new ScriptsArray($name);
    $this->scripts->set($name, $script);
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
   * $rockfrontend->styles()->add(...);
   * // file2.php
   * $rockfrontend->styles()->add(...);
   * // _main.php
   * $rockfrontend->styles()->render();
   *
   * @return StylesArray
   */
  public function styles($name = 'head') {
    if(!$this->styles) $this->styles = new WireData();
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
   * Render svg file
   * @return string
   */
  public function svg($filename) {
    if(!is_file($filename)) return;
    // we use file_get_contents because $files->render can cause parse errors
    // see https://wordpress.stackexchange.com/a/256445
    return file_get_contents($filename);
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
    $inRoot = $this->wire->files->fileInPath($path, $config->paths->root);
    $m = ($inRoot AND is_file($path) AND $cacheBuster) ? "?m=".filemtime($path) : '';
    return str_replace($config->paths->root, $config->urls->root, $path.$m);
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
  public function x($translations) {
    if(is_array($translations)) {
      return $this->translations = array_merge($this->translations, $translations);
    }
    return $this->getTranslation($translations);
  }

  public function ___install() {
    $this->init();
    if($this->rm()) $this->migrate();
  }

  /**
  * Config inputfields
  * @param InputfieldWrapper $inputfields
  */
  public function getModuleConfigInputfields($inputfields) {

    $help = new InputfieldMarkup();
    $help->label = 'Available Profiles';
    $help->collapsed(Inputfield::collapsedYes);
    $inputfields->add($help);

    $this->profileExecute();
    $f = new InputfieldSelect();
    $f->label = "Install Profile";
    $f->description = "**WARNING** This will overwrite existing files - make sure to have backups or use GIT for version controlling your project!";
    $f->name = 'profile';
    $help->value = '<style>h2 {margin:0}</style>';
    foreach($this->profiles() as $path => $label) {
      $help->value .= $this->wire->sanitizer->entitiesMarkdown(
        file_get_contents("$path/readme.md"),
        true
      );
      $f->addOption($label, $label);
    }
    $f->notes = $this->profileInstalledNote();
    $inputfields->add($f);

    // download uikit
    $this->downloadUikit();
    $f = new InputfieldSelect();
    $f->name = 'uikit';
    $f->label = 'Download UIkit';
    $f->notes = "Will be downloaded to /site/templates/";
    foreach($this->getUikitVersions() as $k=>$v) $f->addOption($k);
    $inputfields->add($f);
    $this->addUikitNote($f);

    return $inputfields;
  }

  private function addUikitNote(InputfieldSelect $f) {
    $note = '';
    foreach(scandir($this->wire->config->paths->templates) as $p) {
      if(strpos($p, "uikit-")!==0) continue;
      $note .= "\nFound /site/templates/$p";
    }
    $f->notes .= $note;
  }

  public function profileInstalledNote() {
    $note = $this->wire->pages->get(1)->meta(self::installedprofilekey);
    if($note) return "Installed profile: $note";
  }

}
