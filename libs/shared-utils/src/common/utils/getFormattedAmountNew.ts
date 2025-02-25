import { convertToMajorUnit, CurrencyCodeType } from '@razorpay/i18nify-js/currency';
import { formatAmount } from './formatAmount';

/**
 * Formats the given amount into the specified currency and returns it as a string.
 *
 * @param {number} amount - The amount to format.
 * @param {boolean} showCurrency - Whether or not to display the currency symbol.
 * @param {CurrencyCodeType} [currency='INR'] - The currency code for formatting.
 * @returns {string} - The formatted amount with or without currency symbol.
 *
 * @example
 * const result = getFormattedAmountNew(1000, true, 'USD');
 * console.log(result);
 * // Output: "$10.00"
 */
export const getFormattedAmountNew = (
  amount: number,
  showCurrency: boolean,
  currency: CurrencyCodeType = 'INR',
): string => {
  let adjustedAmount: string;
  try {
    adjustedAmount = convertToMajorUnit(amount, { currency }).toString();
  } catch (error) {
    adjustedAmount = (amount / 100).toFixed(2);
  }

  return formatAmount(
    typeof adjustedAmount === 'number' ? adjustedAmount : +adjustedAmount,
    showCurrency,
    currency,
  );
};
