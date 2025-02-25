/**
 * Converts a sentence to title case, capitalizing the first letter of each word.
 * Delimiters are spaces and underscores.
 * 
 * @param {string} sentence - The sentence to convert to title case.
 * @returns {string} - The sentence in title case.
 * 
 * @example
 * const result = toTitleCase('hello world_this is a test');
 * // returns "Hello World This Is A Test"
 */
export const toTitleCase = (sentence: string | null | undefined): string => {
  return (sentence || '')
    .split(/\s+|_/)
    .map((word) => word.charAt(0).toUpperCase() + word.substr(1).toLowerCase())
    .join(' ');
}
