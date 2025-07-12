<?php

namespace RockFrontend;

use ProcessWire\Wire;

class AJAX extends Wire
{
  // [Informational 1xx]
  const HTTP100_CONTINUE                        = 'ROCKFRONTEND-HTTP100';
  const HTTP101_SWITCHING_PROTOCOLS             = 'ROCKFRONTEND-HTTP101';

  // [Successful 2xx]
  const HTTP200_OK                              = 'ROCKFRONTEND-HTTP200';
  const HTTP201_CREATED                         = 'ROCKFRONTEND-HTTP201';
  const HTTP202_ACCEPTED                        = 'ROCKFRONTEND-HTTP202';
  const HTTP203_NONAUTHORITATIVE_INFORMATION    = 'ROCKFRONTEND-HTTP203';
  const HTTP204_NO_CONTENT                      = 'ROCKFRONTEND-HTTP204';
  const HTTP205_RESET_CONTENT                   = 'ROCKFRONTEND-HTTP205';
  const HTTP206_PARTIAL_CONTENT                 = 'ROCKFRONTEND-HTTP206';

  // [Redirection 3xx]
  const HTTP300_MULTIPLE_CHOICES                = 'ROCKFRONTEND-HTTP300';
  const HTTP301_MOVED_PERMANENTLY               = 'ROCKFRONTEND-HTTP301';
  const HTTP302_FOUND                           = 'ROCKFRONTEND-HTTP302';
  const HTTP303_SEE_OTHER                       = 'ROCKFRONTEND-HTTP303';
  const HTTP304_NOT_MODIFIED                    = 'ROCKFRONTEND-HTTP304';
  const HTTP305_USE_PROXY                       = 'ROCKFRONTEND-HTTP305';
  const HTTP306_UNUSED                          = 'ROCKFRONTEND-HTTP306';
  const HTTP307_TEMPORARY_REDIRECT              = 'ROCKFRONTEND-HTTP307';

  // [Client Error 4xx]
  const HTTP400_BAD_REQUEST                     = 'ROCKFRONTEND-HTTP400';
  const HTTP401_UNAUTHORIZED                    = 'ROCKFRONTEND-HTTP401';
  const HTTP402_PAYMENT_REQUIRED                = 'ROCKFRONTEND-HTTP402';
  const HTTP403_FORBIDDEN                       = 'ROCKFRONTEND-HTTP403';
  const HTTP404_NOT_FOUND                       = 'ROCKFRONTEND-HTTP404';
  const HTTP405_METHOD_NOT_ALLOWED              = 'ROCKFRONTEND-HTTP405';
  const HTTP406_NOT_ACCEPTABLE                  = 'ROCKFRONTEND-HTTP406';
  const HTTP407_PROXY_AUTHENTICATION_REQUIRED   = 'ROCKFRONTEND-HTTP407';
  const HTTP408_REQUEST_TIMEOUT                 = 'ROCKFRONTEND-HTTP408';
  const HTTP409_CONFLICT                        = 'ROCKFRONTEND-HTTP409';
  const HTTP410_GONE                            = 'ROCKFRONTEND-HTTP410';
  const HTTP411_LENGTH_REQUIRED                 = 'ROCKFRONTEND-HTTP411';
  const HTTP412_PRECONDITION_FAILED             = 'ROCKFRONTEND-HTTP412';
  const HTTP413_REQUEST_ENTITY_TOO_LARGE        = 'ROCKFRONTEND-HTTP413';
  const HTTP414_REQUEST_URI_TOO_LONG            = 'ROCKFRONTEND-HTTP414';
  const HTTP415_UNSUPPORTED_MEDIA_TYPE          = 'ROCKFRONTEND-HTTP415';
  const HTTP416_REQUESTED_RANGE_NOT_SATISFIABLE = 'ROCKFRONTEND-HTTP416';
  const HTTP417_EXPECTATION_FAILED              = 'ROCKFRONTEND-HTTP417';

  // [Server Error 5xx]
  const HTTP500_INTERNAL_SERVER_ERROR           = 'ROCKFRONTEND-HTTP500';
  const HTTP501_NOT_IMPLEMENTED                 = 'ROCKFRONTEND-HTTP501';
  const HTTP502_BAD_GATEWAY                     = 'ROCKFRONTEND-HTTP502';
  const HTTP503_SERVICE_UNAVAILABLE             = 'ROCKFRONTEND-HTTP503';
  const HTTP504_GATEWAY_TIMEOUT                 = 'ROCKFRONTEND-HTTP504';
  const HTTP505_VERSION_NOT_SUPPORTED           = 'ROCKFRONTEND-HTTP505';

