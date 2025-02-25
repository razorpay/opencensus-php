/**
 * Converts a given amount in paise to INR and formats it to two decimal places.
 *
 * @param {number | string} amount - The amount in paise.
 * @returns {string} - The formatted INR amount as a string with two decimal places.
 *
 * @example
 * const amount = getFixedINRAmount(1200);
 * console.log(amount); // Output: "12.00"
 */
export const getFixedINRAmount = (amount: number | string): string => (Number(amount) / 100).toFixed(2);
