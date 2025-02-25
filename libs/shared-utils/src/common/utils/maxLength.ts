/**
 * Validates if a string's length exceeds a specified maximum length.
 * 
 * Example:
 * ```ts
 * const validateLength = maxLength(5, 'Too long!');
 * console.log(validateLength('abcdef')); // Output: 'Too long!'
 * console.log(validateLength('abc'));    // Output: ''
 * ```
 * 
 * @param {number} length - The maximum allowed length.
 * @param {string} [message=''] - The optional custom message to return when validation fails.
 * @returns {(value?: string) => string} - A function that takes a string and returns the validation message if invalid.
 */
export const maxLength = (length: number, message: string = ''): (value?: string) => string => {
  message = message || `Enter up to ${length} characters`;

  return (value: string = '') => {
    return value.trim().length > length ? message : '';
  };
};
