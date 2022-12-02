/**
 * @param {string} email test@razorpay.com
 * @returns {string} t**t@razorpay.com
 */
export function getMaskedEmail(email = '') {
  if (email) {
    const [name, domain] = email.split('@');
    const nameLen = name.length;
    const nameLastIndex = nameLen - 1;

    return `${name[0]}${name.substring(1, nameLastIndex).replace(/[A-Za-z]/g, '*')}${
      name[nameLastIndex]
    }@${domain}`;
  }

  return '';
}

/**
 * @param {string} phoneNo +919876543210
 * @returns {string} +9198******10
 */
export function getMaskedContact(phoneNo = '') {
  if (phoneNo) {
    const numLen = phoneNo.length;
    const numSecLastIndex = numLen - 2;
    const startingNonMaskedNumLastIndex = phoneNo.includes('+91') ? 5 : 2;

    return `${phoneNo.substring(0, startingNonMaskedNumLastIndex)}${phoneNo
      .substring(startingNonMaskedNumLastIndex, numSecLastIndex)
      .replace(/[0-9]/g, '*')}${phoneNo.substring(numSecLastIndex)}`;
  }

  return '';
}
