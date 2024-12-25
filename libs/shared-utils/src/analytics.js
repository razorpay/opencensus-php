import errorService from '@razorpay/universe-utils/errorService';
import { v4 as uuid } from 'uuid';

import { Modules } from './constants/enums';
import { Teams, Ranks } from './constants/errorBoundary';
import { getCookie } from './cookies';
import getMobileDetect from './mobileDetect';
import { isMobileDevice } from './home-utils';

import { titleCase, getCommonAnalyticsProperties } from './rzp-utils';
import { ANALYTICS } from './constants';

let source = null;

export const sendToLumberjack = ({ eventName, properties = {} }) => {
  const body = {
    mode: 'live',
    key: window.LUMBERJACK_API_KEY,
    events: [
      {
        event_type: 'pg-dashboard',
        event: eventName,
        event_version: 'v1',
        timestamp: new Date().getTime(),
        properties: {
          ...properties,
        },
      },
    ],
  };

  fetch(window.LUMBERJACK_API_URL, {
    method: 'post',
    body: JSON.stringify(body),
    headers: {
      'Content-Type': 'application/json',
    },
    priority: 'low',
  }).catch(() => {});
};

const throwAnalyticsException = (errorMessage) => {
  const error = new Error(errorMessage);

  errorService.captureError(error, {
    tags: {
      team: Teams.PLATFORM,
      module: 'analytics',
    },
    rank: Ranks.P2,
  });
};

export const initAnalytics = () => {
  return new Promise((resolve) => {
    window.analytics = window.analytics || [];
    const analytics = window.analytics;
    if (!analytics.initialize)
      if (analytics.invoked) {
        if (window.console && console.error) console.error('Segment snippet included twice.');
      } else {
        analytics.invoked = !0;
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
        analytics.factory = function factory(t) {
          return function fn(...args) {
            const e = Array.prototype.slice.call(args);
            e.unshift(t);
            analytics.push(e);
            return analytics;
          };
        };
        for (let t = 0; t < analytics.methods.length; t++) {
          const e = analytics.methods[t];
          analytics[e] = analytics.factory(e);
        }
        analytics.load = function load(t, e) {
          const n = document.createElement('script');
          n.type = 'text/javascript';
          n.async = !0;
          n.src = `https://cdn.segment.com/analytics.js/v1/${t}/analytics.min.js`;
          n.onload = resolve;
          const a = document.getElementsByTagName('script')[0];
          a.parentNode.insertBefore(n, a);
          analytics._loadOptions = e;
        };
        analytics.SNIPPET_VERSION = '4.1.0';
        // Events on signup and signin are required to be sent to Website project(Segment).
        // isAuthPage is set to true only by signup/signin/forgot-password/2FA/resetpassword pages. (excludes all /app pages).
        // if isAuthPage is true, send to website project or else dashboard project.
        analytics.load(window.SEGMENT_API_KEY);
        analytics.page();
      }
  });
};

export const getDeviceSource = () => {
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

export function extractExceptionProps(event) {
  const {
    exception: { values },
    level,
    event_id,
    environment,
    release,
    tags,
    request,
  } = event;

  let exceptionProps = {};

  try {
    exceptionProps = {
      exceptionType: values[0].type,
      exceptionTitle: values[0].value,
      exceptionURL: request.url,
      exceptionLevel: level,
      exceptionId: event_id,
      exceptionTeam: tags.team || 'UNKNOWN',
      exceptionApp: tags.app || 'Merchant',
      exceptionUserRole: tags.role || 'UNKNOWN',
      exceptionEnvironment: environment,
      exceptionRelease: release,
    };
  } catch (ex) {
    console.error(ex, event, 'Error in capturing exception info');
  }
  return exceptionProps;
}

export const analyticsTrack = ({
  objectName,
  actionName,
  screen = ANALYTICS.SCREEN.DASHBOARD,
  properties = {},
  toLumberjack = true, // Send all events to LJ by default
  toCleverTap = false,
  toFacebook = false,
  includeScreenResolution = false,
}) => {
  if (!objectName) {
    throwAnalyticsException(`[analytics]: objectName cannot be empty ${screen} ${actionName}`);
  }

  if (!actionName) {
    throwAnalyticsException(`[analytics]: actionName cannot be empty ${screen} ${objectName}`);
  }

  if (!screen) {
    throwAnalyticsException(`[analytics]: screen cannot be empty ${objectName} ${actionName}`);
  }

  if (/_/g.test(objectName)) {
    throwAnalyticsException(`[analytics]: expected objectName: ${objectName} to not have '_'`);

    return; // Don't capture the event if the objectName contains a "_".
  }

  if (/_/g.test(actionName)) {
    const errorMessage = `[analytics]: expected actionName: ${actionName} to not have '_'`;
    throwAnalyticsException(errorMessage);

    return; // Don't capture the event if the actionName contains a "_".
  }

  const eventTimestamp = new Date().toISOString();
  const eventName = titleCase(`${objectName} ${actionName}`);
  const Facebook = 'Facebook Pixel';

  let screenResolutions = {};
  if (includeScreenResolution) {
    screenResolutions = {
      screenWidth: document.documentElement?.clientWidth || window.innerWidth,
      screenHeight: document.documentElement?.clientHeight || window.innerHeight,
    };
  }

  if (window.analytics && window.analytics.track) {
    window.analytics.track(
      eventName,
      {
        sessionId: window?.session_id ? window.session_id : undefined,
        ...screenResolutions,
        ...properties,
        screen,
        eventTimestamp,
        experiment_ID: getCookie('auth_source') === 'website' ? 'Signup_experiment_1' : 'none',
        // TODO: Deprecated, remove once all iterations are migrated
        // We use 1020px, as we mark tablets and mobile as mweb (in analytics)
        device_type: isMobileDevice(1020) ? 'mweb' : 'dweb',
        source: getDeviceSource(),
        userId: properties.userId || 'UNKNWON_USER',
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
        sessionId: window?.session_id ? window.session_id : undefined,
        ...properties,
        screen,
        eventTimestamp,
        uuid: uuid(),
      },
    });
  }
};

export const captureErrorOnAnalytics = (event, hint) => {
  if (
    event?.level === 'error' &&
    !hint?.originalException?.message?.includes?.('cross-origin error') &&
    event?.rank !== Ranks.P2 &&
    event?.rank !== Ranks.P3
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

export const analyticsTrackWithUserInfo = ({
  properties = {},
  addUserProperties = false,
  ...rest
}) => {
  const { screen } = rest;
  const propertiesWithUserInfo = {
    ...properties,
    ...getCommonAnalyticsProperties(window.rzp_user, { addUserProperties }),
  };
  if (screen === Modules.AccountAndSettings) {
    propertiesWithUserInfo.version = 'v2';
  }

  analyticsTrack({
    ...rest,
    properties: propertiesWithUserInfo,
  });
};

export const Metrics = {
  ERROR_COUNT: 'error.count',
  PAGE_VIEW: 'page.view',
};

const ALLOWED_METRIC_ENVS = ['production'];
export const capturePrometheusMetric = ({ name, labels = {} }) => {
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

    if (ALLOWED_METRIC_ENVS.includes(`${window.APP_ENV}`)) {
      fetch(window.LUMBERJACK_METRICS_API_URL, {
        method: 'post',
        body: JSON.stringify(data),
        headers: {
          accept: 'application/json',
          'Content-Type': 'text/plain',
        },
        priority: 'low',
      }).catch(() => {
        // do nothing
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
    //
  }
};

export const trackShorterKYCEvents = ({ properties, objectName, actionName, screen }) => {
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
