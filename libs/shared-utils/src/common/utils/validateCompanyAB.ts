/**
 * Validates that the company name and contact name are not the same.
 * 
 * @param {string} [value1=''] - The company name to validate.
 * @param {string} [value2=''] - The contact name to validate.
 * @param {boolean} [isExpOn=false] - Flag indicating whether to perform the validation.
 * @returns {true | string} - Returns true if valid, or an error message if invalid.
 * 
 * @example
 * const result = validateCompanyAB('ABC Corp', 'abc corp', true);
 * // returns "Company name cannot be same as Contact Name"
 * 
 * const result2 = validateCompanyAB('ABC Corp', 'XYZ Ltd', true);
 * // returns true
 */
export function validateCompanyAB(value1: string = '', value2: string = '', isExpOn: boolean = false): boolean | string {
  if (!isExpOn) return false;
  value1 = value1 === null ? '' : value1;
  value2 = value2 === null ? '' : value2;
  return value1.toLowerCase() === value2.toLowerCase()
    ? 'Company name cannot be same as Contact Name'
    : false;
}
