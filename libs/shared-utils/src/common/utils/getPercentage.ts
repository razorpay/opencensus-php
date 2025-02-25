import { getFixedNumber } from "./getFixedNumber";

/**
 * Calculates the percentage of the divisor relative to the divident.
 * If the divident is non-zero, it returns the fixed number percentage.
 *
 * @param {number} divident - The base value.
 * @param {number} divisor - The part value to calculate the percentage for.
 * @returns {number} - The calculated percentage, fixed to two decimal places.
 *
 * @example
 * const result = getPercentage(200, 50);
 * console.log(result); // Output: 25
 */
export const getPercentage = (divident: number, divisor: number): number => {
  let value = "";

  if (divident) {
    value = getFixedNumber((divisor / divident) * 100);
  }

  return +value;
};
