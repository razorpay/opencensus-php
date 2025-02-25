/**
 * Checks if a value is a valid PAN number.
 *
 * @param {string} value - The value to check.
 * @returns {boolean} - Returns `true` if the value is a valid PAN number, otherwise `false`.
 */
export function isPanNumber(value: string): boolean {
  return !!(value && value.length === 10 && /^[a-zA-Z]{5}\d{4}[a-zA-Z]{1}$/.test(value));
}