  private static $messages = array(
    // [Informational 1xx]
    'ROCKFRONTEND-HTTP100' => '100 Continue',
    'ROCKFRONTEND-HTTP101' => '101 Switching Protocols',

    // [Successful 2xx]
    'ROCKFRONTEND-HTTP200' => '200 OK',
    'ROCKFRONTEND-HTTP201' => '201 Created',
    'ROCKFRONTEND-HTTP202' => '202 Accepted',
    'ROCKFRONTEND-HTTP203' => '203 Non-Authoritative Information',
    'ROCKFRONTEND-HTTP204' => '204 No Content',
    'ROCKFRONTEND-HTTP205' => '205 Reset Content',
    'ROCKFRONTEND-HTTP206' => '206 Partial Content',

    // [Redirection 3xx]
    'ROCKFRONTEND-HTTP300' => '300 Multiple Choices',
    'ROCKFRONTEND-HTTP301' => '301 Moved Permanently',
    'ROCKFRONTEND-HTTP302' => '302 Found',
    'ROCKFRONTEND-HTTP303' => '303 See Other',
    'ROCKFRONTEND-HTTP304' => '304 Not Modified',
    'ROCKFRONTEND-HTTP305' => '305 Use Proxy',
    'ROCKFRONTEND-HTTP306' => '306 (Unused)',
    'ROCKFRONTEND-HTTP307' => '307 Temporary Redirect',

    // [Client Error 4xx]
    'ROCKFRONTEND-HTTP400' => '400 Bad Request',
    'ROCKFRONTEND-HTTP401' => '401 Unauthorized',
    'ROCKFRONTEND-HTTP402' => '402 Payment Required',
    'ROCKFRONTEND-HTTP403' => '403 Forbidden',
    'ROCKFRONTEND-HTTP404' => '404 Not Found',
    'ROCKFRONTEND-HTTP405' => '405 Method Not Allowed',
    'ROCKFRONTEND-HTTP406' => '406 Not Acceptable',
    'ROCKFRONTEND-HTTP407' => '407 Proxy Authentication Required',
    'ROCKFRONTEND-HTTP408' => '408 Request Timeout',
    'ROCKFRONTEND-HTTP409' => '409 Conflict',
    'ROCKFRONTEND-HTTP410' => '410 Gone',
    'ROCKFRONTEND-HTTP411' => '411 Length Required',
    'ROCKFRONTEND-HTTP412' => '412 Precondition Failed',
    'ROCKFRONTEND-HTTP413' => '413 Request Entity Too Large',
    'ROCKFRONTEND-HTTP414' => '414 Request-URI Too Long',
    'ROCKFRONTEND-HTTP415' => '415 Unsupported Media Type',
    'ROCKFRONTEND-HTTP416' => '416 Requested Range Not Satisfiable',
    'ROCKFRONTEND-HTTP417' => '417 Expectation Failed',

    // [Server Error 5xx]
    'ROCKFRONTEND-HTTP500' => '500 Internal Server Error',
    'ROCKFRONTEND-HTTP501' => '501 Not Implemented',
    'ROCKFRONTEND-HTTP502' => '502 Bad Gateway',
    'ROCKFRONTEND-HTTP503' => '503 Service Unavailable',
    'ROCKFRONTEND-HTTP504' => '504 Gateway Timeout',
    'ROCKFRONTEND-HTTP505' => '505 HTTP Version Not Supported'
  );

  public static function getCode(string $code): int
  {
    return substr($code, strlen('ROCKFRONTEND-HTTP'));
  }

  public static function getMessageForCode($code)
  {
    return self::$messages[$code];
  }

  public static function isError($code)
  {
    return is_numeric($code) && $code >= self::HTTP400_BAD_REQUEST;
  }

  public static function isStatusCode($code)
  {
    return array_key_exists($code, self::$messages);
  }

  public static function canHaveBody($code)
  {
    return
      // True if not in 100s
      ($code < self::HTTP100_CONTINUE || $code >= self::HTTP200_OK)
      && // and not 204 NO CONTENT
      $code != self::HTTP204_NO_CONTENT
      && // and not 304 NOT MODIFIED
      $code != self::HTTP304_NOT_MODIFIED;
  }
}
