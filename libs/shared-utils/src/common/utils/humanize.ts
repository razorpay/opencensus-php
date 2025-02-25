import { toTitleCase } from "./toTitleCase";

/**
 * Converts an underscored sentence into a human-readable title-cased string.
 *
 * @param {string} sentence - The sentence to humanize, where words are separated by underscores.
 * @returns {string} - The humanized sentence with spaces instead of underscores and title-cased words.
 */
export function humanize(sentence: string): string {
  return toTitleCase(sentence.split('_').join(' '));
}
