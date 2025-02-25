import moment from 'moment';

/**
 * Returns the current month as a number (0-11, where 0 = January and 11 = December).
 *
 * @returns {number} - The current month number.
 * @example
 * getCurrentMonth(); // e.g., 8 (for September)
 */
export const getCurrentMonth = (): number => {
  const today = new Date();
  return today.getMonth();
};

/**
 * Returns the current Unix timestamp (seconds since 1 Jan 1970).
 *
 * @returns {number} - The current Unix time.
 * @example
 * getCurrentUnixTime(); // 1633041846
 */
export const getCurrentUnixTime = (): number => {
  return moment().unix();
};

/**
 * Returns the current year.
 *
 * @returns {number} - The current year as a four-digit number.
 * @example
 * getCurrentYear(); // 2024
 */
export const getCurrentYear = (): number => {
  return moment().year();
};

/**
 * Returns the year corresponding to a Unix timestamp.
 *
 * @param {number} timestamp - The Unix timestamp to extract the year from.
 * @returns {number} - The year extracted from the timestamp.
 * @example
 * getYear(1633041846); // 2021
 */
export const getYear = (timestamp: number): number => {
  return moment.unix(timestamp).year();
};

/**
 * Checks if a given Unix timestamp is after a specified date.
 *
 * @param {number} timestamp - The Unix timestamp to check.
 * @param {moment.MomentInput} afterDate - The date to compare against.
 * @returns {boolean} - Returns true if the timestamp is after the specified date, false otherwise.
 * @example
 * isAfterDate(1633041846, moment().subtract(1, 'day')); // true or false
 */
export const isAfterDate = (timestamp: number, afterDate: moment.MomentInput): boolean => {
  return moment.unix(timestamp).isAfter(afterDate);
};

/**
 * An array mapping month indices to their corresponding short-form names.
 * E.g., monthsMap[0] is 'Jan', monthsMap[11] is 'Dec'.
 *
 * @example
 * monthsMap[0]; // 'Jan'
 */
export const monthsMap: string[] = [
  'Jan',
  'Feb',
  'Mar',
  'Apr',
  'May',
  'Jun',
  'Jul',
  'Aug',
  'Sep',
  'Oct',
  'Nov',
  'Dec',
];

/**
 * Returns the date format string based on how long ago the timestamp occurred.
 * - If the timestamp is from the current year, it formats as 'MMM D, h:mma'.
 * - If the timestamp is from within the last 10 days, it formats as 'ddd MMM D, h:mma'.
 * - If the timestamp is from a previous year, it formats as 'MMM D, YYYY, h:mma'.
 *
 * @param {number} timestamp - The Unix timestamp to format.
 * @returns {string} - The appropriate date format string.
 * @example
 * getDateFormat(1633041846); // 'Sep 30, 2021, 8:30pm'
 */
export const getDateFormat = (timestamp: number): string => {
  let format = 'MMM D, YYYY, h:mma'; // previous year format
  const currentYear = getCurrentYear();
  const createdAtYear = getYear(timestamp);
  
  if (createdAtYear === currentYear) {
    // current year format
    format = 'MMM D, h:mma';
    const tendDaysBefore = moment().subtract(10, 'days');
    if (isAfterDate(timestamp, tendDaysBefore)) {
      // last 10 days format
      format = 'ddd MMM D, h:mma';
    }
  }
  return format;
};
