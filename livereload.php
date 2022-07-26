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
      'site/assets/cache/*',
      'site/assets/logs/*',

      // ignore files to make sure we dont reload the backend after file upload
      'site/assets/files/*',
    ], $exclude ?: []);

    $this->dir = $dir;
  }

  /**
   * Execute the command to find changed files
   * @return void
   */
  public function getChanges($since) {
    $exclude = '';
    foreach($this->exclude as $dir) $exclude .= " -not -path '$dir'";
    exec("cd {$this->rootPath} && find {$this->dir} -type f $exclude -newermt '$since'", $out);
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
