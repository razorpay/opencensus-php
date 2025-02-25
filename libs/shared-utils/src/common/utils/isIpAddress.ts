/**
 * Checks if the given string is a valid IP address.
 *
 * @param {string} ipAddress - The string to check.
 * @returns {boolean} - Returns true if the string is a valid IP address, false otherwise.
 */
export const isIpAddress = (ipAddress: string): boolean => {
  const ipRegExp = new RegExp(
    /\b(?:(?:2(?:[0-4][0-9]|5[0-5])|[0-1]?[0-9]?[0-9])\.){3}(?:(?:2([0-4][0-9]|5[0-5])|[0-1]?[0-9]?[0-9]))\b/,
  );
  return ipRegExp.test(ipAddress);
};
