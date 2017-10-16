import axios from 'axios';
import { deepClone } from 'util/index';

export function adminFetch(params) {
  return axios.get('/admin/generic', { params: parseParams(params) });
}

export default function fetch(options) {
  return axios(options);
}

export function adminPost(params, config) {
  return axios.post('/admin/generic', parseParams(params), config);
}

export function adminDelete(params) {
  return axios.delete('/admin/generic', {
    params: parseParams({ data: params }),
  });
}

function parseParams({ data, queryParams }) {
  let params = deepClone(data);

  if (queryParams) {
    params.query_params = JSON.stringify(queryParams);
  }

  if (data.url_params) {
    let curlyParams = {};
    for (let i in data.url_params) {
      curlyParams[`{${i}}`] = data.url_params[i];
    }
    params.url_params = JSON.stringify(curlyParams);
  }
  return params;
}
