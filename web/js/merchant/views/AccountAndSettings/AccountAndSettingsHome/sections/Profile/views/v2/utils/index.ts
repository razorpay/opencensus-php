/**
 * Returns the initials of a name
 * @param {string} name - The name to get initials from
 * @returns {string} - The initials in uppercase
 */
export const getInitials = (name: string): string => {
  const nameParts = name.split(' ');
  const partsSize = nameParts.length;
  if (partsSize === 0) {
    return '';
  }
  let initials = nameParts[0].substring(0, 1);
  if (partsSize > 1) {
    initials += nameParts[partsSize - 1].substring(0, 1);
  }
  return initials.toUpperCase();
};
