/**
 * Updates the file extension of a given URL.
 * 
 * @param {string} fileUrl - The original file URL.
 * @param {string} extension - The new file extension (including the dot, e.g., '.jpg').
 * @returns {string | undefined} - The updated file URL with the new extension, or undefined if the original URL is invalid.
 * 
 * @example
 * const updatedUrl = updateExtension('https://example.com/image.png', '.jpg');
 * // returns 'https://example.com/image.jpg'
 */
export const updateExtension = (fileUrl: string | null | undefined, extension: string): string | undefined => {
  if (!fileUrl) {
    return undefined;
  }

  return fileUrl.substr(0, fileUrl.lastIndexOf('.')) + extension;
};
