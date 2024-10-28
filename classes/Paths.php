<?php

namespace RockFrontend;

use ProcessWire\Paths as ProcessWirePaths;
use ProcessWire\Wire;
use ProcessWire\WireException;

use function ProcessWire\wire;

/**
 * Tests:
 * bd(rockfrontend()->paths()->toPath('/site/templates/'));
 * bd(rockfrontend()->paths()->toRelative('/site/templates/'));
 * bd(rockfrontend()->paths()->toUrl('/site/templates/'));
 * bd(rockfrontend()->paths()->versionUrl('/site/ready.php'));
 *
 * @package RockFrontend
 */
class Paths extends Wire
{

  /**
   * Return an url ready to be used in the browser to load assets
   *
   * NOTE: This will contain the subfolder prefix if PW is installed in a subfolder!
   *
   * Examples in regular PW installation:
   * toUrl('site/templates')   // /site/templates
   * toUrl('site/templates/')  // /site/templates
   * toUrl('/site/templates/') // /site/templates
   *
   * Examples in subfolder installation:
   * toUrl('site/templates')   // /subfolder/site/templates
   * toUrl('site/templates/')  // /subfolder/site/templates
   * toUrl('/site/templates/') // /subfolder/site/templates
   *
   * @param string $path
   * @return string
   */
  public static function toUrl(string $path): string
  {
    return rtrim(wire()->config->urls->root, '/') . self::toRelative($path);
  }

  /**
   * Return a path that starts with the pw root
   *
   * Returns the path without a trailing slash (also works for files)!
   * Does not check if the file or directory exists!
   *
   * Examples:
   *
   * toPath('site/templates')   // /var/www/html/site/templates
   * toPath('site/templates/')  // /var/www/html/site/templates
   * toPath('/site/templates/') // /var/www/html/site/templates
   * toPath('/site/ready.php')  // /var/www/html/site/ready.php
   *
   * Subfolder Installations:
   * toPath('site/templates')   // /var/www/html/subfolder/site/templates
   * toPath('site/templates/')  // /var/www/html/subfolder/site/templates
   * toPath('/site/templates/') // /var/www/html/subfolder/site/templates
   * toPath('/site/ready.php')  // /var/www/html/subfolder/site/ready.php
   *
   * @param string $url
   * @return string
   */
  public static function toPath(string $url): string
  {
    $url = ProcessWirePaths::normalizeSeparators(trim($url));

    // if it is a directory we add a slash
    // so that we can later compare with root path
    if (is_dir($url)) $url = rtrim($url, '/') . '/';

    $root = wire()->config->paths->root;
    if (!$url) return rtrim($root, '/');

    // already a path? return it
    if (str_starts_with($url, $root)) return rtrim($url, '/');

    // check for site folder
    if (str_starts_with($url, '/site/')) return $root . trim($url, '/');
    if (str_starts_with($url, 'site/')) return $root . trim($url, '/');
    if ($url === 'site') return $root . $url;

    // check for wire folder
    if (str_starts_with($url, '/wire/')) return $root . trim($url, '/');
    if (str_starts_with($url, 'wire/')) return $root . trim($url, '/');
    if ($url === 'wire') return $root . $url;

    // QUESTION: Do we have to support other folders as well?

    throw new WireException("Invalid Path $url");
  }

  /**
   * Return path relative to the pw root
   *
   * NOTE: This will NOT contain the subfolder, if PW is installed in a subfolder.
   *
   * Examples:
   *
   * toRelative('site/templates')   // /site/templates
   * toRelative('site/templates/')  // /site/templates
   * toRelative('/site/templates/') // /site/templates
   * toRelative('/site/ready.php')  // /site/ready.php
   *
   * Subfolder Installations (equal to above):
   *
   * toRelative('site/templates')   // /site/templates
   * toRelative('site/templates/')  // /site/templates
   * toRelative('/site/templates/') // /site/templates
   * toRelative('/site/ready.php')  // /site/ready.php
   *
   * @param string $path
   * @return string
   */
  public static function toRelative(string $path): string
  {
    $root = wire()->config->paths->root;
    return "/" . str_replace($root, '', self::toPath($path));
  }

  /**
   * Return a url with cache busting string
   * ready to be used in <link> or <script> tags
   *
   * Compared to $config->versionUrl() this will work also
   * on subfolder installations.
   *
   * Example:
   * versionUrl('/wire/foo.min.css') // /wire/foo.min.css?sm2nwu
   *
   * Subfolder installation:
   * versionUrl('/wire/foo.min.css') // /subfolder/wire/foo.min.css?sm2nwu
   * Note: $config->versionUrl() would return /wire/foo.min.css?sm2nwu here
   *
   * @param string $path
   * @return string
   * @throws WireException
   */
  public static function versionUrl(string $path): string
  {
    return wire()->config->versionUrl(self::toUrl($path));
  }
}
