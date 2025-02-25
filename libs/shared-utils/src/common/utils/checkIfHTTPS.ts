/**
 * Checks if the provided URL is a secure (HTTPS) URL.
 *
 * @param {string} url - The URL to check.
 * @returns {boolean} - Returns `true` if the URL starts with 'https://', otherwise `false`.
 */
export const checkIfHTTPS = (url: string): boolean => {
  const regex = /^https:\/\//i;
  return regex.test(url);
};
