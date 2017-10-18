import axios from 'axios';
import { deepClone } from 'util/index';

export function adminFetch(params) {
  return axios({
    url: '/admin/generic',
    params: parseParams(params),
  });
}

export default function fetch(options) {
  return axios(options);
}

export function adminPost(params) {
  return axios({
    url: '/admin/generic',
    method: 'post',
    ...parseParams(params),
  });
}

export function adminPut(params, config) {
  return axios.put('/admin/generic', parseParams(params), config);
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
  return axios.delete('/admin/generic', {
    params: parseParams({ data: params }),
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
};

const createFormData = (form = {}) => {
  let formData = new FormData();

  Object.keys(form).map(key => {
    formData.append(key, form[key]);
  });
  return formData;
};
