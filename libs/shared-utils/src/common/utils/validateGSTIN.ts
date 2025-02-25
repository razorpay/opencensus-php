import { isValidGSTIN } from "./isValidGSTIN";

/**
 * Validates the GSTIN (Goods and Services Tax Identification Number).
 * 
 * @param {string} gstin - The GSTIN to validate.
 * @returns {string | undefined} - Returns an error message if invalid, or undefined if valid.
 * 
 * @example
 * const result = validateGSTIN('29AAGCR4375J1ZU');
 * // returns "This is Razorpay's GSTIN number. Please enter your GSTIN number"
 * 
 * const invalidResult = validateGSTIN('INVALIDGSTIN');
 * // returns 'Invalid GSTIN'
 * 
 * const validResult = validateGSTIN('07ABCDE1234Z1Z5');
 * // returns undefined
 */
export function validateGSTIN(gstin: string | null | undefined): string | undefined {
  const rzp_gst = '29AAGCR4375J1ZU';

  // No error if field is empty.
  if (!gstin) return undefined;

  if (gstin === rzp_gst) {
    return `This is Razorpay's GSTIN number. Please enter your GSTIN number`;
  }

  // Return error message if invalid.
  if (!isValidGSTIN(gstin)) {
    return 'Invalid GSTIN';
  }

  return undefined; // Implicit is better than explicit
}
