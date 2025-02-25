/**
 * Calculates tax based on the given amount, tax rate, and whether the tax is inclusive or not.
 *
 * @param {number} base - The base amount on which tax is calculated.
 * @param {number} rate - The tax rate as a percentage.
 * @param {boolean} [inclusive=false] - If true, calculates the inclusive tax; otherwise, calculates the exclusive tax.
 * @returns {number} - The calculated tax.
 *
 * @example
 * const result = calculateTax(500, 18, true);
 * console.log(result); 
 * // Output: 76.27 (inclusive tax)
 *
 * @example
 * const result = calculateTax(500, 18, false);
 * console.log(result);
 * // Output: 90 (exclusive tax)
 */
export const calculateTax = (base: number, rate: number, inclusive: boolean = false): number => {
  if (inclusive) {
    return base - base / (1 + rate / 100);
  } else {
    return base * (rate / 100);
  }
};
