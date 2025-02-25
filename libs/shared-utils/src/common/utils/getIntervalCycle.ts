const periods: Record<string, string> = {
  weekly: 'Week',
  monthly: 'Month',
  yearly: 'Year',
  daily: 'Day',
};

/**
 * Returns a string describing the interval and period cycle.
 * @param {number} interval - The interval number.
 * @param {keyof typeof periods} period - The period type ('weekly', 'monthly', 'yearly', 'daily').
 * @returns {string} - A string describing the cycle, e.g., "Every Week" or "Once in 2 Months".
 *
 * @example
 * getIntervalCycle(1, 'weekly'); // Output: "Every Week"
 * getIntervalCycle(2, 'monthly'); // Output: "Once in 2 Months"
 */
export const getIntervalCycle = (interval: number, period: keyof typeof periods): string => {
  if (interval === 1) {
    return `Every ${periods[period]}`;
  } else {
    return `Once in ${interval} ${periods[period]}s`;
  }
};
