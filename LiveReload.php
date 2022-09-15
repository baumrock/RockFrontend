<?php namespace RockFrontend;

use ProcessWire\Debug;
use ProcessWire\RockFrontend;
use ProcessWire\Wire;

class LiveReload extends Wire {

  /**
   * Find modified file after timestamp
   * This will return on first match
   * @return void
   */
  public function findModifiedFile($since) {
    // db(date("H:i:s", $since));
    // $timer = Debug::startTimer();
    $folders = [
      $this->wire->config->paths->templates,
      $this->wire->config->paths->siteModules,
      $this->wire->config->paths->assets,
    ];
    $options = [
      'exclude' => [
        'backups',
        'cache',
        'logs',
        'sessions',
      ]
    ];
    // loop files and return on first match
    foreach($folders as $folder) {
      foreach($this->wire->files->find($folder, $options) as $file) {
        $m = filemtime($file);
        if($m <= $since) continue;
        return $file;
      }
    }
    // db(Debug::stopTimer($timer));
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

  public function validSecret() {
    $secret = (string)$_GET[RockFrontend::getParam];
    $cache = $this->wire->cache->get(RockFrontend::livereloadCacheName) ?: [];
    foreach($cache as $k=>$v) {
      if($secret !== $v) continue;
      unset($cache[$k]);
      $this->wire->cache->save(RockFrontend::livereloadCacheName, $cache);
      return true;
    }
    return false;
  }

  /**
   * Watch the system for changed files
   */
  public function watch() {
    header("Cache-Control: no-cache");
    header("Content-Type: text/event-stream");
    $start = time();
    while(true) {
      $this->sse($this->findModifiedFile($start));
      while(ob_get_level() > 0) ob_end_flush();
      if(connection_aborted()) break;
      $sleepSeconds = (int)$this->wire->config->livereload ?: 1;
      sleep($sleepSeconds);
    }
  }

}
