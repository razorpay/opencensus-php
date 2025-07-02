import { getMandatoryHeaders, getPhpBaseUrl } from '@apps/shell/src/server/utils';
import { shellFetch, HeadersInit } from '@apps/shell/src/server/services/shellFetch';
import { type ChildMiddlewareType } from './concurrentMiddlewareExecutor';
import { ShellError } from '@apps/shell/src/server/utils/error-utils';
import { SHELL_EXTERNAL_API_ROUTES } from '../../configs';

type OptimizedUserFetchMiddleware = () => ChildMiddlewareType;

/** Try fetching user, if there's a response, then session is authenticated
 *  else makes relevant redirects
 */
export const optimizedUserFetchMiddleware: OptimizedUserFetchMiddleware =
  () =>
  async (req, res): Promise<void> => {
    req.shellLogger.info({
      message: `Optimized User data fetch started...`,
      moduleName: '@optimizedUserFetchMiddleware',
    });

    const dashboardBackendBaseUrl = getPhpBaseUrl(req);
    let dashboardBackendRequestId: any;

    const isUserNonCachedCall = req.cookies?.['user_cache_invalidate'] === '1';

    const userOptimizedUrl = `${dashboardBackendBaseUrl}${
      isUserNonCachedCall
        ? SHELL_EXTERNAL_API_ROUTES.NON_CACHED_USER_OPTIMIZED
        : SHELL_EXTERNAL_API_ROUTES.USER_OPTIMIZED
    }`;

    /**
     * Cookie `user_cache_invalidate=1` bypasses cache by appending `skip_cached_data=1` on /user route
     * to fetch fresh user data.
     */

    if (isUserNonCachedCall) {
      req.shellLogger.info({
        message: `Non-cached user data fetch initiated.`,
        moduleName: '@optimizedUserFetchMiddleware',
        context: {
          isUserNonCachedCall,
          path: SHELL_EXTERNAL_API_ROUTES.NON_CACHED_USER_OPTIMIZED,
        },
      });
    }

    // &payouts=0&credits=0
    return await shellFetch(userOptimizedUrl, {
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
            moduleName: '@optimizedUserFetchMiddleware',
            context: {
              dashboardBackendRequestId,
            },
          });
        } else {
          req.shellLogger.warn({
            message: `No Set-Cookie headers found.`,
            moduleName: '@optimizedUserFetchMiddleware',
            context: {
              dashboardBackendRequestId,
            },
          });
        }

        const { status } = response;
        req.shellLogger.info({
          message: `Optimized User data fetch status: ${response.status}`,
          moduleName: '@optimizedUserFetchMiddleware',
          context: {
            dashboardBackendRequestId,
          },
        });
        if (status === 200) {
          return response.json();
        } else {
          throw new ShellError({
            moduleName: '@optimizedUserFetchMiddleware',
            message: `Optimized User data fetch failed.`,
            statusCode: status,
            context: {
              dashboardBackendRequestId,
              path: SHELL_EXTERNAL_API_ROUTES.USER_OPTIMIZED,
            },
          });
        }
      })
      .then((response) => {
        const { data, success } = response as any;
        if (success) {
          res.locals.user = data;
          req.shellLogger.success({
            message: `Optimized User data fetched.`,
            moduleName: '@optimizedUserFetchMiddleware',
            context: {
              currentUserMid: res.locals.user.current,
              dashboardBackendRequestId,
            },
          });
          return;
        } else {
          req.shellLogger.warn({
            message: `Optimized User data fetched. But Invalid.`,
            moduleName: '@optimizedUserFetchMiddleware',
            context: {
              response,
              dashboardBackendRequestId,
            },
          });
          throw new ShellError({
            moduleName: '@optimizedUserFetchMiddleware',
            message: `Invalid API Response`,
            context: {
              dashboardBackendRequestId,
              path: SHELL_EXTERNAL_API_ROUTES.USER_OPTIMIZED,
            },
          });
        }
      })
      .catch((error) => {
        req.shellLogger.error({
          message: 'Fallback to empty user object',
          moduleName: '@optimizedUserFetchMiddleware',
          context: {
            dashboardBackendRequestId,
            path: SHELL_EXTERNAL_API_ROUTES.USER_OPTIMIZED,
          },
          error,
          sentry: false,
        });
        res.locals.user = {};
      });
  };
