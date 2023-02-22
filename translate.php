<?php // no namespace!

/**
 * Add global translation functions to LATTE files
 * In LATTE files use this syntax to add translations:
 * {=__('your string')}
 * {=_x('your string', 'context)}
 * {=_n('found one item', 'fount multiple items', $num)}
 */

use function ProcessWire\wire;

function __($str)
{
  $rf = wire()->modules->get('RockFrontend');
  return \ProcessWire\__($str, $rf->textdomain());
}
function _x($str, $context)
{
  $rf = wire()->modules->get('RockFrontend');
  return \ProcessWire\_x($str, $context, $rf->textdomain());
}
function _n($textsingular, $textplural, $count)
{
  $rf = wire()->modules->get('RockFrontend');
  return \ProcessWire\_n($textsingular, $textplural, $count, $rf->textdomain());
}
