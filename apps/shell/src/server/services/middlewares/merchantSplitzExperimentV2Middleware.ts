import { getMandatoryHeaders, getPhpBaseUrl } from '@apps/shell/src/server/utils';
import { shellFetch, HeadersInit } from '@apps/shell/src/server/services/shellFetch';
import { type ChildMiddlewareType } from './concurrentMiddlewareExecutor';
import { ShellError } from '@apps/shell/src/server/utils/error-utils';
import { SHELL_EXTERNAL_API_ROUTES } from '../../configs';

type MerchantSplitzExperimentV2Middleware = () => ChildMiddlewareType;

/** Try fetching user, if there's a response, then session is authenticated
 *  else makes relevant redirects
 */
export const merchantSplitzExperimentV2Middleware: MerchantSplitzExperimentV2Middleware =
  () =>
  async (req, res): Promise<void> => {
    req.shellLogger.info({
      message: `Merchant splitz experiment v2 fetch started...`,
      moduleName: '@merchantSplitzExperimentV2Middleware',
    });

    const dashboardBackendBaseUrl = getPhpBaseUrl(req);
    let dashboardBackendRequestId: any;

    return await shellFetch(
      `${dashboardBackendBaseUrl}${SHELL_EXTERNAL_API_ROUTES.MERCHANT_SPLITZ_EXPERIMENTS_V2}`,
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
            moduleName: '@merchantSplitzExperimentV2Middleware',
            context: {
              dashboardBackendRequestId,
            },
          });
        } else {
          req.shellLogger.warn({
            message: `No Set-Cookie headers found.`,
            moduleName: '@merchantSplitzExperimentV2Middleware',
            context: {
              dashboardBackendRequestId,
            },
          });
        }

        const { status } = response;
        req.shellLogger.info({
          message: `Merchant splitz experiment v2 fetch status: ${response.status}`,
          moduleName: '@merchantSplitzExperimentV2Middleware',
          context: {
            dashboardBackendRequestId,
          },
        });
        if (status === 200) {
          return response.json();
        } else {
          throw new ShellError({
            moduleName: '@merchantSplitzExperimentV2Middleware',
            message: `Merchant splitz experiment v2 fetch failed.`,
            statusCode: status,
            context: {
              path: SHELL_EXTERNAL_API_ROUTES.MERCHANT_SPLITZ_EXPERIMENTS_V2,
              dashboardBackendRequestId,
            },
          });
        }
      })
      .then(async (response) => {
        const { data, success } = response as any;
        if (success) {
          res.locals.splitz_experiments_v2 = data;
          req.shellLogger.success({
            message: `Merchant splitz experiment v2 fetched.`,
            moduleName: '@merchantSplitzExperimentV2Middleware',
            context: {
              dashboardBackendRequestId,
            },
          });
          return;
        } else {
          throw new ShellError({
            moduleName: '@merchantSplitzExperimentV2Middleware',
            message: `Invalid API Response`,
            context: {
              path: SHELL_EXTERNAL_API_ROUTES.MERCHANT_SPLITZ_EXPERIMENTS_V2,
              dashboardBackendRequestId,
            },
          });
        }
      })
      .catch((error) => {
        req.shellLogger.error({
          message: 'Fallback to empty object for splitz_experiments_v2',
          moduleName: '@merchantSplitzExperimentV2Middleware',
          context: {
            dashboardBackendRequestId,
          },
          sentry: false,
          error,
        });
        res.locals.splitz_experiments_v2 = {};
      });
  };
