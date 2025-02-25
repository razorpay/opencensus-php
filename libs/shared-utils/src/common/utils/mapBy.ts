/**
 * Maps an array of objects by a specified property.
 * Returns an array of values corresponding to the given property from each object.
 *
 * @param {Array} array - The array of objects to map.
 * @param {string} prop - The property to map by.
 * @returns {Array} - An array of values from the specified property.
 *
 * @example
 * const users = [{ id: 1, name: 'Alice' }, { id: 2, name: 'Bob' }];
 * const userNames = mapBy(users, 'name'); // returns ['Alice', 'Bob']
 */
export const mapBy = <T, K extends keyof T>(array: T[], prop: K): T[K][] => {
  return array.map((item) => item[prop]);
};
