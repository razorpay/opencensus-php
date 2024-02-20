const dateAndMonthsFormatRegexMaps = [
  // Matches ISO 8601 date format with optional time and timezone: YYYY-MM-DD or YYYY-MM-DDTHH:MM:SS.mmmZ
  // Example: "2023-01-20" or "2023-01-20T15:00:00.000Z"
  /\b\d{4}-\d{2}-\d{2}(T\d{2}:\d{2}:\d{2}.\d{3}Z)?\b/,

  // Matches dates with numeric day, month, and year in various orders, separated by slashes or dashes: DD/MM/YYYY, MM-DD-YYYY, etc.
  // Allows one or two digits for days and months, and two or four digits for the year.
  // Examples: "01-01-2023", "12/31/23"
  /\b\d{1,2}[/-]\d{1,2}[/-]\d{2,4}\b/,

  // Matches long date formats with month names, optionally including the year at the end.
  // Supports full month names followed by one or two digit day, optionally followed by a comma and a four-digit year.
  // Examples: "January 1, 2023", "March 20"
  /\b(?:January|February|March|April|May|June|July|August|September|October|November|December) \d{1,2}(, \d{4})?\b/,

  // Matches times in 12-hour format, allowing one or two digits for the hour, followed by two digits for minutes, optionally followed by two digits for seconds, and AM or PM indicator.
  // # 12-hour clock examples
  // 12:00 PM    # 12:00 PM
  // 12:00AM     # 12:00 AM
  // 3:45 am     # 3:45 am
  // 07:15PM     # 07:15 PM

  // # 24-hour clock examples
  // 13:30       # 13:30
  // 01:00 pm    # 01:00 pm
  // 05:30AM     # 05:30 AM
  // 23:59       # 23:59
  /\b(?:0?[0-9]|1[0-9]|2[0-3]?)(?::[0-5][0-9])? ?[APap][Mm]?\b/,

  // Matches standalone month names
  /\b(?:January|February|March|April|May|June|July|August|September|October|November|December)\b/i,
];

/**
 * Checks if the provided string value contains hardcoded date, time, or month values.
 * It uses a series of regular expressions defined in `dateAndMonthsFormatRegexMaps` to detect common date, time, or month formats.
 * This function is designed to encourage the use of dynamic date/time values instead of hardcoding, to ensure suitability across different countries and time zones.
 *
 * @param {string} value - The string value to be checked for hardcoded date, time, or month values.
 * @returns {string|false} A warning message if the value contains hardcoded date, time, or month formats; otherwise, returns `false`.
 */
function isDateTimeOrMonthHardcoded(value) {
  if (dateAndMonthsFormatRegexMaps.some((format) => format.test(value))) {
    return 'Avoid hardcoding date, time or months, as they may not be suitable for all countries or time zones.';
  }

  return false;
}

module.exports = isDateTimeOrMonthHardcoded;
