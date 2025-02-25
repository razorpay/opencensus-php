/**
 * Regex to allow development URLs like localhost:8000, localhost, and other valid URLs.
 * It doesn't allow strange URLs like ...., etc., which are generally not allowed in URLs.
 *
 * @param {string} url - The URL string to validate.
 * @returns {boolean} - Returns true if the URL is valid, otherwise false.
 *
 * @example
 * const result = flexibleDevUrl('localhost:8000');
 * console.log(result); // true
 *
 * @example
 * const result = flexibleDevUrl('https://example.com');
 * console.log(result); // true
 *
 * @example
 * const result = flexibleDevUrl('....');
 * console.log(result); // false
 */
export const flexibleDevUrl = (url: string = ''): boolean => {
  const urlRegExp = new RegExp(
    /^(http(s?)?:\/\/)?[\w.-]+(\.[\w.-]+)*(:[0-9]+)?\/?(\/[.\w\-#]*)*(\?.*)?$/
  );
  return urlRegExp.test(url);
};
