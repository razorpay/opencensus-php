/**
 * Checks if the provided string is a valid Base64 encoded string.
 * 
 * @param {string} str - The string to validate.
 * @returns {boolean} - Returns true if the string is Base64 encoded, false otherwise.
 */
export const isBase64 = (str: string): boolean => {
  const base64regex = /^([0-9a-zA-Z+/]{4})*(([0-9a-zA-Z+/]{2}==)|([0-9a-zA-Z+/]{3}=))?$/;
  return base64regex.test(str);
};
