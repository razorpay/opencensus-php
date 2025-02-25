import type { Request, Response, NextFunction } from 'express';
import { shellLogger } from '@apps/shell/src/server/utils';

export const shellLoggerMiddleware = () => {
  return (req: Request, _: Response, next: NextFunction) => {
    const augmentedLogger = Object.keys(shellLogger).reduce((acc, method) => {
      type LoggerMethod = keyof typeof shellLogger;

      if (method in shellLogger) {
        acc[method as LoggerMethod] = (log: any) =>
          shellLogger[method as LoggerMethod]({
            ...log,
            requestMeta: {
              "x-request-id": req?.x_shell_request_id,
              hostName: req?.hostname,
            },
          });
      }

      return acc;
    }, {} as typeof shellLogger);

    req.shellLogger = augmentedLogger;

    return next();
  };
};
