import { NextFunction, Request, Response } from 'express';
import { ShellError, ApiFailureInfo } from '@apps/shell/src/server/utils/error-utils';

/**
 * Type definition for individual middleware functions that can be executed concurrently.
 * Supports both sync and async middlewares with optional abort signal for cancellation.
 */
export type ChildMiddlewareType = (
  req: Request,
  res: Response,
  next?: NextFunction,
  signal?: AbortSignal,
) => Promise<void> | void;

/**
 * Handler function called after all concurrent middlewares complete.
 * Receives the results of all middleware executions for further processing.
 */
export type PostConcurrentResHandler = <T>(
  req: Request,
  res: Response,
  next: NextFunction,
  concurrentPromiseRes?: T[],
) => Promise<void>;

/**
 * Main middleware executor type that takes middlewares and a post-handler,
 * returning an Express middleware function.
 */
type MiddlewareType = (
  middlewares: ChildMiddlewareType[],
  postConcurrentResHandler: PostConcurrentResHandler,
) => (req: Request, res: Response, next: NextFunction) => void;

/**
 * Helper function to extract API failure information from an error object.
 * Standardizes error information for consistent debugging across all middleware failures.
 */
const extractApiFailureInfo = (error: any, middlewareName: string): ApiFailureInfo => {
  return {
    middlewareName,
    statusCode: error?.statusCode || 500,
    message: error?.message || 'Unknown error',
    dashboardBackendRequestId: error?.context?.dashboardBackendRequestId,
    apiPath: error?.context?.path,
    timestamp: new Date().toISOString(),
    moduleName: error?.moduleName,
    error: error,
  };
};

/**
 * Executes multiple middlewares concurrently and collects all errors for debugging.
 * Uses Promise.allSettled to gather both successes and failures.
 */
export const concurrentMiddlewareExecutor: MiddlewareType = (
  middlewares: ChildMiddlewareType[],
  postConcurrentResHandler: PostConcurrentResHandler,
) => {
  return (req, res, next) => {
    req.shellLogger.info({
      moduleName: '@concurrentMiddlewareExecutor',
      message: 'Executing middlewares concurrently',
    });

    const controller = new AbortController();
    const signal = controller.signal;

    // Use Promise.allSettled to collect ALL results (successes AND failures)
    Promise.allSettled(
      middlewares.map((middleware, index) => {
        const middlewareName = `middleware_${index + 1}`;

        // Wrap each middleware with comprehensive error information
        return Promise.resolve(middleware(req, res, next, signal))
          .then(() => ({
            success: true,
            middlewareName,
            timestamp: new Date().toISOString(),
          }))
          .catch((error: any) => ({
            success: false,
            middlewareName,
            error,
            failureInfo: extractApiFailureInfo(error, middlewareName),
            timestamp: new Date().toISOString(),
          }));
      }),
    )
      .then((results) => {
        const failures = results
          .filter(
            (
              result,
            ): result is PromiseFulfilledResult<{
              success: false;
              middlewareName: string;
              error: any;
              failureInfo: ApiFailureInfo;
              timestamp: string;
            }> => result.status === 'fulfilled' && !result.value.success,
          )
          .map((result) => result.value);

        const successes = results.filter(
          (
            result,
          ): result is PromiseFulfilledResult<{
            success: true;
            middlewareName: string;
            timestamp: string;
          }> => result.status === 'fulfilled' && result.value.success,
        );

        if (failures.length > 0) {
          const allFailureInfos = failures.map((f) => f.failureInfo);

          // Log comprehensive failure information for debugging
          req.shellLogger.warn({
            moduleName: '@concurrentMiddlewareExecutor',
            message: `Middleware execution failed - ${failures.length} failure(s), ${successes.length} success(es)`,
            context: {
              totalFailures: failures.length,
              failureDetails: allFailureInfos.map((f) => ({
                middleware: f.middlewareName,
                status: f.statusCode,
                requestId: f.dashboardBackendRequestId,
                path: f.apiPath,
                message: f.message,
              })),
            },
          });

          // Create error focused on displaying all failures (ErrorPage only uses apiFailures)
          const enhancedError = new ShellError({
            moduleName: '@concurrentMiddlewareExecutor',
            message: `${failures.length} middleware failure(s) occurred`,
            statusCode: 500, // Generic 500 since we're showing multiple failures
            context: {
              totalFailures: failures.length,
              totalSuccesses: successes.length,
            },
            apiFailures: allFailureInfos, // This is used by ErrorPage for detailed display
          });

          return next(enhancedError);
        }

        // All middlewares succeeded - continue with normal flow
        req.shellLogger.success({
          moduleName: '@concurrentMiddlewareExecutor',
          message: `Successfully executed all ${successes.length} middlewares`,
        });

        if (!postConcurrentResHandler) {
          throw new ShellError({
            moduleName: '@concurrentMiddlewareExecutor',
            message: 'postConcurrentResHandler is not defined',
            statusCode: 500,
          });
        }

        // Continue with successful results
        const successResults = successes.map(() => undefined); // Maintain original interface
        return postConcurrentResHandler(req, res, next, successResults);
      })
      .catch((error) => {
        // This should rarely happen with allSettled, but handle it gracefully
        req.shellLogger.warn({
          moduleName: '@concurrentMiddlewareExecutor',
          message: 'Unexpected error in middleware execution',
          context: {
            error: error.message,
          },
        });

        const fallbackError = new ShellError({
          moduleName: '@concurrentMiddlewareExecutor',
          message: `Unexpected middleware execution error: ${error.message}`,
          statusCode: 500,
          context: { originalError: error },
        });

        return next(fallbackError);
      });
  };
};
