<?php namespace RockFrontend;

use ProcessWire\Debug;

class AssetsArray extends \ProcessWire\WireArray {

  public $name;

  /** @var array */
  protected $options = [
    'autoload' => true, // flag to autoload default scripts and styles
  ];

  public function __construct(string $name) {
    $this->name = $name;
    parent::__construct();
  }

  /**
   * @return self
   */
  public function add($file, $suffix = '') {
    if(!$file instanceof AssetComment) $file = new Asset($file, $suffix);
    parent::add($file);
    return $this;
  }

  /**
   * Add all files of folder to assets array
   *
   * Depth is 2 to make it work with RockMatrix by default.
   *
   * @return self
   */
  public function addAll($path, $suffix = '', $levels = 2, $ext = ['js']) {
    /** @var RockFrontend $rf */
    $rf = $this->wire('modules')->get('RockFrontend');
    $path = $rf->getPath($path);
    $files = $this->wire->files->find($path, [
      'recursive' => $levels,
      'extensions' => $ext,
    ]);
    foreach($files as $f) $this->add($f, $suffix);
    return $this;
  }

  /**
   * @return self
   */
  public function addIf($file, $condition, $suffix = '') {
    if($condition) parent::add(new Asset($file, $suffix));
    return $this;
  }

  public function comment($str): self {
    $comment = new AssetComment($str);
    $this->add($comment);
    return $this;
  }

  /**
   * Get options value
   * @return mixed
   */
  public function opt(string $key) {
    $opt = $this->options;
    if(array_key_exists($key, $opt)) return $opt[$key];
  }

  /**
   * @return self
   */
  public function prepend($file, $suffix = '') {
    $file = new Asset($file, $suffix);
    parent::prepend($file);
    return $this;
  }

  /**
   * Set options for rendering
   */
  public function setOptions(array $options): self {
    $this->options = array_merge($this->options, $options);
    return $this;
  }

  /**
   * Magic toString Method
   * We return an empty string in case an AssetsArray is requested as string
   * This is to make it possible to add scripts and styles from within latte files
   * {$rockfrontend->styles()->add(...)}
   * Without this magic method that would output something like "array|array"
   */
  public function __toString() {
    return '';
  }

  public function __debugInfo() {
    return array_merge([
      'name' => $this->name,
    ], parent::__debugInfo());
  }

}
