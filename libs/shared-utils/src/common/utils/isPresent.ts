import { isBlank } from "./isBlank";

/**
 * Checks if the given object is present (not blank).
 *
 * @param {any} obj - The object to check.
 * @returns {boolean} - Returns `true` if the object is present, otherwise `false`.
 */
export function isPresent(obj: any): boolean {
  return !isBlank(obj);
}
