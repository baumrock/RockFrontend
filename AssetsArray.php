<?php namespace RockFrontend;
class AssetsArray extends \ProcessWire\WireArray {

  public $name;

  public function __construct($name) {
    $this->name = $name;
    parent::__construct();
  }

  /**
   * @return self
   */
  public function add($file, $suffix = '') {
    $file = new Asset($file, $suffix);
    parent::add($file);
    return $this;
  }

  /**
   * Add all files of folder to assets array
   * @return self
   */
  public function addAll($path, $suffix = '', $levels = 1, $ext = ['js']) {
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

  public function __debugInfo() {
    return array_merge([
      'name' => $this->name,
    ], parent::__debugInfo());
  }

}
