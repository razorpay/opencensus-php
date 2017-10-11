import axios from 'axios';

export function adminFetch({ route, queryParams, urlParams }) {
  let params = {
    route_name: route,
  };
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
  return axios.get('/admin/generic', { params });
}
