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

  const field_layout = self::prefix."layout";

  /** @var WireArray $folders */
  public $folders;

  public static function getModuleInfo() {
    return [
      'title' => 'RockFrontend',
      'version' => '0.0.1',
      'summary' => 'Module for easy frontend development',
      'autoload' => true,
      'singular' => true,
      'icon' => 'code',
      'requires' => [],
      'installs' => [],
    ];
  }

  public function init() {
    $this->wire('rockfrontend', $this);
    $this->rm()->fireOnRefresh($this, "migrate");

    // setup folders that are scanned for files
    $this->folders = $this->wire(new WireArray());
    $this->folders->add($this->config->paths->templates);
    $this->folders->add($this->config->paths->assets);

    // hooks
    $this->addHookAfter("ProcessPageEdit::buildForm", $this, "hideLayoutField");
  }

  /**
   * Get file path of file
   * If path is relative we look in the assets folder of RockUikit
   * @return string
   */
  public function getFile($file) {
    $file = Paths::normalizeSeparators($file);

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
      $file = $folder.ltrim($file,"/");
      if(is_file($file)) return $file;
    }

    // no file, return false
    return false;
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

  public function migrate() {
    $rm = $this->rm();
    $rm->migrate([
      'fields' => [
        self::field_layout => [
          'type' => 'textarea',
          'tags' => self::tags,
          'label' => 'Layout',
          'icon' => 'cubes',
          'collapsed' => Inputfield::collapsedYes,
          'notes' => 'This field is only visible to superusers',
        ],
      ],
    ]);
  }

  /**
   * Render file
   *
   * If path is provided as array then the first path that returns
   * some output will be used. This makes it possible to define a fallback
   * for rendering: echo $uk->render(["$template.php", "basic-page.php"]);
   *
   * Usage with selectors:
   * echo $uk->render([
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

    // options
    $opt = $this->wire(new WireData()); /** @var WireData $opt */
    $opt->setArray([
      'allowedPaths' => [
        $this->assets,
        $this->wire->config->paths->templates,
      ],
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
   * @return RockMigrations
   */
  public function rm() {
    return $this->wire->modules->get('RockMigrations');
  }

  public function ___install() {
    $this->init();
    $this->migrate();
  }

}
