import moment from 'moment';

import { merchantFetch } from 'merchant/utils/ajax';
import {
  MAGIC_APP_NAME,
  RCOD_APP_NAME,
  SOPC_APP_NAME,
} from 'merchant/views/MagicCheckout/common/constants';
import { CATEGORY_TYPES_API } from 'merchant/views/MagicCheckout/OrderAnalytics/Reports/constants';

const getAppType = (dashboardView) => {
  let appType = MAGIC_APP_NAME;
  if (dashboardView === RCOD_APP_NAME || dashboardView === SOPC_APP_NAME) appType = SOPC_APP_NAME;
  return appType;
};

export const fetchAnalyticsData = ({ start, end }, dashboardView) => {
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
      app_type: getAppType(dashboardView),
    },
  });
};

export const getOrderAnalyticsReports = ({ category, from, to, dashboardView }) => {
  return merchantFetch({
    url: `magic/analytics/reports/${CATEGORY_TYPES_API[category]}`,
    method: 'get',
    params: {
      from,
      to,
      app_type: getAppType(dashboardView),
    },
  });
};
