import { NextFunction, Request, Response } from 'express';

export type ChildMiddlewareType = (
  req: Request,
  res: Response,
  next?: NextFunction,
  signal?: AbortSignal,
) => void;

export type PostConcurrentResHandler = <T>(
  req: Request,
  res: Response,
  next: NextFunction,
  concurrentPromiseRes?: T[],
) => Promise<void>;

type MiddlewareType = (
  middlewares: ChildMiddlewareType[],
  postConcurrentResHandler: PostConcurrentResHandler,
) => (req: Request, res: Response, next: NextFunction) => void;

/**
 * Executes multiple middlewares concurrently and proceeds to the next middleware
 * only after all promises resolve successfully.
 *
 * **Note:** Ensure that errors in the passed middlewares are not handled internally.
 * If they are handled, the error will not propagate and the middleware execution
 * will resolve as if successful. For proper error handling, let errors propagate
 * to the `catch` block of this executor.
 *
 * @example
 * const middleware1: ChildMiddlewareType = async (req, res) => {
 *   // Middleware logic
 * };
 *
 * const middleware2: ChildMiddlewareType = async (req, res) => {
 *   // Middleware logic
 * };
 *
 * app.use(
 *   concurrentMiddlewareExecutor([middleware1, middleware2]),
 *   (req, res) => {
 *     res.send('All middlewares executed successfully');
 *   },
 * );
 */
export const concurrentMiddlewareExecutor: MiddlewareType = (
  middlewares: ChildMiddlewareType[],
  postConcurrentResHandler: PostConcurrentResHandler,
) => {
  return (req, res, next) => {
    req.shellLogger.info({
      moduleName: '@concurrentMiddlewareExecutor',
      message: 'Executing middlewares in concurrent mode',
    });
    const controller = new AbortController();
    const signal = controller.signal;

    Promise.all(middlewares.map((middleware) => middleware(req, res, next, signal)))
      .then((concurrentPromiseRes) => {
        req.shellLogger.success({
          moduleName: '@concurrentMiddlewareExecutor',
          message: 'Successfully executed middlewares',
        });

        if (!Boolean(postConcurrentResHandler)) {
          throw new Error('postConcurrentResHandler is not defined');
        }

        return postConcurrentResHandler(req, res, next, concurrentPromiseRes);
      })
      .catch((error) => {
        req.shellLogger.warn({
          moduleName: '@concurrentMiddlewareExecutor',
          message: 'Failed executing middlewares',
        });

        return next(error);
      });
  };
};
