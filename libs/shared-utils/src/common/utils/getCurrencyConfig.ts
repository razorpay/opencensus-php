import { getCurrencyList, CurrencyCodeType } from '@razorpay/i18nify-js/currency';
import { formatCurrencyWithAmount } from './formatCurrencyWithAmount';

/**
 * This function returns the decimals and formatter for the specified currency.
 * 1. Retrieves the currency list from the window object or local file.
 * 2. Gets the denomination value from the currency object (defaults to 100 if the denomination key is missing).
 * 3. Gets the formatter from the currency object (defaults to 3 comma formatter if missing).
 * 4. Returns the data object containing decimals and formatter.
 *
 * @param {CurrencyCodeType} [currency='INR'] - The currency code (e.g., 'INR', 'USD').
 * @returns {Object} An object containing `decimals` (number of decimal places) and a `formatter` function.
 *
 * @example
 * const config = getCurrencyConfig('USD');
 * console.log(config.decimals); // Output: 2
 * console.log(config.formatter(123456)); // Output: US$1,234.56
 */
export const getCurrencyConfig = (
  currency: CurrencyCodeType = 'INR',
): { decimals: number; formatter: (amount: number) => string } => {
  const currencyList = (window as any)?.currencyList;
  const denomination = currencyList?.[currency]?.denomination?.toString().length - 1;

  return {
    decimals: denomination || Number(getCurrencyList()?.[currency]?.minor_unit ?? 2),
    formatter: formatCurrencyWithAmount.bind(null, currency),
  };
};
