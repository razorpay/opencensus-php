import winston, { Logger } from 'winston';
import logConfig from './winstonConfig';
import { getErrorMessage, getErrorStack } from '@apps/shell/src/server/utils/error-utils';
import os from 'os';
import { INSTANCE_TYPE, STAGE } from '@apps/shell/src/env';
import errorService from '@razorpay/universe-cli/errorService';

export interface LogType {
  message: string;
  moduleName?: string;
  context?: Record<string, unknown>;
}

interface ErrorLogType extends LogType {
  error: unknown;
  sentry?: boolean;
}

export type LogLevel = 'error' | 'warn' | 'info' | 'success';

export type GenericLogType<T extends LogLevel> = T extends 'error' ? ErrorLogType : LogType;

// Define custom log levels including 'success'
enum levels {
  ERROR = 'error',
  WARN = 'warn',
  INFO = 'info',
  SUCCESS = 'success',
}

const logParse = <T extends LogLevel>({
  message,
  moduleName,
  context,
  // @ts-ignore
  error,
  // @ts-ignore
  sentry,
  // @ts-ignore  // Will be only available in middlewares using req.shellLogger
  requestMeta = {},
}: GenericLogType<T>) => {
  const logObject = {
    message,
    moduleName,
    deployment_type: INSTANCE_TYPE,
    error: error
      ? {
          message: getErrorMessage(error),
          stack: getErrorStack(error),
        }
      : undefined,
    context,
    appMeta: {
      appName: 'dashboard-shell',
      hostName: os.hostname(),
      stage: STAGE,
      version: __APP_VERSION__,
    },
    requestMeta,
  };

  if (error && sentry) {
    errorService.captureError(error, {
      rank: errorService.ErrorRank.P0,
      tags: {
        moduleName,
        "x-request-id": requestMeta?.["x-request-id"] || "UNKNOWN",
        hostName: requestMeta?.hostName || "UNKNOWN",
        deployment_type: INSTANCE_TYPE,
      },
      extra: {
        message,
      },
    });
  }
  return logObject;
};

const winstonLogger: Logger = winston.createLogger(logConfig);

/**
 * Logger object with error, info, warn and success methods to be used in shell server.
 * @warning Only import this directly in bootstrap.ts file, once `shellLoggerMiddleware` is setup. Use it from `req.shellLogger` in other downstream middlewares.
 */
const logger = {
  error: (log: GenericLogType<'error'>): Logger => winstonLogger.log(levels.ERROR, logParse(log)),
  info: (log: GenericLogType<'info'>): Logger => winstonLogger.log(levels.INFO, logParse(log)),
  warn: (log: GenericLogType<'warn'>): Logger => winstonLogger.log(levels.WARN, logParse(log)),
  success: (log: GenericLogType<'success'>): Logger =>
    winstonLogger.log(levels.SUCCESS, logParse(log)), // Added success log level
};

export default logger;
