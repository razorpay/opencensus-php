import errorService from '@razorpay/universe-cli/errorService';

let sentryHub = {} as ReturnType<typeof errorService.init> | undefined;

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
        beforeSend: () => {
          // TODO: capture in Prometheus as well
        },
      });
      console.log('Sentry Initialized 💅🏻');
    } catch (e) {
      console.error('Error while initializing sentry 🚨', e);
    }
  }

  return sentryHub;
}

export default initSentry;
