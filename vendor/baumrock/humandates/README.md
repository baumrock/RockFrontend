# HumanDates

HumanDates is a PHP library that provides convenient methods for formatting dates as human-readable strings and generating human-friendly date ranges. It uses the `IntlDateFormatter` class to ensure accurate localization and formatting based on the specified locale.

```html
<!-- before --> 1.1.2023 - 3.1.2023
<!-- after  --> 1. - 3.1.2023
```

<img width="861" alt="image" src="https://github.com/baumrock/HumanDates/assets/8488586/59a2a9fb-a925-4c79-8d99-f199583a3bcc">


## Usage

To get started, include the HumanDates library and create a new instance:

```php
// manual download
require_once "/path/to/HumanDates.php";

// using composer
// composer require baumrock/humandates
require_once "/path/to/composer/autoload.php";

// create HumanDates instance
$dates = new HumanDates();
echo $dates->format("2023-01-01");              // 1. Jan 2023
echo $dates->range("2023-01-01", "2023-01-03"); // 1. - 3. Jan 2023
```

## Why Use HumanDates?

HumanDates provides several benefits and reasons to consider using it in your PHP projects:

1. Simplified Date Formatting

    HumanDates simplifies the process of formatting dates in PHP. It offers a straightforward and intuitive API for formatting both single dates and date ranges, eliminating the need for complex and error-prone manual formatting.

2. Localization Support

    HumanDates leverages the `IntlDateFormatter` class, ensuring accurate localization of date formats based on the specified locale. This allows you to generate date strings that are culturally appropriate and easily understood by users from different regions.

3. Customizable Date Range Formats

    With HumanDates, you have the flexibility to define custom format patterns for date ranges. You can easily tailor the output to match your specific requirements, such as including or excluding certain date components, adjusting separators, or changing the order of elements.

4. Replacement for Deprecated `strftime()`

    As of PHP 8, the `strftime()` function is deprecated and will be removed in PHP 9. HumanDates can serve as a replacement for `strftime()`, providing a modern and reliable alternative for formatting dates in PHP.

5. MIT License

    HumanDates is released under the permissive MIT License, allowing you to freely use, modify, and distribute the library in your projects, both personal and commercial.

By using HumanDates, you can enhance the user experience by presenting dates in a human-readable format, improve code maintainability, and ensure consistent and accurate date formatting across your application.

Give HumanDates a try and simplify your date formatting needs with ease!

## Formatting a Single Date

You can format a single date using the `format()` method, which can serve as a replacement for the `strftime()` function which is deprecated in PHP8 and will be removed in PHP9:

```php
echo $dates->format("2023-01-01", "d.M.y"); // Output: 1.1.2023
```

The `format()` method accepts a date string or timestamp as the first parameter and an optional format pattern as the second parameter. The format pattern follows the syntax defined by the `IntlDateFormatter` class: [See here for a list of all available letters](https://unicode-org.github.io/icu/userguide/format_parse/datetime/#date-field-symbol-table).

## Formatting a Date Range

HumanDates provides a convenient way to format date ranges as well. Use the `range()` method by passing the start and end dates:

```php
// example using locale de_DE
echo $dates->range("2023-01-01", "2023-02-03"); // Output: 1. Jan. - 3. Feb. 2023
echo $dates->range("2023-01-01", "2023-01-03"); // Output: 1. - 3. Jan. 2023
```

By default, the `range()` method uses predefined patterns to format the date range. However, you can also specify custom patterns by providing an array of patterns as the third parameter:

```php
// example using locale de_DE
echo $dates->range("2023-01-01", "2023-01-03", [
	'default'   => ['d. MMMM y', ' bis ', 'd. MMMM y'], // 1. Januar 2023 bis 3. Februar 2024
	'sameYear'  => ['d. MMMM',   ' bis ', 'd. MMMM y'], // 1. Januar bis 3. Februar 2023
	'sameMonth' => ['d.',        ' bis ', 'd. MMMM y'], // 1. bis 3. Januar 2023
	'sameDay'   => ['d. MMMM y'],                       // 1. Januar 2023
]);
```

Note that this will set the patterns for this single request only! If you want to modify the globally used patterns you can do this:

```php
echo $dates->setPatterns(...)->range(...);
```

## Setting Locales

You can set the locale for date formatting either globally for the current `HumanDates` instance or on a per-format basis.

To set the locale globally, pass the desired locale as a parameter when creating a new `HumanDates` instance:

```php
$dates = new HumanDates("de_AT");
echo $dates->format($timestamp, "d. MMMM y"); // Output: 1. Jänner 2023
```

To set the locale for a specific `format()` call, include the `locale` parameter:

```php
echo $dates->format(
  $timestamp,
  format: "d. MMMM y",
  locale: "de_DE",
); // Output: 1. Januar 2023
```

## Setting Defaults

You can set default formatting options for your `HumanDates` instance by specifying the locale and format pattern during initialization:

```php
$dates = new HumanDates("de_AT", "dd.MM.y");
echo $dates->format("2023-1-1"); // Output: 01.01.2023
echo $dates->format("2023-1-1", "d. MMMM y"); // Output: 1. Jänner 2023
```

## Star the Repository

If you find HumanDates useful or interesting, please star the repository on GitHub. By starring the repository, you show your appreciation and support for the project. It also helps us understand the level of community interest and motivates us to further improve the library. ⭐
