import { makeArray } from "./makeArray";

/**
 * Creates a new object by omitting specified keys from the source object.
 * 
 * @param {Record<string, any>} source - The source object from which to omit keys.
 * @param {string | string[]} keys - A single key or an array of keys to omit.
 * @returns {Record<string, any>} - A new object without the specified keys.
 * 
 * @example
 * const source = { a: 1, b: 2, c: 3 };
 * const result = without(source, 'b');
 * // returns { a: 1, c: 3 }
 * 
 * const resultMultiple = without(source, ['a', 'c']);
 * // returns { b: 2 }
 */
export const without = (source: Record<string, any>, keys: string | string[]): Record<string, any> => {
  keys = makeArray(keys); // Ensure keys is an array
  return Object.keys(source).reduce((prev, key) => {
    if (!keys.includes(key)) { // Use includes for better readability
      prev[key] = source[key];
    }
    return prev;
  }, {} as Record<string, any>); // Specify the type for the accumulator
};
