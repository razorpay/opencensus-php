/**
 * Converts a string to camelCase format.
 * E.g., "Some RandomString" -> "someRandomString"
 *
 * @param {string} str - The input string to convert to camelCase.
 * @returns {string} - The camelCase version of the input string.
 *
 * @example
 * const result = camelize("Some RandomString");
 * console.log(result); 
 * // Output: "someRandomString"
 */
export const camelize = (str: string): string => {
  if (typeof str !== 'string') return str;
  return str
    .replace(/(?:^\w|[A-Z]|\b\w)/g, (word, index) =>
      index === 0 ? word.toLowerCase() : word.toUpperCase(),
    )
    .replace(/\s+/g, '');
};
