import { formatCurrencyWithAmount } from "./formatCurrencyWithAmount";
import {  CurrencyCodeType } from "@razorpay/i18nify-js/currency";

/**
 * Returns the placeholder value for the amount field based on the given currency.
 * 
 * This function retrieves the minimum value configuration for the specified currency from the global
 * `currencyList` object (if available), and formats it as a placeholder for the amount field. 
 * If the currency is not found or an error occurs, it falls back to a default placeholder of "0.00".
 * 
 * @param {CurrencyCodeType} [currency='INR'] - The currency code to fetch the placeholder for. Defaults to 'INR'.
 * @returns {string} The placeholder value for the amount field, formatted with the currency and minimum amount.
 * 
 * @example
 * // Example 1: Getting the placeholder for INR currency
 * const placeholder = getAmountFieldPlaceholder('INR');
 * console.log(placeholder); // Output: "₹100.00" (based on the configured min_value for INR)
 * 
 * @example
 * // Example 2: Fallback to default placeholder when currency is not found
 * const placeholder = getAmountFieldPlaceholder('USD');
 * console.log(placeholder); // Output: "0.00" (if no configuration is available for USD)
 */
export function getAmountFieldPlaceholder(currency: CurrencyCodeType = "INR"): string {
    try {
        // Access the currency configuration from the global currencyList object
        const config = window.currencyList?.[currency];

        // Return formatted placeholder based on the currency and its minimum value, or default to 100
        return formatCurrencyWithAmount(currency, config?.min_value ?? 100);
    } catch {
        // Fallback to default placeholder if an error occurs
        return '0.00';
    }
}
