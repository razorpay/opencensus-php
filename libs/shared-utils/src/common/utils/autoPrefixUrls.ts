/**
 * Adds 'http://' to the URL if 'http' or 'https' is not present.
 * Ensures that the URL is properly prefixed to avoid issues with relative paths.
 *
 * @param {string} url - The URL to check and possibly prefix.
 * @returns {string} - The modified URL with 'http://' if it was missing, or the original URL.
 *
 * @example
 * // Example 1: If the URL is missing a protocol
 * // Input: 'example.com'
 * // Output: 'http://example.com'
 *
 * // Example 2: If the URL already has 'https://'
 * // Input: 'https://example.com'
 * // Output: 'https://example.com'
 */
export const autoPrefixUrls = (url: string, shouldUseHttpsProtocol: boolean = false): string => {
  const regex = /^https?:\/\//i;
  let tempUrl;
  if (!url || url.length === 0) {
    return url;
  }

  tempUrl = url.toLowerCase();

  if (!regex.test(tempUrl)) {
    const protocol = !shouldUseHttpsProtocol ? 'http://' : 'https://';
    url = protocol + url;
  }
  return url;
};
