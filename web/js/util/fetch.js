import axios from 'axios';
import { deepClone } from 'util/index';
import { notifyError } from 'common/modal';

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
        }

        return data;
      } else {
        return data.data;
      }
    })
    .catch(e => notifyError(e));
}

export function adminFetch(params, customUrl) {
  return fetch({
    url: customUrl ? customUrl : '/admin/generic',
    params:
      typeof params === 'string' ? { route_name: params } : parseParams(params),
  });
}

export function adminPost(data, customUrl) {
  data = parseParams(data);
  return fetch({
    url: customUrl ? customUrl : '/admin/generic',
    method: 'post',
    data,
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

export function adminFormUpload(form, customUrl) {
  //Let axios decide which "Content-Type" to send
  let url = customUrl ? customUrl : '/admin/generic';
  let fData = createFormData(form);

  return axios.post(url, fData);
}

//TODO: [CRITICAL] Merchant batch upload broke due to change in createFormData supporting array
export function adminFormUpload2(form, customUrl) {
  //Let axios decide which "Content-Type" to send
  let url = customUrl ? customUrl : '/admin/generic';
  let fData = createFormData2(form);

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
