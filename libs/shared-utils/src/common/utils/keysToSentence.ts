import { toTitleCase } from "./toTitleCase";

/**
 * Converts object keys into a sentence.
 * Returns a comma-separated sentence ending with "is" or "are" based on plurality.
 *
 * @param {Record<string, unknown>} keys - The object with keys to be converted into a sentence.
 * @returns {string | undefined} - Returns a formatted sentence or undefined if the input is not an object or is empty.
 */
export function keysToSentence(keys: Record<string, unknown>): string | undefined {
  if (typeof keys !== 'object' || !Object.keys(keys).length) {
    return;
  }

  let joiner: string | undefined;

  const formattedKeys = Object.keys(keys).map((key) => {
    if (key[key.length - 1] === 's') {
      // plural term
      joiner = 'are';
    }

    return toTitleCase(key);
  });

  joiner = joiner || (formattedKeys.length > 1 ? 'are' : 'is');

  let sentence = formattedKeys[0];

  for (let i = 1; i < formattedKeys.length; i++) {
    if (i === formattedKeys.length - 1) {
      sentence = sentence + ' and ' + formattedKeys[i];
    } else {
      sentence = sentence + ', ' + formattedKeys[i];
    }
  }

  return sentence + ' ' + joiner;
}
