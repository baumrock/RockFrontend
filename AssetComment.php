<?php namespace RockFrontend;
class AssetComment extends Asset {

  public function __construct($comment) {
    parent::__construct("");
    $this->comment = $comment;
  }

}
