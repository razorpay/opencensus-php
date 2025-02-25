/**
 * Converts a Unix timestamp to a human-readable date format.
 * 
 * @param {number} unixTimeStamp - The Unix timestamp to convert.
 * @returns {string} - The formatted date string in 'Month Day, Year' format.
 */
export const convertUnixToDate = (unixTimeStamp: number): string => {
  const date = new Date(unixTimeStamp * 1000).toLocaleString('en-US', {
    month: 'long',
    day: 'numeric',
    year: 'numeric',
  });
  return date;
};
