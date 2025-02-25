/**
 * Creates a validation function that checks if a string has a specified length.
 * 
 * @param {number} length - The required length of the string.
 * @param {string} [message=''] - The error message to return if validation fails.
 * @returns {(value: string) => string} - A function that takes a string and returns an error message if the length is not valid.
 * 
 * @example
 * const validateLength = validateStringLength(5);
 * const result = validateLength('abc');
 * // returns 'Must be 5 characters'
 * 
 * const validResult = validateLength('abcde');
 * // returns ''
 */
export const validateStringLength = (length: number, message: string = ''): ((value: string) => string) => {
  message = message || `Must be ${length} characters`;

  return (value: string = '') => {
    return value.trim().length !== length ? message : '';
  };
};
