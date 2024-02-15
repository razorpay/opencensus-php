import { COUNTRY_CODES } from 'common/components/CountryCodeInput/constant';

const COUNTRY_MAP = {};
COUNTRY_CODES.forEach(({ code, name }) => (COUNTRY_MAP[code.toLowerCase()] = name));

export function getCountryName(country) {
  // Handle null/undefined/empty string cases
  if (!country) {
    return null;
  }

  // Convert country code to lowercase for case-insensitive lookup
  const lowerCaseCountry = country.toLowerCase();

  // Lookup in COUNTRY_MAP
  const mappedCountry = COUNTRY_MAP[lowerCaseCountry];

  // Return mapped country if found, otherwise return original country
  return mappedCountry ? mappedCountry : country;
}
