/**
 * Converts an object to a URL query string.
 * Ignores undefined, null, and empty string values.
 * Note: This function does not handle nested objects.
 * 
 * @param {Record<string, any>} params - The parameters to convert to a query string.
 * @returns {string} - The resulting query string.
 * 
 * @example
 * const params = {
 *   name: 'John Doe',
 *   age: 30,
 *   country: null,
 *   city: '',
 *   zip: '12345'
 * };
 * 
 * const queryString = stringifyQueryParams(params);
 * // returns "?name=John Doe&age=30&zip=12345"
 */
export const stringifyQueryParams = (params: Record<string, any>): string => {
  let queryString;
  let queryElements: string[] = [];

  for (let key in params) {
    if (params.hasOwnProperty(key) && params[key] != null && params[key] !== '') {
      if (Array.isArray(params[key])) {
        params[key].forEach((value: any) => {
          if (value != null && value !== '') {
            queryElements.push(`${encodeURIComponent(key)}=${encodeURIComponent(value)}`);
          }
        });
      } else {
        queryElements.push(`${encodeURIComponent(key)}=${encodeURIComponent(params[key])}`);
      }
    }
  }

  queryString = '?' + queryElements.join('&');
  return queryString;
};
