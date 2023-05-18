import moment from 'moment';
import { merchantFetch } from 'merchant/utils/ajax';

export const fetchAnalyticsData = ({ start, end }) => {
  if (!start) {
    start = moment().subtract(48, 'hours').unix();
  }
  if (!end) {
    end = moment().unix();
  }
  return merchantFetch({
    url: '1cc/analytics',
    method: 'get',
    params: {
      from: start,
      to: end,
    },
  });
};
