/**
 * Validates that a given string meets the minimum length requirement.
 * 
 * @param {number} minLength - The minimum number of characters required.
 * @returns {(value: string) => string | undefined} - A function that checks if the string meets the length requirement.
 * If the string is shorter than the minimum length, returns an error message. Otherwise, returns `undefined`.
 */
export const minLength = (minLength: number) => (value: string): string | undefined => {
  if (typeof value !== 'string' || value.length < minLength) {
    return `Must be ${minLength} characters or more`;
  }
  return undefined;
};
