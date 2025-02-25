/**
 * Extracts and filters error messages from an API response.
 * Filters out messages that contain "status code".
 *
 * @param {string[]} errors - An array of error messages from the API response.
 * @returns {string[] | string} - Filtered error messages as an array of strings, 
 * a single string error message, or null if no valid errors are found.
 *
 * @example
 * const errors = ['Invalid request', 'status code 400'];
 * const errorMessage = getErrorMessageFromResponse(errors);
 * console.log(errorMessage); // Output: ['Invalid request']
 */
export function getErrorMessageFromResponse(errors: string[]): string[] | string {
  let err: string[] = [];

  if (Array.isArray(errors)) {
    errors.forEach((e) => {
      if (e && !e.toLowerCase().includes('status code')) {
        err.push(e);
      }
    });

  }

  // If there are no valid errors, return a default error message
  if (!Boolean(err.length)) {
    return 'Some network error has occurred';
  }

  return err;
}
