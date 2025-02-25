/**
 * Truncates a string to a specified length and appends "..." if it exceeds that length.
 * 
 * @param {string} str - The string to truncate.
 * @param {number} num - The maximum length of the string before truncation.
 * @returns {string} - The truncated string, or the original string if it is shorter than or equal to the specified length.
 * 
 * @example
 * const result = truncateString('This is a long string that needs to be truncated.', 20);
 * // returns "This is a long str..."
 */
export const truncateString = (str: string | null | undefined, num: number): string => {
    return (str && str.length > num) ? `${str.slice(0, num)}...` : str || '';
  };
  