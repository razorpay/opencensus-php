/**
 * Checks if the config tag API is supported for a given merchant country code.
 * 
 * @param {string} merchantCountryCode - The country code of the merchant.
 * @returns {string | undefined} - Returns the country code if supported, otherwise undefined.
 */
export function isConfigTagAPISupported(merchantCountryCode: string): string | undefined {
  const SUPPORTED_COUNTRIES: string[] = ['MY', 'SG', 'UK', 'US', 'ID', 'TH'];

  return SUPPORTED_COUNTRIES.find((countryCode) => countryCode === merchantCountryCode);
}
