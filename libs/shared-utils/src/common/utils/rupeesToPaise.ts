/**
 * Converts an amount in rupees to paise.
 * @param {number | string} amount - The amount in rupees to convert.
 * @returns {number} - The equivalent amount in paise.
 * 
 * @example
 * // returns 2500
 * rupeesToPaise(25);
 * 
 * @example
 * // returns 1500
 * rupeesToPaise('15');
 */
export const rupeesToPaise = (amount: number | string): number => {
  amount = (Number(amount) * 100).toFixed(0);
  return Number(amount);
};
