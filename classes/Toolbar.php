<?php

namespace RockFrontend;

use ProcessWire\Paths;
use ProcessWire\Wire;
use ProcessWire\WireArray;
use ProcessWire\WireException;
use ProcessWire\WirePermissionException;
use Wa72\HtmlPageDom\HtmlPageCrawler;

use function ProcessWire\rockfrontend;
use function ProcessWire\wire;

class Toolbar extends Wire
{
  public $markup;

  /** @var ToolsArray */
  public $tools;

  /**
   * Hookable method to determine if toolbar should be shown
   */
  public function ___showToolbar(): bool
  {
    return wire()->user->isLoggedin();
  }

  /**
   * Class constructor
   */
  public function __construct()
  {
    $this->setupTools();
  }

  /**
   * Render this toolbar
   */
  public function __toString(): string
  {
    if (!$this->showToolbar()) return "";
    $items = $this->renderItems();
    // note: we return one root html element for dom() to work properly!
    $css = wire()->files->render(
      wire()->config->paths(rockfrontend()) . 'dst/toolbar.min.css'
    );
    $js = wire()->config->urls(rockfrontend()) . 'dst/toolbar.min.js';
    return "<section id='rockfrontend-toolbar'>
        <style>$css</style>
        <div id='toolbar-tools'>$items</div>
        <script src=$js></script>
      </section>";
  }

  public function addEdit(): void {}

  public function dom(): HtmlPageCrawler
  {
    return rockfrontend()->dom((string)$this, false);
  }

  public function loadTool(string $file): void
  {
    if (!is_file($file)) {
      if (wire()->config->debug) throw new WireException("Tool not found: $file");
      else return;
    }
    $name = basename($file, '.php');
    $tool = new Tool($name, $file);
    $this->tools->add($tool);
  }

  public function loadTools($dir): void
  {
    $dir = rtrim(Paths::normalizeSeparators($dir), '/');
    foreach (glob("$dir/*.php") as $file) $this->loadTool($file);
  }

  public function renderItems(): string
  {
    $html = '';
    foreach ($this->tools as $tool) $html .= $tool->render();
    return $html;
  }

  /**
   * Set order of tools
   * Usage: $toolbar->setOrder('pagetree,edit,sticky');
   */
  public function setOrder(string $items): void
  {
    // split items by , and trim whitespace
    $items = explode(',', $items);
    $items = array_map('trim', $items);
    $tools = new ToolsArray();
    foreach ($items as $item) {
      $t = $this->tools->get("name=$item");
      if ($t instanceof Tool) $tools->add($t);
      $this->tools->remove("name=$item");
    }
    foreach ($this->tools as $t) $tools->add($t);
    $this->tools = $tools;
  }

  /**
   * Initial setup of all tools
   */
  public function setupTools(): void
  {
    $this->tools = new ToolsArray();
    $this->loadTools(wire()->config->paths(rockfrontend()) . 'toolbar');
    $dirs = glob(wire()->config->paths->siteModules . '*/RockFrontendToolbar/');
    foreach ($dirs as $dir) $this->loadTools($dir);
    $this->loadTools(wire()->config->paths->templates . 'RockFrontendToolbar/');
    $this->tools->sort('name');
  }
}
