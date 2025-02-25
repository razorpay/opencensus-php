import { getFixedNumber } from "./getFixedNumber";

const _numberFormatRegex = /(.{1,2})(?=.(..)+(\...)$)/g;

/**
 * Formats a number by adding commas and ensures it has two decimal places.
 * Uses a regex to apply formatting and then calls `getFixedNumber` to remove unnecessary decimals.
 *
 * @param {number | string} value - The number or string to format.
 * @returns {string} - The formatted number with commas and possibly two decimal places.
 *
 * @example
 * const result = getFormattedNumber(1234567.89);
 * console.log(result); 
 * // Output: "12,34,567.89"
 */
export const getFormattedNumber = (value: number | string): string => {
  if (typeof value === 'number') {
    value = value.toFixed(2);
  }

  value = value.replace(_numberFormatRegex, '$1,');

  return getFixedNumber(value);
};
