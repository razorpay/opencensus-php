import axios from 'axios';
import { deepClone } from 'util/index';
import { notifyError } from 'common/modal';

export default function fetch(options) {
  return axios(options)
    .then(({ data }) => {
      if (!data.success) {
        data.errors.map(err => {
          notifyError(err);
        });
      } else {
        return data.data;
      }
    })
    .catch(e => notifyError(e));
}

export function adminFetch(params) {
  return fetch({
    url: '/admin/generic',
    params:
      typeof params === 'string' ? { route_name: params } : parseParams(params),
  });
}

export function adminPost(data, customUrl) {
  //Send params if needed for post request
  let params = data.params || null;
  data = parseParams(data);
  return fetch({
    url: customUrl ? customUrl : '/admin/generic',
    method: 'post',
    data,
    params,
  });
}

export function adminPut(data, customUrl) {
  data = parseParams(data);
  return fetch({
    url: customUrl ? customUrl : '/admin/generic',
    method: 'put',
    data,
  });
}

export function adminUserConfirm(params, config) {
  return axios.post('/admin/users/confirm', params);
}

export function adminFormUpload(form, url) {
  //Let axios decide which "Content-Type" to send
  let fData = createFormData(form);
  return axios.post(url, fData);
}

export function adminDelete(params) {
  return fetch({
    url: '/admin/generic',
    method: 'delete',
    params: parseParams(params),
  });
}

function parseParams(origParams) {
  let params = deepClone(origParams);

  if (origParams.query_params) {
    params.query_params = JSON.stringify(origParams.query_params);
  }

  if (origParams.url_params) {
    let curlyParams = {};
    for (let i in origParams.url_params) {
      curlyParams[`{${i}}`] = origParams.url_params[i];
    }
    params.url_params = JSON.stringify(curlyParams);
  }
  return params;
}

const createFormData = (form = {}) => {
  let formData = new FormData();

  Object.keys(form).map(key => {
    formData.append(key, form[key]);
  });
  return formData;
};
