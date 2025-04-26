import errorService from '@razorpay/universe-cli/errorService';

import { DASHBOARD_PRIORITY_RANKS, DASHBOARD_TEAMS, RazorpayUser } from '@libs/shared-types';
import { getCommonAnalyticsProperties, isProductionEnv } from '@libs/shared-utils';

export const initRazorAnalytics = ({ product, user }: {
  product: DASHBOARD_TEAMS;
  user: RazorpayUser;
}) => {
  if (Boolean(window?.razorAnalytics)) {
    try {
      const { lumberjack } = window?.razorAnalyticsPlugins;
      const userIdentity = getCommonAnalyticsProperties(user, { addUserProperties: true }) as { userId: string } & Record<string, any>;
      const isProd = isProductionEnv();

      // Initialize RazorAnalytics will happen once as it is guarded in SDK
      window.razorAnalytics?.init?.({
        plugins: [lumberjack?.({
          // since type of 'APP_ENV' is string, we are using ternary check instead of assigning 'APP_ENV' value directly to 'environment' to avoid TS error
          environment: isProd ? 'production' : 'staging',
        })],
        mode: isProd ? 'live' : 'debug',
        identity: userIdentity,
        eventProperties: {
          appName: DASHBOARD_TEAMS.PG_DASHBOARD,
          product,
        },
        pageConfig: {
          captureSearchParams: true,
        },
        autoCapture: {
          enabled: true,
          linkClick: {
            captureTextContent: true,
            captureSearchParams: true,
          },
        },
      });

      // Enable tracking
      window.razorAnalytics?.enableTracking?.();

      // Set Application and Product info with 'setEventProperties', to be passed in all events
      window.razorAnalytics?.setEventProperties?.({
        product,
      });
    } catch (error) {
      errorService.captureError(error, {
        tags: {
          team: DASHBOARD_TEAMS.PLATFORM,
          module: '[@libs/shared-utils] initRazorAnalytics',
        },
        rank: DASHBOARD_PRIORITY_RANKS.P2,
      });
    }
  }
};
