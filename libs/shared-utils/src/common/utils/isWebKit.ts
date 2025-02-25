/**
 * Checks if the browser is WebKit-based.
 *
 * This function determines whether the browser is WebKit-based by checking
 * the presence of the `-webkit-text-security` property in the computed styles
 * of the document's root element.
 *
 * It ensures the code only runs in a browser environment by verifying that
 * `window` and `document` are defined.
 *
 * @returns A boolean value indicating whether the browser is WebKit-based.
 *
 * @example
 * // Example: Check if the current browser is WebKit-based
 * const result = isWebkit;
 * console.log(result); // Output: true or false based on the browser
 */
export const isWebkit: boolean =
  typeof window !== 'undefined' &&
  Boolean(
    window
      ?.getComputedStyle?.(document?.documentElement)
      ?.getPropertyValue?.('-webkit-text-security') !== '',
  )
    ? true
    : false;
