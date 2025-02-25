/**
 * Validates the IFSC (Indian Financial System Code).
 * 
 * @param {string} value - The IFSC code to validate.
 * @returns {true | string} - Returns true if the IFSC code is valid, or an error message if invalid.
 * 
 * @example
 * const isValid = validateIFSC('SBIN0001234');
 * // returns true
 * 
 * const invalidResult = validateIFSC('SBIN001234');
 * // returns 'IFSC code must be 11 characters'
 */
export function validateIFSC(value: string | null | undefined): true | string {
  if (!value) {
    return true; // Return true if value is not present; mandatory validation should be handled elsewhere
  }

  return value.length !== 11 ? 'IFSC code must be 11 characters' : true;
}
