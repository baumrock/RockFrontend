<?php

namespace ProcessWire;

class RemData extends WireData
{
  public function __toString(): string
  {
    return $this->val . $this->unit;
  }
}
