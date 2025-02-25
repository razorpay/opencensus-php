const countries = {
  UK: 'united kingdom',
  IND: 'india',
} as const;

/**
 * Determines the type of input (text or number) for a country's PIN code based on the country name.
 *
 * @param {string} country - The name of the country.
 * @returns {'text' | 'number'} - Returns 'text' for countries like the UK, otherwise 'number'.
 *
 * @example
 * const pinTypeUK = getCountryPINcodeType('UK');
 * console.log(pinTypeUK); // Output: 'text'
 *
 * const pinTypeIND = getCountryPINcodeType('IND');
 * console.log(pinTypeIND); // Output: 'number'
 */
export const getCountryPINcodeType = (country: string = ''): 'text' | 'number' => {
  const countryLowerCase = country.toLowerCase();

  switch (countryLowerCase) {
    case countries.UK: {
      return 'text';
    }
    default: {
      return 'number';
    }
  }
};
