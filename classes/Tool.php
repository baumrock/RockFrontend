<?php

namespace RockFrontend;

use ProcessWire\WireData;

use function ProcessWire\wire;

class Tool extends WireData
{
  private string $file;

  public function __construct(string $name, string $file)
  {
    $this->name = $name;
    $this->file = $file;
  }

  public function render()
  {
    return wire()->files->render($this->file);
  }
}
