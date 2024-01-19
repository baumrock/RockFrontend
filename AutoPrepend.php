<?php

namespace ProcessWire;

/**
 * This file is automatically added to the prependFile array.
 * If you are using /site/templates/_init.php this file ensures that all defined
 * variables and functions are available from every file that is rendered via
 * RockFrontend.
 */

// first we check if the currently rendered file matches the $page's templateFile
// this is to prevent issues when anything uses $files->render() before the
// page render is triggered (for example using setPageNameReplacements in
// Site::migrate())
$templateFile = $this->wire('page')->template->filename;
if ($templateFile !== $rockfrontend->autoPrependFile) return;

// If page is not set or not a page we exit early.
// This happens if anything calls wireRenderFile() on init or such.
// If the page is not available we also don't want to merge any defined variables.
if (!isset($page)) return;
if (!$page instanceof Page) return;

// we make sure to add variables only once
if ($rockfrontend->autoprepended) return;

// this fixes the issue that $page->any_options_field->title
// shows the page title instead of the options' value title
// see https://processwire.com/talk/topic/29225-show-this-field-only-if-doesnt-seem-to-work/?do=findComment&comment=237096
$vars = get_defined_vars();
foreach ($page->getFields() as $field) {
  if (!array_key_exists($field->name, $vars)) continue;
  unset($vars[$field->name]);
}

// merge arrays and make them available as API variable
$vars = array_merge($vars, (array)$this->wire('all'));
foreach ($vars as $k => $v) {
  $this->wire($k, $v);
}

$rockfrontend->autoprepended = true;
