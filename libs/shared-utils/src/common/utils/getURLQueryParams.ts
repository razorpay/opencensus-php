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
    search.split('&').forEach((param) => {
      const [key, value] = param.split('=');
      const decodedKey = decodeURIComponent(key);
      const decodedValue = window?.decodeURIComponent?.(value);

      if (decodedKey in params) {
        params[decodedKey] = `${params[decodedKey]},${decodedValue}`;
      } else {
        params[decodedKey] = decodedValue;
      }
    });
  }

  return params;
};

