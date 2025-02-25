/**
 * Converts a boolean to 1 or 0, and returns undefined if the input is undefined.
 * 
 * @param {boolean | undefined} bool - The boolean value to normalize.
 * @returns {number | undefined} - Returns 1 for `true`, 0 for `false`, or `undefined` if the input is `undefined`.
 */
export const normalizeBoolean = (bool?: boolean): number | undefined => {
  return bool ? 1 : 0;
};
