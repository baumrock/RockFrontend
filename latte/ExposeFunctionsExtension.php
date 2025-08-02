<?php

use Latte\Extension;
use Latte\Runtime\Template;

use function ProcessWire\rockfrontend;
use function ProcessWire\wire;

final class ExposeFunctionsExtension extends Extension
{

  /**
   * Initializes before template is rendered.
   */
  public function beforeRender(Template $template): void
  {
    // expose all ProcessWire API variables to latte
    $apiVars = wire('all')->getArray();

    // Use reflection to access the protected $params property
    $reflection = new \ReflectionClass($template);
    $paramsProperty = $reflection->getProperty('params');
    $paramsProperty->setAccessible(true);

    // Get current parameters and merge with API variables
    $currentParams = $paramsProperty->getValue($template);
    $newParams = array_merge(
      $apiVars, // lowest priority
      rockfrontend()->definedVars ?? [],
      $currentParams, // highest priority
    );

    // Set the merged parameters back
    $paramsProperty->setValue($template, $newParams);
  }

  /**
   * This method will create a list of all defined PHP functions
   * It will return an array where the key will be the function name
   * (without the namespace) and the value will be a callback to that
   * function.
   */
  public function getFunctions(): array
  {
    $functions = get_defined_functions()['user'];
    $result = [];
    foreach ($functions as $function) {
      if (!str_starts_with($function, 'processwire\\')) continue;
      $name = str_replace('processwire\\', '', $function);
      $result[$name] = $function;
    }
    return $result;
  }
}
