/**
 * Compares two objects and returns a new object containing key-value pairs that differ between the old and new objects.
 *
 * @param {Record<string, unknown>} oldObj - The original object for comparison. Defaults to an empty object.
 * @param {Record<string, unknown>} newObj - The new object to compare against. Defaults to an empty object.
 * @returns {Record<string, unknown>} - An object with key-value pairs that differ between oldObj and newObj.
 */
export const objectDiff = <T extends Record<string, unknown>>(oldObj: T = {} as T, newObj: T = {} as T): Partial<T> => {
  return Object.keys(newObj).reduce((prev, key) => {
    const value = newObj[key as keyof T];
    const oldValue = oldObj[key as keyof T];

    if (JSON.stringify(value) !== JSON.stringify(oldValue)) {
      prev[key as keyof T] = value;
    }
    return prev;
  }, {} as Partial<T>);
};
