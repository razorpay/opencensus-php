/**
 * Filters an array of objects by a specific property and value.
 *
 * @param {T[]} array - The array of objects to filter.
 * @param {K} prop - The property key to filter by.
 * @param {V} value - The value to match against the property.
 * @returns {T[]} - A new array containing only the objects where the specified property matches the value.
 *
 * @example
 * const data = [
 *   { id: 1, name: 'Alice' },
 *   { id: 2, name: 'Bob' },
 *   { id: 3, name: 'Alice' }
 * ];
 * const result = filterBy(data, 'name', 'Alice');
 * console.log(result); 
 * // Output: [{ id: 1, name: 'Alice' }, { id: 3, name: 'Alice' }]
 */
export const filterBy = <T, K extends keyof T, V extends T[K]>(array: T[], prop: K, value: V): T[] => {
  return array.filter((item) => {
    return item[prop] === value;
  });
};
