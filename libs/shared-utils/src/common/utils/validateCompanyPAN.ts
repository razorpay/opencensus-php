import { validatePANCard } from "./validatePANCard";

/**
 * Validates the company PAN (Permanent Account Number) based on the provided value.
 * 
 * @param {string} value - The PAN value to validate.
 * @returns {string | undefined} - Returns an error message if invalid, or undefined if valid.
 * 
 * @example
 * const result = validateCompanyPAN('ABCDE1234F');
 * // returns undefined (valid)
 * 
 * const invalidResult = validateCompanyPAN('ABCDE1234Z');
 * // returns 'Invalid PAN format'
 */
export function validateCompanyPAN(value: string | null | undefined): string | undefined {
  if (!value) {
    return undefined; // Return undefined for empty or null values
  }

  const panValidationError = validatePANCard(value);
  if (typeof panValidationError === "string") {
    return panValidationError; // Return the error from the PAN validation
  } else if (!['C', 'H', 'F', 'A', 'T', 'B', 'J', 'G', 'L'].includes(value[3].toUpperCase())) {
    return 'Invalid PAN format'; // Return error for invalid PAN format
  }

  return undefined; // Return undefined if all validations pass
}
