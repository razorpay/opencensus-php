import axios from 'axios';
import { deepClone } from 'common/util';
import { notifyError } from 'common/modal';

// If directly using fetch, then send absolute url
// Eg: fetch({url: '/admin/api/live/your_url'})
export default function fetch(options, suppressError) {
  return axios(options)
    .then(({ data }) => {
      if (typeof data !== 'object') {
        // Eg- when data is .html template
        return data;
      }

      if (!data.success) {
        if (!suppressError) {
          notifyError(data.errors.join('\n'));
        } else {
          return data;
        }
      } else {
        return data.data || data; // Cases like retry settlement doesn't have data.data but have data.kotak
      }
    })
    .catch(e => notifyError(e));
}

/*
  For all custom adminFetch, Post, /etc helpers, payload must have relative url to "/admin/api/"
  Eg: adminFetch({url: '{mode}/your_url'}), or adminFetch('{mode}/your_url')
*/
export const adminFetch = payload => fetch(_makePayload(payload, 'get'));
export const adminPost = payload => fetch(_makePayload(payload, 'post'));
export const adminPut = payload => fetch(_makePayload(payload, 'put'));
export const adminDelete = payload => fetch(_makePayload(payload, 'delete'));
export const adminPatch = payload => fetch(_makePayload(payload, 'patch'));

function _makePayload(payload, type) {
  let reqPayload = {
    method: type,
  };

  if (typeof payload === 'string') {
    reqPayload.url = payload;
  } else {
    reqPayload = { ...reqPayload, ...payload };
  }

  reqPayload.url = '/admin/api/' + reqPayload.url; // final url is "/admin/api/+url"

  return reqPayload;
}

// url are must be absolute url, Eg: /admin/api/{mode}/your_url
export function adminFormUpload(form, url) {
  //Let axios decide which "Content-Type" to send
  let fData = createFormData(form);

  return axios.post(url, fData);
}

// url are must be absolute url, Eg: /admin/api/{mode}/your_url
//TODO: [CRITICAL] Merchant batch upload broke due to change in createFormData supporting array
export function adminFormUpload2(form, url) {
  //Let axios decide which "Content-Type" to send
  let fData = createFormData2(form);

  return axios.post(url, fData);
}

const createFormData = (form = {}) => {
  let formData = new FormData();

  Object.keys(form).map(key => {
    formData.append(key, form[key]);
  });
  return formData;
};

const createFormData2 = (form = {}) => {
  let formData = new FormData();

  Object.keys(form).map(key => {
    if (typeof form[key] === 'object') {
      Object.keys(form[key]).map(item => {
        formData.append(key + '[' + item + ']', form[key][item]); // Object, eg= type:{a:2,b:4} will be sent as type[a] = 2, type[b] = 4 separately
      });
    } else {
      formData.append(key, form[key]);
    }
  });
  return formData;
};
