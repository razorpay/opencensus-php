/**
 * Converts the input into an array.
 * If the input is already an array, it is returned as-is.
 * If the input is falsy or not an array, it is wrapped into an array.
 *
 * @param {T | T[] | null | undefined} obj - The object to convert to an array.
 * @returns {T[]} - The input wrapped in an array, or an empty array if the input is falsy.
 *
 * @example
 * makeArray(5); // returns [5]
 *
 * @example
 * makeArray([1, 2, 3]); // returns [1, 2, 3]
 *
 * @example
 * makeArray(null); // returns []
 */
export function makeArray<T>(obj: T | T[] | null | undefined): T[] {
  if (!obj) {
    return [];
  }
  return Array.isArray(obj) ? obj : [obj];
}
