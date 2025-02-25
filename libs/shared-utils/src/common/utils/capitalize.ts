/**
 * Capitalizes the first letter of the input string and converts the rest to lowercase.
 *
 * @param {string} input - The input string to capitalize.
 * @returns {string} - The capitalized string, or an empty string if input is falsy.
 *
 * @example
 * const result = capitalize("hello world");
 * console.log(result); 
 * // Output: "Hello world"
 */
export const capitalize = (input: string): string =>
  input ? input.charAt(0).toUpperCase() + input.substr(1).toLowerCase() : '';
