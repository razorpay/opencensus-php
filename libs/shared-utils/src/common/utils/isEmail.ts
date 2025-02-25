/**
 * Checks if the given string is a valid email address format.
 *
 * @param {string} email - The email address to validate.
 * @returns {boolean} - Returns true if the email is valid, false otherwise.
 */
export const isEmail = (email: string = ''): boolean => {
  const emailRegExp = new RegExp(
    /^$|[a-zA-Z0-9.!#$%&’*+/=?^_`{|}~-]+@[a-zA-Z0-9-]+(\.[a-zA-Z0-9-]+)+$/
  );
  return emailRegExp.test(email);
};
