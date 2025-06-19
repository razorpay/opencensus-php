// Note: web/js/common/utils/observability.js
import errorService from '@razorpay/universe-cli/errorService';
import {
  captureErrorOnAnalytics,
  capturePrometheusMetric,
  DASHBOARD_ROUTES,
  getPathForMetrics,
  Metrics,
} from '@libs/shared-utils';
import { STAGE } from '../../env';
import { useEffect } from 'react';
import {
  useLocation,
  useNavigationType,
  createRoutesFromChildren,
  matchRoutes,
} from 'react-router-dom';

const infoEventsToBeIgnored = [
  'ReportingObserver [deprecation]',
  'ReportingObserver [intervention]',
];

function shouldSkipNonExceptions({ event }: any) {
  if (event.level === 'info') {
    for (const infoEventMessage of infoEventsToBeIgnored) {
      if (event?.message?.includes?.(infoEventMessage)) {
        return true;
      }
    }
  }
  return false;
}

const extractPersona = () => {
  const userData = window.rzp_user || {};

  return {
    role: userData.role || null,

    // Merchant related
    isSubMerchant: userData.isSubMerchant || null,
    isAutoKycDone: userData.isAutoKycDone || null,
    activationStatus: userData.activation_status || null,
    activationProgress: userData.verification?.activation_progress || null,
    verificationStatus: userData.verification?.status || null,
    disabledReason: userData.verification?.disabled_reason || null,
    preSignupComplete: userData.pre_signup_complete || null,
    merchantId: userData.current || null,

    // User related
    emailVerified: userData.user?.email_verified || null,
    mobileVerified: userData.user?.contact_mobile_verified || null,
    accountLocked: userData.user?.account_locked || null,
    secondFactorAuth: userData.user?.second_factor_auth || null,
    signupViaEmail: userData.user?.signup_via_email || null,
    userId: userData.user?.id || null,

    // Partner related
    partnerType: userData.partner_type || null,

    // Multi-merchant related
    merchantsMapped: userData.merchants ? Object.keys(userData.merchants).length : 0,
    countryCode: userData.merchant?.country_code || null,
  };
};

export function initSentry(appName: 'la-dashboard' | 'payments-dashboard' | 'one-dashboard') {
  if (__APP_VERSION__) {
    try {
      const EXTRA_KEY = 'ROUTE_TO';

      const transport = errorService.generateMultiplexedTransport((args: any) => {
        const event = args.getEvent(['event', 'transaction', 'replay_event']);
        if (
          event &&
          event.extra &&
          EXTRA_KEY in event.extra &&
          Array.isArray(event.extra[EXTRA_KEY])
        ) {
          return event.extra[EXTRA_KEY];
        }
        return [];
      });

      errorService.init({
        version: __SHELL_CLIENT_SENTRY_VERSION__,
        environment: STAGE,
        dsn: __SHELL_SENTRY_DSN__,
        dist: __APP_VERSION__,
        profilesSampleRate: 1,
        replaysSessionSampleRate: 0.01,
        replaysOnErrorSampleRate: 0.2,
        transport,
        ignoreErrors: ['ResizeObserver loop limit exceeded'],
        routerIntegrations: [
          errorService.reactRouterV6BrowserTracingIntegration({
            useEffect,
            useLocation,
            useNavigationType,
            createRoutesFromChildren,
            matchRoutes,
          }),
        ],
        tracePropagationTargets: [/^https:\/\/(?:.+\.)?(razorpay|curlec)\.(com)(\/|\/app.*)?$/],
        beforeSend: (event: any, hint: any) => {
          // this will be used to identify the errors when swc is enabled
          event.tags = { ...event.tags, isWebpackWithSwc: __IS_WEBPACK_WITH_SWC__ };

          if (shouldSkipNonExceptions({ event })) {
            return null;
          }

          const error = hint?.originalException;
          if (error?.code === 'UNKNOWN_ERROR_CODE') {
            return null;
          }

          const ignoredErrors = ['Illegal invocation'];
          if (error?.message && ignoredErrors.some((err) => error.message.includes(err))) {
            return null;
          }

          captureErrorOnAnalytics(event, hint);

          let module_metadata;

          if (Array.isArray(event?.exception?.values?.[0]?.stacktrace?.frames)) {
            const frames = event.exception.values[0].stacktrace.frames;
            // Find the last frame with module metadata containing a DSN
            const routeTo = frames
              .filter((frame: any) => frame.module_metadata && frame.module_metadata.dsn)
              .map((v: any) => v.module_metadata)
              .slice(-1); // using top frame only - you may want to customize this according to your needs

            if (routeTo.length) {
              module_metadata = routeTo[0];

              event.extra = {
                ...event.extra,
                [EXTRA_KEY]: routeTo,
              };
            }
          }

          if (['error', 'fatal'].includes(event?.level)) {
            capturePrometheusMetric({
              name: Metrics.ERROR_COUNT,
              labels: {
                rank: event?.rank ?? event?.tags?.rank,
                level: event?.level,
                pathname: getPathForMetrics(
                  window?.location?.pathname as keyof typeof DASHBOARD_ROUTES,
                ),
                module_metadata,
              },
            });
          }

          return event;
        },
        tracesSampler: (options: any) => {
          const sentryOp = options?.attributes?.['sentry.op'];
          switch (sentryOp) {
            case 'pageload':
            case 'browser.paint':
              return 0.2;
            case 'measure':
            case 'ui.react.mount':
              return 0.1;
            case 'ui.render':
            case 'ui.task':
            case 'ui.react.render':
              return 0.01;
            case 'ui.react.update':
            case 'ui.update':
            case 'ui.action':
              return 0.001;
            case 'resource.script':
            case 'resource.link':
              return 0.0001;
            case 'navigation':
              return 0.00001;
            case 'http.client':
            case 'http.graphql.query':
            case 'http.graphql.mutation':
            case 'http.graphql.subscription':
              return 0.000001;
            default:
              return 0;
          }
        },
      });

      console.log({
        moduleName: '@initSentry',
        message: 'Sentry Initialized.',
      });
    } catch (e) {
      console.error({
        moduleName: '@initSentry',
        message: 'Error while initializing sentry',
        error: e,
      });
    }

    if (window.rzp_user?.current) {
      const tags = {
        app: appName,
        // @ts-ignore
        protocol: performance?.getEntriesByType?.('navigation')?.[0]?.nextHopProtocol,
        deployment_type: STAGE,
        ...extractPersona(),
      };

      errorService.setUserContext({
        userId: window.rzp_user?.current,
        tags,
      });
    }
  }
}
