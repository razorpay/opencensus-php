import moment from 'moment';

/**
 * Formats start and end timestamps into a human-readable string.
 * If the timestamps are in the same year, it returns a format like "11 May - 18 Jun 2024".
 * If the timestamps are in different years, it returns a format like "19 Dec 2023 - 18 Jan 2024".
 *
 * @param {number | undefined} start_date - The start timestamp in seconds.
 * @param {number | undefined} end_date - The end timestamp in seconds.
 * @returns {string} The formatted date string or an empty string if inputs are invalid.
 */

type timestamp = number | undefined;

export function formatTimestampRange(start_date: timestamp, end_date: timestamp): string {
  if (!start_date || !end_date) return '';

  const startDate = moment.unix(start_date);
  const endDate = moment.unix(end_date);
  const isSameYear = startDate.year() === endDate.year();

  if (isSameYear) {
    return `${startDate.format('DD MMM')} - ${endDate.format('DD MMM YYYY')}`;
  }

  return `${startDate.format('DD MMM YYYY')} - ${endDate.format('DD MMM YYYY')}`;
}
