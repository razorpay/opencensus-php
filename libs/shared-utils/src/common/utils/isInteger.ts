/**
 * Checks if the given value is a valid integer.
 *
 * @param {string} [value=''] - The value to check, defaults to an empty string.
 * @returns {boolean} - Returns true if the value is an integer, false otherwise.
 */
export const isInteger = (value: string = ''): boolean => {
  const integerRegExp = new RegExp(/^[0-9]+$/);
  return integerRegExp.test(value);
};
