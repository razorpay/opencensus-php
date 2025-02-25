import { flattenObject } from "./flattenObject";

/**
 * Creates a query string from an object, using | as the separator instead of &.
 * 
 * @param {Record<string, any>} params - The parameters to convert to a query string.
 * @returns {string} - The resulting query string.
 * 
 * @example
 * const params = {
 *   name: 'John Doe',
 *   age: 30,
 *   details: {
 *     country: 'US',
 *     city: 'New York'
 *   }
 * };
 * 
 * const queryString = stringifyQueryParamsWithPipe(params);
 * // returns "name=John Doe|age=30|details_country=US|details_city=New York"
 */
export const stringifyQueryParamsWithPipe = (params: Record<string, any>): string => {
  if (!params) return '';

  params = flattenObject(params, '_');

  return JSON.stringify(params)
    .replace(/:/g, '=') // Replace : with =
    .replace(/{/g, '') // Remove {
    .replace(/}/g, '') // Remove }
    .replace(/"/g, '') // Remove "
    .replace(/,/g, '|'); // Replace , with |
};
