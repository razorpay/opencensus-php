import { CurrencyCodeType } from "@razorpay/i18nify-js";

/**
 * Converts a given amount to a localized string format based on the country code.
 * 
 * This function uses `Intl.NumberFormat` to format the given amount according to the locale 
 * derived from the provided country code. If `Intl.NumberFormat` is not supported in the 
 * browser, it falls back to JavaScript's `toLocaleString` method.
 * 
 * @param {number} amount - The amount to format.
 * @param {CurrencyCodeType} countryCode - The country code used to determine the locale for formatting.
 * @returns {string} The formatted amount in the locale's string format.
 * 
 * @example
 * // Example 1: Convert amount for Indian locale
 * const result = convertToLocale(1000, 'INR');
 * console.log(result); // Output: "1,000" (in en-IN locale)
 * 
 * @example
 * // Example 2: Fallback to default locale if country code is not supported
 * const result = convertToLocale(1000, 'SGD');
 * console.log(result); // Output: "1,000" (fallback to en-IN locale)
 */
export function convertToLocale(amount: number, countryCode: CurrencyCodeType): string {
    const SUPPORTED_LOCALE: Record<string, string> = {
        IN: 'en-IN',
        MY: 'en-MY',
    };
  
    // Get the appropriate locale or fallback to 'en-IN'
    const locale = SUPPORTED_LOCALE?.[countryCode] || SUPPORTED_LOCALE["IN"];
    
    // Check if `Intl.NumberFormat` is supported
    const hasIntlFormatSupport = typeof window.Intl?.NumberFormat === 'function';
    
    // Use `Intl.NumberFormat` if supported, otherwise fallback to `toLocaleString`
    if (hasIntlFormatSupport) {
        return new Intl.NumberFormat(locale).format(amount);
    }
    
    // @ts-ignore
    return Number.toLocaleString ? Number(amount).toLocaleString(locale) : String(amount);
}
