import { validateAlphanumericWithMaxLength } from "./validateAlphanumericWithMaxLength";

/**
 * Validates if a given string contains only alphanumeric characters
 * and falls within specified minimum and maximum lengths.
 * 
 * @param {string} value - The string to validate.
 * @param {number} minLength - The minimum allowed length for the string.
 * @param {number} maxLength - The maximum allowed length for the string.
 * @returns {boolean} - True if the string is alphanumeric and within the length range, false otherwise.
 * 
 * @example
 * const isValid = validateAlphanumericWithMinAndMaxLength('Hello123', 5, 10);
 * // returns true
 * 
 * const isTooShort = validateAlphanumericWithMinAndMaxLength('Hi', 5, 10);
 * // returns false
 * 
 * const isTooLong = validateAlphanumericWithMinAndMaxLength('Hello123456', 5, 10);
 * // returns false
 * 
 * const isInvalidChars = validateAlphanumericWithMinAndMaxLength('Hello@123', 5, 10);
 * // returns false
 */
export function validateAlphanumericWithMinAndMaxLength(value: string | null | undefined, minLength: number, maxLength: number): boolean {
  if (value === null || value === undefined || value.length < minLength) {
    return false; // Return false for null, undefined, or too short values
  }

  return validateAlphanumericWithMaxLength(value, maxLength);
}
