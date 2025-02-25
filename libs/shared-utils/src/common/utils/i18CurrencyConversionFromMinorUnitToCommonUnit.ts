import { CurrencyCodeType } from "@razorpay/i18nify-js/currency";
import { getCurrencyConfig } from "./getCurrencyConfig";

/**
 * Converts a minor unit of amount to a common unit of amount, e.g., paise to rupees.
 * 1. This function calls `getCurrencyConfig` to get the decimals for the passed currency.
 * 2. Divides the passed amount by (10^decimals) to get the amount in the common unit (e.g., rupees for INR).
 * 
 * @param {number} amount - The minor unit of the currency (e.g., paise).
 * @param {CurrencyCodeType} [currency='INR'] - The currency code (default is 'INR').
 * @returns {number} - The amount in common units (e.g., rupees).
 */
export const i18CurrencyConversionFromMinorUnitToCommonUnit = (amount: number, currency: CurrencyCodeType = 'INR'): number => {
  const { decimals } = getCurrencyConfig(currency);
  const convertedAmount = (Number(amount) / 10 ** decimals).toFixed(decimals);
  return Number(convertedAmount);
};
