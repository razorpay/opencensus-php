/**
 * Returns the file type icon based on the file extension.
 * If the file extension matches one of the available file types, 
 * it appends '-new' to the extension; otherwise, it returns 'misc'.
 *
 * @param {string} fileName - The name of the file, e.g., "document.pdf".
 * @returns {string} - The file type icon or 'misc' if not found.
 *
 * @example
 * const icon = getFileTypeIcon('document.pdf');
 * console.log(icon); // Output: 'pdf-new'
 *
 * const iconUnknown = getFileTypeIcon('document.unknown');
 * console.log(iconUnknown); // Output: 'misc'
 */
export function getFileTypeIcon(fileName: string): string {
  const avlblFileTypeIcons = ['pdf', 'jpg', 'png', 'csv', 'xlsx', 'mp4', 'mp3'];
  const fileType = fileName.split('.').pop() || '';

  return avlblFileTypeIcons.includes(fileType) ? `${fileType}-new` : 'misc';
}
