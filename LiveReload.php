<?php

namespace RockFrontend;

use ProcessWire\Paths;
use ProcessWire\RockFrontend;
use ProcessWire\Wire;
use ProcessWire\WireData;
use RecursiveCallbackFilterIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

use function ProcessWire\wire;

class LiveReload extends Wire
{
  private $config;

  public function __construct()
  {
    $config = $this->wire->config->livereload;
    if (is_bool($config)) $config = (int)$config; // convert true/false to int
    if (is_int($config)) $config = ['interval' => $config]; // set interval
    if (is_float($config)) $config = ['interval' => $config]; // set interval

    // prepare config object
    $this->config = $this->wire(new WireData());
    $this->config->setArray([
      'interval' => 1,
      'includeDefaults' => [
        $this->wire->config->paths->site,
        $this->wire->config->paths->root . "RockShell/docs",
        $this->wire->config->paths->root . "RockShell/App",
      ],
      // user defined includes
      'include' => [],
      'excludeDefaults' => [
        '.*/vendor',
        '.*/\.git',
        '.*/\.github',
        '.*/\.vscode',

        '.*/site/assets/backups',
        '.*/site/assets/cache',
        '.*/site/assets/files',
        '.*/site/assets/logs',
        '.*/site/assets/sessions',
        '.*/site/assets/ProCache-*',
        '.*/site/assets/pwpc',
        '.*/site/assets/RockFrontend/.*.css',
        '.*/site/assets/RockPdfDumps/*',
        '.*/site/assets/RockPdf/*',

        '.*/site/modules/TracyDebugger/tracy-.*',
        '.*/site/modules/RockBlocks/blocks/.*.css',

        '.*/site/templates/bundle/*',
        '.*/site/templates/uikit',
        '.*/site/templates/uikit-*',
      ],
      // user defined exclude regexes
      'exclude' => [],
    ]);
    $this->config->setArray($config ?: []);
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

  /**
   * Watch the system for changed files
   */
  public function watch($pid)
  {
    // we dont want warnings in the stream
    // for debugging you can uncomment this line
    error_reporting(E_ALL & ~E_WARNING);

    header("Cache-Control: no-cache");
    header("Content-Type: text/event-stream");
    $debug = $this->wire->config->debug;
    $this->wire->log->prune('livereload', 1);
    $opt = [
      'showURL' => false,
      'showUser' => false,
    ];

    // expose page variable for included actionFile
    // EDIT dont load the page as this can cause problems
    // if the page uses traits that are not loaded on Session::init
    // $page = wire()->pages->get($pid);

    // start loop
    $start = time();
    $executed = false;
    while (true) {
      $file = $this->findModifiedFile($start);

      // file changed
      if (!$executed && $file && $debug) {
        $this->wire->log->save('livereload', "File changed: $file", $opt);
        $actionFile = wire()->config->paths->site . 'livereload.php';
        if (is_file($actionFile)) {
          $this->wire->log->save('livereload', "Loading actionfile $actionFile");
          include $actionFile;
        } else {
          $this->wire->log->save('livereload', "No actionfile $actionFile");
        }
        $executed = true;
      }

      // send trigger to frontend
      $this->sse($file);

      // add note to log
      if ($file) ob_end_flush();
      while (ob_get_level() > 0) ob_end_flush();

      // stop loop when connection is aborted
      if (connection_aborted()) break;

      // sleep until next try
      $sleepSeconds = (float)$this->wire->config->livereload ?: 1.0;
      usleep($sleepSeconds * 1000000);
    }
  }

  public function __debuginfo()
  {
    return $this->config->getArray();
  }
}
