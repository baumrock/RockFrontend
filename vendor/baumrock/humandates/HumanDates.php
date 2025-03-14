<?php

class HumanDates
{
  /* default range patterns */
  const rangePatterns = [
    // 1. Jan. 2023 - 3. Feb. 2024
    'default'   => ['d. MMM y', ' – ', 'd. MMM y'],

    // 1. Jan. - 3. Feb. 2023
    'sameYear'  => ['d. MMM', ' – ', 'd. MMM y'],

    // 1. - 3. Jan. 2023
    'sameMonth' => ['d.', ' – ', 'd. MMM y'],

    // 1. Jan. 2023
    'sameDay'   => ['d. MMM y', '', ''],
  ];

  /** @var IntlDateFormatter */
  private $formatter;

  private $patterns = self::rangePatterns;

  function __construct(
    $locale = "en_GB",
    $format = "d. MMM y",
    $patterns = null,
  ) {
    $this->setLocale($locale);
    $this->setFormat($format);
    $this->setPatterns($patterns);
  }

  /**
   * Format a single date as human readable date string
   */
  function format($time, $format = null): string
  {
    if ($format) {
      $formatter = clone $this->formatter;
      $formatter->setPattern($format);
    } else $formatter = $this->formatter;
    return $formatter->format($this->timestamp($time));
  }

  /**
   * Given a timestamp return a formatted date string
   */
  private function getString(int $timestamp, $pattern): string
  {
    if (!$pattern) return '';
    if (!$timestamp) return '';
    $this->formatter->setPattern($pattern);
    return $this->formatter->format($timestamp);
  }

  /**
   * Format a daterange as human readable date string
   *
   * Usage:
   * echo $dates->range("2023-12-27", "2023-12-28");
   *
   * Usage with custom pattern:
   * echo $dates->range("2023-12-27", "2023-12-28", [
   *   'sameMonth' => ["d.", " until ", "d. MMM Y"],
   * ]);
   *
   * Note: Setting a pattern for this request will not modify
   * the globally set pattern. If you want to change the pattern globally
   * use $dates->setPatterns(...)->range(...)
   */
  function range($from, $to, array $patterns = []): string
  {
    $from = $this->timestamp($from);
    $to = $this->timestamp($to);

    // find out which pattern we want to use
    if (date("Ymd", $from) === date("Ymd", $to)) $pattern = 'sameDay';
    elseif (date("Ym", $from) === date("Ym", $to)) $pattern = 'sameMonth';
    elseif (date("Y", $from) === date("Y", $to)) $pattern = 'sameYear';
    else $pattern = 'default';

    // merge patterns
    // use global patterns by default and merge custom set patterns
    // for this request without modifying the default patterns
    $patterns = array_merge($this->patterns, $patterns);


    // get the pattern for this range (eg for sameDay)
    $pattern = $patterns[$pattern];
    $pieces = array_filter([
      $this->getString($from, $pattern[0]),
      $this->getString(
        $to,
        count($pattern) === 3 ? $pattern[2] : ''
      ),
    ]);

    // return the final date range string
    $glue = count($pattern) === 3 ? $pattern[1] : '';
    return implode($glue, $pieces);
  }

  /**
   * Set default date format pattern
   */
  function setFormat(string $format): self
  {
    $this->formatter->setPattern($format);
    return $this;
  }

  /**
   * Set locale of the IntlDateFormatter used to format dates
   */
  function setLocale($locale): self
  {
    $this->formatter = new IntlDateFormatter(
      $locale,
      IntlDateFormatter::NONE,
      IntlDateFormatter::NONE,
    );
    return $this;
  }

  /**
   * Set range patterns
   */
  function setPatterns($patterns): self
  {
    if (!$patterns) return $this;
    $this->patterns = array_merge(self::rangePatterns, $patterns);
    return $this;
  }

  /**
   * Convert time to timestamp
   * Use syntax supported by strtotime()
   */
  private function timestamp($time): int
  {
    if (is_string($time)) {
      if (is_numeric($time)) $time = (int)$time;
      else $time = strtotime($time);
    }
    if (is_int($time)) return $time;
    return 0;
  }
}
