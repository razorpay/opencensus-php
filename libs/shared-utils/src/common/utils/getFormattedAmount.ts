import { getCurrencyConfig } from './getCurrencyConfig';
import { CurrencyCodeType } from '@razorpay/i18nify-js/currency';

/**
 * Formats a given amount according to the currency and decimal configuration.
 * The function formats the number into the local format (e.g., Indian comma separated format).
 *
 * @param {number} amount - The amount to format.
 * @param {CurrencyCodeType} [currency='INR'] - The currency code to format the amount in.
 * @returns {string} - The formatted amount as a string.
 *
 * @example
 * const result = getFormattedAmount(123456789, 'INR');
 * console.log(result); // Output: ₹12,34,56,789.00 (formatted based on the currency and locale)
 */
export const getFormattedAmount = (amount: number, currency: CurrencyCodeType = 'INR'): string => {
  const { decimals, formatter } = getCurrencyConfig(currency);
  //@ts-ignore
  return formatter((amount / 10 ** decimals).toFixed(decimals));
};
