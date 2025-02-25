import html2canvas from 'html2canvas';

/**
 * Takes a screenshot of a given HTML node and returns a data URL of the screenshot.
 * 
 * @param {HTMLElement} node - The HTML element to take a screenshot of.
 * @returns {Promise<string>} - A promise that resolves to a data URL of the screenshot.
 * 
 * @example
 * const node = document.getElementById('screenshot-area');
 * takeScreenshot(node).then((dataUrl) => {
 *   console.log(dataUrl); // The base64 data URL of the screenshot
 * });
 */
export const captureScreenshot = (node: HTMLElement | null): Promise<string> => {
  if (!node) {
    return Promise.reject();
  }

  return html2canvas(node).then(canvas => {
    return canvas.toDataURL();
  });
};

