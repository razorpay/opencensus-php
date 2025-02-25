/**
 * Validates the length of a pin code to ensure it is exactly 6 digits.
 * 
 * @param {string} value - The pin code to validate.
 * @returns {string | undefined} - Returns an error message if invalid, or undefined if valid.
 * 
 * @example
 * const validResult = validatePincodeLength('123456');
 * // returns undefined
 * 
 * const invalidResult = validatePincodeLength('12345');
 * // returns 'Pin Code must be 6 digits'
 * 
 * const emptyResult = validatePincodeLength('');
 * // returns undefined (no error for empty value)
 */
export function validatePincodeLength(value: string | null | undefined): string | undefined {
  return !value || /^[0-9]{6}$/.test(value) ? undefined : 'Pin Code must be 6 digits';
}
