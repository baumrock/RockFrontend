<?php namespace RockFrontend;

use ProcessWire\Config;
use ProcessWire\ProcessWire;

class LiveReload {

  private $config;
  private $dir;
  private $exclude;
  private $rootPath;

  public function __construct($exclude = null, $dir = 'site') {
    $this->rootPath = realpath(__DIR__.'/../../../');
    $this->config = $this->loadConfig();

    if(!$this->secretMatches()) header("Location: /");

    // merge default excluded files with user input
    $this->exclude = array_merge([
      'site/assets/backups/*',
      'site/assets/cache/*',
      'site/assets/files/*',
      'site/assets/logs/*',
      'site/assets/sessions/*',
      'site/assets/RockCrawler/*.txt',
      '.git/*',
    ], $exclude ?: []);

    $this->dir = $dir;
  }

  /**
   * Execute the command to find changed files
   * @return void
   */
  public function getChanges($since) {
    if(strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
      return $this->getChangesWindows($since);
    }
    $exclude = '';
    foreach($this->exclude as $dir) $exclude .= " -not -path '$dir'";
    exec("cd {$this->rootPath} && find {$this->dir} -type f $exclude -newermt '$since'", $out);
    return json_encode($out);
  }

  /**
   * For systems without UNIX "find"
   */
  public function getChangesWindows($since) {
    $out = array();

    // Get Path
    $iter = new \RecursiveIteratorIterator(
      new \RecursiveDirectoryIterator(
        $this->rootPath."\\".$this->dir,
        \RecursiveDirectoryIterator::SKIP_DOTS
      ),
      \RecursiveIteratorIterator::SELF_FIRST,
      \RecursiveIteratorIterator::CATCH_GET_CHILD
    );

    $paths = array($this->rootPath."\\".$this->dir);
    $maxdepth = 0;
    // Max path depth
    foreach($iter as $path => $dir) {
      if($dir->isDir()) {
        if($maxdepth < substr_count($path, '\\')) {
          $maxdepth = substr_count($path, '\\');
        }
        $paths[] = $path;
      }
    }

    $pattern = "";
    for($i = 1; $i <= $maxdepth;$i++) $pattern .= ",".str_repeat("*/", $i);
    $pattern = "{".$pattern."}*.*";

    $items = glob($this->dir.$pattern, GLOB_BRACE);
    array_multisort(
      array_map('filemtime', $items),
      SORT_NUMERIC,
      SORT_DESC,
      $items
    );

    foreach($items as $item) {
      if(filemtime($item) > strtotime($since)) {
        $foundExcludeDir = 0;
        foreach(str_replace("\\","/",str_replace("/*","",$this->exclude)) as $a) {
          if(stripos($item,$a) !== false) $foundExcludeDir = 1;
        }
        if(!$foundExcludeDir) array_push($out, $item);
      }
    }

    return json_encode($out);
  }

  /**
   * Load pw config
   * @return Config
   */
  public function loadConfig() {
    if(!class_exists("ProcessWire\\ProcessWire", false)) {
      require_once $this->rootPath."/wire/core/ProcessWire.php";
    }
    $config = ProcessWire::buildConfig($this->rootPath);
    return $config;
  }

  /**
   * Check if provided secret matches the one from the cache
   * @return bool
   */
  public function secretMatches() {
    $input = (string)$_GET['secret'];
    $cachefile = $this->config->paths->cache."rockfrontend_livereload.txt";
    $secret = file_get_contents($cachefile);
    return $input == $secret;
  }

  /**
   * Send SSE message to client
   * @return void
   */
  public function sse($msg) {
    echo "data: $msg\n\n";
    echo str_pad('',8186)."\n";
    flush();
  }

  /**
   * Watch the system for changed files
   */
  public function watch() {
    if(!$this->config->livereload) {
      header("Location: /");
      die('no access');
    }
    header("Cache-Control: no-cache");
    header("Content-Type: text/event-stream");
    $start = date("Y-m-d H:i:s");
    while(true) {
      $this->sse($this->getChanges($start));
      while(ob_get_level() > 0) ob_end_flush();
      if(connection_aborted()) break;
      sleep((int)$this->config->livereload ?: 1);
    }
  }

}
$reload = new LiveReload(isset($exclude) ? $exclude : null);
if(!isset($nowatch)) $reload->watch();
