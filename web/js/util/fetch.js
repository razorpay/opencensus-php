import axios from 'axios';

export function adminFetch(params) {
  return axios.get('/admin/generic', { params: parseParams(params) });
}

export function adminPost(params) {
  return axios.post('/admin/generic', parseParams(params));
}

function parseParams({ route, queryParams, urlParams, body, merchantId }) {
  let params = {
    route_name: route,
  };
  if (body) {
    params.body = body;
  }
  if (merchantId) {
    params.merchant_id = merchantId;
  }
  if (queryParams) {
    params.query_params = JSON.stringify(queryParams);
  }
  if (urlParams) {
    let curlyParams = {};
    for (let i in urlParams) {
      if (urlParams.hasOwnProperty(i)) {
        curlyParams[`{${i}}`] = urlParams[i];
      }
    }
    params.url_params = JSON.stringify(curlyParams);
  }
  return params;
}
