import { getMandatoryHeaders, getPhpBaseUrl } from '@apps/shell/src/server/utils';
import { shellFetch, HeadersInit } from '@apps/shell/src/server/services/shellFetch';
import { type ChildMiddlewareType } from './concurrentMiddlewareExecutor';
import { ShellError } from '@apps/shell/src/server/utils/error-utils';
import { SHELL_EXTERNAL_API_ROUTES } from '../../configs';

type MerchantDetailsFetchMiddleware = () => ChildMiddlewareType;

/** Try fetching user, if there's a response, then session is authenticated
 *  else makes relevant redirects
 */
export const merchantDetailsFetchMiddleware: MerchantDetailsFetchMiddleware =
  () =>
  async (req, res): Promise<void> => {
    req.shellLogger.info({
      message: `Merchant Details data fetch started...`,
      moduleName: '@merchantDetailsFetchMiddleware',
    });

    if (!Boolean(res.locals.user_session?.merchant_id)) {
      req.shellLogger.warn({
        message: 'Fallback to empty merchant details',
        moduleName: '@merchantDetailsFetchMiddleware',
      });
      res.locals.merchant_details = {};
      return;
    }

    const dashboardBackendBaseUrl = getPhpBaseUrl(req);
    let dashboardBackendRequestId: any;

    return await shellFetch(
      `${dashboardBackendBaseUrl}${SHELL_EXTERNAL_API_ROUTES.MERCHANT_DETAILS}`,
      {
        method: 'GET',
        headers: getMandatoryHeaders(req) as unknown as HeadersInit,
      },
    )
      .then((response) => {
        const setCookies = response.headers.raw()['set-cookie'];
        dashboardBackendRequestId = response.headers.get('x-request-id');

        if (setCookies) {
          res.locals['x-set-cookie'] = setCookies;
          // Store cookies for forwarding to subsequent middleware and API calls
          req.forward_cookies = setCookies;

          req.shellLogger.info({
            message: `Set-Cookie headers retrieved and stored in locals.`,
            moduleName: '@merchantDetailsFetchMiddleware',
            context: {
              dashboardBackendRequestId,
            },
          });
        } else {
          req.shellLogger.warn({
            message: `No Set-Cookie headers found.`,
            moduleName: '@merchantDetailsFetchMiddleware',
            context: {
              dashboardBackendRequestId,
            },
          });
        }

        const { status } = response;
        req.shellLogger.info({
          message: `Merchant Details data fetch status: ${response.status}`,
          moduleName: '@merchantDetailsFetchMiddleware',
          context: {
            dashboardBackendRequestId,
          },
        });
        if (status === 200) {
          return response.json();
        } else {
          throw new ShellError({
            moduleName: '@merchantDetailsFetchMiddleware',
            message: `Merchant Details data fetch failed.`,
            statusCode: status,
            context: {
              path: SHELL_EXTERNAL_API_ROUTES.MERCHANT_DETAILS,
              dashboardBackendRequestId,
            },
          });
        }
      })
      .then((response) => {
        const { data, success } = response as any;
        if (success) {
          res.locals.merchant_details = data;
          req.shellLogger.success({
            message: `Merchant Details data fetched.`,
            moduleName: '@merchantDetailsFetchMiddleware',
            context: {
              dashboardBackendRequestId,
            },
          });
          return;
        } else {
          throw new ShellError({
            moduleName: '@merchantDetailsFetchMiddleware',
            message: `Invalid API Response`,
            context: {
              path: SHELL_EXTERNAL_API_ROUTES.MERCHANT_DETAILS,
              dashboardBackendRequestId,
              response,
            },
          });
        }
      });
  };
