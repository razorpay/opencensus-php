/**
 * Validates the beneficiary name to ensure it meets length and character requirements.
 * 
 * @param {string} value - The beneficiary name to validate.
 * @returns {boolean} - True if the name is valid, false otherwise.
 * 
 * @example
 * const isValidName = validateBeneficiaryName('John Doe');
 * // returns true
 * 
 * const isInvalidName = validateBeneficiaryName('JD');
 * // returns false
 */
export function validateBeneficiaryName(value: string | null | undefined): boolean {
  const regex = /^[a-zA-Z0-9 ]+$/;

  if (value === null || value === undefined) {
    return false; // Return false for null or undefined values
  }

  return value.length >= 4 && value.length <= 120 && regex.test(value);
}
