/**
 * Converts an amount in paise to rupees.
 *
 * @param {number} amount - The amount in paise to convert.
 * @returns {number} - The equivalent amount in rupees.
 */
export const paiseToRupees = (amount: number): number => {
  const convertedAmount = (amount / 100).toFixed(2);
  return Number(convertedAmount);
};
