/**
 * Validates whether the provided pin code is a valid 6-digit number.
 * The pin code should start with a digit between 1-9.
 *
 * @param {string} pinCode - The pin code to validate.
 * @returns {boolean} - Returns `true` if the pin code is valid, otherwise `false`.
 */
export const isValidPinCode = (pinCode: string = ''): boolean => {
  const pinCodeRegExp = /^[1-9][0-9]{5}$/;
  return pinCodeRegExp.test(pinCode);
};
