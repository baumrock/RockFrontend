<?php namespace ProcessWire;
/**
 * This file is automatically added to the prependFile array.
 * If you are using /site/templates/_init.php this file ensures that all defined
 * variables and functions are available from every file that is rendered via
 * RockFrontend.
 */
if($rockfrontend->autoprepended) return;
$vars = array_merge(get_defined_vars(), (array)$this->wire('all'));
foreach($vars as $k=>$v) $this->wire($k, $v);
$rockfrontend->autoprepended = true;
