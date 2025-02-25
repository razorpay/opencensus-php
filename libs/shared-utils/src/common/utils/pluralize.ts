/**
 * Adds an 's' to the string if the length is greater than 1 to form a plural.
 *
 * @example
 * pluralize('cat', 2); // Output: 'cats'
 * pluralize('dog', 1); // Output: 'dog'
 *
 * @param {string} str - The string to potentially pluralize.
 * @param {number} length - The length or count to determine if pluralization is needed.
 * @returns {string} - The pluralized string if length is greater than 1, otherwise the original string.
 */
export const pluralize = (str: string, length: number): string => {
  return length > 1 ? `${str}s` : str;
};
