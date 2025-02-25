/**
 * Formats a given number or string to a fixed decimal format.
 * If the decimal part is '00', it removes the decimal portion.
 *
 * @param {number | string} value - The number or string to be formatted.
 * @returns {string} - The formatted number as a string.
 *
 * @example
 * const result = getFixedNumber(123.00);
 * console.log(result); // Output: "123"
 *
 * const result2 = getFixedNumber(123.45);
 * console.log(result2); // Output: "123.45"
 */
export const getFixedNumber = (value: number | string): string => {
  if (typeof value === 'number') {
    value = value.toFixed(2);
  }

  const valueArr = value.split('.');

  if (valueArr[1] === '00') {
    value = valueArr[0].replace('.', '');
  }

  return value;
};
