// TODO: Migrate this usage to shell
import {
  Metrics,
  captureErrorOnAnalytics,
  capturePrometheusMetric,
  getPathForMetrics,
} from '@libs/shared-utils';
import errorService from '@razorpay/universe-cli/errorService';
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

function shouldSkipNonExceptions({ event }) {
  if (event.level === 'info') {
    for (const infoEventMessage of infoEventsToBeIgnored) {
      if (event.message?.includes?.(infoEventMessage)) {
        return true;
      }
    }
  }
  return false;
}

function extractPersona() {
  const {
    role,
    current: merchantId,
    isSubMerchant,
    isAutoKycDone,
    activation_status: activationStatus,
    verification: {
      activation_progress: activationProgress,
      status: verificationStatus,
      disabled_reason: disabledReason,
    } = {},
    pre_signup_complete: preSignupComplete,
    user: {
      id: userId,
      email_verified: emailVerified,
      contact_mobile_verified: mobileCerified,
      account_locked: accountLocked,
      second_factor_auth: secondFactorAuth,
      signup_via_email: signupViaEmail,
    } = {},
    partner: { partner_type: partnerType } = {},
    merchants,
    merchant: { country_code } = {},
  } = window.rzp_user || {};

  return {
    role: role ?? null,

    // Merchant related
    isSubMerchant: isSubMerchant ?? null,
    isAutoKycDone: isAutoKycDone ?? null,
    activationStatus: activationStatus ?? null,
    activationProgress: activationProgress ?? null,
    verificationStatus: verificationStatus ?? null,
    disabledReason: disabledReason ?? null,
    preSignupComplete: preSignupComplete ?? null,
    merchantId: merchantId ?? null,

    // User related
    emailVerified: emailVerified ?? null,
    mobileCerified: mobileCerified ?? null,
    accountLocked: accountLocked ?? null,
    secondFactorAuth: secondFactorAuth ?? null,
    signupViaEmail: signupViaEmail ?? null,
    userId: userId ?? null,

    // Partner related
    partnerType: partnerType ?? null,

    // Multi-merchant related
    merchantsMapped: (merchants && Object.keys(merchants).length) || 0,
    country_code: country_code ?? null,
  };
}

export function initSentry(appName) {
  if (['production', 'canary'].includes(__STAGE__) && __WEB_NEXUS_SENTRY_VERSION__) {
    try {
      const EXTRA_KEY = 'ROUTE_TO';

      const transport = errorService.generateMultiplexedTransport((args) => {
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
        version: __WEB_NEXUS_SENTRY_VERSION__,
        environment: __STAGE__,
        dsn: __WEB_NEXUS_SENTRY_DSN__,
        dist: __APP_VERSION__,
        profilesSampleRate: 1,
        ignoreErrors: ['ResizeObserver loop limit exceeded'],
        tracePropagationTargets: [/^https:\/\/(?:.+\.)?(razorpay|curlec)\.(com)(\/|\/app.*)?$/],
        transport,
        replaysSessionSampleRate: 0.01,
        replaysOnErrorSampleRate: 0.2,
        routerIntegrations: [
          errorService.reactRouterV6BrowserTracingIntegration({
            useEffect,
            useLocation,
            useNavigationType,
            createRoutesFromChildren,
            matchRoutes,
          }),
        ],
        beforeSend: (event, hint) => {
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
              .filter((frame) => frame.module_metadata && frame.module_metadata.dsn)
              .map((v) => v.module_metadata)
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
                pathname: getPathForMetrics(window?.location?.pathname),
                module_metadata,
              },
            });
          }

          return event;
        },
        tracesSampler: (options) => {
          const sentryOp = options?.attributes?.['sentry.op'];
          switch (sentryOp) {
            case 'pageload':
            case 'browser.paint':
              return 0.3;
            case 'measure':
            case 'ui.react.mount':
              return 0.1;
            case 'ui.render':
            case 'ui.task':
            case 'ui.react.render':
              return 0.01;
            case 'http.client':
            case 'http.graphql.query':
            case 'http.graphql.mutation':
            case 'http.graphql.subscription':
            case 'ui.react.update':
            case 'ui.update':
            case 'ui.action':
              return 0.001;
            case 'resource.script':
            case 'resource.link':
            case 'navigation':
              return 0.0001;
            default:
              return 0;
          }
        },
      });
    } catch (e) {
      console.error('Error while initializing sentry');
    }
  }

  if (window.rzp_user?.current) {
    const tags = {
      app: appName,
      protocol: performance?.getEntriesByType?.('navigation')?.[0]?.nextHopProtocol,
      deployment_type: __STAGE__,
      ...extractPersona(),
    };

    errorService.setUserContext({
      userId: window.rzp_user?.current,
      tags,
    });
  }
}
