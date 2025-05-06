import errorService from '@razorpay/universe-cli/errorService';
import { v4 as uuid } from 'uuid';
import {
  toTitleCase,
  getCommonAnalyticsProperties,
  isMobileDevice,
  getMobileDetect,
  getCookie,
} from './';
import { DASHBOARD_PRIORITY_RANKS, DASHBOARD_TEAMS } from '@libs/shared-types';
import { DASHBOARD_ROUTES, ANALYTICS_ONENAV, ANALYTICS } from '../constants';

type DASHBOARD_ROUTES_KEY_TYPE = keyof typeof DASHBOARD_ROUTES;

// Define the type for the Lumberjack event tracking properties.
interface LumberjackEventProps {
  eventName: string;
  properties?: Record<string, unknown>;
}

// Type for analytics tracking function parameters
interface AnalyticsTrackProps {
  objectName: string;
  actionName: string;
  screen: string;
  properties?: Record<string, unknown>;
  toLumberjack?: boolean;
  toCleverTap?: boolean;
  toFacebook?: boolean;
  includeScreenResolution?: boolean;
}

// Type for exception event properties
interface ExceptionEventProps {
  exception: {
    values: Array<{
      type: string;
      value: string;
    }>;
  };
  level: string;
  event_id: string;
  environment: string;
  release: string;
  tags: {
    team: string;
    app: string;
    role: string;
  };
  request: {
    url: string;
  };
}

interface TrackProfileClick {
  objectName: 'Profile Options' | 'L0 Main Frame Icons';
  optionName: string;
  bu_title: string;
  type: 'icon' | 'option'; // Assuming type can be 'icon' or other values
}

/**
 * Metrics constants used for tracking analytics events related to errors and page views.
 */
export const Metrics = {
  ERROR_COUNT: 'error.count',
  PAGE_VIEW: 'page.view',
};

/**
 * Get the team name for the given path/route
 * @param  {DASHBOARD_ROUTES_KEY_TYPE} path - The path/route
 * @returns {Object} - The object with route & team name
 */
export const getTeamName = (path: DASHBOARD_ROUTES_KEY_TYPE) => {
  // Remove /app from start of path
  path = path?.replace?.(/^\/app/, '') as DASHBOARD_ROUTES_KEY_TYPE;
  if (Boolean(DASHBOARD_ROUTES?.[path])) {
    return DASHBOARD_ROUTES[path];
  }

  // remove entity id from path and check if path is in RoutesConfig
  // e.g.
  // '/partners/applications/app_1234' => '/partners/applications'
  // '/payments/pay_1234' => '/payments'
  const route = path?.substr?.(0, path?.lastIndexOf?.('/')) as DASHBOARD_ROUTES_KEY_TYPE;
  if (Boolean(DASHBOARD_ROUTES?.[route])) {
    return DASHBOARD_ROUTES[route];
  }

  // '/ticket-support/rzpind/I5wF456zbRX5jw/conversation' => '/ticket-support'
  const module = path?.substr?.(0, path?.indexOf?.('/', 1)) as DASHBOARD_ROUTES_KEY_TYPE;
  if (Boolean(DASHBOARD_ROUTES?.[module])) {
    return DASHBOARD_ROUTES[module];
  }

  // Team is not configured for the route
  return 'Unknown';
};

/**
 * Get the team name for the given path/route
 * @param  {DASHBOARD_ROUTES_KEY_TYPE} path - The path/route
 * @returns {String} - String (Path)
 */
export const getPathForMetrics = (path: DASHBOARD_ROUTES_KEY_TYPE) => {
  try {
    // Remove /app from start of path
    path = (path || window?.location?.pathname)?.replace?.(
      /^\/app/,
      '',
    ) as DASHBOARD_ROUTES_KEY_TYPE;
    if (Boolean(DASHBOARD_ROUTES?.[path])) {
      return path;
    }

    // remove entity id from path and check if path is in RoutesConfig
    // e.g.
    // '/partners/applications/app_1234' => '/partners/applications'
    // '/payments/pay_1234' => '/payments'
    const route = path?.substr?.(0, path?.lastIndexOf?.('/')) as DASHBOARD_ROUTES_KEY_TYPE;
    if (Boolean(DASHBOARD_ROUTES?.[route])) {
      return route;
    }

    // '/ticket-support/rzpind/I5wF456zbRX5jw/conversation' => '/ticket-support'
    const module = path?.substr?.(0, path?.indexOf?.('/', 1)) as DASHBOARD_ROUTES_KEY_TYPE;
    if (Boolean(DASHBOARD_ROUTES?.[module])) {
      return module;
    }

    // Team is not configured for the route
    return 'UNKNOWN_PATH';
  } catch (err) {
    return 'UNKNOWN_PATH';
  }
};

