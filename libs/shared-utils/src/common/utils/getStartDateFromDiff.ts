import moment, { Moment } from "moment";


moment.updateLocale('en', {
  relativeTime: {
    s: 'few secs',
    ss: '%s secs',
    m: 'a min',
    mm: '%d mins',
  },
});

/**
 * Given a difference in seconds and an end date, this function calculates the start date by subtracting the difference from the end date.
 * The result is the start of the day for the calculated date.
 *
 * @param {number} diff - The difference in seconds to subtract from the end date.
 * @param {Moment} endDate - The end date as a Moment object.
 * @returns {Moment} - The calculated start date as a Moment object, set to the start of the day.
 *
 * @example
 * const endDate = moment();
 * const diff = 86400; // 1 day in seconds
 * const startDate = getStartDateFromDiff(diff, endDate);
 * console.log(startDate.format('YYYY-MM-DD')); // Output: date 1 day before endDate
 */
export const getStartDateFromDiff = (diff: number, endDate: Moment): Moment => 
  moment(endDate.toDate().getTime() - diff * 1000).startOf('day');
