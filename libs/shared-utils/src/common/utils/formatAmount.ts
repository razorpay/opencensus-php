import { formatNumberByParts } from "@razorpay/i18nify-js/currency";

/**
 * Formats a numeric amount into a localized currency string.
 * This function takes a numeric amount and returns it as a formatted string in a given currency style.
 * It utilizes `formatNumberByParts`, a custom implementation of `Intl.NumberFormat.prototype.formatToParts()` from i18nify,
 * to handle the localization and formatting based on the provided options.
 *
 * @param {number} amt - The amount to be formatted.
 * @param {boolean} showCurrency - Whether to include the currency symbol in the formatted output.
 * @param {string} currency - The currency code (e.g., 'USD', 'INR') to format the amount.
 * @returns {string} - The formatted currency string.
 *
 * @example
 * const formattedAmount = formatAmount(1000, true, 'USD');
 * console.log(formattedAmount); // "$1,000.00"
 *
 * @example
 * const formattedAmount = formatAmount(500, false, 'INR');
 * console.log(formattedAmount); // "500.00"
 */
export const formatAmount = (amt: number, showCurrency: boolean, currency: string): string => {
  try {
    let options: { intlOptions: { minimumFractionDigits: number }; currency?: string } = {
      intlOptions: {
        minimumFractionDigits: 2,
      },
    };

    if (showCurrency) {
      options.currency = currency;
    }
    //@ts-ignore
    const byParts = formatNumberByParts(amt, options);
    return byParts.rawParts.reduce((acc, curr) => `${acc}${curr.value}`, '');
  } catch (e) {
    console.error(e);
    return showCurrency ? `${currency} ${amt}` : amt.toString();
  }
};
