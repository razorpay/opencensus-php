import moment from 'moment';
import { merchantFetch } from 'merchant/utils/ajax';

export const fetchAnalyticsData = ({ start, end }) => {
  if (!start) {
    start = moment().startOf('day').subtract(2, 'days').unix();
  }
  if (!end) {
    end = moment().endOf('day').subtract(1, 'day').unix();
  }

  return merchantFetch({
    url: '1cc/analytics',
    method: 'get',
    params: {
      from: start,
      to: end + 1, // adding 1 sec to the end timestamp since backend is not considering end as inclusive due to some limitations
    },
  });
};
