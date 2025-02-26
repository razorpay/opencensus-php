import { getMandatoryHeaders, getPhpBaseUrl } from '@apps/shell/src/server/utils';
import { shellFetch, HeadersInit } from '@apps/shell/src/server/services/shellFetch';
import { type ChildMiddlewareType } from './concurrentMiddlewareExecutor';
import { ShellError } from '@apps/shell/src/server/utils/error-utils';
import { SHELL_EXTERNAL_API_ROUTES } from '../../configs';

type MerchantConfigStoreFetchMiddleware = () => ChildMiddlewareType;

/** Try fetching user, if there's a response, then session is authenticated
 *  else makes relevant redirects
 */
export const merchantConfigStoreFetchMiddleware: MerchantConfigStoreFetchMiddleware =
  () =>
  async (req, res): Promise<void> => {
    req.shellLogger.info({
      message: `Merchant config store data fetch started...`,
      moduleName: '@merchantConfigStoreFetchMiddleware',
    });

    if (!Boolean(res.locals.user_session?.merchant_id)) {
      req.shellLogger.warn({
        message: 'Fallback to empty merchant config store object (MID Not Found).',
        moduleName: '@merchantConfigStoreFetchMiddleware',
      });
      res.locals.config_store = {};
      return;
    }

    const dashboardBackendBaseUrl = getPhpBaseUrl(req);
    let dashboardBackendRequestId: any;

    return await shellFetch(
      `${dashboardBackendBaseUrl}${SHELL_EXTERNAL_API_ROUTES.MERCHANT_CONFIG_STORE_LIVE_ONBOARDING}`,
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
            moduleName: '@merchantConfigStoreFetchMiddleware',
            context: {
              dashboardBackendRequestId,
            },
          });
        } else {
          req.shellLogger.warn({
            message: `No Set-Cookie headers found.`,
            moduleName: '@merchantConfigStoreFetchMiddleware',
            context: {
              dashboardBackendRequestId,
            },
          });
        }

        const { status } = response;
        req.shellLogger.info({
          message: `Merchant config store data fetch status: ${response.status}`,
          moduleName: '@merchantConfigStoreFetchMiddleware',
          context: {
            dashboardBackendRequestId,
          },
        });
        if (status === 200) {
          return response.json();
        } else {
          throw new ShellError({
            moduleName: '@merchantConfigStoreFetchMiddleware',
            message: `Merchant config store data fetch failed.`,
            statusCode: status,
            context: {
              dashboardBackendRequestId,
            },
          });
        }
      })
      .then(async (response) => {
        const { data, success } = response as any;
        if (success) {
          res.locals.config_store = data;
          req.shellLogger.success({
            message: `Merchant config store data fetched.`,
            moduleName: '@merchantConfigStoreFetchMiddleware',
            context: {
              dashboardBackendRequestId,
            },
          });
          return;
        } else {
          throw new ShellError({
            moduleName: '@merchantConfigStoreFetchMiddleware',
            message: `Invalid API Response`,
            context: {
              dashboardBackendRequestId,
              response,
            },
          });
        }
      })
      .catch((error) => {
        req.shellLogger.warn({
          message: 'Something went wrong!',
          moduleName: '@merchantConfigStoreFetchMiddleware',
          context: {
            dashboardBackendRequestId,
            error,
          },
        });
        res.locals.config_store = {};
      });
  };
