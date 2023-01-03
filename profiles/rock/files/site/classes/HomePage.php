<?php

namespace ProcessWire;

use RockMigrations\MagicPage;

class HomePage extends Page
{
  use MagicPage;

  /** magic */

  public function editForm($form)
  {
    $rm = $this->rockmigrations();

    // global site seo wrapper
    // add and remove fields as you like
    $rm->wrapFields($form, [
      RockFrontend::field_favicon,
      RockFrontend::field_ogimage,
    ], [
      'label' => 'Global Site SEO Settings',
      'collapsed' => Inputfield::collapsedYes,
    ]);

    // global site settings
    // add and remove fields as you like
    $rm->wrapFields($form, [
      RockFrontend::field_footerlinks,
    ], [
      'label' => 'Global Site Settings',
      'collapsed' => Inputfield::collapsedYes,
    ]);
  }
}
