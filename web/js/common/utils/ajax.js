// Todo: delete this file, it's available in @dashboard/shared-utils
// TODO: Remove these with refactor
/* eslint-disable no-lonely-if */
/* eslint-disable max-depth */
/* eslint-disable no-shadow */
import axios from 'axios';

import { getCookie } from './cookies';
import { capturePlaywrightAnalytics } from './playwrightAnalytics';
// import { captureXhrResponseMetrics } from './perf';

// axios.interceptors.response.use(function(response) {
//   captureXhrResponseMetrics(response);
//   return response;
// });

export default function ajax(params = {}) {
  return new Promise((resolve, reject) => {
    const { headers = {} } = params;
    headers['X-XSRF-TOKEN'] = getCookie('XSRF-TOKEN');
    headers['X-Requested-With'] = 'XMLHttpRequest';
    headers.Accept = 'application/json, text/plain, */*';
    params.headers = headers;
    if ((!params.method || String(params.method).toLowerCase() === 'get') && params.data) {
      params.params = params.data;
      delete params.data;
    }

    params.paramsSerializer = (currentParams) => {
      const encodedParams = _flattenSearchParams(currentParams);
      return encodedParams.join('&'); // Build the encoded query string
    };

    axios(params).then(
      (resp) => {
        const { data } = resp;
        // Error code is verified to handle api resolution to HTML doc / raw text.
        // Eg: For downloading csv file for api key-secret comes as raw text.
        capturePlaywrightAnalytics({ response: resp, params });
        if (!data.hasOwnProperty('success') || data.success == true) {
          resolve(data);
        } else {
          reject({
            code: 'UNKNOWN_ERROR_CODE',
            ...data,
          });
        }
      },
      (err) => {
        capturePlaywrightAnalytics({ error: err, params });
        document.body.dispatchEvent(
          new CustomEvent('REQUEST_ERROR', {
            bubbles: true,
            detail: {
              url: params.url,
              response: err.response,
            },
          }),
        );

        let message = '';

        function continueAjax() {
          if (!params.method || params.method.toLowerCase() === 'get') {
            axios(params).then(({ data }) => {
              if (data.success) {
                resolve(data);
              } else {
                reject({
                  code: 'UNKNOWN_ERROR_CODE',
                  ...data,
                });
              }
            });
            // Not calling error section Again, the catch block is upto the component to handle
          } else {
            reject({
              code: err.status,
              errors: ['Your recent action was not completed. Please Try again'],
              ...err.responseJSON,
            });
          }
        }

        if (err.response && err.response.status === 401) {
          message = 'Unauthorized';

          document.body.dispatchEvent(
            new CustomEvent('NOT_AUTHENTICATED', {
              bubbles: true,
              detail: { continueAjax },
            }),
          );
        } else {
          if (
            params.timeout &&
            params.url === '/merchant/api/live/merchant/analytics' &&
            err.code === 'ECONNABORTED'
          ) {
            reject({
              code: err.code,
              errors: err.message,
            });
          }
          reject({
            code: err.status,
            errors: [message],
            ...err.responseJSON,
          });
        }
      },
    );
  });
}

// Flatten Search Params to encodeURIComponent format
function _flattenSearchParams(data) {
  const searchParams = [];
  function flattenObj(data, parentKey) {
    for (const key in data) {
      if (data.hasOwnProperty(key) && data[key]) {
        if (data[key] instanceof Object) {
          if (parentKey) {
            flattenObj(data[key], `${parentKey}[${key}]`);
          } else {
            flattenObj(data[key], key);
          }
        } else {
          // Check to ignore undefined values
          if (data[key] != null) {
            if (parentKey) {
              searchParams.push(
                `${encodeURIComponent(`${parentKey}[${key}]`)}=${encodeURIComponent(data[key])}`,
              );
            } else {
              searchParams.push(`${encodeURIComponent(key)}=${encodeURIComponent(data[key])}`);
            }
          }
        }
      }
    }
  }

  flattenObj(data);
  return searchParams;
}
