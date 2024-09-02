<?php

namespace RockFrontend;

use function ProcessWire\rockfrontend;

/**
 * @mixin \ProcessWire\Page
 */
trait FieldMethod
{

  public function field($shortname, $type = null)
  {
    return rockfrontend()->field($this, $shortname, $type);
  }
}
