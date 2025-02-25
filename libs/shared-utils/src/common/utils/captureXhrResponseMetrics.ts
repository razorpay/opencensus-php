interface Response {
  status: number;
  request: {
    responseURL: string;
  };
}

const metricType = {
  RESPONSE_TIME: 'resources.responseTime',
  RESPONSE_CODE: 'resources.responseCode',
};

/**
 * Extracts the location URL from the provided URL or the current location.
 *
 * This function extracts the relevant part of the URL, trimming hash routes (e.g., `#/access/signin`)
 * or internal modules (e.g., `/app/payment/some_module/payment_id`).
 *
 * @param {string} [url] - The URL to extract from. If not provided, it uses the current window location.
 * @returns {string} The trimmed URL.
 */
const getLocationUrl = (url?: string): string => {
  if (!url) {
    if (location.hash) {
      // If there's a hash route, use it and trim leading #/
      url = location.hash.replace(/#\//, '');
    } else {
      // Otherwise, use the pathname and trim /app/ and internal modules
      url = location.pathname.replace('/app/', '').split('/')[0];
    }
  }

  return url.split('?')[0]; // Return the URL without query parameters
};

/**
 * Captures and pushes the response code metric to the `rzpQMetrics` array.
 *
 * This function records the HTTP response code for an XMLHttpRequest and sends it as a metric,
 * including details such as the request type (`xmlhttprequest`), the URL, the route, and the response status code.
 *
 * @param {Response} response - The XMLHttpRequest response object.
 */
const captureResponseCode = (response: Response): void => {
  if (response.status) {
    window.rzpQMetrics.push({
      type: 'metrics',
      properties: {
        name: metricType.RESPONSE_CODE,
        labels: [
          {
            type: 'xmlhttprequest',
            url: getLocationUrl(response.request.responseURL),
            route: getLocationUrl(),
            code: String(response.status),
          },
        ],
      },
    });
  }
};

/**
 * Captures XMLHttpRequest response metrics, including the response code, and pushes them to the metrics queue.
 *
 * This function checks if the `rzpQMetrics` array exists on the window object before capturing response metrics.
 *
 * @param {Response} response - The XMLHttpRequest response object.
 */
export const captureXhrResponseMetrics = (response: Response): void => {
  if (window.rzpQMetrics) {
    captureResponseCode(response);
  }
};
