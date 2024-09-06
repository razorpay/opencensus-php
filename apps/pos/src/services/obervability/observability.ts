import errorService from '@razorpay/universe-cli/errorService';
import {
  captureErrorOnAnalytics,
  capturePrometheusMetric,
} from '@dashboard/shared-utils/analytics';

let sentryHub = {} as ReturnType<typeof errorService.init> | undefined;

export const Metrics = {
  ERROR_COUNT: 'error.count',
  PAGE_VIEW: 'page.view',
};

const POS_ROUTES = [
  '/onboarding/new',
  '/merchantRegistration/mobileNumberVerify',
  '/merchantKyc/merchantKycRedirect',
  '/deviceSelection/deviceSelectionCatalog',
  '/deviceSelection/deviceCart',
  '/deviceSelection/deviceDeliveryAddress',
  '/paymentMethods/vasForm',
  '/paymentMethods/nachForm',
  '/agreementSigning/agreementMode',
  '/additionalDetails/merchantAdditionalDetails',
];

export const getPathForMetrics = (path = window?.location?.pathname): string => {
  const route = path.replace(/^\/app/, '');

  if (route === '/pos-sales') return route;

  // matches /pos-sales/onboarding/:id only //
  const onboardingRegex = new RegExp('^/pos-sales/onboarding/[a-zA-Z0-9]{14}$');

  if (onboardingRegex.test(route)) {
    return '/pos-sales/onboarding/id';
  } else {
    const paths = route.split('/');
    const routeKey = `/${paths[paths.length - 2]}/${paths[paths.length - 1]}`; // last two segments of the path //

    if (POS_ROUTES.includes(routeKey)) {
      return routeKey;
    }
  }

  // no match, return route itself //
  return route;
};

function initSentry(): ReturnType<typeof errorService.init> | undefined {
  let environment = window.APP_ENV;

  if (window.INSTANCE_TYPE === 'canary') {
    // in canary case, environment is set to canary instead of production
    environment = 'canary';
  }

  if (window.APP_ENV === 'production') {
    console.log('Initializing Sentry 🚔');
    try {
      sentryHub = errorService.init({
        version: window.__VERSION__ || 'UNKNOWN',
        environment: window.__VERSION__ ? environment : 'local',
        dsn: process.env.UNIVERSE_PUBLIC_SENTRY_DSN as string,
        shouldUseLocalSentryInstance: true,
        browserTracing: {
          tracingOrigins: ['dashboard.razorpay.com', /^\//],
        },
        tracesSampler: (samplingContext) => {
          // Possible values for operations are 'navigation' and 'pageload'
          // Web vitals can be collected only for pageload transactions, so we are sampling only those

          if (samplingContext?.transactionContext?.op === 'pageload') {
            return 0.2;
          } else {
            return 0;
          }
        },
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
      });
      console.log('Sentry Initialized 💅🏻');
    } catch (e) {
      console.error('Error while initializing sentry 🚨', e);
    }
  }

  if (window.rzp_user?.user?.id) {
    const tags = {
      app: process.env.UNIVERSE_PUBLIC_APP_NAME,
      agentId: window.rzp_user?.user?.id,
      merchantId: window.rzp_user?.id,
    };

    errorService.setUserContext({
      userId: window.rzp_user?.id, // merchant Id
      tags,
      sentryHub: sentryHub?.sentryHub,
    });
  }

  return sentryHub;
}

export default initSentry;
