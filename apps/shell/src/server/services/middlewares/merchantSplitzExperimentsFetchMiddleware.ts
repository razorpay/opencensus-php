import { getMandatoryHeaders, getPhpBaseUrl } from '@apps/shell/src/server/utils';
import { shellFetch, HeadersInit } from '@apps/shell/src/server/services/shellFetch';
import { type ChildMiddlewareType } from './concurrentMiddlewareExecutor';
import { getErrorMessage, ShellError } from '@apps/shell/src/server/utils/error-utils';
import { SHELL_EXTERNAL_API_ROUTES } from '../../configs';

type MerchantSplitzExperimentsFetchMiddleware = () => ChildMiddlewareType;

/** Try fetching user, if there's a response, then session is authenticated
 *  else makes relevant redirects
 */
export const merchantSplitzExperimentsFetchMiddleware: MerchantSplitzExperimentsFetchMiddleware =
  () =>
  async (req, res): Promise<void> => {
    req.shellLogger.info({
      message: `Merchant Splitz Experiments data fetch started...`,
      moduleName: '@merchantSplitzExperimentsFetchMiddleware',
    });

    if (!Boolean(res.locals.user_session?.merchant_id)) {
      req.shellLogger.warn({
        message: 'Fallback to empty merchant_splitz_experiments object',
        moduleName: '@merchantSplitzExperimentsFetchMiddleware',
      });
      res.locals.merchant_splitz_experiments = {};
      return;
    }

    const dashboardBackendBaseUrl = getPhpBaseUrl(req);
    let dashboardBackendRequestId: any;

    return await shellFetch(
      `${dashboardBackendBaseUrl}${SHELL_EXTERNAL_API_ROUTES.MERCHANT_SPLITZ_EXPERIMENTS}`,
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
          req.shellLogger.info({
            message: `Set-Cookie headers retrieved and stored in locals.`,
            moduleName: '@merchantSplitzExperimentsFetchMiddleware',
            context: {
              dashboardBackendRequestId,
            },
          });
        } else {
          req.shellLogger.warn({
            message: `No Set-Cookie headers found.`,
            moduleName: '@merchantSplitzExperimentsFetchMiddleware',
            context: {
              dashboardBackendRequestId,
            },
          });
        }

        const { status } = response;
        req.shellLogger.info({
          message: `Merchant Splitz Experiments data fetch status: ${response.status}`,
          moduleName: '@merchantSplitzExperimentsFetchMiddleware',
          context: {
            dashboardBackendRequestId,
          },
        });
        if (status === 200) {
          return response.json();
        } else {
          throw new ShellError({
            moduleName: '@merchantSplitzExperimentsFetchMiddleware',
            message: `Merchant Splitz Experiments data fetch failed.`,
            statusCode: status,
            context: {
              path: SHELL_EXTERNAL_API_ROUTES.MERCHANT_SPLITZ_EXPERIMENTS,
              dashboardBackendRequestId,
            },
          });
        }
      })
      .then((response) => {
        const { data, success } = response as any;
        if (success) {
          res.locals.merchant_splitz_experiments = data;
          req.shellLogger.success({
            message: `Merchant Splitz Experiments data fetched.`,
            moduleName: '@merchantSplitzExperimentsFetchMiddleware',
            context: {
              merchant_splitz_experiments_length: Object.keys(
                res?.locals?.merchant_splitz_experiments || {},
              ).length,
              dashboardBackendRequestId,
            },
          });
          return;
        } else {
          throw new ShellError({
            moduleName: '@merchantSplitzExperimentsFetchMiddleware',
            message: `Invalid API Response`,
            context: {
              path: SHELL_EXTERNAL_API_ROUTES.MERCHANT_SPLITZ_EXPERIMENTS,
              dashboardBackendRequestId,
              response,
            },
          });
        }
      });
  };
