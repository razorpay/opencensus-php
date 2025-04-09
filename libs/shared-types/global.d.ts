import {
  DASHBOARD_CURRENCY_CODE_TYPE,
  DashboardSegmentAnalytics,
  RazorAnalytics,
  RazorAnalyticsPlugins,
  RazorpayUser,
} from './src/common';

declare const __STAGE__: string;
declare const __BUILD_MODE__: 'legacy' | 'modern';
declare const __APP_VERSION__: string;

declare global {
  interface Window {
    session_id: string;
    APP_NAME: string;
    APP_ENV: string;
    /**
     * @deprecated This is not to be used for anything! Will be removed soon. Rely on `APP_ENV` instead.
     */
    INSTANCE_TYPE: string;
    /**
     * @deprecated This is not to be used for anything!
     */
    __VERSION__: string;
    RZP?: {
      appName: string;
      appHost: string;
    };
    rzpQMetrics: {
      push: (metric: {
        type: string;
        properties: {
          name: string;
          labels: {
            type: string;
            url: string;
            route: string;
            code: string;
          }[];
        };
      }) => void;
    };
    SEGMENT_API_KEY: string;
    LUMBERJACK_API_KEY: string;
    LUMBERJACK_API_URL: string;
    LUMBERJACK_METRICS_API_URL: string;
    INSIGHT_X_SUPERSET_URL: string;
    INSIGHT_X_SUPERSET_OVERVIEW_ID: string;
    INSIGHT_X_SUPERSET_UPI_ID: string;
    INSIGHT_X_SUPERSET_CARDS_ID: string;
    INSIGHT_X_SUPERSET_NETBANKING_ID: string;
    INSIGHT_X_SUPERSET_WALLETS_ID: string;
    BANK_DETAILS_URL: string;
    PP_ECOMMERCE_URL: string;
    EASY_ONBOARDING_URL: string;
    RAZORPAY_WEBSITE: string;
    STREAKS_REWARDS?: string;
    ONE_DASHBOARD?: boolean;
    RAZORPAY_ACCOUNTS_URL: string;
    analytics: DashboardSegmentAnalytics;
    razorAnalytics: RazorAnalytics;
    razorAnalyticsPlugins: RazorAnalyticsPlugins;
    /**
     * @warning Only to be used for cross-repo federated assets.
     */
    cdnBaseUrl: string;
    /**
     * @deprecated Please use user object from `@federated/apps/shell/commonStore` instead. This will be deprecated soon.
     */
    rzp_user: RazorpayUser;
    /**
     * Assets cdn from where browser bundles, other important assets are served
     */
    cdnDashboardAssetsUrl: string;
    xDashboardCDNAssetsUrl: string;
    currencyList?: Record<DASHBOARD_CURRENCY_CODE_TYPE, any>;
    rzpTicketSystem: {
      hostname?: string;
      openModal?: <T>(x: string, data?: T) => void;
    };
    hj?: <T>(x: string, y: T) => void;
    ReactNativeWebView?: {
      postMessage: (x: string) => void;
    };
    gapi: {
      auth2: {
        init: (params: { client_id: string }) => Promise<void>;
        getAuthInstance: () => {
          signOut: () => void;
          disconnect: () => void;
        };
      };
      load: (library: string, callback: () => void) => void;
    };
    // ENVs available in window
    OAUTH_CLIENT_ID: string;
  }
}

declare global {
  interface Navigator {
    msSaveBlob?: (blob: Blob, defaultName?: string) => boolean;
  }
}

export {};
