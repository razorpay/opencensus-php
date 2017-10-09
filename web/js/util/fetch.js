import axios from 'axios';

export function adminFetch({ route, queryParams }) {
  return axios.get('/admin/generic', {
    params: {
      query_params: JSON.stringify(queryParams),
      route_name: route,
    },
  });
}
