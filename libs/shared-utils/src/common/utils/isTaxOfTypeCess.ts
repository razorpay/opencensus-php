/**
 * Figure out if the given Tax is of type cess.
 *
 * @param {Tax} tax - The tax object to check.
 * @returns {boolean} - Returns `true` if the tax is of type "cess", otherwise `false`.
 */
export const isTaxOfTypeCess = (tax: { name: string; rate_type: string }): boolean =>
  Boolean(
    tax && tax.name && tax.name.toLowerCase().startsWith('cess') && tax.rate_type === 'percentage'
  );
