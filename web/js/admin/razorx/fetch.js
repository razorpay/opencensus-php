import { adminFormUpload } from 'common/fetch';
import { AppStore } from 'admin/user';
import { stringifyQueryParams } from 'rzp/utils/rzp-utils';
import { notifyError } from 'common/modal';

/*
  For all custom rexFetch, Post, /etc helpers, payload must have relative url to "/admin/api/"
  Eg: rexFetch({url: '{mode}/your_url'}), or rexFetch('{mode}/your_url')
*/
export const rexFetch = payload => _makeRequest(payload, 'GET');
export const rexPost = payload => _makeRequest(payload, 'POST');
export const rexPut = payload => _makeRequest(payload, 'PUT');
export const rexDelete = payload => _makeRequest(payload, 'DELETE');
export const rexPatch = payload => _makeRequest(payload, 'PATCH');

function _makeRequest(payload, type) {
  const BASE_URL = '/makeapicall/service/razorx';
  const reqPayload = {
    method: type,
    mode: payload.mode || AppStore.mode,
    auth: 'admin',
  };

  if (payload.data) {
    reqPayload.body = payload.data;
  }

  let url;
  if (typeof payload === 'string') {
    url = payload;
  } else {
    url = payload.url;
  }

  const queryParams = payload.params || {};
  url = BASE_URL + stringifyQueryParams({ service_path: url, ...queryParams });

  return adminFormUpload(reqPayload, url) // Final request is axios.post
    .then(resp => {
      if (resp.status === '200') {
        return resp.data;
      }

      throw { errors: resp.data.errors || ['Some network error has occurred'] };
    })
    .catch(({ errors = {} }) => {
      notifyError(errors[0]);
    });
}
