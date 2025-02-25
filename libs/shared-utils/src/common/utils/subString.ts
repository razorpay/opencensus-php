/**
 * Truncates a string to a specified length and appends "..." if it exceeds that length.
 * 
 * @param {string} str - The string to truncate.
 * @param {number} length - The maximum length of the string before truncation.
 * @returns {string} - The truncated string, or the original string if it is shorter than the specified length.
 * 
 * @example
 * const result = subString('Hello, world!', 5);
 * // returns "Hello ..."
 */
export const subString = (str: string | null | undefined, length: number): string => {
  if (!str) {
    return str || ''; // Return an empty string if str is null or undefined
  }

  if (str.length > length) {
    return `${str.substr(0, length)} ...`;
  } else {
    return str;
  }
};
