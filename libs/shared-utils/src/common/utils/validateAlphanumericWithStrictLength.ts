/**
 * Validates if a given string contains only alphanumeric characters
 * and has a specified strict length.
 * 
 * @param {string} value - The string to validate.
 * @param {number} length - The exact length the string must have.
 * @returns {boolean} - True if the string is alphanumeric and has the strict length, false otherwise.
 * 
 * @example
 * const isValid = validateAlphanumericWithStrictLength('Hello123', 8);
 * // returns true
 * 
 * const isInvalidLength = validateAlphanumericWithStrictLength('Hello123', 7);
 * // returns false
 * 
 * const isInvalidChars = validateAlphanumericWithStrictLength('Hello@123', 8);
 * // returns false
 */
export function validateAlphanumericWithStrictLength(value: string | null | undefined, length: number): boolean {
  if (value === null || value === undefined) {
    return false; // Return false for null or undefined values
  }

  const regex = new RegExp(`^[a-z0-9]{${length}}$`, 'i');
  return regex.test(value);
}
