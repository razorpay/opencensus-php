/**
 * Checks if a value is `null` or `undefined`.
 *
 * @param {any} value - The value to check.
 * @returns {boolean} - Returns `true` if the value is `null` or `undefined`, otherwise `false`.
 */
export const isNone = (value: any): boolean => {
  return value === null || value === undefined;
};
