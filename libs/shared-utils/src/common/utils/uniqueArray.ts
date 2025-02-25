/**
 * Returns an array with non-duplicate entries.
 * 
 * @param {T[]} arr - The array to filter for unique entries.
 * @returns {T[]} - An array containing only unique entries.
 * 
 * @example
 * const result = uniqueArray([1, 2, 2, 3, 4, 4, 5]);
 * // returns [1, 2, 3, 4, 5]
 */
export function uniqueArray<T>(arr: T[] | null | undefined): T[] {
  if (!arr || arr.length === 0) {
    return [];
  }

  const map: Record<string, boolean> = {};

  return arr.filter((item) => {
    const key = JSON.stringify(item); // Convert item to a string for use as a key
    if (!map[key]) {
      map[key] = true;
      return true;
    }
    return false;
  });
}
