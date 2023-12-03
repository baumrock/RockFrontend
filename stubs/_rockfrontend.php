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

echo $rockfrontend->render($render);
