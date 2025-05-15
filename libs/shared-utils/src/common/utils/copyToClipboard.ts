/**
 * Fallback method to copy text to clipboard by creating a temporary textarea element.
 * This is used for environments where `navigator.clipboard.writeText` might not be available.
 *
 * @param {string} url - The text or URL to be copied to the clipboard.
 */
const copyFallback = (url: string): void => {
  // Fallback for Android WebView where navigator clipboard.write needs write permission.
  const textarea = document.createElement('textarea');
  textarea.textContent = url;
  textarea.style.position = 'fixed'; // Prevent scrolling to the bottom of the page in Microsoft Edge.
  document.body.appendChild(textarea);
  textarea.select();
  document.execCommand('copy');
  document.body.removeChild(textarea);
};

/**
 * Copies the given text or URL to the clipboard using the modern `navigator.clipboard.writeText` API.
 * If the API is not available or fails, it falls back to a more traditional method using a hidden textarea.
 *
 * @param {string} url - The text or URL to be copied to the clipboard.
 */
export const copyToClipboard = (url: string): void => {
  // Check if the clipboard API is available and the context is secure.
  if (navigator?.clipboard?.writeText && window.isSecureContext) {
    try {
      navigator.clipboard.writeText(url).catch((err) => {
        // If there's an error, fallback to the traditional method.
        if (err) {
          copyFallback(url);
        }
      });
    } catch (error) {
      // Fallback if any exception occurs.
      copyFallback(url);
    }
  } else {
    // Fallback to the traditional method if clipboard API is not available.
    copyFallback(url);
  }
};
