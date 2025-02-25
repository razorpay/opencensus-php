/**
 * Validates if a given string contains only alphanumeric characters (letters and numbers).
 * 
 * @param {string} value - The string to validate.
 * @returns {boolean} - True if the string is alphanumeric, false otherwise.
 * 
 * @example
 * const isValid = validateAlphanumeric('Hello123');
 * // returns true
 * 
 * const isInvalid = validateAlphanumeric('Hello@123');
 * // returns false
 */
export function validateAlphanumeric(value: string | null | undefined): boolean {
  if (value === null || value === undefined) {
    return false; // Return false for null or undefined values
  }

  const regex = /^[a-z0-9]+$/i;
  return regex.test(value);
}
