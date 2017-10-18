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
}
