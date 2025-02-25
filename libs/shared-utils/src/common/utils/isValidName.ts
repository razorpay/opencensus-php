/**
 * Validates whether the provided name contains only alphabetic characters and spaces.
 *
 * @param {string} name - The name to validate.
 * @returns {boolean} - Returns `true` if the name is valid, otherwise `false`.
 */
export const isValidName = (name: string = ''): boolean => {
  const nameRegExp = /^[a-zA-Z ]+$/;
  return nameRegExp.test(name);
};
