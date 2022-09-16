<?php

namespace RockFrontend;

use ProcessWire\Debug;
use ProcessWire\Paths;
use ProcessWire\RockFrontend;
use ProcessWire\Wire;
use ProcessWire\WireData;
use RecursiveCallbackFilterIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class LiveReload extends Wire
{

  public function __construct()
  {
    $config = $this->wire->config->livereload;
    if (is_bool($config)) $config = (int)$config; // convert true/false to int
    if (is_float($config)) $config = ['interval' => $config]; // set interval

    // prepare config object
    $this->config = $this->wire(new WireData());
    $this->config->setArray([
      'interval' => 1,
      'includeDefaults' => [
        $this->wire->config->paths->templates,
        $this->wire->config->paths->classes,
        $this->wire->config->paths->siteModules,
        $this->wire->config->paths->assets,
      ],
      // user defined includes
      'include' => [],
      'excludeDefaults' => [
        $this->wire->config->paths->templates . 'uikit-*',
        $this->wire->config->paths->assets . 'cache',
        '.*/vendor',
        '.*/\.git',
        '.*/\.github',
        '.*/\.vscode',
        '.*site/modules/TracyDebugger/tracy-.*',
      ],
      // user defined exclude regexes
      'exclude' => [],
    ]);
    $this->config->setArray($config);
  }

  /**
   * Find modified file after timestamp
   * This will return on first match
   * @return void
   */
  public function findModifiedFile($since)
  {
    // db(date("H:i:s", $since));
    // $timer = Debug::startTimer();

    // recurse files and return if a file has changed
    $filter = function ($file, $key, $iterator) {
      $file = Paths::normalizeSeparators($file);
      // echo "<br><br>Checking $file... ";
      foreach ($this->getExcludes() as $regex) {
        $isExcluded = preg_match($regex, $file);
        // echo "<br> $isExcluded $regex";
        if ($isExcluded) return false;
      }
      // echo "<br>$file";

      return true;
    };

    // loop over all included folders
    foreach ($this->getIncludes() as $path) {
      if (!is_dir($path)) continue;
      $iterator = new RecursiveDirectoryIterator(
        $path,
        RecursiveDirectoryIterator::SKIP_DOTS
      );
      $files = new RecursiveIteratorIterator(
        new RecursiveCallbackFilterIterator($iterator, $filter)
      );
      foreach ($files as $file) {
        if ($this->hasChanged($file, $since)) {
          // bd(Debug::stopTimer($timer, "ms"));
          return $file;
        }
      }
    }
    // bd(Debug::stopTimer($timer, "ms"));
  }

  /**
   * Get all regexes for excluded folders or files
   */
  public function getExcludes(): array
  {
    $arr = array_unique(array_merge(
      $this->config->excludeDefaults,
      $this->config->exclude
    ));
    $arr = array_map(function ($item) {
      return "~$item~";
    }, $arr);
    return $arr;
  }

  /**
   * Return array of merged include folders
   */
  public function getIncludes(): array
  {
    return array_unique(array_merge(
      $this->config->includeDefaults,
      $this->config->include
    ));
  }

  /**
   * Has the file changed since given timestamp?
   */
  public function hasChanged($file, $since): bool
  {
    $m = filemtime($file);
    return $m > $since;
  }

  /**
   * Send SSE message to client
   * @return void
   */
  public function sse($msg)
  {
    echo "data: $msg\n\n";
    echo str_pad('', 8186) . "\n";
    flush();
  }

  public function validSecret()
  {
    $secret = (string)$_GET[RockFrontend::getParam];
    $cache = $this->wire->cache->get(RockFrontend::livereloadCacheName) ?: [];
    foreach ($cache as $k => $v) {
      if ($secret !== $v) continue;
      unset($cache[$k]);
      $this->wire->cache->save(RockFrontend::livereloadCacheName, $cache);
      return true;
    }
    return false;
  }

  /**
   * Watch the system for changed files
   */
  public function watch()
  {
    header("Cache-Control: no-cache");
    header("Content-Type: text/event-stream");
    $start = time();
    while (true) {
      $this->sse($file = $this->findModifiedFile($start));
      if ($file) {
        ob_end_flush();
        return $this->wire->log->save('livereload', $file);
      }
      while (ob_get_level() > 0) ob_end_flush();
      if (connection_aborted()) break;
      $sleepSeconds = (int)$this->wire->config->livereload ?: 1;
      sleep($sleepSeconds);
    }
  }

  public function __debuginfo()
  {
    return array_merge($this->config->getArray(), [
      'getIncludes()' => $this->getIncludes(),
    ]);
  }
}
