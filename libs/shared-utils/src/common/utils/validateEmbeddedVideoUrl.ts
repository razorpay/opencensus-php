/**
 * Validates if the provided URL is a valid embedded video URL from supported platforms (YouTube, Vimeo).
 * 
 * @param {string} url - The URL to validate.
 * @returns {boolean} - Returns true if the URL is valid, false otherwise.
 * 
 * @example
 * const isValid = validateEmbeddedVideoUrl('https://www.youtube.com/watch?v=dQw4w9WgXcQ');
 * // returns true
 * 
 * const isInvalid = validateEmbeddedVideoUrl('https://example.com');
 * // returns false
 * 
 * @note This function may require updates to accommodate new URL shorteners or changes in the platforms' URL structures.
 */
export const validateEmbeddedVideoUrl = (url: string | null | undefined): boolean => {
  url = url || '';
  const urlRegExp = /^(http(s)?:\/\/)((w){3}\.)?(vimeo\.com|youtu\.be|youtube\.com)\/([\w-_\/]+)([\?].*)?$/i;

  return urlRegExp.test(url);
};
