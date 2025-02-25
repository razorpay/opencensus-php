import errorService from '@razorpay/universe-cli/errorService';
import { IS_PRODUCTION, STAGE } from '../../env';
import { shellLogger } from './shellLogger';

if (IS_PRODUCTION && __APP_VERSION__) {
  shellLogger.info({
    message: 'Sentry Initialization Started - Shell Server',
    moduleName: '@server/utils/sentry',
    context: {
      sentryVersion: __SHELL_SERVER_SENTRY_VERSION__,
      __APP_VERSION__: __APP_VERSION__,
    },
  });

  errorService.init({
    dsn: __SHELL_SENTRY_DSN__,
    environment: STAGE,
    version: __SHELL_SERVER_SENTRY_VERSION__,
    dist: __APP_VERSION__,
    profilesSampleRate: 1,
    ignoreTransactions: [
      'GET /app-health',
      'GET /app-health/',
      'GET /app-versions',
      'GET /app-versions/',
      'GET /metrics',
      'GET /metrics/',
    ],
    tracePropagationTargets: [/^https:\/\/(?:.+\.)?(razorpay|curlec)\.(com)(\/|\/app.*)?$/],
    tracesSampler: (options: any) => {
      const sentryOp = options?.attributes?.['sentry.op'];
      switch (sentryOp) {
        case 'app.bootstrap':
          return 1;
        case 'http.client':
        case 'http.server':
          return 0.3;
        case 'middleware.express':
        case 'middleware.handle':
          return 0.1;
        case 'view.render':
        case 'template.init':
        case 'template.render':
          return 0.05;
        case 'subprocess.wait':
        case 'subprocess.communicate':
        case 'file':
        case 'graphql.request':
        case 'graphql.query':
        case 'graphql.mutation':
        case 'graphql.subscription':
          return 0.01;
        default:
          return 0;
      }
    },
  });

  shellLogger.success({
    message: 'Sentry Initialized - Shell Server',
    moduleName: '@server/utils/sentry',
    context: {
      version: __SHELL_SERVER_SENTRY_VERSION__,
    },
  });
} else {
  if (IS_PRODUCTION) {
    shellLogger.error({
      message: 'Sentry Initialization Failed - Shell Server',
      error: 'Unable to validate envs required for initialization',
      moduleName: '@server/utils/sentry',
      context: {
        __APP_VERSION__: __APP_VERSION__,
        version: __SHELL_SERVER_SENTRY_VERSION__,
      },
    });
  }
}
