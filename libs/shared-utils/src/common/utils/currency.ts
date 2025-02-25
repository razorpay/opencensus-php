import { CurrencyCodeType } from "@razorpay/i18nify-js/currency";

/**
 * Checks if a currency uses three decimal places.
 *
 * @param {CurrencyCodeType} currency - The currency code to check.
 * @returns {boolean} - Returns true if the currency uses three decimal places, false otherwise.
 * @example
 * isCurrencyThreeDecimal('KWD'); // true
 * isCurrencyThreeDecimal('USD'); // false
 */
export const isCurrencyThreeDecimal = (currency: CurrencyCodeType): boolean => {
  const currencies = ['KWD', 'OMR', 'BHD'];
  return currencies.includes(currency);
};

/**
 * Replaces a decimal point with a comma for European-style decimal formatting.
 *
 * @param {string} str - The string containing the decimal point.
 * @param {string} [comma=','] - The replacement character for the decimal point.
 * @returns {string} - The string with the decimal point replaced by a comma.
 * @example
 * makeDecimalComma('1234.56'); // '1234,56'
 * makeDecimalComma('1234.56', '.'); // '1234.56'
 */
const makeDecimalComma = (str: string, comma: string = ','): string => str.replace(/\./, comma);

/**
 * Formats an amount in INR currency style with commas for every two digits before the decimal point.
 *
 * @param {number} amount - The amount to format.
 * @param {number} decimals - The number of decimal places.
 * @returns {string} - The formatted string with commas.
 * @example
 * inrCommaFormatter(123456.00, 2); // '1,23,456.00'
 */
const inrCommaFormatter = (amount: number, decimals: number): string => {
  return String(amount).replace(new RegExp(`(.{1,2})(?=.(..)+(\\..{${decimals}})$)`, 'g'), '$1,');
};

/**
 * Currency formatters for various currency types and formatting styles.
 */
export const CURRENCY_FORMATTERS = {
  /**
   * Formats currency with commas for every three digits before the decimal.
   * E.g., `#,###.##`
   *
   * @param {number} amount - The amount to format.
   * @param {number} decimals - The number of decimal places.
   * @returns {string} - The formatted string.
   * @example
   * CURRENCY_FORMATTERS.three(1234567.89, 2); // '1,234,567.89'
   */
  three: (amount: number, decimals: number): string => {
    const amountStr = String(amount).replace(
      new RegExp(`(.{1,3})(?=(...)+(\\..{${decimals}})$)`, 'g'),
      '$1,',
    );
    return amountStr;
  },

  /**
   * Formats currency with commas replaced by dots and decimal points replaced by commas.
   * E.g., `#.###,##`
   *
   * @param {number} amount - The amount to format.
   * @param {number} decimals - The number of decimal places.
   * @returns {string} - The formatted string.
   * @example
   * CURRENCY_FORMATTERS.threecommadecimal(1234567.89, 2); // '1.234.567,89'
   */
  threecommadecimal: (amount: number, decimals: number): string => {
    const amountStr = makeDecimalComma(String(amount)).replace(
      new RegExp(`(.{1,3})(?=(...)+(\\,.{${decimals}})$)`, 'g'),
      '$1.',
    );
    return amountStr;
  },

  /**
   * Formats currency with spaces separating every three digits before the decimal.
   * E.g., `# ###.##`
   *
   * @param {number} amount - The amount to format.
   * @param {number} decimals - The number of decimal places.
   * @returns {string} - The formatted string.
   * @example
   * CURRENCY_FORMATTERS.threespaceseparator(1234567.89, 2); // '1 234 567.89'
   */
  threespaceseparator: (amount: number, decimals: number): string => {
    const amountStr = String(amount).replace(
      new RegExp(`(.{1,3})(?=(...)+(\\..{${decimals}})$)`, 'g'),
      '$1 ',
    );
    return amountStr;
  },

  /**
   * Formats currency with spaces separating every three digits and decimal points replaced by commas.
   * E.g., `# ###,##`
   *
   * @param {number} amount - The amount to format.
   * @param {number} decimals - The number of decimal places.
   * @returns {string} - The formatted string.
   * @example
   * CURRENCY_FORMATTERS.threespacecommadecimal(1234567.89, 2); // '1 234 567,89'
   */
  threespacecommadecimal: (amount: number, decimals: number): string => {
    const amountStr = makeDecimalComma(String(amount)).replace(
      new RegExp(`(.{1,3})(?=(...)+(\\,.{${decimals}})$)`, 'g'),
      '$1 ',
    );
    return amountStr;
  },

  /**
   * Formats currency with commas separating every three digits and an extra space after every three digits.
   * E.g., `#, ###.##`
   *
   * @param {number} amount - The amount to format.
   * @param {number} decimals - The number of decimal places.
   * @returns {string} - The formatted string.
   * @example
   * CURRENCY_FORMATTERS.szl(1234567.89, 2); // '1, 234, 567.89'
   */
  szl: (amount: number, decimals: number): string => {
    const amountStr = String(amount).replace(
      new RegExp(`(.{1,3})(?=(...)+(\\..{${decimals}})$)`, 'g'),
      '$1, ',
    );
    return amountStr;
  },

  /**
   * Formats currency with single quotes separating every three digits before the decimal.
   * E.g., `#'###.##`
   *
   * @param {number} amount - The amount to format.
   * @param {number} decimals - The number of decimal places.
   * @returns {string} - The formatted string.
   * @example
   * CURRENCY_FORMATTERS.chf(1234567.89, 2); // "1'234'567.89"
   */
  chf: (amount: number, decimals: number): string => {
    const amountStr = String(amount).replace(
      new RegExp(`(.{1,3})(?=(...)+(\\..{${decimals}})$)`, 'g'),
      "$1'",
    );
    return amountStr;
  },

  /**
   * Formats currency in INR style with commas after every two digits from the right.
   * E.g., `#,##,###.##`
   *
   * @param {number} amount - The amount to format.
   * @param {number} decimals - The number of decimal places.
   * @returns {string} - The formatted string.
   * @example
   * CURRENCY_FORMATTERS.inr(1234567.89, 2); // '12,34,567.89'
   */
  inr: (amount: number, decimals: number): string => {
    const amountStr = inrCommaFormatter(amount, decimals);
    return amountStr;
  },

  /**
   * Formats currency with commas separating every three digits before the decimal.
   * Works for MYR and similar formats.
   * E.g., `#,###.## | #,###,###.## | ###,###,###.##`
   *
   * @param {number} amount - The amount to format.
   * @param {number} decimals - The number of decimal places.
   * @returns {string} - The formatted string.
   * @example
   * CURRENCY_FORMATTERS.myr(1234567.89, 2); // '1,234,567.89'
   */
  myr: (amount: number, decimals: number): string => {
    const amountStr = String(amount).replace(
      new RegExp(`(.{1,3})(?=(...)+(\\..{${decimals}})$)`, 'g'),
      '$1,',
    );
    return amountStr;
  },

  /**
   * No formatting applied to the amount.
   * Simply returns the string representation of the number.
   *
   * @param {number} amount - The amount to format.
   * @returns {string} - The amount as a string.
   * @example
   * CURRENCY_FORMATTERS.none(1234567.89); // '1234567.89'
   */
  none: (amount: number): string => String(amount),
};
