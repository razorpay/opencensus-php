import moment from 'moment';

import { merchantFetch } from 'merchant/utils/ajax';
import {
  MAGIC_APP_NAME,
  RCOD_APP_NAME,
  SOPC_APP_NAME,
} from 'merchant/views/MagicCheckout/common/constants';

export const fetchAnalyticsData = ({ start, end }, dashboardView) => {
  let appType = MAGIC_APP_NAME;
  if (dashboardView === RCOD_APP_NAME || dashboardView === SOPC_APP_NAME) appType = SOPC_APP_NAME;

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
      app_type: appType,
    },
  });
};
