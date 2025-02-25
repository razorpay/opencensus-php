import { CurrencyCodeType } from '@razorpay/i18nify-js/currency';
import { getCurrencyConfig } from './getCurrencyConfig';

/**
 * Validates an amount based on specified currency, minimum amount allowed, and decimal rules.
 *
 * @param {number | string} val - The amount to validate.
 * @param {number} minAmountAllowed - The minimum allowable amount.
 * @param {CurrencyCodeType} [currency='INR'] - The currency code for validation.
 * @returns {string | undefined} - Returns an error message if validation fails, otherwise undefined.
 *
 * @example
 * const validationResult = validateAmount(123.456, 100);
 * // returns undefined (valid)
 *
 * const invalidResult = validateAmount(123.456, 100, 'USD');
 * // returns "Amount in selected currency must have up to 2 decimal places" if USD allows 2 decimals
 */
export function validateAmount(
  val: number | string,
  minAmountAllowed: number,
  currency: CurrencyCodeType = 'INR',
): string | undefined {
  if (val) {
    const { decimals } = getCurrencyConfig(currency);
    const decimalPart = val.toString().split('.')[1] ?? '';
    const value = Number(val);

    const validPattern = 123.45; // Example pattern for validation message

    // Validate input is of number type
    if (isNaN(value)) {
      return `Amount must be a number in the format ${validPattern.toFixed(decimals)}`;
    }

    if (value < 0) {
      return "Amount can't be negative.";
    }

    if (decimalPart.length > decimals) {
      return decimals === 0
        ? 'Amount in selected currency must not have any decimal places'
        : `Amount in selected currency must have upto ${decimals} decimal places`;
    }

    if (typeof minAmountAllowed !== 'undefined' && value < Number(minAmountAllowed)) {
      return `Amount must be at least ${minAmountAllowed}`;
    }

    if (decimals === 3 && decimalPart.length === 3 && decimalPart[2] !== '0') {
      return 'Last digit should be 0 for three decimal currencies';
    }
  }
}
