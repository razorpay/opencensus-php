import axios from 'axios';

export function adminFetch({ route, queryParams }) {
  return axios.get('https://dashboard.razorpay.com/admin/generic', {
    query_params: JSON.stringify(queryParams),
    route,
  });
}
