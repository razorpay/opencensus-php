/**
 * Get the tax definition for a given country code.
 *
 * @param {string} countryCode - The country code.
 * @returns {string} The tax definition.
 */
export const getCountryTaxDefinition = ({ countryCode = '' }: { countryCode: string }) => {
  switch (countryCode) {
    case 'MY':
    case 'US':
      return 'Tax';
    case 'IN':
    case 'SG':
    default:
      return 'GST';
  }
};
