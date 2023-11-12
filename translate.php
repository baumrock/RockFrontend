<?php // no namespace!

/**
 * Add global translation functions to LATTE files
 * In LATTE files use this syntax to add translations:
 * {=__('your string')}
 * {=_x('your string', 'context)}
 * {=_n('found one item', 'fount multiple items', $num)}
 */

use ProcessWire\RockFrontend;

function __($str)
{
  $backtrace = debug_backtrace(limit: 1);
  $textdomain = RockFrontend::textdomain($backtrace[0]["file"]);
  return \ProcessWire\__($str, $textdomain);
}

function _x($str, $context): bool|array|string|null
{
  $backtrace = debug_backtrace(limit: 1);
  $textdomain = RockFrontend::textdomain($backtrace[0]["file"]);
  return \ProcessWire\_x($str, $context, $textdomain);
}

function _n($textsingular, $textplural, $count): bool|array|string|null
{
  $backtrace = debug_backtrace(limit: 1);
  $textdomain = RockFrontend::textdomain($backtrace[0]["file"]);
  return \ProcessWire\_n($textsingular, $textplural, $count, $textdomain);
}
