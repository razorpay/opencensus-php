/**
 * Converts an array of strings into a human-readable sentence.
 * If there is one item, it returns that item.
 * If there are multiple items, it concatenates them into a sentence with commas and "and".
 *
 * @param {string[]} arr - The array of strings to convert into a sentence.
 * @returns {string} - The formatted sentence.
 *
 * @example
 * const result = arrayToSentence(['apple']);
 * console.log(result);
 * // Output: 'apple'
 *
 * @example
 * const result = arrayToSentence(['apple', 'banana', 'orange']);
 * console.log(result);
 * // Output: 'apple, banana and orange'
 */
export const arrayToSentence = (arr: string[] = []): string => {
  if (arr.length === 1) {
    return arr[0];
  } else {
    return arr.slice(0, arr.length - 1).join(', ') + ' and ' + arr.slice(-1);
  }
};