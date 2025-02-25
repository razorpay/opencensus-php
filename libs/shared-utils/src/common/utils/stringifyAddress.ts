/**
 * Stringifies an address object into a formatted string.
 * 
 * @param {Object} addr - The address object containing the address components.
 * @param {string} [addr.line1] - The first line of the address.
 * @param {string} [addr.line2] - The second line of the address.
 * @param {string} [addr.city] - The city of the address.
 * @param {string} [addr.state] - The state of the address.
 * @param {string} [addr.country] - The country of the address (ISO code or name).
 * @param {string} [addr.zipcode] - The postal code of the address.
 * @return {string} - The formatted address string.
 * 
 * @example
 * const address = {
 *   line1: '123 Main St',
 *   line2: 'Apt 4B',
 *   city: 'New York',
 *   state: 'NY',
 *   country: 'US',
 *   zipcode: '10001'
 * };
 * 
 * const formattedAddress = stringifyAddress(address);
 * // returns "123 Main St,\nApt 4B,\nNew York, NY, US (10001)"
 */
export const stringifyAddress = (addr: {
  line1?: string;
  line2?: string;
  city?: string;
  state?: string;
  country?: string;
  zipcode?: string;
}): string => {
  let str = '';

  // Add Line 1 and Line 2
  if (addr.line1) {
    str += `${addr.line1},\n`;
  }
  if (addr.line2) {
    str += `${addr.line2},\n`;
  }

  // Generate and add last line (city, state, country)
  let lastLine: string[] = [];
  if (addr.city) {
    lastLine.push(addr.city);
  }
  if (addr.state) {
    lastLine.push(addr.state);
  }
  if (addr.country) {
    let country = addr.country;
    if (country.toLowerCase() === 'in') {
      country = 'India';
    } else {
      country = country.toUpperCase();
    }
    lastLine.push(country);
  }
  str += lastLine.join(', ');

  // Add zipcode
  if (addr.zipcode) {
    str += ` (${addr.zipcode})`;
  }

  return str;
};
