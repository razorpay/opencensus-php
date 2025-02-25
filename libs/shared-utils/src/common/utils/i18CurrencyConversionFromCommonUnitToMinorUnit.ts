import { CurrencyCodeType } from "@razorpay/i18nify-js/currency";
import { getCurrencyConfig } from "./getCurrencyConfig";

/**
 * Converts a common unit of amount to a minor unit of amount, e.g., rupees to paise.
 * 1. This function calls `getCurrencyConfig` to get the decimals of the passed currency.
 * 2. Multiplies the passed amount by (10^decimals) to get the amount in minor units (e.g., paise for INR).
 * 
 * @param {number} amount - The common unit of the currency (e.g., rupees).
 * @param {CurrencyCodeType} [currency='INR'] - The currency code (default is 'INR').
 * @returns {number} - The amount in minor units (e.g., paise).
 */
export const i18CurrencyConversionFromCommonUnitToMinorUnit = (amount: number, currency: CurrencyCodeType = 'INR'): number => {
  const { decimals } = getCurrencyConfig(currency);
  return +(Number(amount) * 10 ** decimals).toFixed(0);
};
