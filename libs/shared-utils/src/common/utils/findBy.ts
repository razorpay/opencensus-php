/**
 * Finds an object in an array by a specific property and value.
 *
 * @param {T[]} array - The array of objects to search.
 * @param {K} prop - The property key to search by.
 * @param {V} value - The value to match against the property.
 * @returns {T | undefined} - The first object where the specified property matches the value, or `undefined` if not found.
 *
 * @example
 * const data = [
 *   { id: 1, name: 'Alice' },
 *   { id: 2, name: 'Bob' },
 *   { id: 3, name: 'Alice' }
 * ];
 * const result = findBy(data, 'name', 'Alice');
 * console.log(result); 
 * // Output: { id: 1, name: 'Alice' }
 */
export const findBy = <T, K extends keyof T, V extends T[K]>(array: T[], prop: K, value: V): T | undefined => {
  return array.find((item) => item[prop] === value);
};
