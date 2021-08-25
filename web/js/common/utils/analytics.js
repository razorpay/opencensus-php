import { titleCase } from './rzp-utils';
import { getCookie } from 'common/utils/cookies';

const sendToLumberjack = ({ eventName, properties = {} }) => {
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
  });
};

const throwAnalyticsException = (errorMessage) => {
  const error = new Error(errorMessage);

  if (window.Sentry) {
    Sentry.captureException(error, (scope) => {
      scope.setTag('section', 'analytics');
      return scope;
    });
  } else {
    throw error;
  }
};

export const initAnalytics = () => {
  return new Promise((resolve) => {
    var analytics = (window.analytics = window.analytics || []);
    if (!analytics.initialize)
      if (analytics.invoked)
        window.console && console.error && console.error('Segment snippet included twice.');
      else {
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
        analytics.factory = function (t) {
          return function () {
            var e = Array.prototype.slice.call(arguments);
            e.unshift(t);
            analytics.push(e);
            return analytics;
          };
        };
        for (var t = 0; t < analytics.methods.length; t++) {
          var e = analytics.methods[t];
          analytics[e] = analytics.factory(e);
        }
        analytics.load = function (t, e) {
          var n = document.createElement('script');
          n.type = 'text/javascript';
          n.async = !0;
          n.src = 'https://cdn.segment.com/analytics.js/v1/' + t + '/analytics.min.js';
          n.onload = resolve;
          var a = document.getElementsByTagName('script')[0];
          a.parentNode.insertBefore(n, a);
          analytics._loadOptions = e;
        };
        analytics.SNIPPET_VERSION = '4.1.0';
        // Events on signup and signin are required to be sent to Website project(Segment).
        // isAuthPage is set to true only by signup/signin/forgot password/2FA pages. (excludes all /app pages).
        // if isAuthPage is true, send to website project or else dashboard project.
        analytics.load(window.SEGMENT_API_KEY);
        analytics.page();
      }
  });
};

export const analyticsTrack = ({
  objectName,
  actionName,
  screen,
  properties = {},
  toLumberjack = false,
}) => {
  if (!objectName) {
    throw new Error('[analytics]: objectName cannot be empty');
  }

  if (!actionName) {
    throw new Error('[analytics]: actionName cannot be empty');
  }

  if (!screen) {
    throw new Error('[analytics]: screen cannot be empty');
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
  if (window.analytics && window.analytics.track) {
    window.analytics.track(eventName, {
      ...properties,
      screen,
      eventTimestamp,
      experiment_ID: getCookie('auth_source') === 'website' ? 'Signup_experiment_1' : 'none',
      device_type: window.innerWidth <= 1020 ? 'mweb' : 'dweb',
    });
  }

  if (toLumberjack) {
    sendToLumberjack({
      eventName,
      properties: {
        ...properties,
        screen,
        eventTimestamp,
      },
    });
  }
};
