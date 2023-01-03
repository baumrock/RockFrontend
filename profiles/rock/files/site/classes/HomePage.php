<?php

namespace ProcessWire;

use RockMigrations\MagicPage;

class HomePage extends Page
{
  use MagicPage;

  const tpl = 'home';

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

  public function migrate()
  {
    $rm = $this->rockmigrations();
    $rm->installModule("RockPageBuilder");
    $rm->migrate([
      'fields' => [],
      'templates' => [
        self::tpl => [
          'fields' => [
            'title',
            RockPageBuilder::field_blocks,
            RockPageBuilder::field_widgets,

            RockFrontend::field_favicon,
            RockFrontend::field_ogimage,
            RockFrontend::field_footerlinks,
          ],
        ],
      ],
    ]);
  }
}
