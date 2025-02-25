/**
 * Checks if a value is a valid phone number.
 *
 * @param {string} phone - The phone number to check.
 * @returns {boolean} - Returns `true` if the value is a valid phone number, otherwise `false`.
 */
export const isPhone = (phone: string = ''): boolean => {
  const phoneRegExp = new RegExp(/^$|\+?[0-9]{8,15}$/);
  return phoneRegExp.test(phone);
};
