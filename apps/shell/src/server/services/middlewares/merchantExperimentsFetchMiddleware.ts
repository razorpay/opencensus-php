import { getMandatoryHeaders, getPhpBaseUrl } from '@apps/shell/src/server/utils';
import { shellFetch, HeadersInit } from '@apps/shell/src/server/services/shellFetch';
import { type ChildMiddlewareType } from './concurrentMiddlewareExecutor';
import { ShellError } from '@apps/shell/src/server/utils/error-utils';
import { SHELL_EXTERNAL_API_ROUTES } from '../../configs';

type MerchantExperimentsFetchMiddleware = () => ChildMiddlewareType;

/** Try fetching user, if there's a response, then session is authenticated
 *  else makes relevant redirects
 */
export const merchantExperimentsFetchMiddleware: MerchantExperimentsFetchMiddleware =
  () =>
  async (req, res): Promise<void> => {
    req.shellLogger.info({
      message: `Merchant Experiments data fetch started...`,
      moduleName: '@merchantExperimentsFetchMiddleware',
    });

    if (!Boolean(res.locals.user_session?.merchant_id)) {
      req.shellLogger.warn({
        message: 'Fallback to empty merchant_experiments object',
        moduleName: '@merchantExperimentsFetchMiddleware',
      });
      res.locals.merchant_experiments = {};
      return;
    }

    const dashboardBackendBaseUrl = getPhpBaseUrl(req);
    let dashboardBackendRequestId: any;

    return await shellFetch(
      `${dashboardBackendBaseUrl}${SHELL_EXTERNAL_API_ROUTES.MERCHANT_EXPERIMENTS}`,
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
            moduleName: '@merchantExperimentsFetchMiddleware',
            context: {
              dashboardBackendRequestId,
            },
          });
        } else {
          req.shellLogger.warn({
            message: `No Set-Cookie headers found.`,
            moduleName: '@merchantExperimentsFetchMiddleware',
            context: {
              dashboardBackendRequestId,
            },
          });
        }

        const { status } = response;
        req.shellLogger.info({
          message: `Merchant Experiments data fetch status: ${response.status}`,
          moduleName: '@merchantExperimentsFetchMiddleware',
          context: {
            dashboardBackendRequestId,
          },
        });
        if (status === 200) {
          return response.json();
        } else {
          throw new ShellError({
            moduleName: '@merchantExperimentsFetchMiddleware',
            message: `Merchant Experiments data fetch failed.`,
            statusCode: status,
            context: {
              path: SHELL_EXTERNAL_API_ROUTES.MERCHANT_EXPERIMENTS,
              dashboardBackendRequestId,
            },
          });
        }
      })
      .then((response) => {
        const { data, success } = response as any;
        if (success) {
          res.locals.merchant_experiments = data;
          req.shellLogger.success({
            message: `Merchant Experiments data fetched.`,
            moduleName: '@merchantExperimentsFetchMiddleware',
            context: {
              merchant_experiments_length: Object.keys(res?.locals?.merchant_experiments || {})
                .length,
              dashboardBackendRequestId,
            },
          });
          return;
        } else {
          throw new ShellError({
            moduleName: '@merchantExperimentsFetchMiddleware',
            message: `Invalid API Response`,
            context: {
              path: SHELL_EXTERNAL_API_ROUTES.MERCHANT_EXPERIMENTS,
              dashboardBackendRequestId,
            },
          });
        }
      });
  };
