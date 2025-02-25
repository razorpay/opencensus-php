/**
 * Truncates a string to a specified length and appends "..." if it exceeds that length.
 * Defaults to a length of 24 characters.
 * 
 * @param {string} string - The string to truncate.
 * @param {number} [length=24] - The maximum length of the string before truncation.
 * @returns {string} - The truncated string, or the original string if it is shorter than the specified length.
 * 
 * @example
 * const result = truncatedString('This is a long string that needs to be truncated.', 20);
 * // returns "This is a long str..."
 */
export const truncatedString = (string: string | null | undefined, length: number = 24): string => {
  if (string && string.length > length) {
    return `${string.substring(0, length)}...`;
  }
  return string || ''; // Return an empty string if string is null or undefined
};
