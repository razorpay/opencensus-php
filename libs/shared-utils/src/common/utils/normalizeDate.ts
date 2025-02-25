import moment from "moment";

moment.updateLocale('en', {
    relativeTime: {
      s: 'few secs',
      ss: '%s secs',
      m: 'a min',
      mm: '%d mins',
    },
  });
  

/**
 * Normalizes the given date to the format 'D/M/Y'.
 *
 * @param {string | Date} date - The date to be formatted. It can be a string or a Date object.
 * @returns {string} - The formatted date string in 'D/M/Y' format.
 */
export const normalizeDate = (date: string | Date): string => moment(date).format('D/M/Y');