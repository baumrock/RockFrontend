<?php

namespace RockFrontend;

/**
 * @mixin \ProcessWire\Page
 */
trait FieldMethod
{

  /**
   * Get field via short name (useful for prefixed fields)
   *
   * For convenient use with Latte this will return a HTML object if possible.
   *
   * Options for type:
   * e (default) = edit
   * u = unformatted
   * f = formatted
   *
   * Example:
   * field = my_prefix_myfield
   * echo $page->field('myfield', 'u');
   */
  public function field(string $shortname, string $type = 'e')
  {
    $type = strtolower($type);
    $fieldname = $this->getRealFieldname($shortname);
    if (!$fieldname) return false;

    // the noEdit flag prevents rendering editable fields
    if ($type === 'e' && $this->noEdit) $type = 'f';

    // edit field
    if ($type === 'e') return $this->html(parent::edit($fieldname));

    // formatted
    if ($type === 'f') return $this->html(parent::get($fieldname));

    // unformatted
    if ($type === 'u') return parent::getUnformatted($fieldname);
  }

  /**
   * Given the short fieldname "foo" find the real fieldname "my_prefixed_foo"
   */
  private function getRealFieldname(string $shortname): string|false
  {
    $fieldnames = $this->fields->each('name');

    // if the fieldname exists we return the unmodified name
    if (in_array($shortname, $fieldnames)) return $shortname;

    // otherwise we check for the final _xxx part of the name
    foreach ($this->fields as $field) {
      $suffix = strrchr($field->name, '_');
      if ($suffix === "_$shortname") return $field->name;
    }

    return false;
  }
}
