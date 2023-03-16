import errorService from '@razorpay/universe-utils/errorService';
import { captureErrorOnAnalytics, capturePrometheusMetric, Metrics } from 'common/utils/analytics';
import { getPathForMetrics } from 'common/new-ui/ErrorBoundary/utils';

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

          if (event?.level === 'error') {
            capturePrometheusMetric({
              name: Metrics.ERROR_COUNT,
              labels: {
                rank: event?.rank ?? event?.tags?.rank,
                pathname: getPathForMetrics(window?.location?.pathname),
              },
            });
          }

          return event;
        },
        browserTracing: {
          tracingOrigins: ['dashboard.razorpay.com', /^\//],
        },
        tracesSampler: (samplingContext) => {
          // Possible values for operation is 'navigation' and 'pageload'
          // 80% of the current transactions are navigation transactions which we are note interested in
          // Web vitals can be collected only for pageload transactions, so we are sampling only those

          if (samplingContext?.transactionContext?.op === 'pageload') {
            return 0.2;
          } else {
            return 0;
          }
        },
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
        protocol: performance?.getEntriesByType?.('navigation')?.[0]?.nextHopProtocol,
        deployment_type: window.INSTANCE_TYPE,
      },
    });
  }
}
