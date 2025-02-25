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
 * Formats a Unix timestamp (in seconds) into a relative time string from the current time.
 * 
 * @example
 * formatFromNow(1633058400); // "5 days ago"
 * 
 * @param {number} unixSeconds - The Unix timestamp in seconds.
 * @returns {string} - The relative time string.
 */
export const formatFromNow = (unixSeconds: number): string => moment(unixSeconds * 1e3).fromNow();
