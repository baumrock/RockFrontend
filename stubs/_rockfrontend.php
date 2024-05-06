<?php

namespace ProcessWire;

/**
 * This file was created by RockFrontend
 * Do not change it to make sure that updating RockFrontend will not break
 * your site! Use _init.php to add custom modifications to your site.
 */

$file = $config->paths->templates . $page->template . ".latte";
if (is_file($file)) $render = $page->template . ".latte";
else $render = $config->paths->siteModules . "RockFrontend/default.latte";

// Render latte file and send all defined vars to the file.
// This is to make sure that variables can be set/overwritten from within
// template files like home.php or basic-page.php but are still available
// to latte files.
echo $rockfrontend->render($render, get_defined_vars());
