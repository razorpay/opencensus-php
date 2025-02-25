import { arrayToCsv } from "./arrayToCsv";

/**
 * Converts a two-dimensional array of any type to a CSV data URL.
 * This can be used to download the CSV as a file from the browser.
 *
 * @template T - The type of elements in the array.
 * @param {T[][]} array - A two-dimensional array where each sub-array represents a row.
 * @returns {string} - The resulting CSV data URL.
 * 
 * @example
 * const data = [
 *   ['Name', 'Age', 'Country'],
 *   ['John', 25, 'USA'],
 *   ['Alice', 30, 'UK']
 * ];
 * const result = arrayToCsvDataUrl(data);
 * console.log(result);
 * // Output: "data:text/csv;utf-8,Name,Age,Country%0AJohn,25,USA%0AAlice,30,UK"
 */
export const arrayToCsvDataUrl = <T>(array: T[][]): string => {
  return 'data:text/csv;utf-8,' + encodeURIComponent(arrayToCsv(array));
};
