import { Request } from 'express';
import { ADMIN_DASHBOARD_INTERNAL_URL, PHP_BASE_URL, STAGE } from '@apps/shell/src/env';

export const getPhpBaseUrl = (req: Request) => {
  const hostName = req.hostname;
  const isDev = STAGE === 'development';

  switch (hostName) {
    // TODO: Canary, Devstack ???
    case 'admin-dashboard.razorpay.com':
      return ADMIN_DASHBOARD_INTERNAL_URL;
    default: {
      return isDev ? PHP_BASE_URL : `https://${hostName}`;
    }
  }
};