let source: string | null = null;

/**
 * Sends events to Lumberjack for analytics tracking.
 *
 * @param eventName - Name of the event.
 * @param properties - Additional properties for the event (optional).
 */
export const sendToLumberjack = ({ eventName, properties = {} }: LumberjackEventProps): void => {
  const merchantCountry = window.rzp_user?.merchant?.country_code;
  if (merchantCountry === 'SG') return;

  const body = {
    mode: 'live',
    key: window.LUMBERJACK_API_KEY,
    events: [
      {
        event_type: 'pg-dashboard',
        event: eventName,
        event_version: 'v1',
        timestamp: new Date().getTime(),
        properties: { ...properties },
      },
    ],
  };

  fetch(window.LUMBERJACK_API_URL, {
    method: 'POST',
    body: JSON.stringify(body),
    headers: {
      'Content-Type': 'application/json',
    },
    // @ts-ignore
    priority: 'low',
  }).catch(() => {});
};

/**
 * Throws an exception specifically for analytics-related errors.
 *
 * @param errorMessage - The error message to throw.
 */
const throwAnalyticsException = (errorMessage: string): void => {
  const error = new Error(errorMessage);
  errorService.captureError(error, {
    tags: {
      team: DASHBOARD_TEAMS.PLATFORM,
      module: 'analytics',
    },
    rank: DASHBOARD_PRIORITY_RANKS.P2,
  });
};

/**
 * Initializes the analytics (Segment) tracking. Ensures Segment is loaded and available for usage.
 *
 * @returns A promise that resolves when Segment is fully initialized.
 */
export const initAnalytics = (): Promise<void> => {
  return new Promise((resolve) => {
    window.analytics = window.analytics || [];
    const analytics = window.analytics;

    if (!analytics['initialize']) {
      if (analytics.invoked) {
        if (console.error) console.error('Segment snippet included twice.');
      } else {
        analytics.invoked = true;
        analytics.methods = [
          'trackSubmit',
          'trackClick',
          'trackLink',
          'trackForm',
          'pageview',
          'identify',
          'reset',
          'group',
          'track',
          'ready',
          'alias',
          'debug',
          'page',
          'once',
          'off',
          'on',
          'addSourceMiddleware',
          'addIntegrationMiddleware',
          'setAnonymousId',
          'addDestinationMiddleware',
        ];

        analytics.factory = function factory(method: string) {
          return function (...args: unknown[]) {
            const eventArgs = Array.prototype.slice.call(args);
            eventArgs.unshift(method);
            analytics?.push?.(eventArgs);
            return analytics;
          };
        };

        for (let t = 0; t < analytics.methods.length; t++) {
          const methodName = analytics.methods[t];
          analytics[methodName] = analytics.factory(methodName);
        }

        analytics.load = function load(apiKey: string, options?: unknown) {
          const scriptElement = document.createElement('script');
          scriptElement.type = 'text/javascript';
          scriptElement.async = true;
          scriptElement.src = `https://cdn.segment.com/analytics.js/v1/${apiKey}/analytics.min.js`;
          scriptElement.onload = resolve as unknown as (
            this: GlobalEventHandlers,
            ev: Event,
          ) => any;
          const firstScript = document.getElementsByTagName('script')[0];
          firstScript.parentNode?.insertBefore?.(scriptElement, firstScript);
          analytics._loadOptions = options;
        };

        analytics.SNIPPET_VERSION = '4.1.0';
        // Events on signup and signin are required to be sent to Website project(Segment).
        // isAuthPage is set to true only by signup/signin/forgot-password/2FA/resetpassword pages. (excludes all /app pages).
        // if isAuthPage is true, send to website project or else dashboard project.
        analytics.load(window.SEGMENT_API_KEY);
        analytics.page?.();
      }
    }
  });
};

/**
 * Retrieves the device source, indicating whether the user is on a mobile device, webview, or desktop dashboard.
 *
 * @returns The device source as a string (e.g., 'Mobile Dashboard', 'Webview - Android').
 */
export const getDeviceSource = (): string => {
  if (source === null) {
    const isWebView = getMobileDetect().isWebView();
    if (isWebView) {
      const isAndroid = getMobileDetect().isAndroid();
      source = isAndroid ? 'Webview - Android' : 'Webview - iOS';
    } else {
      source = isMobileDevice(1020) ? 'Mobile Dashboard' : 'Dashboard';
    }
  }
  return source;
};

/**
 * Extracts relevant properties from an exception event for tracking.
 *
 * @param event - The exception event to extract properties from.
 * @returns An object containing exception properties (e.g., exceptionType, exceptionURL).
 */
