import type { ErrorRequestHandler } from 'express';
import { renderErrorPage } from './ErrorPage';
import { SHELL_ERROR_TRACE, ShellError } from '@apps/shell/src/server/utils/error-utils';
import { redirectToPhp } from '@apps/shell/src/server/utils';

export const shellErrorCaptureMiddleware = (): ErrorRequestHandler => {
  return (error: ShellError, req, res, next) => {
    if (res.headersSent) {
      return next(error);
    }

    const DEFAULT_ERROR_CODE = 500;
    const moduleName = `@shellErrorCaptureMiddleware${
      Boolean(error?.moduleName)
        ? `:${error.moduleName}:${error?.statusCode || DEFAULT_ERROR_CODE}`
        : `:${error?.statusCode || DEFAULT_ERROR_CODE}`
    }`;
    const message = error.message || 'Internal Server Error';

    if (error?.statusCode === 401) {
      if (Boolean(req?.shellLogger)) {
        req.shellLogger.warn({
          moduleName,
          message,
          context: {
            trace: SHELL_ERROR_TRACE.UNAUTHORIZED_USER,
            errorCode: 401,
            path: req.originalUrl || req.url,
            dashboardBackendRequestId: error.context?.dashboardBackendRequestId,
          },
        });
      }
      return redirectToPhp(req, res);
    }

    const pushToSentry = ![404].includes(error?.statusCode || DEFAULT_ERROR_CODE);

    if (Boolean(req?.shellLogger)) {
      req.shellLogger.error({
        moduleName,
        error,
        message,
        sentry: pushToSentry,
        context: {
          trace: error?.trace || SHELL_ERROR_TRACE.INTERNAL_SERVER_ERROR,
          errorCode: error?.statusCode || DEFAULT_ERROR_CODE,
          path: error.context?.path || req.originalUrl || req.url,
          dashboardBackendRequestId: error.context?.dashboardBackendRequestId,
        },
      });
    }

    return renderErrorPage(req, res, error);
  };
};
