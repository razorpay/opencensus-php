/**
 * Checks if a string contains only alphabets and numbers.
 *
 * @param {string} input - The input string to be checked.
 * @returns {boolean} - Returns `true` if the string contains only alphabets and numbers, otherwise `false`.
 */
export const isStringAlphabetAndNumberOnly = (input: string): boolean => !/^[A-Za-z0-9]*$/.test(input);