export function extractExceptionProps(event: ExceptionEventProps): Record<string, string> {
  let exceptionProps: Record<string, string> = {};

  try {
    exceptionProps = {
      exceptionType: event.exception.values[0].type,
      exceptionTitle: event.exception.values[0].value,
      exceptionURL: event.request.url,
      exceptionLevel: event.level,
      exceptionId: event.event_id,
      exceptionTeam: event.tags.team || 'UNKNOWN',
      exceptionApp: event.tags.app || 'Merchant',
      exceptionUserRole: event.tags.role || 'UNKNOWN',
      exceptionEnvironment: event.environment,
      exceptionRelease: event.release,
    };
  } catch (ex) {
    console.error(ex, event, 'Error in capturing exception info');
  }

  return exceptionProps;
}

/**
 * Tracks analytics events such as page views, clicks, and custom events.
 * Ensures events are sent to Segment and Lumberjack.
 *
 * @param objectName - The name of the object being tracked (e.g., 'Button').
 * @param actionName - The action performed on the object (e.g., 'Click').
 * @param screen - The screen where the event occurred.
 * @param properties - Additional event properties (optional).
 * @param toLumberjack - Flag indicating whether to send the event to Lumberjack (default: true).
 * @param toCleverTap - Flag indicating whether to send the event to CleverTap (default: false).
 * @param toFacebook - Flag indicating whether to send the event to Facebook (default: false).
 * @param includeScreenResolution - Flag indicating whether to include screen resolution details (default: false).
 */
export const analyticsTrack = ({
  objectName,
  actionName,
  screen = 'dashboard',
  properties = {},
  toLumberjack = true,
  toCleverTap = false,
  toFacebook = false,
  includeScreenResolution = false,
}: AnalyticsTrackProps): void => {
  const merchantCountry = window?.rzp_user?.merchant?.country_code;
  if (merchantCountry === 'SG') return;

  if (!objectName)
    throwAnalyticsException(`[analytics]: objectName cannot be empty ${screen} ${actionName}`);
  if (!actionName)
    throwAnalyticsException(`[analytics]: actionName cannot be empty ${screen} ${objectName}`);
  if (!screen)
    throwAnalyticsException(`[analytics]: screen cannot be empty ${objectName} ${actionName}`);
  if (/_/g.test(objectName))
    throwAnalyticsException(`[analytics]: objectName "${objectName}" contains '_'`);
  if (/_/g.test(actionName))
    throwAnalyticsException(`[analytics]: actionName "${actionName}" contains '_'`);

  const eventTimestamp = new Date().toISOString();
  const eventName = toTitleCase(`${objectName} ${actionName}`);
  const Facebook = 'Facebook Pixel';

  let screenResolutions: Record<string, number> = {};
  if (includeScreenResolution) {
    screenResolutions = {
      screenWidth: document.documentElement?.clientWidth || window.innerWidth,
      screenHeight: document.documentElement?.clientHeight || window.innerHeight,
    };
  }

  if (window?.analytics?.track) {
    window.analytics.track(
      eventName,
      {
        sessionId: window?.session_id,
        ...screenResolutions,
        ...properties,
        screen,
        eventTimestamp,
        experiment_ID: getCookie('auth_source') === 'website' ? 'Signup_experiment_1' : 'none',
        device_type: isMobileDevice(1020) ? 'mweb' : 'dweb',
        source: getDeviceSource(),
        userId: properties['userId'] || 'UNKNOWN_USER',
      },
      {
        integrations: {
          CleverTap: toCleverTap,
          [Facebook]: toFacebook,
        },
      },
    );
  }

  if (toLumberjack) {
    sendToLumberjack({
      eventName,
      properties: {
        sessionId: window?.session_id,
        ...properties,
        screen,
        eventTimestamp,
        uuid: uuid(),
      },
    });
  }
};

/**
 * Captures errors related to analytics by tracking Sentry errors as custom events.
 *
 * @param event - The Sentry error event.
 * @param hint - Additional context or hints related to the error (optional).
 */
