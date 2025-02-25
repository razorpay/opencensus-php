/**
 * Checks if a value is defined (not `undefined`).
 * 
 * @param {any} value - The value to check.
 * @returns {boolean} - Returns true if the value is defined, false otherwise.
 */
export function isDefined(value: any): boolean {
  return typeof value !== 'undefined';
}
