import { validatePANCard } from './validatePANCard';

/**
 * Validates a personal PAN (Permanent Account Number) based on the value and registration status of the business.
 *
 * @param {string} value - The PAN card number to validate.
 * @param {boolean} isUnregisteredBusiness - Indicates if the business is unregistered.
 * @returns {string | undefined} - Returns an error message if invalid, or undefined if valid.
 *
 * @example
 * const result = validatePersonalPAN('ABCDE1234P', true);
 * // returns undefined (valid)
 *
 * const invalidResult = validatePersonalPAN('ABCDE1234F', false);
 * // returns 'Invalid PAN format'
 *
 * const businessPANResult = validatePersonalPAN('ABCDE1234F', true);
 * // returns "The PAN entered is a business PAN..."
 */
export function validatePersonalPAN(
  value: string | null | undefined,
  isUnregisteredBusiness: boolean,
): string | undefined {
  if (validatePANCard(value)) {
    return validatePANCard(value);
  } else if (value && value[3] !== 'P' && value[3] !== 'p') {
    if (isUnregisteredBusiness) {
      return "The PAN entered is a business PAN. If you are a registered business, please change your business type in the 'Business Overview' tab.";
    } else {
      return 'Invalid PAN format';
    }
  }
}
