<?php

namespace ProcessWire;

/**
 * @author Bernhard Baumrock, 28.03.2023
 * @license Licensed under MIT
 * @link https://www.baumrock.com
 */
class TextformatterRockFrontend extends Textformatter implements ConfigurableModule
{

  public $checkboxclass = 'uk-checkbox';

  public static function getModuleInfo()
  {
    return [
      'title' => 'RockFrontend Textformatter',
      'version' => '1.0.0',
      'summary' => 'Textformatter to manage consent',
    ];
  }

  public function format(&$str)
  {
    // replace consent checkboxes
    if (strpos($str, "[rf-consent=") !== false) {
      $str = preg_replace_callback("/\[rf-consent=(.*?)\](.*?)\[\/rf-consent\]/", function ($matches) {
        $name = $matches[1];
        $str = $matches[2];
        return "<label>
          <input type='checkbox' class='rf-consent-checkbox {$this->checkboxclass}' data-name='$name'>
          $str
          </label>";
      }, $str);
    }

    // replace [[year]] with current year
    $str = str_replace("[rf-year]", date("Y"), $str);
  }

  /**
   * Config inputfields
   * @param InputfieldWrapper $inputfields
   */
  public function getModuleConfigInputfields($inputfields)
  {
    $inputfields->add([
      'type' => 'text',
      'name' => 'checkboxclass',
      'label' => 'Checkbox Class',
      'value' => $this->checkboxclass,
      'notes' => 'Here you can adjust the class that is applied to consent checkboxes. Default is "uk-checkbox".',
    ]);
  }
}
