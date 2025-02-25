import { flattenObject } from "./flattenObject";

/**
 * Method to create a query string separated by '|' instead of '&'.
 * It removes keys with falsy values (null, undefined, empty string, or 0).
 *
 * @param {Record<string, any>} params - The object to convert into a pipe-separated query string.
 * @returns {string} - The pipe-separated string of keys.
 *
 * @example
 * const params = { name: 'John', age: 0, city: 'NY' };
 * const result = getKeysSeparatedByPipe(params);
 * console.log(result); // Output: 'name|city'
 */
export const getKeysSeparatedByPipe = (params: Record<string, any>): string => {
  if (!params) return '';

  params = flattenObject(params, '_');

  let keys = Object.keys(params);
  for (let i = 0; i < keys.length; i++) {
    let key = keys[i],
      val = params[key];

    // Remove keys that don't contain a value.
    if (val === null || val === undefined || val === '' || val == 0) {
      delete params[key];
    }
  }

  // Stringify all the other keys and return the string.
  return Object.keys(params).join('|');
};
