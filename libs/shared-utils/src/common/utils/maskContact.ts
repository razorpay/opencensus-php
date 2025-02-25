/**
 * Masks a contact number by replacing the middle digits with asterisks.
 * 
 * Example:
 * ```ts
 * maskContact('+919876543210', true); // Output: +9198******10
 * maskContact('9876543210', true);    // Output: 98******10
 * ```
 * 
 * If the contact starts with '+91', it will keep the first 5 characters visible, 
 * otherwise it keeps the first 2 characters.
 * 
 * @param {string | undefined | null} contact - The contact number to be masked.
 * @param {boolean} shouldMask - Determines whether masking should be applied. Defaults to false.
 * 
 * @returns {string} - The masked contact number, or the original contact if `shouldMask` is false.
 */
export const maskContact = (contact: string | undefined | null = '', shouldMask = false): string => {
  // Handle empty or non-string values for contact
  if (typeof contact !== 'string' || contact.trim() === '') {
    return '';
  }

  // Execute the function only if shouldMask is true
  if (shouldMask) {
    const numLen = contact.length;
    const numSecLastIndex = numLen - 2;
    const startingNonMaskedNumLastIndex = contact.startsWith('+91') ? 5 : 2;

    return `${contact.substring(0, startingNonMaskedNumLastIndex)}${contact
      .substring(startingNonMaskedNumLastIndex, numSecLastIndex)
      .replace(/[0-9]/g, '*')}${contact.substring(numSecLastIndex)}`;
  }

  // If shouldMask is false, return the contact string as it is
  return contact;
};
