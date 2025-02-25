/**
 * Converts a two-dimensional array of any type to a CSV string.
 * Each sub-array represents a row, and its elements are joined by commas.
 * 
 * @template T - The type of elements in the array.
 * @param {T[][]} array - A two-dimensional array where each sub-array represents a row.
 * @returns {string} - The resulting CSV string.
*
 * @example
 * const data = [
 *   ['Name', 'Age', 'Country'],
 *   ['John', 25, 'USA'],
 *   ['Alice', 30, 'UK']
 * ];
 * const result = arrayToCsv(data);
 * console.log(result);
 * // Output: "Name,Age,Country\nJohn,25,USA\nAlice,30,UK"
 */
export const arrayToCsv = <T>(array: T[][]): string => {
  return array
    .reduce<string[]>((result, item) => {
      return result.concat(Array.isArray(item) ? item.join(',') : String(item));
    }, [])
    .join('\n');
};
