/**
 * Dynamically loads a script into the document.
 *
 * @param {string} src - The source URL of the script to load.
 * @param {object} [options={ async: true }] - Optional settings for the script (e.g., async, defer).
 * @returns {Promise<void>} - A promise that resolves when the script has loaded, or rejects if an error occurs.
 *
 * @example
 * loadScript('https://example.com/script.js')
 *   .then(() => console.log('Script loaded'))
 *   .catch((error) => console.error('Failed to load script', error));
 */
export const loadScript = (
  src: string,
  options: { async?: boolean; defer?: boolean } = { async: true },
): Promise<void> => {
  return new Promise((resolve, reject) => {
    try {
      const script = document.createElement('script');
      script.src = src;
      Object.keys(options).forEach((key) => {
        script[key] = options[key];
      });
      script.onload = () => resolve();
      document.head.appendChild(script);
    } catch (e) {
      reject(e);
    }
  });
};
