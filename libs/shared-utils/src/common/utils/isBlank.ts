import { isNone } from "./isNone";

/**
 * Checks if a given value is blank. It returns true if the value is:
 * - An object with no keys
 * - A string with only whitespace characters
 * - Undefined or null
 * 
 * @param {unknown} value - The value to check.
 * @returns {boolean} - True if the value is blank, false otherwise.
 */
export function isBlank(value: unknown): boolean {
  if (value !== null && typeof value === 'object') {
    return !Object.keys(value).length;
  }
  if (typeof value === 'string') {
    value = value.trim();
    return !value;
  }
  return isNone(value);
}
