<?php

use Latte\Extension;

final class ExposeFunctionsExtension extends Extension
{
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
