/**
 * @param {string} contact +919876543210
 * @returns {string} +9198******10
 */
function maskContact(contact: string | undefined | null = '', shouldMask = false): string {
  // Handle empty or non-string values for contact
  if (typeof contact !== 'string' || contact.trim() === '') {
    return '';
  }

  // Execute the function only if shouldMask is true
  if (shouldMask) {
    const numLen = contact.length;
    const numSecLastIndex = numLen - 2;
    const startingNonMaskedNumLastIndex = contact.includes('+91') ? 5 : 2;

    return `${contact.substring(0, startingNonMaskedNumLastIndex)}${contact
      .substring(startingNonMaskedNumLastIndex, numSecLastIndex)
      .replace(/[0-9]/g, '*')}${contact.substring(numSecLastIndex)}`;
  }

  // If shouldMask is false, return the contact string as it is
  return contact;
}

export default maskContact;
