/**
 * Calculates the number of days from today for a given Unix timestamp.
 * Returns a negative number if the given date is in the past.
 * 
 * @param {number} date - The Unix timestamp (in seconds).
 * @returns {number} - The number of days from today. Negative if the date is in the past.
 */
export const daysFromToday = (date: number): number =>
  Math.floor((Number(date) - new Date().getTime() / 1000) / 86400);
