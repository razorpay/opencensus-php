/**
 * Checks if a given value is empty.
 *
 * @param value - The value to check for emptiness.
 * @returns `true` if the value is empty, `false` otherwise.
 *
 * @example
 * isEmpty(null); // true
 * isEmpty(undefined); // true
 * isEmpty(''); // true
 * isEmpty([]); // true
 * isEmpty({}); // true
 * isEmpty([1, 2, 3]); // false
 * isEmpty({ key: 'value' }); // false
 * isEmpty('text'); // false
 */
export const isEmpty = <T>(value: T): boolean => {
  if (value == null) return true;
  if (typeof value === 'boolean' || typeof value === 'number' || typeof value === 'function')
    return false;
  if (typeof value === 'string') return value.length === 0;
  if (Array.isArray(value)) return value.length === 0;
  if (value instanceof Map || value instanceof Set) return value.size === 0;
  if (typeof value === 'object') return Object.keys(value).length === 0;
  return false;
};
