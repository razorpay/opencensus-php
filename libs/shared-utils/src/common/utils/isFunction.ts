/**
 * Checks if the given value is a function.
 *
 * @param {unknown} value - The value to check.
 * @returns {boolean} - Returns true if the value is a function, false otherwise.
 */
export function isFunction(value: unknown): value is Function {
  return typeof value === 'function';
}
