/**
 * Get human readable file size
 * @param {number} bytes - The file size in bytes (in Binary prefixes).
 * @returns {string} - A human-readable string representing the file size.
 * 
 * @example
 * // returns "1.50 MB"
 * readableFileSize(1572864);
 * 
 * @example
 * // returns "0 bytes"
 * readableFileSize(0);
 */
export const readableFileSize = (bytes: number): string => {
  const sizes = ['bytes', 'KB', 'MB', 'GB', 'TB', 'PB'];

  if (!bytes) return `0 bytes`;
  var e = Math.floor(Math.log(bytes) / Math.log(1024));
  return `${(bytes / 1024 ** e).toFixed(2)} ${sizes[e]}`;
};
