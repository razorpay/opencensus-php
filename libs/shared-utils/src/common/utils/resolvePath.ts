/**
 * Resolves a value from an object based on a given path.
 * @param {object} obj - The object to resolve the value from.
 * @param {string} path - The path to the value, in dot notation (e.g., 'a.b.c').
 * @param {*} defaultValue - The value to return if the resolved value is undefined or if an error occurs.
 * @returns {*} - The resolved value or the default value.
 * 
 * @example
 * // returns "value"
 * const obj = { a: { b: { c: "value" } } };
 * resolvePath(obj, 'a.b.c', 'default'); 
 * 
 * @example
 * // returns "default"
 * const obj = { a: { b: { c: null } } };
 * resolvePath(obj, 'a.b.c.d', 'default');
 */
export const resolvePath = (obj: object, path: string, defaultValue: any): any => {
  const arr = path?.split('.');
  let returnValue;
  try {
    returnValue = arr.reduce((acc, curr) => {
      return acc[curr];
    }, obj);
  } catch (e) {
    returnValue = defaultValue;
  } finally {
    returnValue = returnValue || defaultValue;
  }
  return returnValue;
};
