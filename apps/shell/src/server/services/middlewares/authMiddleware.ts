import { getMandatoryHeaders, getPhpBaseUrl } from '@apps/shell/src/server/utils';
import { shellFetch, HeadersInit } from '@apps/shell/src/server/services/shellFetch';
import { type ChildMiddlewareType } from './concurrentMiddlewareExecutor';
import { ShellError } from '@apps/shell/src/server/utils/error-utils';
import { SHELL_EXTERNAL_API_ROUTES } from '../../configs';

type AuthMiddleware = () => ChildMiddlewareType;

/**
 * Fetches user data and stores it in res.locals._user. This has high latency and should be avoided.
 * @deprecated This api call should not be used in the future. Use optimizedUserFetchMiddleware instead.
 */
export const authMiddleware: AuthMiddleware =
  () =>
  async (req, res): Promise<void> => {
    req.shellLogger.info({
      message: `User data fetch started...`,
      moduleName: '@authMiddleware',
    });

    const dashboardBackendBaseUrl = getPhpBaseUrl(req);
    let dashboardBackendRequestId: any;

    return await shellFetch(`${dashboardBackendBaseUrl}${SHELL_EXTERNAL_API_ROUTES.USER}`, {
      method: 'GET',
      headers: getMandatoryHeaders(req) as unknown as HeadersInit,
    })
      .then((response) => {
        const setCookies = response.headers.raw()['set-cookie'];
        dashboardBackendRequestId = response.headers.get('x-request-id');

        if (setCookies) {
          res.locals['x-set-cookie'] = setCookies;
          // Store cookies for forwarding to subsequent middleware and API calls
          req.forward_cookies = setCookies;
          
          req.shellLogger.info({
            message: `Set-Cookie headers retrieved and stored in locals.`,
            moduleName: '@authMiddleware',
            context: {
              dashboardBackendRequestId,
            },
          });
        } else {
          req.shellLogger.warn({
            message: `No Set-Cookie headers found.`,
            moduleName: '@authMiddleware',
            context: {
              dashboardBackendRequestId,
            },
          });
        }

        const { status } = response;
        req.shellLogger.info({
          message: `User data fetch status: ${response.status}`,
          moduleName: '@authMiddleware',
          context: {
            dashboardBackendRequestId,
          },
        });
        if (status === 200) {
          return response.json();
        } else {
          throw new ShellError({
            moduleName: '@authMiddleware',
            message: `User data fetch failed.`,
            statusCode: status,
            context: {
              dashboardBackendRequestId,
            },
          });
        }
      })
      .then((response) => {
        const { data, success } = response as any;
        if (success) {
          res.locals.legacy_user = data;
          req.shellLogger.success({
            message: `User data fetched.`,
            moduleName: '@authMiddleware',
            context: {
              currentUserMid: res.locals.user.current,
              dashboardBackendRequestId,
            },
          });
          return;
        } else {
          throw new ShellError({
            moduleName: '@authMiddleware',
            message: `Invalid API Response`,
            context: {
              dashboardBackendRequestId,
            },
          });
        }
      });
  };
