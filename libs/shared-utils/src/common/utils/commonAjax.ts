import axios, { type AxiosRequestConfig, type AxiosResponse, type AxiosError } from 'axios';
import { getCookie } from './cookies';
import { capturePlaywrightAnalytics } from './playwrightAnalytics';

axios.interceptors.response.use((response) => {
  const { errors } = response?.data || {};
  if (errors?.source === 'aes') {
    document?.body?.dispatchEvent(
      new CustomEvent('LOGIN_AS_MX', {
        bubbles: true,
        detail: {
          message: errors?.[0] || 'Something went wrong',
        },
      }),
    );
  }
  return response;
});

/**
 * TODO: Remove @ts-ignore and validate enforced types.
 */

/**
 * Interface for AJAX request parameters.
 * Extends AxiosRequestConfig and includes optional data for GET requests.
 *
 * @typedef {Object} DashboardAjaxParams
 * @property {Record<string, any>} [data] - Optional data object passed for GET requests.
 * @property {Record<string, string>} [headers] - HTTP headers included in the request.
 * @property {string} [url] - The URL endpoint for the request.
 */
export type DashboardAjaxParams = AxiosRequestConfig;
export type DashboardAjaxRejection = {
  code: number | string;
  errors?: string[];
  [key: string]: any;
};

/**
 * Interface for API responses.
 * Represents the structure of an API response, where `success` is typically a flag to indicate if the call was successful.
 *
 * @typedef {Object} DashboardAjaxResponse
 * @property {boolean} [success] - Indicates the success or failure of the request.
 * @property {any} [key: string] - Additional fields returned by the API response.
 */
type DashboardAjaxResponse<InferredResponse> = InferredResponse & {
  success?: boolean;
};

/**
 * Flattens an object containing parameters into URL-encoded string format for use in query strings.
 * This function is useful for encoding nested objects in the form of URL parameters.
 *
 * @param {Record<string, any>} data - The object to be flattened into query parameters.
 * @returns {string[]} - An array of encoded query parameters in the form `key=value`.
 */
const _flattenSearchParams = (data: Record<string, any>): string[] => {
  const searchParams: string[] = [];

  const flattenObj = (data: Record<string, any>, parentKey?: string) => {
    for (const key in data) {
      if (Object.prototype.hasOwnProperty.call(data, key) && data[key]) {
        if (typeof data[key] === 'object') {
          // Recursively flatten nested objects
          const fullKey = parentKey ? `${parentKey}[${key}]` : key;
          flattenObj(data[key], fullKey);
        } else {
          if (data[key] != null) {
            // Encode each key-value pair
            const paramKey = parentKey ? `${parentKey}[${key}]` : key;
            searchParams.push(`${encodeURIComponent(paramKey)}=${encodeURIComponent(data[key])}`);
          }
        }
      }
    }
  };

  flattenObj(data);
  return searchParams;
};

/**
 * Function to make an AJAX request using Axios with the specified parameters.
 * Handles GET requests with query parameters, sets common headers, and processes the response.
 * This function also handles CSRF tokens and dispatches events on errors.
 *
 * @param {DashboardAjaxParams} [params={}] - The parameters for the AJAX request.
 * @returns {Promise<DashboardAjaxResponse>} - A promise resolving to the API response or rejecting with an error.
 */
export const commonAjax = <InferredResponse>(
  params: DashboardAjaxParams = {},
): Promise<DashboardAjaxResponse<InferredResponse>> => {
  return new Promise((resolve, reject: (x: DashboardAjaxRejection) => void) => {
    // Set common headers including XSRF token and Accept type
    const { headers = {} } = params;
    headers['X-XSRF-TOKEN'] = getCookie('XSRF-TOKEN');
    headers['X-Requested-With'] = 'XMLHttpRequest';
    headers.Accept = 'application/json, text/plain, */*';
    params.headers = headers;

    // Handle GET requests by converting `data` into query parameters
    if ((!params.method || String(params.method).toLowerCase() === 'get') && params.data) {
      params.params = params.data;
      delete params.data;
    }

    // Serialize parameters into URL-encoded query string
    params.paramsSerializer = (currentParams) => {
      const encodedParams = _flattenSearchParams(currentParams);
      return encodedParams.join('&');
    };

    // Make the actual Axios request
    axios(params).then(
      (resp: AxiosResponse<DashboardAjaxResponse<InferredResponse>>) => {
        const { data } = resp;
        // Error code is verified to handle api resolution to HTML doc / raw text.
        // Eg: For downloading csv file for api key-secret comes as raw text.
        // @ts-ignore
        capturePlaywrightAnalytics({ response: resp, params });
        // Check if the API response contains the `success` flag or is raw text
        if (!data.hasOwnProperty('success') || data.success === true) {
          resolve(data);
        } else {
          reject({
            code: 'UNKNOWN_ERROR_CODE',
            ...data,
          });
        }
      },
      (err: AxiosError) => {
        // @ts-ignore
        capturePlaywrightAnalytics({ error: err, params });
        document.body.dispatchEvent(
          new (CustomEvent as any)('REQUEST_ERROR', {
            bubbles: true,
            detail: {
              url: params.url,
              response: err.response,
            },
          }),
        );

        let message = '';

        /**
         * Helper function to retry the AJAX request in case of errors.
         * Used primarily for GET requests.
         */
        function continueAjax() {
          if (!params.method || params.method.toLowerCase() === 'get') {
            axios(params).then(
              ({ data }: AxiosResponse<DashboardAjaxResponse<InferredResponse>>) => {
                if (data.success) {
                  resolve(data);
                } else {
                  reject({
                    code: 'UNKNOWN_ERROR_CODE',
                    ...data,
                  });
                }
              },
            );
          } else {
            reject({
              // @ts-ignore
              code: err.status ?? err.response?.status ?? 500,
              errors: ['Your recent action was not completed. Please try again.'],
              // @ts-ignore
              ...(err?.responseJSON || err.response?.data),
            });
          }
        }

        // Handle unauthorized error by dispatching a NOT_AUTHENTICATED event
        if (err.response && err.response.status === 401) {
          message = 'Unauthorized';
          document.body.dispatchEvent(
            new (CustomEvent as any)('NOT_AUTHENTICATED', {
              bubbles: true,
              detail: { continueAjax },
            }),
          );
        } else {
          // Handle request timeout or other errors
          if (
            params.timeout &&
            params.url === '/merchant/api/live/merchant/analytics' &&
            err.code === 'ECONNABORTED'
          ) {
            reject({
              code: err.code,
              errors: [err.message],
            });
          }
          reject({
            // @ts-ignore
            code: err.status || 500,
            errors: [message || err.message],
            // @ts-ignore
            ...err.responseJSON,
          });
        }
      },
    );
  });
};
