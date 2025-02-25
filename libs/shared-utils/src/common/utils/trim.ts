/**
 * Removes all whitespace characters from a given string.
 * 
 * @param {string} str - The string from which to remove whitespace.
 * @returns {string} - The string with all whitespace removed.
 * 
 * @example
 * const result = trim('  Hello  World  ');
 * // returns "HelloWorld"
 */
export const trim = (str: string | null | undefined): string => {
  return (str || '').replace(/\s+/g, '');
};
