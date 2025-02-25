/**
 * Checks if the object is empty or all its values are empty strings.
 *
 * @param {Record<string, any>} obj - The object to check.
 * @returns {boolean} - Returns `true` if the object is empty or all values are empty strings, otherwise `false`.
 */
export function checkIsObjectEmpty(obj: Record<string, any>): boolean {
  for (var key in obj) {
    if (obj.hasOwnProperty(key) && obj[key] !== '') return false;
  }
  return true;
}
