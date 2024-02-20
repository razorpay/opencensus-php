/**
 * Checks if the provided string value contains a hardcoded dial code.
 * It uses a regular expression (`dialCodePattern`) to identify strings that match the general pattern of a dial code,
 * which typically starts with a '+' followed by 1 to 3 digits. This function aims to encourage the dynamic retrieval or
 * configuration of dial codes, rather than embedding them directly in the code, to support internationalization and
 * localization practices.
 *
 * @param {string} value - The string value to be checked for hardcoded dial codes.
 * @returns {string|false} A warning message if the value contains a hardcoded dial code; otherwise, returns `false`.
 */
const dialCodePattern = /\+\d{1,3}/;
function isDialCodeHardCoded(value) {
  if (dialCodePattern.test(value)) {
    return 'Avoid using hardcoded dial codes in your code, as they may not be suitable for all countries.';
  }

  return false;
}

module.exports = isDialCodeHardCoded;
