import { getMandatoryHeaders, getPhpBaseUrl } from '@apps/shell/src/server/utils';
import { shellFetch, HeadersInit } from '@apps/shell/src/server/services/shellFetch';
import { ShellError } from '@apps/shell/src/server/utils/error-utils';
import { SHELL_EXTERNAL_API_ROUTES } from '../../configs';
import { NextFunction, Request, Response } from 'express';

type UserSessionFetchMiddleware = () => (req: Request, res: Response, next: NextFunction) => void;

/** Try fetching user, if there's a response, then session is authenticated
 *  else makes relevant redirects
 */
export const userSessionFetchMiddleware: UserSessionFetchMiddleware =
  () =>
  async (req, res, next): Promise<void> => {
    req.shellLogger.info({
      message: `User session data fetch started...`,
      moduleName: '@userSessionFetchMiddleware',
    });

    const dashboardBackendBaseUrl = getPhpBaseUrl(req);
    let dashboardBackendRequestId: any;

    return await shellFetch(`${dashboardBackendBaseUrl}${SHELL_EXTERNAL_API_ROUTES.USER_SESSION}`, {
      method: 'GET',
      headers: getMandatoryHeaders(req) as unknown as HeadersInit,
    })
      .then((response) => {
        const setCookies = response.headers.raw()['set-cookie'];
        dashboardBackendRequestId = response.headers.get('x-request-id');

        if (setCookies) {
          res.locals['x-set-cookie'] = setCookies;
          req.shellLogger.info({
            message: `Set-Cookie headers retrieved and stored in locals.`,
            moduleName: '@userSessionFetchMiddleware',
            context: {
              dashboardBackendRequestId,
            },
          });
        } else {
          req.shellLogger.warn({
            message: `No Set-Cookie headers found.`,
            moduleName: '@userSessionFetchMiddleware',
            context: {
              dashboardBackendRequestId,
            },
          });
        }

        const { status } = response;
        req.shellLogger.info({
          message: `User session data fetch status: ${response.status}`,
          moduleName: '@userSessionFetchMiddleware',
          context: {
            dashboardBackendRequestId,
          },
        });
        if (status === 200) {
          return response.json();
        } else {
          throw new ShellError({
            moduleName: '@userSessionFetchMiddleware',
            message: `User session data fetch failed.`,
            statusCode: status,
            context: {
              dashboardBackendRequestId,
              response,
            },
          });
        }
      })
      .then(async (response) => {
        const { data, success } = response as any;
        if (success) {
          res.locals.user_session = {
            ...data,
          };
          req.shellLogger.success({
            message: `User session data fetched.`,
            moduleName: '@userSessionFetchMiddleware',
            context: {
              currentUserMid: res.locals.user_session?.merchant_id,
              userId: res.locals.user_session?.user_id,
              name: res.locals.user_session?.name,
              dashboardBackendRequestId,
            },
          });
          return next();
        } else {
          throw new ShellError({
            moduleName: '@userSessionFetchMiddleware',
            message: `Invalid API Response`,
            context: {
              dashboardBackendRequestId,
              response,
            },
          });
        }
      })
      .catch((error) => {
        req.shellLogger.error({
          moduleName: '@userSessionFetchMiddleware',
          message: 'Failed to fetch user session data',
          context: {
            dashboardBackendRequestId,
          },
          error,
          sentry: false,
        });

        return next(error);
      });
  };
