<?php // no namespace!

/**
 * Add global translation functions to LATTE files
 * In LATTE files use this syntax to add translations:
 * {=__('your string')}
 * {=_x('your string', 'context)}
 * {=_n('found one item', 'fount multiple items', $num)}
 */

use function ProcessWire\wire;

function getTextdomain($file){
//  bd($file);
  if (!str_contains($file, '.latte')) return false;
  $content = file_get_contents($file);
  // get the string between single quotes from  public const Source = 'something';
  $pattern = "/public const Source = '(.*?)';/";
  preg_match($pattern, $content, $matches);
  return $matches[1];
}

function __($str)
{
  $backtrace = debug_backtrace();
  $file = $backtrace[0]["file"];
  $textdomain = getTextdomain($file);
//  bd($textdomain, 'textdomain');
  return \ProcessWire\__($str, $textdomain);
}

function _x($str, $context): bool|array|string|null
{
  $backtrace = debug_backtrace();
  $file = $backtrace[0]["file"];
  $textdomain = getTextdomain($file);
//  bd($textdomain, 'textdomain');
  return \ProcessWire\_x($str, $context,$textdomain);
}
function _n($textsingular, $textplural, $count): bool|array|string|null
{
  $backtrace = debug_backtrace();
  $file = $backtrace[0]["file"];
  $textdomain = getTextdomain($file);
//  bd($textdomain, 'textdomain');
  return \ProcessWire\_n($textsingular, $textplural, $count, $textdomain);
}
