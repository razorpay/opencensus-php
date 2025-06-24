const MONETARY_UNIT_TEXT: Record<string, string> = {
  IN: 'paise',
  MY: 'cents',
  US: 'cents',
};

/**
 * Returns the monetary unit text for a given country code.
 *
 * @param {string} countryCode - The country code of the merchant, e.g., "IN" or "MY".
 * @returns {string | undefined} - The corresponding monetary unit text, or `undefined` if the country code is not found.
 */
export const monetaryUnitText = (countryCode: string): string | undefined =>
  MONETARY_UNIT_TEXT[countryCode];
