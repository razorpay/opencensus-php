/**
 * Validates the PAN (Permanent Account Number) card.
 * 
 * @param {string} value - The PAN card number to validate.
 * @returns {true | string} - Returns true if the PAN card is valid, or an error message if invalid.
 * 
 * @example
 * const isValid = validatePANCard('ABCDE1234F');
 * // returns true
 * 
 * const invalidLength = validatePANCard('ABCDE123');
 * // returns 'PAN card must be 10 characters'
 * 
 * const invalidFormat = validatePANCard('12345ABCDE');
 * // returns 'Invalid PAN card'
 */
export function validatePANCard(value: string | null | undefined) {
  if (value) {
    if (value.length !== 10) {
      return 'PAN card must be 10 characters';
    } else if (!/^[a-zA-z]{5}\d{4}[a-zA-Z]{1}$/.test(value)) {
      return 'Invalid PAN card';
    }
  }
}
