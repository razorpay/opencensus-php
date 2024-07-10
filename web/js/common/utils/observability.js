import errorService from '@razorpay/universe-utils/errorService';
import { captureErrorOnAnalytics, capturePrometheusMetric, Metrics } from 'common/utils/analytics';
import { getPathForMetrics } from 'common/new-ui/ErrorBoundary/utils';

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
  let environment = window.APP_ENV;

  if (window.INSTANCE_TYPE === 'canary') {
    // in canary case, environment is set to canary instead of production
    environment = 'canary';
  } else if (process.env.REDIRECTOR) {
    // in canary case, environment is set to canary instead of production
    environment = 'redirector';
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
    const tags = {
      app: appName,
      protocol: performance?.getEntriesByType?.('navigation')?.[0]?.nextHopProtocol,
      deployment_type: window.INSTANCE_TYPE,
      ...extractPersona(),
    };

    errorService.setUserContext({
      userId: window.rzp_user?.current,
      tags,
    });
  }
}
