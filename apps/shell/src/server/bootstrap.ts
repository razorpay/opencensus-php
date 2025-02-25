import errorService from '@razorpay/universe-cli/errorService';
import cluster from 'cluster';
import compression from 'compression';
import cookieParser from 'cookie-parser';
import cors from 'cors';
import express from 'express';
import expressStaticGzip from 'express-static-gzip';
import helmet from 'helmet';
import os from 'os';
import slashes from 'connect-slashes';
import { STAGE, SERVER_PORT, APP_ENV } from '@apps/shell/src/env';
import {
  concurrentMiddlewareExecutor,
  identityMiddleware,
  internalEndpointProxyMiddleware,
  localShellRedirectMiddleware,
  morganStreamMiddleware,
  orgMiddleware,
  proxyMiddleware,
  renderMiddleware,
  requestIdMiddleware,
  shellErrorCaptureMiddleware,
  shellLoggerMiddleware,
  userAgentMiddleware,
  merchantDetailsFetchMiddleware,
  merchantExperimentsFetchMiddleware,
  userSessionFetchMiddleware,
  merchantFeaturesFetchMiddleware,
  merchantSplitzExperimentsFetchMiddleware,
  merchantTagsFetchMiddleware,
  optimizedUserFetchMiddleware,
  merchantSplitzExperimentV2Middleware,
  merchantConfigStoreFetchMiddleware,
  shellSplitzMiddleware
} from '@apps/shell/src/server/services/middlewares';
import prom from '@apps/shell/src/server/services/promMetrics';
import { getAppHealth, getAppVersions } from '@apps/shell/src/server/services/routes';
import { shellLogger } from '@apps/shell/src/server/utils';
import { SHELL_SERVER_ROUTES } from '@apps/shell/src/server/configs';
import { SHELL_ERROR_TRACE, ShellError } from '@apps/shell/src/server/utils/error-utils';
import { mainServer } from '@apps/shell/src/server/core';
import { preRenderHandler } from './services/handlers/preRenderHandler';

try {
  const numCPUs = os.cpus().length;
  const maxCPUs = numCPUs - 2;
  const isDev = STAGE === 'development';
  const isInternalEndpointProxyRouteEnabled = false;

  if (cluster.isPrimary && isDev) {
    shellLogger.info({
      message: `Starting primary cluster...`,
      moduleName: '@bootstrap',
    });

    shellLogger.info({
      message: `Using ${maxCPUs} out of ${numCPUs} available CPUs`,
      moduleName: '@bootstrap',
    });

    for (let i = 0; i < Math.min(numCPUs, maxCPUs); i++) {
      cluster.fork();
    }

    cluster.on('exit', (worker, code, signal) => {
      shellLogger.warn({
        message: `Worker ${worker.process.pid} died. Restarting...`,
        moduleName: '@bootstrap',
        context: {
          code,
          signal,
        },
      });
      cluster.fork();
    });
  } else {
    const buildDirectory = 'build';

    const app = express();

    if (isDev) {
      app.use(proxyMiddleware());
    }
    app.set('trust proxy', true);

    app.use(
      helmet({
        dnsPrefetchControl: false,
      }),
    );

    app.use(
      express.json({
        limit: '50mb',
      }),
    );

    app.use(
      express.urlencoded({
        limit: '50mb',
        extended: true,
      }),
    );

    if (!isDev) {
      app.get(SHELL_SERVER_ROUTES.PROM_METRICS, prom.metricsHandler);
    }

    app.use(cookieParser());
    app.use(requestIdMiddleware());
    app.use(shellLoggerMiddleware());
    app.use(morganStreamMiddleware());
    app.use(localShellRedirectMiddleware());
    app.use(compression());
    app.use(slashes(false));
    app.use(userAgentMiddleware());

    if (!isDev) {
      prom.setDefaultMetrics();
      app.use(prom.measureRequestDurationsMiddleware());
    }

    app.get(SHELL_SERVER_ROUTES.APP_HEALTH, getAppHealth);

    app.get(
      SHELL_SERVER_ROUTES.SERVICE_WORKER,
      expressStaticGzip(buildDirectory, {
        index: false,
        enableBrotli: true,
        orderPreference: ['br', 'gzip'],
      }),
    );

    app.use(
      SHELL_SERVER_ROUTES.DEFAULT_ASSETS,
      cors(),
      expressStaticGzip(buildDirectory, {
        index: false,
        enableBrotli: true,
        orderPreference: ['br', 'gzip'],
        serveStatic: {
          maxAge: '365 days',
        },
      }),
    );

    app.use(
      SHELL_SERVER_ROUTES.STATIC_ASSETS,
      cors(),
      expressStaticGzip('static', {
        index: false,
        enableBrotli: true,
        orderPreference: ['br', 'gzip'],
        serveStatic: {
          maxAge: '365 days',
        },
      }),
    );

    if (!isDev) {
      app.get(SHELL_SERVER_ROUTES.APP_VERSIONS, getAppVersions);
    }

    if (!isDev && isInternalEndpointProxyRouteEnabled) {
      app.get(SHELL_SERVER_ROUTES.APP_INTERNAL_PROXY, internalEndpointProxyMiddleware());
    }

    app.get(
      [SHELL_SERVER_ROUTES.STRICT_FRONTEND_APPS, SHELL_SERVER_ROUTES.FRONTEND_APPS],
      userSessionFetchMiddleware(),
      concurrentMiddlewareExecutor(
        [
          merchantDetailsFetchMiddleware(),
          merchantExperimentsFetchMiddleware(),
          merchantFeaturesFetchMiddleware(),
          merchantSplitzExperimentsFetchMiddleware(),
          merchantTagsFetchMiddleware(),
          merchantConfigStoreFetchMiddleware(),
          optimizedUserFetchMiddleware(),
          merchantSplitzExperimentV2Middleware(),
          orgMiddleware(),
          shellSplitzMiddleware(),
        ],
        preRenderHandler,
      ),
      identityMiddleware(),
      renderMiddleware(),
    );

    if (isDev) {
      app.get(SHELL_SERVER_ROUTES.DEV_INDEX_ROUTE, (_, res) => {
        return res.status(301).redirect(SHELL_SERVER_ROUTES.STRICT_FRONTEND_APPS);
      });
    }

    app.use((req) => {
      throw new ShellError({
        statusCode: 404,
        moduleName: '@bootstrap',
        message: `x-request-id=${req.x_shell_request_id} | Invalid: ${req.originalUrl}`,
        trace: SHELL_ERROR_TRACE.PAGE_NOT_FOUND,
      });
    });

    /**
     * errorHandlerMiddleware should be present after all your routes and middleware
     * If you have other error handlers, ensure that errorHandlerMiddleware is present before them
     */
    errorService.setupErrorHandler(app as any);

    app.use(shellErrorCaptureMiddleware());

    const shellCore = mainServer(app);

    shellCore.listen(SERVER_PORT, () => {
      shellLogger.success({
        message: `Worker ${process.pid} is listening at PORT:${SERVER_PORT}`,
        moduleName: '@bootstrap',
        context: {
          APP_ENV,
        },
      });
    });

    // use for triggering a restart in development with Nodemon
    process.once('SIGUSR2', () => {
      shellCore.close(() => {
        shellLogger.warn({
          message: 'Server closed for Nodemon restart',
          moduleName: '@bootstrap',
          context: {
            processId: process.pid,
          },
        });
        process.kill(process.pid, 'SIGUSR2');
      });
    });
  }
} catch (error) {
  shellLogger.error({
    moduleName: '@bootstrap',
    error,
    sentry: true,
    message: '[P0] Critical crash!',
  });
}