export const captureErrorOnAnalytics = (event: any, hint?: Record<string, any>): void => {
  if (
    event?.level === 'error' &&
    !hint?.['originalException']?.message?.includes?.('cross-origin error') &&
    event?.rank !== DASHBOARD_PRIORITY_RANKS.P2 &&
    event?.rank !== DASHBOARD_PRIORITY_RANKS.P3
  ) {
    analyticsTrack({
      objectName: 'Sentry Error',
      actionName: 'Captured',
      screen: window.location.pathname,
      properties: {
        ...extractExceptionProps(event),
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
      toLumberjack: true,
    });
  }
};

/**
 * Tracks analytics events with user information included.
 *
 * @param properties - Additional event properties (optional).
 * @param addUserProperties - Flag indicating whether to include user properties (optional).
 * @param rest - The rest of the analytics event properties (objectName, actionName, screen, etc.).
 */
export const analyticsTrackWithUserInfo = ({
  properties = {},
  addUserProperties = false,
  ...rest
}: AnalyticsTrackProps & { addUserProperties?: boolean }): void => {
  const merchantCountry = window.rzp_user?.merchant?.country_code;
  if (merchantCountry === 'SG') return;

  const { screen } = rest;
  const propertiesWithUserInfo = {
    ...properties,
    ...getCommonAnalyticsProperties(window.rzp_user, { addUserProperties }),
  };

  if (screen === 'Account & Settings') {
    propertiesWithUserInfo['version'] = 'v2';
  }

  analyticsTrack({
    ...rest,
    properties: propertiesWithUserInfo,
  });
};

/**
 * Captures Prometheus metrics to track application-level performance and behavior.
 *
 * @param name - The name of the metric (e.g., 'error.count').
 * @param labels - Additional labels for the metric (optional).
 */
export const capturePrometheusMetric = ({
  name,
  labels = {},
}: {
  name: string;
  labels?: Record<string, unknown>;
}): void => {
  try {
    const data = {
      key: window.LUMBERJACK_API_KEY,
      metrics: [
        {
          name,
          labels: [
            {
              env: window.APP_ENV,
              app: window.APP_NAME,
              deployment_type: window.INSTANCE_TYPE,
              ...labels,
            },
          ],
        },
      ],
    };

    if (['production'].includes(`${window.APP_ENV}`)) {
      fetch(window.LUMBERJACK_METRICS_API_URL, {
        method: 'POST',
        body: JSON.stringify(data),
        headers: {
          accept: 'application/json',
          'Content-Type': 'text/plain',
        },
        // @ts-ignore
        priority: 'low',
      }).catch(() => {
        // Do nothing on error
      });
    } else {
      for (const metric of data.metrics) {
        console.groupCollapsed(
          '%c Prometheus Metric',
          'color: #FFFFF; background: #1566F1; padding: 1px;',
          `${metric.name}`,
        );
        for (const label of metric.labels) {
          console.log('Properties: ', label);
        }
        console.groupEnd();
      }
    }
  } catch (err) {
    // Log error but do not disrupt execution
  }
};

/**
 * Tracks shorter KYC events, specifically including partner KYC-related properties.
 *
 * @param properties - Additional properties for the event.
 * @param objectName - The name of the object being tracked.
 * @param actionName - The action performed on the object.
 * @param screen - The screen where the event occurred.
 */
export const trackShorterKYCEvents = ({
  properties,
  objectName,
  actionName,
  screen,
}: AnalyticsTrackProps): void => {
  analyticsTrack({
    objectName,
    actionName,
    screen,
    properties: {
      partnerID: window.rzp_user?.merchant?.id,
      shorterPartnerKYC: true,
      ...properties,
    },
  });
};

/**
 * Tracks user interactions with the profile dropdown menu.
 *
 * @param {string} objectName - The name of the object being tracked.
 * @param {string} optionName - The name of the option user interacted.
 * @param {object} bu_title - The selected product title.
 * @param {object} type - Icon or option.
 * @returns {void}
 */
export const trackProfileDropdownClicks = ({
  objectName,
  optionName,
  bu_title,
  type,
}: TrackProfileClick): void => {
  const analyticsInfo = {
    objectName,
    actionName: 'Clicked',
    screen: ANALYTICS_ONENAV.SCREEN,
    properties: {
      ...getCommonAnalyticsProperties((window as any).rzp_user, { addUserProperties: true }),
      version: 'v1',
      option_name: optionName,
      page: location.pathname?.replace('/app/', ''),
      bu_title,
      icon_name: type === 'icon' ? 'Profile' : null,
      experiment_name: ANALYTICS_ONENAV.EXPERIMENT_NAME,
    },
  };
  analyticsTrack(analyticsInfo);
};

/**
 * Tracks the "Create A New Account" button click event.
 * @returns {void}
 */
export const createNewAccountTrack = () => {
  analyticsTrack({
    objectName: 'Create A New Account Button',
    actionName: 'Clicked',
    screen: ANALYTICS.SCREEN.DASHBOARD,
    properties: {
      version: 'v2',
      session_id: (window as any).session_id,
      ...getCommonAnalyticsProperties((window as any).rzp_user, { addUserProperties: true }),
    },
  });
};
