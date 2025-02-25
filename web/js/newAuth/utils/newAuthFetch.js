import { dashboardFetch } from '@libs/shared-utils';
import { getMode } from '@dashboards/payments/store';

export const newAuthFetch = (params) => {
  return dashboardFetch(params, { getMode });
};

