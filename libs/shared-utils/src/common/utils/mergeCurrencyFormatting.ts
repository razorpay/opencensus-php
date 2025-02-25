import { CURRENCIES } from "../constants";

/**
 * Merges the formatting of the local currency list with API response data.
 * 
 * This function updates the provided `data` object by adding a `format` property 
 * from the `currencies` constant if the format is available for the currency.
 * 
 * @param {Record<string, { format?: string }>} data - The API response data where each key is a currency code.
 * @returns {Record<string, { format?: string }>} - The merged data object with added format information for each currency.
 */
export const mergeCurrencyFormatting = (data: Record<string, { format?: string }>): Record<string, { format?: string }> => {
  if (typeof data === 'object' && data !== null) {
    const mergedData = { ...data };
    
    Object.keys(data).forEach((currency) => {
      const formatting = CURRENCIES[currency]?.format;
      if (formatting) {
        mergedData[currency].format = formatting;
      }
    });

    return mergedData;
  }

  // @ts-ignore
  return currencies;
};
