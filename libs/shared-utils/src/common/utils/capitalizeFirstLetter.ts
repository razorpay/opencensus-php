/**
 * Capitalizes the first letter of a given string.
 * 
 * This function takes a string and returns a new string where the first character 
 * is converted to uppercase, and the rest of the string remains unchanged.
 * 
 * @param str - The input string to capitalize.
 * @returns A new string with the first letter capitalized.
 * 
 * @example
 * // Example 1: Capitalizing a simple word
 * const result = capitalizeFirstLetter('hello');
 * console.log(result); // Output: 'Hello'
 */
export const capitalizeFirstLetter = (str: string): string => {
    if (str.length === 0) return str;
    return str.charAt(0).toUpperCase() + str.slice(1);
  };
  