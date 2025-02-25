/**
 * Checks if the given URL is a deep link.
 * 
 * @param {string} url - The URL to check.
 * @returns {boolean} - Returns true if the URL is a deep link, false otherwise.
 */
export const isDeepLink = (url: string = ''): boolean => {
  const urlRegExp = /[A-Za-z]+:\/\/.*/;
  return urlRegExp.test(url);
};
