/**
 * Returns whether or not a GSTIN is valid.
 * 
 * GSTIN format:
 *  - 1st character: {0, 1, 2, 3}
 *  - 2nd character: {0...9}
 *  - 3rd - 7th characters: alphabets
 *  - 8th - 11th characters: numbers
 *  - 12th character: alphabet
 *  - 13th character: number
 *  - 14th character: "Z"
 *  - 15th character: alphabet or number
 *
 * @param {string} gstin - The GSTIN to validate.
 * @returns {boolean} - Returns `true` if the GSTIN is valid, otherwise `false`.
 */
export const isValidGSTIN = (gstin: string): boolean => {
  // If GSTIN is not provided or it isn't 15 characters long, it is invalid.
  if (!gstin || gstin.length !== 15) {
    return false;
  }

  const regex = /^[0123][0-9][a-z]{5}[0-9]{4}[a-z][0-9][a-z0-9][a-z0-9]$/gi;
  return regex.test(gstin);
};
