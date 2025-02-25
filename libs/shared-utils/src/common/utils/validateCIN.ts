/**
 * Validates the CIN (Corporate Identification Number) based on specified criteria.
 * 
 * @param {string} value - The CIN value to validate.
 * @param {string} [type='CIN'] - The type of validation (default is 'CIN').
 * @returns {true | string} - Returns true if the CIN is valid, or an error message if invalid.
 * 
 * @example
 * const isValidCIN = validateCIN('ABC-1234', 'CIN');
 * // returns false
 * 
 * const isValidCIN2 = validateCIN('ABC-1234-FGHI', 'CIN');
 * // returns true
 */
export function validateCIN(value: string | null | undefined, type: string = 'CIN'): true | string {
  if (value) {
    if (value.length !== 21 && type === 'CIN') {
      return 'CIN length must be 21 characters';
    } else if (
      !/^([a-z]{3}-\d{4}|([F|f]\w{3}-\d{4})|[ul]\d{5}[a-z]{2}\d{4}[a-z]{3}\d{6})$/i.test(value)
    ) {
      return `Please provide a valid ${type}`;
    }
  }
  
  return true; // Return true if all validations pass
}
