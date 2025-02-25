/**
 * Validates if a given string contains only alphanumeric characters (letters and numbers)
 * and does not exceed a specified maximum length.
 * 
 * @param {string} value - The string to validate.
 * @param {number} maxLength - The maximum allowed length for the string.
 * @returns {boolean} - True if the string is alphanumeric and within the max length, false otherwise.
 * 
 * @example
 * const isValid = validateAlphanumericWithMaxLength('Hello123', 10);
 * // returns true
 * 
 * const isInvalid = validateAlphanumericWithMaxLength('Hello@123', 10);
 * // returns false
 * 
 * const isTooLong = validateAlphanumericWithMaxLength('Hello123456', 10);
 * // returns false
 */
export function validateAlphanumericWithMaxLength(value: string | null | undefined, maxLength: number): boolean {
  if (value === null || value === undefined) {
    return false; // Return false for null or undefined values
  }

  const regex = new RegExp(`^[a-z0-9]{0,${maxLength}}$`, 'i');
  return regex.test(value);
}
