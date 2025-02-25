/**
 * Validates a URL without requiring http/https or www.
 *
 * @param {string} url - The URL to validate.
 * @returns {boolean} - Returns `true` if the URL is valid, otherwise `false`.
 */
export const isUrlLenient = (url: string = ''): boolean => {
  const urlRegExp = /^(https?:\/\/)?[\w.-]+(?:\.[\w\.-]+)+[\w\-\._~:/?#[\]@!\$&'\(\)\*\+,;=.]+$/;
  return urlRegExp.test(url);
};
