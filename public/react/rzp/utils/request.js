import { getCookie } from './cookies';

let store = null;

if (!process.env.RZP_ADMIN) {
  store = require('merchant/store');
}

export default (url, params = {}) => {
  let {
    headers = {},
    appendModeInURL = true,
    appendModeInQueryParam,
    ...rest
  } = params;
  let mode;
  let store;

  // Extract `mode` if either of `appendModeInURL` or `appendModeInQueryParam` is true
  // Mode is either extracted from the request body or from `store`
  if (appendModeInQueryParam || appendModeInURL) {
    mode =
      (rest.body && rest.body.mode) || (store && store.getState().session.mode);
  }

  if (appendModeInQueryParam) {
    rest.queryParams = rest.queryParams || {};
    rest.queryParams.mode = mode;
  } else if (appendModeInURL) {
    url = `/${mode}/${url}`;
  }

  if (rest.queryParams) {
    url = _constructURL(url, rest.queryParams);
    delete rest.queryParams;
  }

  url = _normalizeUrl(url);

  // File upload (where rest.multipart = true) must not flattenSearchParams otherwise fetch api won't autoset Content-Type as multipart
  if (rest.body && !rest.multipart) {
    rest.body = _flattenSearchParams(rest.body).join('&');
  }

  _setContentType(headers, rest);

  headers['X-XSRF-TOKEN'] = getCookie('XSRF-TOKEN');
  headers['Accept'] = 'application/json, text/plain, */*';

  const credentials = 'same-origin';

  rest = { headers, credentials, ...rest };

  return fetch(url, rest)
    .then(_checkStatus)
    .then(_parseJSON)
    .then(response => {
      if (response.success) {
        return response;
      } else {
        throw {
          errors: response.errors,
        };
      }
    })
    .catch(err => {
      if (typeof err === 'object') {
        throw err;
      }

      let message = '';
      if (err.status === 401) {
        message = 'Unauthorized';
      }

      throw {
        code: err.status,
        errors: [message],
        ...err.responseJSON,
      };
    });
};

// This fn. takes args by reference
function _setContentType(headers, rest) {
  // FormData type request doesn't need Content-Type key to be sent in fetch request
  if (rest.multipart) {
    delete headers['Content-Type'];
    delete rest.multipart; // removing extra key from rest
  } else if (!headers['Content-Type']) {
    headers['Content-Type'] = 'application/x-www-form-urlencoded;charset=UTF-8'; // Set default content type
  }
}

// Check response status
function _checkStatus(response) {
  if (response.status >= 200 && response.status < 300) {
    return response;
  } else {
    const error = new Error(response.statusText);
    error.response = response;
    throw error;
  }
}

// Parse response
const _parseJSON = response => response.json();

// Replaces consecutive & trailing slashes from the URL
const _normalizeUrl = url => {
  return url.replace(/([^:]\/)\/+/g, '$1').replace(/\/$/, '');
};

// Constructing url with query string for fetch request
const _constructURL = (url, queryParams) => {
  return url + '?' + _flattenSearchParams(queryParams).join('&');
};

function safeCheckNull(value) {
  return value === null ? '' : value;
}

// Flatten Search Params to encodeURIComponent format
function _flattenSearchParams(data) {
  const searchParams = [];
  function flattenObj(data, parentKey) {
    for (var key in data) {
      if (data.hasOwnProperty(key)) {
        if (data[key] instanceof Object) {
          if (parentKey) {
            flattenObj(data[key], parentKey + '[' + key + ']');
          } else {
            flattenObj(data[key], key);
          }
        } else {
          if (parentKey) {
            searchParams.push(
              encodeURIComponent(parentKey + '[' + key + ']') +
                '=' +
                encodeURIComponent(safeCheckNull(data[key]))
            );
          } else {
            searchParams.push(
              encodeURIComponent(key) +
                '=' +
                encodeURIComponent(safeCheckNull(data[key]))
            );
          }
        }
      }
    }
  }

  flattenObj(data);
  return searchParams;
}
