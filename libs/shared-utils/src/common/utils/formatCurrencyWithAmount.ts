import { formatNumberByParts, CurrencyCodeType } from "@razorpay/i18nify-js/currency";

/**
 * Formats the given amount with the specified currency.
 * @example
 * formatCurrencyWithAmount('INR', 1000) // ₹10.00
 *
 * @param {CurrencyCodeType} currency - The currency symbol or code.
 * @param {number} amount - The amount to be formatted.
 * @returns {string} The formatted currency amount.
 */
export const formatCurrencyWithAmount = (currency: CurrencyCodeType, amount: number): string => {
  const formatted = formatNumberByParts(amount, { currency: currency ?? 'INR' });
  let formattedStr = formatted?.integer + '';

  if (formatted?.decimal && formatted?.fraction) {
    formattedStr += formatted?.decimal + formatted?.fraction;
  }

  return formattedStr;
};
