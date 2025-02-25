/**
 * Validates a slug to ensure it meets the required format (alphanumeric with hyphens).
 * 
 * @param {string} val - The slug to validate.
 * @returns {boolean | undefined} - Returns true if valid, false if invalid, or undefined if the value is not provided.
 * 
 * @example
 * const isValid = validateSlug('my-valid-slug');
 * // returns true
 * 
 * const isInvalid = validateSlug('invalid_slug!');
 * // returns false
 * 
 * const noValue = validateSlug('');
 * // returns undefined
 */
export function validateSlug(val: string | null | undefined): boolean | undefined {
  if (!val) {
    return undefined; // Return undefined if the value is not provided
  }

  const slugRegex = /^[A-Za-z0-9]+(?:-[A-Za-z0-9]+)*$/;

  return slugRegex.test(val); // Return true if valid, false if invalid
}
