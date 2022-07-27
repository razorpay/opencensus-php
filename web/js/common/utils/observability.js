import errorService from '@razorpay/universe-utils/errorService';
import { captureErrorOnAnalytics } from 'common/utils/analytics';

export function initSentry(appName) {
  let environment = window.APP_ENV;

  if (window.INSTANCE_TYPE === 'canary') {
    // in canary case, environment is set to canary instead of production
    environment = 'canary';
  }

  if (window.APP_ENV === 'production') {
    try {
      errorService.init({
        version: window.__VERSION__ || 'UNKNOWN',
        environment: window.__VERSION__ ? environment : 'local',
        dsn: window.SENTRY_DSN,
        beforeSend: (event, hint) => {
          if (hint?.originalException?.code === 'UNKNOWN_ERROR_CODE') {
            return null;
          }

          captureErrorOnAnalytics(event, hint);

          return event;
        },
        browserTracing: {
          tracingOrigins: ['dashboard.razorpay.com', /^\//],
        },
        tracesSampleRate: 0.05,
      });
    } catch (e) {
      console.error('Error while initializing sentry');
    }
  }

  if (window.rzp_user?.current) {
    errorService.setUserContext({
      userId: window.rzp_user?.current,
      tags: {
        app: appName,
        role: window.rzp_user?.role,
      },
    });
  }
}
