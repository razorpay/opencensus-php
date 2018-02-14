import { getCookie } from './cookies';
import { getMode } from 'merchant/store';

export function merchantFetch(params) {
  if (typeof params === 'string') {
    params = {
      url: params
    }
  }

  params.url = `/merchant/api/${getMode()}/${params.url}`;

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
        if (err.status === 401) {
          message = 'Unauthorized';
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
};
