import { makeArray } from "./makeArray";

/**
 * Picks specific properties from an object based on the provided keys.
 *
 * @example
 * const user = { id: 1, name: 'John Doe', email: 'john@example.com', age: 30 };
 * const pickedProps = pickProps(user, ['id', 'name']);
 * console.log(pickedProps); 
 * // Output: { id: 1, name: 'John Doe' }
 *
 * @param {T} source - The source object to pick properties from.
 * @param {K | K[]} keys - The keys of the properties to pick.
 * @returns {Pick<T, K>} - A new object with the picked properties.
 */
export const pickProps = <T extends object, K extends keyof T>(source: T, keys: K | K[]): Pick<T, K> => {
  const keyArray = makeArray(keys) as K[];  // Ensure keys are treated as an array of K

  return Object.keys(source).reduce(
    (collector, key) => ({
      ...collector,
      ...(keyArray.indexOf(key as K) > -1 && { [key]: source[key] }),
    }),
    {} as Pick<T, K>,
  );
};
