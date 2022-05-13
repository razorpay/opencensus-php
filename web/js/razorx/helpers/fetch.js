import adminFetch from 'razorx/helpers/admin-fetch';
import { AppStore } from 'razorx/store';
import { stringifyQueryParams } from 'common/utils/rzp-utils';
import { notifyError } from 'razorx/components/Modal';

/*
  For all custom rexFetch, Post, /etc helpers, payload must have relative url to "/admin/api/"
  Eg: rexFetch({url: '{mode}/your_url'}), or rexFetch('{mode}/your_url')
*/
export const rexFetch = (payload) => _makeRequest(payload, 'GET');
export const rexPost = (payload) => _makeRequest(payload, 'POST');
export const rexPut = (payload) => _makeRequest(payload, 'PUT');
export const rexDelete = (payload) => _makeRequest(payload, 'DELETE');
export const rexPatch = (payload) => _makeRequest(payload, 'PATCH');

// TODO: confirm if we can send live always
const SPLITZ_BASE_URL = `/admin/api/live/service/splitz`;

/*
 * Request Footprint
 * {
 *   url: '/admin/api/${mode}/service/razorx?service_path=__&q1=__&q2=__&mode=__&environment=__'
 *   method: 'GET/POST/...',
 *   data: {},
 *   params: {}
 * }
 *
 * */
function _makeRequest(payload, type) {
  let mode = AppStore.mode; // Default

  // Override mode if present in params / data
  if (typeof payload === 'object') {
    if (payload.params && payload.params.mode) {
      mode = payload.params.mode;
    } else if (payload.data && payload.data.mode) {
      mode = payload.data.mode;
    }
  }

  const BASE_URL = `/admin/api/${mode}/service/razorx`;
  const reqPayload = {
    method: type,
  };

  if (reqPayload.method.toLowerCase() !== 'get') {
    reqPayload.headers = {
      'Content-Type': 'application/json',
    };
  }

  let url;
  if (typeof payload === 'string') {
    url = payload;
  } else {
    url = payload.url;
  }

  const razorxQueryParams = payload.params || {};
  if (!razorxQueryParams.mode && type.toLowerCase() === 'get') {
    razorxQueryParams.mode = mode; // To be attached in query params only when it's GET request, or sent explicitly otherwise
  }

  // Construct url
  url = `${BASE_URL}?service_path=${url}`;

  if (razorxQueryParams && Object.keys(razorxQueryParams).length) {
    url += window.encodeURIComponent(stringifyQueryParams(razorxQueryParams));
  }

  reqPayload.url = url;

  if (payload.data) {
    reqPayload.data = payload.data;
  }

  return adminFetch(reqPayload)
    .then((resp) => {
      if (!resp) {
        return true;
      }

      if (resp.status_code && Math.floor(resp.status_code / 100) !== 2) {
        const errorMessage = { errors: [resp.response.error] };
        throw errorMessage;
      }

      // In some cases like Workflow creation, resp.response / resp.status_code doesn't exist => resp is success
      if (resp) {
        return resp.response || resp;
      }
      return true;
    })
    .catch((err) => {
      let error = typeof err.errors !== 'undefined' ? err.errors : err;

      if (error instanceof Array) {
        error = error[0];
      }

      notifyError(error);
    });
}

export function splitzFetch(requestOptions) {
  const data = requestOptions.data || {};
  if (data.count) {
    data.limit = data.count;
    data.offset = data.skip;
    delete data.count;
    delete data.skip;
  }

  const options = {
    url: `${SPLITZ_BASE_URL}?service_path=twirp/rzp.splitz.${requestOptions.url}`,
    data,
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
    },
  };

  return new Promise((resolve, reject) =>
    adminFetch(options)
      .then((resp) => {
        if (!resp) {
          return;
        }

        if (resp.status_code && Math.floor(resp.status_code / 100) !== 2) {
          reject(resp.response.msg);
        }

        // In some cases like Workflow creation, resp.response / resp.status_code doesn't exist => resp is success
        if (resp) {
          const { files, projects, experiments, groups, segment } = resp.response;

          const entity = projects || experiments || groups || segment || null;

          if (entity) {
            resolve({
              items: entity,
              count: entity.length,
              fileUrl: files,
            });
          } else {
            resolve(resp.response);
          }
        }
      })
      .catch((err) => {
        let error = typeof err.errors !== 'undefined' ? err.errors : err;

        if (error instanceof Array) {
          error = error[0];
        }

        notifyError(error);

        reject(error);
      }),
  );
}
