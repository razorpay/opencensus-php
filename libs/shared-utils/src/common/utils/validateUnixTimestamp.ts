import moment from 'moment';

/**
 * Validates a Unix timestamp and checks if it corresponds to a valid date.
 * 
 * @param {string} timestamp - The Unix timestamp to validate.
 * @returns {number | null} - Returns the valid Unix timestamp as a number if valid, or null if invalid.
 * 
 * @example
 * const validTimestamp = validateUnixTimestamp('1633072800');
 * // returns 1633072800 (valid)
 * 
 * const invalidTimestamp = validateUnixTimestamp('invalid_timestamp');
 * // returns null
 */
export const validateUnixTimestamp = (timestamp: string): number | null => {
  const unixTime = parseInt(timestamp, 10);
  if (moment.unix(unixTime).isValid()) {
    return unixTime; // Return the valid Unix timestamp
  }
  return null; // Return null if invalid
};
