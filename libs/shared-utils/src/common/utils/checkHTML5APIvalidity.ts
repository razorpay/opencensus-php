const htmlApiList = ['URLSearchParams'];

/**
 * Checks the validity of HTML5 APIs listed in `htmlApiList`.
 *
 * @returns {string | undefined} - Returns the first invalid API name if found, otherwise `undefined`.
 *
 * @example
 * const invalidApi = checkHTML5APIvalidity();
 * console.log(invalidApi);
 * // Output: "URLSearchParams" (if not supported) or undefined (if all APIs are valid)
 */
export const checkHTML5APIvalidity = (): string | undefined =>
  htmlApiList.find((apiName) => !(apiName in window));
