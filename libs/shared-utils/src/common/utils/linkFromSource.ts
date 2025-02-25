/**
 * Checks if a given link is from a specific source.
 * If the source is 'youtube', it also checks for 'youtu' in the link.
 *
 * @param {string} link - The link to check.
 * @param {string} source - The source to check for in the link.
 * @returns {boolean} - Returns true if the link is from the specified source, false otherwise.
 */
export const linkFromSource = (link: string = '', source: string = ''): boolean => {
  let isFromSource = link?.indexOf(source) >= 0;

  if (source === 'youtube') isFromSource = isFromSource || link?.indexOf('youtu') >= 0;

  return isFromSource;
};
