/**
 * Adds a prefix to each key in the provided object and returns a new object with the updated keys.
 *
 * @template T - The type of the object values.
 * @param {string} prefix - The prefix to add to each key.
 * @param {Record<string, T>} data - The object whose keys need to be prefixed.
 * @returns {Record<string, T>} - A new object with prefixed keys.
 *
 * @example
 * const data = { name: 'John', age: 30 };
 * const result = addPrefixToObjectKeys('user_', data);
 * console.log(result);
 * // Output: { user_name: 'John', user_age: 30 }
 */
export const addPrefixToObjectKeys = <T>(prefix: string, data: Record<string, T>): Record<string, T> => {
  const newData: Record<string, T> = {};

  for (const key in data) {
    if (data.hasOwnProperty(key)) {
      newData[`${prefix}${key}`] = data[key];
    }
  }

  return newData;
};
