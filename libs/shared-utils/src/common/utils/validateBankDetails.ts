/**
 * Validates bank details based on the specified type (account number, IFSC code, or name).
 * 
 * @param {string} value - The value to validate.
 * @param {string} type - The type of bank detail to validate ('accNo', 'ifsc', or 'name').
 * @returns {boolean | null} - Returns true if valid, false if invalid, or null if type is not recognized.
 * 
 * @example
 * const isValidAccNo = validateBankDetails('123456789', 'accNo');
 * // returns true
 * 
 * const isValidIFSC = validateBankDetails('ABCD0123456', 'ifsc');
 * // returns true
 * 
 * const isInvalidName = validateBankDetails('John D', 'name');
 * // returns false
 */
export function validateBankDetails(value: string | null | undefined, type: string): boolean | null {
  const typeMapRegx: Record<string, RegExp> = {
    accNo: /^\d{9,18}$/,
    ifsc: /^[A-Z]{4}0[A-Z0-9]{6}$/i,
    name: /^([a-zA-Z0-9\s-_()/.']){4,120}$/i,
  };

  if (value && type && typeMapRegx[type]) {
    return typeMapRegx[type].test(value);
  }
  
  return null; // Return null if type is not recognized
}
