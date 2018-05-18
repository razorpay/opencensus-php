import { getCookie } from './cookies';
import { getMode } from 'merchant/store';
import { Event } from './event';

export function merchantFetch(params) {
  if (typeof params === 'string') {
    params = {
      url: params,
    };
  }

  let mode = params.mode;
  if (mode) {
    delete params.mode;
  } else {
    mode = getMode();
  }

  if (params.accountId) {
    params.headers = {
      ...params.headers,
      'X-Razorpay-Account': params.accountId,
    };
  }
  delete params.accountId;

  params.url = `/merchant/api/${mode}/${params.url}`;

  return ajax(params);
}

export default function ajax(params = {}) {
  return new Promise((resolve, reject) => {
    let { headers = {} } = params;
    headers['X-XSRF-TOKEN'] = getCookie('XSRF-TOKEN');
    headers['Accept'] = 'application/json, text/plain, */*';
    params.headers = headers;

    if (
      (!params.method || String(params.method).toLowerCase() === 'get') &&
      params.data
    ) {
      params.params = params.data;
      delete params.data;
    }

    params.paramsSerializer = function(params) {
      const encodedParams = _flattenSearchParams(params);
      return encodedParams.join('&'); // Build the encoded query string
    };

    axios(params).then(
      ({ data }) => {
        if (data.success) {
          resolve(data);
        } else {
          reject(
            Object.assign(
              {
                code: 'UNKNOWN_ERROR_CODE',
              },
              data
            )
          );
        }
      },
      err => {
        let message = '';
        if (err.status === 401 || err.state === 403) {
          message = 'Unauthorized';

          document.body.dispatchEvent(
            new Event(
              err.status === 401 ? 'UNAUTHORIZED' : 'NOT_AUTHENTICATED',
              { bubbles: true }
            )
          );
        }

        reject(
          Object.assign(
            {
              code: err.status,
              errors: [message],
            },
            err.responseJSON
          )
        );
      }
    );
  });
}

// Flatten Search Params to encodeURIComponent format
function _flattenSearchParams(data) {
  const searchParams = [];
  function flattenObj(data, parentKey) {
    for (var key in data) {
      if (data.hasOwnProperty(key) && data[key]) {
        if (data[key] instanceof Object) {
          if (parentKey) {
            flattenObj(data[key], parentKey + '[' + key + ']');
          } else {
            flattenObj(data[key], key);
          }
        } else {
          // Check to ignore undefined values
          if (data[key] != null) {
            if (parentKey) {
              searchParams.push(
                encodeURIComponent(parentKey + '[' + key + ']') +
                  '=' +
                  encodeURIComponent(data[key])
              );
            } else {
              searchParams.push(
                encodeURIComponent(key) + '=' + encodeURIComponent(data[key])
              );
            }
          }
        }
      }
    }
  }

  flattenObj(data);
  return searchParams;
}
