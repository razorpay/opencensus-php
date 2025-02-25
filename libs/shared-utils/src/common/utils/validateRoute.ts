import { match } from 'path-to-regexp';

/**
 * Removes the content within parentheses from the provided URL or path.
 * 
 * This function uses a regular expression to match and remove all text that is enclosed 
 * within parentheses in the provided URL or path string.
 * 
 * @param {string} url - The URL or path string from which to remove the content within parentheses.
 * @returns {string} A string with content inside parentheses removed.
 * 
 * @example
 * // Example 1: Remove content inside parentheses
 * const result = removeParenthesesContent('/path/to/resource(param)');
 * console.log(result); // Output: "/path/to/resource"
 * 
 * @example
 * // Example 2: No parentheses present
 * const result = removeParenthesesContent('/path/to/resource');
 * console.log(result); // Output: "/path/to/resource" (unchanged)
 */
const removeParenthesesContent = (url: string): string => {
  // Define the regex pattern to match content within parentheses
  const pattern = /\([^)]+\)/g;

  // Use replace() to remove the matched content within parentheses
  return url.replace(pattern, '');
};

/**
 * @deprecated Regex paths are not supported in React Router v6 and later. This function 
 * is used to support regex in URL paths for legacy code or older versions of React Router.
 * 
 * This function validates a route path that may contain regex patterns. It checks whether 
 * the given `pathname` matches the `refRoutePath` and returns the cleaned path (without 
 * parentheses) if there's a match, otherwise, it returns the original `refRoutePath`.
 * 
 * @param {string} refRoutePath - The reference route path string that may include regex and validations.
 * @param {string} pathname - The current pathname from React Router to validate against the reference route path.
 * @returns {string} The cleaned route path if a match is found, otherwise the original reference route path.
 * 
 * @example
 * // Example 1: Validating a route path with parentheses
 * const result = validateRoute('/path/to/resource(param)', '/path/to/resource');
 * console.log(result); // Output: "/path/to/resource"
 * 
 * @example
 * // Example 2: No match is found
 * const result = validateRoute('/path/to/other', '/path/to/resource');
 * console.log(result); // Output: "/path/to/other"
 * 
 * @example
 * // Example 3: Complex route with multiple parentheses and regex
 * const result = validateRoute('/users/:userId(profile)/details(transaction)', '/users/123/details');
 * console.log(result); 
 * // Output: "/users/:userId/details"
 * // In this case, both "(profile)" and "(transaction)" are removed, and the matched path "/users/123/details" is validated.
 */
export const validateRoute = (refRoutePath: string, pathname: string): string => {
  // Use path-to-regexp to match the provided pathname with the reference route path
  const isMatched = match(refRoutePath, {
    decode: decodeURIComponent,
  })(pathname) as { path: string };

  // Clean the reference route path by removing content within parentheses
  const cleanedRoutePath = removeParenthesesContent(refRoutePath);

  // If the match is found, return the cleaned route path, otherwise return the original path
  return Boolean(isMatched) ? cleanedRoutePath : refRoutePath;
};
