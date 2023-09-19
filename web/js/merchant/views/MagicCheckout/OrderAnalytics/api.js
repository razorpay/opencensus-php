import moment from 'moment';

import { merchantFetch } from 'merchant/utils/ajax';

export const fetchAnalyticsData = ({ start, end }) => {
  start = start
    ? moment.unix(start).local().unix()
    : moment().startOf('day').subtract(2, 'day').local().unix();

  end = end
    ? moment.unix(end).local().unix()
    : moment().endOf('day').subtract(1, 'day').local().unix();
  return merchantFetch({
    url: '1cc/analytics',
    method: 'get',
    params: {
      from: start,
      to: end + 1, // adding 1 sec to the end timestamp since backend is not considering end as inclusive due to some limitations
    },
  });
};
