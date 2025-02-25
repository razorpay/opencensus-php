/**
 * Converts the location or provided URL into an object of query parameters.
 * Typically used to extract query parameters from the URL.
 *
 * @param {string} [url=document.location.hash] - The URL or hash string to parse.
 * @returns {Record<string, string | undefined>} - An object containing key-value pairs of the query parameters.
 *
 * @example
 * const url = "https://example.com?param1=value1&param2=value2";
 * const params = getURLQueryParams(url);
 * console.log(params); // Output: { param1: "value1", param2: "value2" }
 */
export const getURLQueryParams = (url: string = document.location.hash): Record<string, string | undefined> => {
  const search = url.split('?')[1];
  let params: Record<string, string | undefined> = {};

  if (search) {
    /* split using '&' as separator
    and get the key-value pairs for query params. */
    params = search.split('&').reduce((prev, curr) => {
      const [key, value] = curr.split('=');
      prev[key] = value;
      return prev;
    }, {} as Record<string, string | undefined>);
  }

  return params;
};
