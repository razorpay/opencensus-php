/**
 * Creates a validator function based on a provided truthy function and a default message.
 * The validator returns `undefined` if the value passes the truthy function, otherwise returns the error message.
 *
 * @param {Function} truthyFn - The function that determines if the value is valid.
 * @param {string} defaultMessage - The default error message if the validation fails.
 * @returns {Function} - A function that takes an optional message and returns a validator function.
 *
 * @example
 * const isRequired = makeValidator(value => !!value, 'This field is required');
 * const validate = isRequired(); // Using default message
 * const result = validate(''); // returns 'This field is required'
 *
 * @example
 * const isNumber = makeValidator(value => !isNaN(value), 'Must be a number');
 * const validateWithCustomMessage = isNumber('Custom error message');
 * const result = validateWithCustomMessage('abc'); // returns 'Custom error message'
 */
export const makeValidator =
  (truthyFn: (value: any) => boolean, defaultMessage: string) =>
  (message: string = defaultMessage) =>
  (value: any): string | undefined =>
    truthyFn(value) ? undefined : message;
